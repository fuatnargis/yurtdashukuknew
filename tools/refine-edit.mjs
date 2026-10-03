import {readFileSync,writeFileSync} from 'node:fs';
function edit(file,transform){const source=readFileSync(file,'utf8');const result=transform(source);if(result===source)throw new Error('No changes: '+file);writeFileSync(file,result);}
function replace(s,from,to){if(!s.includes(from))throw new Error('Missing: '+from.slice(0,100));return s.replace(from,to);}
edit('index.php',s=>{
 const start=s.indexOf("elseif($path==='/'):?>");const end=s.indexOf("<?php elseif($path==='/makaleler'):",start);
 if(start<0||end<0)throw new Error('Home markers');
 s=s.slice(0,start)+"elseif($path==='/'):require __DIR__.'/app/views/home.php';?>\n"+s.slice(end);
 s=replace(s,"'/makaleler','/ekibimiz','/iletisim'];","'/makaleler','/kanunlar','/ekibimiz','/iletisim'];");
 s=replace(s,"$titles=['/makaleler'=>'Makaleler'","$titles=['/kanunlar'=>setting('laws_title'),'/makaleler'=>'Makaleler'");
 s=replace(s,"$a['category']===$category","($a['category']?:'Genel')===$category");
 s=replace(s,"?><section class=\"section archive-section\">","if(!$search&&!$category)category_directory($all);\n?><section class=\"section archive-section\" id=\"yayinlar\">");
 s=replace(s,"<?php elseif($path==='/calisma-alanlari'):","<?php elseif($path==='/kanunlar'):require __DIR__.'/app/views/laws.php';?>\n<?php elseif($path==='/kurumsal'&&$detail):require __DIR__.'/app/views/corporate.php';?>\n<?php elseif($path==='/ekibimiz'&&$detail):require __DIR__.'/app/views/team.php';?>\n<?php elseif($path==='/calisma-alanlari'):");
 s=replace(s,'<div class="container practice-grid"><?php foreach(entries(\'practice\') as $i=>$p)practice_card($p,$i);?>','<div class="container practice-photo-grid practice-directory"><?php foreach(entries(\'practice\') as $i=>$p)practice_photo($p,$i);?>');
 return s;
});
edit('app/layout.php',s=>{
 s=replace(s,'declare(strict_types=1);',"declare(strict_types=1);\nrequire_once __DIR__.'/reference-components.php';");
 s=replace(s,"$primary=preg_match", "$action=preg_match('/^#[a-f0-9]{6}$/i',setting('action_color'))?setting('action_color'):'#234e78';\n    $primary=preg_match");
 s=replace(s,'<link rel="stylesheet" href="/assets/site.css?v=1">','<link rel="preload" href="/assets/fonts/manrope-regular.ttf" as="font" type="font/ttf" crossorigin><link rel="preload" href="/assets/fonts/cormorant-medium.ttf" as="font" type="font/ttf" crossorigin><link rel="stylesheet" href="/assets/site.css?v=2"><link rel="stylesheet" href="/assets/refined.css?v=2">');
 s=replace(s,'--navy:<?=e($primary)?>}','--navy:<?=e($primary)?>;--action:<?=e($action)?>}');
 s=replace(s,'/assets/site.js?v=1','/assets/site.js?v=2');
 s=replace(s,'<button class="back-top icon-button"',`<?php if(setting('show_mobile_contact')==='1'):?><nav class="mobile-contact-bar" aria-label="Hızlı iletişim"><?php if(setting('phone')):?><a href="tel:<?=e(preg_replace('/[^+0-9]/','',setting('phone')))?>"><?=icon('phone')?> Telefon</a><?php endif;if(setting('whatsapp')):?><a href="https://wa.me/<?=e(preg_replace('/\\D/','',setting('whatsapp')))?>" target="_blank" rel="noopener noreferrer"><?=icon('mail')?> WhatsApp</a><?php else:?><a href="/iletisim#iletisim-formu"><?=icon('mail')?> Görüşme talebi</a><?php endif;?></nav><?php endif;?>
    <button class="back-top icon-button"`);
 return s;
});
edit('admin/index.php',s=>{
 s=replace(s,"<?php if($type==='menu'):?><label>Bağlantı", "<?php if($type==='law'):?><label>Resmî kaynak bağlantısı <span class=\"required\">*</span><input type=\"url\" name=\"link\" required value=\"<?=e(form_value('link',$en['link']))?>\" placeholder=\"https://www.mevzuat.gov.tr/…\" maxlength=\"500\"></label><label>Kanun numarası<input name=\"category\" maxlength=\"100\" value=\"<?=e(form_value('category',$en['category']))?>\" placeholder=\"4721\"></label><label>Kısa açıklama<textarea name=\"excerpt\" rows=\"3\" maxlength=\"1500\"><?=e(form_value('excerpt',$en['excerpt']))?></textarea></label><p class=\"a-field-hint\">Ziyaretçi kanunun resmî kaynağına yeni sekmede yönlendirilir. HTTPS bağlantısı kullanın.</p><?php elseif($type==='menu'):?><label>Bağlantı");
 s=s.replaceAll("['menu','faq'],true)","['menu','faq','law'],true)");
 s=replace(s,"$type==='menu'?'Bağlantı':'Kategori / Görev'", "$type==='menu'?'Bağlantı':($type==='law'?'Kanun no.':'Kategori / Görev')");
 s=replace(s,"<div class=\"prose\"><?=text_markup($en['body'])?></div></article>","<div class=\"prose\"><?=text_markup($en['body'])?></div><?php if($en['type']==='law'):?><a class=\"a-button\" href=\"<?=e(safe_url($en['link']))?>\" target=\"_blank\" rel=\"noopener noreferrer\">Resmî kaynağı aç <?=icon('arrow-up')?></a><?php endif;?></article>");
 return s;
});
edit('admin/action.php',s=>replace(s,"if(mb_strlen((string)$en['body'])>150000", "if($en['type']==='law'&&(!filter_var($en['link'],FILTER_VALIDATE_URL)||parse_url($en['link'],PHP_URL_SCHEME)!=='https'))throw new RuntimeException('Yedekte geçersiz kanun bağlantısı var.');\n            if(mb_strlen((string)$en['body'])>150000"));
