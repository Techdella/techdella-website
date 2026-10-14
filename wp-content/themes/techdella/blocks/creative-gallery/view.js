// Creative gallery: optional platform filter chips (progressive enhancement;
// without JS every creative shows and the chips stay hidden).
const td = window.td || {};
const ready = td.ready || ((fn) => document.addEventListener('DOMContentLoaded', fn));

ready(() => {
  document.querySelectorAll('.td-creatives__filters').forEach((bar) => {
    const items = bar.parentElement.querySelectorAll('.td-creatives__item');
    const buttons = bar.querySelectorAll('.td-creatives__filter');
    bar.hidden = false;
    bar.addEventListener('click', (e) => {
      const btn = e.target.closest('.td-creatives__filter');
      if (!btn) return;
      const f = btn.dataset.filter;
      buttons.forEach((b) => b.setAttribute('aria-pressed', String(b === btn)));
      items.forEach((it) => {
        // Link cards (no platform) always stay.
        it.hidden = Boolean(f) && Boolean(it.dataset.platform) && it.dataset.platform !== f;
      });
      bar.parentElement.querySelector('.td-creatives__grid').classList.toggle('td-creatives__grid--filtered', Boolean(f));
    });
  });
});
