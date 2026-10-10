// Service-page blocks (worker A2): tabs keyboard + no-JS fallback, "Example"
// labels on every ad mock / creative, axe, no horizontal scroll at 390px.
// Uses /styleguide/ (renders every block example) plus a temporary page with
// every ad-mock format and the hero collage, created and deleted via WP-CLI.
import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { site, expectNoA11yViolations, expectNoHorizontalScroll } from './helpers.js';

const wp = (...args) =>
  execFileSync('docker', ['compose', 'exec', '-T', '-u', 'www-data', 'wp-marketing', 'wp', ...args], { encoding: 'utf8' }).trim();

const MINE = ['service-switcher', 'serp-mock', 'ai-answer-mock', 'before-after', 'feature-grid', 'platform-badges', 'hub-spoke', 'timeline', 'tabs', 'ad-mock', 'creative-gallery', 'funnel', 'report-mock', 'connected-services'];
const FORMATS = ['tiktok', 'instagram', 'meta', 'linkedin', 'linkedin-document', 'google-search', 'google-display', 'reel', 'carousel', 'video', 'motion'];
const block = (name, attrs) => `<!-- wp:techdella/${name} ${JSON.stringify(attrs)} /-->`;

let pageId = '';
const SLUG = 'blocks-a2-test';

test.beforeAll(() => {
  const old = wp('post', 'list', '--post_type=page', `--name=${SLUG}`, '--field=ID', '--post_status=any');
  old.split('\n').filter(Boolean).forEach((id) => wp('post', 'delete', id, '--force'));
  const content = [
    ...FORMATS.map((format) => block('ad-mock', { format, headline: 'Northwind headline', text: 'Example copy for Northwind.', cta: 'Learn more', overlay: 'Northwind', tag: 'Tag', slides: [{ label: '1/3', title: 'Slide' }], contained: true })),
    block('creative-gallery', {
      layout: 'collage', contained: true, note: 'Example creatives made by our team',
      items: [
        { format: 'tiktok', headline: '3 signs your website is invisible to buyers', cta: 'Learn more', tag: 'TikTok · Sponsored', image: 'stock/creator-recording-video-vertical' },
        { format: 'linkedin', brand: 'Northwind', tag: 'Promoted · LinkedIn', text: 'Is your brand showing up when buyers ask AI?', overlay: 'The AI Search Checklist', footer: 'Free guide', cta: 'Download', image: 'stock/designer-drawing-tablet-studio' },
        { format: 'google-search', domain: 'northwind.example', headline: 'SOC 2 Readiness in Weeks | Fixed Fee', text: 'Gap assessment and audit support.' },
        { format: 'instagram', cta: 'Book a call', image: 'stock/designer-drawing-tablet-studio' },
      ],
    }),
  ].join('\n');
  pageId = wp('post', 'create', '--post_type=page', '--post_status=publish', `--post_name=${SLUG}`, '--post_title=Blocks A2 test', `--post_content=${content}`, '--porcelain');
});

test.afterAll(() => {
  if (pageId) wp('post', 'delete', pageId, '--force');
});

