// On-Page SEO Checker (§12.1). Our server fetches the page (SSRF-guarded,
// POST /techdella/v1/tools/fetch) and returns plain facts; the checks below
// turn them into advice.
import { tool, el, section, stats, checks, num } from '../tool-kit/kit.js';

const has = (hay, kw) => !!kw && String(hay || '').toLowerCase().includes(kw.toLowerCase());
const BOT_LABEL = { allowed: 'Allowed', blocked: 'Blocked', partial: 'Partly blocked' };

export function audit(d, keyword) {
  const f = d.facts;
  const t = [...(f.title || '')].length;
  const m = [...(f.meta_description || '')].length;
  const noindex = /noindex/i.test(`${f.meta_robots} ${f.x_robots_tag}`);
  const rows = [];
  const push = (status, label, detail) => rows.push({ status, label, detail });

  push(!f.title ? 'fail' : t >= 30 && t <= 60 ? 'pass' : 'warn', f.title ? `Title: ${t} characters` : 'No title tag', f.title ? `"${f.title}" · Aim for 30–60 characters so it isn’t cut off in results.` : 'Every page needs a unique <title>.');
  push(!m ? 'fail' : m >= 120 && m <= 160 ? 'pass' : 'warn', m ? `Meta description: ${m} characters` : 'No meta description', m ? 'Aim for about 140–155 characters, written to earn the click.' : 'Without one, search engines pick a snippet for you.');
  push(f.h1.length === 1 ? 'pass' : f.h1.length ? 'warn' : 'fail', f.h1.length === 1 ? `One H1: "${f.h1[0]}"` : `${f.h1.length} H1 headings`, f.h1.length === 1 ? null : 'Use exactly one H1 that says what the page is about.');
  push(noindex ? 'fail' : 'pass', noindex ? 'The page is set to noindex' : 'Indexable (no noindex)', noindex ? `robots: ${f.meta_robots || f.x_robots_tag}. Remove noindex if this page should rank.` : null);
  if (!f.canonical) push('warn', 'No canonical tag', 'Add a self-referencing canonical so duplicates (UTM links, ?ref=) point here.');
  else push(f.canonical.replace(/\/$/, '') === f.url.replace(/\/$/, '') ? 'pass' : 'info', `Canonical: ${f.canonical}`, f.canonical.replace(/\/$/, '') === f.url.replace(/\/$/, '') ? null : 'It points somewhere else. Fine for duplicates; a problem if this page should rank.');
  push(f.https ? 'pass' : 'fail', f.https ? 'Served over HTTPS' : 'Not served over HTTPS', f.https ? null : 'Move the page to HTTPS.');
  push(f.word_count >= 300 ? 'pass' : 'warn', `${num(f.word_count)} words on the page`, f.word_count >= 300 ? null : 'Thin pages rarely rank. Answer the question fully, with examples.');
  push(f.images_missing_alt ? 'fail' : 'pass', f.images_missing_alt ? `${f.images_missing_alt} of ${f.images} images have no alt attribute` : `All ${f.images} images have alt attributes`, f.images_missing_alt ? `Describe what each image shows. ${f.missing_alt_srcs.slice(0, 3).join(', ')}` : null);
  push(f.schema_types.length ? 'pass' : 'warn', f.schema_types.length ? `Structured data: ${f.schema_types.join(', ')}` : 'No structured data found', f.schema_types.length ? null : 'Add JSON-LD (Organization, WebPage, and the type that fits: Service, Article, Product…).');
  push(f.viewport ? 'pass' : 'fail', f.viewport ? 'Mobile viewport set' : 'No mobile viewport tag', f.viewport ? null : 'Add <meta name="viewport" content="width=device-width, initial-scale=1">.');
  push(f.lang ? 'pass' : 'warn', f.lang ? `Language: ${f.lang}` : 'No lang attribute on <html>', null);
  push(f.og_title && f.og_image ? 'pass' : 'warn', f.og_title && f.og_image ? 'Open Graph title and image set' : 'Open Graph tags incomplete', f.og_title && f.og_image ? null : 'Add og:title and og:image so shared links look right.');
  push(f.links_internal >= 3 ? 'pass' : 'warn', `${f.links_internal} internal, ${f.links_external} external links`, f.links_internal >= 3 ? null : 'Link to at least 3 related pages on your site.');

  const kw = [];
  if (keyword) {
    [['title', f.title], ['H1', f.h1.join(' ')], ['meta description', f.meta_description], ['first 100 words', f.first_words]].forEach(([where, text]) => {
      kw.push({ status: has(text, keyword) ? 'pass' : where === 'meta description' ? 'warn' : 'fail', label: `${has(text, keyword) ? 'In' : 'Not in'} the ${where}`, detail: null });
    });
  }

  const ai = [{ status: d.llms_txt ? 'pass' : 'info', label: d.llms_txt ? 'llms.txt found' : 'No llms.txt', detail: d.llms_txt ? null : 'Optional: a plain-Markdown summary for AI tools at /llms.txt.' },
    { status: d.robots.found ? 'pass' : 'info', label: d.robots.found ? 'robots.txt found' : 'No robots.txt (everything is allowed)', detail: null }];
  const score = Math.round((rows.filter((r) => r.status === 'pass').length / rows.filter((r) => r.status !== 'info').length) * 100);
  return { f, rows, kw, ai, bots: d.robots.bots, score };
}

