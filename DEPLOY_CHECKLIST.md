# FikrimVar V8 Deploy Checklist

## Web root

- Canli sunucuda document root dogrudan `public/` olmalidir.
- `app/`, `config/`, `tools/`, `admin-local/`, `storage/`, `_backups/`, `.git/` web root disinda kalmalidir.
- Eger hosting zorunlu olarak proje kokunu web root yaparsa kok `.htaccess` korumalari aktif olmalidir.
- Proje koku web root olursa `/.htaccess` hassas klasorleri ve dosyalari kapatir.
- `public/` web root olursa `/public/.htaccess` directory listing, dotfile ve yedek/db uzantilarini kapatir.

## Ortam ayari

- Uygulama kodu local ve canli icin ayni kalmalidir.
- `config/config.php` once ortam degiskenlerini ve `config/local.php` dosyasini okur.
- `config/local.php` yoksa local XAMPP icin `C:/xampp/acetinweb_private/storage`, canli Plesk icin proje kokunun yanindaki `acetinweb_private/storage` yolu otomatik denenir.
- Canlida tercih edilen DB yolu: `/var/www/vhosts/acetin.com.tr/acetinweb_private/storage/fikrimvar.sqlite`.
- Ortama ozel fark gerekiyorsa sadece `config/local.php` veya ortam degiskeni degisir; uygulama dosyalari degismez.

## Private storage

- SQLite dosyasi public altinda olmamalidir.
- Canli `config/local.php` sunucuya ozel olusturulmalidir ve Git'e alinmamalidir.
- `storage_path` public disinda bir dizini gostermelidir.
- DB yedekleri public disinda, private storage altinda tutulmalidir.

## Admin

- `admin-local/` canliya yuklenmemesi tercih edilir.
- Yuklenecekse HTTPS, IP kisiti ve local/admin erisim politikasi ile korunmalidir.
- Admin login canli domainde herkese acik gorunmemelidir.
- Canliya cikmadan once admin sifresi degistirilmelidir.

## System check

- `public/system-check.php` yalnizca local loopback IP'lerinden calismalidir.
- Canli domainde 403 veya `system-check disabled` mesaji donmelidir.
- Canli ortamda DB yolu, storage yolu veya server bilgisi sizdirmamalidir.

## Uploads ve medya

- `public/uploads/` siteyle birlikte yedeklenmelidir.
- `public/uploads/.htaccess` calistirilabilir dosyalari engellemelidir.
- Kucuk gorseller ve kapaklar siteye yuklenebilir.
- Buyuk MP4 ve uzun ses dosyalari mumkunse YouTube, Vimeo, SoundCloud veya CDN uzerinden baglanmalidir.

## Medya ve buyuk dosya stratejisi

Canli kullanimda #FikrimVar sunucusu hikayeyi, kucuk kanit gorsellerini ve temel kapaklari tasimalidir. Buyuk video ve uzun ses dosyalari mumkun oldugunca harici platform veya CDN uzerinden baglanmalidir.

Site icine yuklenebilir:

- Kapak gorselleri.
- Kucuk WebP/JPG/PNG gorseller.
- Hikaye icinde gerekli ekran goruntuleri.
- Kisa PDF/TXT belgeleri.
- Kucuk ses veya video ornekleri.

Link olarak eklenmesi onerilir:

- Buyuk MP4 dosyalari.
- Uzun ses kayitlari.
- Sosyal medya videolari.
- YouTube, Instagram, Vimeo, SoundCloud veya CDN icerikleri.
- Sik degisen veya cok buyuk proje ciktilari.
- Arsiv niteligi tasiyan ama site performansini, kotayi veya yedeklemeyi zorlayacak dosyalar.

Buyuk dosyalari dogrudan `public/uploads` altinda tutmak uc maliyet olusturur:

- Yedekleme agirlasir.
- Hosting kotasi ve trafik limiti daha hizli dolar.
- Siteyi baska sunucuya tasimak zorlasir.

Mevcut upload limitleri teknik olarak buyuk medya yuklemeye izin verir: gorsel 20 MB, video 250 MB, diger/ses 40 MB. Bu limitler kisa ve gerekli dosyalar icin yeterlidir; fakat canli icerik stratejisi buyuk MP4 veya uzun ses dosyalarini dogrudan siteye yuklemeyi varsaymamalidir.

Ileride admin medya ekranina buyuk dosya uyarisi eklenmesi onerilir. Ornek uyari: "Buyuk video ve uzun ses dosyalarini mumkunse YouTube, Vimeo, SoundCloud veya CDN uzerinden baglayin."

## Git ve hassas dosyalar

- `config/local.php`, `.env`, SQLite, DB yedekleri, zip yedekler ve private key dosyalari Git'e alinmamalidir.
- Public repo kullaniliyorsa DB veya admin kullanici hash'i repoda bulunmamalidir.
- Daha once DB veya admin hash'i Git'e girdiyse canli oncesi admin sifresi degistirilmeli ve gerekirse yeni admin kullanicisi olusturulmalidir.

## Canli oncesi yedek

- SQLite DB yedegi al.
- `public/uploads/` klasorunu yedekle.
- Canliya yuklenecek dosya listesini kontrol et.
- Gereksiz local backup, zip ve test dosyalarini canli pakete dahil etme.

## Ilk test URL'leri

- `/`
- `/hikayeler.php`
- `/hikaye.php?slug=acetin-com-tr-gelistirmesi`
- `/hikaye.php?slug=webbordro`
- `/system-check.php` beklenen: local disinda 403
- `/.git/config` beklenen: 403 veya 404
- `/app/` beklenen: 403 veya 404
- `/config/` beklenen: 403 veya 404
- `/tools/` beklenen: 403 veya 404
- `/admin-local/` beklenen: canlida 403 veya hic yuklenmemis
