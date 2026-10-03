<?php
declare(strict_types=1);
function migrate_reference_design(PDO $db): void {
    $q=$db->prepare('SELECT value FROM settings WHERE key=?');$q->execute(['private_design_version']);
    if((int)$q->fetchColumn()>=2)return;
    $db->beginTransaction();
    try {
        $settings=[
            'action_color'=>'#234e78','show_office'=>'1','show_approach'=>'1','show_mobile_contact'=>'1',
            'approach_eyebrow'=>'ÇALIŞMA ANLAYIŞIMIZ','approach_title'=>"Hukuki bilgi birikimi.\nİnsana odaklanan yaklaşım.",
            'approach_text'=>'Bir hukuki süreci anlamak, doğru soruları sormakla başlar. Konunuzu tüm yönleriyle ele alıyor; seçenekleri, sürecin aşamalarını ve ihtiyaç duyulan adımları açık bir dille değerlendiriyoruz.',
            'approach_image'=>'/assets/images/library.jpg','office_eyebrow'=>'BÜROMUZ','office_title'=>"Hukuki sürecinizi\nbirlikte değerlendirelim.",'office_text'=>'Bireysel ve kurumsal hukuki ihtiyaçlarınız için görüşme talebinizi iletebilir, çalışma alanlarımız ve görüşme sürecimiz hakkında bilgi alabilirsiniz.',
            'laws_title'=>'Kanunlar','laws_text'=>'Sık başvurulan kanunlara resmî kaynaklarından ulaşın.','laws_note'=>'Bağlantılar Mevzuat Bilgi Sistemi’ndeki metinleri yeni sekmede açar. Uygulanacak hüküm ve yürürlük bilgisi için resmî kaynaktaki açıklamaları inceleyin.',
            'category_directory_title'=>'Hukuki bilgiye, konusundan ulaşın.','category_directory_text'=>'İlgilendiğiniz alanı seçin; yazılarımıza ve değerlendirmelerimize göz atın.',
            'team_empty_title'=>'Mesleki özen. Ortak bir çalışma anlayışı.','team_empty_text'=>'Çalışma alanlarımız ve görüşme sürecimiz hakkında büromuzla iletişime geçebilirsiniz.',
        ];
        $insert=$db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO NOTHING');foreach($settings as $k=>$v)$insert->execute([$k,$v]);
        $replace=$db->prepare('UPDATE settings SET value=? WHERE key=? AND value=?');
        $replace->execute(["Avukatlık &\nHukuki Danışmanlık",'hero_title',"Hukukun rehberliğinde,\ngüvenle ileriye."]);
        $replace->execute(['Makaleler','text_archive_title',"Hukuku anlamak,\ndoğru adımla başlar."]);
        $insert->execute(['text_archive_title','Makaleler']);
        $rows=[
            ['law','Türk Medeni Kanunu','turk-medeni-kanunu','4721','Kişiler, aile, miras ve eşya hukukuna ilişkin kanun metni.','https://www.mevzuat.gov.tr/MevzuatMetin/1.5.4721.pdf',0],
            ['law','Türk Borçlar Kanunu','turk-borclar-kanunu','6098','Borç ilişkilerine ve sözleşmelere ilişkin kanun metni.','https://www.mevzuat.gov.tr/MevzuatMetin/1.5.6098.pdf',1],
            ['law','Türk Ceza Kanunu','turk-ceza-kanunu','5237','Ceza hukukuna ilişkin temel kanun metni.','https://www.mevzuat.gov.tr/MevzuatMetin/1.5.5237.pdf',2],
            ['law','İş Kanunu','is-kanunu','4857','Çalışma ilişkilerine ilişkin kanun metni.','https://www.mevzuat.gov.tr/MevzuatMetin/1.5.4857.pdf',3],
            ['menu','Kanunlar','kanunlar','','','/kanunlar',3],
        ];
        $insertEntry=$db->prepare("INSERT INTO entries(type,title,slug,category,excerpt,link,sort_order,status,published_at,updated_at) VALUES(?,?,?,?,?,?,?,'published',?,?) ON CONFLICT(type,slug) DO NOTHING");
        foreach($rows as $r){$r[]=date('Y-m-d H:i:s');$r[]=date('Y-m-d H:i:s');$insertEntry->execute($r);}
        $db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value')->execute(['private_design_version','2']);
        $db->commit();
    } catch(Throwable $e){$db->rollBack();throw $e;}
}
function migrate_minimal_redesign(PDO $db): void {
    $q=$db->prepare('SELECT value FROM settings WHERE key=?');$q->execute(['private_design_version']);
    if((int)$q->fetchColumn()>=4)return;
    $db->beginTransaction();
    try {
        $settings=[
            'payment_enabled'=>'1','payment_label'=>'Online Ödeme','payment_url'=>'https://pos.mokaunited.com/CustomerPos/PaymentRequest?uppc=EJc8WsUNcxQ9C4Q0HHKh9w%3d%3d',
        ];
        $insert=$db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO NOTHING');foreach($settings as $k=>$v)$insert->execute([$k,$v]);
        $db->prepare("UPDATE entries SET title='Hakkımızda' WHERE type='menu' AND link='/kurumsal'")->execute();
        $db->prepare("UPDATE entries SET title='Hakkımızda' WHERE type='page' AND slug='kurumsal'")->execute();
        $db->prepare("DELETE FROM entries WHERE type='menu' AND link IN('/kanunlar','/ekibimiz')")->execute();
        $db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value')->execute(['private_design_version','4']);
        $db->commit();
    } catch(Throwable $e){$db->rollBack();throw $e;}
}
