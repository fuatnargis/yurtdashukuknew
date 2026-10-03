<?php
declare(strict_types=1);

const PROFILE_FIELDS=['baro','baro_number','tbb_number','started','university','languages','kep'];
function profile_details(array $profile): array {
    $data=json_decode((string)($profile['profile_details']??''),true);
    return is_array($data)?array_intersect_key($data,array_flip(PROFILE_FIELDS)):[];
}
function profile_details_input(array $source): string {
    $values=[];
    foreach(PROFILE_FIELDS as $key){
        $value=scalar_input($source,$key);
        if(mb_strlen($value)>250)throw new RuntimeException('Mesleki bilgi alanları en fazla 250 karakter olabilir.');
        if($key==='kep'&&$value!==''&&!filter_var($value,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Geçerli bir KEP adresi yazın.');
        if($key==='started'&&$value!==''){
            $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
            if(!$date||$date->format('Y-m-d')!==$value||$value>date('Y-m-d'))throw new RuntimeException('Mesleğe başlama tarihi geçerli ve geçmiş bir tarih olmalıdır.');
        }
        if($value!=='')$values[$key]=$value;
    }
    return json_encode($values,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}
function privacy_notice_ready(): bool {
    $notice=entry('page','kvkk');
    return $notice&&hash_equals(setting('private_privacy_notice_hash'),hash('sha256',$notice['body']));
}
function contact_form_available(): bool {return setting('contact_enabled')==='1'&&privacy_notice_ready();}

function migrate_owner_profile(PDO $db): void {
    $q=$db->prepare('SELECT value FROM settings WHERE key=?');
    $q->execute(['private_owner_profile_version']);
    $version=(int)$q->fetchColumn();
    if($version>=5)return;
    $db->beginTransaction();
    try {
        if($version<1){
        $identity=[
            'city'=>'Hatay',
            'address'=>'A Plaza, Antakya, Küçükdalyan, Antakya/Hatay',
            'phone'=>'0530 855 25 22',
            'whatsapp'=>'905308552522',
            'map_query'=>'',
            'show_home_profile'=>'1',
            'show_home_map'=>'1',
        ];
        $save=$db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value');
        foreach($identity as $key=>$value)$save->execute([$key,$value]);
        $name='Halil İbrahim Yurtdaş';
        $now=date('Y-m-d H:i:s');
        $db->prepare("INSERT INTO entries(type,title,slug,excerpt,body,image,category,author,status,published_at,sort_order,meta_title,meta_description,link,updated_at) VALUES('team',?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON CONFLICT(type,slug) DO NOTHING")
            ->execute([$name,'halil-ibrahim-yurtdas','Yurtdaş Hukuk bünyesinde avukatlık ve hukuki danışmanlık hizmeti sunar.',
                'Halil İbrahim Yurtdaş, Yurtdaş Hukuk bünyesinde avukatlık ve hukuki danışmanlık hizmeti sunar. Hukuki süreçlerde başvurunun koşullarını, ilgili belgeleri ve izlenebilecek adımları özenle değerlendirir.',
                '','Avukat','','published',$now,0,'Av. Halil İbrahim Yurtdaş | Yurtdaş Hukuk','Av. Halil İbrahim Yurtdaş hakkında bilgiler, hukuki yayınları ve iletişim kanalları.','',$now]);
        $brand=$db->query("SELECT value FROM settings WHERE key='brand'")->fetchColumn()?:'Yurtdaş Hukuk';
        $db->prepare("UPDATE entries SET author=? WHERE type='article' AND author IN (?, 'Mizan Hukuk')")->execute([$name,$brand]);
        }
        if($version<2)$db->prepare("UPDATE settings SET value='' WHERE key='map_query' AND value='A Plaza, Küçükdalyan, Antakya, Hatay'")->execute();
        if($version<3){
            $defaults=[
                'brand'=>['Mizan Hukuk','Yurtdaş Hukuk'],
                'brand_initial'=>['M','Y'],
                'seo_title'=>['Mizan Hukuk | Avukatlık ve Hukuki Danışmanlık','Yurtdaş Hukuk | Avukatlık ve Hukuki Danışmanlık'],
                'seo_description'=>['Mizan Hukuk; bireyler ve kurumlar için avukatlık ve hukuki danışmanlık. Çalışma alanlarımızı ve hukuki yayınlarımızı inceleyin.','Yurtdaş Hukuk; bireyler ve kurumlar için avukatlık ve hukuki danışmanlık. Çalışma alanlarımızı ve hukuki yayınlarımızı inceleyin.'],
                'hero_eyebrow'=>['MİZAN HUKUK & DANIŞMANLIK','YURTDAŞ HUKUK & DANIŞMANLIK'],
                'intro_label'=>['Mizan Hukuk','Yurtdaş Hukuk'],
            ];
            $replace=$db->prepare('UPDATE settings SET value=? WHERE key=? AND value=?');
            foreach($defaults as $key=>[$before,$after])$replace->execute([$after,$key,$before]);
            $db->prepare("UPDATE entries SET author='Yurtdaş Hukuk' WHERE author='Mizan Hukuk' AND type<>'article'")->execute();
        }
        if($version<4){
            $homeText=[
                'home_profile_eyebrow'=>'AVUKATIMIZ',
                'home_profile_title'=>'Hukuki sürecinizde doğrudan iletişim.',
                'home_profile_text'=>'Avukatımızın profilini ve yayımladığı yazıları inceleyebilirsiniz.',
                'home_map_eyebrow'=>'BÜROMUZA ULAŞIN',
                'home_map_title'=>'Antakya’daki büromuz.',
            ];
            $insert=$db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO NOTHING');
            foreach($homeText as $key=>$value)$insert->execute([$key,$value]);
        }
        if($version<5){
            $consultation=[
                'show_home_consultation'=>'1',
                'consultation_eyebrow'=>'GÖRÜŞME SÜRECİ',
                'consultation_title'=>'İlk görüşmeden yol haritasına.',
                'consultation_text'=>'Hukuki meseleyi anlamak için önce sizi dinler, belgeleri ve öncelikleri değerlendiririz. Sonraki adımlar somut dosyanın koşullarına göre belirlenir.',
                'consultation_step_1_title'=>'İlk temas',
                'consultation_step_1_text'=>'Başvurunuzun konusunu ve zaman bakımından önemli noktaları dinleriz.',
                'consultation_step_2_title'=>'Belge ve kapsam',
                'consultation_step_2_text'=>'Paylaştığınız bilgi ve belgeleri meselenin kapsamı içinde inceleriz.',
                'consultation_step_3_title'=>'Sonraki adımlar',
                'consultation_step_3_text'=>'Olası hukuki yolları, gerekli hazırlıkları ve çalışma kapsamını açıkça konuşuruz.',
            ];
            $insert=$db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO NOTHING');
            foreach($consultation as $key=>$value)$insert->execute([$key,$value]);
        }
        $db->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value')->execute(['private_owner_profile_version','5']);
        $db->commit();
    }catch(Throwable $e){$db->rollBack();throw $e;}
}

function phone_url(string $phone): string {
    return 'tel:'.preg_replace('/[^+0-9]/','',$phone);
}
function whatsapp_url(string $phone): string {
    $digits=preg_replace('/\D/','',$phone);
    if(strlen($digits)===11&&str_starts_with($digits,'0'))$digits='90'.substr($digits,1);
    elseif(strlen($digits)===10&&str_starts_with($digits,'5'))$digits='90'.$digits;
    return 'https://wa.me/'.$digits;
}
function map_query(): string {
    return setting('map_query')?:setting('address');
}
function map_open_url(): string {
    return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(map_query());
}
function map_embed_url(): string {
    return 'https://maps.google.com/maps?q='.rawurlencode(map_query()).'&z=16&output=embed';
}
function profile_name_key(string $name): string {
    return mb_strtolower(trim(preg_replace('/^(av\.|avukat)\s+/iu','',$name)),'UTF-8');
}
function profile_initials(string $name): string {
    $parts=preg_split('/\s+/u',trim($name));
    return mb_strtoupper(mb_substr($parts[0]??'',0,1).mb_substr($parts[count($parts)-1]??'',0,1),'UTF-8');
}
function profile_articles(array $profile): array {
    $key=profile_name_key($profile['title']);
    return array_values(array_filter(entries('article'),fn($article)=>profile_name_key($article['author'])===$key));
}
function profile_tile(array $profile): void { ?>
    <a class="lawyer-tile" href="<?=e(entry_url($profile))?>">
        <span class="lawyer-tile-photo"><?php if($profile['image']):?><img src="<?=e(safe_image($profile['image']))?>" alt="<?=e($profile['title'])?>" width="440" height="520" loading="lazy"><?php else:?><span class="lawyer-monogram" aria-hidden="true"><?=e(profile_initials($profile['title']))?></span><?php endif;?></span>
        <span class="lawyer-tile-copy"><small><?=e($profile['category']?:'Avukat')?></small><strong><?=e($profile['title'])?></strong><span><?=e($profile['excerpt'])?></span><span class="lawyer-tile-link"><?=e(ui('profile_link'))?> <?=icon('arrow-up')?></span></span>
    </a>
<?php }
function home_profile(): void {
    if(setting('show_home_profile')!=='1'||!($profiles=entries('team')))return; ?>
    <section class="home-lawyer-section" data-home-section="home_profile" aria-labelledby="home-lawyer-title"><div class="container home-lawyer-inner"><div class="home-lawyer-heading"><?php if(setting('home_profile_eyebrow')!==setting('home_profile_title')):?><span class="eyebrow"><?=e(setting('home_profile_eyebrow'))?></span><?php endif;?><h2 id="home-lawyer-title"><?=e(setting('home_profile_title'))?></h2><p><?=e(setting('home_profile_text'))?></p></div><?php profile_tile($profiles[0]);?></div></section>
<?php }
function home_consultation(): void {
    if(setting('show_home_consultation')!=='1')return; ?>
    <section class="home-consultation" data-home-section="home_consultation" aria-labelledby="consultation-title"><div class="container home-consultation-inner"><div class="home-consultation-heading"><span class="eyebrow"><?=e(setting('consultation_eyebrow'))?></span><h2 id="consultation-title"><?=e(setting('consultation_title'))?></h2><p><?=e(setting('consultation_text'))?></p><a class="text-link" href="/iletisim"><?=e(setting('contact_button'))?> <?=icon('arrow')?></a></div><ol class="home-consultation-steps"><?php for($i=1;$i<=3;$i++):?><li><span class="home-consultation-number"><?=sprintf('%02d',$i)?></span><div><h3><?=e(setting('consultation_step_'.$i.'_title'))?></h3><p><?=e(setting('consultation_step_'.$i.'_text'))?></p></div></li><?php endfor;?></ol></div></section>
<?php }
function home_map(): void {
    if(setting('show_home_map')!=='1'||!setting('address'))return; ?>
    <section class="home-map-section" data-home-section="home_map" aria-labelledby="home-map-title"><div class="container home-map-inner"><div class="home-map-heading"><span class="eyebrow"><?=e(setting('home_map_eyebrow'))?></span><h2 id="home-map-title"><span class="desktop-copy"><?=e(setting('home_map_title'))?></span><span class="mobile-copy"><?=e(ui('map_external_title'))?></span></h2><address><?=e(setting('address'))?></address><div class="home-map-actions"><?php if(setting('phone')):?><a class="button" href="<?=e(phone_url(setting('phone')))?>"><?=icon('phone')?> <?=e(setting('phone'))?></a><?php endif;if(setting('whatsapp')):?><a class="button button-outline" href="<?=e(whatsapp_url(setting('whatsapp')))?>" target="_blank" rel="noopener noreferrer"><?=icon('whatsapp')?> <?=e(ui('whatsapp_contact'))?></a><?php endif;?><a class="text-link" href="<?=e(map_open_url())?>" target="_blank" rel="noopener noreferrer"><?=e(ui('map_directions'))?> <?=icon('arrow-up')?></a></div></div><div class="home-map-visual"><div class="home-map-frame" data-external-map data-map-title="<?=e(setting('home_map_title'))?>" data-map-url="<?=e(map_embed_url())?>"><div class="map-placeholder"><span class="map-placeholder-icon"><?=icon('pin')?></span><p><?=e(ui('map_load_notice'))?></p><button class="button button-outline" type="button" data-load-map><?=icon('pin')?> <?=e(ui('map_load'))?></button></div></div><p class="home-map-disclosure"><?=e(ui('map_external_note'))?></p></div></div></section>
<?php }
