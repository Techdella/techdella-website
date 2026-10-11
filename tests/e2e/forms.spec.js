// Forms → Startbuddi (§11, §16 "All 10 forms reach sb-mock with the right payload. The no-JS fallback works.").
// Runs against the local stack; sb-mock (:8090) records every forward.
import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { site, sbMock, expectNoA11yViolations, expectNoHorizontalScroll } from './helpers.js';

test.describe.configure({ mode: 'serial' });
// The sb-mock log is shared: two projects clearing and reading it at once race.
// Run once, on desktop (the 390px checks set their own viewport).
test.skip(({ isMobile }) => isMobile, 'Shares the sb-mock log; runs on desktop only.');

const SITE_OF = {
  contact: 'marketing', audit: 'marketing', studios_brief: 'studios', newsletter: 'journal', lead_magnet: 'marketing',
  tool_gate: 'marketing', popup_exit: 'marketing', popup_scroll: 'journal', mvp_calc: 'studios', guest_post: 'journal',
};

// A valid payload per key, and the fields we expect to see forwarded (Startbuddi names).
const VALID = {
  contact: { name: 'Ada Obi', email: 'ada@example.com', company: 'Example Ltd', website: 'example.com', service_interest: ['seo', 'paid'], budget: '2000-5000', message: 'We need more demos.' },
  audit: { name: 'Ada Obi', email: 'ada@example.com', company: 'Example Ltd', website: 'https://example.com', goals: ['traffic', 'ai'], monthly_budget: '2000-5000', notes: 'Notes', consent_marketing: '1' },
  studios_brief: { project_type: ['mvp'], stage: 'sketches', budget: '5k-15k', timeline: 'quarter', name: 'Ada Obi', email: 'ada@example.com', company: 'Example', details: 'A booking app.' },
  newsletter: { email: 'ada@example.com', first_name: 'Ada', topic_interest: 'seo', consent_marketing: '1' },
  lead_magnet: { email: 'ada@example.com', first_name: 'Ada', company: 'Example', resource_slug: 'ai-search-readiness-checklist', consent_marketing: '1' },
  tool_gate: { email: 'ada@example.com', tool_slug: 'utm-builder', result_summary: '3 links built' },
  popup_exit: { email: 'ada@example.com', offer: 'growth-audit-worksheet', page: '/services/seo/' },
  popup_scroll: { email: 'ada@example.com', offer: 'newsletter', post_slug: 'what-is-ai-search-optimization' },
  mvp_calc: { email: 'ada@example.com', inputs: JSON.stringify({ screens: 12, platform: 'web' }), estimate: '$12,000 – $18,000' },
  guest_post: { name: 'Ada Obi', email: 'ada@example.com', topic: 'Lead scoring', outline: 'Three points.', samples: 'https://example.com/a' },
};
const EXPECT = {
  contact: { service_interest: ['seo', 'paid'], website: 'https://example.com', budget: '2000-5000' },
  audit: { goals: ['traffic', 'ai'], monthly_budget: '2000-5000', website: 'https://example.com' },
  studios_brief: { project_type: ['mvp'], budget: '5k-15k', timeline: 'quarter', stage: 'sketches' },
  newsletter: { topic_interest: 'seo', first_name: 'Ada' },
  lead_magnet: { resource_slug: 'ai-search-readiness-checklist' },
  tool_gate: { tool_slug: 'utm-builder', result_summary: '3 links built' },
  popup_exit: { offer: 'growth-audit-worksheet', page: '/services/seo/' },
  popup_scroll: { post_slug: 'what-is-ai-search-optimization' },
  mvp_calc: { estimate_inputs: { screens: 12, platform: 'web' }, estimate: '$12,000 – $18,000' },
  guest_post: { topic: 'Lead scoring', samples: 'https://example.com/a' },
};
const CONSENT = { audit: true, newsletter: true, lead_magnet: true };
const DOMAIN = { marketing: 'techdella.com', journal: 'blog.techdella.com', studios: 'studios.techdella.com' };

