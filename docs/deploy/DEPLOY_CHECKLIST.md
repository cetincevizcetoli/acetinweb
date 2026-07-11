# FikrimVar V8 Deploy Checklist

## Web root

- Canli sunucuda domain document root `httpdocs/` olmalidir.
- Localde `C:\xampp\htdocs\acetinweb\` neyse canlida `httpdocs/` odur.
- `app/`, `config/` ve `storage/` canlida `httpdocs` disindaki `acetinweb_private/` altinda durmalidir.
- Kullanici `https://www.acetin.com.tr/` actiginda site acilmali, URL'de `/public/` gorunmemelidir.
- `app/`, `config/`, `tools/`, `admin-local/`, `storage/`, `_backups/`, `.git/` web root disinda kalmalidir.
- Eger hosting zorunlu olarak proje kokunu web root yaparsa kok `.htaccess` korumalari aktif olmalidir.
- Proje koku web root olursa `/.htaccess` hassas klasorleri ve dosyalari kapatir.
- Koku web root yapmak ikinci tercihtir. Bu durumda kok `.htaccess` public klasorune internal rewrite yapar ve hassas klasorleri deny eder.
- Canlida kok `.htaccess` dosyasi `httpdocs/.htaccess` olarak yer alir; directory listing, helper dosyalari, dotfile ve yedek/db uzantilarini kapatir.

## Ortam ayari

- Uygulama kodu local ve canli icin ayni kalmalidir.
- `config/config.php` once ortam degiskenlerini ve `config/local.php` dosyasini okur.
- Canlida `config/config.php` yolu `acetinweb_private/config/config.php` olmalidir.
- `config/local.php` yoksa local XAMPP icin `C:/xampp/acetinweb_private/storage`, canli Plesk icin proje kokunun yanindaki `acetinweb_private/storage` yolu otomatik denenir.
- Canlida tercih edilen DB yolu: `/var/www/vhosts/acetin.com.tr/acetinweb_private/storage/fikrimvar.sqlite`.
- Ortama ozel fark gerekiyorsa sadece `config/local.php` veya ortam degiskeni degisir; uygulama dosyalari degismez.
- Yayin Merkezi canli kontrol adresi ve canli DB hedef yolu da ortama ozeldir. Bunlar `deploy_remote_manifest_url` ve `deploy_live_db_target` degerleriyle `config/local.php` icinden veya ortam degiskenlerinden verilebilir.

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

- Sistem kontrolu public kokte tutulmaz; localde admin panelde `admin-local/system.php` kullanilir.
- Canli domainde 403 veya `system-check disabled` mesaji donmelidir.
- Canli ortamda DB yolu, storage yolu veya server bilgisi sizdirmamalidir.

## Deploy manifest

- `deploy-manifest.json` public URL'de acilabilir ama sade olmalidir.
- Public manifest DB hash'i, DB boyutu, local/sunucu path, `app/`, `config/` veya detayli dosya listesi icermemelidir.
- Detayli manifest sadece local/admin tarafinda ve private storage altinda tutulur.
- Canli kontrol icin public manifestte yalnizca version, generated_at, release_hash ve public_assets_hash gibi ozet alanlar kalmalidir.

## Robots ve sitemap stratejisi

- `robots.txt` sik degisen icerik icin elle guncellenmez; sabit kalir ve sadece arama motorlarina genel izin ile sitemap adresini bildirir.
- Hikaye yayinlama/kaldirma gibi sik degisen kararlar `robots.txt` ile degil, sitemap uretimi ve sayfanin kendi 404/200 davranisi ile yonetilir.
- `sitemap.xml` elle tutulan statik liste olmamalidir. Yalnizca public gorunen, yayimlanmis ve Hikayeler sayfasinda gosterilen iceriklerden otomatik uretilmelidir.
- Bir hikaye yayindan kalkarsa sitemap sonraki uretimde o URL'i kendiliginden cikarmalidir.
- Botlar icin pratik hedef: robots sabit, sitemap dinamik, public gorunurluk kurali tek kaynak.
- Public adres `/sitemap.xml` olarak kalir; `.htaccess` bunu `sitemap.php` uzerinden dinamik uretime baglar.
- `sitemap.xml` dosyasi yalnizca mod_rewrite kapali kalirsa kullanilacak sade fallback kopyadir.
- Atolye sayfalari simdilik sitemap'e eklenmez. Kanonik SEO listesi Ana sayfa, Hikayeler ve yayinlanmis Hikaye detaylaridir.

## Uploads ve medya

- `uploads/` siteyle birlikte yedeklenmelidir.
- `uploads/.htaccess` calistirilabilir dosyalari engellemelidir.
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

Buyuk dosyalari dogrudan `uploads` altinda tutmak uc maliyet olusturur:

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
- `uploads/` klasorunu yedekle.
- Canliya yuklenecek dosya listesini kontrol et.
- Gereksiz local backup, zip ve test dosyalarini canli pakete dahil etme.

## Canli dosya ayrimi

Canliya yukle:

- Kok site dosyalari -> `httpdocs/`
- `app/` -> `acetinweb_private/app/`
- `config/config.php` -> `acetinweb_private/config/config.php`
- `config/local.example.php` -> `acetinweb_private/config/local.example.php`
- `uploads/` -> `httpdocs/uploads/`
- sade `deploy-manifest.json` -> `httpdocs/deploy-manifest.json`

Canlida elle olustur:

- `acetinweb_private/config/local.php`
- `acetinweb_private/storage/`
- `fikrimvar.sqlite`
- backup klasoru
- gerekli dosya izinleri

Canliya yukleme:

- `.git/`
- `config/local.php`
- `.env`
- SQLite/DB/backup/zip/sql dosyalari
- `admin-local/` canlida kullanilmayacaksa
- `tools/`
- `mocap/`
- `archive/` veya `arsiv/`
- gelistirme dokumanlari ve prompt dosyalari

## Ilk test URL'leri

- `/`
- `/hikayeler.php`
- `/hikaye.php?slug=acetin-com-tr-gelistirmesi`
- `/hikaye.php?slug=webbordro`
- `/system-check.php` beklenen: 404
- `/.git/config` beklenen: 403 veya 404
- `/app/` beklenen: 403 veya 404
- `/config/` beklenen: 403 veya 404
- `/tools/` beklenen: 403 veya 404
- `/admin-local/` beklenen: canlida 403 veya hic yuklenmemis
