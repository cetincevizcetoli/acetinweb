# FikrimVar Mimari Donusum Checklist

Bu dosya yasayan plandir. Amac bir anda tum sistemi OOP'ye cevirmek degil,
calisan siteyi bozmadan moduler, anlasilir ve baskasinin devralabilecegi bir
yapiya tasimaktir.

## Temel karar

- Local calisan sistem korunur.
- Veritabani ve icerik silinmez.
- Her adim kucuk, test edilebilir ve git ile geri alinabilir olur.
- Once public davranis korunur, sonra kod ic yapisi toparlanir.
- Buyuk refactor yerine "moduler adalar" acilir.
- Eski fonksiyonlar hemen silinmez; gerekirse yeni siniflara kopru olur.

## Hedef klasor yapisi

Son hedef yaklasik olarak su olur:

```text
app/
  Domain/
    Project.php
    Story.php
    Update.php
    Link.php
    Media.php
  Repository/
    ProjectRepository.php
    StoryRepository.php
    UpdateRepository.php
    LinkRepository.php
    MediaRepository.php
    SettingsRepository.php
  Service/
    VisibilityService.php
    PublishReadinessService.php
    LinkPreviewService.php
    MediaService.php
    UploadService.php
    DeployManifestService.php
  View/
    StoryRenderer.php
    MediaRenderer.php
    LinkRenderer.php
    AtelierRenderer.php
  Support/
    Url.php
    Slug.php
    Csrf.php
    Flash.php
    Path.php
admin-local/
  controllers/
  views/
public/
  controllers/
```

Bu klasorlerin hepsi hemen acilmak zorunda degildir. Ihtiyac dogdukca eklenir.

## Faz 0 - Mevcut durumu sabitle

- [ ] Git durumunun temiz oldugunu dogrula.
- [ ] Local ana sayfa HTTP 200.
- [ ] Hikayeler sayfasi HTTP 200.
- [ ] En az bir hikaye detay sayfasi HTTP 200.
- [ ] Admin login ve proje listesi aciliyor.
- [ ] SQLite `integrity_check` sonucu `ok`.
- [ ] SQLite `foreign_key_check` bos.
- [ ] PHP syntax tum dosyalarda temiz.
- [ ] JS syntax temiz.
- [ ] Mevcut klasor ve veri akisini anlatan kisa not guncellendi.

## Faz 1 - Link sistemi modulu

Durum: Baslandi.

- [x] Link render mantigi ayri dosyaya tasindi.
- [x] Link kartlari public tarafta daha anlamli hale geldi.
- [x] YouTube/Vimeo/SoundCloud/dogrudan ses-video linkleri icin player mantigi basladi.
- [ ] LinkRepository ekle.
- [ ] Admin link kaydetme islemlerini LinkRepository uzerinden gecir.
- [ ] Link provider tespitini test eden kucuk tekrar calistirilabilir test ekle.
- [ ] README veya SYSTEM_OVERVIEW icinde link akisini anlat.

Kabul olcutu:
- Eski linkler calisir.
- Basligi "Baglanti" olan eski kayitlar public tarafta daha anlamli gorunur.
- Desteklenmeyen linkler sadece dis kaynak karti olur, siteyi bozmaz.

## Faz 2 - Gorunurluk ve yayin kurallari

Neden onemli:
Ana sayfa, Hikayeler sayfasi, Atolye penceresi ve detay erisimi ayni kavramlari
farkli yerlerden kontrol ediyor. Bunu tek servis anlatmali.

- [ ] `app/Service/VisibilityService.php` ekle.
- [ ] Ana sayfa gorunurluk kuralini servise tasi.
- [ ] Hikayeler sayfasi gorunurluk kuralini servise tasi.
- [ ] Atolye penceresi kuralini servise tasi.
- [ ] Hikaye detay erisim kuralini servise tasi.
- [ ] Admin "neden gorunmuyor?" kutusu bu servisi kullansin.
- [ ] Eski sorgularin davranisi degismeden test et.

Kabul olcutu:
- Proje public degilse public listelerde gorunmez.
- Story yayinda degilse hikaye listelerinde gorunmez.
- Atolye penceresi sadece `workshop_status open/paused` ve `show_in_widget=1` projeleri gosterir.
- Admin ozeti ile public sonuc ayni mantigi anlatir.

## Faz 3 - Repository katmani

Neden onemli:
`app/repositories.php` buyudukce yeni gelen biri hangi sorgunun hangi sayfayi
besledigini anlamakta zorlanir.

- [ ] `ProjectRepository` olustur.
- [ ] `StoryRepository` olustur.
- [ ] `UpdateRepository` olustur.
- [ ] `MediaRepository` olustur.
- [ ] `LinkRepository` olustur.
- [ ] `SettingsRepository` olustur.
- [ ] Eski fonksiyonlar gecici olarak repository metodlarina delege etsin.
- [ ] Sonra kullanilan public/admin sayfalari tek tek yeni repository metodlarina gecsin.

