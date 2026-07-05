# FikrimVar V6.4

Bu paket V6.1 yapısı üzerinde toparlama sürümüdür. Ayrı bir V6.2 sürümü yoktur.

## V6.4 değişiklikleri
- #FikrimVar başlığındaki blur, alt çizgi ve gidip gelen nokta kaldırıldı.
- Başlık daha kalın ve sakin bir tipografik yapıya geçirildi. Yalnızca yazının içinde bir kez geçen ince ışık yansıması vardır.
- V6.1 üzerine eklenen organik çatlak ve tel hareketleri korunmuştur.
- Geniş yörünge kompozisyonu masaüstünde yeniden dengelenmiştir.
- Atölye kayıtları için isteğe bağlı Instagram, YouTube ve GitHub alanları desteklenir.
- Ana sayfada tüm canlı atölyelerden derlenen Son Hareketler akışı bulunur.
- Ana sayfadaki büyük atölye ayrıca pinned_atelier ile sabitlenebilir.
- Atölye günlerinin kalıcı bağlantıları vardır: atolye.php?slug=...#update-gun-03

## Bir atölye gününe sosyal bağlantı eklemek
İlgili updates/*.json dosyasına şunları ekle:

  "instagram_url": "",
  "youtube_url": "",
  "github_url": ""

Boş alanlar sayfada görünmez.

## Ana sayfa ayarları
content/site.json içindeki homepage alanı:
- pinned_atelier: büyük gösterilecek atölye
- recent_updates_limit: Son Hareketler satır sayısı

## Kontrol
PHP 8.x, JSON ve JavaScript sözdizimi test edilmiştir.
