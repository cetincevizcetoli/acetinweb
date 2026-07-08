# FikrimVar Canli Deploy Plani

Bu plan canliya otomatik yukleme yapmaz. Local ana kaynaktir; canli sunucu
yayin kopyasidir. Canliya cikmadan once canli SQLite dosyasi ve
`uploads/` mutlaka yedeklenir.

## Tercih edilen canli klasor yapisi

Bu projede canli icin tercih edilen model:

```text
/var/www/vhosts/acetin.com.tr/
  httpdocs/
    assets/
    includes/
    uploads/
    index.php
    hikaye.php
    hikayeler.php
    atolye.php
    yorum-kaydet.php
    deploy-manifest.json
    .htaccess

  acetinweb_private/
    app/
    config/
    storage/
      fikrimvar.sqlite
      backups/
      deploy-manifests/
```

`httpdocs` sadece ziyaretcinin gorecegi public yuzdur. `acetinweb_private`
uygulamanin motor odasidir.

## A. Canliya yuklenecekler

Domain document root dogrudan `httpdocs/` olmalidir. Localde
`C:\xampp\htdocs\acetinweb\` altinda gordugun site dosyalari canlida
dogrudan `httpdocs/` icine kopyalanir. URL'de `/public/` gorunmez.

Yuklenecek uygulama dosyalari:

- kok site dosyalari -> `httpdocs/`
- `app/` -> `acetinweb_private/app/`
- `config/config.php` -> `acetinweb_private/config/config.php`
- `config/local.example.php` -> `acetinweb_private/config/local.example.php`

Yuklenecek icerik dosyalari:

- `uploads/` -> `httpdocs/uploads/`
- `uploads/.htaccess` -> `httpdocs/uploads/.htaccess`
- `deploy-manifest.json` -> `httpdocs/deploy-manifest.json`

Not: `deploy-manifest.json` DB hash, DB boyutu, local yol, app/config
dosya listesi veya hassas path bilgisi icermemelidir.

## B. Canlida elle olusturulacaklar

Sunucuda `httpdocs` disinda:

- `acetinweb_private/storage/`
- `acetinweb_private/storage/fikrimvar.sqlite`
- `acetinweb_private/storage/backups/`
- `acetinweb_private/storage/deploy-manifests/`
- `acetinweb_private/config/local.php`

Canliya ozel config:

- `acetinweb_private/config/local.php`

Ornek:

```php
<?php
declare(strict_types=1);

return [
    'public_path' => '/var/www/vhosts/acetin.com.tr/httpdocs',
    'storage_path' => '/var/www/vhosts/acetin.com.tr/acetinweb_private/storage',
    'db_path' => '/var/www/vhosts/acetin.com.tr/acetinweb_private/storage/fikrimvar.sqlite',
    'allow_system_check' => false,
];
```

Permissions:

- PHP kullanicisi SQLite dosyasini okuyup yazabilmeli.
- PHP kullanicisi `uploads/` icine dosya yazabilmeli.
- PHP kullanicisi private storage altinda backup ve deploy log yazabilmeli.

## C. Canliya yuklenmeyecekler

- `.git/`
- `.env`
- `config/local.php`
- SQLite backup dosyalari
- `*.sqlite`, `*.sqlite-*`, `*.db`, `*.bak`, `*.zip`, `*.sql`
- `admin-local/` canlida kullanilmayacaksa
- `tools/`
- `mocap/`
- `archive/` veya `arsiv/`
- `_backups/`
- `TEST_REPORT.txt`
- `README.txt`
- `agents.md`
- `ARCHITECTURE_REFACTOR_CHECKLIST.md`
- `REFACTOR_GIT_RULES.md`
- `SYSTEM_OVERVIEW.md`
- `guven*.txt`
- local prompt, analiz, not ve gecici dosyalari

## D. Canli test sirasi

Public sayfalar:

- `https://www.acetin.com.tr/`
- `https://www.acetin.com.tr/hikayeler.php`
- `https://www.acetin.com.tr/hikaye.php?slug=acetin-com-tr-gelistirmesi`
- `https://www.acetin.com.tr/hikaye.php?slug=webbordro`
- `https://www.acetin.com.tr/hikaye.php?slug=ai-context`

Kapali olmasi gerekenler:

- `https://www.acetin.com.tr/.git/config`
- `https://www.acetin.com.tr/config/local.php`
- `https://www.acetin.com.tr/app/bootstrap.php`
- `https://www.acetin.com.tr/admin-local/login.php`
- `https://www.acetin.com.tr/tools/`
- `https://www.acetin.com.tr/README.txt`
- `https://www.acetin.com.tr/TEST_REPORT.txt`
- `https://www.acetin.com.tr/DEPLOY_CHECKLIST.md`
- `https://www.acetin.com.tr/system-check.php` beklenen: 404

Manifest:

- `https://www.acetin.com.tr/deploy-manifest.json` acilirsa sade JSON olmali.
- DB hash, DB boyutu, local/sunucu path veya app/config dosya listesi
  gostermemeli.

## E. Net karar

Canli icin onerilen yol temiz kurulumdur:

1. Canli mevcut kodu yedekle.
2. Canli SQLite dosyasini yedekle.
3. `uploads/` klasorunu yedekle.
4. Domain document root'u `httpdocs/` olarak birak veya ayarla.
5. Local kok site dosyalarini `httpdocs/` icine yukle.
6. `app/` ve `config/` dosyalarini `acetinweb_private/` altina yukle.
7. `acetinweb_private/config/local.php` dosyasini sunucuda elle olustur.
8. SQLite ve uploads dosyalarini yerine koy.
9. Sade `deploy-manifest.json` dosyasini `httpdocs/` icine yukle.
10. Public ve hassas URL testlerini calistir.
