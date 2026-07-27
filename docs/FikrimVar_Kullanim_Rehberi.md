# FikrimVar: Atölye ve Hikâye Çalışma Rehberi

FikrimVar, standart bir "blog" veya "portfolyo" sistemi değildir. Projelerin bir gecede mükemmel bir şekilde bitmediğini; hatalar, denemeler ve geri dönüşlerle zaman içinde şekillendiğini savunur. Bu yüzden sistem iki ana evreye ayrılır: **Atölye** ve **Hikâye**.

Bu rehber, Atölye'ye yeni bir kayıt girerken karşılaştığınız alanların ne anlama geldiğini ve bunların ileride Hikâye'ye nasıl dönüşeceğini açıklar.

---

## 1. Temel Kavramlar: Atölye vs. Hikâye

- **Atölye (Ham Çalışma Masası):** Geliştirme yaparken anlık olarak tuttuğunuz loglar, yapay zekaya yazdığınız promptlar, aldığınız hatalar veya denediğiniz kodların ham halidir. Burada mükemmellik aranmaz; amaç "çalışma kanıtı" bırakmaktır.
- **Hikâye (Düzenlenmiş Anlatı):** Proje (veya projenin bir fazı) bittiğinde, Atölye'deki yüzlerce ham kaydın içinden sadece "dönüm noktası" olanları seçerek, okuyucuya bir hikâye gibi sunduğunuz küratörlü alandır. 

---

## 2. Atölye Kaydı (Update) Alanları ve Anlamları

Admin panelinde bir projeye yeni kayıt (`update-new.php`) girerken sistem size bir form sunar. Buradaki her seçimin bir amacı vardır:

### A. Kayıt Türü (Entry Kind)
Sisteme "Ben şu an ne tür bir iş yapıyorum?" bilgisini verirsiniz. Seçtiğiniz türe göre aşağıdaki **İş Blokları (Update Blocks)** otomatik olarak değişir ve size rehberlik eder.

- **Çalışma notu (journal):** Sadece "şunu yaptım, şu sonucu bekliyorum" dediğiniz düz gözlem notlarıdır.
- **Test / çıktı (experiment):** Yapay zekaya bir prompt yazdığınızda veya terminalde bir kod çalıştırdığınızda kullanılır. Girdi ve çıktıyı belgeler.
- **Sorun / hata (problem):** Sistem patladığında, beklenmedik bir hata mesajı aldığınızda seçilir. Hatayı ve çözüm denemesini içerir.
- **Karar / not (decision):** "Artık bu kütüphaneyi kullanmaktan vazgeçtik" veya "Veritabanı mimarisini değiştiriyoruz" gibi projenin yönünü değiştiren dönüm noktası anlarıdır.
- **Medya / görsel inceleme (media):** Kod değil de; bir UI tasarımı, logo denemesi veya ekran görüntüsü üzerinden yorum yapacağınız durumlarda kullanılır.
- **Bağlantı / kaynak notu (source):** Okuduğunuz bir makale, ilham aldığınız bir GitHub reposu veya referans aldığınız bir videoyu kaydetmek içindir.

### B. Hikâyedeki Rolü (Story Role)
Bu alan **çok önemlidir**. Atölye'deki bu ham kayıt, ileride projeyi "Hikâye" olarak yayınlarken sayfada *ne olarak* görünecek?

- **Otomatik belirle:** Sistem, yukarıda seçtiğiniz Kayıt Türü'ne göre en mantıklı rolü kendi atar.
- **Açılış sahnesi:** Hikâyenin giriş kısmında, "Biz bu işe neden başladık?" sorusunu cevaplayan ilk güçlü sahnedir.
- **Sorun / çatışma:** Okuyucuya "Burada işler sarpa sardı" dedirten problem düğümüdür.
- **Deneme:** Bir fikrin veya teknolojinin ilk kez test edildiği ara bölümdür.
- **Karar / dönüm noktası:** Çatışmanın çözüldüğü ve yönün değiştiği ana anlardır.
- **Ders / çıkarım:** Hikâyenin sonunda okuyucuya verilen özet niteliğindeki tecrübedir.

> [!TIP]
> Bir kaydın "Hikâyedeki Rolü", Hikâye editöründe (`story-builder.php`) hangi tasarım şablonuyla (tam sayfa mı, iki sütunlu mu, kod bloğu mu) ekrana basılacağını belirler.

---

## 3. İş Blokları (Update Blocks)

Eski sistemlerdeki tek bir "İçerik" kutusu yerine FikrimVar parçalı bir blok sistemi kullanır. Her kayda istediğiniz kadar blok ekleyebilirsiniz. 

