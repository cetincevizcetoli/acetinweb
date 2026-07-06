<?php
declare(strict_types=1);

/**
 * #FikrimVar V7 import aracı
 *
 * Bu dosya acetin.com.tr geliştirme sürecini yeni bir taslak proje,
 * ham Atölye kayıtları ve düzenlenmiş Hikâye bölümleri olarak SQLite'a ekler.
 *
 * Kullanım:
 *   php tools/import-acetin-gelistirmesi.php
 *
 * Aynı slug daha önce eklenmişse tekrar kopya oluşturmaz.
 * Yeniden oluşturmak isterseniz:
 *   php tools/import-acetin-gelistirmesi.php --replace
 */

require_once __DIR__ . '/../app/bootstrap.php';

const PROJECT_SLUG = 'acetin-com-tr-gelistirmesi';

function out(string $message): void
{
    echo $message . PHP_EOL;
}

function backup_database(): string
{
    if (!is_file(FV7_DB)) {
        throw new RuntimeException('Veritabanı bulunamadı: ' . FV7_DB);
    }

    $backup = FV7_STORAGE . '/fikrimvar.sqlite.bak-acetin-gelistirmesi-' . date('Ymd-His');
    if (!copy(FV7_DB, $backup)) {
        throw new RuntimeException('Veritabanı yedeği oluşturulamadı.');
    }

    return $backup;
}

