// Ad slots (§12.2): load AdSense only after the CMP allows ads (window.tdConsent from the
// inline consent loader) AND a slot is within ~600px of the viewport. Manual units only.
const td = (window.td = window.td || {});

td.ready(() => {
  const units = [...document.querySelectorAll('[data-td-ad] ins.adsbygoogle')];
  if (!units.length || !window.tdConsent || !('IntersectionObserver' in window)) return;
  let loaded = false;
  const load = (client) => {
    if (loaded) return;
    loaded = true;
    const s = document.createElement('script');
    s.async = true;
    s.crossOrigin = 'anonymous';
    s.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + encodeURIComponent(client);
    document.head.appendChild(s);
  };
  const fill = (ins) => {
    // Hidden units (sidebar on small screens) are never requested.
    if (ins.dataset.tdFilled || !ins.offsetParent) return;
    ins.dataset.tdFilled = '1';
    load(ins.dataset.adClient);
    try { (window.adsbygoogle = window.adsbygoogle || []).push({}); } catch { /* AdSense not ready */ }
  };
  window.tdConsent.ads(() => {
    const io = new IntersectionObserver((entries) => entries.forEach((e) => {
      if (!e.isIntersecting) return;
      io.unobserve(e.target);
      fill(e.target);
    }), { rootMargin: '600px 0px' });
    units.forEach((u) => io.observe(u));
  });
});
