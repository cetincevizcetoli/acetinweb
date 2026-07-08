# FikrimVar Sistem Ozeti

Bu dosya projeyi devralacak kisi icin hizli haritadir. Ayrintili refactor
plani icin `ARCHITECTURE_REFACTOR_CHECKLIST.md`, git/yayin hatirlatmalari icin
`REFACTOR_GIT_RULES.md`, canliya cikis kurallari icin `DEPLOY_CHECKLIST.md`
okunur.

## Site ne is yapar?

#FikrimVar bir portfolyo veya satis sitesi degildir. Ahmet Cetin'in yapay zeka,
kod, gorsel uretim, animasyon, muzik, yontem ve gercek is ihtiyaclari uzerinde
yaptigi denemelerin acik calisma gunlugudur.

Basari, hata, yarim kalan is ve karar degisikligi ayni sistemde saklanir.

## Ana kavramlar

- Proje: Bir isin ana kimligi. Somut proje adi, kategori, gorunurluk, siralama,
  atolye durumu ve yayin alanlari burada tutulur.
- Atolye: Projenin calisma sureci. Ham kayitlar, denemeler, kararlar ve medya
  burada birikir.
- Atolye kaydi / update: Atolyedeki tek gunluk veya tek hareket. Birden fazla
  medya ve baglanti tasiyabilir.
- Hikaye: Ham atolyeden secilip duzenlenmis, okunabilir anlatidir.
- Hikaye bolumu: Hikayenin parcalari. Metin, medya, galeri, kod, alinti veya
  baglanti icerebilir.
- Medya: Dosyanin kendisi `public/uploads` altinda durur; SQLite sadece yol,
  sahiplik ve aciklama bilgisini saklar.
- Baglanti: Harici kaynak, YouTube, Vimeo, SoundCloud veya dogrudan medya linki
  olabilir. Public tarafta `LinkRenderer` tarafindan anlamli kart/player olarak
  gosterilir.

## Klasorler

- `public/`: Web kokudur. Public sayfalar, CSS, JS, upload dosyalari ve public
  manifest burada durur.
- `admin-local/`: Yerel yonetim panelidir. Canli public web kokune tasinmamasi
  gerekir.
- `app/`: Ortak PHP kodu. Repository, service ve render katmanlari burada
  toplanir.
- `app/Repository/`: SQLite okuma/yazma sorgularinin yeni adresidir.
- `app/Service/`: Davranis kurallari burada toplanir. Ornek:
  `VisibilityService`.
- `config/`: Ortam ve yol ayarlari. Ortama ozel degerler `config/local.php`
  veya ortam degiskenlerinden gelir.
- `tools/`: Import, kontrol veya yardimci scriptler.
- `mocap/`: Bu refactor kapsaminda dokunulmayan eski/ayri calisma alani.
- `archive/`: Varsa eski veya arka plan dosyalari; refactor kapsaminda
  dokunulmaz.

## Public akis

- Ana sayfa: `public/index.php`
- Hikayeler listesi: `public/hikayeler.php`
- Hikaye detay: `public/hikaye.php`
- Atolye detay: `public/atolye.php`
- Atolye penceresi partial: `public/includes/atelier_widget.php`
- Render yardimcilari: `app/render.php`, `app/LinkRenderer.php`

Public sayfalar eski fonksiyon adlarini kullanabilir. Bu fonksiyonlar kademeli
olarak `app/Repository` siniflarina delege edilir.

## Admin akis

- Admin giris ve ortak yardimcilar: `admin-local/_bootstrap.php`
- Proje listesi: `admin-local/index.php`
- Proje olusturma/duzenleme: `project-new.php`, `project-edit.php`
- Atolye kaydi olusturma/duzenleme: `update-new.php`, `update-edit.php`
- Hikaye duzenleme: `story-edit.php`, `section-edit.php`
- Medya kutuphanesi: `media.php`
- Yayin kontrolu: `deploy.php`

Admin tarafinda form okuma, validation, SQL ve HTML henuz tamamen ayrilmadi.
Yeni kod yazarken repository/service kullanmak tercih edilir.

## Veri katmani

- SQLite yolu `config/config.php` ve varsa `config/local.php` tarafindan
  belirlenir.
- Local tercih edilen DB:
  `C:/xampp/acetinweb_private/storage/fikrimvar.sqlite`
- Canli tercih edilen DB:
  `/var/www/vhosts/acetin.com.tr/acetinweb_private/storage/fikrimvar.sqlite`
- DB public klasor altinda tutulmaz.
- Migration veya riskli is oncesi DB yedegi alinir.

## Repository katmani

Mevcut repository siniflari:

- `ProjectRepository`: Public/admin proje listeleri, slug ile proje, etiketler.
- `StoryRepository`: Hikaye, bolum ve part okuma.
- `UpdateRepository`: Proje atelye kayitlari ve son hareketler.
- `MediaRepository`: Update medyasi, admin medya listeleri ve medya sahiplik
  kontrolu.
- `LinkRepository`: Owner bazli link okuma/yazma.
- `SettingsRepository`: Site ayarlari okuma/yazma.

Eski fonksiyonlar tamamen kaldirilmadi. Geriye uyumluluk icin bir sure kopru
olarak kalacaklar.

## Gorunurluk kurali

Public gorunurluk kararlari `VisibilityService` icinden okunmalidir.

Ana sayfa ve Hikayeler icin proje ve hikaye birlikte uygunsa gorunur.
Atolye penceresi ise hikaye yayinindan bagimsizdir; proje public, silinmemis,
`workshop_status` open/paused ve `show_in_widget=1` ise gorunebilir.

## Medya stratejisi

Kucuk kapaklar, ekran goruntuleri ve gerekli aciklayici gorseller siteye
yuklenebilir. Buyuk MP4 ve uzun ses dosyalari mumkunse YouTube, Vimeo,
SoundCloud veya CDN ile linklenmelidir.

`public/uploads/` canli yedek planina dahil edilmelidir.

## Git ve yayin

Refactor isleri `codex/architecture-refactor` branch'inde yapilir. `main`
calisan ana hat olarak korunur.

Refactor bitmeden canli sunucuya dosya gonderilmez.

Refactor bittiginde once karar verilir:

1. Kucuk guncelleme: Sadece degisen dosyalar ve manifest gonderilir.
2. Temiz kurulum: Sunucu kodu yedeklenir, eski kod temizlenir, yeni kod,
   SQLite, uploads ve manifest kontrollu sekilde gonderilir.

## Yeni gelistirici nereden baslamali?

1. `SYSTEM_OVERVIEW.md` oku.
2. `ARCHITECTURE_REFACTOR_CHECKLIST.md` icinde aktif fazi bul.
3. Public davranis icin once `public/` dosyasini, sonra ilgili repository ve
   service sinifini oku.
4. Admin davranis icin once ilgili `admin-local/*.php` dosyasini, sonra
   repository/service siniflarini oku.
5. Kod degistirdikten sonra PHP syntax, JS syntax, SQLite kontrolu ve HTTP 200
   kontrollerini calistir.
