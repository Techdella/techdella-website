// Responsive image pipeline (§6.1): assets/images/**  →  theme assets/img/**
// AVIF + WebP + JPEG/PNG fallback at 480/768/1200/1600/2400 (never upscaled),
// plus img/manifest.json, which td_picture() reads for width/height/srcset.
import { readdirSync, mkdirSync, existsSync, statSync, writeFileSync, readFileSync, copyFileSync } from 'node:fs';
import { join, relative, dirname, extname, basename } from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const ROOT = fileURLToPath(new URL('..', import.meta.url));
const SRC = join(ROOT, 'assets/images');
const OUT = join(ROOT, 'wp-content/themes/techdella/assets/img');
const WIDTHS = [480, 768, 1200, 1600, 2400];
const MANIFEST = join(OUT, 'manifest.json');

const walk = (dir) =>
  readdirSync(dir, { withFileTypes: true }).flatMap((d) =>
    d.isDirectory() ? walk(join(dir, d.name)) : /\.(jpe?g|png|webp)$/i.test(d.name) ? [join(dir, d.name)] : [],
  );

const previous = existsSync(MANIFEST) ? JSON.parse(readFileSync(MANIFEST, 'utf8')) : {};
const manifest = {};
let made = 0;

for (const file of walk(SRC)) {
  const key = relative(SRC, file).replace(extname(file), '');
  const mtime = statSync(file).mtimeMs | 0;
  const meta = await sharp(file).metadata();
  const alpha = Boolean(meta.hasAlpha);
  const fallback = alpha ? 'png' : 'jpg';
  const widths = WIDTHS.filter((w) => w < meta.width);
  widths.push(Math.min(meta.width, 2400));
  const uniq = [...new Set(widths)];
  const entry = { width: meta.width, height: meta.height, alpha, fallback, widths: uniq, mtime };
  manifest[key] = entry;

  const outDir = join(OUT, dirname(key));
  const name = basename(key);
  const fresh = previous[key]?.mtime === mtime && uniq.every((w) => existsSync(join(outDir, `${name}-${w}.${fallback}`)));
  if (fresh) continue;

  mkdirSync(outDir, { recursive: true });
  for (const w of uniq) {
    const base = sharp(file).resize({ width: w, withoutEnlargement: true });
    await base.clone().avif({ quality: 55, effort: 4 }).toFile(join(outDir, `${name}-${w}.avif`));
    await base.clone().webp({ quality: 74 }).toFile(join(outDir, `${name}-${w}.webp`));
    if (alpha) await base.clone().png({ compressionLevel: 9, palette: true }).toFile(join(outDir, `${name}-${w}.png`));
    else await base.clone().jpeg({ quality: 78, mozjpeg: true, progressive: true }).toFile(join(outDir, `${name}-${w}.jpg`));
  }
  made++;
}

// Platform logos (SVG/PNG) are copied as-is; td_platform_badge() looks for them here.
const LOGOS = join(ROOT, 'assets/logos/platforms');
if (existsSync(LOGOS)) {
  mkdirSync(join(OUT, 'logos/platforms'), { recursive: true });
  for (const f of readdirSync(LOGOS).filter((n) => /\.(svg|png|webp)$/i.test(n))) {
    copyFileSync(join(LOGOS, f), join(OUT, 'logos/platforms', f));
  }
}

mkdirSync(OUT, { recursive: true });
writeFileSync(MANIFEST, JSON.stringify(manifest, null, 2));
console.log(`  ✓ images: ${Object.keys(manifest).length} sources, ${made} regenerated`);
