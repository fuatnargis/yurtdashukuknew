<?php
declare(strict_types=1);
const CONTENT_TYPES=[
    'team'=>['label'=>'Avukat Profilleri','single'=>'Avukat profili','icon'=>'users','hint'=>'Avukatın fotoğrafını, unvanını, biyografisini ve profil sayfasını yönetin.'],
    'article'=>['label'=>'Makaleler','single'=>'Makale','icon'=>'book','hint'=>'Hukuki yayınlarınızı hazırlayın, taslak olarak saklayın veya yayınlayın.'],
    'practice'=>['label'=>'Çalışma Alanları','single'=>'Çalışma alanı','icon'=>'scale','hint'=>'Çalışma alanlarını, açıklamalarını ve görüntülenme sıralarını düzenleyin.'],
    'page'=>['label'=>'Sayfalar','single'=>'Sayfa','icon'=>'file','hint'=>'Kurumsal bilgiler, aydınlatma metinleri ve ek sayfaları yönetin.'],
    'faq'=>['label'=>'Sık Sorulan Sorular','single'=>'Soru','icon'=>'mail','hint'=>'Ana sayfadaki soru ve yanıtları düzenleyin.'],
    'menu'=>['label'=>'Menüler','single'=>'Menü bağlantısı','icon'=>'menu','hint'=>'Ana menü ve alt menü bağlantılarını sıralayın. Küçük sıra numarası önce görünür.'],
];
function settings_fields(): array {return [
    'identity'=>['title'=>'Büro Kimliği','description'=>'Büronuzun adı, iletişim bilgileri ve sosyal bağlantıları. Logo boşsa sabit renkli kurumsal simge ve büro adı kullanılır. Özel SVG logolar Medya Kütüphanesinden yüklenebilir; logo renkleri temadan etkilenmez.','fields'=>[
        'brand'=>['Büro adı','text'],'brand_subtitle'=>['Alt başlık','text'],'logo'=>['Üst menü logosu (boşsa Yurtdaş mührü ve büro adı)','image'],'logo_mark'=>['Ana görsel ve geçiş logosu (boşsa üst menü logosu)','image'],'favicon'=>['Site simgesi','image'],
        'logo_width'=>['Masaüstü logo alanı genişliği (px)','number'],
        'city'=>['İl','text'],'district'=>['İlçe','text'],'address'=>['Açık adres','textarea'],'map_query'=>['Harita için farklı konum (boşsa adres kullanılır)','text'],'phone'=>['Telefon','text'],'email'=>['E-posta','email'],'whatsapp'=>['WhatsApp numarası','text'],'office_hours'=>['Çalışma saatleri','text'],'linkedin'=>['LinkedIn bağlantısı','url'],'instagram'=>['Instagram bağlantısı','url'],
    ]],
    'privacy'=>['title'=>'Form ve KVKK','description'=>'Önce Sayfalar bölümündeki KVKK metnini gerçek veri sorumlusu, işleme sebepleri, alıcılar, barındırma ve başvuru bilgileriyle tamamlayın. Onay mevcut metin sürümü için geçerlidir; metin değişince form yeniden inceleme bekler.','fields'=>[
        'privacy_notice_reviewed'=>['KVKK metnini gerçek veri işleme süreçlerimize göre tamamladım ve kontrol ettim','checkbox'],
    ]],
    'home'=>['title'=>'Ana Sayfa','description'=>'Karşılama alanını ve kurumsal tanıtım bölümünü düzenleyin.','fields'=>[
        'hero_eyebrow'=>['Üst etiket','text'],'hero_title'=>['Ana başlık','textarea'],'hero_text'=>['Karşılama metni','textarea'],'hero_image'=>['Ana görsel','image'],'hero_button'=>['Birinci buton metni','text'],'hero_button_url'=>['Birinci buton bağlantısı','link'],'hero_secondary'=>['İkinci buton metni','text'],'hero_secondary_url'=>['İkinci buton bağlantısı','link'],'hero_note'=>['Görsel altındaki ilke satırı','text'],
        'intro_eyebrow'=>['Tanıtım üst etiketi','text'],'intro_title'=>['Tanıtım başlığı','textarea'],'intro_text'=>['Tanıtım metni','textarea'],'intro_image'=>['Tanıtım görseli','image'],'intro_label'=>['Hakkımızda görselinin altyazı başlığı','text'],'intro_caption'=>['Hakkımızda görselinin altyazı metni','text'],
        'approach_eyebrow'=>['Çalışma anlayışı etiketi','text'],'approach_title'=>['Çalışma anlayışı başlığı','textarea'],'approach_text'=>['Çalışma anlayışı metni','textarea'],'approach_image'=>['Çalışma anlayışı görseli','image'],
        'office_eyebrow'=>['Büro kartı etiketi','text'],'office_title'=>['Büro kartı başlığı','textarea'],'office_text'=>['Büro kartı açıklaması','textarea'],
        'home_profile_eyebrow'=>['Avukat kartı etiketi','text'],'home_profile_title'=>['Avukat kartı başlığı','text'],'home_profile_text'=>['Avukat kartı açıklaması','textarea'],
        'home_map_eyebrow'=>['Harita bölümü etiketi','text'],'home_map_title'=>['Harita bölümü başlığı','text'],
        'consultation_eyebrow'=>['Görüşme süreci etiketi','text'],'consultation_title'=>['Görüşme süreci başlığı','text'],'consultation_text'=>['Görüşme süreci açıklaması','textarea'],
        'consultation_step_1_title'=>['1. adım başlığı','text'],'consultation_step_1_text'=>['1. adım açıklaması','textarea'],
        'consultation_step_2_title'=>['2. adım başlığı','text'],'consultation_step_2_text'=>['2. adım açıklaması','textarea'],
        'consultation_step_3_title'=>['3. adım başlığı','text'],'consultation_step_3_text'=>['3. adım açıklaması','textarea'],
    ]],
    'sections'=>['title'=>'Bölümler & Görünürlük','description'=>'Bölüm başlıklarını ve ana sayfada hangi bölümlerin görüneceğini seçin.','fields'=>[
        'practice_eyebrow'=>['Çalışma alanları etiketi','text'],'practice_title'=>['Çalışma alanları başlığı','textarea'],'practice_text'=>['Çalışma alanları açıklaması','textarea'],
        'articles_eyebrow'=>['Yayınlar etiketi','text'],'articles_title'=>['Yayınlar başlığı','text'],'articles_text'=>['Yayınlar açıklaması','textarea'],
        'contact_eyebrow'=>['İletişim bandı etiketi','text'],'contact_title'=>['İletişim bandı başlığı','textarea'],'contact_text'=>['İletişim bandı açıklaması','textarea'],'contact_button'=>['İletişim butonu metni','text'],
        'show_intro'=>['Kurumsal tanıtımı göster','checkbox'],'show_approach'=>['Çalışma anlayışını göster','checkbox'],'show_office'=>['Büro ve iletişim kartını göster','checkbox'],'show_practices'=>['Çalışma alanlarını göster','checkbox'],'show_articles'=>['Son yayınları göster','checkbox'],'show_faq'=>['Sık sorulan soruları göster','checkbox'],'show_contact'=>['İletişim bandını göster','checkbox'],'show_mobile_contact'=>['Mobil iletişim çubuğunu göster','checkbox'],'contact_enabled'=>['İletişim formunu etkinleştir','checkbox'],'articles_per_page'=>['Arşivde sayfa başına makale','number'],
    ]],
    'appearance'=>['title'=>'Tema & Renkler','description'=>'Tüm renkleri, yazı tipini ve menü düzenini buradan değiştirin. Önizleme kaydetmeden güncellenir.','fields'=>array_merge(array_map(fn($row)=>[$row[0],'color'],THEME_COLORS),[
        'heading_font'=>['Başlık yazı tipi','select'],'body_font'=>['Gövde yazı tipi','select'],'header_layout'=>['Menü düzeni','select'],'card_radius'=>['Kart köşe yuvarlaklığı (px)','number'],'hero_height'=>['Ana görsel yüksekliği (px)','number'],'hero_overlay_opacity'=>['Ana görsel renk katmanı (%) — doğal fotoğraf için 0','number'],
        'body_font_size'=>['Gövde yazı boyutu (px)','number'],'section_heading_size'=>['Bölüm başlığı boyutu (px)','number'],
        'hero_title_size'=>['Ana başlık boyutu (px)','number'],'mobile_hero_title_size'=>['Mobil ana başlık boyutu (px)','number'],
        'nav_font_size'=>['Menü yazı boyutu (px)','number'],'section_spacing'=>['Masaüstü bölüm boşluğu (px)','number'],
        'mobile_section_spacing'=>['Mobil bölüm boşluğu (px)','number'],'mobile_hero_height'=>['Mobil ana görsel yüksekliği (px)','number'],
        'page_transition'=>['Sayfalar arası geçiş','select'],
        'parallax_effect'=>['Kaydırma efekti (mobilde hafifletilir)','select'],
        'loading_animation'=>['İlk açılış animasyonu (en fazla 1,6 saniye)','select'],
    ])],
    'footer'=>['title'=>'Alt Bilgi (Footer)','description'=>'Alt bilgi açıklamasını, yasal notu ve menü dışındaki bağlantıları düzenleyin. Her ek bağlantıyı Başlık|/adres biçiminde ayrı satıra yazın.','fields'=>[
        'footer_text'=>['Alt bilgi tanıtım metni','textarea'],'legal_notice'=>['Genel bilgilendirme notu','textarea'],
        'footer_extra_links'=>['Ek bağlantılar (her satır: Başlık|/adres)','textarea'],
        'show_footer_practices'=>['Çalışma alanları sütununu göster','checkbox'],'show_footer_discover'=>['Bağlantılar sütununu göster','checkbox'],'show_footer_contact'=>['İletişim sütununu göster','checkbox'],
    ]],
    'layout'=>['title'=>'Sayfa Düzeni','description'=>'Ana sayfa bölümlerini oklarla sıralayın. Görünürlüğü Bölümler & Görünürlük sekmesinden değiştirebilirsiniz.','fields'=>[
        'home_section_order'=>['Ana sayfa bölüm sırası','section-order'],'home_articles_layout'=>['Makale görünümü','select'],'home_practice_count'=>['Ana sayfada çalışma alanı sayısı','number'],'home_article_count'=>['Ana sayfada makale sayısı','number'],
        'show_principles'=>['İlkeler bandını göster','checkbox'],'show_home_profile'=>['Ana sayfada avukat kartını göster','checkbox'],'show_home_consultation'=>['Görüşme sürecini göster','checkbox'],'show_home_map'=>['Ana sayfada haritayı göster','checkbox'],'show_home_contacts'=>['Ana görsel altındaki iletişim satırını göster','checkbox'],'show_topbar'=>['Üst iletişim şeridini göster','checkbox'],'show_header_contact'=>['Menü iletişim butonunu göster','checkbox'],'show_practice_dropdown'=>['Çalışma alanları açılır menüsünü göster','checkbox'],'show_floating_contact'=>['Sabit iletişim butonunu göster','checkbox'],
        'show_search'=>['Üst menü aramasını göster','checkbox'],'show_breadcrumbs'=>['Sayfa yolu bağlantılarını göster','checkbox'],'show_reading_progress'=>['Okuma ilerleme çizgisini göster','checkbox'],'show_back_top'=>['Sayfa başına dön düğmesini göster','checkbox'],
        'show_section_labels'=>['Bölüm üst etiketlerini göster','checkbox'],'show_mobile_practice_text'=>['Mobilde çalışma alanı açıklamalarını göster','checkbox'],
        'corporate_show_image'=>['Hakkımızda sayfasında görseli göster','checkbox'],'corporate_show_principles'=>['Hakkımızda sayfasında ilkeleri göster','checkbox'],
        'header_contact_url'=>['Menü iletişim butonu bağlantısı','link'],'intro_link_url'=>['Tanıtım butonu bağlantısı','link'],'contact_button_url'=>['İletişim bandı buton bağlantısı','link'],'contact_band_image'=>['İç sayfalardaki iletişim bandı görseli','image'],'hero_image_alt'=>['Ana görsel alternatif metni','text'],'intro_image_alt'=>['Tanıtım görseli alternatif metni','text'],'approach_image_alt'=>['Çalışma anlayışı görseli alternatif metni','text'],
    ]],
    'geo'=>['title'=>'GEO & Yerel Bilgiler','description'=>'Arama motorları ve yapay zekâ arama sistemleri için doğrulanabilir büro bilgileri. Bu ayarlar görünür içerikle ve llms.txt ile paylaşılır.','fields'=>[
        'seo_service_area'=>['Hizmet verilen bölge','text'],'seo_postal_code'=>['Posta kodu','text'],'geo_summary'=>['Büro hakkında kısa, olgusal özet','textarea'],'business_profile_url'=>['Doğrulanmış Google İşletme Profili bağlantısı','url'],'geo_note'=>['Bilgilendirme notu','textarea'],
    ]],
    'payment'=>['title'=>'Ödeme Butonu','description'=>'Masaüstü ve mobil üst bar, mobil menü ve alt bilgideki ödeme bağlantısını düzenleyin. Bu site kart bilgisi toplamaz; ziyaretçi ödeme sağlayıcısına yönlendirilir. Yayından önce bağlantının büroya ait olduğunu doğrulayın.','fields'=>[
        'payment_enabled'=>['Ödeme butonunu göster','checkbox'],'payment_label'=>['Buton metni','text'],'payment_url'=>['Ödeme bağlantısı','url'],
    ]],
    'directory'=>['title'=>'Arşiv Metinleri','description'=>'Kategori arşivi sayfasındaki tanıtım metinleri.','fields'=>[
        'category_directory_title'=>['Kategori dizini başlığı','text'],'category_directory_text'=>['Kategori dizini açıklaması','textarea'],
    ]],
    'texts'=>['title'=>'Bölüm & Buton Metinleri','description'=>'Sitedeki ortak başlıkları, açıklamaları ve buton metinlerini düzenleyin.','fields'=>array_combine(array_map(fn($key)=>'text_'.$key,array_keys(SITE_TEXTS)),array_map(fn($row)=>[$row[0],'textarea'],array_values(SITE_TEXTS)))],
    'seo'=>['title'=>'Arama Motorları','description'=>'Site adresi, paylaşım bilgileri ve arama motoru görünürlüğü. Sayfa bazlı başlık ve açıklamalar için “Meta & SEO” bölümünü kullanın.','fields'=>[
        'site_url'=>['Yayın adresi (https://alanadiniz.com)','url'],'seo_title'=>['Ana sayfa başlığı','text'],'seo_description'=>['Ana sayfa açıklaması','textarea'],'indexing'=>['Arama motorlarının siteyi dizine eklemesine izin ver','checkbox'],'sitemap_enabled'=>['XML site haritasını yayımla','checkbox'],
        'seo_og_image'=>['Varsayılan paylaşım görseli (1200×630)','image'],'seo_twitter'=>['X / Twitter kullanıcı adı (@buro)','text'],'seo_google_verification'=>['Google Search Console doğrulama kodu','text'],
        'seo_legal_name'=>['Resmî unvan (schema.org legalName)','text'],'seo_founding_year'=>['Kuruluş yılı','text'],'seo_geo_region'=>['Bölge kodu (ör. 31 = Hatay)','text'],'seo_geo_lat'=>['Büro enlem (latitude)','text'],'seo_geo_lng'=>['Büro boylam (longitude)','text'],
    ]],
];}
function valid_setting(string $key,string $value,string $type): bool {
    if(mb_strlen($value)>15000)return false;
    if(in_array($key,['brand','hero_title','seo_title'],true)&&$value==='')return false;
    if($type==='color')return (bool)preg_match('/^#[a-f0-9]{6}$/i',$value);
    if($type==='checkbox')return in_array($value,['0','1'],true);
    if($type==='select')return isset(THEME_OPTIONS[$key][$value]);
    if(isset(THEME_NUMBERS[$key]))return ctype_digit($value)&&(int)$value>=THEME_NUMBERS[$key][0]&&(int)$value<=THEME_NUMBERS[$key][1];
    if(isset(SITE_CONTROL_NUMBERS[$key]))return ctype_digit($value)&&(int)$value>=SITE_CONTROL_NUMBERS[$key][0]&&(int)$value<=SITE_CONTROL_NUMBERS[$key][1];
    if($type==='section-order'){$keys=array_map('trim',explode(',',$value));return count($keys)===count(HOME_SECTIONS)&&count(array_unique($keys))===count(HOME_SECTIONS)&&!array_diff($keys,array_keys(HOME_SECTIONS));}
    if($key==='articles_per_page')return ctype_digit($value)&&(int)$value>=3&&(int)$value<=24;
    if($key==='footer_extra_links'){
        foreach(preg_split('/\R/u',$value) as $line){if(trim($line)==='')continue;$parts=explode('|',$line,2);if(count($parts)!==2||trim($parts[0])===''||mb_strlen(trim($parts[0]))>100||safe_url(trim($parts[1]))!==trim($parts[1]))return false;}
        return true;
    }
    if($value==='')return true;
    if(in_array($key,['seo_geo_lat','seo_geo_lng'],true))return is_numeric($value)&&abs((float)$value)<=($key==='seo_geo_lat'?90:180);
    if($key==='seo_founding_year')return (bool)preg_match('/^(18|19|20)\d{2}$/',$value);
    if($key==='seo_twitter')return (bool)preg_match('/^@?[A-Za-z0-9_]{1,15}$/',$value);
    if($key==='seo_google_verification')return (bool)preg_match('/^[A-Za-z0-9_-]{8,120}$/',$value);
    if($key==='seo_geo_region')return (bool)preg_match('/^[A-Za-z0-9]{1,3}$/',$value);
    if($type==='email')return (bool)filter_var($value,FILTER_VALIDATE_EMAIL);
    if($type==='url')return filter_var($value,FILTER_VALIDATE_URL)&&in_array(parse_url($value,PHP_URL_SCHEME),['http','https'],true)&&!parse_url($value,PHP_URL_USER)&&!parse_url($value,PHP_URL_PASS)&&!parse_url($value,PHP_URL_FRAGMENT)&&($key!=='site_url'||!parse_url($value,PHP_URL_QUERY));
    if($type==='link')return safe_url($value)===$value;
    if($type==='image')return media_exists($value);
    return true;
}