Kabul olcutu:
- Sorgular dosya dosya ayrilir.
- Public sayfalarin HTML sonucu beklenmedik sekilde degismez.
- Yeni bir gelistirici "proje verisi nereden gelir?" sorusuna tek klasorden cevap bulur.

## Faz 4 - Medya ve upload servisi

- [ ] `MediaService` ekle.
- [ ] `UploadService` ekle.
- [ ] MIME dogrulama ve dosya adi uretme buraya tasinir.
- [ ] Medya sahipligi kontrolu tek yerde toplanir.
- [ ] Public medya render mantigi `MediaRenderer` altina alinir.
- [ ] Buyuk dosya stratejisi deploy checklist ile uyumlu hale getirilir.

Kabul olcutu:
- Baska projeye ait medya iliskisi reddedilir.
- Upload kurallari tek yerden okunur.
- Public medya render davranisi degismez.

## Faz 5 - Story/Atolye render katmani

- [ ] `StoryRenderer` ekle.
- [ ] `AtelierRenderer` ekle.
- [ ] `app/render.php` kucultulur.
- [ ] Story bolum tipleri daha okunur alt metodlara ayrilir.
- [ ] LinkRenderer ve MediaRenderer burada kullanilir.

Kabul olcutu:
- Hikaye sayfasi goruntusu bozulmaz.
- Hero ve CSS animasyonlarina dokunulmaz.
- Bir bolum tipinin render yeri kolay bulunur.

## Faz 6 - Admin controller/view ayrimi

Neden onemli:
Admin dosyalarinda form okuma, validation, SQL ve HTML ayni dosyada karisik.

- [ ] Yeni admin islemleri once controller mantigiyla yazilir.
- [ ] Eski dosyalar kademeli bolunur.
- [ ] Form kaydetme islemleri servis/repository kullanir.
- [ ] Admin view parcaciklari tekrar eden formlar icin ayrilir.
- [ ] CSRF ve validation ortak yardimcilarla calisir.

Kabul olcutu:
- Proje duzenleme, atelye kaydi, hikaye bolumu kaydi ayni davranir.
- Kullaniciya hata mesajlari kaybolmaz.
- Transaction kullanilan yerler korunur.

## Faz 7 - Deploy/Yayin Merkezi toparlama

- [ ] Deploy manifest uretimi `DeployManifestService` altina alinir.
- [ ] Local log dosyasi yazimi ayri yardimciya tasinir.
- [ ] Canli manifest okuma ve karsilastirma test edilir.
- [ ] "Yayin paketi hazirla" akisi dokumante edilir.

Kabul olcutu:
- Manifest hazirlamak SQLite dosyasini degistirmez.
- Local/canli ayniysa ekran bunu acik soyler.
- Hangi dosyalar sunucuya gidecek net gorunur.

## Faz 8 - Sistem anlatim raporu

Is bittiginde veya ana fazlar tamamlandikca kok dizinde su dosya olusturulur:

```text
SYSTEM_OVERVIEW.md
```

Bu dosya sunlari anlatir:

- Site ne is yapar?
- Proje, Atolye, Hikaye, Medya, Link kavramlari ne demek?
- Klasorler ne ise yarar?
- Public site hangi dosyalardan akar?
- Admin hangi dosyalardan akar?
- SQLite nerede durur?
- Canliya yayin akisi nasil yapilir?
- Yeni gelistirici nereden baslamali?
- Hangi dosyalara dokunurken dikkatli olunmali?

## Her fazdan sonra calistirilacak kontrol listesi

- [ ] `git status --short` kontrol edildi.
- [ ] Degisen dosyalar not edildi.
- [ ] PHP syntax kontrolu yapildi.
- [ ] JS syntax kontrolu yapildi.
- [ ] SQLite integrity kontrolu yapildi.
- [ ] Public ana sayfa acildi.
- [ ] Hikayeler sayfasi acildi.
- [ ] En az bir hikaye detay sayfasi acildi.
- [ ] Admin ilgili ekran acildi.
- [ ] Geri alma yontemi belli.
- [ ] Commit mesaji net.

## Geri alma kurali

Her faz ayri commit olur. Bir faz sorun cikarirsa sadece o commit geri alinir.
Veritabani migration gerektiren fazlarda once yedek alinir.

## Notlar

- Bu dosya sabit plan degil, yasayan checklisttir.
- Yeni ihtiyac cikarsa ilgili faza madde eklenir.
- Bitmis maddeler `[x]` yapilir; ertelenenler yanina neden yazilir.
