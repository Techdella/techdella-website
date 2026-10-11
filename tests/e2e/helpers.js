// Shared e2e helpers.
import { expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

export const SITES = {
  marketing: process.env.MARKETING_URL || 'http://localhost:8081',
  journal: process.env.JOURNAL_URL || 'http://localhost:8082',
  studios: process.env.STUDIOS_URL || 'http://localhost:8083',
};
export const SB_MOCK = process.env.SB_MOCK_URL || 'http://localhost:8090';

/** Absolute URL on one of the three sites. */
export const site = (name, path = '/') => SITES[name].replace(/\/$/, '') + path;

/** §16: axe — 0 serious or critical issues. */
export async function expectNoA11yViolations(page, { include, exclude } = {}) {
  let builder = new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']);
  if (include) builder = builder.include(include);
  if (exclude) builder = builder.exclude(exclude);
  const { violations } = await builder.analyze();
  const bad = violations.filter((v) => ['serious', 'critical'].includes(v.impact));
  expect(bad.map((v) => `${v.id}: ${v.help} (${v.nodes.map((n) => n.target.join(' ')).slice(0, 3).join(', ')})`)).toEqual([]);
}

/** §16: no horizontal scroll at the current viewport. */
export async function expectNoHorizontalScroll(page) {
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(0);
}

/** sb-mock: read and clear the forwarded-payload log. */
export const sbMock = {
  async clear(request) { await request.delete(`${SB_MOCK}/_log`); },
  async log(request, key) { return (await request.get(`${SB_MOCK}/_log${key ? `?key=${key}` : ''}`)).json(); },
  async fail(request, key, times = 1, status = 503) { await request.post(`${SB_MOCK}/_fail`, { data: { key, times, status } }); },
};

/**
 * Log in to WordPress. Waits for the login page's scripts to settle and checks both fields hold
 * their values before submitting: wp-login.php can reset the password field while it initialises.
 */
export async function wpLogin(page, name, user, pass) {
  await page.goto(site(name, '/wp-login.php'), { waitUntil: 'load' });
  const userField = page.locator('#user_login');
  const passField = page.locator('#user_pass');
  await userField.fill(user);
  await passField.fill(pass);
  await expect(userField).toHaveValue(user);
  await expect(passField).toHaveValue(pass);
  await Promise.all([page.waitForURL(/wp-admin/), page.click('#wp-submit')]);
}
