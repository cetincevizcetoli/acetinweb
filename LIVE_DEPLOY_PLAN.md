# FikrimVar Canli Deploy Plani

Bu plan canliya otomatik yukleme yapmaz. Local ana kaynaktir; canli sunucu
yayin kopyasidir. Canliya cikmadan once canli SQLite dosyasi ve
`public/uploads/` mutlaka yedeklenir.

## A. Canliya yuklenecekler

Tercih edilen kurulumda domain document root dogrudan `public/` klasorune
bakar. Bu durumda URL'de `/public/` gorunmez.

Yuklenecek uygulama dosyalari:

- `public/`
- `app/`
- `config/config.php`
- `config/local.example.php`
- `.htaccess`
- `index.php`
- `robots.txt`
- `sitemap.xml`
- `VERSION.txt`
- `DEPLOY_CHECKLIST.md` sadece canli web kokunun disinda tutulacaksa
- `LIVE_DEPLOY_PLAN.md` sadece canli web kokunun disinda tutulacaksa

Yuklenecek icerik dosyalari:

- `public/uploads/`
- `public/uploads/.htaccess`
- `public/deploy-manifest.json` sade public kontrol dosyasi olarak

Not: `public/deploy-manifest.json` DB hash, DB boyutu, local yol, app/config
dosya listesi veya hassas path bilgisi icermemelidir.

## B. Canlida elle olusturulacaklar

Sunucuda public web kokunun disinda:

- `acetinweb_private/storage/`
- `acetinweb_private/storage/fikrimvar.sqlite`
- `acetinweb_private/storage/backups/`
- `acetinweb_private/storage/deploy-manifests/`

Canliya ozel config:

- `config/local.php`

Ornek:

```php
<?php
declare(strict_types=1);

return [
    'storage_path' => '/var/www/vhosts/acetin.com.tr/acetinweb_private/storage',
    'db_path' => '/var/www/vhosts/acetin.com.tr/acetinweb_private/storage/fikrimvar.sqlite',
    'allow_system_check' => false,
];
```

Permissions:

- PHP kullanicisi SQLite dosyasini okuyup yazabilmeli.
- PHP kullanicisi `public/uploads/` icine dosya yazabilmeli.
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
- `public/guven*.txt`
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
- `https://www.acetin.com.tr/system-check.php`
- `https://www.acetin.com.tr/public/system-check.php`

Manifest:

- `https://www.acetin.com.tr/deploy-manifest.json` acilirsa sade JSON olmali.
- DB hash, DB boyutu, local/sunucu path veya app/config dosya listesi
  gostermemeli.

## E. Net karar

Canli icin onerilen yol temiz kurulumdur:

1. Canli mevcut kodu yedekle.
2. Canli SQLite dosyasini yedekle.
3. `public/uploads/` klasorunu yedekle.
4. Domain document root'u `public/` olarak ayarla.
5. Uygulama dosyalarini yukle.
6. `config/local.php` dosyasini sunucuda elle olustur.
7. SQLite ve uploads dosyalarini yerine koy.
8. Sade `public/deploy-manifest.json` dosyasini yukle.
9. Public ve hassas URL testlerini calistir.
