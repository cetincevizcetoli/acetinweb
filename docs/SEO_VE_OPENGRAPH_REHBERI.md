# FikrimVar: SEO ve OpenGraph (OG) Rehberi

Bu belge, sitemize eklediğimiz `og:` ve `twitter:` etiketlerinin ne olduğunu, ne işe yaradığını ve projelerimiz için neden çok önemli olduğunu açıklar.

## 1. OpenGraph (OG) Nedir?
OpenGraph (OG), ilk olarak Facebook tarafından geliştirilen, daha sonra LinkedIn, WhatsApp, iMessage, Discord, Slack gibi hemen hemen **tüm sosyal medya ve mesajlaşma uygulamaları** tarafından standart olarak kabul edilen bir "zengin bağlantı" (rich link) protokolüdür.

Bir web sayfasını tasarlarken biz sayfayı güzel gösteririz. Ancak o sayfanın linkini alıp WhatsApp'ta veya LinkedIn'de paylaştığımızda, WhatsApp bizim HTML/CSS tasarımımızı okuyamaz. Bunun yerine `<head>` etiketleri arasındaki `meta property="og:..."` etiketlerini okur ve paylaşım kutusunu ona göre çizer.

## 2. Ne İşe Yarayacak? Neden Ekledik?
Eğer OG etiketlerimiz olmasaydı:
Sitenin linkini (`acetin.com.tr/hikaye.php?slug=ai-context`) LinkedIn'de paylaştığında sadece sıkıcı bir mavi link olarak görünürdü.

**OG etiketlerini eklediğimiz için şimdi ne olacak?**
Linkleri bir yere kopyalayıp yapıştırdığında (WhatsApp, LinkedIn, Twitter, Discord, Slack vb.) platform otomatik olarak bizim sitemize gelecek, sayfayı tarayacak ve **muazzam bir kapak görseli, kalın bir başlık ve açıklama yazısıyla** zengin bir kart oluşturacak. Tıpkı YouTube videolarını paylaştığımızda çıkan büyük önizlemeler gibi.

### Sitemize Eklediğimiz Temel OG Etiketleri:
* `og:title`: Bağlantının büyük başlığı. Kodumuzda burayı `FikrimVar` başlıkları veya hikâye başlıkları (örn: *YZ'ye aynı projeyi anlatmaktan sıkılınca...*) ile dinamik besliyoruz.
* `og:description`: Bağlantının altındaki kısa özet. Kodumuzda burayı projelerin `summary` alanından besliyoruz.
* `og:image`: **En önemlisi budur!** Sosyal medyada görünen o büyük kapak görselidir. Kodumuzda eğer projenin bir `cover` (kapak) resmi varsa onu, yoksa sitemizin varsayılan havalı FikrimVar hero resmini (`assets/img/hero/hero-core.png`) çekiyoruz.
* `og:url`: Bağlantının tıklandığında tam olarak nereye gideceğini söyler.
* `twitter:card` (summary_large_image): Twitter (X) için özel bir direktiftir. "Paylaşımımı küçük bir kare resimle değil, geniş tam boy bir afiş (banner) gibi göster" emrini verir.

## 3. Nasıl Kullanacaksın?
Senin ekstra bir şey yapmana gerek yok! Kodlarımız artık tamamen **dinamik**.
1. **Yönetim Panelinden** (Admin-local) yeni bir hikâye oluşturduğunda.
2. Hikâyeye bir Başlık, Özet ve Kapak Resmi yüklediğinde.
3. O hikâyeyi yayına aldığında...

O hikâyenin URL'sini alıp LinkedIn'de "Yeni bir hikâye yazdım" diyerek yapıştırdığın saniyede, LinkedIn senin panele yüklediğin o kapak resmini ve başlığı çekip, tıklamaya çok müsait devasa bir paylaşım kartına çevirecektir.

## 4. Test Etme Yöntemi (İpucu)
Projen canlıya (`acetin.com.tr`) çıktığında (localhost'ta çalışmaz, dış dünyaya açık olmalı), linklerinin sosyal medyada nasıl görüneceğini önceden görmek için şu resmi araçları kullanabilirsin:
- **LinkedIn Post Inspector:** https://www.linkedin.com/post-inspector/
- **Twitter Card Validator:** https://cards-dev.twitter.com/validator

> **Özetle:** Eklediğimiz bu altyapı sayesinde FikrimVar hikâyeleri sadece sitenin içinde güzel durmakla kalmayacak; dış dünyada paylaşıldığında da bir dergi kapağı kalitesinde, kurumsal ve çekici görünecek.
