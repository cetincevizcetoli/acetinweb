<?php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

function showcase_backup_db(): string
{
    $backupDir = FV7_STORAGE . '/backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0775, true);
    }
    $path = $backupDir . '/fikrimvar-test-atelier-showcase-' . date('Ymd-His') . '.sqlite';
    if (!copy(FV7_DB, $path)) {
        throw new RuntimeException('Veritabanı yedeği oluşturulamadı: ' . $path);
    }
    return $path;
}

function showcase_project(string $slug): array
{
    $st = db()->prepare('SELECT * FROM projects WHERE slug=?');
    $st->execute([$slug]);
    $project = $st->fetch();
    if (!$project) {
        throw new RuntimeException('Proje bulunamadı: ' . $slug);
    }
    return $project;
}

function showcase_category_id(string $slug): ?int
{
    $st = db()->prepare('SELECT id FROM categories WHERE slug=?');
    $st->execute([$slug]);
    $id = $st->fetchColumn();
    return $id === false ? null : (int)$id;
}

function showcase_svg(string $title, string $subtitle, string $accent = '#c45a36'): string
{
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 720" width="1200" height="720"><rect width="1200" height="720" fill="#17130f"/><path d="M90 580 C230 300 390 650 540 350 S760 130 1110 260" fill="none" stroke="#6a8f67" stroke-width="5" opacity=".55"/><path d="M90 120 H1110 M90 600 H1110 M180 80 V640 M1020 80 V640" stroke="#40362b" stroke-width="2" opacity=".55"/><circle cx="260" cy="230" r="68" fill="none" stroke="#c99b3f" stroke-width="4" opacity=".6"/><circle cx="900" cy="470" r="105" fill="none" stroke="#c99b3f" stroke-width="4" opacity=".35"/><rect x="92" y="92" width="260" height="42" fill="' . $accent . '"/><text x="120" y="120" font-family="IBM Plex Mono, monospace" font-size="22" fill="#17130f">ATÖLYE / VİTRİN</text><text x="120" y="405" font-family="Georgia, serif" font-size="76" font-weight="700" fill="#f6efe3">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</text><text x="124" y="462" font-family="IBM Plex Mono, monospace" font-size="22" fill="#b7c6a9">' . htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') . '</text></svg>';
}

function showcase_map_svg(): string
{
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 720" width="1200" height="720"><rect width="1200" height="720" fill="#efe5d4"/><g font-family="IBM Plex Mono, monospace" font-size="22" fill="#231b16"><text x="80" y="92">01 HAM KAYIT</text><text x="430" y="92">02 KAYIT TÜRÜ</text><text x="790" y="92">03 HİKÂYE BÖLÜMÜ</text></g><g fill="#17130f"><rect x="70" y="150" width="270" height="130" rx="8"/><rect x="425" y="150" width="270" height="130" rx="8"/><rect x="780" y="150" width="330" height="130" rx="8"/></g><g font-family="Georgia, serif" font-size="33" fill="#f6efe3"><text x="104" y="224">Deneme</text><text x="470" y="224">Sorun / Karar</text><text x="830" y="224">Sahne / Ders</text></g><path d="M350 215 H410 M705 215 H765" stroke="#c45a36" stroke-width="6"/><g font-family="Inter, sans-serif" font-size="24" fill="#5c5147"><text x="90" y="390">Bu demo özellikle her kayıt türünün farklı işe yaradığını gösterir.</text><text x="90" y="445">Günlük not bağlam verir; sorun gerilim kurar; karar yön değiştirir.</text><text x="90" y="500">Medya kanıt olur; kaynak dış referans olur; builder bunları bölümlere çevirir.</text></g></svg>';
}

function showcase_media(int $projectId, string $projectSlug, string $fileName, string $title, string $alt, string $svg): int
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
    $id = $st->fetchColumn();
    if ($id !== false) {
        db()->prepare('UPDATE media SET project_id=?,title=?,alt_text=?,caption=?,size_bytes=?,checksum_sha256=?,deleted_at=NULL WHERE id=?')
            ->execute([$projectId, $title, $alt, $title, $size, $sha, (int)$id]);
        return (int)$id;
    }

    db()->prepare(
        'INSERT INTO media(project_id,file_name,original_name,relative_path,mime_type,media_type,title,alt_text,caption,width,height,size_bytes,checksum_sha256)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
    )->execute([$projectId, $fileName, $fileName, $relative, 'image/svg+xml', 'image', $title, $alt, $title, 1200, 720, $size, $sha]);
    return (int)db()->lastInsertId();
}