/** Run WP-CLI on one site. */
function wp(name, ...args) {
  return execFileSync('docker', ['compose', 'exec', '-T', '-u', 'www-data', `wp-${name}`, 'wp', ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
}
const resetLimits = () => Object.keys(DOMAIN).forEach((s) => wp(s, 'td', 'forms:reset-limits'));
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

/** Token the same way the browser gets one (GET /form-token on first interaction). */
async function token(request, key) {
  const r = await request.get(site(SITE_OF[key], `/wp-json/techdella/v1/form-token?form=${key}`));
  expect(r.ok()).toBeTruthy();
  return (await r.json()).token;
}
function submit(request, key, data, headers = {}) {
  return request.post(site(SITE_OF[key], `/wp-json/techdella/v1/submit/${key}`), {
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...headers },
    data,
    maxRedirects: 0,
  });
}

const pages = {};
test.beforeAll(() => {
  const content = {
    marketing: '<!-- wp:techdella/form {"formKey":"audit"} /--><!-- wp:techdella/form {"formKey":"lead_magnet","hidden":{"resource_slug":"ai-search-readiness-checklist"}} /-->',
    studios: '<!-- wp:techdella/form {"formKey":"studios_brief"} /-->',
  };
  for (const [s, c] of Object.entries(content)) {
    const existing = wp(s, 'post', 'list', '--post_type=page', '--name=forms-test', '--field=ID').trim();
    if (existing) wp(s, 'post', 'delete', ...existing.split(/\s+/), '--force');
    pages[s] = wp(s, 'post', 'create', '--post_type=page', '--post_status=publish', '--post_name=forms-test', '--post_title=Forms test', `--post_content=${c}`, '--porcelain').trim();
  }
  for (const s of Object.keys(DOMAIN)) wp(s, 'eval', 'global $wpdb; td_forms_install_table(); $wpdb->query( "DELETE FROM " . td_forms_queue_table() );');
});
test.afterAll(() => {
  for (const [s, id] of Object.entries(pages)) if (id) wp(s, 'post', 'delete', id, '--force');
});
test.beforeEach(async ({ request }) => {
  resetLimits();
  await sbMock.clear(request);
});

test('every form delivers the right payload to sb-mock @smoke', async ({ request }) => {
  const keys = Object.keys(VALID);
  const tokens = {};
  for (const k of keys) tokens[k] = await token(request, k);
  await sleep(3200); // min-time

  for (const key of keys) {
    const s = SITE_OF[key];
    const res = await submit(request, key, {
      ...VALID[key], td_token: tokens[key], td_page: site(s, '/some-page/?utm_source=x'), td_ref: 'https://www.google.com/',
    }, { Cookie: `td_utm_first=${encodeURIComponent('{"source":"google","medium":"organic"}')}; td_utm_last=${encodeURIComponent('{"source":"linkedin","medium":"paid","campaign":"q4-aeo"}')}` });
    const body = await res.json();
    expect(res.status(), `${key}: ${JSON.stringify(body)}`).toBe(200);
    expect(body).toMatchObject({ ok: true, redirect: expect.stringContaining(`/thank-you/${key.replace(/_/g, '-')}/`) });

    const log = await sbMock.log(request, key);
    expect(log, key).toHaveLength(1);
    const p = log[0].body;
    expect(log[0].headers['content-type']).toContain('application/json');
    expect(log[0].headers['idempotency-key']).toMatch(/^[0-9a-f-]{36}$/);
    expect(p.form).toBe(key);
    expect(p.email).toBe('ada@example.com');
    expect(p).toMatchObject(EXPECT[key]);
    expect(p).not.toHaveProperty('td_token');
    expect(p).not.toHaveProperty('consent_marketing');
    expect(p.meta).toMatchObject({
      site: DOMAIN[s],
      page_url: site(s, '/some-page/?utm_source=x'),
      referrer: 'https://www.google.com/',
      utm_first: { source: 'google', medium: 'organic' },
      utm_last: { source: 'linkedin', medium: 'paid', campaign: 'q4-aeo' },
      consent_marketing: !!CONSENT[key],
    });
    expect(p.meta.submitted_at).toMatch(/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\dZ$/);
  }
});

test('validation errors return 400 with field errors and nothing is forwarded', async ({ request }) => {
  const t = await token(request, 'audit');
  await sleep(3200);
  const res = await submit(request, 'audit', { td_token: t, email: 'not-an-email', website: 'nope', goals: ['hack'] });
  expect(res.status()).toBe(400);
  const body = await res.json();
  expect(body.ok).toBe(false);
  expect(Object.keys(body.errors)).toEqual(expect.arrayContaining(['email', 'website', 'name', 'goals']));
  expect(await sbMock.log(request, 'audit')).toHaveLength(0);
});

test('bots are rejected: honeypot, <3 s, bad token, foreign origin', async ({ request }) => {
  const fast = await token(request, 'newsletter');
  let res = await submit(request, 'newsletter', { ...VALID.newsletter, td_token: fast });
  expect(res.status()).toBe(403);
  expect((await res.json()).code).toBe('too_fast');

  await sleep(3200);
  res = await submit(request, 'newsletter', { ...VALID.newsletter, td_token: fast, td_hp: 'http://spam.example' });
  expect(res.status()).toBe(403);
  expect((await res.json()).code).toBe('bot');

  res = await submit(request, 'newsletter', { ...VALID.newsletter, td_token: fast.replace(/.$/, (c) => (c === 'a' ? 'b' : 'a')) });
  expect((await res.json()).code).toBe('token');

  res = await submit(request, 'newsletter', { ...VALID.newsletter, td_token: await token(request, 'audit') });
  expect(res.status()).toBe(403); // token bound to another form

  res = await submit(request, 'newsletter', { ...VALID.newsletter, td_token: fast }, { Origin: 'https://evil.example' });
  expect((await res.json()).code).toBe('origin');

  expect(await sbMock.log(request, 'newsletter')).toHaveLength(0);
});

test('rate limit: the 6th rapid submission gets 429', async ({ request }) => {
  const t = await token(request, 'guest_post');
  await sleep(3200);
  const statuses = [];
  for (let i = 0; i < 6; i++) statuses.push((await submit(request, 'guest_post', { td_token: t })).status());
  expect(statuses).toEqual([400, 400, 400, 400, 400, 429]);
});

test('Startbuddi down → queued (ok for the visitor), then forms:retry delivers it', async ({ request }) => {
  await sbMock.fail(request, 'contact', 1, 503);
  const t = await token(request, 'contact');
  await sleep(3200);
  const res = await submit(request, 'contact', { ...VALID.contact, td_token: t });
  expect(res.status()).toBe(202);
  expect(await res.json()).toMatchObject({ ok: true, queued: true, redirect: expect.stringContaining('/thank-you/contact/') });

  const rows = JSON.parse(wp('marketing', 'td', 'forms:queue', '--format=json'));
  expect(rows).toHaveLength(1);
  expect(rows[0]).toMatchObject({ form_key: 'contact', status: 'pending', last_error: 'http_503' });
  expect(JSON.stringify(rows)).not.toContain('ada@example.com');
  // Stored encrypted, never in plain text.
  const raw = wp('marketing', 'eval', 'global $wpdb; echo $wpdb->get_var( "SELECT payload FROM " . td_forms_queue_table() );');
  expect(raw).not.toContain('ada@');

  wp('marketing', 'td', 'forms:retry', '--all');
  expect(JSON.parse(wp('marketing', 'td', 'forms:queue', '--format=json'))).toHaveLength(0);
  const log = await sbMock.log(request, 'contact');
  expect(log).toHaveLength(2);
  expect(log[0].failed_with).toBe(503);
  expect(log[1].failed_with).toBeUndefined();
  expect(log[1].headers['idempotency-key']).toBe(log[0].headers['idempotency-key']);
  expect(log[1].body).toMatchObject({ form: 'contact', email: 'ada@example.com' });
});

test.describe('no-JS fallback', () => {
  test.use({ javaScriptEnabled: false });

  test('plain POST lands on /thank-you/audit/ (noindex, generate_lead)', async ({ page, request }) => {
    await page.goto(site('marketing', '/forms-test/'));
    const f = page.locator('#tdf-audit');
    await f.getByLabel('Website').fill('example.com');
    await f.getByLabel('Full name').fill('Ada Obi');
    await f.getByLabel('Work email').fill('ada@example.com');
    await f.getByRole('checkbox', { name: 'Not showing up in AI search' }).check();
    await page.waitForTimeout(3200);
    await f.getByRole('button', { name: 'Request my audit' }).click();
    await expect(page).toHaveURL(/\/thank-you\/audit\/$/);
    await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', /noindex/);
    expect(await page.content()).toContain('"event":"generate_lead","form_key":"audit"');
    const log = await sbMock.log(request, 'audit');
    expect(log).toHaveLength(1);
    expect(log[0].body).toMatchObject({ form: 'audit', goals: ['ai'], website: 'https://example.com', meta: { page_url: site('marketing', '/forms-test/') } });
  });

  test('server-side errors come back on the form, with no PII in the URL', async ({ page, request }) => {
    await page.goto(site('marketing', '/forms-test/'));
    const f = page.locator('#tdf-audit');
    await f.getByLabel('Website').fill('not a url');
    await f.getByLabel('Full name').fill('Ada Obi');
    await f.getByLabel('Work email').fill('ada@example.com');
    await f.getByRole('checkbox', { name: 'Competitors outrank us' }).check();
    await page.waitForTimeout(3200);
    await f.getByRole('button', { name: 'Request my audit' }).click();
    await expect(page).toHaveURL(/\/forms-test\/\?td_fe=/);
    expect(page.url()).not.toContain('ada');
    await expect(f.getByLabel('Website')).toHaveAttribute('aria-invalid', 'true');
    await expect(f.locator('.td-field__error:not([hidden])')).toContainText('valid web address');
    await expect(f.getByLabel('Work email')).toHaveValue('ada@example.com'); // old input kept
    expect(await sbMock.log(request, 'audit')).toHaveLength(0);
  });
});

test('JS: inline validation, pills, in-place success with focus', async ({ page, request }) => {
  await page.goto(site('marketing', '/forms-test/'));
  const f = page.locator('#tdf-audit');
  const email = f.getByLabel('Work email');
  await email.fill('nope');
  await email.blur();
  await expect(email).toHaveAttribute('aria-invalid', 'true');
  const describedBy = (await email.getAttribute('aria-describedby')).split(' ');
  await expect(page.locator(`#${describedBy.at(-1)}`)).toBeVisible();

  await f.getByRole('button', { name: 'Request my audit' }).click();
  await expect(f.getByLabel('Website')).toBeFocused();

  await f.getByLabel('Website').fill('example.com');
  await f.getByLabel('Full name').fill('Ada Obi');
  await email.fill('ada@example.com');
  const pill = f.getByRole('checkbox', { name: 'Ads aren’t paying back' });
  await pill.focus();
  await page.keyboard.press('Space');
  await expect(pill).toBeChecked();
  await f.getByRole('button', { name: 'Request my audit' }).click();
  const success = f.locator('.td-form__success');
  await expect(success).toBeVisible({ timeout: 10_000 });
  await expect(success).toBeFocused();
  const lead = await page.evaluate(() => (window.dataLayer || []).find((e) => e.event === 'generate_lead'));
  expect(lead).toEqual({ event: 'generate_lead', form_key: 'audit' });
  const log = await sbMock.log(request, 'audit');
  expect(log).toHaveLength(1);
  expect(log[0].body).toMatchObject({ goals: ['ads'], meta: { page_url: site('marketing', '/forms-test/'), consent_marketing: false } });
});

test('JS: Studios brief steps through four questions', async ({ page, request }) => {
  await page.goto(site('studios', '/forms-test/'));
  const f = page.locator('#tdf-studios-brief');
  await expect(f.getByText('Step 1 of 4')).toBeVisible();
  await expect(f.getByRole('button', { name: 'Send brief' })).toBeHidden();
  await f.getByRole('button', { name: 'Continue' }).click();
  await expect(f.locator('[data-field="project_type"] .td-field__error')).toBeVisible();
  await f.getByRole('checkbox', { name: /AI automation/ }).check();
  await f.getByRole('button', { name: 'Continue' }).click();
  await f.getByRole('radio', { name: '$2,000 – $5,000' }).check();
  await f.getByRole('button', { name: 'Continue' }).click();
  await f.getByRole('radio', { name: 'ASAP' }).check();
  await f.getByRole('button', { name: 'Continue' }).click();
  await expect(f.getByText('Step 4 of 4')).toBeVisible();
  await expect(f.locator('.td-form__summary')).toContainText('AI automation');
  await f.getByLabel('The idea, in a few sentences').fill('An agent that triages support email.');
  await f.getByLabel('Name', { exact: true }).fill('Ada Obi');
  await f.getByLabel('Email', { exact: true }).fill('ada@example.com');
  await f.getByRole('button', { name: 'Send brief' }).click();
  await expect(f.locator('.td-form__success')).toBeFocused({ timeout: 10_000 });
  const log = await sbMock.log(request, 'studios_brief');
  expect(log[0].body).toMatchObject({ project_type: ['ai'], budget: '2k-5k', timeline: 'asap', stage: '' });
});

test('form markup is axe-clean, also with errors shown, and fits 390px', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto(site('marketing', '/forms-test/'));
  await expectNoA11yViolations(page, { include: '.td-form' });
  await page.locator('#tdf-audit').getByRole('button', { name: 'Request my audit' }).click();
  await expect(page.locator('#tdf-audit .td-field__error:not([hidden])').first()).toBeVisible();
  await expectNoA11yViolations(page, { include: '.td-form' });
  await expectNoHorizontalScroll(page);
  await page.goto(site('studios', '/forms-test/'));
  await expectNoA11yViolations(page, { include: '.td-form' });
  await expectNoHorizontalScroll(page);
});
