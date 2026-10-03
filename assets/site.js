'use strict';
const menu = document.querySelector('.menu-toggle');
const nav = document.querySelector('.main-nav');
document.querySelectorAll('[data-load-map]').forEach(button => button.addEventListener('click', () => {
  const container = button.closest('[data-external-map]');
  const source = new URL(container.dataset.mapUrl, location.href);
  if (source.origin !== 'https://maps.google.com') return;
  const frame = document.createElement('iframe');
  frame.title = container.dataset.mapTitle || 'Büro konumu';
  frame.referrerPolicy = 'no-referrer';
  frame.allowFullscreen = true;
  frame.src = source.href;
  container.replaceChildren(frame);
  frame.focus();
}));
const setMenuOpen = open => {
  if (!menu || !nav) return;
  menu.setAttribute('aria-expanded', String(open));
  menu.setAttribute('aria-label', open ? 'Menüyü kapat' : 'Menüyü aç');
  nav.classList.toggle('is-open', open);
  document.body.classList.toggle('menu-open', open);
  if (!open) closeSubmenus();
};
menu?.addEventListener('click', () => setMenuOpen(menu.getAttribute('aria-expanded') !== 'true'));
nav?.addEventListener('click', e => { if (e.target.closest('a')) setMenuOpen(false); });
matchMedia('(max-width: 1200px)').addEventListener('change', e => { if (!e.matches) setMenuOpen(false); });
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && menu?.getAttribute('aria-expanded') === 'true') { menu.click(); menu.focus(); }
});
document.addEventListener('click', e => {
  if (nav?.classList.contains('is-open') && !e.target.closest('.site-header')) menu.click();
});
const dialog = document.querySelector('#search-dialog');
document.querySelectorAll('.search-toggle,.mobile-menu-search').forEach(button => button.addEventListener('click', () => {setMenuOpen(false);dialog?.showModal(); document.querySelector('#global-search')?.focus();}));
document.querySelector('.search-close')?.addEventListener('click', () => dialog.close());
dialog?.addEventListener('click', e => {if (e.target === dialog) {const r=dialog.getBoundingClientRect(); if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom) dialog.close();}});
document.querySelectorAll('.view-button').forEach(button => button.addEventListener('click', () => {
  document.querySelectorAll('.view-button').forEach(b => {b.classList.toggle('is-active',b===button); b.setAttribute('aria-pressed',String(b===button));});
  document.querySelector('#archive-results').classList.toggle('grid-view',button.dataset.view==='grid');
}));
document.querySelector('.back-top')?.addEventListener('click', () => window.scrollTo({top:0,behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'}));
const updateScroll = () => document.querySelector('.back-top')?.classList.toggle('visible',window.scrollY>600);
window.addEventListener('scroll',updateScroll,{passive:true});updateScroll();
document.querySelector('.print-button')?.addEventListener('click', () => window.print());
document.querySelector('.copy-link')?.addEventListener('click', async () => {
  const status=document.querySelector('.copy-status');
  try {await navigator.clipboard.writeText(location.href);status.textContent='Bağlantı kopyalandı.';} catch {status.textContent='Bağlantıyı adres çubuğundan kopyalayabilirsiniz.';}
});
document.querySelectorAll('.faq-list details').forEach(detail => detail.addEventListener('toggle', () => {
  if(detail.open) document.querySelectorAll('.faq-list details').forEach(other=>{if(other!==detail)other.open=false;});
}));
// Kaydırmaya bağlı üst bilgi durumu ve okuma ilerlemesi
const header=document.querySelector('.site-header');
const progress=document.querySelector('.reading-progress');
const article=document.querySelector('.post-main .prose, .detail-main .prose');
// İçindekiler: görünen bölümü işaretle
const tocLinks=[...document.querySelectorAll('.toc-list a[href^="#"]')];
const tocTargets=tocLinks.map(a=>document.getElementById(decodeURIComponent(a.getAttribute('href').slice(1)))).filter(Boolean);
const updateToc=()=>{
  if(!tocTargets.length)return;const marker=window.scrollY+Math.max(130,Math.round(window.innerHeight*0.24));let current=tocTargets[0];
  for(const t of tocTargets){if(t.getBoundingClientRect().top+window.scrollY<=marker)current=t;else break;}
  tocLinks.forEach(a=>a.parentElement.classList.toggle('is-active',a.getAttribute('href')==='#'+current.id));
};
tocLinks.forEach(a=>a.addEventListener('click',e=>{const t=document.getElementById(decodeURIComponent(a.getAttribute('href').slice(1)));if(!t)return;e.preventDefault();t.scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth',block:'start'});history.replaceState(null,'',a.getAttribute('href'));}));
const onScroll=()=>{
  header?.classList.toggle('is-scrolled',window.scrollY>40);
  updateToc();
  if(progress&&article){
    const rect=article.getBoundingClientRect();const start=window.scrollY+rect.top-window.innerHeight*0.35;const end=window.scrollY+rect.bottom-window.innerHeight*0.8;
    const ratio=Math.min(1,Math.max(0,(window.scrollY-start)/Math.max(1,end-start)));
    progress.style.width=(ratio*100).toFixed(2)+'%';progress.classList.toggle('is-visible',ratio>0&&ratio<1);
  }
};
window.addEventListener('scroll',onScroll,{passive:true});window.addEventListener('resize',onScroll);onScroll();
// Görünüm alanına girince yumuşak belirme
const reduced=matchMedia('(prefers-reduced-motion: reduce)').matches||matchMedia('(max-width: 700px)').matches;
if(!reduced&&'IntersectionObserver' in window){
  const groups=['.article-grid','.practice-photo-grid','.practice-grid','.category-grid','.law-grid','.team-grid','.team-directory','.faq-list','.principles','.footer-grid','.contact-details','.archive-list','.hub-grid','.post-grid','.juris-about-inner','.juris-values-grid','.juris-practices .service-grid','.juris-approach-inner','.juris-contact','.journal-list'];
  const singles=['.section-heading','.page-heading .container','.intro-grid>*','.editorial-split>*','.approach-section .container>*','.office-panel','.contact-band-inner','.detail-main','.detail-sidebar','.contact-info','.contact-form-card','.archive-sidebar','.directory-heading','.team-welcome','.team-introduction','.intro-plaque','.principle-strip','.post-main','.post-side>*','.post-recent','.list-heading','.juris-ribbon-inner','.juris-values-heading','.juris-section-heading','.juris-practices .section-tail'];
  const targets=new Set();
  singles.forEach(sel=>document.querySelectorAll(sel).forEach(el=>{el.dataset.delay=el.dataset.delay||'0';targets.add(el);}));
  groups.forEach(sel=>document.querySelectorAll(sel).forEach(group=>{[...group.children].forEach((child,i)=>{if(child.closest('.hero'))return;child.dataset.delay=String(Math.min(6,i%6));targets.add(child);});}));
  const observer=new IntersectionObserver(entries=>{entries.forEach(entry=>{if(entry.isIntersecting){entry.target.classList.add('is-visible');observer.unobserve(entry.target);}});},{rootMargin:'0px 0px -8% 0px',threshold:0.08});
  targets.forEach(el=>{if(el.closest('.hero')||el.closest('.site-header'))return;const r=el.getBoundingClientRect();if(r.top<window.innerHeight&&r.bottom>0){el.classList.add('reveal','is-visible');return;}el.classList.add('reveal');observer.observe(el);});
}

// Accessible submenus: pointer, touch, keyboard, outside click and Escape.
const closeSubmenus=(except)=>document.querySelectorAll('.submenu-toggle').forEach(toggle=>{if(toggle===except)return;toggle.setAttribute('aria-expanded','false');document.getElementById(toggle.getAttribute('aria-controls')).hidden=true;});
document.querySelectorAll('.submenu-toggle').forEach(toggle=>toggle.addEventListener('click',()=>{const open=toggle.getAttribute('aria-expanded')!=='true';closeSubmenus(toggle);toggle.setAttribute('aria-expanded',String(open));document.getElementById(toggle.getAttribute('aria-controls')).hidden=!open;}));
document.addEventListener('click',e=>{if(!e.target.closest('.nav-item'))closeSubmenus();});
document.addEventListener('keydown',e=>{if(e.key==='Escape'){const open=document.querySelector('.submenu-toggle[aria-expanded=true]');if(open){closeSubmenus();open.focus();}}});
document.querySelectorAll('.nav-item').forEach(item=>item.addEventListener('focusout',e=>{if(!item.contains(e.relatedTarget)){const toggle=item.querySelector('.submenu-toggle');if(toggle){toggle.setAttribute('aria-expanded','false');document.getElementById(toggle.getAttribute('aria-controls')).hidden=true;}}}));

// Collapse before editable logo, text or menu lengths can collide.
const fitNavigation = () => {
  if (!header || !nav || !document.body.classList.contains('header-inline')) return;
  const inner = header.querySelector('.header-inner');
  if (innerWidth <= 1200) { header.classList.remove('is-compact-nav'); return; }
  nav.classList.add('nav-measuring');
  const required = nav.getBoundingClientRect().width + inner.querySelector('.brand').getBoundingClientRect().width + inner.querySelector('.header-actions').getBoundingClientRect().width + parseFloat(getComputedStyle(inner).gap) * 2;
  nav.classList.remove('nav-measuring');
  const compact = required > inner.clientWidth;
  if (header.classList.contains('is-compact-nav') && !compact) setMenuOpen(false);
  header.classList.toggle('is-compact-nav', compact);
};
if (header && 'ResizeObserver' in window) new ResizeObserver(fitNavigation).observe(header.querySelector('.header-inner'));
document.fonts?.ready.then(fitNavigation);
window.addEventListener('resize', fitNavigation);
fitNavigation();
