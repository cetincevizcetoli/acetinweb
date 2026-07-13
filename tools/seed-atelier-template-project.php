<?php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

function seed_backup_db(string $label): string
{
    $backupDir = FV7_STORAGE . '/backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0775, true);
    }
    $path = $backupDir . '/fikrimvar-' . $label . '-' . date('Ymd-His') . '.sqlite';
    if (!copy(FV7_DB, $path)) {
        throw new RuntimeException('Veritabanı yedeği oluşturulamadı: ' . $path);
    }
    return $path;
}

function seed_category_id(string $slug): ?int
{
    $st = db()->prepare('SELECT id FROM categories WHERE slug=?');
    $st->execute([$slug]);
    $id = $st->fetchColumn();
    return $id === false ? null : (int)$id;
}

function seed_project_by_slug(string $slug): ?array
{
    $st = db()->prepare('SELECT * FROM projects WHERE slug=?');
    $st->execute([$slug]);
    $row = $st->fetch();
    return $row ?: null;
}

function seed_media(int $projectId, string $projectSlug, string $fileName, string $title, string $alt, string $svg): int
{
    $dir = FV7_UPLOAD_ROOT . '/projects/' . $projectSlug;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $path = $dir . '/' . $fileName;
    file_put_contents($path, $svg);
    $relative = 'uploads/projects/' . $projectSlug . '/' . $fileName;
    $sha = hash_file('sha256', $path) ?: '';
    $size = filesize($path) ?: 0;

    $st = db()->prepare('SELECT id FROM media WHERE relative_path=?');
    $st->execute([$relative]);
    $existing = $st->fetchColumn();
    if ($existing !== false) {
        db()->prepare('UPDATE media SET project_id=?,title=?,alt_text=?,caption=?,size_bytes=?,checksum_sha256=?,deleted_at=NULL WHERE id=?')
            ->execute([$projectId, $title, $alt, $title, $size, $sha, (int)$existing]);
        return (int)$existing;
    }

    db()->prepare(
        'INSERT INTO media(project_id,file_name,original_name,relative_path,mime_type,media_type,title,alt_text,caption,width,height,size_bytes,checksum_sha256)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
    )->execute([$projectId, $fileName, $fileName, $relative, 'image/svg+xml', 'image', $title, $alt, $title, 1200, 720, $size, $sha]);

    return (int)db()->lastInsertId();
}

function seed_update(int $projectId, array $data): int
{
    $st = db()->prepare('SELECT id FROM updates WHERE project_id=? AND slug=?');
    $st->execute([$projectId, $data['slug']]);
    $id = $st->fetchColumn();

    $values = [
        $data['work_date'],
        $data['display_label'],
        $data['title'],
        $data['summary'],
        $data['entry_kind'],
        $data['tried'],
        $data['failed'],
        $data['decision'],
        $data['next_step'],
        $data['phase'],
        $data['is_milestone'],
        'published',
        'public',
        $data['show_in_recent'],
        $data['sort_order'],
        now_sql(),
    ];

    if ($id !== false) {
        db()->prepare(
            'UPDATE updates SET work_date=?,display_label=?,title=?,summary=?,entry_kind=?,tried=?,failed=?,decision=?,next_step=?,phase=?,is_milestone=?,status=?,visibility=?,show_in_recent=?,sort_order=?,published_at=?,deleted_at=NULL,updated_at=CURRENT_TIMESTAMP WHERE id=?'
        )->execute([...$values, (int)$id]);
        return (int)$id;
    }

    db()->prepare(
        'INSERT INTO updates(project_id,slug,work_date,display_label,title,summary,entry_kind,tried,failed,decision,next_step,phase,is_milestone,status,visibility,show_in_recent,sort_order,published_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    )->execute([
        $projectId,
        $data['slug'],
        ...$values,
    ]);

    return (int)db()->lastInsertId();
}

function seed_attach_update_media(int $updateId, int $mediaId): void
{
    db()->prepare('INSERT OR IGNORE INTO update_media(update_id,media_id,role,sort_order) VALUES (?,?,"gallery",10)')
        ->execute([$updateId, $mediaId]);
}

