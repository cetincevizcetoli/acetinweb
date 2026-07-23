# ADR-001: Yaşayan Projeler Mimarisi

**Tarih:** 23 Temmuz 2026
**Durum:** Kabul Edildi
**Bağlam:** FikrimVar, bitmiş ürünleri sergileyen statik bir portfolyo olmaktan çıkıp, projelerin gelişim sürecini, hataları ve güncel durumunu şeffafça yansıtan bir "Açık Ar-Ge Laboratuvarına" dönüşmektedir.

## Mimari Kararlar

### 1. Sistem Merkezine "Proje"nin Alınması
Projeler sadece "Hikâyesi" olan varlıklar olmaktan çıkarılmıştır. Yeni hiyerarşi şu şekildedir:
- **PROJE (Merkez)**
  - **Hikâye:** Geçmişi ve dönüm noktalarını anlatan, "Neden?" sorusuna cevap veren yaşayan vitrin.
  - **Atölye:** Bugünü ve canlı laboratuvar sürecini anlatan mutfak.
  - **Kaynaklar (Dört Kapı):** Projeye ait esnek bağlantılar (GitHub, Demo, Rapor vb.).

### 2. Esnek Kaynak Yönetimi (Links Tablosu)
`projects` tablosuna `github_url` gibi statik sütunlar eklemek reddedilmiştir. Bunun yerine sistemde var olan `links` tablosu kullanılacaktır.
- `owner_type = 'project'` ve `owner_id = ProjeID` ile sınırsız kaynak eklenebilecek.
- Gelecekte eklenebilecek (Figma, Docker, Makale) kaynak türleri için altyapı sınırsız esnekliğe kavuşmuştur.

### 3. Yapısal Sürümleme (Versioning)
Mevcut sürümleri metin (`v2.5`) olarak saklamak yerine; karşılaştırma, sorgulama ve filtreleme yeteneklerini artırmak için veritabanında yapısal olarak tutulmasına karar verilmiştir:
- `version_major` (INT)
- `version_minor` (INT)
- `version_patch` (INT)

### 4. Bağımsız "Son Aktivite" (Last Activity)
Projenin canlılık durumu sadece "Atölye" kayıtlarına bağlanmamış, projenin geneline yayılan bağımsız bir `last_activity_at` sütunu oluşturulmasına karar verilmiştir. Hikaye güncellendiğinde, atölye kaydı girildiğinde veya yeni sürüm çıktığında bu tarih tetiklenecektir.

### 5. Projenin Birinci Sınıf Vatandaşları
Her projenin künyesinde artık değişmez bir şekilde şu metrikler yer alacaktır:
- `status` (🟢 Aktif, 🟡 Beklemede, 🔴 Arşiv vb.)
- `last_activity_at`
- Semantik Sürüm (v1.4.2 vb.)

## Sonuçlar
Bu mimari sayesinde FikrimVar, veri tabanını yormadan ve spagetti koda dönüşmeden 5 yıl sonrasının ihtiyaçlarını bile karşılayabilecek bir esnekliğe kavuşmuştur.
