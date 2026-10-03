<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/layout.php';
$path=rawurldecode((string)parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH));
$path=rtrim($path,'/')?:'/';
$managed=managed_redirects()[$path]??null;
if($managed){header('Location: '.$managed['target_path'],true,(int)$managed['status_code']);exit;}
if($legacy=legacy_redirect_path($path)){header('Location: '.$legacy.(!empty($_SERVER['QUERY_STRING'])&&!str_contains($legacy,'#')?'?'.$_SERVER['QUERY_STRING']:''),true,301);exit;}
if($path==='/sitemap.xml') {
    if(setting('sitemap_enabled','1')!=='1'){http_response_code(404);header('Content-Type: text/plain; charset=utf-8');echo 'Site haritası kapalı.';exit;}
    header('Content-Type: application/xml; charset=utf-8');
    $base=site_base();if(!$base){http_response_code(503);echo '<?xml version="1.0" encoding="UTF-8"?><error>Site adresi yönetim panelinden tanımlanmalıdır.</error>';exit;}
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
    foreach(sitemap_rows() as $row){echo '<url><loc>'.e($base.$row['path']).'</loc><lastmod>'.e($row['lastmod']).'</lastmod>'.($row['image']?'<image:image><image:loc>'.e($base.safe_image($row['image'])).'</image:loc><image:title>'.e($row['title']).'</image:title></image:image>':'').'</url>';}
    echo '</urlset>';exit;
}
if($path==='/rss.xml'){
    header('Content-Type: application/rss+xml; charset=utf-8');$base=rtrim(setting('site_url'),'/');
    echo '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel><title>'.e(setting('brand')).' — Makaleler</title><link>'.e($base.'/makaleler').'</link><description>'.e(setting('seo_description')).'</description><language>tr-TR</language><atom:link href="'.e($base.'/rss.xml').'" rel="self" type="application/rss+xml"/>';
    foreach(array_slice(entries('article'),0,20) as $a)echo '<item><title>'.e($a['title']).'</title><link>'.e($base.entry_url($a)).'</link><guid isPermaLink="true">'.e($base.entry_url($a)).'</guid><pubDate>'.date(DATE_RSS,strtotime($a['published_at'])).'</pubDate>'.($a['category']?'<category>'.e($a['category']).'</category>':'').'<description>'.e(seo_plain($a['meta_description']?:($a['excerpt']?:$a['body']),300)).'</description></item>';
    echo '</channel></rss>';exit;
}
if($path==='/llms.txt'){
    if(setting('indexing')!=='1')header('X-Robots-Tag: noindex, nofollow');
    header('Content-Type: text/plain; charset=utf-8');$base=rtrim(setting('site_url'),'/');
    echo '# '.setting('brand')."\n\n> ".setting('seo_description')."\n\n";
    if(setting('geo_summary'))echo setting('geo_summary')."\n\n";
    if(setting('seo_service_area'))echo 'Hizmet bölgesi: '.setting('seo_service_area')."\n";
    if(setting('geo_note'))echo setting('geo_note')."\n\n";
    if(setting('address'))echo 'Adres: '.preg_replace('/\s+/',' ',setting('address'))."\n";if(setting('phone'))echo 'Telefon: '.setting('phone')."\n";if(setting('email'))echo 'E-posta: '.setting('email')."\n";echo "Dil: Türkçe\n\n## Çalışma Alanları\n\n";
    foreach(entries('practice') as $p)echo '- ['.$p['title'].']('.$base.entry_url($p).'): '.seo_plain($p['meta_description']?:$p['excerpt'],200)."\n";
    echo "\n## Makaleler\n\n";foreach(entries('article') as $a)echo '- ['.$a['title'].']('.$base.entry_url($a).'): '.seo_plain($a['meta_description']?:$a['excerpt'],200)."\n";
    echo "\n## Avukatlar\n\n";foreach(entries('team') as $person)echo '- ['.$person['title'].']('.$base.entry_url($person).'): '.seo_plain($person['excerpt'],200)."\n";
    echo "\n## Sayfalar\n\n- [Hakkımızda]($base/kurumsal)\n- [Kurucu Avukat]($base/avukatlar)\n- [İletişim]($base/iletisim)\n- [Site haritası]($base/sitemap.xml)\n- [RSS]($base/rss.xml)\n- [Sıkça sorulan sorular]($base/sikca-sorulan-sorular)\n";exit;
}
if($path==='/robots.txt'){header('Content-Type: text/plain');echo "User-agent: *\n".(setting('indexing')==='1'?"Disallow: /admin/\nDisallow: /storage/\nDisallow: /app/\nAllow: /llms.txt\n":"Disallow: /\n");if(setting('site_url')&&setting('sitemap_enabled','1')==='1')echo 'Sitemap: '.rtrim(setting('site_url'),'/').'/sitemap.xml';exit;}
$title=setting('seo_title');$description=setting('seo_description');$detail=null;$type='';$page='';$meta=['jsonld'=>[]];
if(preg_match('~^/(makaleler|calisma-alanlari|sayfa|avukatlar)/([a-z0-9-]+)$~',$path,$m)){
    $type=['makaleler'=>'article','calisma-alanlari'=>'practice','sayfa'=>'page','avukatlar'=>'team'][$m[1]];
    $detail=entry($type,$m[2]);
    if(!$detail){
        $redirect=db()->prepare('SELECT e.* FROM entry_redirects r JOIN entries e ON e.id=r.entry_id WHERE r.type=? AND r.slug=? AND e.status=? AND e.published_at<=?');
        $redirect->execute([$type,$m[2],'published',date('Y-m-d H:i:s')]);
        if($target=$redirect->fetch()){header('Location: '.entry_url($target),true,301);exit;}
    }
    if($detail){$title=branded_title($detail['meta_title']?:$detail['title']);$description=$detail['meta_description']?:($detail['excerpt']?:entry_plain($detail));$page='detail';}
}
if($path==='/kurumsal'){$detail=entry('page','kurumsal');$type='page';if($detail){$page='kurumsal';$title=branded_title($detail['meta_title']?:$detail['title']);$description=$detail['meta_description']?:($detail['excerpt']?:entry_plain($detail));}}
$routeKey=ltrim($path,'/');$isStatic=$routeKey!==''&&isset(SEO_ROUTES[$routeKey]);
if($isStatic){$rm=seo_route_meta($routeKey);$title=$rm['title'].' | '.setting('brand');$description=$rm['description'];$meta['noindex']=$rm['noindex'];$meta['jsonld'][]=jsonld_webpage($rm['title'],$description,$path,$routeKey==='iletisim'?'ContactPage':'CollectionPage');$meta['jsonld'][]=jsonld_breadcrumbs([SEO_ROUTES[$routeKey]['label']=>$path]);}
if(($path==='/sikca-sorulan-sorular'||($path==='/'&&setting('show_faq')==='1'))&&($faqs=entries('faq')))$meta['jsonld'][]=jsonld_faq($faqs);
if($detail){
    $meta['og_title']=$detail['og_title']??'';$meta['og_description']=$detail['og_description']??'';$meta['og_image']=$detail['og_image']??'';
    $crumbLabel=match($type){'article'=>['Makaleler'=>'/makaleler'],'practice'=>['Çalışma Alanları'=>'/calisma-alanlari'],'team'=>['Kurucu Avukat'=>'/avukatlar'],default=>[]};
    $crumbLabel[$detail['title']]=$path;
    $meta['jsonld'][]=jsonld_breadcrumbs($crumbLabel);
    if($type==='article'){$meta['type']='article';$meta['published']=date(DATE_ATOM,strtotime($detail['published_at']));$meta['modified']=date(DATE_ATOM,strtotime(substr((string)$detail['updated_at'],0,19))?:strtotime($detail['published_at']));$meta['author']=$detail['author']?:setting('brand');$meta['section']=$detail['category'];$meta['jsonld'][]=jsonld_article($detail,$path);}
    elseif($type==='practice')$meta['jsonld'][]=jsonld_service($detail,$path);
    elseif($type==='team'){
        $meta['jsonld'][]=jsonld_person($detail,$path);
        $profilePage=jsonld_webpage($detail['title'],$description,$path,'ProfilePage');
        $profilePage['mainEntity']=['@id'=>site_base().$path.'#person'];$meta['jsonld'][]=$profilePage;
    }
    else $meta['jsonld'][]=jsonld_webpage($detail['title'],$description,$path,$path==='/kurumsal'?'AboutPage':'WebPage');
}
if($path==='/makaleler'&&(query('q')!==''||query('kategori')!==''||query('sayfa')!==''))$meta['noindex']=true;
$known=$path==='/' || $isStatic || $detail;
if(!$known){http_response_code(404);$title='Sayfa Bulunamadı | '.setting('brand');$meta['noindex']=true;}
if($path==='/iletisim' && $_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();$errors=[];$old=[];foreach(['name','email','phone','subject','message'] as $k)$old[$k]=input($k);
    if(!contact_form_available())$errors[]='İletişim formu şu anda kullanılamıyor.';
    if(mb_strlen($old['name'])<2 || mb_strlen($old['name'])>100)$errors[]='Adınızı ve soyadınızı 2–100 karakter arasında yazın.';
    if(!filter_var($old['email'],FILTER_VALIDATE_EMAIL)||strlen($old['email'])>190)$errors[]='Geçerli bir e-posta adresi yazın.';
    if(mb_strlen($old['phone'])>30)$errors[]='Telefon numarası en fazla 30 karakter olabilir.';
    if(mb_strlen($old['subject'])<2||mb_strlen($old['subject'])>150)$errors[]='Lütfen görüşme konusunu belirtin.';
    if(mb_strlen($old['message'])<15||mb_strlen($old['message'])>5000)$errors[]='Mesajınız 15–5000 karakter arasında olmalıdır.';
    $bucket=rate_bucket('contact');if(rate_limited($bucket,5,3600))$errors[]='Çok sayıda talep iletildi. Lütfen daha sonra tekrar deneyin.';
    if(input('website')!=='')$errors[]='Form doğrulanamadı.';
    if(empty($errors)){
        $notice=entry('page','kvkk');db()->prepare('INSERT INTO messages(name,email,phone,subject,message,created_at,notice_version) VALUES(?,?,?,?,?,?,?)')->execute([$old['name'],$old['email'],$old['phone'],$old['subject'],$old['message'],date('Y-m-d H:i:s'),hash('sha256',$notice['body']??'')]);
        rate_hit($bucket);$_SESSION['csrf']=bin2hex(random_bytes(32));flash('Görüşme talebiniz kaydedildi. İlettiğiniz iletişim bilgileri üzerinden sizinle bağlantı kurulabilir.');redirect('/iletisim#iletisim-formu');
    }
    http_response_code(422);
}
if($path==='/sikca-sorulan-sorular')$meta['jsonld']=array_values(array_filter($meta['jsonld'],fn($n)=>($n['@type']??'')!=='CollectionPage'));
if($path==='/' )$meta['jsonld'][]=jsonld_webpage($title,$description,'/');
head_html($title,$description,$detail['image']??'',$path,$meta);
?><main id="main"><?php
if(!$known): page_heading('404',ui('notfound_title'),ui('notfound_text'));?><section class="section"><div class="container empty-state"><?=icon('search')?><h2><?=e(ui('notfound_next'))?></h2><p><?=e(ui('notfound_help'))?></p><div class="button-row"><a href="/" class="button"><?=e(ui('home_link'))?> <?=icon('arrow')?></a><a href="/makaleler" class="button button-outline">Makaleler</a></div></div></section><?php
elseif($path==='/'):require __DIR__.'/app/views/home.php';?>
<?php elseif($path==='/sikca-sorulan-sorular'):page_heading(ui('faq_eyebrow'),ui('all_faq'),ui('faq_text'));?><section class="section"><div class="container faq-page"><?php faq_list();?></div></section><?php contact_band();?>
<?php elseif($path==='/makaleler'):
    $all=entries('article');$categories=array_count_values(array_map(fn($a)=>$a['category']?:'Genel',$all));ksort($categories);$search=mb_substr(query('q'),0,100);$category=query('kategori');
    $filtered=array_values(array_filter($all,fn($a)=>(!$category||($a['category']?:'Genel')===$category)&&(!$search||mb_stripos(mb_strtolower(str_replace(['I','İ'],['ı','i'],$a['title'].' '.$a['excerpt'].' '.$a['body'].' '.$a['category'])),mb_strtolower(str_replace(['I','İ'],['ı','i'],$search)))!==false)));
    $perPage=max(3,min(24,(int)setting('articles_per_page','6')));$pages=max(1,(int)ceil(count($filtered)/$perPage));$num=max(1,min($pages,(int)query('sayfa','1')));$items=array_slice($filtered,($num-1)*$perPage,$perPage);
    if(!$search&&!$category):?><section class="hub-section"><div class="container"><?php simple_breadcrumb([ui('hub_title')=>'']);?><h1 class="hub-title"><?=e(ui('hub_title'))?></h1><?php if(setting('articles_text')):?><p class="hub-text"><?=e(setting('articles_text'))?></p><?php endif;?><div class="hub-grid"><?php foreach($categories as $name=>$count)hub_card($name,$count);?></div><?php if(!$categories):?><div class="empty-state"><?=icon('book')?><h2><?=e(ui('article_empty'))?></h2></div><?php endif;?></div></section><?php
    else:$listTitle=$search?'Arama: “'.$search.'”':trim($category.' '.ui('category_list_suffix'));?><section class="list-section"><div class="container"><?php simple_breadcrumb([ui('hub_title')=>'/makaleler',($search?'Arama':$category)=>'']);?><div class="list-heading"><h1 class="list-title"><?=e($listTitle)?></h1><span class="list-count"><?=count($filtered)?> <?=e(ui('article_count_suffix'))?></span></div><div class="post-grid" id="yayinlar"><?php foreach($items as $a)post_card($a);?></div><?php if(!$items):?><div class="empty-state"><?=icon('search')?><h2><?=e(ui('article_search_empty'))?></h2><p><?=e(ui('article_search_help'))?></p><a class="text-link" href="/makaleler"><?=e(ui('all_categories'))?> <?=icon('arrow')?></a></div><?php endif;if($pages>1):?><nav class="pagination" aria-label="Sayfalar"><?php for($i=1;$i<=$pages;$i++):?><a href="/makaleler?<?=e(http_build_query(array_filter(['q'=>$search,'kategori'=>$category,'sayfa'=>$i])))?>" <?=$i===$num?'aria-current="page"':''?>><?=$i?></a><?php endfor;?></nav><?php endif;?><p class="list-back"><a class="text-link" href="/makaleler">← <?=e(ui('all_categories'))?></a></p></div></section><?php endif;?>