function seed_link(string $ownerType, int $ownerId, string $type, string $title, string $url, int $sortOrder): void
{
    $st = db()->prepare('SELECT id FROM links WHERE owner_type=? AND owner_id=? AND url=?');
    $st->execute([$ownerType, $ownerId, $url]);
    $id = $st->fetchColumn();
    if ($id !== false) {
        db()->prepare('UPDATE links SET link_type=?,title=?,sort_order=? WHERE id=?')->execute([$type, $title, $sortOrder, (int)$id]);
        return;
    }
    db()->prepare('INSERT INTO links(owner_type,owner_id,link_type,title,url,sort_order) VALUES (?,?,?,?,?,?)')
        ->execute([$ownerType, $ownerId, $type, $title, $url, $sortOrder]);
}

function seed_story(int $projectId, string $title, string $question, string $summary, string $readingTime): int
{
    $st = db()->prepare('SELECT id FROM stories WHERE project_id=?');
    $st->execute([$projectId]);
    $id = $st->fetchColumn();
    if ($id !== false) {
        db()->prepare('UPDATE stories SET title=?,question=?,summary=?,reading_time=?,status="published",visibility="public",show_on_home=0,show_in_archive=0,published_at=COALESCE(published_at,CURRENT_TIMESTAMP),updated_at=CURRENT_TIMESTAMP,deleted_at=NULL WHERE id=?')
            ->execute([$title, $question, $summary, $readingTime, (int)$id]);
        db()->prepare('DELETE FROM story_sections WHERE story_id=?')->execute([(int)$id]);
        return (int)$id;
    }

    db()->prepare(
        'INSERT INTO stories(project_id,title,question,summary,reading_time,status,visibility,show_on_home,show_in_archive,published_at)
         VALUES (?,?,?,?,?,"published","public",0,0,CURRENT_TIMESTAMP)'
    )->execute([$projectId, $title, $question, $summary, $readingTime]);

    return (int)db()->lastInsertId();
}

function seed_section(int $storyId, array $data): int
{
    db()->prepare(
        'INSERT INTO story_sections(story_id,source_update_id,type,layout,section_kind,label,title,body_text,quote_text,intro_text,note_text,code_text,media_id,sort_order)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    )->execute([
        $storyId,
        $data['source_update_id'] ?? null,
        $data['type'],
        $data['layout'] ?? 'default',
        $data['section_kind'] ?? '',
        $data['label'] ?? '',
        $data['title'] ?? '',
        $data['body_text'] ?? '',
        $data['quote_text'] ?? '',
        $data['intro_text'] ?? '',
        $data['note_text'] ?? '',
        $data['code_text'] ?? '',
        $data['media_id'] ?? null,
        $data['sort_order'],
    ]);
    return (int)db()->lastInsertId();
}

function seed_item(int $sectionId, array $data): void
{
    db()->prepare(
        'INSERT INTO story_section_items(section_id,group_key,item_type,step,title,subtitle,text,state,value,media_id,source_update_id,url,sort_order)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
    )->execute([
        $sectionId,
        $data['group_key'] ?? '',
        $data['item_type'] ?? 'item',
        $data['step'] ?? '',
        $data['title'] ?? '',
        $data['subtitle'] ?? '',
        $data['text'] ?? '',
        $data['state'] ?? '',
        $data['value'] ?? '',
        $data['media_id'] ?? null,
        $data['source_update_id'] ?? null,
        $data['url'] ?? '',
        $data['sort_order'] ?? 0,
    ]);
}

function seed_cover_svg(string $title, string $subtitle, string $accent = '#d05b38'): string
{
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 720" width="1200" height="720"><rect width="1200" height="720" fill="#17130f"/><path d="M120 520 C260 260 370 660 520 360 S760 150 1040 260" fill="none" stroke="#6a8f67" stroke-width="5" opacity=".55"/><path d="M80 120 H1120 M80 600 H1120 M160 80 V640 M1040 80 V640" stroke="#40362b" stroke-width="2" opacity=".5"/><circle cx="260" cy="210" r="70" fill="none" stroke="#c99b3f" stroke-width="4" opacity=".55"/><circle cx="900" cy="470" r="100" fill="none" stroke="#c99b3f" stroke-width="4" opacity=".35"/><rect x="92" y="92" width="240" height="42" fill="' . $accent . '"/><text x="120" y="120" font-family="IBM Plex Mono, monospace" font-size="22" fill="#17130f">ATÖLYE / ŞABLON</text><text x="120" y="410" font-family="Georgia, serif" font-size="70" font-weight="700" fill="#f6efe3">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</text><text x="124" y="462" font-family="IBM Plex Mono, monospace" font-size="22" fill="#b7c6a9">' . htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') . '</text></svg>';
}

