// TechDella front-end core. Block behaviour lives in blocks/<name>/view.js and
// is bundled into the same file (scripts/build.mjs). No jQuery, no framework.
//
// Convention: each module exports nothing and self-initialises on
// DOMContentLoaded via td.ready(). Shared helpers hang off window.td.

const td = (window.td = window.td || {});

td.ready = (fn) => {
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn, { once: true });
  else fn();
};

td.reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Safe storage (§13.1: localStorage inside try/catch, cookie fallback).
td.store = {
  get(key) {
    try {
      const v = window.localStorage.getItem(key);
      if (v !== null) return v;
    } catch { /* blocked storage */ }
    const m = document.cookie.match(new RegExp('(?:^|; )' + key.replace(/[.$?*|{}()[\]\\/+^]/g, '\\$&') + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : null;
  },
  set(key, value, days = 365) {
    try {
      window.localStorage.setItem(key, value);
      return;
    } catch { /* fall through to cookie */ }
    document.cookie = `${key}=${encodeURIComponent(value)}; max-age=${days * 86400}; path=/; SameSite=Lax`;
  },
};

// Analytics events: pushed to dataLayer; GA4 (loaded only after consent, §12.3) picks them up.
td.track = (event, params = {}) => {
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push({ event, ...params });
};

// Focus trap for dialogs/sheets (§13.1 popups, nav mobile sheet).
td.trapFocus = (container) => {
  const selector = 'a[href],button:not([disabled]),input:not([disabled]):not([type=hidden]),select,textarea,[tabindex]:not([tabindex="-1"])';
  const onKey = (e) => {
    if (e.key !== 'Tab') return;
    const items = [...container.querySelectorAll(selector)].filter((el) => el.offsetParent !== null);
    if (!items.length) return;
    const first = items[0];
    const last = items[items.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  };
  container.addEventListener('keydown', onKey);
  return () => container.removeEventListener('keydown', onKey);
};

td.cookie = (key, value, days = 90) => {
  document.cookie = `${key}=${encodeURIComponent(value)}; max-age=${days * 86400}; path=/; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
};

// UTM capture: first + last touch as cookies, which the form route reads
// server-side when it enriches a submission (§11.2 step 3).
td.ready(() => {
  const params = new URLSearchParams(window.location.search);
  const keys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
  const touch = {};
  keys.forEach((k) => { if (params.get(k)) touch[k.replace('utm_', '')] = params.get(k).slice(0, 120); });
  if (!Object.keys(touch).length) return;
  const value = JSON.stringify(touch);
  if (!/(?:^|; )td_utm_first=/.test(document.cookie)) td.cookie('td_utm_first', value);
  td.cookie('td_utm_last', value);
});
import './nav.js';

// Tidy the address bar: Google's cross-domain linker adds ?_gl=… to links between our sites.
// Google Analytics reads it as the page loads; a few seconds later it's dropped from the visible URL
// (history.replaceState: no reload, no new history entry). Canonicals already ignore query strings.
window.addEventListener(
  'load',
  () => {
    setTimeout(() => {
      try {
        const url = new URL(window.location.href);
        if (!url.searchParams.has('_gl')) return;
        url.searchParams.delete('_gl');
        window.history.replaceState(window.history.state, '', url.pathname + url.search + url.hash);
      } catch { /* old browser: leave the URL as it is */ }
    }, 4000);
  },
  { once: true }
);
