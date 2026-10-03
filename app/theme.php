<?php
declare(strict_types=1);

// One schema for defaults, safe CSS output and the administration controls.
const THEME_COLORS = [
    'primary_color'=>['Ana kurumsal renk','navy','#102b46'],
    'accent_color'=>['Vurgu ve simge rengi','gold','#c9ad7a'],
    'action_color'=>['Buton ve bağlantı rengi','action','#20527a'],
    'button_text_color'=>['Buton yazısı','button-text','#ffffff'],
    'background_color'=>['Sayfa arka planı','background','#f4f6f8'],
    'surface_color'=>['Kart ve form zemini','surface','#ffffff'],
    'text_color'=>['Ana metin','ink','#192b3b'],
    'muted_color'=>['İkincil metin','muted','#596b7a'],
    'border_color'=>['Çizgi ve kenarlık','line','#dbe3ea'],
    'header_color'=>['Üst menü zemini','header-bg','#ffffff'],
    'header_text_color'=>['Üst menü yazısı','header-text','#102b46'],
    'topbar_color'=>['Üst iletişim şeridi zemini','topbar-bg','#eef3f0'],
    'topbar_text_color'=>['Üst iletişim şeridi yazısı','topbar-text','#253238'],
    'footer_color'=>['Alt bilgi zemini','footer-bg','#102b46'],
    'footer_text_color'=>['Alt bilgi yazısı','footer-text','#eef2f5'],
    'hero_overlay_color'=>['Ana görsel üzerindeki gölge','hero-overlay','#102b46'],
    'hero_text_color'=>['Ana görsel üzerindeki yazı','hero-text','#ffffff'],
    'dark_section_color'=>['Koyu bölüm zemini','dark-bg','#102b46'],
    'dark_section_text_color'=>['Koyu bölüm yazısı','dark-text','#ffffff'],
];
const HOME_SECTIONS = ['home_profile'=>'Avukat profili','home_consultation'=>'Görüşme süreci','principles'=>'İlkeler','intro'=>'Tanıtım','approach'=>'Çalışma anlayışı','office'=>'Büro bilgileri','practices'=>'Çalışma alanları','articles'=>'Makaleler','faq'=>'Sık sorulan sorular','contact'=>'İletişim bandı','home_map'=>'Büro konumu'];
const THEME_OPTIONS = [
    'heading_font'=>['arial'=>'Arial','classic'=>'Klasik başlıklar ve sade metinler','sans'=>'Manrope','serif'=>'Cormorant Garamond'],
    'body_font'=>['arial'=>'Arial','system'=>'Sistem yazı tipi','sans'=>'Manrope'],
    'page_transition'=>['signature'=>'Logo ve yumuşak geçiş','fade'=>'Yalnızca yumuşak geçiş','off'=>'Kapalı'],
    'parallax_effect'=>['gentle'=>'Yumuşak derinlik','off'=>'Kapalı'],
    'loading_animation'=>['signature'=>'Yurtdaş logosuyla açılış','off'=>'Kapalı'],
    'header_layout'=>['centered'=>'Ortalanmış logo ve iki katlı menü','inline'=>'Tek satır logo ve menü'],
    'home_articles_layout'=>['editorial'=>'Öne çıkan yazı ve son yazılar','grid'=>'Eşit boyutlu kartlar'],
];
const THEME_NUMBERS = ['hero_overlay_opacity'=>[0,90,0],'hero_height'=>[400,950,600],'card_radius'=>[0,32,8],'home_practice_count'=>[1,24,6],'home_article_count'=>[1,12,6],'home_press_count'=>[1,12,3]];
const SITE_CONTROL_NUMBERS = [
    'body_font_size'=>[14,20,16],'section_heading_size'=>[24,44,34],
    'hero_title_size'=>[32,104,80],'mobile_hero_title_size'=>[28,56,44],
    'nav_font_size'=>[12,16,14],'section_spacing'=>[32,96,64],
    'mobile_section_spacing'=>[24,64,40],'mobile_hero_height'=>[320,560,420],
    'logo_width'=>[180,300,244],
];
const SITE_CONTROL_DEFAULTS = [
    'body_font'=>'arial','page_transition'=>'signature','parallax_effect'=>'gentle','loading_animation'=>'signature','show_search'=>'1',
    'show_breadcrumbs'=>'1','show_reading_progress'=>'1','show_back_top'=>'1',
    'show_section_labels'=>'1','show_mobile_practice_text'=>'0',
    'show_footer_practices'=>'1','show_footer_discover'=>'1','show_footer_contact'=>'1',
    'corporate_show_image'=>'1','corporate_show_principles'=>'1',
];
function site_control_defaults(): array {
    return SITE_CONTROL_DEFAULTS+array_map(fn($row)=>(string)$row[2],SITE_CONTROL_NUMBERS);
}
function theme_css(): string {
    $css='';foreach(THEME_COLORS as $key=>[$label,$variable,$default]){
        $value=setting($key,$default);if(!preg_match('/^#[a-f0-9]{6}$/i',$value))$value=$default;
        $css.='--'.$variable.':'.$value.';';
    }
    foreach(['hero_overlay_opacity'=>'hero-opacity','hero_height'=>'hero-height','card_radius'=>'radius'] as $key=>$variable){
        [$min,$max,$default]=THEME_NUMBERS[$key];$v=max($min,min($max,(int)setting($key,(string)$default)));
        $css.='--'.$variable.':'.($key==='hero_overlay_opacity'?$v/100:$v.'px').';';
    }
    foreach(SITE_CONTROL_NUMBERS as $key=>[$min,$max,$default]){
        $css.='--'.str_replace('_','-',$key).':'.max($min,min($max,(int)setting($key,(string)$default))).'px;';
    }
    $font=match(setting('body_font','arial')){
        'system'=>'system-ui,-apple-system,"Segoe UI",Arial,sans-serif',
        'sans'=>'Manrope,Arial,sans-serif',
        default=>'Arial,Helvetica,sans-serif',
    };
    $heading=match(setting('heading_font','arial')){
        'classic'=>'Georgia,"Times New Roman",serif',
        'sans'=>'Manrope,Arial,sans-serif',
        'serif'=>'"Cormorant Garamond",Georgia,serif',
        default=>'Arial,Helvetica,sans-serif',
    };
    $css.='--sans:'.$font.';--serif:'.$heading.';--heading:'.$heading.';';
    return ':root{'.$css.'--navy-deep:color-mix(in srgb,var(--navy) 85%,#000);--paper:var(--background);--ink-soft:var(--muted);--line-soft:var(--line)}';
}
function migrate_site_controls(PDO $db): void {
    $check=$db->prepare('SELECT value FROM settings WHERE key=?');$check->execute(['private_site_controls_version']);
    if((int)$check->fetchColumn()>=2)return;
    $insert=$db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO NOTHING');
    foreach(site_control_defaults() as $key=>$value)$insert->execute([$key,$value]);
    foreach(['topbar_color'=>'#eef3f0','topbar_text_color'=>'#253238'] as $key=>$value)$insert->execute([$key,$value]);
    $db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value')->execute(['private_site_controls_version','2']);
}
function home_sections(): array {
    $order=array_filter(array_map('trim',explode(',',setting('home_section_order',implode(',',array_keys(HOME_SECTIONS))))));
    // Older installations kept these sections outside the sortable list.
    if(!in_array('home_consultation',$order,true))array_unshift($order,'home_consultation');
    if(!in_array('home_profile',$order,true))array_unshift($order,'home_profile');
    return array_values(array_unique(array_merge(array_intersect($order,array_keys(HOME_SECTIONS)),array_keys(HOME_SECTIONS))));
}
function migrate_cinar_theme(PDO $db): void {
    $q=$db->prepare('SELECT value FROM settings WHERE key=?');$q->execute(['private_cinar_theme_version']);$version=(int)$q->fetchColumn();if($version>=2)return;
    $db->beginTransaction();
    try {
        $defaults=['heading_font'=>'serif','header_layout'=>'inline','home_articles_layout'=>'editorial','home_section_order'=>implode(',',array_keys(HOME_SECTIONS)),'show_principles'=>'1','show_press'=>'1','show_topbar'=>'0','show_header_contact'=>'1','show_practice_dropdown'=>'1','show_floating_contact'=>'1','hero_image_alt'=>'Adalet ve hukuk temalı ana görsel','intro_image_alt'=>'Hukuk bürosu tanıtımı','approach_image_alt'=>'Hukuk kütüphanesi','contact_band_image'=>'/assets/images/architecture.jpg','header_contact_url'=>'/iletisim','intro_link_url'=>'/kurumsal','contact_button_url'=>'/iletisim','seo_service_area'=>'Türkiye','seo_postal_code'=>'','geo_summary'=>'','geo_note'=>'Bu sitedeki yayınlar genel bilgilendirme amaçlıdır. Somut olay için hukuki değerlendirme gerekir.'];
        foreach(THEME_COLORS as $key=>$row)$defaults[$key]=$row[2];
        foreach(THEME_NUMBERS as $key=>$row)$defaults[$key]=(string)$row[2];
        $insert=$db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO NOTHING');foreach($defaults as $k=>$v)$insert->execute([$k,$v]);
        // Only replace the previous bundled palette. Preserve custom brand colors.
        $update=$db->prepare('UPDATE settings SET value=? WHERE key=? AND value=?');
        if($version<1)foreach(['primary_color'=>['#142b36','#14110f'],'accent_color'=>['#b59463','#b48348'],'action_color'=>['#234e78','#856821']] as $k=>[$old,$new])$update->execute([$new,$k,$old]);
        $menu=$db->prepare("INSERT INTO entries(type,title,slug,link,sort_order,status,published_at,updated_at) SELECT 'menu',?,?,?,?, 'published',?,? WHERE NOT EXISTS(SELECT 1 FROM entries WHERE type='menu' AND link=?) ON CONFLICT(type,slug) DO NOTHING");
        foreach([['Sıkça Sorulan Sorular','sikca-sorulan-sorular','/sikca-sorulan-sorular',8]] as [$title,$slug,$url,$order])$menu->execute([$title,$slug,$url,$order,date('Y-m-d H:i:s'),date('Y-m-d H:i:s'),$url]);
        $db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value')->execute(['private_cinar_theme_version','2']);$db->commit();
    }catch(Throwable $e){$db->rollBack();throw $e;}
}
function migrate_editorial_theme(PDO $db): void {
    $q=$db->prepare('SELECT value FROM settings WHERE key=?');$q->execute(['private_editorial_theme_version']);
    if((int)$q->fetchColumn()>=1)return;
    $db->beginTransaction();
    try {
        // Change bundled defaults only. An administrator's different palette stays intact.
        $replace=$db->prepare('UPDATE settings SET value=? WHERE key=? AND value=?');
        foreach([
            'primary_color'=>['#14110f','#19342f'],'accent_color'=>['#b48348','#c5a87a'],
            'action_color'=>['#856821','#735639'],'background_color'=>['#f7f5f2','#f5f3ee'],
            'text_color'=>['#14110f','#22332e'],'muted_color'=>['#6b665f','#596b64'],
            'border_color'=>['#e8e4df','#d9ded6'],'header_text_color'=>['#14110f','#19342f'],
            'footer_color'=>['#14110f','#19342f'],'footer_text_color'=>['#eee9e2','#eeeae1'],
            'hero_overlay_color'=>['#14110f','#19342f'],'dark_section_color'=>['#14110f','#19342f'],
            'heading_font'=>['sans','serif'],'header_layout'=>['centered','inline'],
        ] as $key=>[$old,$new])$replace->execute([$new,$key,$old]);
        $db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value')->execute(['private_editorial_theme_version','1']);
        $db->commit();
    }catch(Throwable $e){$db->rollBack();throw $e;}
}
function migrate_juris_home(PDO $db): void {
    $q=$db->prepare('SELECT value FROM settings WHERE key=?');$q->execute(['private_juris_home_version']);
    if((int)$q->fetchColumn()>=1)return;
    $db->beginTransaction();
    try {
        $db->prepare('UPDATE settings SET value=? WHERE key=? AND value=?')->execute([
            'office,intro,principles,practices,articles,press,faq,approach,contact',
            'home_section_order',implode(',',array_keys(HOME_SECTIONS)),
        ]);
        $db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value')->execute(['private_juris_home_version','1']);
        $db->commit();
    }catch(Throwable $e){$db->rollBack();throw $e;}
}
function migrate_navy_palette(PDO $db): void {
    $q=$db->prepare('SELECT value FROM settings WHERE key=?');$q->execute(['private_navy_palette_version']);
    if((int)$q->fetchColumn()>=1)return;
    $db->beginTransaction();
    try {
        // Replace the bundled green palette. Leave any administrator customizations intact.
        $replace=$db->prepare('UPDATE settings SET value=? WHERE key=? AND value=?');
        foreach([
            'primary_color'=>['#19342f','#102b46'],'accent_color'=>['#c5a87a','#c9ad7a'],
            'action_color'=>['#735639','#20527a'],'background_color'=>['#f5f3ee','#f4f6f8'],
            'text_color'=>['#22332e','#192b3b'],'muted_color'=>['#596b64','#596b7a'],
            'border_color'=>['#d9ded6','#dbe3ea'],'header_text_color'=>['#19342f','#102b46'],
            'footer_color'=>['#19342f','#102b46'],'footer_text_color'=>['#eeeae1','#eef2f5'],
            'hero_overlay_color'=>['#19342f','#102b46'],'dark_section_color'=>['#19342f','#102b46'],
            'hero_overlay_opacity'=>['55','68'],
        ] as $key=>[$old,$new])$replace->execute([$new,$key,$old]);
        $db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value')->execute(['private_navy_palette_version','1']);
        $db->commit();
    }catch(Throwable $e){$db->rollBack();throw $e;}
}
function migrate_arial_font(PDO $db): void {
    $q=$db->prepare('SELECT value FROM settings WHERE key=?');$q->execute(['private_arial_font_version']);
    if((int)$q->fetchColumn()>=1)return;
    $db->beginTransaction();
    try {
        $db->prepare("UPDATE settings SET value='arial' WHERE key='heading_font' AND value IN ('sans','serif')")->execute();
        $db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value')->execute(['private_arial_font_version','1']);
        $db->commit();
    }catch(Throwable $e){$db->rollBack();throw $e;}
}
function migrate_remove_press(PDO $db): void {
    $q=$db->prepare('SELECT value FROM settings WHERE key=?');$q->execute(['private_remove_press_version']);
    if((int)$q->fetchColumn()>=1)return;
    $db->beginTransaction();
    try {
        // "Basında Biz" removed from the site: archive its entries and unlink every reference.
        $db->exec("UPDATE entries SET status='archived' WHERE type='press'");
        $db->exec("DELETE FROM entries WHERE type='menu' AND (link='/basinda-biz' OR slug='basinda-biz')");
        $db->exec("DELETE FROM settings WHERE key IN ('home_press_count','show_press','meta_basinda-biz_title','meta_basinda-biz_description','meta_basinda-biz_noindex')");
        $db->exec("UPDATE settings SET value=REPLACE(value,char(10)||'Basında Biz|/basinda-biz','') WHERE key='footer_extra_links'");
        $db->exec("UPDATE settings SET value=REPLACE(value,'Basında Biz|/basinda-biz'||char(10),'') WHERE key='footer_extra_links'");
        $db->prepare("UPDATE settings SET value=? WHERE key='home_section_order' AND value LIKE '%press%'")->execute([implode(',',array_keys(HOME_SECTIONS))]);
        $db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value')->execute(['private_remove_press_version','1']);
        $db->commit();
    }catch(Throwable $e){$db->rollBack();throw $e;}
}
function migrate_natural_hero(PDO $db): void {
    $q=$db->prepare('SELECT value FROM settings WHERE key=?');$q->execute(['private_natural_hero_version']);
    if((int)$q->fetchColumn()>=1)return;
    $db->beginTransaction();
    try {
        // Remove the bundled blue tint once; later admin changes remain editable.
        $db->prepare("UPDATE settings SET value='0' WHERE key='hero_overlay_opacity' AND value IN ('55','68')")->execute();
        $db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value')->execute(['private_natural_hero_version','1']);
        $db->commit();
    }catch(Throwable $e){$db->rollBack();throw $e;}
}
