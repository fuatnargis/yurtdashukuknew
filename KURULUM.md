# Yurtdaş Hukuk — web sitesi ve yönetim paneli

Ana sayfa tek, sabit hukuk görseli; büro tanıtımı, avukat profili, çalışma alanları, yayınlar, sorular ve konum bölümlerinden oluşur. Tema renkleri, yazı tipi, menü düzeni ve ana sayfa bölüm sırası panelden yönetilir; mevcut kayıttaki kurumsal lacivert palet korunur. Ayrıntılar için [Tema ve içerik yönetimi](TEMA-YONETIMI.md) belgesini okuyun.

Güncel geliştirmeler, test sınırları ve tamamlanacak gerçek bilgiler: [Teslim notları](TESLIM-NOTLARI.md).

## Yerel olarak açma

Mac'e USB ile taşıma ve başlatma adımları: [Mac'e taşıma](MAC-ILE-TASIMA.md).

Mac'te proje klasöründen:

```bash
bash baslat-mac.sh
```

Windows PowerShell'de proje klasöründen:

```powershell
.\baslat.ps1
```

PowerShell betik çalıştırmayı engelliyorsa sistem politikasını değiştirmeden:

```powershell
php -d extension=php_pdo_sqlite.dll -d extension=php_gd.dll -S 127.0.0.1:8088 router.php
```

- Site: **http://127.0.0.1:8088/**
- Makaleler: **http://127.0.0.1:8088/makaleler**
- Panel: **http://127.0.0.1:8088/admin/**
- İlk giriş bilgileri: **storage/admin-access.txt**. Parola kurulumda rastgele oluşturulur; kodda ortak/sabit parola yoktur.
- Durdurmak için sunucunun çalıştığı terminalde `Ctrl+C`.
- Başka port: `.\baslat.ps1 -Port 8090`.

Mevcut teslimde yerel sunucu arka planda 8088 portunda çalıştırılmıştır. Aynı portta ikinci sunucu başlatmayın. Çalışan sunucuyu doğrulamak için `Get-NetTCPConnection -LocalPort 8088 -State Listen` kullanabilirsiniz. Sunucuyu durdurmadan önce portu dinleyen işlemin komut satırını kontrol edin; işlem kimlikleri yeniden kullanılabilir.

### Gereksinimler

- PHP 8.2 veya üzeri; PDO SQLite, mbstring ve GD eklentileri.
- Yerel başlangıç betiği Windows'taki mevcut PHP kurulumunda eksik SQLite/GD eklentilerini yalnızca bu işlem için etkinleştirir; ortak php.ini dosyanızı değiştirmez.
- Üretimde Apache + mod_rewrite veya aşağıdaki Nginx yönlendirmeleri.
- PHP'nin `storage/` ve `assets/uploads/` dizinlerine yazma yetkisi.
- NPM veya Composer kurulumu, harici JS/CSS CDN'i ve MySQL sunucusu gerekmez.

## Hangi dosya ne işe yarıyor?

| Dosya/dizin | Görevi |
|---|---|
| `index.php` | Sunucuda çalışan dinamik ön yüz ve sayfa yönlendirmeleri |
| `app/` | Veritabanı, oturum, şablonlar, ayarlar ve başlangıç içerikleri |
| `admin/` | Giriş, içerik düzenleyici, panel ekranları ve güvenli işlemler |
| `assets/site.css`, `assets/site.js` | Yeni sitenin görünümü ve etkileşimleri |
| `assets/admin.css`, `assets/admin.js` | Yönetim panelinin görünümü ve etkileşimleri |
| `assets/images/` | Projeye özel görseller |
| `assets/uploads/` | Panelden yüklenen, yeniden kodlanan WebP görseller |
| `storage/site.sqlite` | Kalıcı içerikler, yöneticiler, talepler ve içerik geçmişi |
| `router.php` | PHP yerel geliştirme sunucusunun yönlendirmeleri |
| `.htaccess` | Apache yönlendirmeleri ve özel dosya erişim engelleri |
| `app/views/admin-setup.php` | Panelde eksik bilgileri ve doğrudan düzenleme bağlantılarını gösteren yayın rehberi |
| `tests/integration.mjs` | Geçici ve ayrı veritabanıyla çalışan uygulama testleri |

