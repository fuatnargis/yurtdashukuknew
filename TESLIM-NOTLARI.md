# Yurtdaş Hukuk - 3 Ekim 2026 kontrol notları

Yerel önizleme: http://127.0.0.1:8088/  
Yönetim paneli: http://127.0.0.1:8088/admin/  
Avukat profili: http://127.0.0.1:8088/avukatlar/halil-ibrahim-yurtdas

## Mac'te devam — 3 Ekim 2026

- Panelde **Yayın Hazırlığı** ekranı eklendi: http://127.0.0.1:8088/admin/?view=setup. Büro e-postası ve iletişim bilgileri, avukatın mesleki bilgileri, KVKK/form durumu ve yayın aşaması tek ekranda açıklanır. Eksik alanlar işaretlenir; bağlantılar ilgili giriş alanına kaydırıp odaklanır. Kaydedilen bilgiler rehbere yansır.
- Kullanıcı e-posta, baro/sicil/eğitim bilgilerini panelden kendisi ekleyecek. Bilgiler tahmin edilmedi. Mevcut avukat portresinin doğru kişiye ait olduğu ve kullanımının onaylandığı bu konuşmada kullanıcı tarafından doğrulandı; fotoğraf korundu.
- Alan adı ve hosting henüz seçilmedi. Paneldeki kayıtlı yayın adresi hostingin kurulduğu anlamına gelmez; canlı yayın sonraki aşamaya bırakıldı. Mevcut kurumsal lacivert tema ayarları korundu.
- Vercel yönlendirmelerine yeni CSS/JS dosyaları ve çalışma alanı/yayın görsellerinin alt klasörleri dahil edildi. Yüklenen SVG logoların PHP işlevinden sunulması düzeltildi. Dağıtım kontrolü artık 43 statik dosyanın doğru yönlendirildiğini de denetler.
- Buluta taşıma aracı kalıcı yönlendirmeleri, kayıtlı içerik yedeklerini ve SVG yüklemelerini aktarır. Kaynak SQLite veritabanı salt okunur kullanılır; dolu hedef veritabanına aktarım yapılmaz. İletişim talepleri yalnızca açıkça istenen aktarım seçeneğiyle taşınır.
- 53 PHP dosyasında sözdizimi ve 273 entegrasyon kontrolü geçti. Mac Chrome/Playwright ile 143 genel sayfa/ekran kontrolü tamamlandı; yeni rehber dahil 42 panel ekranı, alan bağlantıları, odak, e-posta/baro bilgisi kaydetme ve özelleştirilmiş tema ayrıca doğrulandı. Rehber 320, 390 ve 1440 pikselde kontrol edildi.
- Bulut aktarımı ve PHP işlevinin SVG/HEAD/yönlendirme davranışı ayrı bir geçici yerel PostgreSQL sunucusunda doğrulandı. Bu, Vercel'e canlı dağıtım doğrulaması değildir. Yerel gerçek veritabanına test içerikleri eklenmedi.
- Kurulum ve tema belgeleri mevcut tasarım, Mac komutları ve panel rehberine göre güncellendi.

## Yapılan değişiklikler

