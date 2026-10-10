// Tabs (§2.3): progressive enhancement of a stacked panel list into ARIA tabs.
// Roving tabindex; Left/Right/Home/End move and activate (automatic activation).
const td = window.td || {};
const ready = td.ready || ((fn) => document.addEventListener('DOMContentLoaded', fn));

ready(() => {
  document.querySelectorAll('[data-td-tabs]').forEach((root) => {
    const list = root.querySelector('[role="tablist"]');
    if (!list) return;
    const tabs = [...list.querySelectorAll('[role="tab"]')];
    const panels = tabs.map((t) => document.getElementById(t.getAttribute('aria-controls')));
    if (!tabs.length || panels.some((p) => !p)) return;

    const select = (index, focus) => {
      tabs.forEach((tab, i) => {
        const on = i === index;
        tab.setAttribute('aria-selected', String(on));
        tab.tabIndex = on ? 0 : -1;
        panels[i].hidden = !on;
      });
      if (focus) tabs[index].focus();
    };

    panels.forEach((p, i) => {
      p.setAttribute('role', 'tabpanel');
      p.setAttribute('aria-labelledby', tabs[i].id);
      p.tabIndex = 0;
    });
    list.hidden = false;
    root.classList.add('td-tabs--enhanced');
    select(Math.max(0, tabs.findIndex((t) => t.getAttribute('aria-selected') === 'true')), false);

    list.addEventListener('click', (e) => {
      const i = tabs.indexOf(e.target.closest('[role="tab"]'));
      if (i > -1) select(i, true);
    });
    list.addEventListener('keydown', (e) => {
      const cur = tabs.indexOf(document.activeElement);
      if (cur < 0) return;
      const last = tabs.length - 1;
      const next = { ArrowRight: cur === last ? 0 : cur + 1, ArrowLeft: cur === 0 ? last : cur - 1, Home: 0, End: last }[e.key];
      if (next === undefined) return;
      e.preventDefault();
      select(next, true);
    });
  });
});
