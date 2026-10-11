// Per-block CSS is inlined only when a block renders through render_block (inc/blocks.php).
// Templates that print block markup by hand must call td_block_css(). This crawls every
// sitemap URL on the three sites and fails when a block's markup appears without its CSS.
import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import { SITES } from './helpers.js';

const BUILD = 'wp-content/themes/techdella/assets/build/blocks';
const ROOTS = fs.existsSync(BUILD)
  ? fs.readdirSync(BUILD).filter((f) => f.endsWith('.css')).map((f) => f.slice(0, -4))
    .filter((slug) => fs.readFileSync(`${BUILD}/${slug}.css`, 'utf8').includes(`.td-${slug}`))
  : [];

test.skip(({ isMobile }) => isMobile, 'Markup check; runs once.');

test('every block on every page has its CSS', async ({ request }) => {
  test.setTimeout(5 * 60 * 1000);
  expect(ROOTS.length).toBeGreaterThan(20); // build present
  const urls = new Set();
  for (const base of Object.values(SITES)) {
    const index = await (await request.get(`${base}/sitemap_index.xml`)).text();
    for (const sm of index.match(/<loc>[^<]+<\/loc>/g) || []) {
      const xml = await (await request.get(sm.slice(5, -6))).text();
      (xml.match(/<loc>[^<]+<\/loc>/g) || []).forEach((u) => urls.add(u.slice(5, -6)));
    }
    ['/', '/no-such-page/'].forEach((p) => urls.add(base + p));
  }
  const missing = [];
  for (const url of urls) {
    if (/\.(jpe?g|png|webp|avif|pdf)$/i.test(url)) continue;
    const html = await (await request.get(url)).text();
    const styles = (html.match(/<style[^>]*>[\s\S]*?<\/style>/g) || []).join('');
    for (const slug of ROOTS) {
      const used = new RegExp(`class="[^"]*\\btd-${slug}(?:\\s|"|__|--)`).test(html);
      // Styled if any inline <style> on the page (core, block CSS) defines the root class.
      if (used && !styles.includes(`.td-${slug}`)) missing.push(`${slug} on ${url}`);
    }
  }
  expect(missing).toEqual([]);
});
