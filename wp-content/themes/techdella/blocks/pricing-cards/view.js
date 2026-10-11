// Currency (§ pricing): prices render in USD; visitors whose device time zone is Africa/Lagos see
// naira, and the switch on pricing cards lets anyone change it. No IP lookup, no third party.
const td = (window.td = window.td || {});

td.ready(() => {
  const prices = [...document.querySelectorAll('.td-price[data-ngn]')];
  if (!prices.length) return;
  const KEY = 'td-currency';
  let saved = null;
  try { saved = window.localStorage.getItem(KEY); } catch (e) { saved = null; }
  let zone = '';
  try { zone = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) { zone = ''; }
  const switches = [...document.querySelectorAll('[data-td-currency]')];
  const apply = (cur) => {
    prices.forEach((p) => { p.textContent = cur === 'ngn' ? p.dataset.ngn : p.dataset.usd; });
    switches.forEach((s) => s.querySelectorAll('[data-currency]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.currency === cur))));
    document.documentElement.dataset.currency = cur;
  };
  switches.forEach((s) => {
    s.hidden = false;
    s.addEventListener('click', (e) => {
      const b = e.target.closest('[data-currency]');
      if (!b) return;
      apply(b.dataset.currency);
      try { window.localStorage.setItem(KEY, b.dataset.currency); } catch (err) { /* private mode */ }
    });
  });
  apply(saved === 'ngn' || saved === 'usd' ? saved : (zone === 'Africa/Lagos' ? 'ngn' : 'usd'));
});