function showcase_update(int $projectId, array $data): int
{
    $st = db()->prepare('SELECT id FROM updates WHERE project_id=? AND slug=?');
    $st->execute([$projectId, $data['slug']]);
    $id = $st->fetchColumn();
    $values = [
        $data['work_date'], $data['display_label'], $data['title'], $data['summary'], $data['entry_kind'],
        $data['story_role'] ?? 'auto', $data['story_section_type'] ?? 'auto', $data['story_layout'] ?? 'auto', $data['story_label'] ?? '',
        $data['tried'], $data['failed'], $data['decision'], $data['next_step'], $data['phase'],
        $data['is_milestone'], 'published', 'public', $data['show_in_recent'], $data['sort_order'], now_sql(),
    ];
    if ($id !== false) {
        db()->prepare(
            'UPDATE updates SET work_date=?,display_label=?,title=?,summary=?,entry_kind=?,story_role=?,story_section_type=?,story_layout=?,story_label=?,tried=?,failed=?,decision=?,next_step=?,phase=?,is_milestone=?,status=?,visibility=?,show_in_recent=?,sort_order=?,published_at=?,deleted_at=NULL,updated_at=CURRENT_TIMESTAMP WHERE id=?'
        )->execute([...$values, (int)$id]);
        return (int)$id;
    }
    db()->prepare(
        'INSERT INTO updates(project_id,slug,work_date,display_label,title,summary,entry_kind,story_role,story_section_type,story_layout,story_label,tried,failed,decision,next_step,phase,is_milestone,status,visibility,show_in_recent,sort_order,published_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    )->execute([$projectId, $data['slug'], ...$values]);
    return (int)db()->lastInsertId();
}

function showcase_attach(int $updateId, int $mediaId): void
{
    db()->prepare('INSERT OR IGNORE INTO update_media(update_id,media_id,role,sort_order) VALUES (?,?,"gallery",10)')
        ->execute([$updateId, $mediaId]);
}

function showcase_link(string $ownerType, int $ownerId, string $type, string $title, string $url, int $sortOrder): void
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

function showcase_story(int $projectId): int
{
    $st = db()->prepare('SELECT id FROM stories WHERE project_id=?');
    $st->execute([$projectId]);
    $id = $st->fetchColumn();
    $values = [
        'Bir test projesi nasıl gerçek kullanım rehberine dönüşür?',
        'Atölye kayıtlarını yalnızca günlük gibi değil, hikâyeyi kuran ham malzeme gibi kullanabilir miyim?',
        'Bu hikâye, Atölye kayıt türlerinin bir anlatıya nasıl dönüştüğünü gösteren uygulamalı demo olarak hazırlandı.',
        '5-6 dk',
    ];
    if ($id !== false) {
        db()->prepare('UPDATE stories SET title=?,question=?,summary=?,reading_time=?,status="published",visibility="public",show_on_home=0,show_in_archive=0,published_at=COALESCE(published_at,CURRENT_TIMESTAMP),updated_at=CURRENT_TIMESTAMP,deleted_at=NULL WHERE id=?')
            ->execute([...$values, (int)$id]);
        db()->prepare('DELETE FROM story_sections WHERE story_id=?')->execute([(int)$id]);
        return (int)$id;
    }
    db()->prepare('INSERT INTO stories(project_id,title,question,summary,reading_time,status,visibility,show_on_home,show_in_archive,published_at) VALUES (?,?,?,?,?,"published","public",0,0,CURRENT_TIMESTAMP)')
        ->execute([$projectId, ...$values]);
    return (int)db()->lastInsertId();
}

function showcase_section(int $storyId, array $data): int
{
    db()->prepare(
        'INSERT INTO story_sections(story_id,source_update_id,type,layout,section_kind,label,title,body_text,quote_text,intro_text,note_text,code_text,media_id,sort_order)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    )->execute([
        $storyId, $data['source_update_id'] ?? null, $data['type'], $data['layout'] ?? 'default',
        $data['section_kind'] ?? '', $data['label'] ?? '', $data['title'] ?? '', $data['body_text'] ?? '',
        $data['quote_text'] ?? '', $data['intro_text'] ?? '', $data['note_text'] ?? '', $data['code_text'] ?? '',
        $data['media_id'] ?? null, $data['sort_order'],
    ]);
    return (int)db()->lastInsertId();
}

