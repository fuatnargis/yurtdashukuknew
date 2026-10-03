'use strict';
const themeForm = document.querySelector('.settings-form');
const preview = document.querySelector('.theme-preview');
const colorVars = {primary_color:'navy',accent_color:'gold',action_color:'action',button_text_color:'button-text',background_color:'background',surface_color:'surface',text_color:'ink',muted_color:'muted',border_color:'line',header_color:'header-bg',header_text_color:'header-text',topbar_color:'topbar-bg',topbar_text_color:'topbar-text',footer_color:'footer-bg',footer_text_color:'footer-text',hero_overlay_color:'hero-overlay',hero_text_color:'hero-text',dark_section_color:'dark-bg',dark_section_text_color:'dark-text'};
function contrast(a,b){
  const lum=hex=>{const c=hex.slice(1).match(/../g).map(x=>parseInt(x,16)/255).map(v=>v<=.04045?v/12.92:((v+.055)/1.055)**2.4);return c[0]*.2126+c[1]*.7152+c[2]*.0722;};
  const x=lum(a),y=lum(b);return (Math.max(x,y)+.05)/(Math.min(x,y)+.05);
}
function updateThemePreview(){
  if(!preview)return;
  for(const [key,variable] of Object.entries(colorVars)){const input=themeForm.elements.namedItem(key);if(input)preview.style.setProperty('--'+variable,input.value);}
  const font=themeForm.elements.namedItem('heading_font')?.value;
  const bodyFont=themeForm.elements.namedItem('body_font')?.value;
  preview.style.setProperty('--preview-font',bodyFont==='sans'?'Manrope,Arial,sans-serif':bodyFont==='system'?'system-ui,Arial,sans-serif':'Arial,Helvetica,sans-serif');
  preview.style.setProperty('--preview-heading',font==='classic'?"Georgia,'Times New Roman',serif":font==='serif'?"'Cormorant Garamond',Georgia,serif":font==='sans'?'Manrope,Arial,sans-serif':'Arial,Helvetica,sans-serif');
  for(const key of ['body_font_size','section_heading_size','card_radius','hero_overlay_opacity']){
    const value=themeForm.elements.namedItem(key)?.value;if(value)preview.style.setProperty('--preview-'+key.replaceAll('_','-'),key==='hero_overlay_opacity'?Number(value)/100:value+'px');
  }
  const pairs=[['text_color','background_color','Sayfa metni'],['text_color','surface_color','Kart metni'],['muted_color','surface_color','İkincil metin'],['button_text_color','action_color','Buton'],['header_text_color','header_color','Menü'],['footer_text_color','footer_color','Alt bilgi'],['dark_section_text_color','dark_section_color','Koyu bölüm']];
  const low=pairs.filter(([a,b])=>contrast(themeForm.elements[a].value,themeForm.elements[b].value)<4.5).map(x=>x[2]);
  document.querySelector('.theme-contrast').textContent=low.length?'Okunabilirliği artırmak için şu renk çiftlerinin kontrastını yükseltebilirsiniz: '+low.join(', ')+'.':'Metin ve zemin renkleri en az 4,5:1 kontrast sağlıyor.';
}
document.querySelectorAll('.color-control').forEach(control=>{
  const picker=control.querySelector('[type=color]'),hex=control.querySelector('.color-hex');
  picker.addEventListener('input',()=>{hex.value=picker.value;hex.setCustomValidity('');updateThemePreview();});
  hex.addEventListener('input',()=>{const valid=/^#[a-f0-9]{6}$/i.test(hex.value);hex.setCustomValidity(valid?'':'#20527A biçiminde altı haneli renk kodu girin.');if(valid){picker.value=hex.value;updateThemePreview();}});
});
for(const key of ['heading_font','body_font','body_font_size','section_heading_size','card_radius','hero_overlay_opacity'])themeForm?.elements.namedItem(key)?.addEventListener('input',updateThemePreview);
const basePalette={background_color:'#f7f5f2',surface_color:'#ffffff',text_color:'#14110f',muted_color:'#6b665f',border_color:'#e8e4df',header_color:'#ffffff',header_text_color:'#14110f',button_text_color:'#ffffff',hero_text_color:'#ffffff',footer_text_color:'#eee9e2',dark_section_text_color:'#ffffff'};
const palettes={navy:{...basePalette,primary_color:'#102b46',accent_color:'#c9ad7a',action_color:'#20527a',background_color:'#f4f6f8',text_color:'#192b3b',muted_color:'#596b7a',border_color:'#dbe3ea',header_text_color:'#102b46',footer_color:'#102b46',footer_text_color:'#eef2f5',hero_overlay_color:'#102b46',hero_overlay_opacity:'0',dark_section_color:'#102b46'},editorial:{...basePalette,primary_color:'#19342f',accent_color:'#c5a87a',action_color:'#735639',background_color:'#f5f3ee',text_color:'#22332e',muted_color:'#596b64',border_color:'#d9ded6',header_text_color:'#19342f',footer_color:'#19342f',footer_text_color:'#eeeae1',hero_overlay_color:'#19342f',dark_section_color:'#19342f'},warm:{...basePalette,primary_color:'#14110f',accent_color:'#b48348',action_color:'#856821',footer_color:'#14110f',hero_overlay_color:'#14110f',dark_section_color:'#14110f'},green:{...basePalette,primary_color:'#173e32',accent_color:'#b79764',action_color:'#285845',footer_color:'#173e32',hero_overlay_color:'#173e32',dark_section_color:'#173e32',background_color:'#f4f6f2'}};
document.querySelectorAll('[data-palette]').forEach(button=>button.addEventListener('click',()=>{for(const [key,value] of Object.entries(palettes[button.dataset.palette])){const input=themeForm.elements[key];input.value=value;input.dispatchEvent(new Event('input',{bubbles:true}));}updateThemePreview();}));
document.querySelectorAll('.section-order').forEach(editor=>{
  const list=editor.querySelector('ol'),input=editor.querySelector('input');
  const sync=()=>{input.value=[...list.children].map(x=>x.dataset.section).join(',');[...list.children].forEach((li,i)=>{li.querySelector('[data-move=up]').disabled=i===0;li.querySelector('[data-move=down]').disabled=i===list.children.length-1;});};
  editor.addEventListener('click',e=>{const button=e.target.closest('[data-move]');if(!button)return;const li=button.closest('li');if(button.dataset.move==='up'&&li.previousElementSibling)list.insertBefore(li,li.previousElementSibling);else if(button.dataset.move==='down'&&li.nextElementSibling)list.insertBefore(li.nextElementSibling,li);sync();input.dispatchEvent(new Event('input',{bubbles:true}));editor.querySelector('.order-status').textContent=li.querySelector('span').textContent+' sırası güncellendi.';const usable=li.querySelector('button:not(:disabled)');usable?.focus();});sync();
});
updateThemePreview();
