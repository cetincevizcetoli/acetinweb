# #fikrimvar v2

Teknoloji: Saf PHP + HTML + CSS + Vanilla JavaScript. Framework ve derleme yok.

Kurulum:
1. Klasörü XAMPP htdocs içine kopyalayın.
2. Tarayıcıda /fikrimvar_v2/index.php adresini açın.
3. data/projects.json ve data/site.json içerikleri yönetir.
4. assets/img/projects içindeki SVG dosyaları geçici yer tutuculardır.

Önemli:
- YouTube ve Instagram URL'leri site.json içinde şimdilik # değerindedir.
- Mehmet Fırat bölümü ana sayfada data/site.json üzerinden yönetilir.
- Yorum formu data/notes-pending.json dosyasına moderasyon bekleyen kayıt yazar. Sunucuda data klasörünün yazılabilir olması gerekir.
- Production öncesi yorum mekanizmasına ek rate-limit / CAPTCHA tercihi değerlendirilebilir.
- Eski sitelerde bulunan .env, veritabanı dosyaları ve gizli anahtarlar bu pakete alınmamıştır.
