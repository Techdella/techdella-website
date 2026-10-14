// Popup engine behaviour (§13.1). Markup + config come from techdella-core/popups (wp_footer).
// Rules: never in the first 10 s; exit-intent modals desktop only; on phones only the
// slide-in / top bar, after 50% scroll or 30 s; tool pages wait for `td:tool-used`;
// once per N days per visitor, never after a conversion; Esc closes; the modal traps
// focus and returns it on close. GA4: popup_impression / popup_conversion.
const td = (window.td = window.td || {});

const MIN_DELAY = 10000;
const SEARCH = /(^|\.)(google|bing|duckduckgo|yahoo|baidu|yandex|ecosia|brave|startpage|naver|seznam|qwant)\.[a-z.]+$/i;

const read = (id) => {
  try { return JSON.parse(td.store.get('td_popup_' + id) || '{}') || {}; } catch { return {}; }
};
const write = (id, state) => td.store.set('td_popup_' + id, JSON.stringify(state), 365);

const scrolled = () => {
  const max = document.documentElement.scrollHeight - window.innerHeight;
  return max <= 0 ? 100 : (window.scrollY / max) * 100;
};

function referrerIsSearch() {
  try { return !!document.referrer && SEARCH.test(new URL(document.referrer).hostname); } catch { return false; }
}

function popupOf(el) {
  return el && el.closest ? el.closest('[data-td-popup]') : null;
}

function markConverted(root, key) {
  const el = popupOf(root);
  const id = el ? el.dataset.tdPopup : null;
  document.querySelectorAll('[data-td-popup]').forEach((p) => {
    if (p.dataset.tdPopup !== id && !(key && p.dataset.formKey === key && p.dataset.type !== 'inline')) return;
    const state = read(p.dataset.tdPopup);
    if (state.c) return;
    state.c = 1;
    write(p.dataset.tdPopup, state);
    if (p.dataset.tdPopup === id) td.track('popup_conversion', { popup_id: id, popup_variant: p.dataset.variantShown || 'a', form_key: key || p.dataset.formKey });
  });
}

// Plain fallback form (when the forms module is absent): submit with fetch.
function initFallbackForms(scope) {
  scope.querySelectorAll('form[data-td-popup-form]').forEach((form) => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error(String(res.status));
        const done = form.querySelector('.td-popup__done');
        if (done) done.hidden = false;
        form.dispatchEvent(new CustomEvent('td:form-success', { bubbles: true, detail: { key: form.dataset.tdPopupForm, root: form } }));
      } catch { form.submit(); }
    });
  });
}

function chooseVariant(el, state) {
  const count = Number(el.dataset.variants || 1);
  const v = state.v && (state.v === 'a' || count > 1) ? state.v : count > 1 && Math.random() < 0.5 ? 'b' : 'a';
  el.dataset.variantShown = v;
  el.querySelectorAll('.td-popup__copy').forEach((c) => { c.hidden = c.dataset.variant !== v; });
  el.setAttribute('aria-labelledby', `${el.id}-title-${v}`);
  const label = el.dataset['button' + v.toUpperCase()];
  if (label) el.querySelectorAll('.td-form__submit-label').forEach((s) => { s.textContent = label; });
  return v;
}

