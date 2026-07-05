#FikrimVar V6.6
================

Bu sürüm V6.5'in çalışan Hero yapısını ve organik çatlak/tel hareketlerini korur.
Ana yenilik, Atölye ile Hikâye ayrımını tek proje yaşam döngüsü altında toplayan
yerel yönetim sistemidir.

TEMEL KARAR
-----------

Atölye ve Hikâye artık iki ayrı içerik değildir:

- Atölye: proje sürerken eklenen ham kayıtlar, denemeler ve kararlar.
- Hikâye: aynı projenin seçilmiş dönüm noktalarından düzenlenmiş anlatımı.

Bir proje aynı anda yayımlanmış bir hikâyeye ve yeniden açılmış bir Atölyeye sahip
olabilir. Ham Atölye kayıtları hiçbir zaman hikâyeye dönüştürülürken silinmez.

KURULUM
-------

Klasörü XAMPP htdocs altına kopyala:

C:\xampp\htdocs\acetin_fv\fikrimvar_v6_6

Site:

http://localhost/acetin_fv/fikrimvar_v6_6/

Yönetim:

http://localhost/acetin_fv/fikrimvar_v6_6/admin/

İlk girişte yönetim şifresi oluşturma ekranı açılır. İlk şifre kurulumu güvenlik
nedeniyle yalnızca localhost üzerinden yapılabilir. Şifre açık metin olarak değil,
password_hash() çıktısı olarak data/admin-auth.json dosyasında saklanır.

YÖNETİM SİSTEMİ
---------------

/admin/ altında şu işlemler bulunur:

1. Yeni proje oluşturma
   - Atölye olarak başlat
   - Hikâye taslağı olarak başlat
   - Yalnızca taslak proje oluştur

2. Proje bilgilerini düzenleme
   - Başlık, soru, özet, kategori ve etiketler
   - Atölye durumu: yok / açık / beklemede / kapandı
   - Hikâye durumu: yok / taslak / yayında
   - Ana sayfada gösterme ve Atölyeyi sabitleme
   - Kapak görseli veya videosu yükleme

3. Atölye kaydı ekleme
   - Tarih ve aşama
   - Denediğim / Çalışmayan / Kararım / Sıradaki
   - Dönüm noktası işareti
   - Görsel, video veya ses yükleme
   - Instagram, YouTube ve GitHub bağlantıları

4. Atölyeyi kapatıp Hikâye taslağı oluşturma
   - Bu hâliyle bitti
   - Yarım bıraktım
   - Beklemeye aldım
   - Başka projeye dönüştü
   - Ham kayıtlardan dönüm noktalarını seçme
   - Açılış, zaman çizgisi, kapanış ve öğrenilenler bloklarını otomatik üretme

5. Hikâyeyi düzenleme ve yayımlama
   - Hikâye başlığı, özet ve okuma süresi
   - Mevcut blok motoruyla uyumlu JSON blok editörü
   - Taslak / yayında durumu

6. Site ve Atölye ayarları
   - Ana sayfada sabitlenen açık Atölye
   - Atölye açılır penceresi
   - Son Hareketler kayıt sayısı

PROJE KLASÖRÜ
-------------

Mevcut URL yapısını bozmamak için klasör adı content/stories olarak korunmuştur;
fakat her klasör artık tek bir proje kaydıdır:

content/stories/<slug>/
├── project.json   Ana yaşam döngüsü ve proje bilgileri
├── story.json     Düzenlenmiş Hikâye blokları
├── updates/       Ham Atölye kayıtları
└── media/         Görseller, videolar ve sesler

project.json içindeki iki ayrı durum alanı:

"workshop": {
  "status": "open"
}

"story": {
  "status": "draft"
}

Bu sayede Atölye ile Hikâye birbirine karışmadan aynı projenin iki görünümü olur.

ATÖLYE KAPANDIĞINDA
-------------------

Yönetim ekranındaki “Atölyeyi kapat” işlemi:

1. Atölye durumunu closed yapar.
2. Kapanış kararını ve tarihi kaydeder.
3. Seçilen milestone kayıtlarından story.json taslağı oluşturur.
4. Hikâyeyi draft durumuna getirir.
5. Sabitlenen Atölyeyse ana sayfa sabitlemesini kaldırır.
6. updates/ altındaki bütün ham kayıtları olduğu gibi korur.

Hikâye yayımlandıktan sonra:

- hikaye.php düzenlenmiş anlatıyı gösterir.
- atolye.php eski ham günlüğü “Atölye Arşivi” olarak göstermeye devam eder.
- Proje daha sonra yeniden Atölye durumuna alınabilir; yayımlanmış Hikâye silinmez.

DOSYA GÜVENLİĞİ
---------------

JSON yazımları doğrudan mevcut dosyanın üzerine yapılmaz:

1. Veri geçici dosyaya yazılır.
2. JSON doğrulanır.
3. Mevcut dosyanın yedeği data/backups/ altına alınır.
4. Geçici dosya gerçek dosyanın yerine taşınır.

data/.htaccess yönetim şifresini ve not dosyalarını web erişimine kapatır.
content/.htaccess JSON dosyalarının doğrudan tarayıcıdan okunmasını engeller;
medya dosyaları erişilebilir kalır.

SUNUCU İZİNLERİ
---------------

Canlı sunucuya taşırken PHP kullanıcısının şu klasörlere yazabilmesi gerekir:

- content/stories/
- data/

Paylaşımlı hostingde sağlayıcının önerdiği izinleri kullan. Genellikle 775 yeterlidir;
777 kullanma.

HERO
----

V6.5'te bulunan düzeltme bu sürüme dahildir:

.hero-core--organic {
  aspect-ratio: 2048 / 1117;
}

Bu kural organik katman kapsayıcısına gerçek yükseklik verir. Üç çatlak maskesi,
üç tel katmanı, rastgele parlamalar, geniş yörüngeler ve büyük Hero konumu korunur.

KOMUT SATIRI ARACI
------------------

Yönetim paneli ana yöntemdir. İstenirse yerelde komut satırı da kullanılabilir:

php tools/new_story.php yeni-proje "Yeni Proje" workshop
php tools/new_story.php yeni-hikaye "Yeni Hikâye" story
php tools/new_story.php yeni-taslak "Yeni Taslak" draft

TESTLER
-------

- Tüm PHP dosyaları php -l ile kontrol edildi.
- app.js node --check ile kontrol edildi.
- Bütün JSON dosyaları doğrulandı.
- Ana sayfa, Bütün Hikâyeler, WebBordro ve Atölye sayfaları HTTP 200 döndürdü.
- Hero organik kapsayıcısının 0 px olmadığı doğrulandı.
- Çatlak opacity değerlerinin ve tel transform değerlerinin zamanla değiştiği ölçüldü.
- 1600 px ve 390 px genişliklerde yatay taşma kontrol edildi.
- Yönetim sistemi ayrı bir test kopyasında uçtan uca denendi:
  şifre kurulumu → proje oluşturma → Atölye kaydı ekleme → Atölyeyi kapatma →
  Hikâye taslağı üretme → Hikâyeyi yayımlama.
- Her kayıt sırasında otomatik yedek dosyalarının oluştuğu doğrulandı.

BU SÜRÜMDE YOK
--------------

- E-posta bülteni ve otomatik bildirim gönderimi
- Sürükle-bırak görsel blok editörü
- Çok kullanıcılı yönetim ve roller

Veri modeli, ileride yalnızca milestone:true kayıtlarında veya haftalık özet şeklinde
e-posta gönderecek sisteme genişletilebilir.
