// Currency (§ pricing): prices render in USD; visitors whose device time zone is Africa/Lagos see
// naira. No switch, no IP lookup, no third party.
const td = (window.td = window.td || {});

td.ready(() => {
  const prices = [...document.querySelectorAll('.td-price[data-ngn]')];
  if (!prices.length) return;
  // Owner, 12 Oct 2026: no currency switch. The device time zone picks the price list: naira for
  // Africa/Lagos, US dollars everywhere else. Exact prices come with a PDF quote.
  let zone = '';
  try { zone = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch { zone = ''; }
  const cur = zone === 'Africa/Lagos' ? 'ngn' : 'usd';
  prices.forEach((p) => { p.textContent = cur === 'ngn' ? p.dataset.ngn : p.dataset.usd; });
  document.documentElement.dataset.currency = cur;
});
