// Phase 3 Marketing core pages (worker T1): built from patterns/marketing-*.php by
// `wp td preview:seed` (published on local). Each page: 200, exactly one H1, key
// headings present, axe clean, no horizontal scroll at 390px.
import { test, expect } from '@playwright/test';
import { site, expectNoA11yViolations, expectNoHorizontalScroll } from './helpers.js';

const PAGES = [
  { name: 'home', path: '/', h1: /Get found\. Get noticed\. Grow\./, headings: ['One connected growth system', 'What we did for Paycape', 'Ready to grow?'], smoke: true },
  { name: 'services', path: '/services/', h1: /Marketing services that work as one system/, headings: ['Everything connects to growth', 'From first call to results'] },
  { name: 'seo', path: '/services/seo/', h1: /B2B SEO services/, headings: ['What’s included in our SEO services', 'SEO works better connected', 'SEO questions'] },
  { name: 'technical-seo', path: '/services/technical-seo/', h1: /Technical SEO services/, headings: ['What’s included in technical SEO', 'Technical SEO questions'] },
  { name: 'local-seo', path: '/services/local-seo/', h1: /Local SEO services/, headings: ['What’s included in local SEO', 'Local SEO questions'] },
  { name: 'ai-search', path: '/services/ai-search-optimization/', h1: /AI search optimization/, headings: ['How AI answers decide which brands to mention', 'What’s included in AEO & GEO'] },
  { name: 'paid-ads', path: '/services/paid-ads/', h1: /Paid ads that pay back/, headings: ['The right platform for each job', 'We make the creatives too', 'Is paid right for you yet?'] },
  { name: 'lead-generation', path: '/services/lead-generation/', h1: /B2B lead generation/, headings: ['What’s included in our lead generation', 'From stranger to sales call'] },
  { name: 'email-marketing', path: '/services/email-marketing/', h1: /Email marketing that turns leads into clients/, headings: ['What’s included in our email marketing', 'Emails people actually read'] },
  { name: 'marketing-automation', path: '/services/marketing-automation/', h1: /AI marketing automation/, headings: ['What we automate', 'What changes when it’s automated'] },
  { name: 'growth-audit', path: '/growth-audit/', h1: /See where your growth is leaking/, headings: ['Request your audit', 'Common questions'] },
  { name: 'pricing', path: '/pricing/', h1: /Clear ways to start/, headings: ['Start with an audit.', 'Paid ads management'] },
];

const norm = (s) => s.replace(/\s+/g, ' ').trim();

for (const p of PAGES) {
  test.describe(`${p.name} page${p.smoke ? ' @smoke' : ''}`, () => {
    test(`${p.path} renders with one H1 and its key sections`, async ({ page }) => {
      const res = await page.goto(site('marketing', p.path));
      expect(res.status()).toBe(200);
      await expect(page.locator('h1')).toHaveCount(1);
      expect(norm(await page.locator('h1').innerText())).toMatch(p.h1);
      const headings = (await page.locator('h2, h3').allTextContents()).map(norm); // textContent: below-the-fold sections skip rendering (content-visibility), so innerText is empty until scrolled
      for (const h of p.headings) {
        expect(headings.some((t) => t.includes(h)), `heading "${h}"`).toBe(true);
      }
      await expectNoA11yViolations(page);
    });

    test(`${p.path} has no horizontal scroll at 390px`, async ({ page }) => {
      await page.setViewportSize({ width: 390, height: 844 });
      await page.goto(site('marketing', p.path));
      await expectNoHorizontalScroll(page);
    });
  });
}

test('service pages link to each other through the service switcher', async ({ page }) => {
  await page.goto(site('marketing', '/services/seo/'));
  const current = page.locator('.td-service-switcher [aria-current="page"]');
  await expect(current).toBeVisible();
  await expect(current).toHaveText(/^\s*SEO\s*$/);
  for (const path of ['/services/ai-search-optimization/', '/services/paid-ads/', '/services/lead-generation/', '/services/email-marketing/', '/services/marketing-automation/']) {
    await expect(page.locator(`.td-service-switcher a[href$="${path}"]`).first()).toBeAttached();
  }
});

test('growth audit page carries the audit form', async ({ page }) => {
  await page.goto(site('marketing', '/growth-audit/'));
  await expect(page.locator('form[action*="techdella/v1/submit/audit"]')).toHaveCount(1);
});
