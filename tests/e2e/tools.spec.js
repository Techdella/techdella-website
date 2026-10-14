// Free tools (§12.1): every tool page mounts its app, runs, and shows results
// (or the email gate / a clear error), with real content below and no serious
// axe issues. Server tools: the on-page checker refuses private URLs (SSRF
// guard) and the AI tools say when AI_API_KEY isn't configured: never fake output.
import { test, expect } from '@playwright/test';
import { site, expectNoA11yViolations, expectNoHorizontalScroll } from './helpers.js';

const TOOLS = [
  { slug: 'free-utm-parameter-generator', app: 'utm-parameter-generator', fill: { url: 'https://techdella.com/growth-audit/', utm_source: 'LinkedIn', utm_medium: 'paid_social', utm_campaign: 'Q4 Growth Audit' }, expect: 'utm_source=linkedin' },
  { slug: 'free-hashtag-generator', app: 'hashtag-generator', fill: { topic: 'fintech product launch in Lagos' }, expect: '#Fintech' },
  { slug: 'free-readability-checker', app: 'readability-checker', fill: { text: 'We set up marketing automation for you. Your team spends less time on manual work. Short sentences are easier to read on a phone.' }, expect: 'Flesch' },
  { slug: 'free-keyword-density-checker', app: 'keyword-density-checker', fill: { keyword: 'seo', text: 'B2B SEO takes time. Good SEO starts with research. Technical SEO keeps pages fast and crawlable for search engines. '.repeat(4) }, expect: 'seo' },
  { slug: 'email-subject-line-analyzer', app: 'email-subject-line-analyzer', fill: { subject: 'Your audit report now shows AI search visibility' }, expect: 'characters' },
];

const fillForm = async (root, values) => {
  for (const [name, value] of Object.entries(values)) await root.locator(`[name="${name}"]`).fill(value);
};

// Every tool is email-gated (gate=email, owner 11 Oct 2026): results show after the tool_gate
// form succeeds. Delivery of that form to Startbuddi is covered in forms.spec.js; here the
// browser gets a success reply so the shared sb-mock log isn't touched from a parallel worker.
const unlock = async (page, root) => {
  const gate = root.locator('[data-td-tool-gate]');
  await expect(gate).toBeVisible();
  await expect(gate).toContainText('Enter your email');
  await gate.locator('input[name="email"]').fill('tools-e2e@example.com');
  await gate.locator('button[type="submit"]').click();
};

test.describe('tool pages', () => {
  for (const t of TOOLS) {
    test(`${t.app}: runs in the browser, asks for an email, then shows results`, async ({ page }) => {
      await page.route('**/submit/tool_gate', (route) => route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ ok: true }) }));
      await page.goto(site('marketing', `/tools/${t.slug}/`));
      const root = page.locator(`[data-td-tool-app="${t.app}"]`);
      await expect(root).toBeVisible();
      await fillForm(root, t.fill);
      await root.locator('.td-tool__submit').click();
      await unlock(page, root);
      const results = root.locator('[data-td-tool-results]');
      await expect(results).toContainText(t.expect, { ignoreCase: true });
      // §12.1: real content below the tool.
      await expect(page.getByRole('heading', { name: /How to use this/ })).toBeVisible();
      await expect(page.locator('.td-faq details').first()).toBeAttached();
      await expectNoA11yViolations(page);
    });
  }

  test('required fields are validated inline', async ({ page }) => {
    await page.goto(site('marketing', '/tools/free-readability-checker/'));
    const root = page.locator('[data-td-tool-app="readability-checker"]');
    await root.locator('.td-tool__submit').click();
    await expect(root.locator('[name="text"]')).toHaveAttribute('aria-invalid', 'true');
    await expect(root.locator('[data-td-tool-results]')).toBeEmpty();
  });

  test('on-page SEO checker refuses private URLs', async ({ page }) => {
    await page.goto(site('marketing', '/tools/on-page-seo-checker/'));
    const root = page.locator('[data-td-tool-app="on-page-seo-checker"]');
    await root.locator('[name="url"]').fill('http://127.0.0.1/');
    await root.locator('.td-tool__submit').click();
    await expect(root.locator('.td-tool__error')).toBeVisible();
    await expect(root.locator('[data-td-tool-results]')).toBeEmpty();
  });

  for (const [slug, fill] of [
    ['persona-builder', { product: 'Invoicing software for logistics companies', audience: 'Finance manager' }],
    ['ai-business-name-generator', { industry: 'Fintech', keywords: 'Cross-border payments for freelancers' }],
  ]) {
    test(`${slug}: AI tool says when it is not configured, never fakes output`, async ({ page, request }) => {
      const probe = await request.post(site('marketing', '/wp-json/techdella/v1/tools/ai'), { data: { tool: slug, input: fill } });
      test.skip(probe.status() !== 503, 'AI_API_KEY is configured here; this checks the unconfigured path.');
      await page.goto(site('marketing', `/tools/${slug}/`));
      const root = page.locator(`[data-td-tool-app="${slug}"]`);
      await fillForm(root, fill);
      await root.locator('.td-tool__submit').click();
      await expect(root.locator('.td-tool__error')).toContainText('not configured');
      await expect(root.locator('[data-td-tool-results]')).toBeEmpty();
      await expect(page.getByText(/AI-generated/).first()).toBeVisible();
    });
  }

  test('no horizontal scroll on a tool page at 390px', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(site('marketing', '/tools/free-utm-parameter-generator/'));
    await expectNoHorizontalScroll(page);
  });
});
