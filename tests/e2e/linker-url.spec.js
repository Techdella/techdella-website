// Google's cross-domain linker (?_gl=…) is removed from the address bar after load; other params stay.
import { test, expect } from '@playwright/test';
import { site } from './helpers.js';

test('the _gl linker parameter is dropped from the visible URL, other query params kept', async ({ page }) => {
  await page.goto(site('marketing', '/?_gl=1*abc*_ga*MTIz&utm_source=test'));
  await expect.poll(() => page.url(), { timeout: 8000 }).not.toContain('_gl=');
  expect(page.url()).toContain('utm_source=test');
});
