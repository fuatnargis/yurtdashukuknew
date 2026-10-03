<?php
declare(strict_types=1);

$profiles=array_values(array_filter(entries('team',false),fn($profile)=>$profile['status']!=='archived'));
$notice=entry('page','kvkk',false);
$noticeReady=privacy_notice_ready();
$noticeDraft=$notice&&preg_match('/taslak|tamamlanması gereken/iu',$notice['body']);
$noticeUrl=$notice?'/admin/?view=edit&type=page&id='.$notice['id']:'/admin/?view=content&type=page';

function setup_fields(array $fields,string $base): void { ?>
    <dl class="setup-fields">
        <?php foreach($fields as $key=>[$label,$value]): ?>
            <div data-setup-field="<?=e($key)?>" data-filled="<?=$value!==''?'true':'false'?>">
                <dt><a href="<?=e($base.'#field-'.$key)?>"><?=e($label)?> <?=icon('arrow')?></a></dt>
                <dd><?php if($value!==''):?><?=e($value)?><?php else:?><span class="setup-missing">Henüz eklenmedi</span><?php endif;?></dd>
            </div>
        <?php endforeach;?>
    </dl>
<?php } ?>

<div class="a-page-heading">
    <div>
        <span class="a-overline">BİLGİLERİ TAMAMLAYIN</span>
        <h1>Yayın hazırlığı</h1>
        <p>Büro ve avukat bilgilerini ilgili ekranlardan ekleyin. Kaydettiğiniz bilgiler burada güncellenir.</p>
    </div>
    <a class="a-button secondary" href="/" target="_blank" rel="noopener">Siteyi görüntüle <?=icon('arrow-up')?></a>
</div>

<nav class="setup-jump-links" aria-label="Hazırlık adımları">
    <a href="#setup-identity">Büro bilgileri</a>
    <a href="#setup-profiles">Avukat profili</a>
    <a href="#setup-privacy">KVKK ve form</a>
    <a href="#setup-publishing">Yayın aşaması</a>
</nav>

