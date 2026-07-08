# FikrimVar Mimari Donusum Checklist

Bu dosya yasayan plandir. Amac bir anda tum sistemi OOP'ye cevirmek degil,
calisan siteyi bozmadan moduler, anlasilir ve baskasinin devralabilecegi bir
yapiya tasimaktir.

## Temel karar

- Local calisan sistem korunur.
- Veritabani ve icerik silinmez.
- Her adim kucuk, test edilebilir ve git ile geri alinabilir olur.
- Refactor isleri `main` uzerinde yapilmaz; ayri branch kullanilir.
- `main` calisan ve geri donulebilir ana hat olarak korunur.
- Refactor bitene kadar canli sunucuya refactor branch'i gonderilmez.
- Once public davranis korunur, sonra kod ic yapisi toparlanir.
- Buyuk refactor yerine "moduler adalar" acilir.
- Eski fonksiyonlar hemen silinmez; gerekirse yeni siniflara kopru olur.

## 2026-07-08 refactor turu kapanis notu

Bu turda hedeflenen guvenli cekirdek tamamlandi:

- Gorunurluk kurallari `VisibilityService` altinda toplandi.
- Link, proje, hikaye, atolye kaydi, medya ve ayarlar icin repository katmani acildi.
- Eski public/admin fonksiyonlari geriye uyumluluk icin korundu ve yeni repository siniflarina delege edildi.
- Klasor ve veri akisini anlatan `SYSTEM_OVERVIEW.md` eklendi.

Bu turda bilerek zorlanmayan isler:

- Upload davranisini `UploadService` altina tamamen tasimak.
- Public render katmanini `View/` altinda bolmek.
- Admin controller/view ayrimini yapmak.
- Deploy manifest uretimini servis sinifina tasimak.

Neden ertelendi: Bunlar calisan admin akisini ve public render ciktisini daha
genis etkileyen islerdir. Ayrica bir sonraki refactor turunda, ayri commitler ve
ayri testlerle alinmalidir.

## Git ve yayin guvenlik kurali

Ahmet bu kurali ozellikle unutabilir. Her refactor isine baslamadan once bu bolum
kontrol edilir ve kullaniciya hatirlatilir.

Kisa ve zorunlu hatirlatma metni icin ayrica kok dizindeki
`REFACTOR_GIT_RULES.md` dosyasi okunur.

### Branch karari

- `main`: Calisan, canliya cikmaya uygun ana hat.
- `codex/architecture-refactor`: Mimari donusum calisma hatti.
- Buyuk mimari isler, dosya tasimalari ve servis/repository ayrimlari bu branch'te yapilir.
- Dokumantasyon gibi dusuk riskli tek dosya degisiklikleri gerekirse `main`e alinabilir.
- Kararsiz kalinirsa `main` yerine branch secilir.

### Commit kurali

- Her faz ayri commit olur.
- Bir commit sadece tek mantiksal isi anlatir.
- Commit atmadan once:
  - [ ] `git status --short` kontrol edildi.
  - [ ] Degisen dosyalar kullaniciya soylendi.
  - [ ] PHP syntax kontrolu yapildi.
  - [ ] Gerekliyse JS syntax kontrolu yapildi.
  - [ ] Gerekliyse HTTP 200 kontrolleri yapildi.

### Push kurali

- `main`e sadece calisan ve kucuk adimlar push edilir.
- Refactor branch'i GitHub'a push edilebilir ama canliya gonderilmez.
- Refactor branch'i bitmeden sunucuya dosya yuklenmez.

### Canliya cikis kurali

Refactor tamamlanmadan canli sunucuya yeni mimari dosyalari gonderilmez.

Refactor bitince once su karar verilir:

1. Degisim kucukse:
   - Degisen dosyalar listelenir.
   - Yayın Merkezi ile kontrol dosyasi hazirlanir.
   - Gerekli dosyalar sunucuya gonderilir.

2. Degisim buyukse:
   - Temiz canli kurulum plani uygulanir.
   - Sunucudaki mevcut kod yedeklenir.
   - `acetinweb_private/storage/fikrimvar.sqlite` yedeklenir.
   - `uploads/` yedeklenir.
   - Eski kod kalintilari temizlenir.
   - Yeni kod gonderilir.
   - SQLite ve uploads geri konur.
   - `deploy-manifest.json` gonderilir.
   - Public ana sayfa, Hikayeler, en az bir Hikaye detay sayfasi ve admin local akisi test edilir.

### Kullaniciya zorunlu hatirlatma

Refactor isleri surerken her yeni adimdan once su cumle kontrol edilir:

> Bu is refactor branch'inde mi? Main temiz mi? Canliya henuz gondermuyoruz.

Refactor bittiginde su hatirlatilir:

> Simdi canliya cikis plani secilecek: kucuk guncelleme mi, temiz kurulum mu?

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
site-root/
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
- [x] Mevcut klasor ve veri akisini anlatan kisa not guncellendi.

## Faz 1 - Link sistemi modulu

Durum: Baslandi.

- [x] Link render mantigi ayri dosyaya tasindi.
- [x] Link kartlari public tarafta daha anlamli hale geldi.
- [x] YouTube/Vimeo/SoundCloud/dogrudan ses-video linkleri icin player mantigi basladi.
- [x] LinkRepository ekle.
- [x] Admin link kaydetme islemlerini LinkRepository uzerinden gecir.
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

- [x] `app/Service/VisibilityService.php` ekle.
- [x] Ana sayfa gorunurluk kuralini servise tasi.
- [x] Hikayeler sayfasi gorunurluk kuralini servise tasi.
- [x] Atolye penceresi kuralini servise tasi.
- [x] Hikaye detay erisim kuralini servise tasi.
- [x] Admin "neden gorunmuyor?" kutusu bu servisi kullansin.
- [x] Eski sorgularin davranisi degismeden test et.

Kabul olcutu:
- Proje public degilse public listelerde gorunmez.
- Story yayinda degilse hikaye listelerinde gorunmez.
- Atolye penceresi sadece `workshop_status open/paused` ve `show_in_widget=1` projeleri gosterir.
- Admin ozeti ile public sonuc ayni mantigi anlatir.

## Faz 3 - Repository katmani

Neden onemli:
`app/repositories.php` buyudukce yeni gelen biri hangi sorgunun hangi sayfayi
besledigini anlamakta zorlanir.

- [x] `ProjectRepository` olustur.
- [x] `StoryRepository` olustur.
- [x] `UpdateRepository` olustur.
- [x] `MediaRepository` olustur.
- [x] `LinkRepository` olustur.
- [x] `SettingsRepository` olustur.
- [x] Eski fonksiyonlar gecici olarak repository metodlarina delege etsin.
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

Durum:

- [x] `SYSTEM_OVERVIEW.md` olusturuldu.

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
