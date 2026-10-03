'use strict';
(() => {
  const root = document.documentElement;
  const mode = document.body.dataset.pageTransition;
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  const key = 'hukuk-page-navigation';
  let cleanup;
  const clear = () => {
    clearTimeout(cleanup);
    root.classList.remove('page-entering', 'page-entering-signature');
  };
  const enter = () => {
    clear();
    let pending;
    try {
      pending = JSON.parse(sessionStorage.getItem(key) || 'null');
      sessionStorage.removeItem(key);
    } catch { return; }
    if (mode === 'off' || reduced.matches || !pending || pending.url !== location.href || Date.now() - pending.time > 10000) return;
    root.classList.add('page-entering');
    if (mode === 'signature') root.classList.add('page-entering-signature');
    cleanup = setTimeout(clear, 450);
  };
  enter();
  // Normal browser navigation remains responsible for history, forms and new tabs.
  document.addEventListener('click', event => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || mode === 'off' || reduced.matches) return;
    const anchor = event.target.closest('a[href]');
    if (!anchor || anchor.hasAttribute('download') || (anchor.target && anchor.target !== '_self')) return;
    const url = new URL(anchor.href, location.href);
    if (url.origin !== location.origin || url.pathname.startsWith('/admin') || (url.pathname === location.pathname && url.search === location.search)) return;
    try {
      const pending = JSON.stringify({ url: url.href, time: Date.now() });
      sessionStorage.setItem(key, pending);
      setTimeout(() => {
        try { if (sessionStorage.getItem(key) === pending) sessionStorage.removeItem(key); } catch { /* Storage can be disabled after navigation starts. */ }
      }, 10000);
    } catch { /* Navigation works when browser storage is disabled. */ }
  });
  window.addEventListener('pageshow', event => { if (event.persisted) clear(); });
  reduced.addEventListener('change', clear);
})();
