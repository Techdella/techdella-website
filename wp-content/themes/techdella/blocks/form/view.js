// techdella/form: progressive enhancement for td_form_render() markup (§11).
// Without JS the form posts to the same REST route and gets a 303 redirect.
// With JS: fresh cache-safe token on first interaction, inline validation
// (blur + submit), steps for multi-step briefs, fetch() submit, loading state,
// in-place success with focus moved to it, lazy Turnstile.
const td = (window.td = window.td || {});

const MIN_MS = 3200; // server min-time is 3 s from token issue

let turnstileLoading = null;
function loadTurnstile() {
  if (window.turnstile) return Promise.resolve(window.turnstile);
  if (!turnstileLoading) {
    turnstileLoading = new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
      s.async = true;
      s.onload = () => resolve(window.turnstile);
      s.onerror = reject;
      document.head.appendChild(s);
    });
  }
  return turnstileLoading;
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function initForm(root) {
  const form = root.querySelector('form');
  if (!form || root.dataset.tdReady) return;
  root.dataset.tdReady = '1';
  form.noValidate = true;

  const key = root.dataset.tdForm;
  const msg = (k) => root.dataset['msg' + k.charAt(0).toUpperCase() + k.slice(1)] || '';
  const alertBox = root.querySelector('.td-form__alert[id]');
  const success = root.querySelector('.td-form__success');
  const body = root.querySelector('.td-form__body');
  const submit = form.querySelector('.td-form__submit');
  const submitLabel = submit && submit.querySelector('.td-form__submit-label');
  const tokenInput = form.querySelector('input[name="td_token"]');
  let tokenAt = 0;
  let tokenPromise = null;
  let turnstileId = null;

  const page = form.querySelector('input[name="td_page"]');
  const ref = form.querySelector('input[name="td_ref"]');
  const cleanHref = () => window.location.href.replace(/([?&])(td_fe|dl)=[^&#]*&?/g, '$1').replace(/[?&](#|$)/, '$1');
  if (page) page.value = cleanHref();
  if (ref && document.referrer) ref.value = document.referrer.slice(0, 500);

  function fetchToken() {
    const url = (root.dataset.tokenUrl || ((window.TD && window.TD.rest) || '/wp-json/techdella/v1/') + 'form-token');
    tokenPromise = fetch(url + (url.includes('?') ? '&' : '?') + 'form=' + encodeURIComponent(key), { credentials: 'same-origin', cache: 'no-store' })
      .then((r) => (r.ok ? r.json() : Promise.reject(r.status)))
      .then((d) => { tokenInput.value = d.token; tokenAt = Date.now(); })
      .catch(() => { tokenAt = Date.now(); }); // keep the rendered token
    return tokenPromise;
  }

  function firstTouch() {
    if (tokenPromise) return;
    fetchToken();
    const slot = root.dataset.turnstile && form.querySelector('[data-turnstile-slot]');
    if (slot) {
      loadTurnstile().then((ts) => {
        if (ts && turnstileId === null) turnstileId = ts.render(slot, { sitekey: root.dataset.turnstile, appearance: 'interaction-only' });
      }).catch(() => {});
    }
  }
  form.addEventListener('focusin', firstTouch, { once: true });
  form.addEventListener('pointerdown', firstTouch, { once: true });

  // ---------- validation ----------
  const fieldWrap = (el) => el.closest('[data-field]');
  function setError(wrap, text) {
    if (!wrap) return;
    const err = wrap.querySelector('.td-field__error');
    const inputs = wrap.querySelectorAll('input:not([type=hidden]),select,textarea');
    wrap.classList.toggle('is-invalid', !!text);
    inputs.forEach((i) => { if (text) i.setAttribute('aria-invalid', 'true'); else i.removeAttribute('aria-invalid'); });
    if (err) { err.textContent = text || ''; err.hidden = !text; }
  }
  function check(wrap) {
    if (!wrap) return '';
    const isPills = wrap.classList.contains('td-field--pills');
    if (isPills) {
      const required = wrap.hasAttribute('data-required');
      const any = wrap.querySelector('input:checked');
      return required && !any ? msg('choose') : '';
    }
    const el = wrap.querySelector('input:not([type=hidden]),select,textarea');
    if (!el) return '';
    if (el.type === 'checkbox') return el.required && !el.checked ? msg('consent') : '';
    const v = el.value.trim();
    if (!v) return el.required ? msg('required') : '';
    const max = Number(el.getAttribute('maxlength') || 0);
    if (max && v.length > max) return msg('max').replace('%d', String(max));
    if (el.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) return msg('email');
    if (el.dataset.type === 'url' && !/^(https?:\/\/)?[^\s/.]+(\.[^\s/.]+)+(\/\S*)?$/i.test(v)) return msg('url');
    return '';
  }
  function validate(scope) {
    let first = null;
    scope.querySelectorAll('[data-field]').forEach((wrap) => {
      const e = check(wrap);
      setError(wrap, e);
      if (e && !first) first = wrap.querySelector('input:not([type=hidden]),select,textarea');
    });
    return first;
  }
  form.addEventListener('focusout', (e) => {
    const wrap = fieldWrap(e.target);
    if (!wrap || wrap.contains(e.relatedTarget)) return;
    // Only show "required" on blur once the visitor has typed something or the field was flagged before.
    const el = e.target;
    if (wrap.classList.contains('is-invalid') || (el.value && el.type !== 'checkbox' && el.type !== 'radio')) setError(wrap, check(wrap));
  });
  form.addEventListener('change', (e) => {
    const wrap = fieldWrap(e.target);
    if (wrap && wrap.classList.contains('is-invalid')) setError(wrap, check(wrap));
  });

  // ---------- steps ----------
  const steps = [...form.querySelectorAll('.td-form__step')];
  let current = 0;
  let nav = null;
  let progress = null;
  if (steps.length > 1) {
    root.classList.add('is-stepped');
    progress = document.createElement('div');
    progress.className = 'td-form__progress';
    progress.innerHTML = '<div class="td-form__progress-text"><span data-p-step></span><span data-p-label></span></div><div class="td-form__progress-bar" aria-hidden="true"><span></span></div>';
    steps[0].before(progress);
    nav = document.createElement('div');
    nav.className = 'td-form__stepnav';
    nav.innerHTML = '<button type="button" class="td-btn td-btn--secondary td-btn--sm" data-back>' + msg('back') + '</button><span class="td-form__stepnav-hint" data-hint></span><button type="button" class="td-btn td-btn--primary td-btn--sm" data-next><span data-next-label>' + msg('next') + '</span><span class="td-btn__arrow">' + (submit.querySelector('.td-btn__arrow') || { innerHTML: '' }).innerHTML + '</span></button>';
    form.querySelector('.td-form__actions').before(nav);
    nav.querySelector('[data-back]').addEventListener('click', () => go(current - 1, true));
    nav.querySelector('[data-next]').addEventListener('click', () => {
      const bad = validate(steps[current]);
      if (bad) { bad.focus(); return; }
      go(current + 1, true);
    });
  }
  function summary() {
    const box = form.querySelector('[data-summary]');
    if (!box) return;
    const rows = steps.slice(0, -1).map((s) => {
      const picked = [...s.querySelectorAll('.td-pill__input:checked')].map((i) => i.closest('.td-pill').querySelector('.td-pill__label').textContent);
      return [s.dataset.stepLabel || '', picked.join(', ') || '—'];
    });
    box.innerHTML = '';
    const t = document.createElement('span');
    t.className = 'td-form__summary-title';
    t.textContent = msg('summary');
    box.append(t);
    rows.forEach(([label, value]) => {
      const row = document.createElement('div');
      row.className = 'td-form__summary-row';
      const a = document.createElement('span');
      a.textContent = label;
      const b = document.createElement('strong');
      b.textContent = value;
      row.append(a, b);
      box.append(row);
    });
    box.hidden = false;
  }
  function go(i, focus) {
    if (!steps.length || steps.length < 2) return;
    current = Math.max(0, Math.min(i, steps.length - 1));
    steps.forEach((s, n) => s.classList.toggle('is-current', n === current));
    const last = current === steps.length - 1;
    progress.querySelector('[data-p-step]').textContent = msg('step').replace('%1$d', current + 1).replace('%2$d', steps.length);
    progress.querySelector('[data-p-label]').textContent = steps[current].dataset.stepLabel || '';
    progress.querySelector('.td-form__progress-bar span').style.width = ((current + 1) / steps.length) * 100 + '%';
    nav.querySelector('[data-back]').hidden = current === 0;
    nav.querySelector('[data-hint]').hidden = current !== 0;
    nav.querySelector('[data-next]').hidden = last;
    submit.parentElement.querySelector('.td-form__submit').hidden = !last;
    if (last) summary();
    if (focus) { const h = steps[current].querySelector('.td-form__step-title'); if (h) h.focus(); }
  }
  if (steps.length > 1) {
    // Land on the first step with an error (no-JS error flash), else step 1.
    const errStep = steps.findIndex((s) => s.querySelector('.is-invalid'));
    go(errStep > -1 ? errStep : 0, false);
  }

  // ---------- submit ----------
  function data() {
    const out = {};
    new FormData(form).forEach((v, k) => {
      if (k.endsWith('[]')) { const n = k.slice(0, -2); (out[n] = out[n] || []).push(v); }
      else out[k] = v;
    });
    form.querySelectorAll('.td-field--pills').forEach((w) => {
      const first = w.querySelector('input[type=checkbox]');
      if (first && !(first.name.slice(0, -2) in out)) out[first.name.slice(0, -2)] = [];
    });
    return out;
  }
  function busy(on) {
    if (!submit) return;
    submit.setAttribute('aria-busy', on ? 'true' : 'false');
    submit.disabled = on;
    if (submitLabel) {
      if (on) { submitLabel.dataset.label = submitLabel.textContent; submitLabel.textContent = msg('sending'); }
      else if (submitLabel.dataset.label) submitLabel.textContent = submitLabel.dataset.label;
    }
  }
  function showFormError(text) {
    if (!alertBox) return;
    alertBox.textContent = text || '';
    alertBox.hidden = !text;
  }
  function showSuccess(res) {
    const dl = success.querySelector('[data-download]');
    if (dl && res.download && res.download.url) {
      const a = dl.querySelector('a');
      a.href = res.download.url;
      if (res.download.title) a.querySelector('[data-download-label]').textContent = res.download.title;
      dl.hidden = false;
    }
    body.hidden = true;
    success.hidden = false;
    success.focus();
    if (td.track) td.track('generate_lead', { form_key: key });
    else { window.dataLayer = window.dataLayer || []; window.dataLayer.push({ event: 'generate_lead', form_key: key }); }
    try { document.dispatchEvent(new CustomEvent('td:form-success', { detail: { key, root } })); } catch { /* old browsers */ }
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    showFormError('');
    const bad = validate(form);
    if (bad) {
      if (steps.length > 1) {
        const idx = steps.findIndex((s) => s.contains(bad));
        if (idx > -1 && idx !== current) go(idx, false);
      }
      bad.focus();
      return;
    }
    busy(true);
    if (!tokenPromise) firstTouch();
    await tokenPromise;
    const wait = MIN_MS - (Date.now() - tokenAt);
    if (wait > 0) await sleep(wait);
    if (turnstileId !== null && window.turnstile) {
      const field = form.querySelector('[name="cf-turnstile-response"]');
      for (let i = 0; i < 40 && field && !field.value; i++) await sleep(250);
    }
    let res;
    let status = 0;
    try {
      const r = await fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(data()),
      });
      status = r.status;
      res = await r.json().catch(() => ({}));
    } catch {
      busy(false);
      showFormError(msg('network'));
      return;
    }
    busy(false);
    if (res && res.ok) { showSuccess(res); return; }
    const errors = (res && res.errors) || {};
    let firstBad = null;
    Object.keys(errors).forEach((name) => {
      if (name === '_form') return;
      const wrap = form.querySelector(`[data-field="${CSS.escape(name)}"]`);
      if (wrap && wrap.querySelector('input:not([type=hidden]),select,textarea')) {
        setError(wrap, errors[name]);
        if (!firstBad) firstBad = wrap.querySelector('input:not([type=hidden]),select,textarea');
      } else if (!errors._form) errors._form = errors[name];
    });
    if (errors._form || !firstBad) showFormError(errors._form || msg('network'));
    if (status === 403 && res && (res.code === 'token' || res.code === 'too_fast')) fetchToken();
    if (window.turnstile && turnstileId !== null) window.turnstile.reset(turnstileId);
    if (firstBad) {
      if (steps.length > 1) { const idx = steps.findIndex((s) => s.contains(firstBad)); if (idx > -1) go(idx, false); }
      firstBad.focus();
    } else if (alertBox) {
      alertBox.setAttribute('tabindex', '-1');
      alertBox.focus();
    }
  });

  const again = success && success.querySelector('[data-again]');
  if (again) {
    again.addEventListener('click', () => {
      form.reset();
      if (page) page.value = cleanHref();
      success.hidden = true;
      body.hidden = false;
      fetchToken();
      if (steps.length > 1) go(0, false);
      const firstInput = form.querySelector('input:not([type=hidden]):not([tabindex="-1"]),select,textarea');
      if (firstInput) firstInput.focus();
    });
  }
}

(td.ready || ((fn) => (document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', fn) : fn())))(() => {
  document.querySelectorAll('.td-form[data-td-form]').forEach(initForm);
});
// Popups render forms later: they can call td.initForms(container).
td.initForms = (scope = document) => scope.querySelectorAll('.td-form[data-td-form]').forEach(initForm);
