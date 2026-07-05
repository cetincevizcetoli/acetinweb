#fikrimvar V4 — Dijital Atölye Sergisi

KURULUM
1. fikrimvar_v4 klasörünü XAMPP/htdocs altına kopyalayın.
2. Tarayıcıdan http://localhost/fikrimvar_v4/ adresini açın.
3. PHP 8.1+ önerilir.

TEKNOLOJİ
- PHP + HTML + CSS + Vanilla JavaScript
- Framework ve derleme adımı yoktur.
- İçerikler data/projects.json, data/categories.json ve data/site.json dosyalarından okunur.

ANA SAYFA YAPISI
- Koyu, teknik ve katmanlı #fikrimvar hero sahnesi
- Scroll tabanlı, düşük mesafeli parallax hareketi
- Üç ana afiş: WebBordro, Görselden Harekete, ai-context
- Kategori sekmeleriyle üçer içerik gösteren arşiv keşfi
- Mehmet Fırat için ayrı “Başlangıç Noktası” bölümü
- Kenar Notları sağ çekmecesi ve moderasyon kuyruğu

GÖRSEL DURUMU
Aşağıdaki görseller projeye özel tasarım taslaklarıdır. Ziyaretçiye “placeholder” yazısı göstermezler, fakat gerçek ekran/görsel geldiğinde değiştirilmelidir:
- assets/img/featured/webbordro-system.svg
- assets/img/featured/invoke-flow.svg
- assets/img/featured/ai-context-terminal.svg

Hero için mevcut çatlak çekirdek görseli kullanılmıştır:
- assets/img/hero/hero-core.png

MEHMET FIRAT GÖRSELİ
- assets/img/mentor/mehmet-firat-ders.webp
Bu görsel eski acetin.com.tr arşivinden alınmıştır.

KENAR NOTLARI
- Form gönderileri data/notes-pending.json dosyasına yazılır.
- Hosting ortamında data klasörüne PHP tarafından yazma izni verilmelidir.
- Onaylanan notlar manuel olarak data/notes.json içine taşınabilir.
- CSRF, honeypot ve 45 saniyelik oturum bazlı gönderim sınırı vardır.

BAĞLANTILAR
site.json içindeki YouTube ve Instagram URL alanları henüz # durumundadır. Gerçek profil adresleriyle değiştirilmelidir.

MOBİL VE HAREKET
- Mobilde tek sütuna geçer.
- prefers-reduced-motion etkinse parallax ve reveal hareketleri kapanır.
