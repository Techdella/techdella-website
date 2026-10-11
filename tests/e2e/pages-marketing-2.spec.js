// Phase 3 Marketing templates (worker T3): case studies, solutions, locations, glossary,
// resources, tools, About, Book a call, legal pages, 404.
// Relies on the local fixtures (see the T3 report) or real content at these URLs.
import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { site, expectNoA11yViolations, expectNoHorizontalScroll } from './helpers.js';

// The feature-grid "01–04" numbers are aria-hidden decoration (WCAG 1.4.3 exempts pure
// decoration); axe still flags their contrast. Reported to the integrator (block owner).
const AXE = {};

const PAGES = [
  { path: '/case-studies/', schema: 'CollectionPage' },
  { path: '/case-studies/paycape/', schema: 'Article' },
  { path: '/solution/fintech-marketing-agency/', schema: 'Service' },
  { path: '/solution/saas-marketing-agency/', schema: 'Service' },
  { path: '/locations/', schema: 'CollectionPage' },
  { path: '/locations/nigeria/', schema: 'Service' },
  { path: '/glossary/', schema: 'DefinedTermSet' },
  { path: '/glossary/aeo/', schema: 'DefinedTerm' },
  { path: '/resources/', schema: 'CollectionPage' },
  { path: '/resources/ai-search-readiness-checklist/', schema: 'DigitalDocument' },
  { path: '/tools/', schema: 'CollectionPage', ads: true },
  { path: '/tools/free-utm-parameter-generator/', schema: 'SoftwareApplication', ads: true },
  { path: '/about-us/', schema: 'AboutPage' },
  { path: '/book-a-discovery-call/', schema: null },
  { path: '/privacy-policy/', schema: null },
  { path: '/terms/', schema: null },
  { path: '/cookies/', schema: null },
  { path: '/disclaimer/', schema: null },
];

/** All @type values in the page's JSON-LD. */
async function schemaTypes(page) {
  return page.$$eval('script[type="application/ld+json"]', (nodes) => {
    const types = new Set();
    const walk = (v) => {
      if (Array.isArray(v)) v.forEach(walk);
      else if (v && typeof v === 'object') {
        if (v['@type']) [].concat(v['@type']).forEach((t) => types.add(t));
        Object.values(v).forEach(walk);
      }
    };
    nodes.forEach((n) => { try { walk(JSON.parse(n.textContent)); } catch { /* ignore */ } });
    return [...types];
  });
}

for (const p of PAGES) {
  test(`marketing ${p.path}: 200, one H1, schema, ads rule, a11y @mobile`, async ({ page }) => {
    const res = await page.goto(site('marketing', p.path));
    expect(res.status()).toBe(200);
    await expect(page.locator('h1')).toHaveCount(1);
    if (p.schema) expect(await schemaTypes(page)).toContain(p.schema);
    expect(await schemaTypes(page)).not.toContain('FAQPage');
    const ads = await page.locator('[data-td-ad]').count();
    if (p.ads) expect(ads).toBeGreaterThan(0);
    else expect(ads).toBe(0);
    expect(ads).toBeLessThanOrEqual(3);
    await expectNoHorizontalScroll(page);
    await expectNoA11yViolations(page, AXE);
  });
}

test('404 is helpful: search, links, audit CTA @smoke', async ({ page }) => {
  const res = await page.goto(site('marketing', '/this-page-does-not-exist-t3/'));
  expect(res.status()).toBe(404);
  await expect(page.locator('h1')).toHaveCount(1);
  await expect(page.getByRole('search')).toBeVisible();
  await expect(page.locator('a[href$="/growth-audit/"]', { hasText: 'Get an audit' }).last()).toBeVisible();
  await expectNoA11yViolations(page);
});

test('case study: verified Paycape metrics, story sections, the 11 permissioned cases on the hub', async ({ page }) => {
  await page.goto(site('marketing', '/case-studies/paycape/'));
  const pills = page.locator('.td-stat-pills');
  await expect(pills).toContainText('50%');
  await expect(pills).toContainText('50 days');
  await page.goto(site('marketing', '/case-studies/wezops/'));
  await expect(page.locator('.td-m2-story__step')).toHaveCount(3);
  await expect(page.locator('.td-m2-story__body ul').first()).toBeVisible();
  await expect(page.getByText('Levi Barbosa')).toBeVisible();
  await page.goto(site('marketing', '/case-studies/'));
  await expect(page.locator('.td-case-card a[href*="/case-studies/"]')).toHaveCount(11);
  await expect(page.locator('.td-m2-chips a[aria-current="page"]')).toHaveText(/All industries/);
});