**Dinamik siteyi PHP sunucusundan açın.** Live Server PHP uygulamasını çalıştırmaz. Paneldeki değişiklikler dinamik siteye kaydettiğinizde yansır.

Eski şablon sayfaları sunucu üzerinden ilgili yeni sayfalara 301 ile yönlendirilir. Eski, doğrulamasız `include/contact-process.php` gönderim ucu devre dışı bırakılmıştır.

## Yönetim paneli

- **Yayın Hazırlığı:** eksik e-posta ve mesleki bilgiler, KVKK/form durumu ve yayın aşamasının açıklamaları. Her alanın bağlantısı doğrudan ilgili giriş alanını açar. `/admin/?view=setup` adresinden ulaşılır.
- **Genel bakış:** gerçek içerik sayıları, yeni talepler, yayın hazırlığı ve işlem kaydı.
- **Makaleler:** başlık, bağlantı, özet, kategori, yazar, kapak, ana metin ve sayfaya özel arama motoru bilgileri.
- **Yayın kontrolü:** taslak, yayın, ileri tarihli yayın ve geri alınabilir arşivleme.
- **İçerik geçmişi:** her değişiklik öncesi kayıt; önceki sürüme dönme.
- **Çalışma alanları:** sayfa içeriği, simge, görsel ve sıra.
- **Sayfalar:** kurumsal metin, ekip tanıtımı, KVKK, çerez, yasal bilgiler ve yeni özel sayfalar.
- **Avukat Profilleri:** avukatın unvanı, özgeçmişi, fotoğrafı, mesleki bilgileri ve yazarlık bağlantıları.
- **Sık sorulan sorular:** soru, yanıt, sıralama ve görünürlük.
- **Menüler:** bağlantı, başlık, sıra ve yayın durumu; üst ve alt menü aynı içerikten üretilir.
- **Medya:** JPG, PNG, WebP yükleme ve içerik düzenleyicisinden görsel seçme. En fazla 5 MB / 20 megapiksel; yüklemeler GD ile WebP olarak yeniden kodlanır.
- **Site ayarları:** büro adı, logo, iletişim, sosyal bağlantılar, ana sayfa metin ve görselleri, renkler, bölüm görünürlüğü, buton ve bölüm metinleri, genel arama motoru ayarları.
- **İletişim:** gelen talepleri görme ve yeni/okundu/yanıtlandı/arşiv durumları.
- **Yedekleme:** içerik ve ayarları JSON dışa/içe aktarma. İçe aktarma öncesi otomatik içerik yedeği alınır.
- **Hesabım:** yönetici adı, e-posta ve parola değiştirme. Değişiklik diğer oturumları kapatır.

### Metin düzenleme

Metin alanı güvenli, sınırlı Markdown sözdizimi kullanır:

```text
## Bölüm başlığı
### Alt başlık
Normal paragraf. **Kalın ifade**.
- Liste maddesi
[Bağlantı metni](https://ornek.com)
```

Düz metin biçiminde HTML metin olarak gösterilir. HTML biçimi seçilirse yalnızca izin verilen etiketler temizlenerek işlenir; JavaScript çalıştırılmaz. Kayıtlı taslaklar yönetici önizlemesinde incelenebilir. Düzenleyici kaydedilmemiş değişikliklerde sayfadan ayrılmadan önce uyarır. Eşzamanlı düzenlemelerde eski kayıt yeni içeriğin üzerine yazılmaz.

## Yayından önce tamamlanacak gerçek bilgiler

