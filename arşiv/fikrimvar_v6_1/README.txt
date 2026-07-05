# FikrimVar V6.1

Bu sürüm, V6'nın içerik motorunu korur; ana sayfa, hikâye, canlı atölye ve arşiv görünümünü daha kontrollü bir görsel sistemle yeniden kurar.

## Temel karar

Bu site bir portfolyo değil; Ahmet Çetin'in yapay zekâyla tanıştıktan sonra aklına gelenleri denemeye başladığı açık çalışma günlüğüdür.

V6.1'de:

- #FikrimVar yazımı her yerde aynılaştırıldı. F ve V büyük kullanılır.
- Ana #FikrimVar başlığına bir kez çizilen ışık izi, dolaşan küçük kıvılcım ve F/V harflerinde sıcak vurgu eklendi.
- Hero dışındaki aşırı boşluklar ve birbirinden kopuk serbest yerleşimler kaldırıldı.
- Font görevleri netleştirildi:
  - Fraunces: anlatı başlıkları
  - Inter: okuma metinleri
  - IBM Plex Mono: küçük teknik bilgi, tarih ve durum etiketleri
- Manifesto ve kısa anlatım aynı kompozisyonda karşılaşır.
- Mehmet Fırat / Üretim Takip başlangıcı ile aktif atölye aynı çalışma yüzeyinde bulunur.
- Ana hikâyeler iki dengeli editoryal kesit olarak görünür.
- Diğer hikâyeler kart duvarına dönüşmeden kısa bir kayıt şeridinde yer alır.
- Hikâye sayfasının boş siyah açılışı kaldırıldı.
- Hikâye blokları büyük sahne kuleleri yerine kompakt, yan yana çalışan editoryal düzene geçirildi.
- Canlı atölye motoru korunarak daha okunaklı iki kolonlu çalışma düzenine alındı.
- Bütün Hikâyeler sayfası tutarlı üç kolonlu görsel kayıt sistemine geçirildi.

## Testler

Kontrol edilen PHP: 7 dosya
Kontrol edilen JSON: 25 dosya
JavaScript sözdizimi: temiz
Yatay taşma kontrolü: temiz

Görsel test ölçüleri:

- 1600 × 1000 masaüstü
- 1100 × 780, yaklaşık %120 yakınlaştırma davranışını temsil eden ara genişlik
- 1024 × 900 tablet
- 390 × 844 mobil

Kontrol edilen sayfalar:

- index.php
- hikaye.php?slug=webbordro
- atolye.php?slug=gorselden-harekete
- hikayeler.php

## Kurulum

Klasörü XAMPP htdocs altına kopyala:

    C:\xampp\htdocs\fikrimvar_v6_1\

Tarayıcıdan aç:

    http://localhost/fikrimvar_v6_1/

## İçerik yapısı

Her hikâye kendi klasöründe yaşar:

    content/stories/<slug>/
      story.json
      media/
      updates/

Yeni hikâye:

    php tools/new_story.php yeni-hikaye "Yeni Hikâye" story

Yeni canlı atölye:

    php tools/new_story.php yeni-atolye "Yeni Atölye" atelier

## Not

Ana sayfadaki atölye alanı, `content/site.json` içindeki `homepage.active_atelier` değerinden son günlük kaydını otomatik okur.