function showcase_item(int $sectionId, array $data): void
{
    db()->prepare(
        'INSERT INTO story_section_items(section_id,group_key,item_type,step,title,subtitle,text,state,value,media_id,source_update_id,url,sort_order)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
    )->execute([
        $sectionId, $data['group_key'] ?? '', $data['item_type'] ?? 'item', $data['step'] ?? '',
        $data['title'] ?? '', $data['subtitle'] ?? '', $data['text'] ?? '', $data['state'] ?? '',
        $data['value'] ?? '', $data['media_id'] ?? null, $data['source_update_id'] ?? null,
        $data['url'] ?? '', $data['sort_order'] ?? 0,
    ]);
}

$backup = showcase_backup_db();

db()->beginTransaction();
try {
    $project = showcase_project('test-atolye');
    $projectId = (int)$project['id'];
    $categoryId = showcase_category_id('yz-yontem') ?? showcase_category_id('kod-sistem');

    db()->prepare(
        "UPDATE projects SET title=?,question=?,summary=?,category_id=?,status='rehber',status_label='Atölye vitrini',type_label='Demo / rehber',visibility='public',workshop_status='open',workshop_question=?,show_on_home=0,show_in_archive=0,show_in_widget=1,home_section='none',sort_order=35,updated_at=CURRENT_TIMESTAMP,deleted_at=NULL WHERE id=?"
    )->execute([
        'İlk Test Projemiz',
        'Bir Atölye kaydı hangi anda hikâyeye dönüşmeye başlar?',
        'Bu proje artık basit test değil; Atölye kayıt türlerini, medya/kaynak kullanımını ve hikâye dönüşümünü gösteren canlı demo.',
        $categoryId,
        'Atölye kayıt türlerini gerçek bir üretim masası gibi kullanmak ve hikâyeye nasıl beslendiğini göstermek.',
        $projectId,
    ]);

    $coverId = showcase_media($projectId, 'test-atolye', 'showcase-cover.svg', 'Atölye vitrin kapağı', 'Atölye kayıtlarının hikâyeye dönüşümünü gösteren koyu kapak görseli.', showcase_svg('Atölye Vitrini', 'KAYIT TÜRÜ → HİKÂYE BİÇİMİ'));
    $mapId = showcase_media($projectId, 'test-atolye', 'showcase-map.svg', 'Atölye hikâye haritası', 'Ham kayıt, kayıt türü ve hikâye bölümü arasındaki ilişkiyi gösteren şema.', showcase_map_svg());
    db()->prepare('UPDATE projects SET cover_media_id=? WHERE id=?')->execute([$coverId, $projectId]);

    $updates = [];
    $updates['legacy-opening'] = showcase_update($projectId, [
        'slug' => 'atolyetest',
        'work_date' => '2026-07-12',
        'display_label' => 'Gün 00',
        'title' => 'Başlangıç sorusu: yeni proje dışarıdan nasıl görünür?',
        'summary' => 'İlk kayıt, Atölye penceresi ve proje görünürlüğü davranışını anlamak için başlangıç noktası olarak korundu.',
        'entry_kind' => 'journal',
        'story_role' => 'opening',
        'story_section_type' => 'opening',
        'story_layout' => 'wide',
        'story_label' => 'Başlangıç',
        'tried' => 'Yeni proje oluşturulup Atölye görünürlüğü açıldığında ziyaretçinin ne gördüğü kontrol edildi.',
        'failed' => 'Sadece proje açmak, hikâyeye dönüşecek bağlamı açıklamaya yetmedi.',
        'decision' => 'Atölye kaydı, daha ilk anda hikâyedeki rolünü de taşımalı.',
        'next_step' => 'Bir sonraki kayıtta görünürlük ve kayıt sırası ayrı ayrı doğrulanacak.',
        'phase' => 'Başlangıç',
        'is_milestone' => 1,
        'show_in_recent' => 1,
        'sort_order' => 10,
    ]);
    $updates['legacy-visibility'] = showcase_update($projectId, [
        'slug' => 'ilk-kayit',
        'work_date' => '2026-07-12',
        'display_label' => 'Gün 00',
        'title' => 'Görünürlük deneyi: Atölye kaydı tek başına yeterli mi?',
        'summary' => 'Bu kayıt, hikâye olmadan da Atölye kaydının takip edilebilir olması gerektiğini gösterir.',
        'entry_kind' => 'experiment',
        'story_role' => 'experiment',
        'story_section_type' => 'timeline',
        'story_layout' => 'default',
        'story_label' => 'Deneme',
        'tried' => 'Proje Atölye durumuna alındı ve public görünürlük zinciri kontrol edildi.',
        'failed' => 'Kayıt görünse bile hikâyedeki yeri belli değilse okur neye baktığını anlamıyor.',
        'decision' => 'Atölye kaydı, yayın durumu kadar hikâye aday kimliğini de taşımalı.',
        'next_step' => 'Sorun kaydıyla eksik kalan anlam katmanı işaretlenecek.',
        'phase' => 'Görünürlük',
        'is_milestone' => 0,
        'show_in_recent' => 1,
        'sort_order' => 20,
    ]);
    $updates['legacy-problem'] = showcase_update($projectId, [
        'slug' => 'bu-nedir',
        'work_date' => '2026-07-12',
        'display_label' => 'Gün 00',
        'title' => 'Sorun: kayıt var ama hikâyedeki karşılığı belirsiz',
        'summary' => 'Atölye kaydı yalnızca soru-cevap gibi durduğunda, hikâyeye taşınacak bölüm kimliği zayıf kalıyor.',
        'entry_kind' => 'problem',
        'story_role' => 'problem',
        'story_section_type' => 'questions',
        'story_layout' => 'default',
        'story_label' => 'Sorun',
        'tried' => 'Kayıt alanları dolduruldu ve public Atölye görünümü kontrol edildi.',
        'failed' => 'Bu kayıttan kategori, bölüm tipi ve anlatım rolü kendiliğinden güçlü çıkmadı.',
        'decision' => 'Atölye formunda hikâyedeki rol ve bölüm tipi açıkça seçilebilmeli.',
        'next_step' => 'Sıralama kararıyla Atölye akışı ve yayın akışı ayrılacak.',
        'phase' => 'Sürtüşme',
        'is_milestone' => 1,
        'show_in_recent' => 1,
        'sort_order' => 30,
    ]);
    $updates['legacy-order'] = showcase_update($projectId, [
        'slug' => 'siralama-karari',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 01',
        'title' => 'Sıralama kararı: kayıt akışı ve yayın sırası ayrı tutulmalı',
        'summary' => 'Atölye kaydının sırası proje içindeki çalışma akışını, yayın sırası ise public yerleşimi belirlemeli.',
        'entry_kind' => 'decision',
        'story_role' => 'decision',
        'story_section_type' => 'lesson',
        'story_layout' => 'default',
        'story_label' => 'Karar',
        'tried' => 'Aynı proje içinde çalışma kaydı sırası ve yayın sırası birlikte test edildi.',
        'failed' => 'Aynı sayı iki farklı anlama gelince admin tarafında kafa karışıklığı oluştu.',
        'decision' => 'Atölye sırası yalnızca proje içi kayıt akışıdır; yayın sırası ayrı kontrol edilir.',
        'next_step' => 'Demo artık kayıt türü, hikâye rolü ve bölüm önerisini birlikte gösterecek.',
        'phase' => 'Karar',
        'is_milestone' => 1,
        'show_in_recent' => 1,
        'sort_order' => 40,
    ]);
    $updates['journal'] = showcase_update($projectId, [
        'slug' => 'masa-kurulumu',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 01',
        'title' => 'Masa kurulumu: bu demo neyi kanıtlayacak?',
        'summary' => 'Demo, Atölye’nin yalnızca not tutma yeri değil hikâyeyi besleyen ana damar olduğunu göstermek için yeniden kuruldu.',
        'entry_kind' => 'journal',
        'story_role' => 'opening',
        'story_section_type' => 'opening',
        'story_layout' => 'hero-split',
        'story_label' => 'Başlangıç',
        'tried' => 'Önce projenin amacını netleştirdim: her kayıt türü gerçek bir kullanım senaryosu gösterecek.',
        'failed' => 'İki kayıtlı basit test sayfası, sistemi anlatmak yerine eksik hissettiriyordu.',
        'decision' => 'Demo proje, okuyana ve adminde çalışana yol gösterecek şekilde zenginleştirilecek.',
        'next_step' => 'Deneme kaydıyla form alanlarının nasıl kullanılacağı gösterilecek.',
        'phase' => 'Başlangıç',
        'is_milestone' => 0,
        'show_in_recent' => 1,
        'sort_order' => 50,
    ]);
    $updates['experiment'] = showcase_update($projectId, [
        'slug' => 'form-denemesi',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 01',
        'title' => 'Deneme: kayıt türü formu yönlendirsin',
        'summary' => 'Deneme kaydı, “ne denedim, ne olmadı, neye karar verdim?” çizgisini hızlı kurar.',
        'entry_kind' => 'experiment',
        'story_role' => 'experiment',
        'story_section_type' => 'timeline',
        'story_layout' => 'default',
        'story_label' => 'Deneme',
        'tried' => 'Kayıt türü Deneme / test seçildi ve alanlar deneme mantığına göre dolduruldu.',
        'failed' => 'Tek tip kayıt formu, kaynak notu ile teknik sorun kaydını aynı dilde göstermeye çalışıyordu.',
        'decision' => 'Formun yardım metinleri seçilen kayıt türüne göre değişmeli.',
        'next_step' => 'Sorun kaydıyla sürtüşme ve gerilim alanı gösterilecek.',
        'phase' => 'Deneme',
        'is_milestone' => 0,
        'show_in_recent' => 1,
        'sort_order' => 60,
    ]);
    $updates['problem'] = showcase_update($projectId, [
        'slug' => 'sig-demo-sorunu',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 01',
        'title' => 'Sorun: demo sığ kalınca sistem de küçük görünür',
        'summary' => 'Atölye zenginleşmezse hikâyeleştirme de mekanik ve yüzeysel kalır.',
        'entry_kind' => 'problem',
        'story_role' => 'problem',
        'story_section_type' => 'questions',
        'story_layout' => 'default',
        'story_label' => 'Sorun',
        'tried' => 'Önce basit iki kayıtla örnek proje oluşturuldu.',
        'failed' => 'Bu yaklaşım, mevcut hikâye bölüm türlerinin gücünü göstermedi; kullanıcıya “bunu böyle kullan” dedirtmedi.',
        'decision' => 'Demo, gerçek üretim sürecini taklit edecek kadar kapsamlı olmalı.',
        'next_step' => 'Karar kaydıyla Atölye-Hikâye eşleşmesi ana ilke olarak yazılacak.',
        'phase' => 'Sürtüşme',
        'is_milestone' => 1,
        'show_in_recent' => 1,
        'sort_order' => 70,
    ]);
    $updates['decision'] = showcase_update($projectId, [
        'slug' => 'ana-damar-karari',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 02',
        'title' => 'Karar: Atölye ham veri değil, anlatının kaynağı',
        'summary' => 'Hikâye sonradan uydurulmayacak; Atölye’de doğru türle biriken kayıtlar seçilip düzenlenecek.',
        'entry_kind' => 'decision',
        'story_role' => 'decision',
        'story_section_type' => 'lesson',
        'story_layout' => 'default',
        'story_label' => 'Dönüm noktası',
        'tried' => 'Atölye kayıt türleri hikâye bölüm türleriyle eşleştirildi.',
        'failed' => 'Dönüm noktası tiki tek başına yeterli değil; kaydın türü de hikâyedeki rolünü belirlemeli.',
        'decision' => 'Her önemli kayıt, hikâyede sahne, soru, ders, kanıt veya kaynak olarak karşılık bulacak.',
        'next_step' => 'Medya kaydıyla görsel kanıtın nasıl kullanılacağı gösterilecek.',
        'phase' => 'Karar',
        'is_milestone' => 1,
        'show_in_recent' => 1,
        'sort_order' => 80,
    ]);
    $updates['media'] = showcase_update($projectId, [
        'slug' => 'medya-kaniti',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 02',
        'title' => 'Medya: akış haritası kanıt olarak dursun',
        'summary' => 'Bir diyagram, Atölye ile Hikâye arasındaki ilişkiyi metinden daha hızlı anlatır.',
        'entry_kind' => 'media',
        'story_role' => 'media',
        'story_section_type' => 'split',
        'story_layout' => 'wide',
        'story_label' => 'Görsel kanıt',
        'tried' => 'Ham kayıt → kayıt türü → hikâye bölümü akışını gösteren şema eklendi.',
        'failed' => 'Sadece açıklama yazmak, yeni kullanıcıya sistemi yeterince görünür kılmıyordu.',
        'decision' => 'Demo projede medya kaydı özellikle hikâyede kanıt olarak kullanılacak.',
        'next_step' => 'Kaynak kaydıyla dış referans ve sistem içi bağlantı örneği eklenecek.',
        'phase' => 'Kanıt',
        'is_milestone' => 0,
        'show_in_recent' => 1,
        'sort_order' => 90,
    ]);
    showcase_attach($updates['media'], $mapId);
    $updates['source'] = showcase_update($projectId, [
        'slug' => 'kaynak-ve-baglanti',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 02',
        'title' => 'Kaynak: bağlantı kaydı neden ayrı tutulur?',
        'summary' => 'Bağlantılar hikâyede dayanak, dış referans veya çalışan demo olarak anlam kazanır.',
        'entry_kind' => 'source',
        'story_role' => 'source',
        'story_section_type' => 'questions',
        'story_layout' => 'default',
        'story_label' => 'Kaynak',
        'tried' => 'Kaynak kaydına hem site içi Atölye bağlantısı hem depo bağlantısı bağlandı.',
        'failed' => 'Bağlantı yalnızca “Bağlantı” diye görünürse ne işe yaradığı anlaşılmıyor.',
        'decision' => 'Kaynak kaydı, bağlantının neden önemli olduğunu kendi metniyle açıklamalı.',
        'next_step' => 'Bu kayıtlar hikâyede farklı bölüm biçimleriyle gösterilecek.',
        'phase' => 'Kaynak',
        'is_milestone' => 0,
        'show_in_recent' => 1,
        'sort_order' => 100,
    ]);
    showcase_link('update', $updates['source'], 'website', 'Bu Atölye kaydı', 'http://localhost/acetinweb/atolye.php?slug=test-atolye', 10);
    showcase_link('project', $projectId, 'github', 'Kod deposu', 'https://github.com/cetincevizcetoli/acetinweb', 10);
    $updates['status'] = showcase_update($projectId, [
        'slug' => 'durum-kontrolu',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 03',
        'title' => 'Durum: ne hazır, ne eksik, ne riskli?',
        'summary' => 'Demo, yalnızca güzel görünmek için değil, hangi parçanın ne işe yaradığını kontrol etmek için de kullanılmalı.',
        'entry_kind' => 'journal',
        'story_role' => 'status',
        'story_section_type' => 'status',
        'story_layout' => 'default',
        'story_label' => 'Durum',
        'tried' => 'Atölye, Hikâye, medya, kaynak ve builder karşılıkları tek projede kontrol edildi.',
        'failed' => 'Görsel sunum güçlenirken kullanım amacı bulanık kalırsa demo yine eksik sayılır.',
        'decision' => 'Hikâyeye ayrıca durum bölümü eklenecek.',
        'next_step' => 'Son bölümde bu demo projenin nasıl kullanılacağı açıkça yazılacak.',
        'phase' => 'Kontrol',
        'is_milestone' => 0,
        'show_in_recent' => 1,
        'sort_order' => 110,
    ]);
    $updates['closing'] = showcase_update($projectId, [
        'slug' => 'nasil-kullanilir',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 03',
        'title' => 'Kapanış: bu proje kopyalanacak örnek değil, kullanım pusulası',
        'summary' => 'Yeni gerçek projelerde önce Atölye kayıt türü seçilecek; hikâye sonra bu kayıtlardan kurulacak.',
        'entry_kind' => 'decision',
        'story_role' => 'closing',
        'story_section_type' => 'text',
        'story_layout' => 'wide',
        'story_label' => 'Kapanış',
        'tried' => 'Bütün kayıt türleri tek proje içinde örneklendi.',
        'failed' => 'Demo birebir kopyalanacak metin değil; kullanım mantığını göstermeli.',
        'decision' => 'Bu proje, yeni içerik girerken bakılacak referans olarak kalacak.',
        'next_step' => 'Gerçek projelerde aynı mantıkla kayıt girilip builder çıktısı kontrol edilecek.',
        'phase' => 'Kapanış',
        'is_milestone' => 1,
        'show_in_recent' => 1,
        'sort_order' => 120,
    ]);

    $storyId = showcase_story($projectId);
    $opening = showcase_section($storyId, [
        'type' => 'opening',
        'layout' => 'hero-split',
        'section_kind' => 'scene',
        'label' => 'BAŞLANGIÇ',
        'title' => 'Basit testten gerçek Atölye vitrini çıkarmak.',
        'body_text' => 'Bu proje önce sadece görünürlük ve sıra kontrolü için açılmıştı. Sonra eksik kaldığı görüldü: Atölye, Hikâye’ye malzeme veren yer olacaksa demo da bunu gösterecek kadar güçlü olmalıydı.',
        'quote_text' => 'Demo, sistemin kendini anlattığı yerdir.',
        'media_id' => $coverId,
        'source_update_id' => $updates['journal'],
        'sort_order' => 10,
    ]);
    $timeline = showcase_section($storyId, [
        'type' => 'timeline',
        'label' => 'ÜRETİM AKIŞI',
        'title' => 'Kayıtlar ham kaldı ama dağınık kalmadı.',
        'intro_text' => 'Aşağıdaki akış doğrudan Atölye kayıtlarından kuruldu.',
        'media_id' => $mapId,
        'sort_order' => 20,
    ]);
    $i = 1;
    foreach ($updates as $key => $updateId) {
        $row = db()->query('SELECT * FROM updates WHERE id=' . (int)$updateId)->fetch();
        $cfg = atelier_entry_kind_config($row);
        showcase_item($timeline, [
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
    $compare = showcase_section($storyId, [
        'type' => 'compare',
        'label' => 'ÖNCE / SONRA',
        'title' => 'Sığ demo ile üretim vitrini aynı şey değil.',
        'intro_text' => 'Bu bölüm aynı projenin iki kullanım biçimini karşılaştırır.',
        'sort_order' => 30,
    ]);
    showcase_item($compare, ['group_key' => 'left', 'item_type' => 'heading', 'title' => 'Önce', 'sort_order' => 1]);
    showcase_item($compare, ['group_key' => 'left', 'item_type' => 'bullet', 'text' => 'İki kayıt vardı; sadece görünürlük test ediliyordu.', 'sort_order' => 2]);
    showcase_item($compare, ['group_key' => 'left', 'item_type' => 'bullet', 'text' => 'Kayıtların hikâyede neye dönüşeceği anlaşılmıyordu.', 'sort_order' => 3]);
    showcase_item($compare, ['group_key' => 'right', 'item_type' => 'heading', 'title' => 'Şimdi', 'sort_order' => 4]);
    showcase_item($compare, ['group_key' => 'right', 'item_type' => 'bullet', 'text' => 'Her kayıt türü ayrı rol taşıyor: not, deneme, sorun, karar, medya, kaynak.', 'sort_order' => 5]);
    showcase_item($compare, ['group_key' => 'right', 'item_type' => 'bullet', 'text' => 'Hikâye timeline, karşılaştırma, soru, durum, ders ve teknik notla kuruluyor.', 'sort_order' => 6]);
    $questions = showcase_section($storyId, [
        'type' => 'questions',
        'label' => 'KARAR SORULARI',
        'title' => 'Yeni kayıt girerken kendime ne soracağım?',
        'sort_order' => 40,
    ]);
    showcase_item($questions, ['title' => 'Bu kayıt hangi tür?', 'text' => 'Eğer sadece ilerleme notuysa günlük; bir şeyi deniyorsam deneme; takıldıysam sorun; yön değiştiriyorsam karar.', 'source_update_id' => $updates['experiment'], 'sort_order' => 1]);
    showcase_item($questions, ['title' => 'Hikâyeye girecek mi?', 'text' => 'Her kayıt girmek zorunda değil. Dönüm noktası olanlar işaretlenir, diğerleri ham günlükte kalır.', 'source_update_id' => $updates['decision'], 'sort_order' => 2]);
    showcase_item($questions, ['title' => 'Kanıt veya kaynak var mı?', 'text' => 'Medya ve bağlantı ayrı kayıt türleriyle tutulursa hikâye daha ikna edici olur.', 'source_update_id' => $updates['media'], 'sort_order' => 3]);
    $status = showcase_section($storyId, [
        'type' => 'status',
        'label' => 'DURUM PANOSU',
        'title' => 'Bu demo hangi parçaları gösteriyor?',
        'source_update_id' => $updates['status'],
        'sort_order' => 50,
    ]);
    showcase_item($status, ['state' => 'Hazır', 'title' => 'Atölye türleri', 'text' => 'Kayıtlar tür bilgisiyle ayrılıyor.', 'sort_order' => 1]);
    showcase_item($status, ['state' => 'Hazır', 'title' => 'Hikâye karşılığı', 'text' => 'Aynı kayıtlar farklı bölüm biçimlerine taşınıyor.', 'sort_order' => 2]);
    showcase_item($status, ['state' => 'Kontrol', 'title' => 'Görsel sunum', 'text' => 'Atölye tarafı hâlâ hikâye kadar zenginleşmeye açık.', 'sort_order' => 3]);
    $lesson = showcase_section($storyId, [
        'type' => 'lesson',
        'label' => 'KULLANIM DERSİ',
        'title' => 'Bu projeyi nasıl okuyacağım?',
        'source_update_id' => $updates['closing'],
        'sort_order' => 60,
    ]);
    showcase_item($lesson, ['text' => 'Atölye kaydı hızlı girilir; ama türü doğru seçilirse sonra hikâye kurmak kolaylaşır.', 'sort_order' => 1]);
    showcase_item($lesson, ['text' => 'Dönüm noktası tiki, hikâyeye aday kayıtları ayırmak içindir.', 'sort_order' => 2]);
    showcase_item($lesson, ['text' => 'Medya ve kaynak kayıtları, anlatıyı süslemek için değil kanıtlamak için kullanılır.', 'sort_order' => 3]);
    showcase_item($lesson, ['text' => 'Demo proje, yeni içerik girerken bakılacak kullanım pusulasıdır.', 'sort_order' => 4]);
    showcase_section($storyId, [
        'type' => 'code',
        'label' => 'SİSTEM HARİTASI',
        'title' => 'Atölye türü hikâyede neye dönüşür?',
        'body_text' => 'Bu teknik not, demo projenin arkasındaki eşleşmeyi açık bırakır.',
        'code_text' => "journal    -> bağlam / geçiş\nexperiment -> deneme sahnesi\nproblem    -> soru / sürtüşme\ndecision   -> dönüm noktası / ders\nmedia      -> görsel kanıt\nsource     -> kaynak notu",
        'sort_order' => 70,
    ]);
    showcase_section($storyId, [
        'type' => 'text',
        'layout' => 'wide',
        'label' => 'NASIL KULLANDIM?',
        'title' => 'Bu demo bilinçli olarak sistemi anlatacak şekilde yazıldı.',
        'body_text' => "Önce zayıf kalan iki kayıt temizlendi.\n\nSonra aynı projeye farklı kayıt türleri eklendi: günlük, deneme, sorun, karar, medya, kaynak ve durum kontrolü.\n\nMedya kaydı için akış haritası üretildi; kaynak kaydı için bağlantı eklendi.\n\nHikâye tarafında aynı kayıtlar tek biçime sıkıştırılmadı; timeline, karşılaştırma, soru, durum, ders ve teknik not olarak dağıtıldı.",
        'quote_text' => 'Bundan sonra gerçek projelerde Atölye önce üretir, Hikâye sonra seçer ve düzenler.',
        'sort_order' => 80,
    ]);
    db()->commit();
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    throw $e;
}

echo 'Showcase upgraded.' . PHP_EOL;
echo 'Backup: ' . $backup . PHP_EOL;
echo 'Project: test-atolye' . PHP_EOL;