<?php elseif($path==='/kurumsal'&&$detail):require __DIR__.'/app/views/corporate.php';?>
<?php elseif($path==='/avukatlar'):page_heading(ui('founder_title'),ui('founder_title'),ui('founder_intro'));?><section class="section"><div class="container lawyer-directory"><?php foreach(entries('team') as $profile)profile_tile($profile);?></div></section><?php
elseif($type==='team'&&$detail):require __DIR__.'/app/views/profile.php';?>
<?php elseif($path==='/calisma-alanlari'):page_heading('ÇALIŞMA ALANLARIMIZ',ui('practice_archive_title'),'');?><section class="section"><div class="container"><div class="prose archive-lead"><?=rich_text(setting('practice_text'))?></div></div></section><section class="section"><div class="container service-grid practice-directory"><?php foreach(entries('practice') as $i=>$p)service_card($p,$i);?></div></section><?php contact_band();?>
<?php elseif($path==='/iletisim'):page_heading('İLETİŞİM',ui('contact_page_title'),'');$old=$old??[];?><section class="section"><div class="container"><div class="prose archive-lead"><?=rich_text(setting('contact_text'))?></div></div></section><section class="section"><div class="container contact-grid"><div class="contact-info"><span class="eyebrow"><?=e(setting('brand'))?></span><h2><?=e(ui('contact_info_title'))?></h2><p><?=e(ui('contact_info_text'))?></p><div class="contact-details"><div><?=icon('pin')?><div><h3><?=e(ui('contact_address_label'))?></h3><p><?=nl2br(e(setting('address')))?></p><a class="text-link" href="<?=e(map_open_url())?>" target="_blank" rel="noopener noreferrer"><?=e(ui('contact_map_link'))?> <?=icon('arrow-up')?></a></div></div><?php if(setting('phone')):?><div><?=icon('phone')?><div><h3><?=e(ui('contact_phone_label'))?></h3><a href="tel:<?=e(preg_replace('/[^+0-9]/','',setting('phone')))?>"><?=e(setting('phone'))?></a></div></div><?php endif;if(setting('email')):?><div><?=icon('mail')?><div><h3><?=e(ui('contact_email_label'))?></h3><a href="mailto:<?=e(setting('email'))?>"><?=e(setting('email'))?></a></div></div><?php endif;?><div><?=icon('clock')?><div><h3><?=e(ui('contact_hours_label'))?></h3><p><?=e(setting('office_hours'))?></p></div></div></div><?php if(setting('whatsapp')):?><a class="button button-outline" href="<?=e(whatsapp_url(setting('whatsapp')))?>" target="_blank" rel="noopener noreferrer"><?=icon('whatsapp')?> <?=e(ui('whatsapp_contact'))?></a><?php endif;?></div><div class="contact-form-card" id="iletisim-formu"><span class="eyebrow"><?=e(ui('contact_form_eyebrow'))?></span><h2><?=e(ui('contact_form_title'))?></h2><?=flash_html()?><?php if(!empty($errors)):?><div class="notice error" role="alert"><ul><?php foreach($errors as $error):?><li><?=e($error)?></li><?php endforeach;?></ul></div><?php endif;if(contact_form_available()):?><form action="/iletisim#iletisim-formu" method="post" class="contact-form"><?=csrf_field()?><div class="form-grid"><label><?=e(ui('form_name'))?> <span>*</span><input name="name" autocomplete="name" required minlength="2" maxlength="100" value="<?=e($old['name']??'')?>" placeholder="Adınız ve soyadınız"></label><label><?=e(ui('form_email'))?> <span>*</span><input type="email" name="email" autocomplete="email" required maxlength="190" value="<?=e($old['email']??'')?>" placeholder="ornek@eposta.com"></label><label><?=e(ui('form_phone'))?><input type="tel" name="phone" autocomplete="tel" maxlength="30" value="<?=e($old['phone']??'')?>" placeholder="05__ ___ __ __"></label><label><?=e(ui('form_subject'))?> <span>*</span><select name="subject" required><option value=""><?=e(ui('form_choose'))?></option><?php $subjects=array_merge(array_column(entries('practice'),'title'),[ui('contact_other_subject')]);foreach($subjects as $s):?><option <?=($old['subject']??'')===$s?'selected':''?>><?=e($s)?></option><?php endforeach;?></select></label></div><label><?=e(ui('form_message'))?> <span>*</span><textarea name="message" rows="5" minlength="15" maxlength="5000" required placeholder="Görüşmek istediğiniz konuyu kısaca paylaşın."><?=e($old['message']??'')?></textarea></label><div class="honeypot" aria-hidden="true"><label>Web siteniz<input name="website" tabindex="-1" autocomplete="off"></label></div><p class="form-privacy"><a href="/sayfa/kvkk" target="_blank" rel="noopener"><?=e(ui('contact_notice_agreement'))?></a></p><button class="button" type="submit"><?=e(ui('contact_form_button'))?> <?=icon('arrow')?></button><p class="form-note"><?=e(ui('contact_form_note'))?></p></form><?php else:?><p><?=e(ui('contact_form_closed'))?></p><?php endif;?></div></div></section>
<?php elseif($detail&&$type==='article'):
    $cat=$detail['category']?:'Genel';$parsed=prose_with_toc($detail);$others=array_values(array_filter(entries('article'),fn($a)=>$a['id']!==$detail['id']));
    $recent=array_slice($others,0,6);$same=array_slice(array_values(array_filter($others,fn($a)=>($a['category']?:'Genel')===$cat)),0,5);$updated=$detail['updated_at']?substr((string)$detail['updated_at'],0,19):$detail['published_at'];