test.describe('service blocks', () => {
  test('styleguide renders every A2 block', async ({ page }) => {
    await page.goto(site('marketing', '/styleguide/'));
    for (const name of MINE) {
      await expect(page.locator(`[data-td-block="techdella/${name}"] .td-sg__block-body > *`).first(), name).toBeVisible();
    }
  });

  test('tabs: ARIA roles and keyboard', async ({ page }) => {
    await page.goto(site('marketing', '/styleguide/'));
    const root = page.locator('[data-td-block="techdella/tabs"] [data-td-tabs]');
    const tabs = root.getByRole('tab');
    const count = await tabs.count();
    expect(count).toBeGreaterThan(2);
    const visiblePanels = () => root.locator('[role="tabpanel"]:visible');

    await expect(tabs.nth(0)).toHaveAttribute('aria-selected', 'true');
    await expect(visiblePanels()).toHaveCount(1);

    await tabs.nth(0).focus();
    await page.keyboard.press('ArrowRight');
    await expect(tabs.nth(1)).toBeFocused();
    await expect(tabs.nth(1)).toHaveAttribute('aria-selected', 'true');
    await expect(tabs.nth(0)).toHaveAttribute('aria-selected', 'false');
    await expect(tabs.nth(0)).toHaveAttribute('tabindex', '-1');
    await expect(visiblePanels()).toHaveCount(1);
    await expect(page.locator(`#${await tabs.nth(1).getAttribute('aria-controls')}`)).toBeVisible();

    await page.keyboard.press('End');
    await expect(tabs.nth(count - 1)).toBeFocused();
    await expect(tabs.nth(count - 1)).toHaveAttribute('aria-selected', 'true');
    await page.keyboard.press('ArrowRight'); // wraps
    await expect(tabs.nth(0)).toHaveAttribute('aria-selected', 'true');
    await page.keyboard.press('ArrowLeft'); // wraps back
    await expect(tabs.nth(count - 1)).toHaveAttribute('aria-selected', 'true');
    await page.keyboard.press('Home');
    await expect(tabs.nth(0)).toBeFocused();
    await expect(tabs.nth(0)).toHaveAttribute('aria-selected', 'true');
    await expect(visiblePanels()).toHaveCount(1);

    await tabs.nth(2).click();
    await expect(tabs.nth(2)).toHaveAttribute('aria-selected', 'true');
    const panel = page.locator(`#${await tabs.nth(2).getAttribute('aria-controls')}`);
    await expect(panel).toHaveAttribute('aria-labelledby', await tabs.nth(2).getAttribute('id'));
  });

  test('tabs + gallery work without JavaScript', async ({ browser }) => {
    const ctx = await browser.newContext({ javaScriptEnabled: false });
    const page = await ctx.newPage();
    await page.goto(site('marketing', '/styleguide/'));
    const root = page.locator('[data-td-block="techdella/tabs"]');
    await expect(root.locator('[role="tablist"]')).toBeHidden();
    const panels = root.locator('[data-td-tab-panel]');
    const n = await panels.count();
    expect(n).toBeGreaterThan(2);
    for (let i = 0; i < n; i++) await expect(panels.nth(i)).toBeVisible();
    // Filter chips stay hidden; every creative shows.
    const gallery = page.locator('[data-td-block="techdella/creative-gallery"]');
    await expect(gallery.locator('.td-creatives__filters')).toBeHidden();
    const items = gallery.locator('.td-creatives__item');
    for (let i = 0; i < (await items.count()); i++) await expect(items.nth(i)).toBeVisible();
    await ctx.close();
  });

  test('gallery filter chips (enhancement)', async ({ page }) => {
    await page.goto(site('marketing', '/styleguide/'));
    const gallery = page.locator('[data-td-block="techdella/creative-gallery"]');
    await gallery.getByRole('button', { name: 'TikTok' }).click();
    await expect(gallery.getByRole('button', { name: 'TikTok' })).toHaveAttribute('aria-pressed', 'true');
    await expect(gallery.locator('.td-creatives__item[data-platform="meta"]').first()).toBeHidden();
    await gallery.getByRole('button', { name: 'All' }).click();
    await expect(gallery.locator('.td-creatives__item[data-platform="meta"]').first()).toBeVisible();
  });

  test('every ad mock and creative carries an "Example" label', async ({ page }) => {
    for (const path of ['/styleguide/', `/${SLUG}/`]) {
      await page.goto(site('marketing', path));
      const mocks = page.locator('figure.td-ad:not(.td-ad--live), .td-creatives__figure');
      const n = await mocks.count();
      expect(n, path).toBeGreaterThan(0);
      for (let i = 0; i < n; i++) {
        const label = mocks.nth(i).locator(':scope > figcaption.td-ad__label');
        await expect(label, `${path} mock ${i}`).toHaveText(/^Example/);
        if (await mocks.nth(i).isVisible()) await expect(label).toBeVisible(); // mocks in inactive tabs are hidden
      }
      // SERP / AI answer / report mocks are labelled too.
      for (const sel of ['.td-serp-mock__caption', '.td-ai-answer__caption', '.td-report__badge']) {
        for (const el of await page.locator(sel).all()) await expect(el).toHaveText(/Example/);
      }
    }
    await page.goto(site('marketing', `/${SLUG}/`));
    await expect(page.locator('figure.td-ad')).toHaveCount(FORMATS.length + 4);
  });

  test('axe: no serious/critical issues in A2 blocks', async ({ page }) => {
    await page.goto(site('marketing', '/styleguide/'));
    for (const name of MINE) await expectNoA11yViolations(page, { include: `[data-td-block="techdella/${name}"]` });
    await page.goto(site('marketing', `/${SLUG}/`));
    await expectNoA11yViolations(page, { include: 'main' });
  });

  test('no horizontal scroll at 390px', async ({ browser }) => {
    const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
    const page = await ctx.newPage();
    for (const path of ['/styleguide/', `/${SLUG}/`]) {
      await page.goto(site('marketing', path));
      await expectNoHorizontalScroll(page);
      for (const name of MINE) {
        const el = page.locator(`[data-td-block="techdella/${name}"] .td-sg__block-body`);
        if (!(await el.count())) continue;
        const over = await el.evaluate((n) => n.scrollWidth - n.clientWidth);
        expect(over, name).toBeLessThanOrEqual(0);
      }
    }
    await ctx.close();
  });
});
