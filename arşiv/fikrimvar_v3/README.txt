#fikrimvar v3

Kurulum
1. fikrimvar_v3 klasörünü XAMPP htdocs içine kopyalayın.
2. Tarayıcıdan klasörün index.php dosyasını açın.
3. PHP 8.0 veya üzeri önerilir.

Bu sürümde değişenler
- Ana sayfadaki ayrı "atölye panosu" kaldırıldı.
- Hero tek bir güncel proje kartıyla bütünleştirildi.
- Hero'nun hemen altına dört gerçek kayıttan oluşan Son Kayıtlar bölümü eklendi.
- Header kimliği "Ahmet Çetin / #fikrimvar" olarak netleştirildi.
- Mehmet Fırat teşekkür bölümünde eski sitedeki gerçek ders ekran görüntüsü kullanıldı.
- Kayıt arşivi, çalışma alanları ve ziyaretçi notları korunarak yeniden düzenlendi.
- projects.json içindeki hatalı görsel dosya yolları düzeltildi.
- Mobil yerleşim güncellendi.

İçerik yönetimi
- data/projects.json: projeler, durumlar, araçlar ve detay metinleri
- data/categories.json: çalışma alanları
- data/site.json: ana metinler, öne çıkan proje ve Son Kayıtlar seçimi
- data/notes.json: onaylı ziyaretçi notları
- data/notes-pending.json: moderasyon bekleyen notlar

Görseller
Proje SVG'leri bilinçli olarak geçici yer tutucudur. Gerçek proje görselleri hazırlandığında aynı dosya yolu korunarak değiştirilebilir veya projects.json içindeki thumbnail alanı güncellenebilir.
