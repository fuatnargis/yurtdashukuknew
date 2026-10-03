<?php
// One-time palette migration: align bundled defaults with the KK Hukuk-style deep navy identity.
// Only replaces the bundled palette values; any administrator customization stays untouched.
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
$db=db();
$q=$db->prepare('SELECT value FROM settings WHERE key=?');
$q->execute(['private_kk_palette_version']);
if((int)$q->fetchColumn()>=1){echo "Zaten uygulanmis.\n";exit;}
$db->beginTransaction();
try{
    $replace=$db->prepare('UPDATE settings SET value=? WHERE key=? AND value=?');
    // Deeper, colder navy like kk hero artwork; warmer champagne accent; darker ink.
    foreach([
        'primary_color'=>['#102b46','#0c1a2b'],
        'action_color'=>['#20527a','#1d3a5f'],
        'accent_color'=>['#c9ad7a','#d8c29a'],
        'text_color'=>['#192b3b','#1c2430'],
        'muted_color'=>['#596b7a','#5a6674'],
        'header_text_color'=>['#102b46','#0c1a2b'],
        'footer_color'=>['#102b46','#0c1a2b'],
        'hero_overlay_color'=>['#102b46','#0c1a2b'],
        'dark_section_color'=>['#102b46','#0c1a2b'],
        'hero_height'=>['600','760'],
        'hero_overlay_opacity'=>['0','42'],
        'heading_font'=>['arial','sans'],
        'home_profile_eyebrow'=>['AVUKATIMIZ','KURUCU AVUKAT'],
        'home_profile_title'=>['Hukuki sürecinizde doğrudan iletişim.','Dosyalarınızı bizzat kurucu avukat yürütür.'],
        'home_profile_text'=>['Avukatımızın profilini ve yayımladığı yazıları inceleyebilirsiniz.','Yurtdaş Hukuk\'un kurucusu Av. Halil İbrahim Yurtdaş; her dosyayı baştan sona bizzat takip eder, görüşmelerinizi doğrudan kendisiyle yaparsınız.'],
    ] as $key=>[$old,$new])$replace->execute([$new,$key,$old]);
    $db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value')->execute(['private_kk_palette_version','1']);
    $db->commit();
    echo "Palet guncellendi.\n";
}catch(Throwable $e){$db->rollBack();throw $e;}