?><section class="post-section"><div class="container"><?php simple_breadcrumb([ui('hub_title')=>'/makaleler',$cat=>category_url($cat),$detail['title']=>'']);?><div class="post-layout <?=$parsed['toc']?'':'post-layout-no-toc'?>"><?php if($parsed['toc']):?><aside class="post-toc"><?php toc_html($parsed['toc'],'toc-sticky');?></aside><?php endif;?><article class="post-main"><header class="post-header"><h1 class="post-title"><?=e($detail['title'])?></h1><div class="post-meta"><?php $articleAuthor=author_of($detail);if($articleAuthor):?><a href="<?=e(entry_url($articleAuthor))?>" rel="author"><?=e($articleAuthor['title'])?></a><?php else:?><span><?=e($detail['author']?:setting('brand'))?></span><?php endif;?><a href="<?=e(category_url($cat))?>"><?=e($cat)?></a><span><?=e(ui('updated_label'))?>: <time datetime="<?=e(substr($updated,0,10))?>"><?=date_tr($updated)?></time></span><span><?=read_minutes($detail['body'])?> <?=e(ui('reading_suffix'))?></span></div></header><?php if($detail['image']):?><img class="post-cover" src="<?=e(safe_image($detail['image']))?>" alt="<?=e($detail['title'])?>" width="800" height="400" fetchpriority="high"><?php endif;if($detail['excerpt']):?><p class="post-lead"><?=e($detail['excerpt'])?></p><?php endif;?><div class="prose"><?=$parsed['html']?></div><div class="post-footer"><div class="post-disclaimer"><?=rich_text(setting('legal_notice'))?></div><div class="post-actions"><a class="text-link" href="<?=e(category_url($cat))?>">← <?=e($cat)?> <?=e(ui('category_posts_suffix'))?></a><button class="text-link copy-link" type="button"><?=e(ui('copy_link'))?> <?=icon('arrow-up')?></button><button class="text-link print-button" type="button"><?=e(ui('print'))?></button><span class="copy-status" role="status"></span></div></div><?php author_card($detail,true);?></article><aside class="post-side"><?php author_card($detail);side_list(ui('recent_posts'),$recent);side_list(ui('same_category'),$same);?></aside></div><?php if($recent):?><section class="post-recent" aria-labelledby="post-recent-title"><h2 class="side-title" id="post-recent-title"><?=e(ui('recent_posts'))?></h2><div class="post-grid post-grid-3"><?php foreach(array_slice($recent,0,3) as $a)post_card($a,true);?></div></section><?php endif;?></div></section>
<?php elseif($detail):
    $label=match($type){'article'=>$detail['category']?:'HUKUKİ YAYINLAR','practice'=>'ÇALIŞMA ALANLARI',default=>$detail['title']};
    $crumbs=match($type){'article'=>['Makaleler'=>'/makaleler'],'practice'=>['Çalışma Alanları'=>'/calisma-alanlari'],default=>[]};
    page_heading($label,$detail['title'],$detail['excerpt'],$crumbs);
