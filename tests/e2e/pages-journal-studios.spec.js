// Phase 4 pages (worker T2): Journal (localhost:8082) and Studios (localhost:8083).
// Pages come from theme patterns via `wp td preview:seed`; the Journal needs published
// posts (local sample fixtures) and Studios needs studio_work items.
// Every page: 200, one H1, axe clean, no horizontal scroll at 390px.
import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { site, expectNoA11yViolations, expectNoHorizontalScroll } from './helpers.js';

const PAGES = [
  ['journal', '/'],
  ['journal', '/newsletter/'],
  ['journal', '/write-for-us/'],
  ['studios', '/'],
  ['studios', '/work/'],
  ['studios', '/start/'],
  ['studios', '/about/'],
  ['studios', '/mvp-calculator/'],
  ...['mvp-development', 'ai-automation', 'ui-ux-design', 'website-design', 'branding', 'graphic-design', 'motion-design'].map((s) => ['studios', `/services/${s}/`]),
];

// The numbered feature-grid digits (aria-hidden, 30px, --td-num) are under 3:1; the fix belongs
// in blocks/feature-grid/style.css (same exclusion as pages-marketing.spec.js until it lands).
const AXE_EXCLUDE = [];

async function check(page, url, { axe = true } = {}) {
  const res = await page.goto(url);
  expect(res.status(), url).toBe(200);
  await expect(page.locator('h1'), url).toHaveCount(1);
  const html = await page.content();
  expect(html, url).not.toMatch(/(Warning|Notice|Fatal error)<\/b>:/);
  if (axe) await expectNoA11yViolations(page, { exclude: AXE_EXCLUDE });
}

test.describe('Journal + Studios pages', () => {
  for (const [s, path] of PAGES) {
    test(`${s} ${path}`, async ({ page }) => {
      await check(page, site(s, path));
    });
  }

  test('journal home shows real posts @smoke', async ({ page }) => {
    await page.goto(site('journal', '/'));
    await expect(page.locator('.td-post-featured')).toHaveCount(1);
    const cards = page.locator('.td-post-grid__cards .td-post-card__link');
    expect(await cards.count()).toBeGreaterThan(0);
    // Real posts link to the Journal itself, never to an inline "Example".
    await expect(page.locator('.td-post-grid .td-label-example')).toHaveCount(0);
    await expect(page.locator('.td-newsletter form')).toHaveCount(1);
  });

  test('studios home renders the prototype sections @smoke', async ({ page }) => {
    await page.goto(site('studios', '/'));
    await expect(page.locator('h1')).toHaveCount(1);
    for (const id of ['services', 'work', 'team', 'process', 'packages']) {
      await expect(page.locator(`#${id}`), id).toHaveCount(1);
    }
    // Concept work is always labelled.
    const concept = page.locator('.td-case-card--concept');
    const n = await concept.count();
    for (let i = 0; i < n; i++) await expect(concept.nth(i)).toContainText('Concept');
    // Prices are set (docs/PRICING.md); no placeholders left on the page.
    await expect(page.locator('#packages')).toContainText(/From \$[\d,]+/);
    await expect(page.locator('#packages')).not.toContainText('[PRICE]');
  });

  test('journal post: header, toc, tldr, author box, related', async ({ page }) => {
    await page.goto(site('journal', '/'));
    const href = await page.locator('.td-post-grid__cards .td-post-card__link').first().getAttribute('href');
    await check(page, href);
    await expect(page.locator('.td-post-header h1')).toHaveCount(1);
    await expect(page.locator('.td-toc')).toHaveCount(1);
    await expect(page.locator('.td-author-box')).toHaveCount(1);
    // TOC links point at real heading ids.
    const first = await page.locator('.td-toc a').first().getAttribute('href');
    await expect(page.locator(first)).toHaveCount(1);
  });

  test('journal category + author archives', async ({ page }) => {
    test.slow();
    await page.goto(site('journal', '/'));
    const cat = await page.locator('.td-post-grid__filters a:not([aria-current])').first().getAttribute('href');
    await check(page, cat);
    await expect(page.locator('.td-post-grid__filters [aria-current="page"]')).toHaveCount(1);
    await page.goto(site('journal', '/'));
    await page.goto(await page.locator('.td-post-grid__cards .td-post-card__link').first().getAttribute('href'));
    const author = await page.locator('.td-post-header a[rel="author"]').getAttribute('href');
    await check(page, author);
    await expect(page.locator('.td-post-card').first()).toBeVisible();
    await check(page, site('journal', '/?s=seo'), { axe: false });
  });

  test('studios work archive + single show Live/Concept labels', async ({ page }) => {
    await page.goto(site('studios', '/work/'));
    const links = page.locator('.td-case-card__link');
    expect(await links.count()).toBeGreaterThan(0);
    const concept = await page.locator('.td-case-card--concept .td-case-card__link').first().getAttribute('href');
    await check(page, concept);
    await expect(page.locator('.td-work-single__status')).toContainText('Concept');
    await page.goto(site('studios', '/work/'));
    const live = await page.locator('.td-case-card:not(.td-case-card--concept) .td-case-card__link').first().getAttribute('href');
    await check(page, live);
    await expect(page.locator('.td-work-single__meta')).toContainText('Live');
  });

  test('start page: four-step studios brief form', async ({ page }) => {
    await page.goto(site('studios', '/start/'));
    await expect(page.locator('.td-form')).toBeVisible();
    await expect(page.getByText('Step 1 of 4')).toBeVisible();
  });
});

