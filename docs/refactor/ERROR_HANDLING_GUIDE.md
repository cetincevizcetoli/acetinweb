# Hata Yonetimi ve Aciklama Kurali

Bu belge #FikrimVar icin hata yakalama, loglama ve kod/dokuman aciklama
kurallarini toplar.

## Temel ilke

- Kullanici teknik hata metni gormemeli.
- Gelistirici hatanin izini private log dosyasinda bulabilmeli.
- Validation hatalari insani ve dogrudan kalmali.
- Veritabani, dosya sistemi veya beklenmeyen teknik hatalar referans kodu ile
  gosterilmeli.

## Hata logu

Beklenmeyen hatalar `AppErrorService` ile kaydedilir.

Log hedefi:

```text
FV7_STORAGE/logs/app-errors.jsonl
```

Bu klasor private storage altindadir. Public `httpdocs` altina konmaz.

Her satir JSON olarak su bilgileri tasir:

- tarih
- referans kodu
- baglam
- hata sinifi
- mesaj
- dosya ve satir

## Public davranis

Public tarafta yakalanmayan hata olursa:

- Canlida genel mesaj ve hata kodu gosterilir.
- `FV7_DEBUG=true` ise detay mesaj da gorunur.
- Hata detaylari loga yazilir.

## Admin davranis

Admin sayfalari `admin_error_message()` kullanir.

- `PDOException` gibi teknik hatalar ham haliyle ekrana basilmaz.
- Kullanici hatalari, ornegin "Baslik gerekli" veya "Sira cakismasi var",
  dogrudan gosterilebilir.
- Her hata yine loga yazilir.

## Kod ici aciklama kurali

Yorum yazarken amac "bu satir ne yapiyor" demek degil, "neden bu karar
verildi" demektir.

Iyi yorum:

```php
// Public sitemap, yayindaki hikaye kararindan turetilir; robots.txt icerik
// yayinlama/kaldirma icin kullanilmaz.
```

Zayif yorum:

```php
// Degiskeni ata.
```

## Dokuman uyumu

Bir davranis degisirse uc yer birlikte dusunulur:

- Kod davranisi
- Admin ekrani dili
- `docs/` altindaki sistem aciklamasi

Bu uc yer ayni kavramlari kullanmaliyse ayni commit icinde guncellenmelidir.
