#FikrimVar V6.5
================

Bu sürüm V6.1'in çalışan Hero yapısını korur. Organik çatlak maskeleri,
yavaş salınan tel katmanları, büyütülmüş Hero görseli ve geniş yörüngeler
paketin içinde hazırdır.

V6.5 ile gelen ana değişiklikler
--------------------------------

1. Atölye artık ana sayfaya gömülü büyük bir bölüm değildir.
   Sağ alttaki "ATÖLYE AÇIK" düğmesi ile bağımsız bir bülten/pencere olarak açılır.
   Mobilde alttan açılan bir yüzey hâline gelir.

2. Ana sayfadaki Son Hareketler alanı korunur.
   Atölye kaydı, Instagram, YouTube ve GitHub bağlantıları aynı kayıt üzerinde
   buluşabilir.

3. Atölye sayfası 70-80 günlük projelerde uzayıp dağılmasın diye üç katmanlıdır:
   - Şu anki durum
   - Yalnızca önemli Dönüm Noktaları
   - Fazlar altında açılıp kapanan bütün ham günlük arşivi

4. Bir günün ana anlatıda görünmesi için update JSON'una:

   "milestone": true

   yazılır. Küçük ara notlar false olabilir. Hepsi arşivde saklanır.

5. Kayıtları dönemlere ayırmak için:

   "phase": "Yön değişikliği"

   alanı kullanılır. Aynı phase değerine sahip kayıtlar tek açılır grubun altında
   toplanır.

6. Her kayıt için isteğe bağlı sosyal bağlantılar:

   "instagram_url": ""
   "youtube_url": ""
   "github_url": ""

   Boş bırakılan bağlantılar görünmez.

Kurulum
-------

Klasörü XAMPP htdocs altına kopyala:

C:\xampp\htdocs\acetin_fv\fikrimvar_v6_5

Tarayıcı:

http://localhost/acetin_fv/fikrimvar_v6_5/

Atölye ayarı
------------

content/site.json içindeki:

"homepage": {
  "pinned_atelier": "gorselden-harekete"
}

ana sayfada ve açılır Atölye penceresinde hangi atölyenin kullanılacağını belirler.

Aynı dosyadaki:

"atelier_widget": {
  "enabled": true,
  "status": "open",
  "floating": true,
  "auto_open": false
}

ile pencereyi açıp kapatabilirsin. enabled false olduğunda düğme ve panel görünmez.

Yeni hikâye / atölye
--------------------

php tools/new_story.php yeni-hikaye "Yeni Hikâye" story
php tools/new_story.php yeni-atolye "Yeni Atölye" atelier

Atölye komutu updates/001.json dosyasını milestone, phase, tarih ve sosyal bağlantı
alanlarıyla birlikte hazırlar.

Kontrol edilenler
-----------------

- Tüm PHP dosyaları php -l ile kontrol edildi.
- app.js node --check ile kontrol edildi.
- Bütün JSON dosyaları doğrulandı.
- 1600, 1100 ve 390 piksel genişliklerde yatay taşma kontrol edildi.
- Hero organik katmanlarının z-index ve görünürlük değerleri doğrulandı.
- Atölye penceresi açma/kapama test edildi.
- Dönüm noktası seçildiğinde ana sahnenin değişmesi test edildi.
- Atölye arşivi fazlar hâlinde kapalı/açık çalışıyor.

Not
---

E-posta bildirimi bu sürümde yoktur. Veri modeli ileride yalnızca milestone:true
olan kayıtlarda bildirim gönderecek şekilde genişletilebilir.