?><section class="section"><div class="container detail-layout"><article class="detail-main"><?php if($type==='article'):?><div class="detail-meta"><span><?=icon('file')?><?=e($detail['author'])?></span><time datetime="<?=e(substr($detail['published_at'],0,10))?>"><?=date_tr($detail['published_at'])?></time><span><?=read_minutes($detail['body'])?> <?=e(ui('reading_suffix'))?></span><button class="text-link print-button" type="button"><?=e(ui('print'))?></button></div><?php endif;if($detail['image']):?><img class="detail-cover" src="<?=e(safe_image($detail['image']))?>" alt="<?=e($detail['title'])?>" width="900" height="520"><?php endif;?><div class="prose"><?=content_html($detail)?></div><?php if($type==='article'):?><div class="article-disclaimer"><?=icon('book')?><p><?=e(setting('legal_notice'))?></p></div><div class="article-end"><a class="text-link" href="/makaleler">← <?=e(ui('all_articles'))?></a><button class="text-link copy-link" type="button"><?=e(ui('copy_link'))?> <?=icon('arrow-up')?></button><span class="copy-status" role="status"></span></div><?php endif;?></article><aside class="detail-sidebar"><div class="sidebar-block"><h2><?=$type==='article'?'Diğer yayınlar':'Çalışma Alanlarımız'?></h2><?php foreach(array_slice(entries($type==='article'?'article':'practice'),0,6) as $p):if($p['id']===$detail['id'])continue;?><a class="related-link" href="<?=e(entry_url($p))?>"><?=e($p['title'])?> <?=icon('chevron')?></a><?php endforeach;?></div><div class="sidebar-contact"><?=icon('scale')?><h2><?=nl2br(e(ui('sidebar_title')))?></h2><p><?=e(ui('sidebar_text'))?></p><a class="text-link" href="/iletisim"><?=e(setting('contact_button'))?> <?=icon('arrow')?></a></div></aside></div></section><?php contact_band();?>
<?php endif;?></main><?php footer_html();