- [Keleş & Koçak](https://www.keleskocak.av.tr/) sitesinin bölüm düzeni referans alındı. Referansın logosu, fotoğrafları, metinleri ve siyah renk teması kopyalanmadı. Yerel Windows bilgisayarda bir yardımcı ajan, Edge/Playwright'ın görünmez test moduyla masaüstü ve mobil sayfaları inceledi; kullanılabilir bağlı tarayıcı bulunmadığından görünür tarayıcı kontrolü yapılmadı.
- Ana sayfa tek, sabit görselle büro kimliğini gösterir. Görselin altında adres, telefon ve e-posta/ofis saatleri yer alır. E-posta boşsa iletişim adresi uydurulmaz, ofis saatleri gösterilir. Varsayılan bölüm sırası büro tanıtımı, avukat profili, çalışma alanları, yayınlar, sorular ve konumdur. Görüşme usulü panelden açılabilir; varsayılan olarak gizlidir.
- Açık gri/beyaz zeminler, koyu yeşil vurgular, Georgia başlıklar ve Arial gövde metni kullanıldı. Büyük portreli avukat alanı, masaüstünde iki sütunlu yatay fotoğraflı altı çalışma alanı ve üç yayın kartı düzenlendi. Mobilde çalışma alanları tek sütunda, kısa başlık ve görsel ile sunulur; ayrıntılı metin kendi sayfasındadır.
- Otomatik slayt, yinelenen tanıtım bölümleri, sabit reklam görünümündeki butonlar ve üst menüdeki ödeme vurgusu kaldırıldı. Mobilde sade menü, büyük dokunma alanları, telefon ve WhatsApp bağlantıları korundu. Ana görsel yükseklik ve gölge ayarları panelden çalışmaya devam eder; mobil yüksekliği 420 piksel ile sınırlandırılır.
- Meslektaşlarla karşılaştırmalar ve yanıt/sonuç vaatleri çıkarıldı. Altı çalışma alanı, avukat biyografisi ve büro tanıtımı olgusal metinlerle düzenlendi. Uzmanlık veya başarı garantisi kullanılmadı. Yayımlanmış “Deneme Hukuk” kaydı silinmeden taslağa alındı.
- Mevcut içerik ve ayarlar değiştirilmeden önce panelde içerik yedeği, değişen kayıtlar için önceki sürümler kaydedildi. `tools/refine-public-copy.php` tekrar çalıştırıldığında aynı incelemeyi yeniden uygulamaz. Eski içerik güncelleme araçları da bu ortak metinlere bağlandı.
- İletişim formundaki zorunlu “okudum” kutusu istemciden ve sunucudan kaldırıldı; aydınlatma metni ayrı bağlantı olarak sunulur. Formun açılabilmesi için gerçek metnin yönetici tarafından tamamlanıp mevcut sürümünün incelenmesi şartı korundu.
- Profil sayfası; portre/baş harf alanı, mesleki bilgiler, biyografi, yayınlar ve iletişim bağlantılarıyla yeniden düzenlendi. Profil kartının tamamı tıklanabilir; profil bağlantısı sade ve belirgindir.
- Baro, baro ve TBB sicil numaraları, mesleğe başlama tarihi, üniversite, yabancı dil ve KEP adresi profil düzenleyicisinden yönetilir. Boş bilgiler gösterilmez; bilinmeyen mesleki bilgiler tahmin edilmedi. Mevcut profil fotoğrafının kimliği ve kullanım izni Mac'te devam edilen konuşmada kullanıcı tarafından doğrulandı.
- Profil, görüşme süreci ve konum dahil bütün ana sayfa bölümleri panelden sıralanabilir ve gizlenebilir. Ortak profil, form ve arşiv metinleri mevcut metin ayarlarına bağlandı.
- Panelde profil, tema, bölüm sırası ve KVKK için hızlı bağlantılar; ayar sayfalarında arama eklendi. Menülerin hatalı “İçerik eksik” uyarısı giderildi.
- Tema ayarındaki sıfır köşe yarıçapı korunur; iç içe paragraf HTML’i düzeltildi. Ana görsel otomatik olarak değişmez.
- Sayfa adresi değişince eski adresten yeni adrese 301 yönlendirmesi oluşur. Arşivlenmiş/taslak içerik bu yönlendirmelerle açılmaz. Menüdeki ilgili adres güncellenir.
- Profil adı meta düzenleyicisinde değiştiğinde makale yazar bağlantıları da güncellenir. Aynı büro adının sayfa başlığında iki kez görünmesi düzeltildi.
- Ayarlar her HTTP isteğinde bir kez okunur. Yedekler mesleki bilgileri ve kalıcı yönlendirmeleri de içerir; eski bölüm sırasına sahip yedekler desteklenir.
- Google Haritalar gömülü haritası yalnızca ziyaretçi “Google haritasını göster” düğmesine bastığında yüklenir. Öncesinde Google'a harita isteği gönderilmez. Mobilde adres ve yol tarifi bağlantısı sunulur. Harita, yol tarifi ve WhatsApp'ın harici hizmetler olduğu çerez metninde açıklanır. Bu düzen, üçüncü taraf veri aktarım koşullarına ilişkin mesleki incelemenin yerini tutmaz.
- Mobil alt çubukta telefon ve WhatsApp ayrı, büyük dokunma alanlarına taşındı. WhatsApp simgesi projenin önceki Font Awesome marka setinden alınan SVG'dir; kaynak ve lisans bilgisi SVG içinde bulunur.

## Panelde tamamlanacak gerçek bilgiler

1. **Avukat Profilleri:** kullanıcı doğrulanmış mesleki bilgilerini panelden ekleyecek. Mevcut fotoğraf kullanıcı tarafından onaylandı.
2. **Site Ayarları → Büro Kimliği:** e-posta ve iletişim bilgilerinin doğruluğu. Yerel kayıtta e-posta boş.
3. **Sayfalar → Kişisel Verilerin Korunması:** gerçek veri sorumlusu, veri kategorileri, amaçlar, hukuki sebepler, toplama yöntemi, alıcılar, barındırma/aktarım süreçleri ve başvuru kanalları. Metin hâlen taslak; gerçek süreçler bilinmeden tamamlanmış sayılmaz.
4. **Site Ayarları → Form ve KVKK:** tamamlanan metnin mevcut sürümünü doğrulama. Doğrulanmadan form gösterilmez ve sunucu başvuru kabul etmez. KVKK gövdesi değişince inceleme yenilenir. Telefon ve iletişim bağlantıları çalışmaya devam eder. Form talepleri panelde saklanır; SMTP/e-posta bildirimi kurulu değildir.
5. Yayın öncesi içeriklerin mesleki incelemesi, gerçek sunucuda HTTPS ve veri saklama/aktarım koşullarının kontrolü. Yerel veritabanında arama motoru dizinlemesi kapalı tutuldu; canlı dağıtım yapılmadı.

## Yeni görsel

Ana görsel, yerleşik ImageGen aracıyla yeni görsel üretimi modunda oluşturulan özgün, hukuk temalı bir kavramsal fotoğraftır; gerçek büro fotoğrafı olarak sunulmaz. Kaynak: `assets/images/law-hero-light-v1.png`. Sitede kullanılan WebP: `assets/images/law-hero-light-v1.webp` (91.362 bayt). `tools/prepare-reference-hero.php`, kaynak PNG'yi GD ile WebP'ye dönüştürür. Referans sitenin görselleri kullanılmadı.

Üretim istemi:

> Use case: photorealistic-natural. Asset type: wide website hero background for a Turkish lawyer's professional informational website. Create an original high-quality, realistic editorial still-life photograph, landscape 16:9 composition. Pale cool gray stone wall and a clean light gray desk. On the far right third: two neatly stacked law books with plain unlabelled dark green covers, one open book with no legible writing, a small wooden judge's gavel in front, a delicate brass justice balance scale toward the upper right edge. Objects must be clearly recognizable and naturally proportioned, restrained and dignified, no staged office claims. Leave the left two-thirds and the central area almost entirely uncluttered, bright matte cool-gray background with very soft natural daylight and subtle real shadows, giving high contrast for dark teal website identity text to be added later in HTML. Ground the scene at the lower edge. Not black, not beige-dominant, no dark dramatic atmosphere, no gradients as illustration, no decorative floating elements. No people, no logos, no letters, no text, no watermarks. This is a legal-themed concept photograph, not a photograph of a specific law office.

## Hukuki yaklaşım

TBB Reklam Yasağı Yönetmeliği m. 7(d)-(e), web sitesi bilgilerini ve meslektaşlarla rekabete/iş elde etmeye yönelik sıralama uygulamalarını sınırlar. Çalışma alanları uzmanlık iddiası olmadan sunulmalıdır. Üst sıralara çıkma veya yapay zekâ sonuçlarında önerilme garantisi verilmez. Teknik erişilebilirlik, tutarlı kimlik ve görünür içerikle uyumlu yapılandırılmış veri korunmuştur; bu, sitenin bütün içerik ve işletme süreçlerine ilişkin hukuki uygunluk belgesi değildir.

İncelemede 2024 değişikliklerini ve 2 Mayıs 2026 tarihli ek düzenlemeyi içeren güncel TBB metni esas alındı. Aydınlatma yükümlülüğü, ziyaretçiden hizmet koşulu olarak metni okuduğuna dair onay almaktan ayrıldı. Gerçek veri sorumlusu ve veri işleme/aktarım süreçleri bilinmediğinden KVKK metni tamamlanmış sayılmadı.

- [TBB — güncel Reklam Yasağı Yönetmeliği](https://d.barobirlik.org.tr/mevzuat/avukata_ozel/yonetmelikler/2011/reklam_yas_yon.pdf)
- [KVKK — aydınlatma yükümlülüğü açıklaması](https://www.kvkk.gov.tr/Icerik/6765/AYDINLATMA-YUKUMLULUGUNUN-YERINE-GETIRILMESI-HAKKINDA-KAMUOYU-DUYURUSU)
- [KVKK — çerez ve aydınlatma uygulamaları, 2024/1361](https://www.kvkk.gov.tr/Icerik/8884/2024-1361)

## Doğrulama sınırı

`node tools/deploy-check.mjs` PHP sözdizimini ve ayrı geçici veritabanındaki HTTP/entegrasyon senaryolarını kontrol eder. Son sonuçlar `tests/artifacts/integration-results.json` dosyasındadır. Yönetici girişi, CSRF, içerik yayınlama, tema ayarları, profil bilgileri, bölüm sırası, form, KVKK sürüm denetimi, dosya yükleme, yedekler ve eski adresler kapsanır. Gerçek siteye test içerikleri veya test başvuruları eklenmez.

3 Ekim sonucu: 50 PHP dosyasında sözdizimi kontrolü ve 249 entegrasyon kontrolü başarılı. Playwright/Edge ile 320, 360, 390, 430, 700, 768, 820, 1024, 1100, 1280 ve 1440 piksel genişliklerde 11 adres üzerinden 121 sayfa/ekran kontrolü tamamlandı. Taşma, başlık sayısı, üst menü çakışması, bozuk görseller, menü/alt menü, arama, Escape, sık sorulan sorular ve ekran boyutu geçişleri denetlendi. Ek olarak 320, 390, 768, 1100, 1440 ve 1920 pikselde yeni ana sayfa ve çalışma alanı fotoğraflarının gerçekten görünür olduğu kontrol edildi.

Ek tarayıcı kontrolünde haritanın kendiliğinden yüklenmediği, açık kullanıcı işlemiyle yüklendiği, başka kaynaktan iframe kabul etmediği ve yönlendiren adres bilgisini göndermediği doğrulandı. Tema değişkenlerinin yeni görselde uygulanması kontrol edildi. Yeni görünümün ekran görüntüleri `tests/artifacts/reference-local/`, referans incelemesi `tests/artifacts/reference-keleskocak/`, yerel tarayıcı testleri `tests/artifacts/mobile-check.mjs` ve `tests/artifacts/reference-local-check.mjs` altındadır. Görsel inceleme ana sayfa, iletişim, profil ve içerik sayfalarını kapsar. Üretim sunucusu/PostgreSQL dağıtımı ve tüm yayınların ayrı hukuki doğruluğu bu yerel kontrolün kapsamında değildir.

## 3 Ekim 2026 — görsel kimlik, içerik ve SEO yönetimi

Anasayfa başlığı masaüstünde 80 px, mobilde 44 px ve 700 ağırlığında; açık görsel üzerinde koyu mürekkep rengi kullanır. Boyutlar **Site Ayarları → Görünüm**, metin ve fotoğraflar **Site Ayarları → Ana Sayfa** üzerinden değiştirilebilir. Yeni dairesel Yurtdaş mührü ve yatay logo **Büro Kimliği** bölümünden seçilebilir. Ana görsel/geçiş simgesi ayrı seçilebilir. Büro hakkında görseli ana görselden farklıdır. Görseller ve üretim istemi `GORSELLER.md` içindedir.

**Makaleler / Çalışma Alanları:** Yeni ekle, Düzenle, Önizle, Yayından kaldır işlemleri listede bulunur. Seçilen içerikler topluca arşivlenebilir. Arşiv filtresinde Geri al ve Kalıcı sil bulunur. Geri alınan içerik taslak olur; yayımlamadan ziyaretçiye gösterilmez. Kalıcı silme öncesinde otomatik içerik yedeği saklanır. Mevcut örnek makaleler kullanıcı isteğine göre sonraki aşamada değiştirilebilir; bu güncelleme onları silmez. Çalışma alanı ekleme, sıralama ve yayın değişiklikleri anasayfa, menü, dizin ve haritaya yansır. Anasayfada gösterilecek içerik adedi Görünüm bölümündedir.

**301 / 302 Yönlendirme:** Kaynak ve hedef, `/eski-sayfa` ve `/yeni-sayfa` gibi site içi yollarla girilir. Kod seçilebilir, kayıt kapatılabilir, düzenlenebilir veya silinebilir. Yönetim/sistem adresleri ve ana sayfa kaynak yapılamaz. Döngüler reddedilir. İçerik bağlantı adı değişince önceki adresten otomatik 301 korunur.

**Site Haritası:** Yayınlanan sayfalar otomatik eklenir. Her adresin haritaya dahil olma, noindex ve gerçek son değişiklik tarihi kontrol edilebilir. Adresler eklenebilir, haritadan çıkarılabilir ve özel ayarları silinebilir. Sayfa içeriği bu işlemlerle silinmez. Taslak, arşiv, ileri tarihli yayın ve yönlendirme kaynakları haritada bulunmaz. Harita tamamen kapatılırsa XML 404 verir ve robots.txt içindeki harita kaydı kaldırılır. Noindex ayarı ziyaretçi erişimini kapatmaz; arama motorlarına verilen dizinleme talimatıdır.

**Yerel bilgiler:** İl Hatay, ilçe Antakya; gerçek adres, telefon ve avukat kimliği görünür içerik, PostalAddress/LegalService/Person yapısal verisi, meta bilgileri ve llms.txt rehberi ile tutarlıdır. Gerçek koordinatlar ve doğrulanmış Google İşletme Profili bağlantısı **Arama Motorları / GEO & Yerel Bilgiler** bölümlerine sonradan girilebilir. Uydurma sicil, deneyim veya işletme bilgisi eklenmez. Teknik hazırlık arama sonucu sırası veya yapay zekâ cevaplarında yer alma garantisi değildir.

Alan adı/hosting sonraki aşamadır. Yayın adresi ve Search Console doğrulaması gerçek alan adı kararlaştırıldığında tamamlanmalı; genel dizine ekleme yayına alınırken açılmalıdır. E-posta ve mesleki bilgiler **Yayın Hazırlığı** rehberindeki bağlantılardan tamamlanabilir.

İçerik JSON yedeği ve SQLite → PostgreSQL taşıması yeni yönlendirmeler ile sitemap/noindex ayarlarını da korur. Görsel kimlik uygulanmadan önce içerik yedeği alınmıştır. `tools/update-site-identity.php` aynı kurulumda bir kez uygulanır; tekrar çalıştırılması sonraki panel düzenlemelerini ezmez.

Son doğrulama: `npm run deploy:check` ile **59 PHP dosyası**, **47 statik dosya yönlendirmesi** ve **312 entegrasyon kontrolü** geçti. Chrome ile **143 ziyaretçi sayfası/ekran genişliği kontrolü (320–1920 px)** ve **72 panel kontrolü (320, 390, 768, 1440 px)** geçti. Açık SEO formları, medya seçicisinden logo değiştirme, ayrı ana görsel simgesi, mobil menü ve başlık çarpışmaları ayrıca denetlendi. 320 px avukat düzenleyicisindeki taşma düzeltildi. İzole PostgreSQL taşıma testi yeni redirect/sitemap ayarları dahil geçti. Bu kontroller gerçek site içeriklerini silmeden, geçici veritabanlarında yapıldı.

Güncel gerçek site ayarlarının kopyasındaki masaüstü/mobil görsel inceleme: `tests/artifacts/identity-review/home-1440.png`, `home-390.png`, `new-logo.png` ve `results.json`. Panel ve çoklu genişlik ekranları `tests/artifacts/professional-review/` içinde. İçerik test sonuçları `tests/artifacts/integration-results.json` içinde. Canlı hosting henüz seçilmediği için son alan adı/HTTPS/yayın denetimi yayın aşamasında yapılacak.

## Parallaks ve açılış animasyonu

Seçili anasayfa fotoğrafları ve merkez logo farklı hızlarda kayar; kurumsal fotoğraf da aynı derinlik etkisini kullanır. Metinler, bağlantılar ve düğmelerin normal konumu korunur. Mobil hareket daha hafiftir. İlk açılış animasyonunda büro logosu, dönen ince halka ve kısa akış çizgisi bulunur; gerçek yükleme yüzdesi taklit edilmez. Animasyon doğrudan açılış/yenilemede görünür, site içi gezintilerde tekrarlanmaz. Sayfa hazır olunca, kullanıcı etkileşiminde veya süre sınırında kapanır. JavaScript temizliği çalışmasa bile CSS kapanış sınırı vardır. Hareket azaltma ve veri tasarrufunda kapalıdır.

İki kontrol **Site Ayarları → Tema & Renkler** içindedir. Mevcut ayarlar korunarak eksik hareket ayarları eklenir. Dosyalar: `assets/motion-start.js`, `assets/motion.js`, `assets/motion.css`. Fotoğraf kenarlarının açılmaması için görseller yalnızca etkin modda küçük bir payla büyütülür ve çerçevesinde kırpılır.

`npm run test:motion`: 32 kontrol; 320–1920 px, farklı hareket hızları, sabit yazılar, kenar boşlukları, canlı hareket azaltma tercihi, veri tasarrufu, yavaş/başarısız görsel, CSS kapanış güvencesi, klavye, JavaScript/depolama kapalı, site içi geçiş/geri dönüş ve panelden kapatma/açma. Ekran görüntüleri ve sonuçlar `tests/artifacts/motion-review/` içindedir. Genel entegrasyon: 315 kontrol, 59 PHP dosyası ve 50 statik dosya yönlendirmesi geçti.

Hareket efektleri sonrasında genel Chrome kontrolü de geçti: 143 ziyaretçi sayfası/ekran genişliği ve 72 yönetim paneli kontrolü; menü, sayfa geçişi, logo seçimi ve SEO formları çalışıyor.
