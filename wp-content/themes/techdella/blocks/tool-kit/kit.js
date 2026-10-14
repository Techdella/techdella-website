// Shared runtime for the free tools (§12.1). Each blocks/tool-<name>/tool.js
// imports this and calls tool(name, { run, render, summary }); esbuild bundles
// it into assets/build/tools/<name>.js, which loads only on that tool's page.
//
// Flow: submit → validate required fields → run() (sync or async) → first use
// fires `td:tool-used` (popups wait for it, §13.1) + td.track('tool_used') →
// if the page is email-gated (gate=email) and not yet unlocked, the result
// summary goes into the tool_gate form and results show after `td:form-success`;
// otherwise results render straight into the aria-live region.

const td = (window.td = window.td || {});
const ready = td.ready || ((fn) => (document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', fn, { once: true }) : fn()));

/** Create an element: el('p', { class: 'x', text: 'hi' }, child, …). Text only — never innerHTML. */
export function el(tag, attrs = {}, ...children) {
  const node = document.createElement(tag);
  for (const [k, v] of Object.entries(attrs || {})) {
    if (v === null || v === undefined || v === false) continue;
    if (k === 'text') node.textContent = String(v);
    else if (k === 'class') node.className = v;
    else if (k.startsWith('on') && typeof v === 'function') node.addEventListener(k.slice(2), v);
    else node.setAttribute(k, v === true ? '' : String(v));
  }
  children.flat().forEach((c) => { if (c !== null && c !== undefined && c !== false) node.append(c instanceof Node ? c : String(c)); });
  return node;
}

/** Result section: heading + body. */
export const section = (title, ...body) => el('section', { class: 'td-tool__section' }, el('h3', { class: 'td-tool__h', text: title }), ...body);

/** Stat tiles: [[value, label], …]. */
export const stats = (rows) => el('dl', { class: 'td-tool__stats' }, rows.map(([v, l]) => el('div', { class: 'td-tool__stat' }, el('dt', { text: l }), el('dd', { text: v }))));

/** Check list: [{ status: 'pass'|'warn'|'fail'|'info', label, detail }]. */
const STATUS = { pass: 'Good', warn: 'Improve', fail: 'Fix', info: 'Note' };
export const checks = (rows) => el('ul', { class: 'td-tool__checks', role: 'list' }, rows.map((r) => el('li', { class: `td-tool__check-row is-${r.status}` },
  el('span', { class: 'td-tool__badge', text: STATUS[r.status] || r.status }),
  el('div', {}, el('p', { class: 'td-tool__check-label', text: r.label }), r.detail ? el('p', { class: 'td-tool__check-detail', text: r.detail }) : null))));

/** Copy-to-clipboard button with a status message for screen readers. */
export function copyButton(label, getText) {
  const btn = el('button', { type: 'button', class: 'td-btn td-btn--secondary td-btn--sm td-tool__copy', text: label });
  btn.addEventListener('click', async () => {
    const text = getText();
    let ok = false;
    try { await navigator.clipboard.writeText(text); ok = true; } catch {
      const ta = el('textarea', { class: 'td-tool__sr', 'aria-hidden': 'true' });
      ta.value = text; document.body.append(ta); ta.select();
      try { ok = document.execCommand('copy'); } catch { ok = false; }
      ta.remove();
    }
    btn.textContent = ok ? 'Copied' : 'Copy failed: select and copy by hand';
    setTimeout(() => { btn.textContent = label; }, 2000);
  });
  return btn;
}

/** POST JSON to /wp-json/techdella/v1/<path>; throws Error(message) on failure. */
export async function api(root, path, body) {
  const base = root.dataset.rest || ((window.TD && window.TD.rest) || '/wp-json/techdella/v1/');
  let res;
  try {
    res = await fetch(base + path, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(body) });
  } catch {
    throw new Error('We couldn’t reach the server. Check your connection and try again.');
  }
  let data = {};
  try { data = await res.json(); } catch { /* non-JSON */ }
  if (!res.ok || !data.ok) {
    const err = new Error(data.message || 'Something went wrong. Please try again.');
    err.code = data.code || String(res.status);
    err.errors = data.errors || {};
    throw err;
  }
  return data;
}

export const num = (n, d = 0) => Number(n).toLocaleString('en-US', { maximumFractionDigits: d, minimumFractionDigits: d });
export const words = (text) => (String(text).match(/[\p{L}\p{N}][\p{L}\p{N}'’-]*/gu) || []);

/**
 * Mount a tool.
 * @param {string} name tool name (matches data-td-tool-app)
 * @param {{run:(values:object, ctx:object)=>any, render:(result:any, out:HTMLElement, ctx:object)=>void, summary?:(result:any)=>string, validate?:(values:object)=>object}} spec
 */
export function tool(name, spec) {
  ready(() => document.querySelectorAll(`[data-td-tool-app="${name}"]`).forEach((root) => mount(root, name, spec)));
}

function mount(root, name, spec) {
  if (root.dataset.tdToolReady) return;
  root.dataset.tdToolReady = '1';
  const form = root.querySelector('[data-td-tool-form]');
  const out = root.querySelector('[data-td-tool-results]');
  const errorBox = form.querySelector('.td-tool__error');
  const submit = form.querySelector('.td-tool__submit');
  const label = submit && submit.querySelector('.td-tool__submit-label');
  const gateBox = root.querySelector('[data-td-tool-gate]');
  const slug = root.dataset.slug || name;
  const storeKey = `td_tool_unlocked_${slug}`;
  let unlocked = !gateBox || (td.store && td.store.get(storeKey) === '1');
  let used = false;
  let pending = null;
  const ctx = { root, form, api: (path, body) => api(root, path, body) };

  const values = () => {
    const v = {};
    form.querySelectorAll('input,select,textarea').forEach((i) => {
      if (!i.name) return;
      v[i.name] = i.type === 'checkbox' ? i.checked : i.value.trim();
    });
    return v;
  };
  const setFieldError = (fieldName, msg) => {
    const input = form.querySelector(`[name="${fieldName}"]`);
    if (!input) return null;
    const err = form.querySelector(`#${input.id}-error`);
    if (msg) input.setAttribute('aria-invalid', 'true'); else input.removeAttribute('aria-invalid');
    if (err) { err.textContent = msg || ''; err.hidden = !msg; }
    return input;
  };
  const showError = (msg) => { errorBox.textContent = msg || ''; errorBox.hidden = !msg; };

  function show(result) {
    out.replaceChildren();
    spec.render(result, out, ctx);
    out.classList.add('is-filled');
  }

  function firstUse() {
    if (used) return;
    used = true;
    try { window.dispatchEvent(new CustomEvent('td:tool-used', { detail: { tool: name } })); } catch { /* old browsers */ }
    if (td.track) td.track('tool_used', { tool: name });
  }

  form.addEventListener('input', (e) => { if (e.target.getAttribute('aria-invalid')) setFieldError(e.target.name, ''); });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    showError('');
    const v = values();
    // Required fields + tool-specific checks.
    const errors = {};
    form.querySelectorAll('[required]').forEach((i) => { if (!v[i.name]) errors[i.name] = 'This field is required.'; });
    Object.assign(errors, spec.validate ? spec.validate(v) || {} : {});
    form.querySelectorAll('[name]').forEach((i) => setFieldError(i.name, errors[i.name] || ''));
    const bad = Object.keys(errors);
    if (bad.length) {
      const first = form.querySelector(`[name="${bad[0]}"]`);
      if (first) first.focus();
      return;
    }
    submit.disabled = true;
    submit.setAttribute('aria-busy', 'true');
    const prev = label.textContent;
    label.textContent = spec.busyLabel || 'Working…';
    let result;
    try {
      result = await spec.run(v, ctx);
    } catch (err) {
      showError(err.message || 'Something went wrong. Please try again.');
      if (err.errors) Object.entries(err.errors).forEach(([k]) => setFieldError(k, 'Check this field.'));
      return;
    } finally {
      submit.disabled = false;
      submit.removeAttribute('aria-busy');
      label.textContent = prev;
    }
    firstUse();
    if (unlocked) {
      show(result);
      return;
    }
    pending = result;
    const summaryInput = gateBox.querySelector('input[name="result_summary"]');
    if (summaryInput) summaryInput.value = (spec.summary ? spec.summary(result) : '').slice(0, 1000);
    out.replaceChildren();
    out.classList.remove('is-filled');
    gateBox.hidden = false;
    const note = gateBox.querySelector('.td-tool__gate-note');
    if (note) note.focus();
  });

  if (gateBox) {
    document.addEventListener('td:form-success', (e) => {
      const d = e.detail || {};
      if (d.key !== 'tool_gate' || !(d.root && gateBox.contains(d.root))) return;
      unlocked = true;
      if (td.store) td.store.set(storeKey, '1');
      gateBox.hidden = true;
      if (pending) { show(pending); pending = null; }
      const h = out.querySelector('.td-tool__h');
      if (h) { h.setAttribute('tabindex', '-1'); h.focus(); }
    });
  }
}
