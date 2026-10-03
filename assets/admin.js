'use strict';
const menuButton=document.querySelector('.admin-menu-toggle');
menuButton?.addEventListener('click',()=>{const open=menuButton.getAttribute('aria-expanded')!=='true';menuButton.setAttribute('aria-expanded',String(open));document.querySelector('.admin-sidebar').classList.toggle('open',open);});
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&menuButton?.getAttribute('aria-expanded')==='true')menuButton.click();});
document.addEventListener('click',e=>{if(menuButton?.getAttribute('aria-expanded')==='true'&&!e.target.closest('.admin-sidebar,.admin-menu-toggle'))menuButton.click();});
let dirty=false;let submitting=false;
const changed=event=>{if(event?.target?.matches('[data-settings-filter]'))return;dirty=true;document.querySelectorAll('.save-status').forEach(s=>{s.textContent='Kaydedilmemiş değişiklikler var.';s.classList.add('unsaved');});};
document.querySelectorAll('[data-dirty-form]').forEach(form=>{form.addEventListener('input',changed);form.addEventListener('change',changed);form.addEventListener('submit',event=>{queueMicrotask(()=>{if(event.defaultPrevented)return;submitting=true;form.querySelectorAll('button[type=submit]').forEach(button=>button.disabled=true);form.querySelectorAll('.save-status').forEach(status=>status.textContent='Kaydediliyor…');});});});
window.addEventListener('beforeunload',event=>{if(dirty&&!submitting){event.preventDefault();event.returnValue='';}});
document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{if(!window.confirm(form.dataset.confirm))event.preventDefault();else submitting=true;}));
document.querySelectorAll('[data-format]').forEach(button=>button.addEventListener('click',()=>{
  const area=document.querySelector('#body-editor');if(!area)return;
  const start=area.selectionStart,end=area.selectionEnd,selected=area.value.slice(start,end);let text='';
  const html=document.querySelector('#body-format')?.value==='html';
  switch(button.dataset.format){case 'heading':text=html?'<h2>'+(selected||'Bölüm başlığı')+'</h2>':'\n## '+(selected||'Bölüm başlığı')+'\n';break;case 'subheading':text=html?'<h3>'+(selected||'Alt başlık')+'</h3>':'\n### '+(selected||'Alt başlık')+'\n';break;case 'bold':text=html?'<strong>'+(selected||'kalın metin')+'</strong>':'**'+(selected||'kalın metin')+'**';break;case 'list':text=html?'<ul><li>'+(selected||'Liste maddesi')+'</li></ul>':'\n- '+(selected||'Liste maddesi')+'\n';break;case 'link':text=html?'<a href="https://ornek.com">'+(selected||'Bağlantı metni')+'</a>':'['+(selected||'Bağlantı metni')+'](https://ornek.com)';break;}
  area.setRangeText(text,start,end,'select');area.focus();area.dispatchEvent(new Event('input',{bubbles:true}));
}));
const picker=document.querySelector('#media-picker');let pickerTarget=null;
document.querySelector('#rich-editor')?.addEventListener('rich-image-request',()=>{pickerTarget='rich-image';picker?.showModal();});
function updateImage(input){const field=input.closest('.media-field');if(!field)return;const img=field.querySelector('.media-field-preview img'),empty=field.querySelector('.media-field-preview>span');const path=input.value;const valid=/^\/assets\/(images|uploads)\/[a-zA-Z0-9_./-]+\.(jpg|jpeg|png|webp|gif|svg)$/.test(path);img.hidden=!valid;empty.hidden=valid;if(valid)img.src=path;}
document.querySelectorAll('.media-picker-open').forEach(button=>button.addEventListener('click',()=>{pickerTarget=document.getElementById(button.dataset.target);picker.showModal();}));
document.querySelector('.media-picker-close')?.addEventListener('click',()=>picker.close());
document.querySelectorAll('.media-choice').forEach(button=>button.addEventListener('click',()=>{if(pickerTarget==='rich-image'){picker.close();document.querySelector('#rich-editor')?.dispatchEvent(new CustomEvent('rich-image-selected',{detail:{path:button.dataset.path,alt:button.querySelector('img')?.alt||''}}));}else if(pickerTarget){pickerTarget.value=button.dataset.path;updateImage(pickerTarget);pickerTarget.dispatchEvent(new Event('input',{bubbles:true}));picker.close();}pickerTarget=null;}));
document.querySelectorAll('.media-clear').forEach(button=>button.addEventListener('click',()=>{const input=document.getElementById(button.dataset.target);input.value='';updateImage(input);input.dispatchEvent(new Event('input',{bubbles:true}));}));
document.querySelectorAll('.media-field>input').forEach(input=>input.addEventListener('change',()=>updateImage(input)));
document.querySelectorAll('.copy-path').forEach(button=>button.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(button.dataset.path);button.textContent='Kopyalandı';document.querySelector('.copy-feedback').textContent='Görselin dosya yolu kopyalandı.';}catch{const input=button.parentElement.querySelector('input');input.focus();input.select();document.querySelector('.copy-feedback').textContent='Dosya yolunu Ctrl+C ile kopyalayabilirsiniz.';}}));
// Meta & SEO: karakter sayaçları, canlı Google önizlemesi, slug önerisi
const brandName=(document.querySelector('.admin-brand>span')?.firstChild?.textContent||'').trim();
document.querySelectorAll('[data-counter]').forEach(field=>{const limit=Number(field.dataset.counter);const out=field.parentElement.querySelector('.counter');if(!out)return;const paint=()=>{const n=[...field.value].length;out.textContent=n+' / '+limit+(n>limit?' · önerilen sınır aşıldı':n===0?' · boş bırakılırsa varsayılan kullanılır':'');out.classList.toggle('over',n>limit);out.classList.toggle('good',n>0&&n<=limit&&n>=Math.floor(limit*0.6));};field.addEventListener('input',paint);paint();});
document.querySelectorAll('.seo-form').forEach(form=>{const t=form.querySelector('[data-serp="title"]'),d=form.querySelector('[data-serp="desc"]'),pt=form.querySelector('[data-serp-title]'),pd=form.querySelector('[data-serp-desc]'),h1=form.querySelector('[data-slug-source]');
  const sync=()=>{if(pt){const base=(t?.value.trim()||h1?.value.trim()||pt.dataset.fallback||'');pt.textContent=base+(brandName?' | '+brandName:'');}if(pd){const text=(d?.value.trim()||pd.dataset.fallback||'');pd.textContent=text.length>160?text.slice(0,157)+'…':text;}};
  [t,d,h1].forEach(el=>el?.addEventListener('input',sync));sync();
  const slug=form.querySelector('[data-slug-target]');if(slug&&h1&&!slug.readOnly){let touched=slug.value!=='';slug.addEventListener('input',()=>{touched=slug.value!=='';});h1.addEventListener('input',()=>{if(!touched)slug.value=slugifyTr(h1.value);});}
});
function slugifyTr(s){const map={'ç':'c','ğ':'g','ı':'i','ö':'o','ş':'s','ü':'u','Ç':'c','Ğ':'g','İ':'i','I':'i','Ö':'o','Ş':'s','Ü':'u'};return s.replace(/[çğıöşüÇĞİIÖŞÜ]/g,c=>map[c]).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,180);}
if(location.hash){try{const target=document.getElementById(decodeURIComponent(location.hash.slice(1)));if(target){if(target.tagName==='DETAILS')target.open=true;requestAnimationFrame(()=>{target.scrollIntoView({block:'center'});if(target.matches('input,textarea,select'))target.focus({preventScroll:true});});}}catch{ /* Invalid fragment identifiers do not affect the editor. */ }}

const settingsFilter=document.querySelector('[data-settings-filter]');
settingsFilter?.addEventListener('input',()=>{
 const query=settingsFilter.value.toLocaleLowerCase('tr').trim();let visible=0;
 document.querySelectorAll('.settings-fields > *').forEach(field=>{field.hidden=!field.textContent.toLocaleLowerCase('tr').includes(query);if(!field.hidden)visible++;});
 document.querySelector('[data-settings-empty]').hidden=visible>0;
});

// Checkboxes belong to the bulk form without nesting the individual action forms.
document.querySelector('[data-select-content]')?.addEventListener('change', event => {
  document.querySelectorAll('input[name="ids[]"][form="content-bulk"]').forEach(input => { input.checked = event.target.checked; });
});
