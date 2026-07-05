# fikrimvar V6

Bu sürümün merkezindeki karar:

“Bu site Ahmet Çetin’in yaptığı işlerin portfolyosu değil; yapay zekâyla tanıştıktan sonra aklına gelenleri denemeye başlayan bir insanın açık çalışma günlüğü.”

V6’da neler değişti?
--------------------

1. #fikrimvar manifestosu ile kısa imzası ana sayfada karşı karşıya duruyor.
2. Ana sayfa yukarıdan aşağı aynı genişlikte kutu/bölüm kulesi olarak tasarlanmadı.
   Manifesto, başlangıç hikâyesi, canlı atölye ve proje kesitleri tek editoryal alanda iç içe geçiyor.
3. Mehmet Fırat hocanın verdiği cesaret ile Üretim Takip Sistemi, projenin başlangıç çizgisi olarak ana sayfada yer alıyor.
4. “Atölyede şimdi” alanı aktif projenin son günlüğünü otomatik gösteriyor.
5. Hikâyeler tek stories.json yerine kendi klasörlerinde yaşıyor.
6. Hikâye sayfası blok tabanlı. Yeni bir görsel, video, zaman çizgisi veya teknik soru eklemek için PHP değiştirmek gerekmiyor.
7. Canlı atölye projelerinde her gün/karar ayrı JSON kaydı olarak ekleniyor.
8. Proje, Ahmet “bu hâliyle bitti” veya “yarım bıraktım” dediği anda kapanabilir. Tamamlanma zorunluluğu yok.

Klasör yapısı
-------------

content/
  site.json
  stories/
    webbordro/
      story.json
      media/
      updates/
    ai-context/
      story.json
      media/
      updates/
    gorselden-harekete/
      story.json
      media/
      updates/
        001.json
        002.json
        003.json
    _template/
      story.json.example
      update.json.example

tools/
  new_story.php

Yeni hikâye eklemek
-------------------

Komut satırında proje klasöründen:

php tools/new_story.php yeni-hikaye "Yeni Hikâye Başlığı" story

Canlı atölye için:

php tools/new_story.php yeni-atolye "Yeni Atölye" atelier

Araç otomatik olarak şunları oluşturur:

content/stories/yeni-hikaye/story.json
content/stories/yeni-hikaye/media/cover.svg
content/stories/yeni-hikaye/updates/

Mevcut hikâyeyi değiştirmek
----------------------------

Örnek:

content/stories/webbordro/story.json

Dosyada şu alanları değiştirebilirsin:

- question: ana sayfadaki merak cümlesi
- summary: kısa açıklama
- status / status_label: projenin durumu
- cover: kapak görseli
- tags: kullanılan araçlar
- blocks: hikâyenin ekrandaki parçaları

Desteklenen blok türleri
------------------------

- opening: açılış metni + görsel + alıntı
- split: metin/görsel bölümü
- timeline: gelişim haritası
- compare: işe yarayan / değişen gibi iki taraflı karşılaştırma
- questions: açılır teknik sorular
- roles: yapay zekâ / Ahmet ayrımı
- status: güncel sürümler veya mevcut durum
- lesson: bende kalanlar
- code: terminal veya kod örneği
- gallery: görsel serisi
- video: video kaydı

Blokların görünümü layout alanıyla değişir:

hero-split, wide, full-bleed, offset, cross, diagonal, terminal, compact

Yeni atölye kaydı eklemek
-------------------------

Aktif atölyenin updates klasörüne yeni bir JSON dosyası ekle:

content/stories/gorselden-harekete/updates/004.json

_template/update.json.example dosyasını kopyalayıp düzenleyebilirsin.

Ana sayfadaki “Atölyede şimdi” alanı, order değeri en yüksek olan son kaydı otomatik gösterir.

Aktif atölyeyi değiştirmek
--------------------------

content/site.json içindeki:

"active_atelier": "gorselden-harekete"

değerini yeni atölyenin slug’ıyla değiştir.

Ana sayfadaki hikâyeleri değiştirmek
------------------------------------

content/site.json:

"focus_stories": ["webbordro", "ai-context"]
"trace_stories": [ ... ]

Bu listeler yalnızca ana sayfa sunumunu değiştirir. Hikâyeler silinmez.

Durum önerileri
---------------

- fikir / Fikir
- suruyor / Üzerinde çalışıyorum
- calisiyor / Çalışıyor
- beklemede / Beklemede
- bu-haliyle-bitti / Bu hâliyle bitti
- yarim / Yarım bıraktım
- arsiv / Arşivde

Çalıştırma
----------

XAMPP kullanıyorsan klasörü htdocs içine koy:

http://localhost/fikrimvar_v6/

PHP yerleşik sunucusu:

php -S 127.0.0.1:8080 -t .

Sonra:

http://127.0.0.1:8080

Notlar
------

- Harici framework veya build adımı yok.
- PHP + HTML + CSS + Vanilla JavaScript kullanılıyor.
- Yorumlar data/notes-pending.json dosyasına moderasyon bekleyen kayıt olarak yazılır.
- Hosting’de data klasörüne yazma izni gerekebilir.
- YouTube ve Instagram bağlantıları content/site.json içinde gerçek adresler eklenene kadar gösterilmez.
