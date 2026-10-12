// Redirects (§9), ads.txt + consent-gated scripts (§12), popup rules (§13.1).
import { test, expect, devices } from '@playwright/test';
import { execSync } from 'node:child_process';
import { site, SITES, expectNoA11yViolations } from './helpers.js';

const wp = (siteName, args) =>
  execSync(`docker compose exec -T -u www-data wp-${siteName} wp ${args}`, { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }).trim();

const SERVICE_SLUG = 'e2e-popup-service';
let serviceId = '';

test.describe.configure({ mode: 'serial' });

test.beforeAll(() => {
  wp('marketing', 'td setup --quiet');
  wp('marketing', 'td redirects import');
  // A published service page for P3 (the pre-publish checklist is overridden for this fixture only).
  serviceId = wp('marketing', `post list --post_type=service --name=${SERVICE_SLUG} --post_status=any --field=ID`);
  if (!serviceId) {
    serviceId = wp('marketing', `post create --post_type=service --post_title="E2E popup service" --post_name=${SERVICE_SLUG} --post_status=draft --porcelain --user=admin`);
  }
  wp('marketing', `post meta update ${serviceId} _td_checklist_override 1 --user=admin`);
  wp('marketing', `post update ${serviceId} --post_status=publish --user=admin`);
});

test.afterAll(() => {
  if (serviceId) wp('marketing', `post delete ${serviceId} --force`);
});

const hop = async (request, url) => {
  const res = await request.get(url, { maxRedirects: 0 });
  return { status: res.status(), location: res.headers().location || '' };
};

test.describe('redirects', () => {
  const cases = [
    ['/free-tools/', site('marketing', '/tools/')],
    ['/service/seo/', site('marketing', '/services/seo/')],
    ['/guide/what-is-vibe-coding/', `${SITES.journal}/what-is-vibe-coding/`],
    ['/guide/some-unlisted-guide/', `${SITES.journal}/some-unlisted-guide/`],
    ['/solutions/x/', site('marketing', '/solution/x/')],
    ['/studio/', `${SITES.studios}/`],
  ];
  for (const [source, target] of cases) {
    test(`${source} → one 301 hop`, async ({ request }) => {
      const first = await hop(request, site('marketing', source));
      expect(first.status).toBe(301);
      expect(first.location).toBe(target);
      // One hop: the target itself does not redirect again (it may 404 until content exists).
      if (target.startsWith(SITES.marketing)) {
        const second = await hop(request, target);
        expect(second.status === 301 || second.status === 302).toBe(false);
      }
    });
  }

  test('§9.2 regression: a live /solution/ URL is never substring-matched', async ({ request }) => {
    const res = await hop(request, site('marketing', '/solution/health-marketing-agency/'));
    expect([301, 302, 307, 308]).not.toContain(res.status);
  });

  test('GSC review outcomes (12 Oct 2026): <20 clicks 301, Ibadan stays, off-topic terms 410', async ({ request }) => {
    expect(await hop(request, site('marketing', '/locations/germany/'))).toEqual({ status: 301, location: site('marketing', '/services/') });
    expect((await hop(request, site('marketing', '/locations/ibadan-nigeria/'))).status).not.toBe(301);
    expect((await hop(request, site('marketing', '/glossary/contract-law/'))).status).toBe(410);
    expect(await hop(request, site('marketing', '/directory/founder-perks/'))).toEqual({ status: 301, location: site('marketing', '/founders-hub-waitlist/') });
  });
});

test.describe('ads.txt and consent', () => {
  test('ads.txt on Marketing, 404 on Journal', async ({ request }) => {
    const ok = await request.get(site('marketing', '/ads.txt'));
    expect(ok.status()).toBe(200);
    expect(ok.headers()['content-type']).toContain('text/plain');
    expect((await ok.text()).trim()).toMatch(/^google\.com, pub-\d{10,20}, DIRECT, f08c47fec0942fa0$/);
    const missing = await request.get(site('journal', '/ads.txt'));
    expect(missing.status()).toBe(404);
  });

  test('no AdSense or CMP script on a non-tool page; consent defaults come first', async ({ page }) => {
    await page.goto(site('marketing', '/'));
    await page.waitForLoadState('load');
    await page.waitForTimeout(500);
    expect(await page.locator('script[src*="fundingchoicesmessages"], script[src*="adsbygoogle"], script[src*="googletagmanager"]').count()).toBe(0);
    expect(await page.locator('[data-td-ad]').count()).toBe(0);
    const first = await page.evaluate(() => {
      const scripts = [...document.head.querySelectorAll('script')];
      return scripts.findIndex((s) => s.id === 'td-consent');
    });
    expect(first).toBe(0);
    const consent = await page.evaluate(() => (window.dataLayer || []).filter((e) => e[0] === 'consent').map((e) => [e[1], e[2]]));
    expect(consent[0][0]).toBe('default');
    expect(consent[0][1].analytics_storage).toBe('denied');
    expect(consent[0][1].region).toEqual(expect.arrayContaining(['DE', 'FR', 'GB', 'CH', 'NO']));
  });
});

