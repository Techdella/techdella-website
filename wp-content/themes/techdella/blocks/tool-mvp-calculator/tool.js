// MVP cost calculator (Studios): a live planning range from the published Studios prices and the
// rules in td_mvp_calc_rules(), then the inputs and range go into the mvp_calc form (MVP_CALC).
const td = (window.td = window.td || {});
const ready = td.ready || ((fn) => (document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', fn) : fn()));

ready(() => {
  const root = document.querySelector('[data-td-mvp]');
  if (!root) return;
  let rules;
  try { rules = JSON.parse(root.dataset.tdMvp); } catch { return; }
  const Q = rules.questions;
  const base = rules.base;
  const low = root.querySelector('[data-td-mvp-low]');
  const high = root.querySelector('[data-td-mvp-high]');
  const weeksEl = root.querySelector('[data-td-mvp-weeks]');
  const lines = root.querySelector('[data-td-mvp-lines]');
  const form = document.querySelector('form[action*="/submit/mvp_calc"]');

  const picked = (key) => [...root.querySelectorAll(`[data-q="${key}"] input:checked`)].map((i) => i.value);
  const round = (n, cur) => { const step = cur === 'ngn' ? 50000 : 500; return Math.round(n / step) * step; };
  const fmt = (n, cur) => (cur === 'ngn' ? '₦' : '$') + Math.round(n).toLocaleString('en-US');
  const label = (key, v) => (Q[key].options[v] || [v])[0];

  const compute = () => {
    const stage = picked('stage')[0] || Q.stage.default;
    const platform = picked('platform')[0] || 'web';
    const features = picked('features');
    const design = picked('design')[0] || 'full';
    const timeline = picked('timeline')[0] || 'standard';
    const proto = stage === 'prototype';

    // Build part: base × platform × design × timeline, plus a share per feature.
    let mult = 1;
    let addShare = 0;
    let wk = 0;
    [['platform', platform], ['design', design], ['timeline', timeline]].forEach(([k, v]) => {
      const o = Q[k].options[v] || [];
      mult *= Number(o[2] ?? 1);
      wk += Number(o[3] ?? 0);
    });
    features.forEach((f) => { const o = Q.features.options[f] || []; addShare += Number(o[2] ?? 0); wk += Number(o[3] ?? 0); });

    const total = {};
    ['usd', 'ngn'].forEach((cur) => {
      const disc = base.discovery[cur] * (proto ? Math.max(1, Math.sqrt(mult)) : 1);
      const build = proto ? 0 : base.build[cur] * mult * (1 + addShare);
      total[cur] = { lo: round(disc + build, cur), hi: round((disc + build) * base.spread, cur) };
    });
    const w = base.weeks;
    const wLo = proto ? w.discovery[0] : w.discovery[0] + Math.max(4, w.build[0] + Math.floor(wk / 2));
    const wHi = proto ? w.discovery[1] + (features.length > 3 ? 1 : 0) : w.discovery[1] + Math.max(6, w.build[1] + Math.ceil(wk));

    const cur = document.documentElement.dataset.currency === 'ngn' ? 'ngn' : 'usd';
    [[low, 'lo'], [high, 'hi']].forEach(([el, k]) => {
      el.dataset.usd = fmt(total.usd[k], 'usd');
      el.dataset.ngn = fmt(total.ngn[k], 'ngn');
      el.textContent = cur === 'ngn' ? el.dataset.ngn : el.dataset.usd;
    });
    weeksEl.textContent = `${wLo}–${wHi}`;

    const summary = [
      label('stage', stage),
      label('platform', platform),
      features.length ? features.map((f) => label('features', f)).join(', ') : 'Accounts only',
      label('design', design),
      label('timeline', timeline),
    ];
    lines.replaceChildren(...summary.map((t) => { const li = document.createElement('li'); li.textContent = t; return li; }));

    if (form) {
      const set = (name, v) => { const i = form.querySelector(`input[type="hidden"][name="${name}"]`); if (i) i.value = v; };
      set('inputs', JSON.stringify({ stage, platform, features, design, timeline, currency: cur }));
      set('estimate', `${fmt(total[cur].lo, cur)}–${fmt(total[cur].hi, cur)}, ${wLo}–${wHi} weeks`);
    }
  };

  root.addEventListener('change', compute);
  document.querySelectorAll('[data-td-currency]').forEach((s) => s.addEventListener('click', () => setTimeout(compute, 0)));
  compute();
});