<div class="setup-guide">
    <section class="a-card" id="setup-identity">
        <div class="a-card-heading">
            <div><span class="a-overline">01 · SİTE AYARLARI → BÜRO KİMLİĞİ</span><h2>Büro iletişim bilgileri</h2></div>
            <a class="a-button secondary" href="/admin/?view=settings&amp;group=identity">Bilgileri düzenle <?=icon('arrow')?></a>
        </div>
        <p class="a-muted">Kullanılacak e-posta adresini ekleyin; telefon, açık adres ve çalışma saatlerini kontrol edin. Bu bilgiler iletişim sayfasına ve alt bilgiye yansır.</p>
        <?php setup_fields([
            'email'=>['E-posta',setting('email')],
            'phone'=>['Telefon',setting('phone')],
            'address'=>['Açık adres',setting('address')],
            'whatsapp'=>['WhatsApp numarası',setting('whatsapp')],
            'office_hours'=>['Çalışma saatleri',setting('office_hours')],
        ],'/admin/?view=settings&group=identity'); ?>
        <p class="a-field-hint">Büro e-posta adresi, yönetici girişinde kullandığınız e-posta adresinden ayrı bir ayardır.</p>
    </section>

    <section class="a-card" id="setup-profiles">
        <div class="a-card-heading">
            <div><span class="a-overline">02 · AVUKAT PROFİLLERİ</span><h2>Mesleki bilgiler ve fotoğraf</h2></div>
            <a class="a-button secondary" href="/admin/?view=content&amp;type=team">Profilleri aç <?=icon('arrow')?></a>
        </div>
        <p class="a-muted">Avukatın adını, unvanını, biyografisini ve doğrulanmış mesleki bilgilerini profil düzenleyicisinden ekleyin. Boş mesleki bilgiler sitede gösterilmez.</p>
        <?php if(!$profiles):?>
            <p>Henüz avukat profili eklenmedi.</p>
            <a class="a-button" href="/admin/?view=edit&amp;type=team">Avukat profili ekle <?=icon('plus')?></a>
        <?php endif;?>
        <?php foreach($profiles as $profile):
            $facts=profile_details($profile);
            $profileUrl='/admin/?view=edit&type=team&id='.$profile['id'];
            $fields=[];
            foreach(PROFILE_FIELDS as $key){
                $value=(string)($facts[$key]??'');
                if($key==='started'&&$value!=='')$value=date_tr($value);
                $fields['profile-'.$key]=[ui('profile_'.$key),$value];
            }
        ?>
            <div class="setup-profile">
                <div class="setup-profile-heading">
                    <div>
                        <h3><?=e($profile['title'])?></h3>
                        <p class="a-muted"><?=e($profile['category']?:'Unvan eklenmedi')?> · <?=e($profile['status']==='published'&&strtotime($profile['published_at'])<=time()?'Yayında':($profile['status']==='published'?'İleri tarihli yayın':'Taslak'))?></p>
                    </div>
                    <a class="a-button" href="<?=e($profileUrl)?>">Profili düzenle <?=icon('edit')?></a>
                </div>
                <?php setup_fields($fields,$profileUrl);?>
                <div class="setup-photo">
                    <?php if($profile['image']):?><img src="<?=e(safe_image($profile['image']))?>" alt="<?=e($profile['title'])?> için kayıtlı profil fotoğrafı" width="72" height="88" loading="lazy"><?php endif;?>
                    <div>
                        <strong><?=$profile['image']?'Profil fotoğrafı ekli':'Profil fotoğrafı henüz eklenmedi'?></strong>
                        <p class="a-muted">Fotoğrafı profil düzenleyicisindeki “Profil fotoğrafı” alanından seçin. Yeni fotoğrafı Medya Kütüphanesine yükleyebilirsiniz.</p>
                        <a class="a-link" href="<?=e($profileUrl.'#field-image')?>">Fotoğraf alanına git <?=icon('arrow')?></a>
                    </div>
                </div>
            </div>
        <?php endforeach;?>
    </section>

    <section class="a-card" id="setup-privacy">
        <div class="a-card-heading">
            <div><span class="a-overline">03 · SAYFALAR → KİŞİSEL VERİLERİN KORUNMASI</span><h2>KVKK metni ve iletişim formu</h2></div>
            <span class="badge <?=$noticeReady?'published':'draft'?>" data-setup-privacy><?=$noticeReady?'Mevcut metin doğrulandı':($noticeDraft?'Metin taslak':'Metin incelemesi bekleniyor')?></span>
        </div>
        <p class="a-muted">Aydınlatma metnini büronun gerçek işleyişine göre tamamlayın. İlgili alanları doldururken aşağıdaki bilgileri esas alın:</p>
        <ul class="setup-notice-topics">
            <li>Veri sorumlusunun kimliği ve başvuru için kullanılacak iletişim kanalları.</li>
            <li>Formda toplanan bilgiler, veri toplama yöntemi, işleme amaçları ve hukuki sebepleri.</li>
            <li>Verilerin kimlere, hangi amaçla aktarılacağı; hosting ve veritabanı sağlayıcıları ile sunucuların ülkeleri.</li>
            <li>Saklama ve silme süreçleri, verilere erişebilen kişiler.</li>
            <li>İlgili kişinin hakları ve başvuru yöntemi.</li>
        </ul>
        <div class="setup-actions">
            <a class="a-button" href="<?=e($noticeUrl)?>">1. KVKK metnini düzenle <?=icon('edit')?></a>
            <a class="a-button secondary" href="/admin/?view=settings&amp;group=privacy">2. Tamamlanan metni doğrula <?=icon('check')?></a>
        </div>
        <p class="a-field-hint">Metni kaydedip yayımladıktan sonra “Form ve KVKK” ekranındaki doğrulama kutusunu işaretleyip ayarları kaydedin. Metin değişirse yeniden doğrulama gerekir.</p>
        <div class="setup-form-state" data-setup-contact>
            <strong>İletişim formu: <?=contact_form_available()?'Açık':($noticeReady?'Ayarı kapalı':'KVKK tamamlanmasını bekliyor')?></strong>
            <p class="a-muted">Form talepleri yönetim panelindeki “İletişim Talepleri” bölümünde saklanır. E-posta bildirimi şu anda kurulu değildir.</p>
            <a class="a-link" href="/admin/?view=settings&amp;group=sections#field-contact_enabled">Formu açma/kapatma ayarına git <?=icon('arrow')?></a>
        </div>
        <p class="a-field-hint"><a class="a-link" href="https://www.kvkk.gov.tr/Icerik/2033/Aydinlatma-Yukumlulugu-" target="_blank" rel="noopener noreferrer">KVKK Kurumu: aydınlatma yükümlülüğü <?=icon('arrow-up')?></a></p>
    </section>

    <section class="a-card" id="setup-publishing">
        <div class="a-card-heading">
            <div><span class="a-overline">04 · YAYIN AŞAMASINDA</span><h2>Alan adı, hosting ve görünürlük</h2></div>
            <span class="badge">Yayın sırasında tamamlanacak</span>
        </div>
        <p class="a-muted">Alan adı ve hosting seçildiğinde yayın adresini güncelleyin; sunucu ve veritabanı bilgilerini KVKK metnine işleyin. Hosting hesabı ve alan adı bağlantısı ayrıca kurulacaktır.</p>
        <?php setup_fields(['site_url'=>['Kayıtlı yayın adresi',setting('site_url')]],'/admin/?view=settings&group=seo');?>
        <p class="a-field-hint">Adresin panelde kayıtlı olması, alan adının veya hosting bağlantısının kurulduğu anlamına gelmez.</p>
        <p data-setup-indexing><strong>Arama motoru görünürlüğü: <?=setting('indexing')==='1'?'Açık':'Kapalı'?></strong></p>
        <p class="a-muted">Canlı site hazır olduğunda Arama Motorları bölümünden görünürlüğü açın. Yayın öncesinde içeriklerinizi, iletişim bağlantılarını ve HTTPS üzerinden açılan sayfaları kontrol edin.</p>
        <div class="setup-actions">
            <a class="a-button secondary" href="/admin/?view=settings&amp;group=seo">Yayın adresi ve görünürlük <?=icon('arrow')?></a>
            <a class="a-button secondary" href="/admin/?view=backup">İçerik yedeği al <?=icon('download')?></a>
        </div>
    </section>
</div>
