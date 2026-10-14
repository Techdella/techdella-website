// Technical SEO (§8.2, §8.3, §5.3, §16): one JSON-LD graph with one Organization,
// canonicals, noindex (meta + X-Robots-Tag), robots.txt, llms.txt, sitemaps.
// Needs the local Docker stack (WP-CLI via docker compose for fixtures).
import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { site, SITES } from './helpers.js';

const NAMES = ['marketing', 'journal', 'studios'];
const FORBIDDEN = ['FAQPage', 'HowTo', 'AggregateRating'];

/** Run WP-CLI in a site's container; returns trimmed stdout. */
function wp(name, args) {
  return execFileSync('docker', ['compose', 'exec', '-T', '-u', 'www-data', `wp-${name}`, 'wp', ...args], {
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
  }).trim();
}

/** All JSON-LD scripts on the page, parsed. */
async function jsonLd(page) {
  const raw = await page.locator('script[type="application/ld+json"]').allTextContents();
  return raw.map((text) => JSON.parse(text));
}

/** Every object in a JSON tree. */
function walk(value, out = []) {
  if (Array.isArray(value)) value.forEach((v) => walk(v, out));
  else if (value && typeof value === 'object') {
    out.push(value);
    Object.values(value).forEach((v) => walk(v, out));
  }
  return out;
}

const types = (node) => [].concat(node['@type'] || []);

async function robotsOf(page, res) {
  const meta = await page.locator('meta[name="robots"]').evaluateAll((els) => els.map((e) => e.content));
  return { meta, header: res.headers()['x-robots-tag'] || '' };
}

