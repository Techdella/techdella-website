// Admin + content model (§7, §14): dashboard, grouped menus, publishing gates,
// live URL bases, roles. Needs the local Docker stack (WP-CLI via docker compose).
import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { site, wpLogin } from './helpers.js';

const ADMIN = { user: process.env.WP_ADMIN_USER || 'admin', pass: process.env.WP_ADMIN_PASS || 'admin' };

/** Run WP-CLI in a site's container; returns trimmed stdout. */
function wp(name, args) {
  return execFileSync('docker', ['compose', 'exec', '-T', '-u', 'www-data', `wp-${name}`, 'wp', ...args], {
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
  }).trim();
}

const login = (page, name, user = ADMIN.user, pass = ADMIN.pass) => wpLogin(page, name, user, pass);

/** REST nonce for the logged-in user of this page's context. */
async function restNonce(page, name) {
  const res = await page.request.get(site(name, '/wp-admin/admin-ajax.php?action=rest-nonce'));
  expect(res.ok()).toBeTruthy();
  return (await res.text()).trim();
}

test.describe.configure({ mode: 'serial' });

test.beforeAll(() => {
  for (const name of ['marketing', 'journal', 'studios']) {
    wp(name, ['eval', 'td_sync_roles();']);
    wp(name, ['rewrite', 'flush']);
  }
});

test('dashboard shows the TechDella HQ widgets', async ({ page }) => {
  await login(page, 'marketing');
  await page.goto(site('marketing', '/wp-admin/'));
  for (const id of ['td_hq_main', 'td_hq_review', 'td_hq_refresh', 'td_hq_published', 'td_hq_health']) {
    await expect(page.locator(`#${id}`)).toBeVisible();
  }
  await expect(page.locator('#td_hq_main h2')).toContainText('TechDella HQ');
  await expect(page.locator('#td_hq_main')).toContainText('Leads & CRM live in Startbuddi');
  await expect(page.locator('#td_hq_main')).toContainText('Add a glossary term');
  // Default widgets are gone.
  await expect(page.locator('#dashboard_activity, #dashboard_quick_press, #dashboard_primary')).toHaveCount(0);
  await expect(page.locator('#td_hq_health')).toContainText('PageSpeed');
});

const MENUS = {
  marketing: { has: ['Content', 'Growth', 'Brand'], not: ['Studios', 'Comments', 'Posts'] },
  journal: { has: ['Content', 'Growth', 'Brand'], not: ['Studios', 'Comments', 'Posts'] },
  studios: { has: ['Content', 'Growth', 'Studios', 'Brand'], not: ['Comments', 'Posts'] },
};

for (const [name, { has, not }] of Object.entries(MENUS)) {
  test(`grouped admin menu on ${name}`, async ({ page }) => {
    await login(page, name);
    await page.goto(site(name, '/wp-admin/'));
    const tops = (await page.locator('#adminmenu > li.menu-top .wp-menu-name').allInnerTexts()).map((t) => t.replace(/\s*\d+$/, '').trim());
    for (const label of has) expect(tops).toContain(label);
    for (const label of not) expect(tops).not.toContain(label);
    if (name === 'marketing') {
      const growth = page.locator('#adminmenu > li.menu-top', { has: page.locator('.wp-menu-name', { hasText: /^Growth$/ }) });
      await expect(growth.locator('.wp-submenu')).toContainText('Services');
      await expect(growth.locator('.wp-submenu')).toContainText('Solutions');
      const content = page.locator('#adminmenu > li.menu-top', { has: page.locator('.wp-menu-name', { hasText: /^Content$/ }) });
      await expect(content.locator('.wp-submenu')).toContainText('Glossary');
      await expect(content.locator('.wp-submenu')).toContainText('Case studies');
    }
  });
}

test('help page lists the how-tos', async ({ page }) => {
  await login(page, 'marketing');
  await page.goto(site('marketing', '/wp-admin/admin.php?page=td-help'));
  await expect(page.locator('h1')).toHaveText('How to');
  await expect(page.locator('#case-study')).toContainText('permission');
});