**Neden Blok Sistemi?**
Çünkü okuyucu (veya yapay zeka), neyin **girdi (prompt)**, neyin **çıktı (output)**, neyin **hata (error)** olduğunu anlamalıdır.

- Blok türünü seçin (Örn: *Prompt / komut*).
- Başlık girin (Örn: *Veritabanı oluşturma komutu*).
- Gövdeyi girin (Gerçek kodu veya metni yapıştırın).
- `+ İş bloğu ekle` diyerek bir sonrakine geçin (Örn: *Hata / beklenmeyen sonuç*).

> [!IMPORTANT]
> **Özel Blok: Laboratuvar Notu / Keşif**
> Bir hata çözüldüğünde veya bir karar alındığında, eğer bundan projeyi aşan, gelecekteki projelere de yön verecek genel bir **metodoloji dersi** çıktıysa (Örn: "Yapay zekâya doğrudan görev vermek yerine sınır çizmek daha verimliymiş"), bu bilgiyi normal bir metne gömmek yerine **Laboratuvar Notu** bloğu olarak seçin. 
> Bu özel bloklar, sayfada göze çarpan bir şekilde (🧪 ikonu ile) ayrışır ve ileride bir araya getirilip "Yapay Zeka ile Yazılım Geliştirme Metodolojisi" kitabının yapıtaşlarını oluşturur.

---

## 4. Dönüm Noktası (Milestone) Kavramı

Sayfanın sağ alt köşesinde **"Dönüm noktası"** (is_milestone) adında bir onay kutusu göreceksiniz.
Eğer Atölye'ye girdiğiniz bu kayıt, projenin gidişatını değiştiren, "Hikâyede kesinlikle anlatmalıyım" dediğiniz bir kayıt ise bu kutuyu işaretleyin. 

> [!IMPORTANT]
> Hikâye Oluşturucu (`story-builder.php`), binlerce ham kayıt içinden sadece "Dönüm Noktası" işaretli olanları otomatik olarak çeker ve hikâyenin taslağını oluşturur. Diğerleri ise arka planda, meraklıların okuyabileceği "Ham Atölye Kanıtları" olarak yaşamaya devam eder.

---

## 5. Medya ve Bağlantılar

- **Yeni Medya Yükle / Mevcut Medyadan Seç:** Kod dışındaki kanıtları (ekran görüntüleri, taslak PDF'ler) doğrudan o kayda bağlayabilirsiniz.
- **Bağlantılar (Links):** Eğer deneme yaparken bir YouTube videosu, GitHub reposu veya dış bağlantı kullandıysanız bunu sağ alttaki "Bağlantılar" kısmından ekleyin. (FikrimVar, YouTube veya Vimeo linklerini otomatik olarak Player'a dönüştürüp ekranda oynatılabilir hale getirir).

## Özet İş Akışı
1. Kod yaz, sorun yaşa, prompt üret.
2. `update-new.php` aç. Türü seç (örneğin *Test* veya *Hata*).
3. Blokları doldur (Prompt bloku + Hata bloku + Karar bloku).
4. Önemliyse "Dönüm Noktası" işaretle ve Kaydet.
5. Proje bittiğinde Admin panelinden `story-builder.php` açıp dönüm noktalarını sürükle/bırak ile hikâyeye çevir!

---

## 6. Somut Bir Örnek: "AI-Context GUI" Kaydı

Soyut tanımları bir kenara bırakıp gerçek bir kaydı nasıl doldurduğumuza bakalım:

**Senaryo:** AI-Context aracı CLI üzerinden çok zorluyordu, PyQt6 ile bir arayüz yazmaya karar verdik.

- **Başlık:** AI-Context: CLI'dan GUI'ye Mimari Evrim
- **Kayıt Türü:** `Karar / not` (Çünkü sadece kod denemiyoruz, projenin gidişatını değiştiren bir mimari karar aldık).
- **Hikâyedeki Rolü:** `Açılış sahnesi` (Çünkü bu karar, okuyucuya bu yeni arayüz projesine *neden* başladığımızı anlatan ilk olay).
- **İş Blokları:**
  1. **Saha / Çalışma notu:** "Yaklaşık bir yıldır ai-context'i komut satırından kullanıyorum. Ancak projeler büyüdükçe doğru dosyaları seçmek zaman almaya başladı..."
  2. **Net karar:** "Mevcut CLI aracının sınırlarını aşmak için PyQt6 ile bir masaüstü arayüzü geliştirmeye karar verdik."
- **Dönüm Noktası:** `İşaretli` (Çünkü bu karar hikâyenin omurgasını oluşturuyor).

İşte bu kadar! Bu kayıt kaydedildiğinde; Atölye'de bir karar logu olarak dururken, Hikâye tarafında güçlü bir "Açılış sahnesi" olarak okuyucuyu karşılamaya hazır hale gelir.
