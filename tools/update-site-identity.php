<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/bootstrap.php';
$db=db();$key='private_identity_update_20261003';
if(setting($key)==='1'){echo "Görsel kimlik güncellemesi zaten uygulandı.\n";exit;}
$db->beginTransaction();
try {
    $backup=['format'=>'mizan-cms','version'=>1,'exported_at'=>date(DATE_ATOM),
        'settings'=>$db->query("SELECT key,value FROM settings WHERE key NOT LIKE 'private_%'")->fetchAll(PDO::FETCH_KEY_PAIR),
        'entries'=>$db->query('SELECT * FROM entries ORDER BY id')->fetchAll(),
        'redirects'=>$db->query('SELECT r.type,r.slug,e.slug AS target_slug FROM entry_redirects r JOIN entries e ON e.id=r.entry_id')->fetchAll(),
        'url_redirects'=>$db->query('SELECT source_path,target_path,status_code,enabled FROM url_redirects')->fetchAll(),
        'seo_urls'=>$db->query('SELECT path,sitemap_enabled,noindex,lastmod FROM seo_urls')->fetchAll()];
    persist_content_backup($backup);
    $values=['intro_image'=>'/assets/images/law-about-study-v1.webp','intro_image_alt'=>'Açık hukuk kitabı ve pirinç terazi içeren temsili çalışma masası','hero_title_size'=>'80','mobile_hero_title_size'=>'44','hero_text_color'=>'#172c35','district'=>'Antakya','sitemap_enabled'=>'1','seo_service_area'=>'Antakya, Hatay','seo_title'=>PUBLIC_SITE_COPY['seo_title'],'seo_description'=>PUBLIC_SITE_COPY['seo_description'],'geo_summary'=>PUBLIC_SITE_COPY['geo_summary']];
    foreach($values as $name=>$value)save_setting($name,$value);
    save_setting($key,'1');audit('Yeni Yurtdaş kimliği, okunaklı başlık ve Antakya büro bilgileri uygulandı');$db->commit();echo "İçerik yedeği alındı; başlık, farklı büro görseli ve yerel büro bilgileri güncellendi.\n";
}catch(Throwable $error){$db->rollBack();throw $error;}
