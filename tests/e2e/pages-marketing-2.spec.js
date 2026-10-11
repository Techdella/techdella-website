// Phase 3 Marketing templates (worker T3): case studies, solutions, locations, glossary,
// resources, tools, About, Book a call, legal pages, 404.
// Relies on the local fixtures (see the T3 report) or real content at these URLs.
import { test, expect } from '@playwright/test';
import { site, expectNoA11yViolations, expectNoHorizontalScroll } from './helpers.js';

// The feature-grid "01–04" numbers are aria-hidden decoration (WCAG 1.4.3 exempts pure
// decoration); axe still flags their contrast. Reported to the integrator (block owner).
const AXE = { exclude: ['.td-feature-grid__num'] };

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

test('case study: verified Paycape metrics, services linked, no unpermissioned cases on the hub', async ({ page }) => {
  await page.goto(site('marketing', '/case-studies/paycape/'));
  const pills = page.locator('.td-stat-pills');
  await expect(pills).toContainText('50%');
  await expect(pills).toContainText('50 days');
  await page.goto(site('marketing', '/case-studies/'));
  // Drafts / permission_confirmed=false never show.
  await expect(page.locator('a[href*="/case-studies/wezops/"]')).toHaveCount(0);
  await expect(page.locator('.td-m2-chips a[aria-current="page"]')).toHaveText(/All industries/);
});

test('solution without permissioned proof shows the honest first steps, not a case study', async ({ page }) => {
  await page.goto(site('marketing', '/solution/saas-marketing-agency/'));
  await expect(page.getByRole('heading', { name: /What we’d do first/ })).toBeVisible();
  await expect(page.locator('.td-case-card')).toHaveCount(0);
});

test('location shows only proof from its own market', async ({ page }) => {
  await page.goto(site('marketing', '/locations/usa/'));
  await expect(page.locator('.td-case-card')).toHaveCount(0);
});

test('glossary: answer-first definition, A–Z index, byline', async ({ page }) => {
  await page.goto(site('marketing', '/glossary/'));
  await expect(page.getByRole('navigation', { name: 'Jump to letter' })).toBeVisible();
  await page.goto(site('marketing', '/glossary/aeo/'));
  const first = page.locator('.td-m2-term > *').first();
  await expect(first).toHaveClass(/td-tldr/);
  await expect(page.locator('.td-m2-byline')).toContainText('Reviewed by');
});

test('resource: lead_magnet form carries the resource slug', async ({ page }) => {
  await page.goto(site('marketing', '/resources/ai-search-readiness-checklist/'));
  await expect(page.locator('input[name="resource_slug"]')).toHaveValue('ai-search-readiness-checklist');
});

test('tool: app mount, then content; three ad positions', async ({ page }) => {
  await page.goto(site('marketing', '/tools/free-utm-parameter-generator/'));
  await expect(page.locator('[data-td-tool="utm-parameter-generator"]')).toContainText('Tool loads here');
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
