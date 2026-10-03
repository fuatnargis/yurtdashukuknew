# Tema ve içerik yönetimi

Yerel site: http://127.0.0.1:8088/ · Panel: http://127.0.0.1:8088/admin/

Ana sayfa tek, sabit hukuk görseli; iletişim satırı, büro tanıtımı, avukat profili, çalışma alanları, yayınlar, sorular ve konum bölümleri içerir. Yeni kurulum açık gri/beyaz yüzeyler ve koyu yeşil vurgular kullanır; başlıklar Georgia, gövde metni Arial'dır. Mevcut yerel kayıtta seçilen kurumsal lacivert palet korunur. Metinler ve görünüm panelden yönetilir.

Karşılama metni sıralı olarak belirir; menü kaydırırken görünür kalır. Ana sayfa bölümleri ekrana girdiklerinde yumuşak geçişle açılır. Bağlantı ve kartlarda kısa, ölçülü tepki efektleri vardır. Cihazında hareket azaltma seçeneğini kullanan ziyaretçiler için bu animasyonlar devre dışıdır.

Bölüm düzeni için [Keleş & Koçak](https://www.keleskocak.av.tr/) sitesi referans alınmıştır. Görseller, logo ve metinler projeye aittir. Tasarım incelemesinin ayrıntıları [teslim notlarında](TESLIM-NOTLARI.md) yer alır.

Ana sayfa menüsü açık zemin üzerinde lacivert yazıyla görünür. Ana görsele varsayılan olarak renk katmanı veya doygunluk filtresi uygulanmaz; yazılar fotoğraf üzerinde gölgeyle okunur. Panelde **Site Ayarları → Tema & Renkler → Ana görsel renk katmanı** değeri `0` doğal fotoğrafı gösterir. İstenirse buradan renk katmanı yeniden ayarlanabilir.

Ana sayfadaki avukat kartı `/avukatlar/halil-ibrahim-yurtdas` profilini açar; profil sayfası aynı yazar adına bağlı yayımlanmış makaleleri listeler. Makale kartları ve makale ayrıntısındaki yazar kartı da bu profile bağlanır. Fotoğrafı **Medya Kütüphanesi**'ne yükleyip **Avukat Profilleri → Halil İbrahim Yurtdaş → Profil fotoğrafı** alanından seçebilirsiniz. Adres **Büro Kimliği**'nden yönetilir; farklı bir konum yazılmadıkça yol tarifi bağlantısı adresi kullanır; harita otomatik yüklenmez. Arama ve WhatsApp bağlantıları aynı paneldeki telefon alanlarından oluşur.

Güncel değişiklikler ve tamamlanacak bilgiler: [Teslim notları](TESLIM-NOTLARI.md). İletişim formu, KVKK metni tamamlanıp **Form ve KVKK** ayarından doğrulandıktan sonra başvuru alır.

## Eksik bilgileri tamamlama

Panelde **Yayın Hazırlığı** bölümünü açın: http://127.0.0.1:8088/admin/?view=setup

Bu ekran kayıtlı büro bilgilerini, her avukatın mesleki bilgilerini ve eksik alanları gösterir. Alan adına tıklayınca düzenleyici ilgili girişe kaydırılır ve odaklanır. KVKK metnini düzenleme ve mevcut sürümü doğrulama bağlantıları ile formun güncel durumu ayrı bölümde yer alır. Alan adı/hosting ve arama motoru görünürlüğü yayın aşamasında tamamlanacak adımlar olarak açıklanır.

## Panelde nereden değiştirilir?

| Değişiklik | Panel bölümü |
|---|---|
| 17 ayrı renk, hazır palet, site yazı tipi (Arial, Manrope veya Cormorant Garamond), menü düzeni, köşe yuvarlaklığı, ana görsel yüksekliği ve gölge yoğunluğu | Site Ayarları → Tema & Renkler |
| Bölümleri yukarı/aşağı taşıma, gösterilecek kart sayıları, makale düzeni, görsel alternatif metinleri, buton bağlantıları | Site Ayarları → Sayfa Düzeni |
| Ana sayfa başlıkları, metinleri, sabit ana görsel, tanıtım ve çalışma anlayışı görselleri ile ilk iki buton | Site Ayarları → Ana Sayfa |
| Tanıtım, çalışma alanları, makaleler, SSS ve iletişim bölümlerini açma/kapatma | Site Ayarları → Bölümler & Görünürlük |
| İlkeler bandı, görüşme süreci, avukat kartı, konum, üst iletişim şeridi, açılır menü ve sabit iletişim butonunu açma/kapatma | Site Ayarları → Sayfa Düzeni |
| Logo, favicon, adres, haritada aranacak konum, telefon, e-posta, WhatsApp ve sosyal hesaplar | Site Ayarları → Büro Kimliği |
| Avukatın adı, unvanı, fotoğrafı, biyografisi, baro/sicil/eğitim bilgileri ve profil sayfası | Avukat Profilleri |
| Makalenin yazarı ve profil bağlantısı | Makaleler → Yazar (profil adını seçin) |
| Bölüm ve buton metinleri, SSS ve medya sayfası başlıkları, alt menü metinleri | Site Ayarları → Bölüm & Buton Metinleri |
| Menü bağlantıları ve sıraları | Menüler |
| Makale, çalışma alanı, kurumsal ve diğer sayfa içerikleri | İlgili içerik bölümü |
| Sorular, yanıtlar, sıralama ve yayın durumu | Sık Sorulan Sorular |
| Sayfa başlığı, meta açıklaması ve sabit sayfa dizin durumu | Meta & SEO |
| Alan adı, dizinleme, paylaşım görseli, Search Console ve konum | Site Ayarları → Arama Motorları |
| Olgusal büro özeti, hizmet bölgesi, posta kodu ve makine tarafından okunabilir bilgilendirme notu | Site Ayarları → GEO & Yerel Bilgiler |
| Üst ve alt bilgi ödeme bağlantısı | Site Ayarları → Ödeme Butonu |

Renk önizlemesi ve hazır paletler, **Ayarları kaydet** seçilene kadar siteyi değiştirmez. HEX renk kodu da yazabilirsiniz. Mevcut yerel kayıtta ana renk `#102B46`, buton rengi `#20527A`, vurgu rengi `#C9AD7A` ve açık zemin `#F4F6F8` seçilidir. Yeni kurulumun varsayılan renkleri açık yüzeyler ve yeşil vurgulardır. Kontrast kontrolü, sayfa/kart metni, ikincil metin, buton, menü, alt bilgi ve koyu bölüm için 4,5:1 oranını kontrol eder. Görseller üzerindeki yazı kontrastı ayrıca görsel olarak değerlendirilmelidir.

Basın bölümü kaldırılmıştır; eski `/basinda-biz` adresi ana sayfaya yönlendirilir. İçerikler taslak, ileri tarihli yayın ve arşiv durumlarını destekler. SSS sayfası `/sikca-sorulan-sorular` adresindedir; ana sayfadaki SSS kapatılsa bile kendi sayfasında erişilebilir kalır. Yeni sayfaların menü bağlantıları Menüler bölümünden düzenlenebilir.

## SEO ve GEO

Sunucuda üretilen HTML, canonical adresler, sosyal paylaşım metaları, LegalService/Organization, Article, Service, BreadcrumbList ve görünür sorularla eşleşen FAQPage verileri korunur. Profil ve SSS adresleri site haritasına dahildir; taslak ve ileri tarihli yayınlar dahil edilmez. Arama/filtre sayfaları `noindex,follow` kullanır. RSS ve `llms.txt` dinamik üretilir. Büro özeti, hizmet bölgesi ve posta kodu görünür içerikle birlikte paylaşılır.

Bu teknik altyapı arama veya yapay zekâ sonuçlarında görünme garantisi değildir. `llms.txt` ek bir içerik rehberidir; Google için özel bir GEO dosyası zorunluluğu yoktur. [Google'ın AI arama yönergeleri](https://developers.google.com/search/docs/appearance/ai-features), erişilebilir içerik ve görünür metinle tutarlı yapılandırılmış veriyi esas alır.

Yeni kurulumda dizinleme kapalıdır. Gerçek büro bilgileri ve yayın alan adı tamamlanınca panelden açılabilir. İletişim formu talepleri panelde saklar; mevcut uygulamada SMTP/e-posta gönderimi bağlı değildir.

## Doğrulama ve dosyalar

`npm run deploy:check`: 43 statik dosyanın Vercel yönlendirmesi, 53 PHP dosyasının sözdizimi ve 273 entegrasyon kontrolü geçti.

Mac'te Chrome/Playwright ile 143 genel sayfa/ekran kontrolü tamamlandı. Yeni rehber dahil 42 panel ekranı ve e-posta/baro bilgisi ekleme akışı ayrıca doğrulandı. Rehber 320, 390 ve 1440 pikselde kontrol edildi; ekran görüntüleri `tests/artifacts/professional-review/setup-*.png` altında. Testler ayrı geçici veritabanları kullanır.

Alan adı ve hosting henüz seçilmedi; canlı yayın sonraki aşamada yapılacak.
- `app/theme.php`: ayar şeması, renk değişkenleri ve veri kaybetmeyen sürüm geçişi.
- `assets/cinar.css`: önceki temanın temel bileşenleri.
- `assets/mizan.css`: sitedeki ortak editoryal görünüm.
- `assets/juris-home.css`: Juris düzeninden uyarlanan ana sayfa görünümü.
- `app/views/home-juris.php`: panel sırasıyla oluşturulan yeni ana sayfa.
- `app/cinar-components.php`: açılır menü, çalışma alanı, medya ve SSS bileşenleri.
- `app/theme-admin.php`, `assets/theme-admin.js`: renk önizlemesi ve bölüm sıralama arayüzü.
- `storage/before-cinar-theme-*.sqlite`: yerel veritabanının alınan tutarlı yedeği. Genel erişime kapalı dizindedir.

Önceki sürümün özel içerikleri, yönetici hesapları ve ödeme ayarları korunur. Panelden seçilmiş kurumsal renkler korunur. Bundan sonraki renk değişiklikleri yeniden açılışta sıfırlanmaz.

### Yeni kimlik ve SEO ekranları

- **Büro Kimliği:** Üst menü logosu ve ana görsel/geçiş logosu bağımsız seçilebilir. Yeni dairesel ve yatay logo Medya Kütüphanesinde hazırdır.
- **Ana Sayfa:** Ana görsel ve Tanıtım görseli ayrı alanlardır. **Görünüm:** Başlık boyutu masaüstü ve mobil için ayrı ayarlanır.
- **301 / 302 Yönlendirme:** Site içi kaynak/hedef, kod ve aktiflik seçilir; mevcut yönlendirme düzenlenebilir/silinebilir.
- **Site Haritası:** Harita yayını, adres ekleme/çıkarma, sayfa bazlı noindex ve son değişiklik tarihi yönetilir. Noindex sayfa haritadan da çıkarılır.
- **Makaleler / Çalışma Alanları:** Liste üzerinden düzenleme, önizleme ve yayından kaldırma; Arşiv filtresinden geri alma ve kalıcı silme. Kutucuklarla toplu arşivleme.

### Parallaks ve ilk açılış animasyonu

**Site Ayarları → Tema & Renkler** bölümünde:

- **Kaydırma efekti:** “Yumuşak derinlik” veya “Kapalı”. Anasayfanın arka plan fotoğrafı, ortadaki logo ve Büro hakkında fotoğrafı farklı hızlarda hareket eder. Hakkımızda sayfasındaki görsel de aynı etkiyi kullanır. Yazılar ve butonlar normal kaydırma düzeninde kalır. Mobilde hareket mesafesi azaltılır.
- **İlk açılış animasyonu:** “Yurtdaş logosuyla açılış” veya “Kapalı”. Ana görsel/geçiş logosu, büro adı ve tema renkleri kullanılır. Doğrudan açılışta veya yenilemede kısa süre görünür; site içi gezinmede ve geri dönüşte tekrarlanmaz. Yüklenen sayfa hazır olduğunda kapanır; yavaş/başarısız kaynakta da 1,6 saniyeyi aşmaz. Klavye/dokunma ile hemen kapatılır.

Cihazın **hareketi azalt** veya desteklenen **veri tasarrufu** tercihi iki efekti otomatik kapatır. JavaScript kapalıysa içerik doğrudan görünür. Kaydırma tekniği yalnızca görünür görselleri, kaydırma sırasında günceller; yerel kaydırmayı değiştirmez. Yeni çerez veya tarayıcı depolama anahtarı eklenmez.