function seed_diagram_svg(): string
{
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 720" width="1200" height="720"><rect width="1200" height="720" fill="#efe5d4"/><g font-family="IBM Plex Mono, monospace" font-size="24" fill="#231b16"><text x="100" y="100">ATÖLYE</text><text x="500" y="100">ŞABLON</text><text x="900" y="100">HİKÂYE</text></g><g fill="#17130f"><rect x="80" y="150" width="240" height="120" rx="8"/><rect x="480" y="150" width="240" height="120" rx="8"/><rect x="880" y="150" width="240" height="120" rx="8"/></g><g font-family="Georgia, serif" font-size="34" fill="#f6efe3"><text x="115" y="220">Ham kayıt</text><text x="515" y="220">Kayıt türü</text><text x="925" y="220">Bölüm</text></g><path d="M330 210 H460 M730 210 H860" stroke="#c45a36" stroke-width="6" marker-end="url(#a)"/><defs><marker id="a" markerWidth="10" markerHeight="10" refX="5" refY="3" orient="auto"><path d="M0 0 L6 3 L0 6 Z" fill="#c45a36"/></marker></defs><g font-family="Inter, sans-serif" font-size="24" fill="#5c5147"><text x="105" y="370">Günlük, deneme, sorun, karar, medya, kaynak</text><text x="105" y="430">Her kayıt hikâyeye ne olarak taşınacağını bilir.</text><text x="105" y="490">Builder bu bilgiyi kullanıp doğru bölüm iskeletini kurar.</text></g></svg>';
}

$backup = seed_backup_db('atelier-template-project');