test.describe('390px', () => {
  test.use({ viewport: { width: 390, height: 844 } });
  for (const [s, path] of [['journal', '/'], ['studios', '/'], ['studios', '/start/'], ['studios', '/work/'], ['studios', '/services/mvp-development/']]) {
    test(`no horizontal scroll ${s} ${path}`, async ({ page }) => {
      await page.goto(site(s, path));
      await expectNoHorizontalScroll(page);
    });
  }
});

test('MVP calculator: live range from Studios prices, naira switch, fills the MVP_CALC form', async ({ page }) => {
  await page.goto(site('studios', '/mvp-calculator/'));
  const range = page.locator('.td-mvp__range');
  await expect(range).toContainText('$');
  const before = await range.innerText();
  await page.locator('.td-mvp label:has-text("Payments")').click();
  await page.locator('.td-mvp label:has-text("Web and mobile apps")').click();
  await expect(range).not.toHaveText(before);
  await page.locator('.td-mvp [data-currency="ngn"]').click();
  await expect(range).toContainText('₦');
  const form = page.locator('form[action*="/submit/mvp_calc"]');
  await expect(form.locator('input[name="estimate"]')).toHaveValue(/^₦[\d,]+–₦[\d,]+, \d+–\d+ weeks$/);
  expect(JSON.parse(await form.locator('input[name="inputs"]').inputValue())).toMatchObject({ platform: 'both', features: ['payments'] });
  await expect(page.locator('main')).not.toContainText('[CONFIRM');
});

test.describe('paid placements', () => {
  const wpj = (args) => execFileSync('docker', ['compose', 'exec', '-T', '-u', 'www-data', 'wp-journal', 'wp', ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }).trim();
  let id = '';
  test.beforeAll(() => { id = wpj(['post', 'list', '--name=b2b-seo-strategy', '--post_type=post', '--field=ID']); });
  test.afterEach(() => { for (const k of ['sponsored', 'sponsor_name', 'sponsored_links']) { try { wpj(['post', 'meta', 'delete', id, k]); } catch { /* not set */ } } });

  test('a sponsored post is labelled and every outside link is rel="sponsored"', async ({ page }) => {
    wpj(['post', 'meta', 'update', id, 'sponsored', '1']);
    wpj(['post', 'meta', 'update', id, 'sponsor_name', 'Acme Ltd']);
    await page.goto(site('journal', '/b2b-seo-strategy/'));
    await expect(page.locator('.td-post-header__sponsored')).toContainText('Acme Ltd');
    const rels = await page.$$eval('.td-article__content a[href^="http"]', (as) => as.filter((a) => !a.href.includes('localhost')).map((a) => a.rel));
    expect(rels.length).toBeGreaterThan(0);
    for (const r of rels) expect(r).toContain('sponsored');
  });

  test('a link insertion marks only that link and adds a disclosure', async ({ page }) => {
    await page.goto(site('journal', '/b2b-seo-strategy/'));
    const href = await page.locator('.td-article__content a[href^="https://"]').first().getAttribute('href');
    wpj(['post', 'meta', 'update', id, 'sponsored_links', href]);
    await page.reload();
    await expect(page.locator(`.td-article__content a[href="${href}"]`).first()).toHaveAttribute('rel', /sponsored/);
    await expect(page.locator('.td-sponsored-note')).toBeVisible();
    await expect(page.locator('.td-post-header__sponsored')).toHaveCount(0);
  });
});

test('write for us: paid options, rules and the request form', async ({ page }) => {
  await page.goto(site('journal', '/write-for-us/'));
  await expect(page.locator('main')).toContainText('Link insertion');
  await expect(page.locator('main')).toContainText('rel="sponsored"');
  await expect(page.locator('form[action*="/submit/guest_post"] input[name="request_type"]')).toHaveCount(3);
});
