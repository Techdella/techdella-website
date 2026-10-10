// techdella/toc: collapse on phones, keep open on desktop, and mark the
// section in view (aria-current) — progressive enhancement only.
const td = window.td || {};

const initToc = (nav) => {
  const details = nav.querySelector('details');
  const links = [...nav.querySelectorAll('a.td-toc__link')];
  const mq = window.matchMedia('(min-width: 1024px)');
  const sync = () => { if (details) details.open = mq.matches; };
  sync();
  mq.addEventListener?.('change', sync);
  // Close the sheet after a jump on phones.
  links.forEach((a) => a.addEventListener('click', () => { if (!mq.matches && details) details.open = false; }));

  if (!('IntersectionObserver' in window)) return;
  const targets = links
    .map((a) => document.getElementById(decodeURIComponent(a.hash.slice(1))))
    .filter(Boolean);
  if (!targets.length) return;
  // The current section is the last heading that has passed the top band.
  const mark = () => {
    let current = null;
    targets.forEach((t) => { if (t.getBoundingClientRect().top <= 140) current = t; });
    links.forEach((a) => {
      if (current && a.hash === `#${current.id}`) a.setAttribute('aria-current', 'true');
      else a.removeAttribute('aria-current');
    });
  };
  const io = new IntersectionObserver(mark, { rootMargin: '-120px 0px -50% 0px' });
  // Jumps (anchor links, keyboard) can skip the observer band: re-check on scroll too.
  let frame = 0;
  window.addEventListener('scroll', () => {
    if (!frame) frame = window.requestAnimationFrame(() => { frame = 0; mark(); });
  }, { passive: true });
  mark();
  targets.forEach((t) => io.observe(t));
};

(td.ready || ((fn) => fn()))(() => document.querySelectorAll('[data-td-toc]').forEach(initToc));