Başlangıç verilerinde **Yurtdaş Hukuk**, Av. Halil İbrahim Yurtdaş, Antakya/Hatay adresi ve verilen telefon numarası bulunur. Profil fotoğrafı henüz yüklenmediğinden avukat kartında adının baş harfleri görünür. Fotoğrafı **Avukat Profilleri** bölümünden, e-posta ve diğer iletişim bilgilerini **Büro Kimliği** bölümünden tamamlayın. Örnek yazılar ve çalışma alanları da panelden düzenlenebilir.

KVKK sayfası açıkça işaretlenmiş bir **taslaktır**. Form, bu metin tamamlanıp **Site Ayarları → Form ve KVKK** ekranından doğrulanana kadar başvuru kabul etmez. Metin gövdesi değiştirildiğinde doğrulama yenilenmelidir. Gerçek veri sorumlusu kimliği, işleme amaçları/hukuki sebepler, aktarım bilgileri ve başvuru kanalları büronun uygulamasına göre tamamlanmalıdır. Çerez ve diğer yasal metinler de gerçek kurulumla birlikte gözden geçirilmelidir. Formun aydınlatma metni okundu onayı, pazarlama veya genel açık rıza olarak sunulmaz.

Başlangıçta arama motoru dizinlemesi kapalıdır. Gerçek bilgiler tamamlandıktan sonra **Site Ayarları → Arama Motorları** bölümünden kök alan adını ve görünürlüğü ayarlayın.

**İletişim formu talepleri veritabanına ve panele kaydeder. E-posta/SMTP gönderimi bağlı değildir.** Paneldeki e-posta bağlantısı yöneticinin e-posta uygulamasını açar; kendiliğinden e-posta göndermez. Hassas belge yükleme veya müvekkil dosyası yönetimi bu sitenin kapsamı değildir.

## Üretime taşıma

Bu teslim yerel ortamda çalışır; herhangi bir alan adına veya sunucuya yayınlanmadı. PHP'nin geliştirme sunucusu üretim sunucusu olarak kullanılmamalıdır.

1. PHP gereksinimlerini sağlayan HTTPS etkin bir sunucu hazırlayın. Uygulama alan adının **kök dizinine** kurulacak şekilde yazılmıştır.
2. `app/`, `admin/`, `assets/`, `index.php`, `makaleler.php` ve `.htaccess` dosyalarını aktarın. Nginx için aşağıdaki örneği uyarlayın. Özgün şablonlar, testler, statik önizlemeler, geliştirme günlükleri ve `admin-access.txt` üretim paketine gerekli değildir.
3. `storage` dizinini tercihen web kökünün dışında oluşturup PHP sürecinde `HUKUK_DATA_DIR` ortam değişkenini bu mutlak yola ayarlayın. Web kökünde kalacaksa erişim engellerinin çalıştığını doğrulayın.
4. Boş kurulum için `php tools/install.php --email=yonetici@alanadiniz.com --name="Site Yöneticisi"` komutunu SSH/CLI üzerinden çalıştırın. Araç yalnızca hesap yoksa hesap oluşturur; mevcut hesabı değiştirmez. Parolayı panelden değiştirin ve ilk parola dosyasını güvenli şekilde kaldırın.
5. Mevcut siteyi taşıyorsanız SQLite'ın tutarlı yedeğini ve görselleri birlikte aktarın. Canlı veritabanı dosyasını tek başına kopyalamayın; bakım sırasında servisi durdurun veya SQLite backup API / `VACUUM INTO` kullanın.
6. Dosya izinlerini uygulama kullanıcısıyla sınırlayın; düzenli sunucu yedeği ve iletişim talepleri için saklama/silme politikası belirleyin.
7. Panelde alan adını ve gerçek büro bilgilerini tamamlayıp bağlantıları, dosya engellerini ve formları yeni ortamda yeniden test edin.

### Nginx örneği

Bu örnekte PHP-FPM soketini ve kök dizini kendi sunucunuza göre düzenleyin:

