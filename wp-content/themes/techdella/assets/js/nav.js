// Site navigation (§2.3): blur-on-scroll, mega-menus (Services, Industries, Resources), mobile sheet.
// Progressive enhancement only: without JS, Services is a plain link to
// /services/ and the mobile menu is a <details> element.
const td = (window.td = window.td || {});

function initScroll(nav) {
  let ticking = false;
  const update = () => {
    nav.classList.toggle('is-scrolled', window.scrollY > 8);
    ticking = false;
  };
  window.addEventListener('scroll', () => {
    if (!ticking) { ticking = true; window.requestAnimationFrame(update); }
  }, { passive: true });
  update();
}

function initMega(nav) {
  const items = [...nav.querySelectorAll('[data-td-mega]')];
  const closers = [];
  items.forEach((item) => {
    const toggle = item.querySelector('[data-td-mega-toggle]');
    const panel = item.querySelector('[data-td-mega-panel]');
    if (!toggle || !panel) return;
    toggle.hidden = false;
    let closeTimer;

    const isOpen = () => toggle.getAttribute('aria-expanded') === 'true';
    const close = (returnFocus = false) => {
      clearTimeout(closeTimer);
      if (!isOpen()) return;
      toggle.setAttribute('aria-expanded', 'false');
      panel.hidden = true;
      if (returnFocus) toggle.focus();
    };
    const open = () => {
      clearTimeout(closeTimer);
      closers.forEach((c) => { if (c !== close) c(); }); // One menu open at a time.
      toggle.setAttribute('aria-expanded', 'true');
      panel.hidden = false;
    };
    closers.push(close);

    toggle.addEventListener('click', () => (isOpen() ? close() : open()));
    // Hover opens on pointer devices; a short delay avoids flicker.
    const hover = window.matchMedia('(hover: hover) and (pointer: fine)');
    item.addEventListener('mouseenter', () => { if (hover.matches) open(); });
    item.addEventListener('mouseleave', () => {
      if (hover.matches) closeTimer = setTimeout(() => close(), 180);
    });
    item.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && isOpen()) { e.preventDefault(); close(true); }
    });
    item.addEventListener('focusout', (e) => {
      if (!item.contains(e.relatedTarget)) close();
    });
    document.addEventListener('click', (e) => { if (!item.contains(e.target)) close(); });
  });
}

function initSheet(nav) {
  const details = nav.querySelector('[data-td-nav-details]');
  const button = nav.querySelector('[data-td-sheet-open]');
  const sheet = details && details.querySelector('[data-td-sheet]');
  if (!details || !button || !sheet) return;

  // Lift the sheet out of the no-JS <details> and drive it from a real button.
  details.after(sheet);
  details.remove();
  sheet.hidden = true;
  sheet.setAttribute('role', 'dialog');
  sheet.setAttribute('aria-modal', 'true');
  sheet.setAttribute('aria-label', button.textContent.trim() || 'Menu');
  button.hidden = false;
  nav.classList.add('td-nav--js');
  const closeBtn = sheet.querySelector('[data-td-sheet-close]');
  if (closeBtn) closeBtn.hidden = false;

  let release = null;
  const close = () => {
    if (sheet.hidden) return;
    sheet.hidden = true;
    button.setAttribute('aria-expanded', 'false');
    document.documentElement.classList.remove('td-sheet-open');
    if (release) { release(); release = null; }
    button.focus();
  };
  const open = () => {
    sheet.hidden = false;
    button.setAttribute('aria-expanded', 'true');
    document.documentElement.classList.add('td-sheet-open');
    release = td.trapFocus ? td.trapFocus(sheet) : null;
    (closeBtn || sheet.querySelector('a,button,input')).focus();
  };

  button.addEventListener('click', () => (sheet.hidden ? open() : close()));
  if (closeBtn) closeBtn.addEventListener('click', close);
  sheet.addEventListener('keydown', (e) => { if (e.key === 'Escape') { e.preventDefault(); close(); } });
  // Same-page anchors (e.g. /#how) close the sheet.
  sheet.addEventListener('click', (e) => { if (e.target.closest('a')) close(); });
  // Leaving the mobile breakpoint closes it too.
  const desktop = window.matchMedia('(min-width: 961px)');
  const onChange = () => { if (desktop.matches) close(); };
  if (desktop.addEventListener) desktop.addEventListener('change', onChange);
}

const ready = td.ready || ((fn) => (document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', fn, { once: true }) : fn()));
ready(() => {
  document.querySelectorAll('[data-td-nav]').forEach((nav) => {
    initScroll(nav);
    initMega(nav);
    initSheet(nav);
  });
});
