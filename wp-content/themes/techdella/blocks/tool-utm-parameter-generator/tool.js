// UTM Parameter Generator (§12.1). Runs entirely in the browser.
import { tool, el, section, checks, copyButton } from '../tool-kit/kit.js';

const KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

const toUrl = (raw) => {
  const v = raw.trim();
  try { return new URL(/^[a-z][a-z0-9+.-]*:\/\//i.test(v) ? v : `https://${v}`); } catch { return null; }
};

const tidy = (v) => v.trim().toLowerCase().replace(/\s+/g, '-').replace(/-{2,}/g, '-');

tool('utm-parameter-generator', {
  validate(v) {
    const u = toUrl(v.url || '');
    if (v.url && (!u || !/^https?:$/.test(u.protocol) || !u.hostname.includes('.'))) return { url: 'Enter a full web address, like https://example.com/page/.' };
    return {};
  },
  run(v) {
    const url = toUrl(v.url);
    const notes = [];
    const values = {};
    KEYS.forEach((k) => {
      let val = v[k] || '';
      if (!val) return;
      if (v.clean) val = tidy(val);
      else if (/\s/.test(val)) notes.push({ status: 'warn', label: `${k} contains spaces`, detail: 'Spaces become %20 or + in reports. Hyphens are easier to read and filter.' });
      if (!v.clean && /[A-Z]/.test(val)) notes.push({ status: 'warn', label: `${k} has capital letters`, detail: 'Analytics treats "LinkedIn" and "linkedin" as two sources. Pick one case and keep it.' });
      values[k] = val;
    });
    KEYS.forEach((k) => {
      if (url.searchParams.has(k)) notes.push({ status: 'info', label: `Replaced an existing ${k}`, detail: 'The URL already had this parameter; the new value wins.' });
      url.searchParams.delete(k);
    });
    KEYS.forEach((k) => { if (values[k]) url.searchParams.set(k, values[k]); });
    if (url.protocol === 'http:') notes.push({ status: 'warn', label: 'The page uses http://', detail: 'Use the https:// version if the site has one, so the redirect doesn’t drop the referrer.' });
    if (['google', 'bing'].includes(values.utm_source) && values.utm_medium === 'cpc') notes.push({ status: 'info', label: 'Auto-tagging may already cover this', detail: 'Google Ads and Microsoft Ads can tag clicks for you. Use manual UTMs only if auto-tagging is off.' });
    return { url: url.toString(), values, notes };
  },
  render(r, out) {
    const code = el('code', { class: 'td-tool__code', text: r.url });
    out.append(
      section('Your campaign URL',
        el('div', { class: 'td-tool__output' }, code, el('div', { class: 'td-tool__row' }, copyButton('Copy URL', () => r.url), copyButton('Copy parameters only', () => r.url.split('?')[1] || '')))),
      section('Parameters',
        el('div', { class: 'td-tool__table-wrap' }, el('table', { class: 'td-tool__table' },
          el('thead', {}, el('tr', {}, el('th', { scope: 'col', text: 'Parameter' }), el('th', { scope: 'col', text: 'Value' }))),
          el('tbody', {}, Object.entries(r.values).map(([k, v]) => el('tr', {}, el('th', { scope: 'row', text: k }), el('td', { text: v }))))))),
    );
    if (r.notes.length) out.append(section('Notes', checks(r.notes)));
  },
  summary: (r) => r.url,
});
