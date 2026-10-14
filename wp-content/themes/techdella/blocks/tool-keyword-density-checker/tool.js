// Keyword Density Checker (§12.1). Counts, density and top words/phrases, in the browser.
import { tool, el, section, stats, checks, num, words } from '../tool-kit/kit.js';

const STOP = new Set('a about above after again against all am an and any are as at be because been before being below between both but by can could did do does doing down during each few for from further had has have having he her here hers him his how i if in into is it its itself just me more most my no nor not now of off on once only or other our ours out over own same she should so some such than that the their them then there these they this those through to too under until up very was we were what when where which while who whom why will with you your yours us also get got may might must shall would'.split(' '));

const norm = (s) => s.toLowerCase().replace(/[’]/g, "'");

export function countPhrase(tokens, phrase) {
  const p = words(norm(phrase));
  if (!p.length) return 0;
  let n = 0;
  for (let i = 0; i <= tokens.length - p.length; i++) {
    let ok = true;
    for (let j = 0; j < p.length; j++) if (tokens[i + j] !== p[j]) { ok = false; break; }
    if (ok) n++;
  }
  return n;
}

function top(tokens, size, limit = 10) {
  const m = new Map();
  for (let i = 0; i <= tokens.length - size; i++) {
    const g = tokens.slice(i, i + size);
    if (STOP.has(g[0]) || STOP.has(g[size - 1]) || g.some((t) => /^\d+$/.test(t) || t.length < 2)) continue;
    const k = g.join(' ');
    m.set(k, (m.get(k) || 0) + 1);
  }
  return [...m.entries()].filter(([, c]) => size === 1 || c > 1).sort((a, b) => b[1] - a[1]).slice(0, limit);
}

tool('keyword-density-checker', {
  validate: (v) => (v.text && words(v.text).length < 50 ? { text: 'Paste at least 50 words.' } : {}),
  run(v) {
    const tokens = words(norm(v.text));
    const first100 = tokens.slice(0, 100);
    const kws = [v.keyword, v.keyword2].filter(Boolean).map((k) => {
      const count = countPhrase(tokens, k);
      const len = words(k).length || 1;
      return { k, count, density: (count * len * 100) / Math.max(1, tokens.length), early: countPhrase(first100, k) > 0 };
    });
    return { total: tokens.length, kws, one: top(tokens, 1), two: top(tokens, 2), three: top(tokens, 3) };
  },
  render(r, out) {
    const rows = [];
    r.kws.forEach((k) => {
      rows.push({ status: k.count ? 'pass' : 'fail', label: `"${k.k}" appears ${k.count} time${k.count === 1 ? '' : 's'} (${num(k.density, 2)}%)`, detail: k.count ? null : 'Use the exact phrase at least once, in the intro or a heading.' });
      if (k.density > 3) rows.push({ status: 'warn', label: `"${k.k}" may read as stuffed`, detail: 'There’s no ideal density. Above about 3%, copy often reads repetitive; use synonyms and related terms instead.' });
      rows.push({ status: k.early ? 'pass' : 'warn', label: k.early ? `"${k.k}" is in the first 100 words` : `"${k.k}" isn’t in the first 100 words`, detail: k.early ? null : 'Answer-first intros that name the topic help readers and search engines.' });
    });
    const table = (title, list) => section(title, list.length
      ? el('div', { class: 'td-tool__table-wrap' }, el('table', { class: 'td-tool__table' },
        el('thead', {}, el('tr', {}, el('th', { scope: 'col', text: 'Phrase' }), el('th', { scope: 'col', text: 'Count' }), el('th', { scope: 'col', text: 'Density' }))),
        el('tbody', {}, list.map(([k, c]) => el('tr', {}, el('td', { text: k }), el('td', { text: num(c) }), el('td', { text: `${num((c * k.split(' ').length * 100) / Math.max(1, r.total), 2)}%` }))))))
      : el('p', { class: 'td-tool__p', text: 'No repeated phrases.' }));
    out.append(
      section('Overview', stats([[num(r.total), 'Words'], ...r.kws.map((k) => [`${num(k.density, 2)}%`, `"${k.k}"`])])),
      section('Your keywords', checks(rows)),
      table('Top words', r.one), table('Top 2-word phrases', r.two), table('Top 3-word phrases', r.three),
    );
  },
  summary: (r) => `${r.total} words. ${r.kws.map((k) => `"${k.k}": ${k.count}× (${num(k.density, 2)}%)`).join('; ')}`,
});
