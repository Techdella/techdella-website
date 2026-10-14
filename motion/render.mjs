#!/usr/bin/env node
/**
 * Motion samples (§6.3): render each HTML scene in motion/scenes/ to video.
 *
 * Every scene animates with CSS keyframes only. This script opens the scene in Chromium at each
 * output size, pauses every animation, seeks them all to each frame's time
 * (document.getAnimations()), screenshots the frame, and encodes with ffmpeg:
 *   assets/video/<slug>-<format>.mp4   H.264, CRF 23, yuv420p, faststart, no audio
 *   assets/video/<slug>-<format>.webm  VP9, CRF 34, no audio
 *   assets/video/<slug>-<format>.jpg   poster (the scene's `poster` time)
 *   assets/video/<slug>.vtt            captions, when the scene has on-screen text
 * Formats: tall 1080×1920 (Reels/TikTok), square 1080×1080, wide 1920×1080.
 *
 * Fails (exit 1) when a looping web video (`"loop": true`) is 2.5 MB or more (§6.3).
 *
 * Usage:
 *   npm run motion:render                         all scenes, all formats
 *   npm run motion:render -- --only mo-01,mo-05   some scenes
 *   npm run motion:render -- --format wide        one format
 *   npm run motion:render -- --preview            contact sheet of 8 frames per scene/format
 *                                                 (motion/out/preview/) instead of video
 */