test.describe('solution without proof', () => {
  const wp = (args) => execFileSync('docker', ['compose', 'exec', '-T', '-u', 'www-data', 'wp-marketing', 'wp', ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }).trim();
  let id = '';
  test.beforeAll(() => {
    id = wp(['post', 'create', '--post_type=solution', '--post_status=draft', '--post_title=E2E healthcare marketing agency', '--post_name=e2e-healthcare-marketing-agency', '--porcelain']);
    wp(['post', 'term', 'set', id, 'td_industry', 'e2e-healthcare']);
    // The pre-publish checklist blocks a bare page; an admin override (logged) publishes it.
    wp(['post', 'meta', 'update', id, '_td_checklist_override', '1']);
    wp(['post', 'update', id, '--post_status=publish', '--user=admin']);
  });
  test.afterAll(() => {
    if (id) wp(['post', 'delete', id, '--force']);
    try { wp(['term', 'delete', 'td_industry', 'e2e-healthcare', '--by=slug']); } catch (e) { /* already gone */ }
  });
  test('shows the honest first steps, not a case study', async ({ page }) => {
    await page.goto(site('marketing', '/solution/e2e-healthcare-marketing-agency/'));
    await expect(page.getByRole('heading', { name: /What we’d do first/ })).toBeVisible();
    await expect(page.locator('.td-case-card')).toHaveCount(0);
  });
});

test('location shows only proof from its own market', async ({ page }) => {
  await page.goto(site('marketing', '/locations/usa/'));
  await expect(page.locator('.td-case-card')).toHaveCount(0);
});

test('glossary: answer-first definition, A–Z index, byline', async ({ page }) => {
  await page.goto(site('marketing', '/glossary/'));
  await expect(page.getByRole('navigation', { name: 'Jump to letter' })).toBeVisible();
  await page.goto(site('marketing', '/glossary/aeo/'));
  const first = page.locator('.td-m2-term > :not(style)').first();
  await expect(first).toHaveClass(/td-tldr/);
  await expect(page.locator('.td-m2-byline')).toContainText('Reviewed by');
});

test('resource: lead_magnet form carries the resource slug', async ({ page }) => {
  await page.goto(site('marketing', '/resources/ai-search-readiness-checklist/'));
  await expect(page.locator('input[name="resource_slug"]')).toHaveValue('ai-search-readiness-checklist');
});

test('tool: app mount, then content; three ad positions', async ({ page }) => {
  await page.goto(site('marketing', '/tools/free-utm-parameter-generator/'));
  await expect(page.locator('[data-td-tool="utm-parameter-generator"] [data-td-tool-app="utm-parameter-generator"]')).toBeVisible();
  for (const pos of ['result', 'content', 'sidebar']) {
    await expect(page.locator(`[data-td-ad="${pos}"]`)).toHaveCount(1);
  }
});

test('book a discovery call: contact form, no popups', async ({ page }) => {
  await page.goto(site('marketing', '/book-a-discovery-call/'));
  await expect(page.locator('form[action*="/submit/contact"]')).toBeVisible();
  await expect(page.locator('.td-popup, [data-td-popup]')).toHaveCount(0);
});

test('legal pages are marked for legal review', async ({ page }) => {
  for (const p of ['/privacy-policy/', '/terms/', '/cookies/', '/disclaimer/']) {
    await page.goto(site('marketing', p));
    await expect(page.locator('main, body').first()).toContainText('[CONFIRM legal review]');
  }
});

test.describe('naira pricing', () => {
  test('USD by default; the switch shows naira and is remembered', async ({ page }) => {
    await page.goto(site('marketing', '/pricing/'));
    const audit = page.locator('.td-pricing__card').first().locator('.td-price');
    await expect(audit).toHaveText('$1,500');
    const ngn = page.locator('[data-td-currency] [data-currency="ngn"]');
    await expect(ngn).toBeVisible();
    await ngn.click();
    await expect(audit).toHaveText('₦300,000');
    await expect(ngn).toHaveAttribute('aria-pressed', 'true');
    await page.reload();
    await expect(audit).toHaveText('₦300,000');
    expect(await page.content()).not.toContain('{{price:');
  });

  test('a device in Nigeria (Africa/Lagos) sees naira on its first visit', async ({ browser }) => {
    const context = await browser.newContext({ timezoneId: 'Africa/Lagos' });
    const page = await context.newPage();
    await page.goto(site('marketing', '/services/paid-ads/'));
    await expect(page.locator('.td-price').first()).toHaveText(/^₦/);
    await page.goto(site('studios', '/'));
    await expect(page.locator('.td-pricing .td-price').first()).toHaveText('₦700,000');
    await context.close();
  });
});