function fetch_project_id_by_slug(string $slug): ?int
{
    $stmt = db()->prepare('SELECT id FROM projects WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int)$id;
}

function category_id_by_slug(string $slug): ?int
{
    $stmt = db()->prepare('SELECT id FROM categories WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int)$id;
}

function cleanup_existing_project(int $projectId): void
{
    $storyId = db()->prepare('SELECT id FROM stories WHERE project_id = ? LIMIT 1');
    $storyId->execute([$projectId]);
    $storyIdValue = $storyId->fetchColumn();

    if ($storyIdValue !== false) {
        $sectionIds = db()->prepare('SELECT id FROM story_sections WHERE story_id = ?');
        $sectionIds->execute([(int)$storyIdValue]);
        foreach ($sectionIds->fetchAll(PDO::FETCH_COLUMN) as $sectionId) {
            db()->prepare("DELETE FROM links WHERE owner_type = 'story_section' AND owner_id = ?")->execute([(int)$sectionId]);
        }
    }

    $updateIds = db()->prepare('SELECT id FROM updates WHERE project_id = ?');
    $updateIds->execute([$projectId]);
    foreach ($updateIds->fetchAll(PDO::FETCH_COLUMN) as $updateId) {
        db()->prepare("DELETE FROM links WHERE owner_type = 'update' AND owner_id = ?")->execute([(int)$updateId]);
    }

    db()->prepare("DELETE FROM links WHERE owner_type = 'project' AND owner_id = ?")->execute([$projectId]);
    db()->prepare('DELETE FROM projects WHERE id = ?')->execute([$projectId]);
}

function insert_project(): int
{
    $categoryId = category_id_by_slug('yz-yontem');

    $stmt = db()->prepare(<<<'SQL'
INSERT INTO projects(
    slug,title,question,summary,category_id,status,status_label,type_label,visibility,
    workshop_status,workshop_question,show_on_home,show_in_archive,show_in_widget,
    home_section,sort_order,started_at,published_at
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
SQL);

    $stmt->execute([
        PROJECT_SLUG,
        'acetin.com.tr Geliştirmesi',
        'Bir site yaparken, aslında yapay zekâyı nasıl kullanacağımı da öğrenmiş olabilir miyim?',
        'Başta sadece yaptığım işleri koyacağım bir site diye düşündüm. Sonra iş büyüdü. Portfolyo mu olacak, Atölye defteri mi olacak, admin nasıl olacak, JSON mu SQLite mı derken sitenin kendisi de başlı başına bir #FikrimVar hikâyesine dönüştü.',
        $categoryId,
        'taslak',
        'Taslak',
        'YZ ile site geliştirme günlüğü',
        'private',
        'paused',
        'Bu siteyi yaparken asıl öğrendiğim şey, yapay zekâya nasıl iş yaptırılır sorusuydu.',
        0,
        0,
        0,
        'none',
        997,
        '2026-06-24',
        null,
    ]);

    return (int)db()->lastInsertId();
}

function insert_story(int $projectId): int
{
    $stmt = db()->prepare(<<<'SQL'
INSERT INTO stories(
    project_id,title,question,summary,reading_time,status,visibility,
    show_on_home,show_in_archive,is_pinned,sort_order,published_at
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
SQL);

    $stmt->execute([
        $projectId,
        'acetin.com.tr Geliştirmesi',
        'Bir site yaparken, aslında yapay zekâyı nasıl kullanacağımı da öğrenmiş olabilir miyim?',
        'Bu hikâye, acetin.com.tr ve #FikrimVar fikrinin 10-15 günlük yoğun bir deneme döneminde nasıl şekillendiğini anlatıyor. ChatGPT, Claude, Gemini ve sonradan Codex masadaydı; ama direksiyonda Ahmet vardı. Başarı da var, bozulma da var, itiraz da var, karar da var.',
        '8-12 dk',
        'draft',
        'private',
        0,
        0,
        0,
        997,
        null,
    ]);

    return (int)db()->lastInsertId();
}

function insert_update_row(int $projectId, int $order, array $data): int
{
    $stmt = db()->prepare(<<<'SQL'
INSERT INTO updates(
    project_id,slug,work_date,display_label,title,summary,tried,failed,decision,next_step,
    phase,is_milestone,status,visibility,show_in_recent,sort_order,published_at
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
SQL);

    $stmt->execute([
        $projectId,
        $data['slug'],
        $data['date'],
        'Gün ' . str_pad((string)$order, 2, '0', STR_PAD_LEFT),
        $data['title'],
        $data['summary'],
        $data['tried'] ?? '',
        $data['failed'] ?? '',
        $data['decision'] ?? '',
        $data['next'] ?? '',
        $data['phase'] ?? 'Site geliştirme',
        1,
        'draft',
        'private',
        1,
        $order,
        null,
    ]);

    return (int)db()->lastInsertId();
}

function insert_section(int $storyId, int $order, array $data): int
{
    $stmt = db()->prepare(<<<'SQL'
INSERT INTO story_sections(
    story_id,type,layout,label,title,body_text,quote_text,intro_text,note_text,code_text,media_id,sort_order
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
SQL);

    $stmt->execute([
        $storyId,
        $data['type'] ?? 'text',
        $data['layout'] ?? 'wide',
        $data['label'] ?? '',
        $data['title'] ?? '',
        $data['body'] ?? '',
        $data['quote'] ?? '',
        $data['intro'] ?? '',
        $data['note'] ?? '',
        $data['code'] ?? '',
        null,
        $order,
    ]);

    $sectionId = (int)db()->lastInsertId();

    foreach (($data['items'] ?? []) as $i => $item) {
        insert_section_item($sectionId, $i + 1, $item);
    }

    foreach (($data['links'] ?? []) as $i => $link) {
        $url = safe_external_url((string)($link['url'] ?? ''));
        if ($url === '') continue;
        db()->prepare("INSERT INTO links(owner_type,owner_id,link_type,title,url,sort_order) VALUES ('story_section',?,?,?,?,?)")
            ->execute([$sectionId, $link['type'] ?? 'external', $link['title'] ?? 'Bağlantı', $url, $i + 1]);
    }

    return $sectionId;
}

function insert_section_item(int $sectionId, int $order, array $item): void
{
    $stmt = db()->prepare(<<<'SQL'
INSERT INTO story_section_items(
    section_id,group_key,item_type,step,title,subtitle,text,state,value,url,sort_order
) VALUES (?,?,?,?,?,?,?,?,?,?,?)
SQL);

    $stmt->execute([
        $sectionId,
        $item['group'] ?? '',
        $item['type'] ?? 'item',
        $item['step'] ?? '',
        $item['title'] ?? '',
        $item['subtitle'] ?? '',
        $item['text'] ?? '',
        $item['state'] ?? '',
        $item['value'] ?? '',
        $item['url'] ?? '',
        $order,
    ]);
}

function story_sections_data(): array
{
    return [
        [
            'type' => 'opening',
            'layout' => 'hero-split',
            'label' => 'AÇILIŞ',
            'title' => 'Site diye başladık, Atölye çıktı.',
            'body' => <<<'TXT'
İlk başta derdim çok basitti.

Yaptığım şeyler bir yerde dursun istedim. WebBordro var, ai-context var, görsel denemeler var, Invoke var, Krita var, video fikirleri var. Bir de bunların arkasında benim nasıl düşündüğüm var.

Sonra fark ettik ki mesele sadece bunları listelemek değil. Benim işim biraz deneme işi. Bazen oluyor, bazen olmuyor. Olmayınca da yok saymak istemiyorum. Çünkü bazen asıl öğrenme orada çıkıyor.

Bu yüzden site de klasik portfolyo gibi duramazdı. Bu site biraz açık çalışma günlüğü olmalıydı.
TXT,
            'quote' => 'Kusursuz olmak değil; denemek, yanılmak, öğrenmek ve fikri hayata geçirmek.',
            'note' => 'Bu hikâye taslak olarak eklendi. Yayına almadan önce admin panelinde okuyup istediğin yerleri düzeltmek için özellikle private bırakıldı.',
        ],
        [
            'type' => 'status',
            'layout' => 'wide',
            'label' => 'MASADAKİLER',
            'title' => 'Dört kişi gibi çalıştık, ama direksiyon bende kaldı.',
            'items' => [
                ['state' => 'Ahmet', 'title' => 'Karar veren taraf', 'text' => 'Bazen beğenmedi, bazen kızdı, bazen “burada bir şey var” dedi. En önemlisi, yapay zekânın verdiği şeyi olduğu gibi kabul etmedi.'],
                ['state' => 'ChatGPT', 'title' => 'Mimar ve not tutan taraf', 'text' => 'Kavramları ayırdı, sistemi parçalara böldü, bazen de acele edip kod yazmaya kalktı. O zaman fren yedi.'],
                ['state' => 'Claude', 'title' => 'Eleştiren ve mesafe koyan taraf', 'text' => 'Tasarımın kalabalıklaştığı, görsellerin yanıltabileceği, sitenin nefes alması gerektiği yerlerde iyi bir dış ses oldu.'],
                ['state' => 'Gemini', 'title' => 'Pratik ama bazen ipin ucunu kaçıran taraf', 'text' => 'Kod yazdı, hız verdi; ama bir ara HTML semantiğini dağıtınca iş katalog siteye döndü. Sonra tekrar masaya alındı.'],
                ['state' => 'Codex', 'title' => 'Sonradan gelen dış denetçi', 'text' => 'Kahraman olmadı. Daha çok kontrol memuru gibi davrandı. “Kod yazma, önce incele” deyince gerçekten işe yaradı.'],
            ],
        ],
        [
            'type' => 'timeline',
            'layout' => 'full-bleed',
            'label' => 'KISA HARİTA',
            'title' => '10-15 gün gibi kısa bir sürede işler böyle büyüdü.',
            'intro' => 'Bunlar resmi tarih değil; işin akışını göstermek için tutulmuş Atölye notları.',
            'items' => [
                ['step' => '01', 'subtitle' => 'Başlangıç', 'title' => 'Site yapalım dedik', 'text' => 'Amaç, yapılan işleri bir yere koymaktı.'],
                ['step' => '02', 'subtitle' => 'Kırılma', 'title' => 'Portfolyo yetmedi', 'text' => 'İşlerin sonucundan çok, nasıl oluştuğu önemli hale geldi.'],
                ['step' => '03', 'subtitle' => 'Tasarım', 'title' => 'Ana sayfa doldu taştı', 'text' => 'Ne varsa koyunca site ferah değil, katalog gibi oldu.'],
                ['step' => '04', 'subtitle' => 'Kimlik', 'title' => '#FikrimVar oturdu', 'text' => 'Fikirlerin ham halini göstermek ana düşünceye dönüştü.'],
                ['step' => '05', 'subtitle' => 'Sistem', 'title' => 'Atölye ve Hikâye ayrıldı', 'text' => 'Ham kayıt ve düzenlenmiş anlatı aynı projenin iki hali oldu.'],
                ['step' => '06', 'subtitle' => 'Admin', 'title' => 'HTML yazmak yetmez dedik', 'text' => 'Sitenin yönetilebilmesi için panel gerekti.'],
                ['step' => '07', 'subtitle' => 'Veri', 'title' => 'JSON’dan SQLite’a geçtik', 'text' => 'Dosya dosya uğraşmak yerine küçük bir veritabanı daha doğru oldu.'],
                ['step' => '08', 'subtitle' => 'Kontrol', 'title' => 'Codex denetledi', 'text' => 'Kaydedilen şeyin public tarafta gerçekten görünüp görünmediği kontrol edildi.'],
            ],
        ],
        [
            'type' => 'text',
            'layout' => 'wide',
            'label' => 'İLK KARAR',
            'title' => 'Portfolyo değil bu.',
            'body' => <<<'TXT'
Bir ara site portfolyo gibi gidiyordu. Kutu kutu işler, düzgün başlıklar, güzel görseller... Dışarıdan bakınca belki temiz dururdu ama bana tam oturmadı.

Çünkü ben sadece “bakın ne yaptım” demek istemiyordum. Nasıl yaptım, nerede takıldım, hangi fikri çöpe attım, hangi fikri sonra tekrar geri aldım, bunlar da görünsün istedim.

Burada önemli olan sonuç kadar yolun kendisiydi. Hatta bazen yol sonuçtan daha öğretici oldu.
TXT,
            'quote' => 'Ben daha ne yapacağıma bile tam karar vermemişken, site benim adıma fazla düzgün konuşmasın istedim.',
        ],
        [
            'type' => 'compare',
            'layout' => 'wide',
            'label' => 'TASARIM KAVGASI',
            'title' => 'Ana sayfayı tıka basa doldurduk.',
            'intro' => 'Bir noktada ana sayfa her şeyi anlatmaya çalışıyordu. O zaman sorun da görünür oldu.',
            'items' => [
                ['group' => 'left', 'type' => 'heading', 'title' => 'Olmayan taraf'],
                ['group' => 'left', 'type' => 'bullet', 'text' => 'Ne varsa ana sayfaya koyduk.'],
                ['group' => 'left', 'type' => 'bullet', 'text' => 'Blog sitesi gibi değil, gazete gibi oldu.'],
                ['group' => 'left', 'type' => 'bullet', 'text' => 'Nefes alacak yer kalmadı.'],
                ['group' => 'left', 'type' => 'bullet', 'text' => 'Ferah site derken katalog siteye yaklaşmaya başladık.'],
                ['group' => 'right', 'type' => 'heading', 'title' => 'Son karar'],
                ['group' => 'right', 'type' => 'bullet', 'text' => 'Hero sitenin gösteri alanı olacak.'],
                ['group' => 'right', 'type' => 'bullet', 'text' => 'Geri kalan alanlar daha sakin duracak.'],
                ['group' => 'right', 'type' => 'bullet', 'text' => 'Projeler bağırmayacak; okuyana yol gösterecek.'],
                ['group' => 'right', 'type' => 'bullet', 'text' => 'Site kendini portfolyo diye satmayacak.'],
            ],
        ],
        [
            'type' => 'code',
            'layout' => 'wide',
            'label' => 'İLK TEKNİK YÖN',
            'title' => 'HTML, PHP ve JSON ile hızlı başlamak mantıklıydı.',
            'note' => 'Başlangıçta büyük framework istemedik. Basit olsun, anlaşılır olsun, dosyayı açınca ne olduğunu göreyim dedim.',
            'code' => <<<'CODE'
<?php
$projects = json_decode(
    file_get_contents(__DIR__ . '/data/projects.json'),
    true
);

foreach ($projects as $project) {
    // İlk fikir buydu:
    // Projeleri JSON'dan oku, kart olarak göster.
    // Hızlıydı ama sistem büyüyünce yetmedi.
}
CODE,
        ],
        [
            'type' => 'text',
            'layout' => 'wide',
            'label' => 'GEMINI ANISI',
            'title' => 'Bir ara iş katalog siteye döndü.',
            'body' => <<<'TXT'
Bu kısım biraz komik kaldı.

Ferah ve net bir site yapmaya çalışırken işler karıştı. Kod parça parça gelmeye başladı. HTML ayrı yerden, CSS ayrı yerden, class isimleri başka yerden konuşuyordu. Bir noktada artık şunu söyledim:

“Kendi verdiğin kod yapısına aykırı kodlar yazıyorsun. Böyle saçma iş olmaz. Hepsi topu 60-70 satır kod yazacaksın, onu da tam yaz. Neden yarım yamalak veriyorsun? Hatalı başladın; çünkü ne CSS uyumlu, ne div’ler, ne de div ID’leri doğru. Baştan yazar mısın?”

Gemini de burada hak verdi. Parça parça yama yaparken HTML hiyerarşisini ve CSS class’larını çorbaya çevirdiğini kabul etti.

Sonra daha da komik bir yer oldu. Sitenin ana kimliğini anlatması gereken bölümü aside içine koydu. Dedim ki:

“Şaka gibisin ya. Soldaki aside bölümüne gerçekten section içinde olması gereken şeyleri yazdın? Vallahi diyecek bir şey yok artık. Bu iş seni aşar, seninle bu işi yapmayacağım.”

Gemini yine hak verdi. aside etiketinin ana vizyonu anlatmak için değil, yan içerik için olduğunu söyledi. Bu iyi bir örnekti. Yapay zekâ bazen hızlı kod yazıyor ama neyi nereye koyduğunu denetlemezsen site bir anda başka şeye dönüşüyor.
TXT,
            'quote' => 'Buradaki ders basitti: YZ kod yazabilir ama HTML semantiğini de, sitenin ruhunu da kontrol etmek yine bize düşüyor.',
            'note' => 'Bu bölüm Gemini ile yaşanan konuşmanın düzeltilmiş özetidir. Birebir günlük değil, Atölye diline alınmış halidir.',
        ],
        [
            'type' => 'text',
            'layout' => 'wide',
            'label' => 'GERİ DÖNÜŞ',
            'title' => 'İki gün sonra Gemini yine masadaydı.',
            'body' => <<<'TXT'
Ben Gemini’yi tamamen dışlamak istemedim. Çünkü mesele bir aracı sevmek veya silmek değildi. Hangi araca hangi işi vereceğini öğrenmekti.

İki gün sonra geldiğimiz noktayı paylaştım:

“Buraya kadar geldik biz. Seni dışlamak istemiyorum ama takıldığın yerden çıkamadın bir türlü. Claude ve GPT ile bu aşamadayız şu anda.”

Gemini bu sefer daha doğru bir yerden cevap verdi. Yeni mimariyi kabul etti, “Hedefi göster, sadece o kısma odaklanayım” dedi.

Bence burası önemli. Yapay zekâyı her işte başrol yapmak gerekmiyor. Bazen sadece belirli bir düğümü çözmesi gerekiyor. Bütün siteyi ona emanet edince dağıtabiliyor. Ama sınır çizince işe yarıyor.
TXT,
        ],
        [
            'type' => 'code',
            'layout' => 'wide',
            'label' => 'HERO DERSİ',
            'title' => 'Bazen büyük sorun tek satırdan çıkıyor.',
            'note' => 'Hero kısmı sitenin kalbi oldu. Karanlık çekirdek, beyaz çatlaklar, organik teller... Ama bir ara organik katman görünmüyordu. Sorun büyük fikirde değil, küçük bir CSS ayrıntısındaydı.',
            'code' => <<<'CODE'
.hero-core--organic {
  aspect-ratio: 2048 / 1117;
}
CODE,
        ],
        [
            'type' => 'compare',
            'layout' => 'wide',
            'label' => 'KAVRAM KARARI',
            'title' => 'Atölye ve Hikâye aynı işin iki hali oldu.',
            'intro' => 'Başta bunlar iki ayrı içerik türü gibi duruyordu. Sonra sistem oturdu.',
            'items' => [
                ['group' => 'left', 'type' => 'heading', 'title' => 'Atölye'],
                ['group' => 'left', 'type' => 'bullet', 'text' => 'İşi yaparken tutulan ham kayıt.'],
                ['group' => 'left', 'type' => 'bullet', 'text' => 'Bugün ne denedim? Ne çalışmadı? Karar ne oldu?'],
                ['group' => 'left', 'type' => 'bullet', 'text' => 'Görsel, video, bağlantı ve küçük notlar burada yaşar.'],
                ['group' => 'right', 'type' => 'heading', 'title' => 'Hikâye'],
                ['group' => 'right', 'type' => 'bullet', 'text' => 'Sonradan ayıklanmış anlatı.'],
                ['group' => 'right', 'type' => 'bullet', 'text' => 'Her kayıt taşınmaz, sadece dönüm noktaları seçilir.'],
                ['group' => 'right', 'type' => 'bullet', 'text' => 'Okuyan biri süreci anlayacak kadar görür, dosya çöplüğüne düşmez.'],
            ],
        ],
        [
            'type' => 'text',
            'layout' => 'wide',
            'label' => 'ADMIN KARARI',
            'title' => 'Her seferinde HTML yazılmaz.',
            'body' => <<<'TXT'
Bir noktadan sonra şunu gördüm: Ben bu siteyle her seferinde HTML yazarak baş edemem.

Bir Atölye kaydı yazacağım, bir görsel ekleyeceğim, belki yanında YouTube ya da Instagram linki olacak. Bunu her defasında kod dosyası açarak yaparsam site beni yorar.

O yüzden admin paneli şart oldu. Ama admin paneli de sadece “veriyi kaydet” demek değil. Kayıt ekleyeceğim, düzenleyeceğim, gizleyeceğim, sıralayacağım, gerekirse sileceğim. Siteyi yönetmek için siteye karşı savaşmayacağım.
TXT,
            'code' => <<<'CODE'
// Asıl istenen şey buydu:
proje_olustur();
atolye_kaydi_ekle();
medya_bagla();
link_ekle();
yayinla_veya_gizle();
sirala();
CODE,
        ],
        [
            'type' => 'compare',
            'layout' => 'wide',
            'label' => 'JSON KUTUSU',
            'title' => 'Admin yaptık sandık, ekrana JSON kutusu geldi.',
            'intro' => 'Bu kırılma noktası önemliydi. Çünkü “kolay yönetim” diye çıktığımız yolda kullanıcıya ham JSON göstermek tersine işti.',
            'items' => [
                ['group' => 'left', 'type' => 'heading', 'title' => 'Olmadı'],
                ['group' => 'left', 'type' => 'bullet', 'text' => 'story.json dosyasını büyük bir kutuda elle düzenlemek.'],
                ['group' => 'left', 'type' => 'bullet', 'text' => 'Blok sırasını, tırnakları, virgülleri benim takip etmem.'],
                ['group' => 'left', 'type' => 'bullet', 'text' => 'Admin paneli var gibi görünüp yine dosya düzenletmesi.'],
                ['group' => 'right', 'type' => 'heading', 'title' => 'Olmalı'],
                ['group' => 'right', 'type' => 'bullet', 'text' => 'Başlık yaz, metin yaz, görsel seç, video ekle.'],
                ['group' => 'right', 'type' => 'bullet', 'text' => 'Blokları yukarı aşağı taşı.'],
                ['group' => 'right', 'type' => 'bullet', 'text' => 'Kaydet ve public tarafta gerçekten gör.'],
            ],
        ],
        [
            'type' => 'text',
            'layout' => 'wide',
            'label' => 'MEDYA VE LİNK HATASI',
            'title' => 'Kaydedildi ama görünmedi.',
            'body' => <<<'TXT'
Sonra başka bir hata çıktı.

Video ekledim, link ekledim. Admin tarafında kayıt var gibi görünüyordu. Ama public sayfada yoktu.

İlk bakışta yazma izni mi yok, SQLite mı kaydetmedi diye düşündük. Ama sorun başka yerdeydi. Veri kaydediliyordu. Sorgu bazı şeyleri alıyordu. Ama renderer her şeyi ekrana basmıyordu.

Burada güzel bir ders çıktı: Bir sistemde veri girmek başka şey, o verinin anlamlı şekilde görünmesi başka şey.
TXT,
            'code' => <<<'CODE'
// Kontrol sırası böyle olmalı:
veri_kaydedildi_mi();
sorgu_veriyi_aliyor_mu();
renderer_bunu_basiyor_mu();
tarayicida_gercekten_gorunuyor_mu();
CODE,
            'quote' => 'Bu dört aşamadan biri eksikse kullanıcı için sistem çalışmıyor.',
        ],
        [
            'type' => 'compare',
            'layout' => 'wide',
            'label' => 'VERİ KARARI',
            'title' => 'MySQL mi, SQLite mı?',
            'intro' => 'Burada da güzel bir insan-YZ tartışması oldu. İlk cevap MySQL’e yakındı, ama kullanım şekli netleşince SQLite daha doğru çıktı.',
            'items' => [
                ['group' => 'left', 'type' => 'heading', 'title' => 'İlk düşünce'],
                ['group' => 'left', 'type' => 'bullet', 'text' => 'MySQL her sunucuda var, ileride büyürse rahat olur.'],
                ['group' => 'left', 'type' => 'bullet', 'text' => 'Yazar sistemi, yorum, üyelik olursa daha güçlü durur.'],
                ['group' => 'left', 'type' => 'bullet', 'text' => 'Ama bu cevap benim gerçek kullanımımı biraz fazla büyüttü.'],
                ['group' => 'right', 'type' => 'heading', 'title' => 'Son karar'],
                ['group' => 'right', 'type' => 'bullet', 'text' => 'Ben tek kişiyim. Her gün on kişi kayıt girmeyecek.'],
                ['group' => 'right', 'type' => 'bullet', 'text' => 'Üç dört günde bir kayıt gireceğim.'],
                ['group' => 'right', 'type' => 'bullet', 'text' => 'Taşıması ve yedeklemesi kolay olsun.'],
                ['group' => 'right', 'type' => 'bullet', 'text' => 'Bu yüzden SQLite bu iş için daha doğru oldu.'],
            ],
        ],
        [
            'type' => 'code',
            'layout' => 'wide',
            'label' => 'SQLITE OMURGASI',
            'title' => 'JSON ana veri olmaktan çıktı.',
            'note' => 'Medya yine dosyada kaldı. Ama proje, hikâye, Atölye kaydı, link ve sıralama ilişkileri SQLite’a geçti.',
            'code' => <<<'CODE'
storage/fikrimvar.sqlite

projects
updates
stories
story_sections
media
links

// JSON artık ana sistem değil.
// En fazla yedek, aktarım veya dışarı verme biçimi.
CODE,
        ],
        [
            'type' => 'text',
            'layout' => 'wide',
            'label' => 'CODEX',
            'title' => 'Codex’i kod yazdırmak için değil, önce denetlemek için kullandık.',
            'body' => <<<'TXT'
Bir noktadan sonra projeye dışarıdan baktırmak istedim.

Codex’e verdiğimiz ilk iş “kod yaz” değildi. Tam tersine, “hiçbir dosyayı değiştirme, önce incele” dedik.

Bu önemliydi. Çünkü yapay zekâya her zaman kod yazdırmak gerekmiyor. Bazen sadece baktırmak daha doğru. Nerede veri kaydediliyor, nerede public taraf bunu görmüyor, hangi tik çalışıyor gibi şeyleri dış gözle yakaladı.

Mesela admin’de bazı görünürlük seçenekleri vardı ama public sorgular hepsine aynı şekilde bakmıyordu. Yani kullanıcı “gösterme” diyordu ama sistem başka yerden karar veriyordu.
TXT,
            'quote' => 'YZ sadece üretici değil. Denetçi de olabilir. Ama sınırı net çizmek gerekiyor.',
        ],
        [
            'type' => 'code',
            'layout' => 'wide',
            'label' => 'GIT ANI',
            'title' => 'Git tarafında da küçük bir panik yaşandı.',
            'note' => 'Klasör adı değişti, repo kökte mi olsun alt klasörde mi olsun derken birkaç tur döndük. Sonunda “ne varsa gitsin” dedik. Belki en temiz yöntem değildi ama o anda işi kayıt altına aldı.',
            'code' => <<<'CODE'
git add -f .
git commit -m "acetinweb ilk sürüm"
git branch -M main
git remote add origin https://github.com/cetincevizcetoli/acetinweb.git
git push -u origin main
CODE,
        ],
        [
            'type' => 'lesson',
            'layout' => 'wide',
            'label' => 'ÖĞRENDİKLERİM',
            'title' => 'Bu iş sadece site geliştirmek değildi.',
            'items' => [
                ['text' => 'Yapay zekâya iş verirken önce sınır çizmek gerekiyor. “Her şeyi düzelt” dersen çalışan şeyi de bozabiliyor.'],
                ['text' => 'Küçük paketlerle gitmek daha güvenli. Önce incele, sonra tek işi yap, sonra test et.'],
                ['text' => 'YZ’nin verdiği kodu kabul etmek zorunda değilsin. Bazen kullanıcı yapay zekâyı ikna etmeli.'],
                ['text' => 'Admin kaydediyor diye public taraf çalışıyor sanmamak gerekiyor.'],
                ['text' => 'Teknik karar, gerçek kullanım şekline göre verilmeli. SQLite kararı bunun güzel örneğiydi.'],
                ['text' => 'Başarısız deneme çöpe atılacak şey değil. Bazen sonraki doğru kararın sebebi oluyor.'],
            ],
        ],
        [
            'type' => 'questions',
            'layout' => 'wide',
            'label' => 'KENDİME SORULAR',
            'title' => 'Bu hikâye daha bitmedi.',
            'items' => [
                ['title' => 'Bu site artık ne?', 'text' => 'Klasik portfolyo değil. Yaptığım işlerin ve o işlere giderken yaşanan kararların açık günlüğü.'],
                ['title' => 'Atölye Günlüğü ne zaman ayrı sayfa olacak?', 'text' => 'Hikâyeler sayfası netleşti. Sırada ham Atölye kayıtlarının tarih sıralı akışı var.'],
                ['title' => 'YZ burada ne işe yaradı?', 'text' => 'Sadece kod yazmadı. Bazen tartıştı, bazen eleştirdi, bazen bozdu, bazen düzeltti, bazen sadece dış göz oldu.'],
                ['title' => 'Bu hikâye neden önemli?', 'text' => 'Çünkü #FikrimVar’ın söylediği şeyi kendi üzerinde gösteriyor: fikir, deneme, hata, karar ve tekrar deneme.'],
            ],
        ],
        [
            'type' => 'text',
            'layout' => 'wide',
            'label' => 'BUGÜN',
            'title' => 'Elimizde artık sadece bir site yok.',
            'body' => <<<'TXT'
Şu anda elimizde sadece bir site yok. Küçük bir içerik yönetim sistemi var.

Projeler var. Atölye kayıtları var. Hikâyeler var. Medya var. Linkler var. Görünürlük, sıralama, yayınlama, gizleme var. Admin yerelde çalışıyor. Public taraf daha sakin duruyor. Hero da kendi yerinde nefes alıyor.

Daha bitmedi. Zaten bu sitenin olayı da bu.

Bir fikir var. Biraz kurcalıyoruz. Olursa hikâye oluyor. Olmazsa Atölye defterinde izi kalıyor.
TXT,
        ],
    ];
}

function updates_data(): array
{
    return [
        [
            'slug' => 'site-diye-basladik',
            'date' => '2026-06-24',
            'title' => 'Site diye başladık',
            'summary' => 'Başta amaç yapılan işleri bir yerde toplamaktı.',
            'tried' => 'Projeleri kartlar halinde gösterecek sade bir site fikriyle başladık.',
            'failed' => 'Kısa süre sonra sadece sonuç göstermenin bana yetmeyeceği ortaya çıktı.',
            'decision' => 'Sitenin asıl meselesi sonuç değil, süreç olmalı.',
            'next' => 'Portfolyo mu, çalışma günlüğü mü ayrımını netleştirmek.',
        ],
        [
            'slug' => 'portfolyo-yetmedi',
            'date' => '2026-06-25',
            'title' => 'Portfolyo bana dar geldi',
            'summary' => 'İşlerin arkasındaki düşünme biçimini göstermek daha önemli hale geldi.',
            'tried' => 'Klasik proje vitrini gibi duran metinler ve kartlar denendi.',
            'failed' => 'Fazla düzgün ve dışarıdan konuşan bir dil oluştu.',
            'decision' => '#FikrimVar açık çalışma günlüğü olacak.',
            'next' => 'Ana sayfanın tonunu ve yoğunluğunu azaltmak.',
        ],
        [
            'slug' => 'anasayfa-doldu',
            'date' => '2026-06-26',
            'title' => 'Ana sayfayı tıka basa doldurduk',
            'summary' => 'Ne varsa koyunca site ferah değil, gazete gibi oldu.',
            'tried' => 'Projeler, açıklamalar, bağlantılar ve pek çok içerik ana sayfaya alındı.',
            'failed' => 'Sayfa nefes almadı. Fikirler öne çıkacağına birbirinin üstüne bindi.',
            'decision' => 'Hero gösteri alanı olacak, geri kalan yapı daha sakin kalacak.',
            'next' => 'Hero kimliğini güçlendirmek.',
        ],
        [
            'slug' => 'gemini-katalog-kazasi',
            'date' => '2026-06-27',
            'title' => 'Gemini ile katalog site kazası',
            'summary' => 'Ferah site derken HTML semantiği dağıldı, katalog havası çıktı.',
            'tried' => 'Gemini’den daha temiz HTML/CSS yapısı istendi.',
            'failed' => 'Parça parça kodlar, uyumsuz class ve ID’ler, yanlış aside kullanımı işi bozdu.',
            'decision' => 'YZ’den gelen kodu denetlemeden kabul etmeyeceğiz.',
            'next' => 'Araçları tüm siteye değil, net sınırlı görevlere çağırmak.',
        ],
        [
            'slug' => 'hero-kalbi-oldu',
            'date' => '2026-06-28',
            'title' => 'Hero sitenin kalbi oldu',
            'summary' => 'Karanlık çekirdek, beyaz çatlaklar ve organik teller sitenin ana duygusunu taşıdı.',
            'tried' => 'Parlak çatlaklar, organik tel katmanları ve geniş yörüngeler denendi.',
            'failed' => 'Bir ara organik katman yükseklik yüzünden görünmedi.',
            'decision' => 'Hero korunacak, geri kalan tasarım onu boğmayacak.',
            'next' => 'Hareketi abartmadan daha rafine tutmak.',
        ],
        [
            'slug' => 'atolye-hikaye-ayrimi',
            'date' => '2026-06-29',
            'title' => 'Atölye ve Hikâye ayrımı oturdu',
            'summary' => 'Aynı projenin ham kayıt ve düzenlenmiş anlatı olmak üzere iki hali oldu.',
            'tried' => 'Atölye ve Hikâyeyi ayrı içerik tipleri gibi düşündük.',
            'failed' => 'Bu ayrım projeleri gereksiz bölüyordu.',
            'decision' => 'Atölye ve Hikâye aynı projenin iki görünümü olacak.',
            'next' => 'Admin panelini bu yaşam döngüsüne göre kurmak.',
        ],
        [
            'slug' => 'admin-paneli-sart-oldu',
            'date' => '2026-06-30',
            'title' => 'Admin paneli şart oldu',
            'summary' => 'Her yeni kayıt için HTML yazmak sürdürülebilir değildi.',
            'tried' => 'İçeriği dosyalar üzerinden elle yönetme fikri devam ettirildi.',
            'failed' => 'Site büyüdükçe bu yöntem beni yoracaktı.',
            'decision' => 'Projeler, kayıtlar, medya ve linkler panelden yönetilecek.',
            'next' => 'Ham JSON göstermeyen form tabanlı editör yapmak.',
        ],
        [
            'slug' => 'json-kutusu-faciasi',
            'date' => '2026-07-01',
            'title' => 'JSON kutusu faciası',
            'summary' => 'Admin var sandık ama ekrana kocaman JSON düzenleme alanı geldi.',
            'tried' => 'Story bölümlerini JSON olarak gösteren editör denendi.',
            'failed' => 'Bu admin değil, başka bir dosya düzenleme derdiydi.',
            'decision' => 'JSON kullanıcıdan gizlenecek, formlar kullanılacak.',
            'next' => 'Veri modelini daha sağlam hale getirmek.',
        ],
        [
            'slug' => 'medya-link-gorunmedi',
            'date' => '2026-07-02',
            'title' => 'Medya ve link kaydedildi ama görünmedi',
            'summary' => 'Admin tarafı kayıt alıyor gibi görünüyordu ama public taraf her şeyi basmıyordu.',
            'tried' => 'Video, görsel ve link ekleme test edildi.',
            'failed' => 'Bazı alanlar kaydedildiği halde hikâyede görünmedi.',
            'decision' => 'Kaydetme, sorgulama, render ve tarayıcı testi ayrı ayrı kontrol edilecek.',
            'next' => 'Public render bütünlüğünü düzeltmek.',
        ],
        [
            'slug' => 'sqlite-karari',
            'date' => '2026-07-03',
            'title' => 'SQLite kararı',
            'summary' => 'MySQL yerine kullanım şekline daha uygun olan SQLite seçildi.',
            'tried' => 'MySQL ve SQLite seçenekleri tartışıldı.',
            'failed' => 'MySQL bu aşama için fazla ağır durdu.',
            'decision' => 'Tek kullanıcı ve taşınabilirlik için SQLite yeterli.',
            'next' => 'JSON verisini SQLite sistemine oturtmak.',
        ],
        [
            'slug' => 'codex-dis-goz',
            'date' => '2026-07-04',
            'title' => 'Codex dış göz oldu',
            'summary' => 'Codex’e önce kod yazdırmadık, inceleme yaptırdık.',
            'tried' => 'Projeyi güvenlik, veri modeli ve public davranış açısından incelettik.',
            'failed' => 'Bazı admin kontrollerinin public karşılığı eksik çıktı.',
            'decision' => 'Codex küçük, sınırları net paketlerle kullanılacak.',
            'next' => 'V7.1 kavram ve navigasyon temizliğini tamamlamak.',
        ],
        [
            'slug' => 'git-ve-ne-varsa-gitsin',
            'date' => '2026-07-05',
            'title' => 'Git ve “ne varsa gitsin” anı',
            'summary' => 'Klasör, arşiv ve repo karıştı; sonunda projeyi kayıt altına alma kararı verildi.',
            'tried' => 'Repo kökte mi olsun, fikrimvar_v7 içinde mi olsun tartışıldı.',
            'failed' => 'İlk etapta temizlikten çok ilerleme gerekiyordu.',
            'decision' => 'Ne varsa gitsin, sonra temizleriz.',
            'next' => 'Repo temizliğini ileride ayrı iş olarak ele almak.',
        ],
        [
            'slug' => 'v71-kavram-temizligi',
            'date' => '2026-07-06',
            'title' => 'V7.1 kavram temizliği',
            'summary' => 'Hikâyeler, Atölye penceresi ve Bütün kayıtlar karışıklığı azaltıldı.',
            'tried' => 'Codex’e sadece kavram ve navigasyon temizliği görevi verildi.',
            'failed' => 'Tam Atölye Günlüğü sayfası henüz yok.',
            'decision' => 'Yanıltıcı Bütün kayıtlar bağlantısı kaldırıldı, Hikâyeler sayfası netleştirildi.',
            'next' => 'Gerçek Atölye Günlüğü sayfası ve proje linklerinin public renderı sırada.',
        ],
    ];
}

function insert_project_links(int $projectId): void
{
    $links = [
        ['type' => 'github', 'title' => 'GitHub deposu', 'url' => 'https://github.com/cetincevizcetoli/acetinweb'],
    ];

    foreach ($links as $i => $link) {
        db()->prepare("INSERT INTO links(owner_type,owner_id,link_type,title,url,sort_order) VALUES ('project',?,?,?,?,?)")
            ->execute([$projectId, $link['type'], $link['title'], $link['url'], $i + 1]);
    }
}

$replace = in_array('--replace', $argv, true);

try {
    out('#FikrimVar import başladı: acetin.com.tr Geliştirmesi');
    $backup = backup_database();
    out('Veritabanı yedeği oluşturuldu: ' . $backup);

    $existingId = fetch_project_id_by_slug(PROJECT_SLUG);
    if ($existingId !== null && !$replace) {
        out('Bu proje zaten var: ' . PROJECT_SLUG);
        out('Tekrar oluşturmak için: php tools/import-acetin-gelistirmesi.php --replace');
        exit(0);
    }

    db()->beginTransaction();

    if ($existingId !== null && $replace) {
        cleanup_existing_project($existingId);
        out('Eski taslak temizlendi: project_id=' . $existingId);
    }

    $projectId = insert_project();
    $storyId = insert_story($projectId);

    insert_project_links($projectId);

    $updateCount = 0;
    foreach (updates_data() as $i => $update) {
        insert_update_row($projectId, $i + 1, $update);
        $updateCount++;
    }

    $sectionCount = 0;
    $itemCount = 0;
    foreach (story_sections_data() as $i => $section) {
        insert_section($storyId, $i + 1, $section);
        $sectionCount++;
        $itemCount += count($section['items'] ?? []);
    }

    db()->commit();

    out('Proje eklendi: acetin.com.tr Geliştirmesi');
    out('Atölye kaydı: ' . $updateCount);
    out('Hikâye bölümü: ' . $sectionCount);
    out('Hikâye alt öğesi: ' . $itemCount);
    out('Durum: Taslak / private / ana sayfa kapalı / arşiv kapalı');
    out('Admin panelinden kontrol edin: admin-local/project-edit.php?id=' . $projectId);
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    fwrite(STDERR, 'Import hatası: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
