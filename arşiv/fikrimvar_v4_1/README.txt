#fikrimvar V4.1

Bu sürüm V4'ün teknik/atmosferik yönünü korur, fakat ana sayfadaki üç projeyi "en iyi/seçili işler" olarak sunmaz.

Yapılan ana değişiklikler
- "Seçili işler" dili kaldırıldı; bölüm "Şu sıralar masamda" olarak yeniden yazıldı.
- WebBordro, Görselden Harekete ve ai-context kalite sıralaması değil, bugün masada açık duran üç hikâye olarak gösteriliyor.
- Ana sayfaya "Masanın diğer tarafı" bölümü eklendi. Altı farklı kayıt doğrudan görünür.
- Yeni hikayeler.php sayfası eklendi. Tüm kayıtlar kategori, durum ve arama ile filtrelenebilir.
- Proje sayfasına Bütün Hikâyeler dönüşü, araç etiketleri ve önceki/sonraki kayıt geçişi eklendi.
- Durum etiketleri standartlaştırıldı: Üzerinde çalışıyorum, Çalışıyor, Denendi, Yarım kaldı, Fikir, Not, Arşivde, Tamamlandı.
- Jenerik SVG kapaklardaki ziyaretçiye görünen "geçici proje görseli" metinleri kaldırıldı; yerine gerçek araç adları yazıldı.
- YouTube ve Instagram için gerçek adres verilmediğinden ölü # bağlantıları kaldırıldı. Adresler site.json içine eklendiğinde otomatik olarak tıklanabilir olur.

Kurulum
1. Klasörü XAMPP htdocs içine kopyalayın.
2. PHP üzerinden açın: http://localhost/.../fikrimvar_v4_1/
3. Ana sayfa: index.php
4. Bütün hikâyeler: hikayeler.php

Veri
- data/projects.json: proje ve hikâye kayıtları
- data/categories.json: çalışma alanları
- data/site.json: ana metinler, sosyal bağlantılar ve ana sayfa listeleri

Gerçek sosyal bağlantılar için data/site.json içindeki channels bölümünde url alanlarını doldurun.
