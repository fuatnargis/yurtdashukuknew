'use strict';
// Runs in the head so the first paint can include the opening identity.
(() => {
  const root = document.documentElement;
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  const navigation = performance.getEntriesByType('navigation')[0];
  let internal = false;
  try { internal = Boolean(document.referrer) && new URL(document.referrer).origin === location.origin && navigation?.type !== 'reload'; } catch { /* An absent referrer is an ordinary direct visit. */ }
  try {
    const pending = JSON.parse(sessionStorage.getItem('hukuk-page-navigation') || 'null');
    internal ||= pending?.url === location.href && Date.now() - pending.time < 10000;
  } catch { /* Existing navigation storage is optional. */ }
  if (root.dataset.loadingAnimation !== 'signature' || reduced.matches || navigator.connection?.saveData || internal || location.hash || navigation?.type === 'back_forward') return;
  const started = performance.now();
  let finished = false;
  let readyTimer;
  root.classList.add('is-site-loading');
  const finish = () => {
    if (finished) return;
    finished = true;
    clearTimeout(readyTimer);
    clearTimeout(deadline);
    root.classList.remove('is-site-loading');
    window.removeEventListener('keydown', finish, true);
    window.removeEventListener('pointerdown', finish, true);
    reduced.removeEventListener('change', finish);
  };
  // A slow or failed resource must never keep the visitor behind an overlay.
  const deadline = setTimeout(finish, 1380);
  const ready = () => { if (finished) return; readyTimer = setTimeout(finish, Math.max(0, 350 - (performance.now() - started))); };
  if (document.readyState === 'complete') ready();
  else window.addEventListener('load', ready, { once: true });
  window.addEventListener('keydown', finish, { capture: true, once: true });
  window.addEventListener('pointerdown', finish, { capture: true, once: true });
  window.addEventListener('pageshow', event => { if (event.persisted) finish(); });
  reduced.addEventListener('change', finish);
})();
