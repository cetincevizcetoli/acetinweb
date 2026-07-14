# Atolye - Hikaye Donusum Plani

Tarih: 2026-07-14

## Karar

Atolye, hikayenin kopyasi olmayacak. Atolye gercek is masasi olacak; hikaye ise bu masadan secilen, duzenlenen ve okura uygun hale getirilen anlatim olacak.

Bugunku sorun sadece gorunum sorunu degil. Atolye kaydi, gercek calisma kaniti ile hikaye metni arasinda kaliyor. Bu yuzden story-builder zayif taslak uretiyor ve public Atolye ekrani da "ne is yapildi?" sorusunu net cevaplamiyor.

## Hikaye tekrar Atolyeye alindiginda

Eski hikayeler Atolye verisinden dogmamis olabilir. Bu hikayelere sonradan sahte Atolye gecmisi uretmek dogru degildir.

Kural:

- Eski hikaye bolumleri Atolye kaydina kopyalanmaz.
- Eski hikaye public Atolye ekraninda "mevcut hikaye referansi" olarak gosterilir.
- Yeni calismalar ayri Atolye kaydi olarak eklenir.
- Bir hikaye gercekten Atolye kayitlarindan uretilmisse, `story_sections.source_update_id` iliskisi korunur.
- Kaynak kaydi olan hikaye bolumleri "hikayeye tasinmis Atolye kayitlari" olarak gosterilir.
- Kaynak kaydi olmayan hikaye bolumleri ham kayit gibi davranmaz.

Bu kuralin sonucu: "Hikayeyi Atolyeye geri al" islemi yeni `updates` kaydi uretmez. Proje yeniden Atolye durumuna gecerse eski hikaye referans, yeni isler ise gercek Atolye akisi olur.

## Hedef

Atolyeden hikayeye donusum rahat, izlenebilir ve kopukluksuz olmali.

Bir Atolye kaydi sunlari tasiyabilmeli:

- Yapilan is
- Kullanilan girdi, prompt, kod, komut veya medya
- Alinan cikti
- Hata, surtusme veya eksik kalan nokta
- Karar veya yon degisimi
- Kanit
- Sonraki is
- Hikayedeki muhtemel rolu

## Faz 1 - Kavrami Sabitleme

Atolye public ekrani hikaye gibi davranmayacak. Kayitlar gercek uretim izleri olarak okunacak.

Yapilacaklar:

- Atolye kayit turleri netlestirilecek.
- Soyut ve zorlayici anlatim basliklari azaltilacak.
- Kayitlarin "hikayeye etkisi" ayrica gosterilecek.
- Ham kayit ve hikaye adayi birbirinden kopuk iki bolum gibi davranmayacak.

## Faz 2 - Veri Modeli Yukseltme

Yeni blok modeli eklenecek.

Onerilen tablo:

```sql
CREATE TABLE update_blocks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  update_id INTEGER NOT NULL,
  block_type TEXT NOT NULL,
  title TEXT NOT NULL DEFAULT '',
  body TEXT NOT NULL DEFAULT '',
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(update_id) REFERENCES updates(id) ON DELETE CASCADE
);
```

Mevcut `updates.tried`, `updates.failed`, `updates.decision`, `updates.next_step` alanlari hemen silinmeyecek. Geriye donuk uyumluluk icin kalacak.

## Faz 3 - Admin Atolye Formu

Admin formu kayit turune gore daha anlamli alanlar sunacak.

Ornek:

- Prompt / YZ konusmasi: prompt, cevap, sonuc, karar
- Kod / terminal: komut, cikti, hata, cozum
- Saha notu: gozlem, sorun, kanit, sonraki is
- Medya: medya notu, neyi gosteriyor, hikayede nasil kullanilir

## Faz 4 - Public Atolye Sunumu

Atolye ekrani tek bir calisma akisi gibi okunacak.

Yapilacaklar:

- Kayitlar is karti gibi gosterilecek.
- Prompt, kod, log ve kanit duz paragraf gibi akmayacak.
- Her kaydin hikayeye nasil baglanabilecegi kucuk ama net gosterilecek.
- Ham kalan kayitlar ile hikayeye tasinacak kayitlar ayni akisin icinde anlasilir olacak.

## Faz 5 - Story Builder

Builder artik tek kaydi tek bolume kopyalamayacak.

Yapilacaklar:

- Secilen Atolye kayitlarini okuyacak.
- Kayit bloklarini kullanacak.
- Kayitlari hikaye rollerine gore gruplayacak.
- `story_sections` ve `story_section_items` uretirken bolum tipine uygun veri uretecek.
- Eksik blok varsa admini uyaracak.

## Faz 6 - Kalibrasyon Hikayeleri

Yeni sistem su hikayelerle test edilecek:

- ai-context
- WebBordro / DeltaBordro
- Uretim Takip Sistemi

Kabul olcutu:

- ai-context icin teknik prompt, kod, karar ve ders akisi kurulabilmeli.
- WebBordro icin surum degisimleri ve kararlar timeline'a donusebilmeli.
- Uretim Takip icin saha notu, gercek ihtiyac ve yasayan sistem anlatisi kurulabilmeli.

## Faz 7 - Demo Proje

Demo proje tekrar yazilacak.

Demo, gercek bir mini proje gibi davranmali:

- Saha notu
- Prompt
- Kod veya cikti
- Hata
- Medya kaniti
- Karar
- Hikayeye secilecek guclu kayitlar

Demo kullaniciya "bu sistemi boyle kullan" dedirtmeli; bos ve soyut anlatim uretmemeli.

## Geri Alma

Her faz ayri commit olmalidir.

Geri alma sirasinda:

1. Son faz commit'i geri alinir.
2. Migration yapildiysa once veritabani yedeginden donulur.
3. Mevcut eski alanlar silinmedigi icin ilk blok modeli geriye uyumlu kalir.

## Ilk Uygulama Karari

Ilk uygulama Faz 2 ile baslar:

- `update_blocks` tablosu eklenecek.
- Eski dort calisma alani bu blok modeline okunabilir sekilde baglanacak.
- Builder bloklari okuyabilecek.
- Eski kayitlar bozulmayacak.

## 2026-07-14 Uygulama Durumu

Uygulananlar:

- `update_blocks` modeli eklendi ve eski alanlar geriye uyumlu fallback olarak korundu.
- Atolye kayit formunda is bloklari birincil kaynak haline getirildi.
- Kayit turune gore onerilen bloklar admin formunda gosteriliyor.
- Story-builder uzun Atolye gunlugunu dogrudan hikayeye dokmek yerine kisa / orta / detayli yogunlukla suzuyor.
- Builder secilen kayitlari acilis, anlatim, kanit, durum ve kapanis gruplarina ayiriyor.
- Prompt, kod ve cikti bloklari hikaye iskeletine sinirsiz ham metin olarak degil, kisaltilmis kanit ozeti olarak tasiniyor.
- Eski hikaye bolumleri Atolyeye sahte ham kayit olarak kopyalanmiyor; referans olarak gosteriliyor.

Kalan isler:

- Atolye public sunumunun son gorsel ritmi kullanici ile beraber kontrol edilecek.
- Demo proje verisi gercek kullanim senaryosuna gore yeniden kalibre edilecek.
- Yeni akistan uretilen ilk gercek hikaye taslagi admin story-edit ekraninda birlikte incelenecek.
