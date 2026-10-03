'use strict';
(() => {
  const root = document.documentElement;
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  const narrow = matchMedia('(max-width: 700px)');
  const records = [...document.querySelectorAll('[data-parallax]')].map(element => ({
    element,
    frame: element.closest('.juris-hero, .juris-about-visual, .editorial-visual'),
    kind: element.dataset.parallax,
    visible: false
  })).filter(record => record.frame);
  if (!records.length) return;
  let frameId = 0;
  let active = false;
  const render = () => {
    frameId = 0;
    if (!active || document.hidden) return;
    const compact = narrow.matches;
    for (const record of records) {
      if (!record.visible) continue;
      const box = record.frame.getBoundingClientRect();
      let offset;
      if (record.kind === 'background' || record.kind === 'mark') {
        const travel = Math.max(0, -box.top);
        offset = record.kind === 'background' ? travel * (compact ? .07 : .15) : -travel * (compact ? .015 : .035);
      } else {
        offset = (innerHeight / 2 - (box.top + box.height / 2)) * (compact ? .035 : .075);
      }
      const limit = record.kind === 'mark' ? (compact ? 5 : 10) : Math.min(compact ? 14 : 34, box.height * .06);
      record.element.style.setProperty('--parallax-y', Math.max(-limit, Math.min(limit, offset)).toFixed(2) + 'px');
    }
  };
  const queue = () => { if (active && !document.hidden && !frameId) frameId = requestAnimationFrame(render); };
  let observer;
  const updatePreference = () => {
    active = root.dataset.parallaxEffect === 'gentle' && !reduced.matches && !navigator.connection?.saveData;
    root.classList.toggle('parallax-active', active);
    if (!active) {
      cancelAnimationFrame(frameId);frameId = 0;
      records.forEach(record => record.element.style.removeProperty('--parallax-y'));
    } else queue();
  };
  if ('IntersectionObserver' in window) {
    observer = new IntersectionObserver(entries => {
      for (const entry of entries) for (const record of records) if (record.frame === entry.target) { record.visible = entry.isIntersecting;record.element.classList.toggle('is-parallax-visible', record.visible); }
      queue();
    }, { rootMargin: '100px 0px' });
    new Set(records.map(record => record.frame)).forEach(element => observer.observe(element));
  } else records.forEach(record => { record.visible = true; });
  window.addEventListener('scroll', queue, { passive: true });
  window.addEventListener('resize', queue, { passive: true });
  window.addEventListener('pageshow', queue);
  document.addEventListener('visibilitychange', () => { if (document.hidden) { cancelAnimationFrame(frameId);frameId = 0; } else queue(); });
  reduced.addEventListener('change', updatePreference);
  narrow.addEventListener('change', queue);
  navigator.connection?.addEventListener?.('change', updatePreference);
  updatePreference();
})();