import { chromium } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { mkdir, mkdtemp, readdir, readFile, rm, stat, writeFile, copyFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(HERE, '..');
const SCENES = path.join(HERE, 'scenes');
const OUT = path.join(ROOT, 'assets', 'video');
const PREVIEW = path.join(HERE, 'out', 'preview');
const LOOP_BUDGET = 2.5 * 1024 * 1024;
const FORMATS = { tall: [1080, 1920], square: [1080, 1080], wide: [1920, 1080] };

const args = process.argv.slice(2);
const opt = (name) => {
	const i = args.indexOf(`--${name}`);
	return i >= 0 ? args[i + 1] : null;
};
const only = (opt('only') || '').split(',').filter(Boolean).map((s) => s.toLowerCase());
const formats = opt('format') ? opt('format').split(',') : Object.keys(FORMATS);
const preview = args.includes('--preview');

const files = (await readdir(SCENES)).filter((f) => f.endsWith('.html') && (!only.length || only.some((o) => f.startsWith(o)))).sort();
if (!files.length) {
	console.error('No scenes matched.');
	process.exit(1);
}

const sharp = (await import('sharp')).default;
const fmtTime = (t) => {
	const ms = Math.round(t * 1000);
	const h = String(Math.floor(ms / 3600000)).padStart(2, '0');
	const m = String(Math.floor((ms % 3600000) / 60000)).padStart(2, '0');
	const s = String(Math.floor((ms % 60000) / 1000)).padStart(2, '0');
	return `${h}:${m}:${s}.${String(ms % 1000).padStart(3, '0')}`;
};

await mkdir(preview ? PREVIEW : OUT, { recursive: true });
const browser = await chromium.launch();
const report = [];
const errors = [];

for (const file of files) {
	const slug = file.replace(/\.html$/, '');
	const html = await readFile(path.join(SCENES, file), 'utf8');
	const metaMatch = html.match(/<script type="application\/json" id="scene-meta">([\s\S]*?)<\/script>/);
	if (!metaMatch) {
		errors.push(`${slug}: missing #scene-meta`);
		continue;
	}
	const meta = JSON.parse(metaMatch[1]);
	const fps = meta.fps || 30;
	const frames = Math.round(meta.duration * fps);

	for (const format of formats) {
		const [width, height] = FORMATS[format];
		const page = await browser.newPage({ viewport: { width, height }, deviceScaleFactor: 1 });
		await page.goto(pathToFileURL(path.join(SCENES, file)).href, { waitUntil: 'load' });
		await page.evaluate(() => document.fonts.ready);
		await page.evaluate(() => document.getAnimations().forEach((a) => a.pause()));
		const seek = (t) => page.evaluate((ms) => {
			document.getAnimations().forEach((a) => {
				a.currentTime = ms;
			});
		}, t * 1000);

		if (preview) {
			const times = Array.from({ length: 8 }, (_, i) => (meta.duration * i) / 8 + meta.duration / 16);
			const tiles = [];
			for (const t of times) {
				await seek(t);
				tiles.push(await sharp(await page.screenshot({ type: 'png' })).resize({ width: Math.round(width / 4) }).toBuffer());
			}
			const tw = Math.round(width / 4);
			const th = Math.round(height / 4);
			await sharp({ create: { width: tw * 4, height: th * 2, channels: 3, background: '#ffffff' } })
				.composite(tiles.map((input, i) => ({ input, left: (i % 4) * tw, top: Math.floor(i / 4) * th })))
				.jpeg({ quality: 70 })
				.toFile(path.join(PREVIEW, `${slug}-${format}.jpg`));
			console.log(`preview ${slug} ${format}`);
			await page.close();
			continue;
		}

		const dir = await mkdtemp(path.join(tmpdir(), `td-motion-${slug}-`));
		for (let f = 0; f < frames; f++) {
			await seek(f / fps);
			await page.screenshot({ path: path.join(dir, `${String(f).padStart(5, '0')}.jpg`), type: 'jpeg', quality: 92 });
		}
		await page.close();

		const base = path.join(OUT, `${slug}-${format}`);
		const input = ['-y', '-loglevel', 'error', '-framerate', String(fps), '-i', path.join(dir, '%05d.jpg')];
		execFileSync('ffmpeg', [...input, '-c:v', 'libx264', '-crf', '23', '-preset', 'slow', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', '-an', `${base}.mp4`]);
		execFileSync('ffmpeg', [...input, '-c:v', 'libvpx-vp9', '-crf', '34', '-b:v', '0', '-row-mt', '1', '-deadline', 'good', '-cpu-used', '4', '-pix_fmt', 'yuv420p', '-an', `${base}.webm`]);
		const posterFrame = Math.min(frames - 1, Math.round((meta.poster ?? meta.duration / 2) * fps));
		await sharp(path.join(dir, `${String(posterFrame).padStart(5, '0')}.jpg`)).jpeg({ quality: 80, mozjpeg: true }).toFile(`${base}.jpg`);
		await rm(dir, { recursive: true, force: true });

		const mp4 = (await stat(`${base}.mp4`)).size;
		const webm = (await stat(`${base}.webm`)).size;
		const row = { id: meta.id, slug, format, width, height, seconds: meta.duration, loop: !!meta.loop, mp4_mb: +(mp4 / 1048576).toFixed(2), webm_mb: +(webm / 1048576).toFixed(2) };
		report.push(row);
		console.log(`${meta.id.padEnd(7)} ${slug.padEnd(32)} ${format.padEnd(6)} mp4 ${String(row.mp4_mb).padStart(5)} MB  webm ${String(row.webm_mb).padStart(5)} MB`);
		if (meta.loop && Math.max(mp4, webm) >= LOOP_BUDGET) {
			errors.push(`${slug}-${format}: ${row.mp4_mb} MB mp4 / ${row.webm_mb} MB webm is over the 2.5 MB web-loop budget (§6.3)`);
		}
	}

	if (!preview && Array.isArray(meta.captions) && meta.captions.length) {
		const cues = meta.captions.map(([a, b, text], i) => `${i + 1}\n${fmtTime(a)} --> ${fmtTime(b)}\n${text}\n`);
		await writeFile(path.join(OUT, `${slug}.vtt`), `WEBVTT\n\n${cues.join('\n')}`);
	}
}

await browser.close();
if (!preview) {
	// Merge with the last report, so rendering one scene keeps the others' rows.
	let previous = [];
	try {
		previous = JSON.parse(await readFile(path.join(OUT, 'report.json'), 'utf8')).videos || [];
	} catch {
		previous = [];
	}
	const key = (r) => `${r.slug}-${r.format}`;
	const fresh = new Set(report.map(key));
	const videos = [...previous.filter((r) => !fresh.has(key(r))), ...report].sort((a, b) => key(a).localeCompare(key(b)));
	await writeFile(path.join(OUT, 'report.json'), JSON.stringify({ generated_at: new Date().toISOString(), videos }, null, '\t'));
	// The README travels with the renders so whoever uploads them knows what's what.
	await copyFile(path.join(HERE, 'README.md'), path.join(OUT, 'README.md')).catch(() => {});
}
if (errors.length) {
	console.error(`\n${errors.join('\n')}`);
	process.exit(1);
}
