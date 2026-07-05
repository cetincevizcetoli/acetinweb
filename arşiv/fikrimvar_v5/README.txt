# #fikrimvar V5

Bu sürümün merkez cümlesi:

“Bu site Ahmet Çetin’in yaptığı işlerin portfolyosu değil; yapay zekâyla tanıştıktan sonra aklına gelenleri denemeye başlayan bir insanın açık çalışma günlüğüdür.”

## V5’te değişen ana fikir

- Hero teknik ve sanatsal atmosferini korur.
- Hero’nun hemen ardından Ahmet’in kişisel başlangıç hikâyesi gelir.
- Mehmet Fırat hocanın rolü dipnot değil, anlatının ilk kırılma noktasıdır.
- Üretim Takip Sistemi ilk yaşayan proje olarak ana anlatıya bağlanır.
- WebBordro, Invoke/Flow ve ai-context “en iyi işler” değil, şu anda açık üç hikâye olarak görünür.
- Diğer işler kutu yığını yerine yatay bir çalışma günlüğü şeridinde görünür.
- Bütün kayıtlar hikayeler.php içinde filtrelenebilir.
- WebBordro için ilk tam görsel hikâye prototipi hikaye.php?slug=webbordro adresinde hazırdır.

## WebBordro hikâye prototipi

Uzun makale yerine üç okuma derinliği kullanır:

1. 30 saniye: başlıklar, görseller ve hızlı bilgiler
2. 3 dakika: kısa editoryal hikâye
3. Teknik: açılır soru/cevap bölümleri

Hikâyenin doğrulanmış omurgası:

- Eşinin şirketindeki gerçek ihtiyaç
- İlk Python masaüstü sürümü
- PHP masaüstü sürümünün şirkete kurulması
- Basit hesap sayfası olarak başlayan WebBordro
- Daha sade ihtiyaca cevap veren OOP tabanlı DeltaBordro

## Kurulum

Klasörü XAMPP htdocs altına kopyalayın ve:

http://localhost/fikrimvar_v5/

adresinden açın.

PHP 8+ önerilir. Herhangi bir framework veya build adımı yoktur.

## Temel dosyalar

- index.php: V5 ana sayfa
- hikaye.php: görsel çalışma hikâyesi şablonu
- hikayeler.php: bütün kayıtlar
- proje.php: kısa/generic proje kaydı
- data/site.json: ana sayfa metinleri ve yapı
- data/projects.json: bütün proje kayıtları
- data/stories.json: uzun görsel hikâyeler
- assets/css/style.css: tasarım ve responsive yapı
- assets/js/app.js: reveal, parallax, filtreleme, mobil menü ve kenar notları

## Görseller

Gerçek görseller henüz bulunmayan yerlerde ziyaretçiye “geçici görsel” yazısı göstermeyen, projeye özel SVG sahneleri kullanıldı. Gerçek ekran görüntüleri ve üretim görselleri geldikçe aynı dosya yolları korunarak değiştirilebilir.

## Kenar notları

Yorumlar data/notes-pending.json dosyasına moderasyon bekleyen kayıt olarak yazılır. Hosting ortamında data klasörünün PHP tarafından yazılabilir olması gerekir.