db()->beginTransaction();
try {
    $categoryId = seed_category_id('yz-yontem') ?? seed_category_id('kod-sistem');

    $test = seed_project_by_slug('test-atolye');
    if ($test) {
        db()->prepare(
            "UPDATE projects SET title=?,question=?,summary=?,status=?,status_label=?,type_label=?,visibility='public',workshop_status='open',workshop_question=?,show_on_home=0,show_in_archive=0,show_in_widget=1,home_section='none',updated_at=CURRENT_TIMESTAMP WHERE id=?"
        )->execute([
            'İlk Test Projemiz',
            'Bir projenin Atölye akışında görünmesi için hangi kayıtlar gerekir?',
            'Atölye görünürlüğü, sıralama ve ham kayıt davranışını denemek için açılmış kontrollü test projesi.',
            'test',
            'Test ediliyor',
            'Atölye testi',
            'Atölye kaydı, yayın sırası ve hikâyeye hazırlık mantığını aynı proje üzerinde doğrulamak.',
            (int)$test['id'],
        ]);

        seed_update((int)$test['id'], [
            'slug' => 'ilk-kayit',
            'work_date' => '2026-07-12',
            'display_label' => '12.07.2026',
            'title' => 'İlk kayıt: Atölye görünürlüğü',
            'summary' => 'Proje Atölye’ye alındığında public sayfada görünür mü diye kontrol edildi.',
            'entry_kind' => 'experiment',
            'tried' => 'Proje public yapıldı, Atölye açık bırakıldı ve Atölye penceresinde göster seçeneği açıldı.',
            'failed' => 'İlk denemede sıralama ve görünürlük metinleri yeterince açık değildi.',
            'decision' => 'Atölye kayıt sırası, yayın sırasından ayrı anlatılmalı.',
            'next_step' => 'Aynı projede ikinci kayıtla sıralama davranışı tekrar denenecek.',
            'phase' => 'Başlangıç',
            'is_milestone' => 0,
            'show_in_recent' => 1,
            'sort_order' => 10,
        ]);

        seed_update((int)$test['id'], [
            'slug' => 'siralama-karari',
            'work_date' => '2026-07-12',
            'display_label' => 'Aynı gün',
            'title' => 'Sıralama kararı: kayıt akışı ayrı tutulmalı',
            'summary' => 'Atölye kaydının sırası proje içindeki çalışma akışını, yayın sırası ise public yerleşimi belirlemeli.',
            'entry_kind' => 'decision',
            'tried' => 'Atölye kaydı sırası ile yayın sırası aynı ekranda karışınca davranış tekrar incelendi.',
            'failed' => 'Aynı numara verilen kayıtlar kullanıcıya yeterince erken uyarı vermiyordu.',
            'decision' => 'Atölye sırası proje içi akış; yayın sırası ana sayfa ve hikâyeler listesi akışı olarak ayrı anlatılacak.',
            'next_step' => 'Yeni Atölye şablon projesinde her kayıt türü ayrı ayrı gösterilecek.',
            'phase' => 'Karar',
            'is_milestone' => 1,
            'show_in_recent' => 1,
            'sort_order' => 20,
        ]);
    }

    $demoSlug = 'atolye-sablon-kullanim-rehberi';
    $demo = seed_project_by_slug($demoSlug);
    if ($demo) {
        $demoId = (int)$demo['id'];
        db()->prepare('DELETE FROM stories WHERE project_id=?')->execute([$demoId]);
        db()->prepare('DELETE FROM updates WHERE project_id=?')->execute([$demoId]);
        db()->prepare('DELETE FROM links WHERE owner_type="project" AND owner_id=?')->execute([$demoId]);
    } else {
        db()->prepare(
            "INSERT INTO projects(slug,title,question,summary,category_id,status,status_label,type_label,visibility,workshop_status,workshop_question,started_at,show_on_home,show_in_archive,show_in_widget,home_section,sort_order,published_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,0,0,'none',900,CURRENT_TIMESTAMP)"
        )->execute([
            $demoSlug,
            'Atölye Şablon Kullanım Rehberi',
            'Bir ham Atölye kaydı, doğru tür seçilince nasıl hikâye malzemesine dönüşür?',
            'Bu test projesi Atölye türlerini, medya ve bağlantı kullanımını, dönüm noktası seçimini ve hikâyeye dönüşümü tek yerde gösterir.',
            $categoryId,
            'rehber',
            'Şablon proje',
            'Test / rehber',
            'public',
            'open',
            'Atölye kayıtlarını hikâye üretiminin ana damarı gibi kullanmak.',
            '2026-07-13',
        ]);
        $demoId = (int)db()->lastInsertId();
    }

    db()->prepare(
        "UPDATE projects SET title=?,question=?,summary=?,category_id=?,status='rehber',status_label='Şablon proje',type_label='Test / rehber',visibility='public',workshop_status='open',workshop_question=?,started_at='2026-07-13',show_on_home=0,show_in_archive=0,show_in_widget=0,home_section='none',sort_order=900,updated_at=CURRENT_TIMESTAMP,deleted_at=NULL WHERE id=?"
    )->execute([
        'Atölye Şablon Kullanım Rehberi',
        'Bir ham Atölye kaydı, doğru tür seçilince nasıl hikâye malzemesine dönüşür?',
        'Bu test projesi Atölye türlerini, medya ve bağlantı kullanımını, dönüm noktası seçimini ve hikâyeye dönüşümü tek yerde gösterir.',
        $categoryId,
        'Atölye kayıtlarını hikâye üretiminin ana damarı gibi kullanmak.',
        $demoId,
    ]);

    $coverId = seed_media($demoId, $demoSlug, 'cover.svg', 'Atölye şablon projesi kapağı', 'Atölye kayıtlarının hikâyeye dönüştüğü şematik kapak görseli.', seed_cover_svg('Atölye Şablon Rehberi', 'HAM KAYIT → ŞABLON → HİKÂYE'));
    $diagramId = seed_media($demoId, $demoSlug, 'atelier-flow.svg', 'Atölye akış diyagramı', 'Atölye kaydından hikâye bölümüne giden akışı gösteren diyagram.', seed_diagram_svg());
    db()->prepare('UPDATE projects SET cover_media_id=? WHERE id=?')->execute([$coverId, $demoId]);

    $updates = [];
    $updates['journal'] = seed_update($demoId, [
        'slug' => 'gunluk-not-baslangic',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 01',
        'title' => 'Günlük not: önce masayı kur',
        'summary' => 'Projeye başlamadan önce neyin takip edileceği ve hangi kayıtların tutulacağı netleştirildi.',
        'entry_kind' => 'journal',
        'tried' => 'Projeyi Atölye durumuna aldım ve ilk kaydın sadece kısa bir çalışma notu olmasına izin verdim.',
        'failed' => 'Her kaydı dönüm noktası yapmaya çalışmak Atölye’yi yavaşlatıyor.',
        'decision' => 'Günlük notlar hızlı tutulacak; yalnızca yön değiştiren kayıtlar dönüm noktası olacak.',
        'next_step' => 'Bir deneme kaydıyla şablonların hikâyeye nasıl beslendiği kontrol edilecek.',
        'phase' => 'Başlangıç',
        'is_milestone' => 0,
        'show_in_recent' => 1,
        'sort_order' => 10,
    ]);
    $updates['experiment'] = seed_update($demoId, [
        'slug' => 'deneme-kaydi-sablon-secimi',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 01',
        'title' => 'Deneme: kayıt türü seçilince form dili değişmeli',
        'summary' => 'Deneme kaydı, ne denendi ve ne öğrenildi sorularını öne çıkarır.',
        'entry_kind' => 'experiment',
        'tried' => 'Kayıt türü Deneme / test seçildi; özet, deneme, sorun ve karar alanları bu bağlama göre dolduruldu.',
        'failed' => 'Tek tip form, her kaydı aynı anlatı kalıbına zorluyordu.',
        'decision' => 'Atölye formu kayıt türüne göre yardım metni göstermeli.',
        'next_step' => 'Sorun kaydı açılıp sürtüşme alanının hikâyeye nasıl taşındığı kontrol edilecek.',
        'phase' => 'Şablon denemesi',
        'is_milestone' => 0,
        'show_in_recent' => 1,
        'sort_order' => 20,
    ]);
    $updates['problem'] = seed_update($demoId, [
        'slug' => 'sorun-kaydi-uyumsuzluk',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 01',
        'title' => 'Sorun: Atölye ve Hikâye ayrı diller konuşuyordu',
        'summary' => 'Atölye başka, Hikâye başka, public sunum başka davranınca üretim akışı kopuk görünüyordu.',
        'entry_kind' => 'problem',
        'tried' => 'Atölye kaydı doğrudan hikâyeye taşındığında başlıklar vardı ama tür ve amaç görünmüyordu.',
        'failed' => 'Okur ve admin, kaydın günlük not mu sorun mu karar mı olduğunu anlamıyordu.',
        'decision' => 'Her Atölye kaydı hikâyede neye dönüşeceğini baştan taşımalı.',
        'next_step' => 'Karar kaydıyla bu dönüşümün dönüm noktası olarak nasıl çalıştığı gösterilecek.',
        'phase' => 'Sürtüşme',
        'is_milestone' => 1,
        'show_in_recent' => 1,
        'sort_order' => 30,
    ]);
    $updates['decision'] = seed_update($demoId, [
        'slug' => 'karar-kaydi-ana-damar',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 02',
        'title' => 'Karar: Atölye hikâyenin ana damarı olacak',
        'summary' => 'Hikâye sonradan yazılan metin değil; Atölye’de biriken doğru işaretlenmiş kayıtların düzenlenmiş hâli olacak.',
        'entry_kind' => 'decision',
        'tried' => 'Kayıt türleri builder tarafından hikâye bölüm tiplerine eşlendi.',
        'failed' => 'Yalnızca “dönüm noktası” tiki tek başına yeterli anlatı bilgisi taşımıyordu.',
        'decision' => 'Atölye kaydı türü, hikâyeye taşınırken bölüm rolünü belirleyecek.',
        'next_step' => 'Medya ve kaynak kayıtlarıyla kanıt ve bağlantı kullanımı gösterilecek.',
        'phase' => 'Karar',
        'is_milestone' => 1,
        'show_in_recent' => 1,
        'sort_order' => 40,
    ]);
    $updates['media'] = seed_update($demoId, [
        'slug' => 'medya-kaydi-akis-diyagrami',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 02',
        'title' => 'Medya: akış diyagramı ekle',
        'summary' => 'Bir görsel, Atölye kaydının hikâyede kanıt olarak kullanılmasını kolaylaştırır.',
        'entry_kind' => 'media',
        'tried' => 'Atölye → şablon → hikâye akışını gösteren basit diyagram eklendi.',
        'failed' => 'Sadece metinle anlatınca yeni sistemin nasıl bağlandığı yeterince hızlı anlaşılmıyordu.',
        'decision' => 'Şablon projede en az bir medya kaydı bulunmalı.',
        'next_step' => 'Kaynak notuyla dış bağlantı kullanımını göstermek.',
        'phase' => 'Kanıt',
        'is_milestone' => 0,
        'show_in_recent' => 1,
        'sort_order' => 50,
    ]);
    seed_attach_update_media($updates['media'], $diagramId);

    $updates['source'] = seed_update($demoId, [
        'slug' => 'kaynak-notu-admin-akisi',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 02',
        'title' => 'Kaynak: admin akışı nereden takip edilir?',
        'summary' => 'Kaynak kaydı, dış bağlantı veya sistem içi referansın hikâyede nasıl kullanılacağını gösterir.',
        'entry_kind' => 'source',
        'tried' => 'Atölye kaydına bağlantı eklendi ve başlık boş kalırsa sistemin anlamlı ad üretmesi beklendi.',
        'failed' => 'Kaynaklar ayrı tutulmazsa hikâye içinde neden gösterildikleri belirsiz kalıyor.',
        'decision' => 'Bağlantı kayıtları hikâyede dayanak veya referans notu olarak kullanılacak.',
        'next_step' => 'Bu proje builder ile hikâyeye çevrilip bölüm karşılıkları kontrol edilecek.',
        'phase' => 'Kaynak',
        'is_milestone' => 0,
        'show_in_recent' => 1,
        'sort_order' => 60,
    ]);
    seed_link('update', $updates['source'], 'website', 'Atölye sayfası', 'https://www.acetin.com.tr/atolye.php?slug=' . $demoSlug, 10);
    seed_link('project', $demoId, 'github', 'Kod deposu', 'https://github.com/cetincevizcetoli/acetinweb', 10);

    $storyId = seed_story(
        $demoId,
        'Atölye kaydı hikâyeye nasıl dönüşür?',
        'Ham çalışma notlarını kaybetmeden düzenli bir hikâyeye çevirmek mümkün mü?',
        'Bu örnek hikâye, Atölye kayıt türlerinin düzenlenmiş anlatıya nasıl dönüştüğünü gösteren canlı şablondur.',
        '3-4 dk'
    );

    $opening = seed_section($storyId, [
        'type' => 'opening',
        'layout' => 'hero-split',
        'section_kind' => 'scene',
        'label' => 'BAŞLANGIÇ',
        'title' => 'Önce kayıt türünü seç, sonra hikâye kendini toplamaya başlar.',
        'body_text' => 'Atölye tarafında her kayıt aynı ağırlıkta değildir. Bazısı günlük nottur, bazısı sorun çıkarır, bazısı projeyi yön değiştirir. Bu proje, o ayrımı görünür yapmak için hazırlandı.',
        'quote_text' => 'Atölye hamdır; ama gelişigüzel değildir.',
        'media_id' => $coverId,
        'sort_order' => 10,
    ]);

    $timeline = seed_section($storyId, [
        'type' => 'timeline',
        'layout' => 'default',
        'section_kind' => 'flow',
        'label' => 'ATÖLYE AKIŞI',
        'title' => 'Altı kayıt türü aynı hikâyeyi besler.',
        'intro_text' => 'Her satır bir Atölye kaydından gelir ve kendi rolünü yanında taşır.',
        'media_id' => $diagramId,
        'sort_order' => 20,
    ]);
    $i = 1;
    foreach ($updates as $key => $updateId) {
        $cfg = atelier_entry_kind_config($key);
        $row = db()->query('SELECT * FROM updates WHERE id=' . (int)$updateId)->fetch();
        seed_item($timeline, [
            'item_type' => (string)$cfg['item_type'],
            'step' => str_pad((string)$i, 2, '0', STR_PAD_LEFT),
            'title' => (string)$row['title'],
            'subtitle' => (string)$cfg['label'],
            'text' => (string)$row['summary'],
            'source_update_id' => $updateId,
            'sort_order' => $i,
        ]);
        $i++;
    }

    $questions = seed_section($storyId, [
        'type' => 'questions',
        'layout' => 'default',
        'section_kind' => 'discovery',
        'label' => 'KONTROL SORULARI',
        'title' => 'Atölye kaydı girerken neye bakacağım?',
        'sort_order' => 30,
    ]);
    seed_item($questions, ['title' => 'Bu kayıt sadece not mu, yoksa karar mı?', 'text' => 'Günlük not hızlı tutulur; karar kaydı ise hikâyede dönüm noktası olarak kullanılabilir.', 'source_update_id' => $updates['decision'], 'sort_order' => 1]);
    seed_item($questions, ['title' => 'Sorun görünür mü olmalı?', 'text' => 'Evet. Sorun kaydı hikâyeye gerilim verir; okur değişimin nedenini görür.', 'source_update_id' => $updates['problem'], 'sort_order' => 2]);
    seed_item($questions, ['title' => 'Medya neden ayrı tür?', 'text' => 'Çünkü görsel veya video bazen metinden daha güçlü kanıttır.', 'source_update_id' => $updates['media'], 'sort_order' => 3]);

    $lesson = seed_section($storyId, [
        'type' => 'lesson',
        'layout' => 'default',
        'section_kind' => 'reflection',
        'label' => 'KULLANIM KURALI',
        'title' => 'Atölye hızlı kalır, Hikâye seçerek anlatır.',
        'sort_order' => 40,
    ]);
    seed_item($lesson, ['text' => 'Her çalışma kaydı hikâyeye girmek zorunda değildir.', 'sort_order' => 1]);
    seed_item($lesson, ['text' => 'Dönüm noktası işaretlenen kayıtlar builder için güçlü adaydır.', 'sort_order' => 2]);
    seed_item($lesson, ['text' => 'Kayıt türü doğru seçilirse hikâyede doğru bölüm rolü oluşur.', 'sort_order' => 3]);
    seed_item($lesson, ['text' => 'Medya ve bağlantılar Atölye’de saklanır, Hikâye’de kanıt olarak kullanılır.', 'sort_order' => 4]);

    seed_section($storyId, [
        'type' => 'code',
        'layout' => 'default',
        'section_kind' => 'technical_note',
        'label' => 'TEKNİK NOT',
        'title' => 'Bu şablon hangi alanları gösteriyor?',
        'body_text' => 'Aşağıdaki kısa harita, Atölye ile Hikâye arasındaki yeni ortak dili özetler.',
        'code_text' => "updates.entry_kind\n  journal    -> bağlam / geçiş\n  experiment -> deneme sahnesi\n  problem    -> soru / sürtüşme\n  decision   -> dönüm noktası / ders\n  media      -> görsel kanıt\n  source     -> kaynak notu",
        'sort_order' => 50,
    ]);

    seed_section($storyId, [
        'type' => 'text',
        'layout' => 'wide',
        'section_kind' => 'closing',
        'label' => 'NASIL KULLANILDI?',
        'title' => 'Bu proje bilinçli olarak bir kullanım kılavuzu gibi yazıldı.',
        'body_text' => "1. Önce proje Atölye durumunda bırakıldı.\n\n2. Her Atölye kayıt türünden birer örnek eklendi.\n\n3. Dönüm noktası olması gereken kayıtlar işaretlendi.\n\n4. Medya ve bağlantı örnekleri aynı projeye bağlandı.\n\n5. Hikâye tarafında aynı kayıtlar timeline, soru, ders ve teknik not olarak gösterildi.",
        'quote_text' => 'Bundan sonra gerçek projelerde de önce Atölye doğru beslenecek; Hikâye sonra seçip düzenleyecek.',
        'sort_order' => 60,
    ]);

    db()->commit();
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    throw $e;
}

echo 'Seed completed.' . PHP_EOL;
echo 'Backup: ' . $backup . PHP_EOL;
echo 'Fixed project: test-atolye' . PHP_EOL;
echo 'Demo project: atolye-sablon-kullanim-rehberi' . PHP_EOL;
