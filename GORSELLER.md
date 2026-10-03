# Projeye özel görseller

İlk tasarımdaki üç görsel yerleşik **image_gen / imagegen** aracıyla üretildi. CLI/API kullanılmadı. Görseller gerçek bir büroyu veya belirli bir adliye binasını temsil etmez. Aşağıdaki tablo ilk tasarımın dosya adlarını gösterir; güncel anasayfa varlıkları belgenin sonundaki “Güncel Yurtdaş görsel kimliği” bölümündedir.

| Kullanım | Web dosyası | Asıl dosya |
|---|---|---|
| Ana sayfa karşılama alanı | `assets/images/justice-hero.jpg` | `assets/images/justice-hero.png` |
| Kurumsal bölüm / yayın görseli | `assets/images/architecture.jpg` | `assets/images/architecture.png` |
| Makale kapak görseli | `assets/images/library.jpg` | `assets/images/library.png` |

Logo işareti ve site simgesi kodla oluşturulmuş özgün SVG çizimleridir (`app/bootstrap.php`, `assets/images/favicon.svg`).

## Kullanılan tam istemler

### Ana görsel

> Use case: photorealistic-natural. Asset type: full-width hero background photograph for a sophisticated Turkish law firm's website. Create an exceptionally refined editorial architectural photograph, panoramic landscape 1792x1024 or similar. Subject: an antique bronze Lady Justice statue with blindfold and carefully formed scales, positioned on the far right third, viewed from waist up; imposing fluted classical courthouse stone columns softly out of focus behind it on right. Left half of image mostly empty, deeply shadowed navy-charcoal architectural surface, subtle atmospheric texture, suitable for overlaying large white headline. Warm muted champagne light comes from upper right and catches bronze sculpture and stone column edges. Near-monochromatic midnight navy, warm stone, desaturated brass palette. Rich photographic grain, very realistic museum-quality sculpture, sophisticated cinematic lighting, restrained premium editorial mood. No text, letters, logos, watermark, people, interface, borders, gavels, or books. Avoid bright orange or oversaturated gold. Single coherent photograph, not collage.

### Mimari detay

> Use case photorealistic-natural. Original editorial architectural photo for a refined Turkish law firm's website. Vertical leaning landscape 4:3 composition of imposing classical pale limestone courthouse columns and elegant steps, close-up architectural study with rich stone textures, oblique view and strong perspective looking slightly upward, warm natural side light, deep charcoal shadows, muted ivory and warm beige palette, sophisticated fine art architectural photography. No identifiable landmarks, no flags, no signage, no text, no logos, no people, no watermark. Realistic physical architecture. Premium quiet timeless atmosphere.

### Kitaplar

> Use case photorealistic-natural. Landscape 3:2 editorial still life for an elegant law website article. Neatly arranged antique leather bound law books in warm brown and dark navy on a dark oak desk, opened book in foreground, beautiful indirect warm window light from left, dark bookcase subtly out of focus in background, refined scholarly atmosphere, restrained warm muted colors, tactile leather and paper texture, cinematic but natural photographic detail. No legible book titles, no lettering, no text, no watermark, no logos, no people, no gavels. Focus is the books, not the background.

## Güncel Yurtdaş görsel kimliği — 3 Ekim 2026

- Başlangıç görseli: `assets/images/law-hero-light-v1.webp`. Anasayfanın karşılama bölümündedir.
- **Büro hakkında için ayrı görsel:** `assets/images/law-about-study-v1.webp` (1200 × 960); PNG aslı `assets/images/law-about-study-v1.png`. Yerleşik **imagegen** ile üretildi, CLI/API kullanılmadı. Gerçek büro fotoğrafı olarak sunulmaz; alt metni temsili hukuk çalışma masasını tarif eder.
- Yeni dairesel mühür: `assets/images/yurtdas-seal-v2.svg`; yatay logo: `assets/images/yurtdas-logo-v2.svg`; küçük ekran/sekme simgesi: `assets/images/yurtdas-favicon-v2.svg`. Kullanıcının gönderdiği [referansın](https://www.yildizhukuk.av.tr/upload/genel-resimler/yagldagz-hukuk-logo.jpg) dairesel yazı, terazi ve iki renkli dal düzeni Yurtdaş ismiyle yeniden çizildi. Bunlar kodla üretilen vektörlerdir. Yazılar font bağımlılığı olmadan yol olarak saklanır. Tekrar üretmek için `node tools/prepare-yurtdas-seal.mjs`.
- Logoyu değiştirmek: **Site Ayarları → Büro Kimliği → Üst menü logosu**. Anasayfa ve sayfa geçişinde ayrı simge kullanmak için aynı bölümde **Ana görsel ve geçiş logosu**. Boş bırakılan alanlar varsayılan kimliği kullanır. Medya kütüphanesine güvenli SVG, PNG, JPG veya WebP yüklenebilir.
- Fotoğrafı değiştirmek: **Site Ayarları → Ana Sayfa → Tanıtım görseli**; karşılama fotoğrafı aynı bölümdeki **Ana görsel** alanıdır.

Yeni Büro hakkında görselinin tam üretim istemi:

> Use case: photorealistic-natural. Asset type: secondary About section photograph for Yurtdaş Hukuk, a lawyer's informational website in Turkey. Create a new original editorial photograph, landscape 5:4 framing, distinct from the homepage's pale gray legal still-life. Subject: close-up of an orderly wooden desk with a single open legal book without legible text, a dark navy closed book, a fountain pen and a small elegant brass balance scale. Background a softly blurred neutral bookshelf, restrained daylight from a side window. Warm natural wood, clean soft gray background, dark navy details, dignified and calm, realistic texture and physically plausible objects. Balanced composition with scale on the right, open book foreground on the left, no large empty area needed. This is a conceptual legal study photograph, not a depiction of any particular real office. No people, no portraits, no signage, no logos, no visible letters or text, no watermarks, no gavel, no flags.
