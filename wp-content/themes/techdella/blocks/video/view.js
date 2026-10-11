// Video (§6.3): loop videos play muted only while near the viewport, never for visitors who
// prefer reduced motion (they keep the poster), and have a pause/play button (WCAG 2.2.2).
const td = (window.td = window.td || {});

td.ready(() => {
  const loops = [...document.querySelectorAll('video[data-td-video="loop"]')];
  if (!loops.length) return;
  const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');
  if ((reduce && reduce.matches) || !('IntersectionObserver' in window)) return;

  loops.forEach((video) => {
    const btn = video.parentElement.querySelector('[data-td-video-toggle]');
    let paused = false;
    const label = btn && btn.querySelector('.td-sr');
    const sync = () => {
      if (!btn) return;
      btn.setAttribute('aria-pressed', paused ? 'true' : 'false');
      if (label) label.textContent = paused ? label.dataset.labelPlay : label.dataset.labelPause;
    };
    const play = () => {
      if (paused) return;
      video.muted = true; // never with sound
      const p = video.play();
      if (p && p.catch) p.catch(() => {});
    };
    if (btn) {
      btn.hidden = false;
      btn.addEventListener('click', () => {
        paused = !paused;
        if (paused) video.pause(); else play();
        sync();
      });
    }
    new IntersectionObserver((entries) => entries.forEach((e) => {
      if (e.isIntersecting) {
        if (video.preload === 'none') video.preload = 'auto';
        play();
      } else {
        video.pause();
      }
    }), { rootMargin: '200px 0px' }).observe(video);
  });
});