td.ready(() => {
  // Conversions from any form (forms module dispatches td:form-success on document).
  document.addEventListener('td:form-success', (e) => markConverted(e.detail && e.detail.root ? e.detail.root : e.target, e.detail && e.detail.key), true);
  initFallbackForms(document);

  // Inline cards: impression when seen.
  const inline = [...document.querySelectorAll('.td-popup--inline[data-td-popup]')];
  if (inline.length && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => entries.forEach((en) => {
      if (!en.isIntersecting) return;
      io.unobserve(en.target);
      td.track('popup_impression', { popup_id: en.target.dataset.tdPopup, popup_variant: 'a', popup_type: 'inline' });
    }), { threshold: 0.5 });
    inline.forEach((el) => io.observe(el));
  }

  const cfgEl = document.getElementById('td-popups-config');
  if (!cfgEl) return;
  let cfg;
  try { cfg = JSON.parse(cfgEl.textContent); } catch { return; }

  const started = Date.now();
  const mobile = window.matchMedia('(max-width: 767px), (pointer: coarse)').matches;
  const fromSearch = referrerIsSearch();
  let toolUsed = !cfg.isTool;
  let open = null;
  let release = null;
  let returnTo = null;

  const pv = Number(td.store.get('td_pv') || 0) + 1;
  td.store.set('td_pv', String(pv), 30);

  function close(el, reason) {
    if (!el || el.hidden) return;
    el.hidden = true;
    el.classList.remove('is-open');
    if (release) { release(); release = null; }
    if (open === el) open = null;
    if (returnTo && document.contains(returnTo) && el.contains(document.activeElement)) returnTo.focus();
    else if (returnTo && el.dataset.type === 'exit' && document.contains(returnTo)) returnTo.focus();
    returnTo = null;
    td.track('popup_close', { popup_id: el.dataset.tdPopup, reason });
  }

  function show(p, el) {
    if (open || !el.hidden) return false;
    const state = read(p.id);
    if (state.c || (state.s && Date.now() - state.s < p.days * 86400000)) return false;
    state.v = chooseVariant(el, state);
    state.s = Date.now();
    write(p.id, state);
    returnTo = document.activeElement;
    el.hidden = false;
    el.classList.add('is-open');
    open = el;
    if (p.type === 'exit') {
      release = td.trapFocus(el);
      const first = el.querySelector('input:not([type=hidden]):not([tabindex="-1"]), .td-popup__close');
      (first || el.querySelector('.td-popup__panel')).focus();
    }
    td.track('popup_impression', { popup_id: p.id, popup_variant: state.v, popup_type: p.type });
    return true;
  }

  cfg.popups.forEach((p) => {
    const el = document.querySelector(`[data-td-popup="${CSS.escape(p.id)}"]`);
    if (!el) return;
    const state = read(p.id);
    if (state.c || (state.s && Date.now() - state.s < p.days * 86400000)) return;
    // Phones: never a modal; only the slide-in / top bar (§13.1, intrusive interstitials).
    if (mobile && !['slidein', 'topbar'].includes(p.type)) return;

    el.querySelectorAll('[data-td-popup-close]').forEach((b) => b.addEventListener('click', () => close(el, 'button')));
    el.addEventListener('click', (e) => { if (e.target === el && p.type === 'exit') close(el, 'backdrop'); });
    el.querySelectorAll('[data-td-popup-cta]').forEach((a) => a.addEventListener('click', () => td.track('popup_click', { popup_id: p.id, popup_variant: el.dataset.variantShown || 'a' })));

    const ready = () => toolUsed && Date.now() - started >= MIN_DELAY;
    // Mobile (and any visitor from search on a phone): only after 50% scroll or 30 s.
    const mobileGate = () => !mobile || scrolled() >= 50 || Date.now() - started >= 30000;
    const due = () => {
      if (!ready() || !mobileGate()) return false;
      if (p.trigger === 'scroll') return scrolled() >= p.value;
      if (p.trigger === 'time') return Date.now() - started >= p.value * 1000;
      if (p.trigger === 'pages') return pv >= p.value;
      return false;
    };

    if (p.trigger === 'exit') {
      if (mobile) return;
      document.addEventListener('mouseout', (e) => {
        if (e.relatedTarget || e.clientY > 0 || !ready()) return;
        show(p, el);
      });
      return;
    }
    let done = false;
    const check = () => {
      if (done || !due()) return;
      done = show(p, el);
    };
    window.addEventListener('scroll', check, { passive: true });
    const timer = window.setInterval(() => { check(); if (done) window.clearInterval(timer); }, 1000);
    window.addEventListener('td:tool-used', () => { toolUsed = true; check(); });
  });

  window.addEventListener('td:tool-used', () => { toolUsed = true; });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && open) close(open, 'escape'); });
  // Note for QA: fromSearch is exposed so tests can assert the mobile/search rule.
  document.documentElement.dataset.tdFromSearch = fromSearch ? '1' : '0';
});