test.describe('ad slots on tool pages', () => {
  const TOOL = 'e2e-ad-tool';
  let toolId = '';
  test.beforeAll(() => {
    toolId = wp('marketing', `post create --post_type=td_tool --post_title="E2E ad tool" --post_name=${TOOL} --post_status=draft --porcelain --user=admin --post_content='<!-- wp:paragraph --><p>Tool</p><!-- /wp:paragraph --><!-- wp:techdella/ad-slot {"position":"result"} /--><!-- wp:techdella/ad-slot {"position":"content"} /--><!-- wp:techdella/ad-slot {"position":"sidebar"} /--><!-- wp:techdella/ad-slot {"position":"content"} /-->'`);
    wp('marketing', `post meta update ${toolId} _td_checklist_override 1 --user=admin`);
    wp('marketing', `post update ${toolId} --post_status=publish --user=admin`);
    wp('marketing', `option update td_adsense_slots '{"result":"1111111111","content":"2222222222","sidebar":"3333333333"}' --format=json`);
  });
  test.afterAll(() => {
    if (toolId) wp('marketing', `post delete ${toolId} --force`);
    wp('marketing', 'option delete td_adsense_slots');
  });

  test('labelled units with reserved height, max 3, AdSense only after consent + near viewport', async ({ page }) => {
    test.skip(test.info().project.name !== 'desktop', 'desktop layout');
    await page.goto(site('marketing', `/tools/${TOOL}/`));
    const slots = page.locator('[data-td-ad]');
    await expect(slots).toHaveCount(3);
    await expect(slots.first().locator('.td-ad-slot__label')).toHaveText('Advertisement');
    const height = await slots.first().locator('ins').evaluate((el) => parseFloat(getComputedStyle(el).minHeight));
    expect(height).toBeGreaterThanOrEqual(250);
    expect(await page.locator('ins.adsbygoogle[data-ad-client^="ca-pub-"]').count()).toBe(3);
    // Outside the consent region the CMP still loads on ad pages; AdSense follows once a slot is near.
    await page.locator('[data-td-ad]').first().scrollIntoViewIfNeeded();
    await expect.poll(() => page.locator('script[src*="adsbygoogle.js"]').count()).toBe(1);
    expect(await page.locator('script[src*="fundingchoicesmessages"]').count()).toBe(1);
  });
});

test.describe('popups', () => {
  const serviceUrl = () => site('marketing', `/services/${SERVICE_SLUG}/`);
  const exitIntent = (page) => page.evaluate(() => document.dispatchEvent(new MouseEvent('mouseout', { clientY: -2, clientX: 400, bubbles: true, relatedTarget: null })));

  test('P3 opens on exit intent (desktop), Esc closes, focus returns, capped on reload', async ({ page }) => {
    test.skip(test.info().project.name !== 'desktop', 'desktop behaviour');
    await page.clock.install();
    await page.goto(serviceUrl());
    const dialog = page.locator('[data-td-popup="p3"]');
    await expect(dialog).toHaveAttribute('role', 'dialog');
    await expect(dialog).toHaveAttribute('aria-modal', 'true');
    await expect(dialog).toBeHidden();

    // Not in the first 10 seconds.
    await exitIntent(page);
    await expect(dialog).toBeHidden();

    await page.clock.fastForward(11_000);
    const opener = page.locator('body a[href]').first();
    await opener.focus();
    await exitIntent(page);
    await expect(dialog).toBeVisible();
    await expect(dialog.locator('.td-popup__close')).toBeVisible();
    await expectNoA11yViolations(page, { include: '[data-td-popup="p3"]' });
    expect(await dialog.evaluate((el) => el.contains(document.activeElement))).toBe(true);
    const events = await page.evaluate(() => (window.dataLayer || []).filter((e) => e.event === 'popup_impression').map((e) => e.popup_id));
    expect(events).toContain('p3');

    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();
    expect(await opener.evaluate((el) => el === document.activeElement)).toBe(true);

    // Frequency: once per 7 days per visitor.
    await page.reload();
    await page.clock.fastForward(11_000);
    await exitIntent(page);
    await expect(dialog).toBeHidden();
  });

  test('never a modal on mobile for a visitor from search', async ({ browser }) => {
    const context = await browser.newContext({ ...devices['Pixel 7'] });
    const page = await context.newPage();
    await page.clock.install();
    await page.goto(serviceUrl(), { referer: 'https://www.google.com/' });
    expect(await page.evaluate(() => document.referrer)).toContain('google.com');
    await page.clock.fastForward(31_000);
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await exitIntent(page);
    await page.clock.fastForward(2_000);
    await expect(page.locator('[data-td-popup="p3"]')).toBeHidden();
    await context.close();
  });

  test('no popups on the booking / audit pages', async ({ page }) => {
    for (const path of ['/book-a-discovery-call/', '/growth-audit/']) {
      await page.goto(site('marketing', path));
      expect(await page.locator('#td-popups-config').count()).toBe(0);
    }
  });
});
