// Phase 1 design system (§2, §15): styleguide, blocks, nav/footer, a11y, no-JS behaviour.
import { test, expect } from '@playwright/test';
import { site, expectNoA11yViolations, expectNoHorizontalScroll } from './helpers.js';

const CORE_BLOCKS = [
  'button', 'label-chip', 'section-head', 'hero-orbit', 'hero', 'logo-strip', 'bento-grid',
  'steps-fold', 'stat-pills', 'testimonial', 'faq', 'cta-dark', 'pricing-cards', 'founder',
];

for (const name of ['marketing', 'journal', 'studios']) {
  test(`styleguide loads on ${name} and is noindex @smoke`, async ({ page }) => {
    const res = await page.goto(site(name, '/styleguide/'));
    expect(res.status()).toBe(200);
    const robots = await page.locator('meta[name="robots"]').evaluateAll((els) => els.map((e) => e.content).join(','));
    const header = res.headers()['x-robots-tag'] || '';
    expect(`${robots} ${header}`).toMatch(/noindex/);
    await expect(page.locator('header[data-td-nav]')).toHaveCount(1);
    await expect(page.locator('footer.td-footer')).toHaveCount(1);
  });
}

test('every techdella block renders on the styleguide @smoke', async ({ page }) => {
  await page.goto(site('marketing', '/styleguide/'));
  const sections = page.locator('[data-td-block^="techdella/"]');
  const names = await sections.evaluateAll((els) => els.map((e) => e.dataset.tdBlock));
  for (const b of CORE_BLOCKS) expect(names).toContain(`techdella/${b}`);
  // Every block section (including other workers' blocks) produced markup — except ad-slot,
  // which by design renders nothing outside the tools pages (§12.2).
  const empty = await sections.evaluateAll((els) => els
    .filter((e) => !e.querySelector('.td-sg__block-body')?.children.length)
    .map((e) => e.dataset.tdBlock)
    .filter((n) => n !== 'techdella/ad-slot'));
  expect(empty).toEqual([]);
  // The styleguide example still shows a price placeholder; the founder letter is signed off (§2.4).
  await expect(page.locator('#block-pricing-cards .td-confirm').first()).toHaveText(/\[PRICE\]/);
  await expect(page.locator('#block-founder')).toBeVisible();
  await expect(page.locator('#block-founder .td-confirm')).toHaveCount(0);
  // FAQ never emits FAQPage schema (§8.2).
  expect(await page.content()).not.toContain('FAQPage');
});

test('styleguide has no serious/critical axe issues @smoke', async ({ page }) => {
  await page.goto(site('marketing', '/styleguide/'));
  await expectNoA11yViolations(page);
});

for (const name of ['marketing', 'journal', 'studios']) {
  test(`${name} home has no serious/critical axe issues @smoke`, async ({ page }) => {
    await page.goto(site(name, '/'));
    await expectNoA11yViolations(page);
  });
}

test.describe('phone width', () => {
  test.use({ viewport: { width: 390, height: 844 } });
  for (const path of ['/styleguide/', '/']) {
    for (const name of ['marketing', 'journal', 'studios']) {
      test(`no horizontal scroll at 390px: ${name} ${path} @smoke`, async ({ page }) => {
        await page.goto(site(name, path));
        await expectNoHorizontalScroll(page);
      });
    }
  }

  test('mobile sheet: opens, traps focus, Esc closes and returns focus @smoke', async ({ page }) => {
    await page.goto(site('marketing', '/styleguide/'));
    const button = page.locator('[data-td-sheet-open]');
    await expect(button).toBeVisible();
    await expect(button).toHaveAttribute('aria-expanded', 'false');
    await button.click();
    const sheet = page.locator('#td-sheet');
    await expect(sheet).toBeVisible();
    await expect(button).toHaveAttribute('aria-expanded', 'true');
    // Focus stays inside the sheet while tabbing.
    for (let i = 0; i < 25; i++) await page.keyboard.press('Tab');
    expect(await page.evaluate(() => !!document.activeElement.closest('#td-sheet'))).toBe(true);
    await page.keyboard.press('Escape');
    await expect(sheet).toBeHidden();
    await expect(button).toBeFocused();
    await expect(button).toHaveAttribute('aria-expanded', 'false');
  });
});

