#FikrimVar V7.0.0
==================

Bu sürümde JSON ana veri kaynağı olmaktan çıkarıldı. Proje, Atölye kaydı,
Hikâye, medya, bağlantı, görünürlük ve sıralama bilgileri SQLite veritabanında
saklanır.

V7'nin temel ilkesi
-------------------
Atölye ve Hikâye iki ayrı içerik değildir.

Proje
  -> Atölye açık
  -> İhtiyaç oldukça çalışma kayıtları eklenir
  -> Önemli kayıtlar dönüm noktası seçilir
  -> Ahmet "burada bitti" dediğinde Atölye kapatılır
  -> Seçilen dönüm noktalarından Hikâye taslağı oluşturulur
  -> Ham Atölye kayıtları korunur

Klasörler
---------
kok klasor    Ziyaretçinin gördüğü site ve yüklenen medya
admin-local/  Yalnızca yerel bilgisayardan açılan yönetim paneli
app/          SQLite erişimi, sorgular ve içerik çizim motoru
config/       Uygulama ayarları
storage/      Yerel gecici/yedek alan; SQLite yolu config/local.php ile htdocs disina alinir
tools/        Bakım ve kontrol araçları

Kurulum: XAMPP / Windows
------------------------
1. Paketi şu klasöre çıkarın:

   C:\xampp\htdocs\acetinweb

2. XAMPP Control Panel > Apache > Config > PHP (php.ini) dosyasını açın.

3. Aşağıdaki uzantıların başındaki noktalı virgülü kaldırın:

   extension=pdo_sqlite
   extension=sqlite3

4. Apache'yi yeniden başlatın.

5. Önce sistem kontrolünü açın:

   http://localhost/acetinweb/admin-local/system.php

6. Site:

   http://localhost/acetinweb/

7. Yönetim paneli:

   http://localhost/acetinweb/admin-local/

8. İlk açılışta kullanıcı adı ve en az 10 karakterli yönetici parolası oluşturun.
   Pakette hazır parola yoktur.

Yönetim akışı
-------------
Yeni proje:
  Projeler > Yeni proje

Atölye kaydı:
  Projeyi aç > Yeni Atölye kaydı

Bir Atölye kaydına şunlar birlikte eklenebilir:
  - Metin ve çalışma notları
  - Birden fazla görsel
  - Video veya ses dosyası
  - Daha önce yüklenmiş medya
  - YouTube, Instagram, GitHub, web sitesi ve başka bağlantılar
  - Dönüm noktası işareti
  - Taslak / yayımlanmış durumu
  - Herkese açık / bağlantıya özel / gizli görünürlük

Atölyeden Hikâyeye:
  Projeyi aç > Atölyeden hikâye
  - Hikâyeye taşınacak kayıtları seçin
  - Dönüm noktaları önceden işaretli gelir
  - Atölyeyi kapatma kararı verin
  - Hikâye taslağını oluşturun
  - Bölümleri formlarla düzenleyin ve yayımlayın

Yayın ve sıralama
-----------------
Yayın ve sıra ekranından içerikleri sürükleyerek sıralayabilirsiniz.

Bir proje silinmeden:
  - Ana sayfadan kaldırılabilir
  - Arşivden kaldırılabilir
  - Büyük Hikâye veya Masanın Diğer Tarafı alanına alınabilir
  - Gizli ya da bağlantıya özel yapılabilir
  - Atölye penceresinde gösterilebilir
  - Üste sabitlenebilir

Silme önce Çöp Kutusuna taşır. Kalıcı silme ayrı bir işlemdir.

Medya
-----
Dosyalar uploads/projects/<proje-slug>/ altında saklanır.
SQLite dosyanın kendisini değil, yolunu ve özelliklerini tutar.

İzin verilen yüklemeler:
  JPEG, PNG, WebP, GIF, MP4, WebM, MP3, WAV, OGG, PDF, TXT

SVG yönetim panelinden yüklenmez. Mevcut güvenilir SVG'ler taşınmıştır.
Dosya uzantısı kullanıcının verdiği addan değil, doğrulanan MIME türünden üretilir.

Yedek
-----
Yonetim panelindeki Yedek ekrani SQLite veritabaninin guvenli bir kopyasini
config/local.php icindeki storage_path altindaki backups/ klasorune olusturur.

Tam yedek için birlikte saklayın:
  config/local.php icindeki db_path ile belirlenen SQLite dosyasi
  uploads/

Güvenlik ve canlı sunucu
-----------------------
admin-local/ hem Apache "Require local" kuralıyla hem PHP tarafında yalnızca
localhost erişimine kilitlidir.

Bu paket yerel geliştirme için tek klasör hâlindedir. Canlı sunucuda önerilen
kurulum:

  /uygulama/app
  /uygulama/config
  /uygulama/storage
  /uygulama/admin-local   (internete açık document root dışında)
  /public_html            (yalnızca site kökü içeriği)

Canlı sitenin document root'u httpdocs/ olmalıdır. admin-local/, storage/,
app/ ve config/ doğrudan web kökünde bırakılmamalıdır.

Sunucuda yazma izinleri gereken yerler:
  storage/
  uploads/

Hero
----
V6.1'de çalışan büyük çekirdek, çatlak parlamaları, tel salınımları ve geniş
yörüngeler korunmuştur. Organik katmanın yüksekliğini oluşturan kritik kural:

  .hero-core--organic { aspect-ratio: 2048 / 1117; }

İşletim sisteminde "hareketleri azalt" seçeneği açıksa animasyonlar erişilebilirlik
gereği durur.

Mevcut veri
-----------
V7 veritabanına önceki içerikten 19 proje, 19 Hikâye, 61 Hikâye bölümü,
70 alt öğe, 3 Atölye kaydı ve 24 medya kaydı aktarılmıştır.

Teknik not
----------
Bu geliştirme ortamındaki PHP kurulumunda PDO_SQLite sürücüsü bulunmadığı için
SQLite bağlantılı sayfalar burada PHP üzerinden çalıştırılamadı. Buna karşılık:
  - Tüm PHP dosyaları sözdizimi kontrolünden geçti
  - JavaScript dosyaları node --check kontrolünden geçti
  - SQLite integrity_check sonucu ok
  - Yabancı anahtar kontrolünde bozuk ilişki bulunmadı
  - Proje, kayıt, Hikâye, bölüm, medya, sıra ve silme CRUD akışları veritabanı
    kopyası üzerinde sınandı

XAMPP'ta admin-local/system.php, gerekli sürücünün etkin olup olmadığını açıkça
gösterir.
