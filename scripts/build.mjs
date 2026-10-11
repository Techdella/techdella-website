// Theme asset build (§2.3, §8.4): one CSS bundle + one vanilla JS bundle.
//
//   node scripts/build.mjs           build once
//   node scripts/build.mjs --watch   rebuild on change
//   node scripts/build.mjs --check   build to memory and enforce the budgets only
//
// CSS order: tokens → base → components (assets/css/*.css) → every block's style.css.
// JS: assets/js/main.js + every block's view.js, bundled as one IIFE.
import { readFileSync, writeFileSync, mkdirSync, readdirSync, existsSync, watch, statSync } from 'node:fs';
import { join, relative } from 'node:path';
import { gzipSync } from 'node:zlib';
import { fileURLToPath } from 'node:url';
import * as esbuild from 'esbuild';
import { transform, browserslistToTargets } from 'lightningcss';

const ROOT = fileURLToPath(new URL('..', import.meta.url));
const THEME = join(ROOT, 'wp-content/themes/techdella');
const OUT = join(THEME, 'assets/build');
const args = new Set(process.argv.slice(2));
const CHECK = args.has('--check');

// §2.3 / §8.4 budgets (gzipped bytes).
const BUDGET = { 'main.js': 25 * 1024, 'main.css': 45 * 1024, 'core.css': 20 * 1024 };

// Last two years of evergreen browsers, roughly "> 0.5%, not dead".
const targets = browserslistToTargets(['chrome 109', 'edge 109', 'firefox 115', 'safari 15.4', 'ios_saf 15.4']);

const blockDirs = () =>
  readdirSync(join(THEME, 'blocks'), { withFileTypes: true })
    .filter((d) => d.isDirectory())
    .map((d) => join(THEME, 'blocks', d.name))
    .sort();

const cssFiles = (dir) =>
  existsSync(dir) ? readdirSync(dir).filter((f) => f.endsWith('.css')).sort().map((f) => join(dir, f)) : [];

// Core CSS (inlined in <head> on every page): everything except per-block styles.
function coreCssSources() {
  return cssSources().filter((f) => !f.includes('/blocks/'));
}

function cssSources() {
  const css = join(THEME, 'assets/css');
  const first = ['tokens.css', 'fonts.css', 'base.css', 'layout.css'].map((f) => join(css, f)).filter(existsSync);
  const rest = cssFiles(css).filter((f) => !first.includes(f) && !f.endsWith('editor.css'));
  const blocks = blockDirs().map((d) => join(d, 'style.css')).filter(existsSync);
  // Front-end CSS that lives with a core-plugin module (markup printed by the plugin).
  const core = ['seo/breadcrumbs.css'].map((f) => join(ROOT, 'wp-content/mu-plugins/techdella-core', f)).filter(existsSync);
  return [...first, ...rest, ...blocks, ...core];
}

function buildCss(files, name) {
  const src = files.map((f) => `/* ${relative(ROOT, f)} */\n${readFileSync(f, 'utf8')}`).join('\n');
  const { code, warnings } = transform({
    filename: name,
    code: Buffer.from(src),
    minify: true,
    targets,
    errorRecovery: false,
  });
  warnings.forEach((w) => console.warn(`  ⚠ ${name}: ${w.message}`));
  return code;
}

async function buildJs(entries, name) {
  const contents = entries.map((f) => `import ${JSON.stringify(f)};`).join('\n');
  const result = await esbuild.build({
    stdin: { contents, resolveDir: THEME, sourcefile: name },
    bundle: true,
    minify: true,
    format: 'iife',
    target: ['es2020'],
    write: false,
    legalComments: 'none',
  });
  return Buffer.from(result.outputFiles[0].contents);
}

function report(name, buf) {
  const gz = gzipSync(buf).length;
  const budget = BUDGET[name];
  const over = budget && gz > budget;
  console.log(`  ${over ? '✗' : '✓'} ${name.padEnd(12)} ${(buf.length / 1024).toFixed(1).padStart(6)} KB  ${(gz / 1024).toFixed(1).padStart(5)} KB gz${budget ? `  (budget ${budget / 1024} KB)` : ''}`);
  return !over;
}

async function build() {
  const t = Date.now();
  const outputs = {};
  // main.css: the whole bundle (editor canvas + fallback). core.css + blocks/<name>.css: front end,
  // where core.css is inlined in <head> and each block's CSS is inlined once before its first use.
  outputs['main.css'] = buildCss(cssSources(), 'main.css');
  outputs['core.css'] = buildCss(coreCssSources(), 'core.css');
  for (const d of blockDirs()) {
    const f = join(d, 'style.css');
    if (existsSync(f)) outputs[`blocks/${d.split('/').pop()}.css`] = buildCss([f], `${d.split('/').pop()}.css`);
  }
  const editorCss = join(THEME, 'assets/css/editor.css');
  outputs['editor.css'] = buildCss([join(THEME, 'assets/css/tokens.css'), join(THEME, 'assets/css/fonts.css'), ...(existsSync(editorCss) ? [editorCss] : [])].filter(existsSync), 'editor.css');

  const views = blockDirs().map((d) => join(d, 'view.js')).filter(existsSync);
  outputs['main.js'] = await buildJs([join(THEME, 'assets/js/main.js'), ...views].filter(existsSync), 'main.js');
  const editorJs = join(THEME, 'assets/js/editor.js');
  if (existsSync(editorJs)) outputs['editor.js'] = await buildJs([editorJs], 'editor.js');

  let ok = true;
  for (const [name, buf] of Object.entries(outputs)) if (!name.startsWith('blocks/')) ok = report(name, buf) && ok;
  console.log(`  ✓ blocks/*.css   ${Object.keys(outputs).filter((n) => n.startsWith('blocks/')).length} per-block files`);
  if (!CHECK) {
    mkdirSync(join(OUT, 'blocks'), { recursive: true });
    for (const [name, buf] of Object.entries(outputs)) writeFileSync(join(OUT, name), buf);
    writeFileSync(
      join(OUT, 'manifest.json'),
      JSON.stringify(Object.fromEntries(Object.keys(outputs).map((n) => [n, { version: String(statSync(join(OUT, n)).mtimeMs | 0) }])), null, 2),
    );
  }
  console.log(`  built in ${Date.now() - t} ms`);
  return ok;
}

const ok = await build().catch((e) => { console.error(e.message || e); return false; });
if (args.has('--watch')) {
  let timer;
  console.log('  watching wp-content/themes/techdella …');
  watch(THEME, { recursive: true }, (_e, file) => {
    if (!file || file.startsWith('assets/build') || !/\.(css|js)$/.test(file)) return;
    clearTimeout(timer);
    timer = setTimeout(() => build().catch((e) => console.error(e.message || e)), 80);
  });
} else if (!ok) {
  console.error('  Budget exceeded (§8.4).');
  process.exit(1);
}
