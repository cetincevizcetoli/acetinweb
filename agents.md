# FikrimVar V7 Proje Kuralları

## Projenin amacı

Bu site Ahmet Çetin'in portfolyosu veya satış sitesi değildir.

#FikrimVar; yapay zekâyla tanıştıktan sonra kod, görsel üretim,
animasyon, müzik, yöntem ve gerçek iş ihtiyaçları üzerinde yaptığı
denemelerin açık çalışma günlüğüdür.

Başarılar, başarısızlıklar, yarım kalan işler ve değişen kararlar
aynı sistemde yaşar.

## Teknik yapı

- PHP
- SQLite / PDO
- Vanilla JavaScript
- Vanilla CSS
- XAMPP / Windows
- Framework ve build sistemi yok
- Public web kökü: public/
- Yerel yönetim paneli: admin-local/
- Veritabanı: config/local.php içindeki db_path ile belirlenen htdocs dışı SQLite dosyası

## Kesinlikle korunacaklar

- #FikrimVar yazımı, F ve V büyük olacak.
- Çalışan Hero kompozisyonu korunacak.
- Hero çatlak parlama katmanları korunacak.
- Organik tel hareketleri korunacak.
- Geniş yörünge geometrisi korunacak.
- .hero-core--organic aspect-ratio düzeltmesi kaldırılmayacak.
- Atölye ile Hikâye aynı projenin iki yaşam evresi olarak kalacak.
- Admin kullanıcıya ham JSON göstermeyecek.
- Medya dosyaları dosya sisteminde, ilişkileri SQLite içinde kalacak.
- admin-local canlı public web köküne taşınmayacak.

## Güvenlik kuralları

- Veritabanını silme, sıfırlama veya örnek veriyle değiştirme.
- Gerçek veritabanı üzerinde migration öncesi yedek oluştur.
- SQL sorgularında PDO prepared statements kullan.
- Admin POST işlemlerinde CSRF kontrolü kullan.
- Dosya yüklemelerinde MIME doğrulaması yap.
- Kullanıcı tarafından gönderilen dosya adını doğrudan kullanma.
- HTML çıktılarında uygun escaping uygula.
- Parolaları, gizli anahtarları veya veritabanı içeriğini rapora yazma.

## Çalışma yöntemi

- İlk görevde yalnızca inceleme yap. Dosya değiştirme.
- Her bulguyu dosya ve mümkünse satır bilgisiyle destekle.
- Tahmin ile doğrulanmış hatayı açıkça ayır.
- Kod değiştirmeden önce uygulanacak planı sun.
- Değişiklikleri küçük ve geri alınabilir gruplara böl.
- Bir sorunu düzeltirken ilgisiz dosyaları değiştirme.
- Her değişiklikten sonra ilgili sayfaları ve PHP/JS sözdizimini test et.
- CSS veya Hero değişikliğinde masaüstü ve mobil görünümü kontrol et.

## Temel kullanıcı akışı

Proje oluşturulur.
Atölye açılır.
İstenilen sıklıkta çalışma kayıtları eklenir.
Kayıtlara birden fazla görsel, video ve bağlantı bağlanabilir.
Önemli kayıtlar dönüm noktası olarak seçilir.
Atölye kapatılır.
Seçilen dönüm noktalarından Hikâye taslağı oluşturulur.
Hikâye düzenlenip yayımlanır.
Ham Atölye kayıtları korunur.

## Yayın kontrolü

Her proje ve Hikâye için şu kontroller ayrı olmalıdır:

- durum
- görünürlük
- yayımlanma
- ana sayfada gösterme
- arşivde gösterme
- sabitleme
- manuel sıralama
- çöp kutusu / geri yükleme
