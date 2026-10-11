// Every page built from a pattern (preview:seed) must open in the block editor
// with no invalid blocks ("This block contains unexpected or invalid content"),
// so the team can edit it (§14.2). Needs the local Docker stack and seeded pages.
import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { site } from './helpers.js';

const ADMIN = { user: process.env.WP_ADMIN_USER || 'admin', pass: process.env.WP_ADMIN_PASS || 'admin' };

function wp(name, args) {
  return execFileSync('docker', ['compose', 'exec', '-T', '-u', 'www-data', `wp-${name}`, 'wp', ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }).trim();
}

/** IDs of pages whose content is block markup from our patterns. */
function patternPages(name) {
  const ids = wp(name, ['post', 'list', '--post_type=page,service,studio_service', '--post_status=publish,draft', '--format=ids']).split(/\s+/).filter(Boolean);
  return ids.filter((id) => wp(name, ['post', 'get', id, '--field=post_content']).includes('<!-- wp:techdella/'));
}

test.describe.configure({ mode: 'serial' });
test.skip(({ browserName, isMobile }) => isMobile || browserName !== 'chromium', 'Editor check runs once, on desktop Chromium.');

for (const name of ['marketing', 'journal', 'studios']) {
  test(`${name}: pattern pages have no invalid blocks in the editor`, async ({ page }) => {
    test.setTimeout(10 * 60 * 1000);
    await page.goto(site(name, '/wp-login.php'));
    await page.fill('#user_login', ADMIN.user);
    await page.fill('#user_pass', ADMIN.pass);
    await Promise.all([page.waitForURL(/wp-admin/), page.click('#wp-submit')]);

    const bad = [];
    for (const id of patternPages(name)) {
      await page.goto(site(name, `/wp-admin/post.php?post=${id}&action=edit`));
      await page.waitForFunction(() => window.wp?.data?.select('core/block-editor')?.getBlocks().length > 0, null, { timeout: 60000 }); // the editor boots slowly while the other worker runs
      const invalid = await page.evaluate(() => {
        const out = [];
        const walk = (blocks) => blocks.forEach((b) => { if (!b.isValid) out.push(b.name); walk(b.innerBlocks || []); });
        walk(window.wp.data.select('core/block-editor').getBlocks());
        return out;
      });
      if (invalid.length) bad.push(`#${id}: ${[...new Set(invalid)].join(', ')}`);
    }
    expect(bad).toEqual([]);
  });
}
