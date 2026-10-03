# Siteyi Mac'e taşıma

1. Windows'ta açık site sunucusunu çalıştığı terminalde **Ctrl+C** ile durdurun. Sonra `Hukuk` klasörünün tamamını USB'ye kopyalayın. Özellikle `storage/site.sqlite` ve `assets/uploads/` klasörünün kopyada bulunduğunu kontrol edin. `storage/admin-access.txt` ilk kurulum parolasını içerebildiği için USB'yi güvenli saklayın.
2. Mac'te USB'deki klasörü önce bilgisayarın yerel diskine kopyalayın; siteyi USB üzerinde çalıştırmayın. İlk kopyayı ayrıca yedek olarak koruyun. Mac'te VS Code ile bu klasörü açın.
3. Mac'te **PHP 8.2 veya üzeri** ve `pdo_sqlite`, `mbstring`, `gd` eklentileri bulunmalı. VS Code tek başına PHP uygulamasını çalıştırmaz. Terminalde `php -v` ve `php -m` ile kontrol edin.
4. VS Code terminalinde `Hukuk` klasörüne geçip `bash baslat-mac.sh` çalıştırın. Tarayıcıda `http://127.0.0.1:8088/` ve yönetim paneli için `http://127.0.0.1:8088/admin/` adreslerini açın. Port doluysa `bash baslat-mac.sh 8090` kullanın. Durdurmak için **Ctrl+C**.

Mevcut veritabanı taşındığı için yeniden kurulum komutu çalıştırmanız gerekmez. Yönetici hesabı ve mevcut parola veritabanıyla birlikte gelir. `storage/admin-access.txt` yalnızca ilk oluşturulan parolayı gösterebilir; parolayı sonradan değiştirdiyseniz güncel parolanızı kullanın.

`baslat.ps1` Windows'a özeldir. `bash baslat-mac.sh` veri dosyasını bulamazsa yeni, boş bir site açmadan durur. Sitenin dinamik sürümünü VS Code Live Server veya `index.php` dosyasına çift tıklayarak açamazsınız; PHP sunucusu gerekir. `git clone` veya yalnızca takip edilen dosyaları kopyalama yöntemi de yeterli değildir: `storage/` ve `assets/uploads/` Git tarafından dışlanır.

İki bilgisayarda ayrı ayrı yapılan değişiklikler kendiliğinden birleşmez. Mac'te düzenlemeye başladıktan sonra Windows'taki klasörü Mac kopyasının üzerine yazmayın; önce güncel klasörün tam yedeğini alın. Bu adımlar yerel kullanım içindir; internette yayınlama ayrıca sunucu kurulumu gerektirir.