test('services mega-menu: keyboard opens it, Esc closes and returns focus @smoke', async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto(site('marketing', '/styleguide/'));
  const toggle = page.locator('[data-td-mega-toggle][aria-controls="td-mega"]');
  const panel = page.locator('#td-mega');
  await expect(toggle).toBeVisible();
  await toggle.focus();
  await page.keyboard.press('Enter');
  await expect(toggle).toHaveAttribute('aria-expanded', 'true');
  await expect(panel).toBeVisible();
  await expect(panel.locator('a.td-mega__link')).toHaveCount(6);
  await expect(panel.locator('a.td-mega__studios')).toHaveAttribute('href', /localhost:8083|studios\.techdella\.com/);
  await page.keyboard.press('Tab');
  expect(await page.evaluate(() => !!document.activeElement.closest('#td-mega'))).toBe(true);
  await page.keyboard.press('Escape');
  await expect(panel).toBeHidden();
  await expect(toggle).toBeFocused();

  // Industries and Resources: label-only dropdowns, one open at a time.
  for (const id of ['td-mega-industries', 'td-mega-resources']) {
    const btn = page.locator(`[aria-controls="${id}"]`);
    await btn.focus();
    await page.keyboard.press('Enter');
    await expect(page.locator(`#${id}`)).toBeVisible();
    await expect(page.locator(`#${id} a`).first()).toBeVisible();
    await expect(panel).toBeHidden();
    await page.keyboard.press('Escape');
    await expect(page.locator(`#${id}`)).toBeHidden();
  }
});

test('nav gets the scrolled (blurred) state', async ({ page }) => {
  await page.goto(site('marketing', '/styleguide/'));
  const nav = page.locator('header[data-td-nav]');
  await expect(nav).not.toHaveClass(/is-scrolled/);
  await page.mouse.wheel(0, 800);
  await expect(nav).toHaveClass(/is-scrolled/);
});

test.describe('without JavaScript', () => {
  test.use({ javaScriptEnabled: false });

  test('steps and FAQ <details> toggle; Services is a plain link; mobile menu works @smoke', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(site('marketing', '/styleguide/'));
    const steps = page.locator('#block-steps-fold details');
    await expect(steps.first()).toHaveAttribute('open', '');
    const second = steps.nth(1);
    await expect(second).not.toHaveAttribute('open', '');
    await second.locator('summary').click();
    await expect(second).toHaveAttribute('open', '');
    await expect(second.locator('.td-steps__body')).toBeVisible();

    const services = page.locator('.td-nav__item--mega > a.td-nav__link');
    await expect(services).toHaveAttribute('href', /\/services\/$/);
    await expect(page.locator('[data-td-mega-toggle]:visible')).toHaveCount(0);

    await page.setViewportSize({ width: 390, height: 844 });
    const summary = page.locator('.td-nav__menu > summary');
    await expect(summary).toBeVisible();
    await summary.click();
    await expect(page.locator('.td-nav__menu .td-sheet')).toBeVisible();
  });
});

test('cross-site links use the other sites, footers have legal links and no "#" links', async ({ page }) => {
  for (const name of ['marketing', 'journal', 'studios']) {
    await page.goto(site(name, '/styleguide/'));
    const footer = page.locator('footer.td-footer');
    for (const path of ['/privacy-policy/', '/terms/', '/cookies/', '/disclaimer/']) {
      await expect(footer.locator(`a[href$="${path}"]`)).toHaveCount(1);
    }
    expect(await page.locator('header[data-td-nav] a[href="#"], footer a[href="#"]').count()).toBe(0);
  }
  await page.goto(site('studios', '/styleguide/'));
  await expect(page.locator('header[data-td-nav] .td-nav__actions > a.td-btn')).toHaveAttribute('href', /\/start\/$/);
});