test('a case study cannot be published without permission_confirmed (REST)', async ({ page }) => {
  await login(page, 'marketing');
  const nonce = await restNonce(page, 'marketing');
  const headers = { 'X-WP-Nonce': nonce };
  const url = site('marketing', '/wp-json/wp/v2/case_study');

  const refused = await page.request.post(url, { headers, data: { title: 'E2E case study', status: 'publish' } });
  expect(refused.status()).toBe(403);
  expect((await refused.json()).code).toBe('td_permission_required');

  const draft = await page.request.post(url, { headers, data: { title: 'E2E case study', status: 'draft' } });
  expect(draft.status()).toBe(201);
  const { id } = await draft.json();
  try {
    const again = await page.request.post(`${url}/${id}`, { headers, data: { status: 'publish', meta: { permission_confirmed: false } } });
    expect(again.status()).toBe(403);
    // WP-CLI path is gated too: stays a draft.
    wp('marketing', ['post', 'update', String(id), '--post_status=publish', '--user=admin']);
    expect(wp('marketing', ['post', 'get', String(id), '--field=post_status'])).toBe('draft');
  } finally {
    wp('marketing', ['post', 'delete', String(id), '--force']);
  }
});

test('publishing a guide needs the pre-publish checklist (REST)', async ({ page }) => {
  await login(page, 'journal');
  const headers = { 'X-WP-Nonce': await restNonce(page, 'journal') };
  const res = await page.request.post(site('journal', '/wp-json/wp/v2/posts'), {
    headers,
    data: { title: 'E2E guide', content: '<!-- wp:paragraph --><p>Hello [CONFIRM]</p><!-- /wp:paragraph -->', status: 'publish' },
  });
  expect(res.status()).toBe(403);
  const body = await res.json();
  expect(body.code).toBe('td_checklist_failed');
  expect(Object.keys(body.data.failures)).toEqual(expect.arrayContaining(['meta_description', 'internal_links', 'placeholders']));
});

test('documents are not in REST for anonymous users', async ({ request }) => {
  const res = await request.get(site('marketing', '/wp-json/wp/v2/document'));
  expect([401, 403]).toContain(res.status());
});

const LIVE_BASES = [
  ['service', '/services/'],
  ['solution', '/solution/'],
  ['location', '/locations/'],
  ['em-glossary', '/glossary/'],
  ['td_tool', '/tools/'],
  ['business', '/business/'],
];

test('CPTs resolve on their live URL bases', async ({ request }) => {
  const ids = [];
  try {
    for (const [type, base] of LIVE_BASES) {
      const slug = `td-e2e-${type.replace(/[^a-z]/g, '')}`;
      const id = wp('marketing', ['post', 'create', `--post_type=${type}`, `--post_title=E2E ${type}`, `--post_name=${slug}`, '--post_status=draft', '--porcelain']);
      ids.push(id);
      // Draft → publish as an admin with the logged checklist override (test content only).
      wp('marketing', ['post', 'update', id, '--post_status=publish', '--meta_input={"_td_checklist_override":"1"}', '--user=admin']);
      expect(wp('marketing', ['post', 'get', id, '--field=post_status'])).toBe('publish');
      const res = await request.get(site('marketing', `${base}${slug}/`), { maxRedirects: 0 });
      expect(res.status(), `${type} at ${base}${slug}/`).toBe(200);
    }
    // The business archive keeps its paging URLs.
    expect((await request.get(site('marketing', '/business/'), { maxRedirects: 0 })).status()).toBe(200);
  } finally {
    for (const id of ids) wp('marketing', ['post', 'delete', id, '--force']);
  }
});

test('a Writer cannot publish', async ({ browser }) => {
  const login_ = 'td-e2e-writer';
  const pass = 'td-e2e-Writer-pass-1';
  try {
    wp('journal', ['user', 'delete', login_, '--yes']);
  } catch {
    // Not there yet.
  }
  wp('journal', ['user', 'create', login_, 'td-e2e-writer@example.com', '--role=td_writer', `--user_pass=${pass}`]);
  const context = await browser.newContext();
  const page = await context.newPage();
  try {
    await login(page, 'journal', login_, pass);
    const headers = { 'X-WP-Nonce': await restNonce(page, 'journal') };
    const url = site('journal', '/wp-json/wp/v2/posts');
    const refused = await page.request.post(url, { headers, data: { title: 'E2E writer post', status: 'publish' } });
    expect(refused.status()).toBe(403);
    expect((await refused.json()).code).toBe('rest_cannot_publish');
    const pending = await page.request.post(url, { headers, data: { title: 'E2E writer post', status: 'pending' } });
    expect(pending.status()).toBe(201);
    // No publish caps on the role at all.
    expect(wp('journal', ['eval', 'echo user_can( get_user_by( "login", "td-e2e-writer" ), "publish_posts" ) ? "yes" : "no";'])).toBe('no');
  } finally {
    await context.close();
    wp('journal', ['user', 'delete', login_, '--yes']);
  }
});
