// Video block (§6.3): uses a real render (MO-05, wide) uploaded to the local Media Library.
// Loops: muted, no autoplay attribute, preload none, play only in view, pause button,
// poster only with reduced motion. VideoObject in the schema graph. Needs the Docker stack
// and `npm run motion:render -- --only mo-05 --format wide` (skipped otherwise).
import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { site, expectNoA11yViolations } from './helpers.js';

const BASE = 'assets/video/mo-05-workflow';
const FILES = [`${BASE}-wide.mp4`, `${BASE}-wide.webm`, `${BASE}-wide.jpg`, `${BASE}.vtt`];
const rendered = FILES.every((f) => existsSync(f));

const wp = (args) => execFileSync('docker', ['compose', 'exec', '-T', '-u', 'www-data', 'wp-marketing', 'wp', ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }).trim();

test.describe.configure({ mode: 'serial' });
test.skip(({ isMobile }) => isMobile, 'Runs once, on desktop.');
test.skip(!rendered, 'Render MO-05 first: npm run motion:render -- --only mo-05 --format wide');

let ids = [];
let pageId = '';
let url = '';

test.beforeAll(() => {
  if (!rendered) return;
  ids = FILES.map((f) => wp(['media', 'import', `/var/www/repo/${f}`, '--porcelain']));
  const [mp4, webm, poster, vtt] = ids.map(Number);
  const attrs = JSON.stringify({ mp4, webm, poster, captions: vtt, mode: 'loop', ratio: '16:9', title: 'How a new lead is handled', duration: 10, label: 'Example', caption: 'Form to CRM to email to Slack.' });
  const spacer = '<!-- wp:paragraph --><p>Spacer</p><!-- /wp:paragraph -->'.repeat(1);
  pageId = wp(['post', 'create', '--post_type=page', '--post_status=publish', '--post_title=E2E video', '--post_name=e2e-video', `--post_content=${spacer}<!-- wp:techdella/video ${attrs} /-->`, '--porcelain']);
  url = wp(['post', 'url', pageId]).replace(/^https?:\/\/[^/]+/, '');
});

test.afterAll(() => {
  if (pageId) wp(['post', 'delete', pageId, '--force']);
  if (ids.length) wp(['post', 'delete', ...ids, '--force']);
});

test('loop markup: muted, no autoplay, preload none, poster, sources, captions', async ({ page }) => {
  await page.goto(site('marketing', url));
  const video = page.locator('video[data-td-video="loop"]');
  await expect(video).toHaveCount(1);
  await expect(video).toHaveAttribute('preload', /none|auto/);
  // Loop posters are the frame background (lazy via content-visibility), not the poster attribute.
  await expect(page.locator('.td-video__frame').first()).toHaveAttribute('style', /mo-05-workflow-wide/);
  await expect(video).toHaveAttribute('aria-label', 'How a new lead is handled');
  expect(await video.evaluate((v) => [v.muted, v.loop, v.hasAttribute('autoplay'), v.hasAttribute('controls')])).toEqual([true, true, false, false]);
  await expect(video.locator('source')).toHaveCount(2);
  await expect(video.locator('track[kind="captions"]')).toHaveAttribute('src', /\.vtt$/);
  await expect(page.locator('.td-video .td-label-example')).toHaveText('Example');
  await expectNoA11yViolations(page, { include: '.td-video' });
});

test('plays in view; the pause button pauses and resumes', async ({ page }) => {
  await page.goto(site('marketing', url));
  const video = page.locator('video[data-td-video="loop"]');
  await video.scrollIntoViewIfNeeded();
  await expect.poll(() => video.evaluate((v) => !v.paused && v.currentTime > 0), { timeout: 10_000 }).toBe(true);
  const toggle = page.locator('[data-td-video-toggle]');
  await expect(toggle).toBeVisible();
  await toggle.click();
  await expect(toggle).toHaveAttribute('aria-pressed', 'true');
  await expect(toggle).toHaveAccessibleName('Play video');
  expect(await video.evaluate((v) => v.paused)).toBe(true);
  await toggle.click();
  await expect.poll(() => video.evaluate((v) => !v.paused)).toBe(true);
});

test('reduced motion: poster only, never plays', async ({ browser }) => {
  const context = await browser.newContext({ reducedMotion: 'reduce' });
  const page = await context.newPage();
  await page.goto(site('marketing', url));
  const video = page.locator('video[data-td-video="loop"]');
  await video.scrollIntoViewIfNeeded();
  await page.waitForTimeout(1500);
  expect(await video.evaluate((v) => [v.paused, v.currentTime])).toEqual([true, 0]);
  await expect(page.locator('[data-td-video-toggle]')).toBeHidden();
  await context.close();
});

test('VideoObject in the schema graph', async ({ page }) => {
  await page.goto(site('marketing', url));
  const graphs = await page.locator('script[type="application/ld+json"]').allTextContents();
  const nodes = graphs.flatMap((g) => JSON.parse(g)['@graph'] || []);
  const video = nodes.find((n) => n['@type'] === 'VideoObject');
  expect(video).toBeTruthy();
  expect(video.name).toBe('How a new lead is handled');
  expect(video.contentUrl).toMatch(/mo-05-workflow-wide(-\d+)?\.mp4$/);
  expect(video.thumbnailUrl).toMatch(/mo-05-workflow-wide(-\d+)?\.jpg$/);
  expect(video.duration).toBe('PT10S');
  expect(video.uploadDate).toBeTruthy();
});

test('scene videos from td_videos: paid ads row of three Example clips', async ({ page }) => {
  await page.goto(site('marketing', '/services/paid-ads/'));
  const clips = page.locator('.td-video-section .td-video-row .td-video');
  await expect(clips).toHaveCount(3);
  for (const clip of await clips.all()) {
    await expect(clip.locator('source[type="video/mp4"]')).toHaveAttribute('src', /-tall\.mp4$/);
    // The renders carry their own Example badge; the caption says it in text too.
    await expect(clip.locator('figcaption')).toHaveText(/^Example · /);
  }
  await expect(page.locator('.td-video-section h2')).toContainText('Creative in');
});