for (const name of NAMES) {
  test.describe(`SEO on ${name}`, () => {
    test(`one JSON-LD graph, one Organization, nothing forbidden @smoke`, async ({ page }) => {
      await page.goto(site(name, '/'));
      const docs = await jsonLd(page);
      expect(docs).toHaveLength(1);
      const graph = docs[0]['@graph'];
      expect(Array.isArray(graph)).toBe(true);
      expect(docs[0]['@context']).toBe('https://schema.org');

      const orgs = graph.filter((n) => types(n).includes('Organization'));
      expect(orgs).toHaveLength(1);
      expect(orgs[0]['@id']).toBe(`${SITES.marketing.replace(/\/$/, '')}/#org`);
      expect(orgs[0].name).toBe('TechDella');
      expect(JSON.stringify(orgs[0])).not.toContain('[URLS]');

      const ids = graph.map((n) => n['@id']).filter(Boolean);
      expect(new Set(ids).size).toBe(ids.length);
      for (const node of walk(docs[0])) {
        for (const t of types(node)) expect(FORBIDDEN).not.toContain(t);
        expect(node).not.toHaveProperty('aggregateRating');
      }
      expect(graph.some((n) => types(n).includes('WebSite'))).toBe(true);
      expect(graph.some((n) => types(n).some((t) => /Page$/.test(t)))).toBe(true);
      const website = graph.find((n) => types(n).includes('WebSite'));
      expect(Boolean(website.potentialAction)).toBe(name === 'journal');
    });

    test(`canonical: once, absolute, trailing slash, no tracking params @smoke`, async ({ page }) => {
      await page.goto(site(name, '/?utm_source=x&ref=y&gclid=z'));
      const canonicals = await page.locator('link[rel="canonical"]').evaluateAll((els) => els.map((e) => e.href));
      expect(canonicals).toHaveLength(1);
      expect(canonicals[0]).toBe(site(name, '/'));
      expect(canonicals[0]).not.toContain('?');
    });

    test(`home is indexable locally (local is not staging) @smoke`, async ({ page }) => {
      const res = await page.goto(site(name, '/'));
      const { meta, header } = await robotsOf(page, res);
      expect(meta.length).toBeLessThanOrEqual(1);
      expect(meta.join(',')).not.toMatch(/noindex/);
      expect(header).not.toMatch(/noindex/);
    });

    test(`search results are noindex (meta + header) @smoke`, async ({ page }) => {
      const res = await page.goto(site(name, '/?s=test'));
      const { meta, header } = await robotsOf(page, res);
      expect(meta).toHaveLength(1);
      expect(meta[0]).toMatch(/noindex/);
      expect(header).toMatch(/noindex/);
    });

    test(`robots.txt rules, sitemap and AI-crawler policy @smoke`, async ({ request }) => {
      const res = await request.get(site(name, '/robots.txt'));
      expect(res.status()).toBe(200);
      const body = await res.text();
      expect(body).toContain('User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\nDisallow: /?s=\nDisallow: /search/\n');
      expect(body).toContain(`Sitemap: ${site(name, '/sitemap_index.xml')}`);
      let policy = 'allow-all';
      try {
        policy = wp(name, ['option', 'get', 'td_ai_crawler_policy']) || 'allow-all';
      } catch {
        /* option not set: default */
      }
      if (policy === 'block-training') {
        expect(body).toContain('User-agent: GPTBot\nDisallow: /');
        expect(body).toContain('User-agent: Google-Extended\nDisallow: /');
      } else {
        for (const bot of ['GPTBot', 'OAI-SearchBot', 'PerplexityBot', 'ClaudeBot', 'Google-Extended']) {
          expect(body).not.toContain(`User-agent: ${bot}`);
        }
      }
      expect(body).not.toMatch(/^Disallow: \/$/m);
    });

    test(`llms.txt is text/plain Markdown with service URLs @smoke`, async ({ request }) => {
      const res = await request.get(site(name, '/llms.txt'));
      expect(res.status()).toBe(200);
      expect(res.headers()['content-type']).toMatch(/^text\/plain;\s*charset=utf-8/i);
      const body = await res.text();
      expect(body).toMatch(/^# TechDella/);
      expect(body).toContain('## Contact');
      expect(body).toContain(`(${SITES.marketing.replace(/\/$/, '')}/book-a-discovery-call/)`);
      expect(body).toContain('Joanna Okedara-Kalu');
      expect(body).not.toMatch(/\[(CONFIRM|PRICE)/);
      if (name === 'marketing') {
        for (const slug of ['seo', 'ai-search-optimization', 'paid-ads', 'lead-generation', 'email-marketing', 'marketing-automation']) {
          expect(body).toContain(site('marketing', `/services/${slug}/`));
        }
      }
      if (name === 'studios') expect(body).toContain(site('studios', '/services/mvp-development/'));
    });

    test(`sitemap index is up and lists no document/thank-you sitemaps @smoke`, async ({ request }) => {
      const res = await request.get(site(name, '/sitemap_index.xml'));
      expect(res.status()).toBe(200);
      const body = await res.text();
      expect(body).toContain('<sitemapindex');
      expect(body).not.toMatch(/document-sitemap|thank-you-sitemap|td_team-sitemap|td_testimonial-sitemap|platform-sitemap/);
    });
  });
}

test.describe('noindex post types', () => {
  test('a thank-you post is noindex in meta and X-Robots-Tag @smoke', async ({ page }) => {
    let exists = '';
    try {
      exists = wp('marketing', ['eval', 'echo post_type_exists("thank-you") ? "1" : "0";']);
    } catch {
      exists = '0';
    }
    test.skip(exists !== '1', 'thank-you post type not registered');
    const id = wp('marketing', ['post', 'create', '--post_type=thank-you', '--post_status=publish', '--post_title=SEO e2e thank you', '--post_name=seo-e2e-thank-you', '--porcelain']);
    try {
      const res = await page.goto(site('marketing', '/thank-you/seo-e2e-thank-you/'));
      expect(res.status()).toBe(200);
      const { meta, header } = await robotsOf(page, res);
      expect(meta).toHaveLength(1);
      expect(meta[0]).toMatch(/noindex/);
      expect(header).toMatch(/noindex/);
      const sitemap = await page.request.get(site('marketing', '/sitemap_index.xml'));
      expect(await sitemap.text()).not.toContain('thank-you');
    } finally {
      wp('marketing', ['post', 'delete', id, '--force']);
    }
  });

  test('a page with breadcrumbs: BreadcrumbList matches the trail, canonical is clean', async ({ page }) => {
    const id = wp('marketing', ['post', 'create', '--post_type=page', '--post_status=publish', '--post_title=SEO e2e page', '--post_name=seo-e2e-page', '--porcelain']);
    try {
      await page.goto(site('marketing', '/seo-e2e-page/?utm_campaign=a&fbclid=b'));
      const canonicals = await page.locator('link[rel="canonical"]').evaluateAll((els) => els.map((e) => e.href));
      expect(canonicals).toEqual([site('marketing', '/seo-e2e-page/')]);
      const [doc] = await jsonLd(page);
      const crumbs = doc['@graph'].find((n) => types(n).includes('BreadcrumbList'));
      expect(crumbs.itemListElement.map((i) => i.name)).toEqual(['Home', 'SEO e2e page']);
      const webpage = doc['@graph'].find((n) => n['@id'] === `${site('marketing', '/seo-e2e-page/')}#webpage`);
      expect(webpage).toBeTruthy();
      expect(webpage.breadcrumb['@id']).toBe(crumbs['@id']);
    } finally {
      wp('marketing', ['post', 'delete', id, '--force']);
    }
  });
});

test.describe('favicons and manifest (all sites)', () => {
  for (const [name, app] of [['marketing', 'TechDella'], ['journal', 'TechDella Journal'], ['studios', 'TechDella Studios']]) {
    test(`${name}: brand icons, /favicon.ico and /site.webmanifest`, async ({ page, request }) => {
      const ico = await request.get(site(name, '/favicon.ico'));
      expect(ico.status()).toBe(200);
      expect(ico.headers()['content-type']).toContain('image/x-icon');
      const manifest = await request.get(site(name, '/site.webmanifest'));
      expect(manifest.status()).toBe(200);
      const json = await manifest.json();
      expect(json.name).toBe(app);
      expect(json.icons.length).toBeGreaterThanOrEqual(2);
      await page.goto(site(name, '/'));
      await expect(page.locator('link[rel="apple-touch-icon"]')).toHaveAttribute('href', /assets\/img\/icons\/apple-touch-icon\.png$/);
      // Only our icon tags: no WordPress Site Icon (cropped-*.png) left over from the old sites.
      expect(await page.locator('link[rel~="icon"][href*="cropped-"]').count()).toBe(0);
    });
  }
});

test('business guides carry an authored Article; the Organization lists its Studios division', async ({ page }) => {
  await page.goto(site('marketing', '/business/how-to-start-a-mobile-car-wash-business/'));
  const graph = JSON.parse(await page.locator('#td-schema').textContent())['@graph'];
  const article = graph.find((n) => n['@type'] === 'Article');
  expect(article).toBeTruthy();
  expect(article.datePublished).toBeTruthy();
  expect(article.author).toBeTruthy();
  const org = graph.find((n) => n['@type'] === 'Organization' && n.subOrganization);
  expect(org.subOrganization.name).toBe('TechDella Studios');
  expect(org.knowsAbout.length).toBeGreaterThan(5);
  expect(graph.some((n) => n['@type'] === 'HowTo' || n['@type'] === 'FAQPage')).toBe(false);
});