```nginx
server {
    listen 443 ssl;
    server_name alanadiniz.com;
    root /var/www/hukuk;
    index index.php;
    client_max_body_size 12m;
    # ssl_certificate ve ssl_certificate_key burada tanımlanır.

    location ~ (^|/)\. { deny all; }
    location ~* ^/(storage|app|tools|tests|template-original|node_modules)(/|$) { deny all; }
    location ~* \.(sqlite|db|log|ini|ps1|md|json|lock|bak)$ { deny all; }
    location = /robots.txt { rewrite ^ /index.php last; }
    location = /sitemap.xml { rewrite ^ /index.php last; }
    location ~ \.html$ { rewrite ^ /index.php last; }
    location /assets/ { try_files $uri =404; }
    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ ^/(index|makaleler|admin/index|admin/login|admin/action)\.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ \.php$ { return 404; }
}
```

Medya dizininde PHP yürütmeye izin vermeyin. Nginx'te `.htaccess` dosyaları uygulanmaz; özel dizinleri Nginx yapılandırmasında engellemek gerekir.

## Doğrulama

```powershell
node tests/integration.mjs
```

Vercel'e güncelleme göndermeden önce `npm.cmd run deploy:check` komutunu çalıştırın. Bu komut gerekli dağıtım dosyalarını, PHP sözdizimini ve entegrasyon testlerini kontrol eder. PowerShell'de `npm` komutu çalıştırma ilkesi nedeniyle engellenirse `npm.cmd` kullanın.

Node.js 22+ gerekir. Test kendi PHP sunucusunu ve ayrı geçici veritabanını başlatır. Gerçek site içeriklerini değiştirmez. Ürettiği geçici yükleme dosyasını temizler. Sonuç dosyası `tests/artifacts/integration-results.json` içine yazılır.

Testler HTTP sayfalarını, görsel yollarını, arama ve filtreleri, yetkilendirmeyi, CSRF korumasını, yayınlama/arşiv/sürüm işlemlerini, iletişim kaydını, yükleme doğrulamasını, yedek içe/dışa aktarmayı ve oturum geçersizleştirmeyi kapsar. Mac üzerinde Chrome/Playwright ile 320–1920 pikselde 143 genel sayfa/ekran kontrolü tamamlandı. Yeni yayın rehberi dahil 42 panel ekranı, ilgili alana geçiş, odak ve bilgi kaydetme akışı da ayrı geçici veritabanında doğrulandı.

Mac'te kurulu Chrome ile tarayıcı testleri:

```bash
HUKUK_BROWSER_CHANNEL=chrome npm run test:browser
```

Vercel statik dosya yönlendirmeleri `npm run deploy:check` içine dahildir. Bulut taşıma ve SVG dosyalarının sunulması, ayrı bir yerel PostgreSQL kurulumu ile `npm run test:cloud` üzerinden test edilir. PostgreSQL araçları PATH'te değilse örneğin `HUKUK_PG_BIN=/opt/homebrew/opt/postgresql@16/bin npm run test:cloud` kullanılabilir. Bu test yeni bir geçici PostgreSQL sunucusu başlatır; mevcut veritabanına bağlanmaz.

## Görseller ve içerik kaynakları

- [Görsel dosyaları, üretim yöntemi ve tam istemler](GORSELLER.md)
- Tasarım referansı: [Kadim Hukuk](https://kadimhukuk.com.tr/)
- Aydınlatma metni için incelenen birincil kaynak: [KVKK — Aydınlatma Yükümlülüğü](https://www.kvkk.gov.tr/Icerik/2033/Aydinlatma-Yukumlulugu-)
- Mesleki sunum için incelenen kaynak: [Türkiye Barolar Birliği — Reklam Yasağı Yönetmeliği](https://d.barobirlik.org.tr/mevzuat/avukata_ozel/yonetmelikler/2011/reklam_yas_yon.pdf)

Özgün şablonun kullanım lisansı kullanıcı tarafından sağlanan dosyalara bağlıdır. Bu çalışma şablon için ayrıca lisans satın alındığı veya hukuki uygunluk denetimi tamamlandığı anlamına gelmez.