tool('on-page-seo-checker', {
  busyLabel: 'Checking…',
  validate(v) {
    if (v.url && !/^(https?:\/\/)?[^\s/.]+(\.[^\s/.]+)+(:\d+)?(\/\S*)?$/i.test(v.url)) return { url: 'Enter a full web address, like https://example.com/page/.' };
    return {};
  },
  async run(v, ctx) {
    const data = await ctx.api('tools/fetch', { url: v.url, keyword: v.keyword });
    return audit(data, v.keyword);
  },
  render(r, out) {
    const f = r.f;
    out.append(
      section('Summary',
        el('p', { class: 'td-tool__p' }, 'Checked ', el('strong', { text: f.url }), f.redirects ? ` after ${f.redirects} redirect${f.redirects === 1 ? '' : 's'}.` : '.'),
        stats([[`${r.score}%`, 'Checks passed'], [`HTTP ${f.status}`, 'Status'], [`${num(f.response_ms)} ms`, 'Fetch time'], [num(f.word_count), 'Words']])),
      section('On-page checks', checks(r.rows)),
    );
    if (r.kw.length) out.append(section('Target keyword', checks(r.kw)));
    out.append(
      section('AI search readiness',
        checks(r.ai),
        el('div', { class: 'td-tool__table-wrap' }, el('table', { class: 'td-tool__table' },
          el('caption', { class: 'td-tool__sr', text: 'AI crawler access from robots.txt' }),
          el('thead', {}, el('tr', {}, el('th', { scope: 'col', text: 'Crawler' }), el('th', { scope: 'col', text: 'Access' }))),
          el('tbody', {}, Object.entries(r.bots).map(([b, s]) => el('tr', {}, el('th', { scope: 'row', text: b }), el('td', { text: BOT_LABEL[s] || s }))))))),
      section('Heading outline', f.headings.length
        ? el('ul', { class: 'td-tool__list' }, f.headings.slice(0, 30).map((h) => el('li', { style: `margin-left:${(h.level - 1) * 12}px`, text: `H${h.level} · ${h.text || '(empty)'}` })))
        : el('p', { class: 'td-tool__p', text: 'No headings found.' })),
    );
  },
  summary: (r) => `${r.f.url}: ${r.score}% of checks passed. ${r.rows.filter((x) => x.status === 'fail').map((x) => x.label).join('; ')}`,
});
