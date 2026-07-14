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
        throw new RuntimeException('Veritabani yedegi olusturulamadi: ' . $path);
    }
    return $path;
}

function showcase_project(string $slug): array
{
    $st = db()->prepare('SELECT * FROM projects WHERE slug=?');
    $st->execute([$slug]);
    $project = $st->fetch(PDO::FETCH_ASSOC);
    if (!$project) {
        throw new RuntimeException('Proje bulunamadi: ' . $slug);
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

function showcase_svg(string $title, string $subtitle, string $accent = '#c99b3f'): string
{
    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $subtitle = htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8');
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 720" width="1200" height="720"><rect width="1200" height="720" fill="#17130f"/><path d="M92 560 H1110 M92 468 H1110 M92 376 H1110 M92 284 H1110 M92 192 H1110" stroke="#40362b" stroke-width="2" opacity=".65"/><path d="M230 120 V620 M520 120 V620 M810 120 V620" stroke="#40362b" stroke-width="2" opacity=".65"/><rect x="112" y="210" width="260" height="120" rx="10" fill="#efe5d4"/><rect x="465" y="330" width="260" height="120" rx="10" fill="#efe5d4"/><rect x="815" y="250" width="260" height="120" rx="10" fill="#efe5d4"/><path d="M373 270 H452 M728 390 H802" stroke="' . $accent . '" stroke-width="8"/><rect x="92" y="92" width="300" height="42" fill="' . $accent . '"/><text x="120" y="120" font-family="IBM Plex Mono, monospace" font-size="22" fill="#17130f">ATOLYE / SAHA NOTU</text><text x="120" y="520" font-family="Georgia, serif" font-size="72" font-weight="700" fill="#f6efe3">' . $title . '</text><text x="124" y="578" font-family="IBM Plex Mono, monospace" font-size="22" fill="#b7c6a9">' . $subtitle . '</text></svg>';
}

function showcase_map_svg(): string
{
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 720" width="1200" height="720"><rect width="1200" height="720" fill="#efe5d4"/><g font-family="IBM Plex Mono, monospace" font-size="22" fill="#231b16"><text x="80" y="92">SIPARIS</text><text x="430" y="92">URETIMDE</text><text x="800" y="92">TESLIM</text></g><g fill="#17130f"><rect x="70" y="150" width="280" height="130" rx="8"/><rect x="420" y="150" width="280" height="130" rx="8"/><rect x="790" y="150" width="280" height="130" rx="8"/></g><g font-family="Georgia, serif" font-size="31" fill="#f6efe3"><text x="104" y="224">Bekleyen</text><text x="458" y="224">Atolyede</text><text x="830" y="224">Biten</text></g><path d="M360 215 H410 M710 215 H780" stroke="#c45a36" stroke-width="6"/><g font-family="Inter, sans-serif" font-size="24" fill="#5c5147"><text x="90" y="390">Ilk taslakta rapor yok. Sadece is kartinin nerede durdugu gorunuyor.</text><text x="90" y="445">Usta telefonda cevap vermek yerine karti bir sutundan digerine surukleyecek.</text><text x="90" y="500">Hikayede bu gorsel, karar aninin kaniti olarak kullanilacak.</text></g></svg>';
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
        $data['work_date'],
        $data['display_label'],
        $data['title'],
        $data['summary'],
        $data['entry_kind'],
        $data['story_role'],
        $data['story_section_type'],
        $data['story_layout'],
        $data['story_label'],
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

function showcase_blocks(int $updateId, array $blocks): void
{
    UpdateBlockRepository::saveForUpdate($updateId, $blocks);
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
        'Bir ustanın telefon trafiği nasıl küçük bir üretim panosuna dönüştü?',
        'Üç kişilik bir imalathanede işin nerede takıldığını görmek için gerçekten büyük bir sisteme ihtiyaç var mı?',
        'Bu hikâye, küçük bir üretim atölyesinde defter, telefon ve Excel arasında kaybolan siparişleri görünür kılma denemesinden doğdu.',
        '4-5 dk',
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

function showcase_item(int $sectionId, array $data): void
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

function showcase_cleanup_old_meta_updates(int $projectId): void
{
    $keep = [
        'saha-gorunumu',
        'excel-denemesi',
        'telefon-sorunu',
        'uc-sutun-karari',
        'pano-taslagi',
        'usta-geri-bildirimi',
        'rapor-degil-akis',
        'hikaye-cekirdegi',
    ];
    $placeholders = implode(',', array_fill(0, count($keep), '?'));
    db()->prepare("UPDATE updates SET deleted_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE project_id=? AND slug NOT IN ($placeholders)")
        ->execute([$projectId, ...$keep]);
}

$backup = showcase_backup_db();

db()->beginTransaction();
try {
    $project = showcase_project('test-atolye');
    $projectId = (int)$project['id'];
    $categoryId = showcase_category_id('kod-sistem') ?? showcase_category_id('yz-yontem');

    db()->prepare(
        "UPDATE projects SET title=?,question=?,summary=?,category_id=?,status='working',status_label='Atölyede',type_label='Sanal üretim projesi',visibility='public',workshop_status='open',workshop_question=?,show_on_home=0,show_in_archive=0,show_in_widget=1,home_section='none',sort_order=35,updated_at=CURRENT_TIMESTAMP,deleted_at=NULL WHERE id=?"
    )->execute([
        'Mini Üretim Takip Sistemi',
        'Üç kişilik üretimde işin nerede takıldığını nasıl görebiliriz?',
        'Defter, telefon ve Excel arasında kaybolan küçük siparişleri tek ekranda izleme denemesi.',
        $categoryId,
        'Küçük imalathanede sipariş, üretim ve teslim akışını sade bir pano ile görünür yapmak.',
        $projectId,
    ]);

    $coverId = showcase_media(
        $projectId,
        'test-atolye',
        'showcase-cover.svg',
        'Mini üretim takip kapağı',
        'Küçük üretim takibi için bekleyen, üretimde ve teslim sütunlarını gösteren kapak görseli.',
        showcase_svg('Mini Üretim Takip', 'SIPARIS -> URETIM -> TESLIM')
    );
    $mapId = showcase_media(
        $projectId,
        'test-atolye',
        'showcase-map.svg',
        'Üretim panosu taslağı',
        'Sipariş, üretimde ve teslim sütunlarından oluşan sade üretim panosu şeması.',
        showcase_map_svg()
    );
    db()->prepare('UPDATE projects SET cover_media_id=? WHERE id=?')->execute([$coverId, $projectId]);

    $updates = [];
    $updates['saha'] = showcase_update($projectId, [
        'slug' => 'saha-gorunumu',
        'work_date' => '2026-07-12',
        'display_label' => 'Gün 01',
        'title' => 'Ham kayıt: üretimde sorulan gerçek durum cümleleri',
        'summary' => 'Telefon konuşmalarından ve defter düzeninden üretim takibinde gereken ilk veri çıkarıldı.',
        'entry_kind' => 'journal',
        'story_role' => 'opening',
        'story_section_type' => 'opening',
        'story_layout' => 'hero-split',
        'story_label' => 'Başlangıç',
        'tried' => "Saha notu:\n09:40 - “Mehmet ustanın parçası kesime girdi mi?”\n10:15 - “Dünkü sipariş montajda mı bekliyor?”\n11:05 - “Teslim bugün mü, yarın mı?”\n\nDefterde alanlar: müşteri, iş adı, adet, teslim tarihi.",
        'failed' => 'Gözlem: defter siparişi saklıyor ama “şu anda nerede?” sorusuna hızlı cevap vermiyor.',
        'decision' => 'İlk veri modeli siparişin konumuna odaklanacak: bekleyen, üretimde, teslim.',
        'next_step' => 'Bu ham notları YZ’ye verip minimum pano alanlarını çıkart.',
        'phase' => 'Başlangıç',
        'is_milestone' => 1,
        'show_in_recent' => 1,
        'sort_order' => 10,
    ]);
    $updates['excel'] = showcase_update($projectId, [
        'slug' => 'excel-denemesi',
        'work_date' => '2026-07-12',
        'display_label' => 'Gün 01',
        'title' => 'Prompt 01: ham notlardan minimum pano alanlarını çıkar',
        'summary' => 'Saha notu YZ’ye verildi; ilk cevap fazla tablo koktuğu için daraltma gerekti.',
        'entry_kind' => 'experiment',
        'story_role' => 'experiment',
        'story_section_type' => 'timeline',
        'story_layout' => 'default',
        'story_label' => 'Deneme',
        'tried' => "PROMPT:\nAşağıdaki üretim notlarından küçük bir atölye için minimum takip panosu alanlarını çıkar. CRM, stok veya muhasebe önerme. Sadece “iş nerede?” sorusuna cevap veren alanları ver.\n\nNotlar:\n- Kesime girdi mi?\n- Montajda mı bekliyor?\n- Teslim bugün mü?",
        'failed' => "YZ cevabı:\n1. Sipariş no\n2. Müşteri\n3. Ürün adı\n4. Sorumlu kişi\n5. Durum\n6. Teslim tarihi\n7. Açıklama\n\nSorun: cevap hâlâ form/tablo gibi. Sahada hızlı bakılacak pano değil.",
        'decision' => 'Alanları 4’e indir: iş adı, müşteri, teslim tarihi, durum.',
        'next_step' => 'Bu dört alanla statik HTML kart iskeleti yaz.',
        'phase' => 'Deneme',
        'is_milestone' => 0,
        'show_in_recent' => 1,
        'sort_order' => 20,
    ]);
    $updates['telefon'] = showcase_update($projectId, [
        'slug' => 'telefon-sorunu',
        'work_date' => '2026-07-12',
        'display_label' => 'Gün 01',
        'title' => 'Test sonucu: tablo açılmadı, telefon trafiği devam etti',
        'summary' => 'Excel benzeri takip denenince üretimde kimse dosyayı güncellemedi; problem çözülmedi.',
        'entry_kind' => 'problem',
        'story_role' => 'problem',
        'story_section_type' => 'questions',
        'story_layout' => 'default',
        'story_label' => 'Hata kaydı',
        'tried' => "Test:\nExcel benzeri liste oluşturuldu.\nDurum seçenekleri: Bekleyen / Üretimde / Teslim.\nKullanıcıdan gün içinde durum değiştirmesi istendi.",
        'failed' => "Belirti / log:\n- Dosya 1 kez açıldı.\n- 3 siparişten yalnızca 1’i güncellendi.\n- “Bu iş nerede?” sorusu yine telefondan geldi.",
        'decision' => 'Liste değil pano. Kullanıcı satır düzenlemeyecek; kartı başka sütuna taşıyacak.',
        'next_step' => 'Üç sütunlu pano iskeleti kodlanacak.',
        'phase' => 'Hata kayıtları',
        'is_milestone' => 1,
        'show_in_recent' => 1,
        'sort_order' => 30,
    ]);
    $updates['karar'] = showcase_update($projectId, [
        'slug' => 'uc-sutun-karari',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 02',
        'title' => 'Karar kaydı: ilk ekran üç sütunlu pano olacak',
        'summary' => 'İlk sürüm yalnızca Bekleyen, Üretimde ve Teslim edildi akışını gösterecek.',
        'entry_kind' => 'decision',
        'story_role' => 'decision',
        'story_section_type' => 'lesson',
        'story_layout' => 'default',
        'story_label' => 'Karar',
        'tried' => "Kapsam listesi:\n1. Stok takibi\n2. Cari hesap\n3. Rapor ekranı\n4. Üç sütunlu pano\n5. Kullanıcı rolleri\n\nKullanıcı sorusu: “Şu iş nerede?”",
        'failed' => 'Kanıt: stok/cari/rapor konuşulunca ilk problem kayboluyor. Telefonla sorulan şey stok değil, “iş nerede?” sorusu.',
        'decision' => 'Kapsam: sadece üretim panosu. Stok, cari ve rapor ikinci aşamaya alınmayacak.',
        'next_step' => 'Pano taslağı SVG olarak eklenecek ve kart alanları kontrol edilecek.',
        'phase' => 'Karar',
        'is_milestone' => 1,
        'show_in_recent' => 1,
        'sort_order' => 40,
    ]);
    $updates['pano'] = showcase_update($projectId, [
        'slug' => 'pano-taslagi',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 02',
        'title' => 'Görsel çıktı: üç sütunlu pano SVG eskizi',
        'summary' => 'Sipariş kartları üç sütunda durunca işin nerede beklediği metin okumadan görüldü.',
        'entry_kind' => 'media',
        'story_role' => 'media',
        'story_section_type' => 'split',
        'story_layout' => 'wide',
        'story_label' => 'Görsel kanıt',
        'tried' => "Dosya üretildi:\nuploads/projects/test-atolye/showcase-map.svg\n\nGörsel yapı:\n- Sol: SIPARIS / bekleyen\n- Orta: URETIMDE\n- Sağ: TESLIM\n- Kart akışı oklarla gösterildi.",
        'failed' => 'Gözlem: karta çok bilgi koyunca pano tekrar tabloya benziyor.',
        'decision' => 'Kartta yalnızca müşteri, iş adı ve teslim tarihi kalacak.',
        'next_step' => 'Bu eskiz kullanıcıya gösterilecek; eksik alan sorulacak.',
        'phase' => 'Kanıt',
        'is_milestone' => 0,
        'show_in_recent' => 1,
        'sort_order' => 50,
    ]);
    showcase_attach($updates['pano'], $mapId);
    $updates['geri'] = showcase_update($projectId, [
        'slug' => 'usta-geri-bildirimi',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 02',
        'title' => 'Görüşme notu: barkod sonraya, hızlı durum değişimi şimdi',
        'summary' => 'İlk görüşmede barkod fikri konuşuldu, ama kullanıcı asıl ihtiyacın hızlı durum değiştirmek olduğunu söyledi.',
        'entry_kind' => 'source',
        'story_role' => 'source',
        'story_section_type' => 'questions',
        'story_layout' => 'default',
        'story_label' => 'Kaynak',
        'tried' => "Görüşme notu:\nSoru: “Barkod okutsak işi çözer mi?”\nCevap: “Önce işin nerede olduğunu hızlı görelim. Barkod sonra olabilir.”\n\nKaynak: kullanıcı görüşmesi / sözlü not.",
        'failed' => 'Barkod ilk sürüm için fazla ağır. Etiket basma ve okutma düzeni yok.',
        'decision' => 'İlk prototipte barkod yok. Kart durum değişimi tek işlem olacak.',
        'next_step' => 'Kartı bir sütundan diğerine taşıyan küçük JS prototipi denenecek.',
        'phase' => 'Saha notu',
        'is_milestone' => 0,
        'show_in_recent' => 1,
        'sort_order' => 60,
    ]);
    showcase_link('update', $updates['geri'], 'website', 'Atölye prototip notu', 'http://localhost/acetinweb/atolye.php?slug=test-atolye', 10);
    $updates['durum'] = showcase_update($projectId, [
        'slug' => 'rapor-degil-akis',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 03',
        'title' => 'Kod kaydı: üç sütun ve örnek sipariş kartı',
        'summary' => 'Pano davranışı için ilk HTML iskeleti ve örnek kart yapısı yazıldı.',
        'entry_kind' => 'experiment',
        'story_role' => 'status',
        'story_section_type' => 'status',
        'story_layout' => 'default',
        'story_label' => 'Durum',
        'tried' => "<section class=\"board\">\n  <div class=\"lane\" data-status=\"bekleyen\">\n    <article class=\"job-card\" data-id=\"S-1024\">\n      <strong>Kapak kesimi</strong>\n      <span>Mehmet Fırat · 12 Temmuz</span>\n    </article>\n  </div>\n  <div class=\"lane\" data-status=\"uretimde\"></div>\n  <div class=\"lane\" data-status=\"teslim\"></div>\n</section>",
        'failed' => "Çıktı:\n- Kart görünüyor.\n- Sütunlar ayrılıyor.\n- Henüz taşıma yok.\n- Mobilde kart genişliği kontrol edilmedi.",
        'decision' => 'Önce tek kartın okunaklılığı ve kolon yapısı doğrulanacak. Sonra taşıma davranışı eklenecek.',
        'next_step' => 'Butonla durum değiştirme dene: “Üretime al”, “Teslim edildi”.',
        'phase' => 'Kod / çıktı',
        'is_milestone' => 0,
        'show_in_recent' => 1,
        'sort_order' => 90,
    ]);
    $updates['cekirdek'] = showcase_update($projectId, [
        'slug' => 'hikaye-cekirdegi',
        'work_date' => '2026-07-13',
        'display_label' => 'Gün 03',
        'title' => 'Builder çıktısı kontrolü: kod kaydı hikâyeye girmesin',
        'summary' => 'Hikâye taslağı için seçilecek kayıtlar ayrıldı; kod ve görüşme notu ham kanıt olarak bırakıldı.',
        'entry_kind' => 'decision',
        'story_role' => 'closing',
        'story_section_type' => 'text',
        'story_layout' => 'wide',
        'story_label' => 'Kapanış',
        'tried' => "Builder kontrol listesi:\n\nHikâyeye taşınacak kayıtlar:\n- saha notu: problemin nereden çıktığını kurar\n- prompt 01: YZ ile ilk daraltma denemesini gösterir\n- tablo testi hatası: listenin neden yetmediğini kanıtlar\n- üç sütun kararı: yön değişimini anlatır\n- pano SVG: okura görsel sonuç verir\n\nHam Atölye kanıtı olarak kalacak kayıtlar:\n- HTML kart iskeleti\n- görüşme notu",
        'failed' => 'İlk taslak kod kaydını da ana anlatıya soktu; hikâye “neden bu projeye başladım?” sorusundan uzaklaşıp teknik rapora döndü.',
        'decision' => 'Hikâye, telefon trafiğinden üç sütunlu panoya giden nedeni anlatacak. Kod kaydı Atölye içinde kanıt olarak kalacak.',
        'next_step' => 'Story builder’da yalnızca dönüm noktası kayıtları işaretli kalacak; kod çıktısı gerekirse ilgili bölümde kısa kanıt olarak bağlanacak.',
        'phase' => 'Kapanış',
        'is_milestone' => 1,
        'show_in_recent' => 1,
        'sort_order' => 80,
    ]);

    showcase_blocks($updates['saha'], [
        ['block_type' => 'field_note', 'title' => 'Telefonla gelen gerçek sorular', 'body' => "09:40 - Mehmet ustanın parçası kesime girdi mi?\n10:15 - Dünkü sipariş montajda mı bekliyor?\n11:05 - Teslim bugün mü, yarın mı?", 'sort_order' => 10],
        ['block_type' => 'observation', 'title' => 'Defterin yetmediği yer', 'body' => 'Defter siparişi saklıyor, ama üretim anında “şu iş nerede?” sorusuna hızlı cevap vermiyor.', 'sort_order' => 20],
        ['block_type' => 'decision', 'title' => 'İlk veri sınırı', 'body' => 'İlk kayıt modeli yalnızca siparişin konumuna odaklanacak: bekleyen, üretimde, teslim.', 'sort_order' => 30],
        ['block_type' => 'next', 'title' => 'YZ’ye sorulacak iş', 'body' => 'Bu ham notlardan minimum pano alanlarını çıkart; CRM, stok ve muhasebeyi dışarıda bırak.', 'sort_order' => 40],
    ]);
    showcase_blocks($updates['excel'], [
        ['block_type' => 'prompt', 'title' => 'Prompt 01', 'body' => "Aşağıdaki üretim notlarından küçük bir atölye için minimum takip panosu alanlarını çıkar. CRM, stok veya muhasebe önerme. Sadece “iş nerede?” sorusuna cevap veren alanları ver.\n\nNotlar:\n- Kesime girdi mi?\n- Montajda mı bekliyor?\n- Teslim bugün mü?", 'sort_order' => 10],
        ['block_type' => 'output', 'title' => 'İlk YZ cevabı', 'body' => "1. Sipariş no\n2. Müşteri\n3. Ürün adı\n4. Sorumlu kişi\n5. Durum\n6. Teslim tarihi\n7. Açıklama", 'sort_order' => 20],
        ['block_type' => 'error', 'title' => 'Cevabın sorunu', 'body' => 'Cevap hâlâ form/tablo gibi. Sahada hızlı bakılacak pano fikrine dönüşmedi.', 'sort_order' => 30],
        ['block_type' => 'decision', 'title' => 'Daraltma kararı', 'body' => 'Alanlar 4’e indirilecek: iş adı, müşteri, teslim tarihi, durum.', 'sort_order' => 40],
    ]);
    showcase_blocks($updates['telefon'], [
        ['block_type' => 'field_note', 'title' => 'Test senaryosu', 'body' => "Excel benzeri liste oluşturuldu.\nDurum seçenekleri: Bekleyen / Üretimde / Teslim.\nKullanıcıdan gün içinde durum değiştirmesi istendi.", 'sort_order' => 10],
        ['block_type' => 'evidence', 'title' => 'Gözlenen sonuç', 'body' => "Dosya 1 kez açıldı.\n3 siparişten yalnızca 1’i güncellendi.\n“Bu iş nerede?” sorusu yine telefondan geldi.", 'sort_order' => 20],
        ['block_type' => 'decision', 'title' => 'Liste değil pano', 'body' => 'Kullanıcı satır düzenlemeyecek; kartı başka sütuna taşıyacak.', 'sort_order' => 30],
        ['block_type' => 'next', 'title' => 'Sonraki prototip', 'body' => 'Üç sütunlu pano iskeleti kodlanacak.', 'sort_order' => 40],
    ]);
    showcase_blocks($updates['karar'], [
        ['block_type' => 'field_note', 'title' => 'Masadaki seçenekler', 'body' => "Stok takibi\nCari hesap\nRapor ekranı\nÜç sütunlu pano\nKullanıcı rolleri", 'sort_order' => 10],
        ['block_type' => 'evidence', 'title' => 'Kararı zorlayan soru', 'body' => 'Telefonla sorulan şey stok değil; “şu iş nerede?” sorusu.', 'sort_order' => 20],
        ['block_type' => 'decision', 'title' => 'Net kapsam', 'body' => 'İlk sürüm sadece üretim panosu olacak. Stok, cari ve rapor ikinci aşamaya alınmayacak.', 'sort_order' => 30],
        ['block_type' => 'next', 'title' => 'Uygulanacak iş', 'body' => 'Pano taslağı SVG olarak eklenecek ve kart alanları kontrol edilecek.', 'sort_order' => 40],
    ]);
    showcase_blocks($updates['pano'], [
        ['block_type' => 'source', 'title' => 'Üretilen görsel', 'body' => 'uploads/projects/test-atolye/showcase-map.svg', 'sort_order' => 10],
        ['block_type' => 'observation', 'title' => 'Görselin gösterdiği', 'body' => 'Sipariş kartları üç sütunda durunca işin nerede beklediği metin okumadan görülüyor.', 'sort_order' => 20],
        ['block_type' => 'decision', 'title' => 'Kart sade kalacak', 'body' => 'Kartta yalnızca müşteri, iş adı ve teslim tarihi kalacak.', 'sort_order' => 30],
    ]);
    showcase_blocks($updates['geri'], [
        ['block_type' => 'source', 'title' => 'Kullanıcı görüşmesi', 'body' => "Soru: Barkod okutsak işi çözer mi?\nCevap: Önce işin nerede olduğunu hızlı görelim. Barkod sonra olabilir.", 'sort_order' => 10],
        ['block_type' => 'observation', 'title' => 'Barkod neden erken?', 'body' => 'Etiket basma ve okutma düzeni yok. İlk problem durum görünürlüğü.', 'sort_order' => 20],
        ['block_type' => 'decision', 'title' => 'İlk prototip kararı', 'body' => 'Barkod yok. Kart durum değişimi tek işlem olacak.', 'sort_order' => 30],
    ]);
    showcase_blocks($updates['durum'], [
        ['block_type' => 'code', 'title' => 'İlk HTML iskeleti', 'body' => "<section class=\"board\">\n  <div class=\"lane\" data-status=\"bekleyen\">\n    <article class=\"job-card\" data-id=\"S-1024\">\n      <strong>Kapak kesimi</strong>\n      <span>Mehmet Fırat · 12 Temmuz</span>\n    </article>\n  </div>\n  <div class=\"lane\" data-status=\"uretimde\"></div>\n  <div class=\"lane\" data-status=\"teslim\"></div>\n</section>", 'sort_order' => 10],
        ['block_type' => 'output', 'title' => 'Kontrol çıktısı', 'body' => "Kart görünüyor.\nSütunlar ayrılıyor.\nHenüz taşıma yok.\nMobilde kart genişliği kontrol edilmedi.", 'sort_order' => 20],
        ['block_type' => 'next', 'title' => 'Sıradaki test', 'body' => 'Butonla durum değiştirme dene: Üretime al, Teslim edildi.', 'sort_order' => 30],
    ]);
    showcase_blocks($updates['cekirdek'], [
        ['block_type' => 'story_note', 'title' => 'Hikâyeye girecek kayıtlar', 'body' => "Saha notu: problemin nereden çıktığını kurar.\nPrompt 01: YZ ile ilk daraltma denemesini gösterir.\nTablo testi hatası: listenin neden yetmediğini kanıtlar.\nÜç sütun kararı: yön değişimini anlatır.\nPano SVG: okura görsel sonuç verir.", 'sort_order' => 10],
        ['block_type' => 'decision', 'title' => 'Ham kalacak kayıtlar', 'body' => 'HTML kart iskeleti ve görüşme notu Atölye kanıtı olarak kalabilir; hikâye ana omurgasına doğrudan girmek zorunda değil.', 'sort_order' => 20],
        ['block_type' => 'next', 'title' => 'Builder kontrolü', 'body' => 'Hikâye taslağı, “telefon trafiğinden üç sütunlu panoya” omurgasıyla kurulacak.', 'sort_order' => 30],
    ]);

    showcase_cleanup_old_meta_updates($projectId);

    $storyId = showcase_story($projectId);
    showcase_section($storyId, [
        'type' => 'opening',
        'layout' => 'hero-split',
        'section_kind' => 'Başlangıç',
        'label' => 'BAŞLANGIÇ',
        'title' => 'İş defterde vardı; üretim masasının üstünde yoktu.',
        'body_text' => 'Bu küçük üretim takip denemesi, büyük bir yazılım fikri olarak başlamadı. Bir işyerinde aynı siparişin durumunun gün içinde birkaç kez sorulmasıyla başladı.',
        'quote_text' => 'Asıl problem veri tutmak değil, işin o anda nerede olduğunu görebilmekti.',
        'media_id' => $coverId,
        'source_update_id' => $updates['saha'],
        'sort_order' => 10,
    ]);
    $timeline = showcase_section($storyId, [
        'type' => 'timeline',
        'label' => 'ATÖLYEDEN GELENLER',
        'title' => 'Hikâyeye dönüşmeden önce masada böyle ilerledi.',
        'intro_text' => 'Bu bölüm doğrudan Atölye kayıtlarından kuruldu; her satırın kaynağı bir çalışma kaydıdır.',
        'media_id' => $mapId,
        'sort_order' => 20,
    ]);
    $i = 1;
    foreach ($updates as $updateId) {
        $row = db()->query('SELECT * FROM updates WHERE id=' . (int)$updateId)->fetch(PDO::FETCH_ASSOC);
        $cfg = atelier_entry_kind_config($row);
        showcase_item($timeline, [
            'item_type' => (string)$cfg['item_type'],
            'step' => str_pad((string)$i, 2, '0', STR_PAD_LEFT),
            'title' => (string)$row['title'],
            'subtitle' => atelier_story_label($row),
            'text' => (string)$row['summary'],
            'source_update_id' => $updateId,
            'sort_order' => $i,
        ]);
        $i++;
    }
    $compare = showcase_section($storyId, [
        'type' => 'compare',
        'label' => 'YANLIŞ / DOĞRU',
        'title' => 'Önce sistemi büyütmek istedim; sonra problemi küçülttüm.',
        'intro_text' => 'Atölye kayıtları, hangi fikrin erken ve hangi kararın doğru olduğunu ayırdı.',
        'sort_order' => 30,
    ]);
    showcase_item($compare, ['group_key' => 'left', 'item_type' => 'heading', 'title' => 'Erken büyüyen fikir', 'sort_order' => 1]);
    showcase_item($compare, ['group_key' => 'left', 'item_type' => 'bullet', 'text' => 'Stok, barkod, rapor ve kullanıcı rolleri aynı anda konuşuldu.', 'sort_order' => 2]);
    showcase_item($compare, ['group_key' => 'left', 'item_type' => 'bullet', 'text' => 'Bu yol küçük atölyenin ilk sorununu çözmeden projeyi büyütüyordu.', 'sort_order' => 3]);
    showcase_item($compare, ['group_key' => 'right', 'item_type' => 'heading', 'title' => 'Doğru ilk sürüm', 'sort_order' => 4]);
    showcase_item($compare, ['group_key' => 'right', 'item_type' => 'bullet', 'text' => 'Bekleyen, üretimde ve teslim edildi sütunları yeterli başlangıç oldu.', 'sort_order' => 5]);
    showcase_item($compare, ['group_key' => 'right', 'item_type' => 'bullet', 'text' => 'Her sipariş kartı gün içinde tek hareketle yer değiştirecek.', 'sort_order' => 6]);

    $questions = showcase_section($storyId, [
        'type' => 'questions',
        'label' => 'KARAR SORULARI',
        'title' => 'Bu Atölye kaydı hikâyede neye dönüşür?',
        'sort_order' => 40,
    ]);
    showcase_item($questions, ['title' => 'Sorun kaydı neyi taşıdı?', 'text' => 'Telefon trafiği, hikâyede asıl çatışmayı kurdu: bilgi var ama doğru yerde değil.', 'source_update_id' => $updates['telefon'], 'sort_order' => 1]);
    showcase_item($questions, ['title' => 'Karar kaydı neyi taşıdı?', 'text' => 'Üç sütun kararı, hikâyede dönüm noktası oldu; proje bundan sonra rapor değil akış ekranı olarak ilerledi.', 'source_update_id' => $updates['karar'], 'sort_order' => 2]);
    showcase_item($questions, ['title' => 'Medya kaydı neyi taşıdı?', 'text' => 'Pano taslağı, okuyucuya kararın sadece fikir değil görünür bir çözüm olduğunu gösterdi.', 'source_update_id' => $updates['pano'], 'sort_order' => 3]);

    $status = showcase_section($storyId, [
        'type' => 'status',
        'label' => 'BUGÜN NEREDE?',
        'title' => 'İlk prototipin sınırı bilerek dar tutuldu.',
        'source_update_id' => $updates['durum'],
        'sort_order' => 50,
    ]);
    showcase_item($status, ['state' => 'Hazır', 'title' => 'Üç sütunlu pano', 'text' => 'İşin günlük akışını anlatmaya yeterli.', 'sort_order' => 1]);
    showcase_item($status, ['state' => 'Beklemede', 'title' => 'Barkod ve stok', 'text' => 'İlk problem çözülmeden eklenmeyecek.', 'sort_order' => 2]);
    showcase_item($status, ['state' => 'Sıradaki', 'title' => 'Kart hareketi', 'text' => 'Sipariş kartını tek hareketle durum değiştirecek hale getirmek.', 'sort_order' => 3]);

    $lesson = showcase_section($storyId, [
        'type' => 'lesson',
        'label' => 'DERS',
        'title' => 'Bu sanal proje Atölye için ne öğretiyor?',
        'source_update_id' => $updates['cekirdek'],
        'sort_order' => 60,
    ]);
    showcase_item($lesson, ['text' => 'Atölye kaydı, hikâyeden önceki ham düşünceyi saklar; ama doğru rol verilirse hikâyeye temiz taşınır.', 'sort_order' => 1]);
    showcase_item($lesson, ['text' => 'Her kayıt özellik listesi olmak zorunda değildir; bazen yalnızca sahne, bazen sorun, bazen karar olur.', 'sort_order' => 2]);
    showcase_item($lesson, ['text' => 'İyi hikâye, Atölye kayıtlarını süslemez; içlerinden anlamlı olanları seçer.', 'sort_order' => 3]);

    showcase_section($storyId, [
        'type' => 'text',
        'layout' => 'wide',
        'label' => 'KAPANIŞ',
        'title' => 'Hikâyeye geçerse ana omurga bu olacak.',
        'body_text' => "Bir küçük imalathanede işlerin takılma nedeni büyük teknoloji eksikliği değildi.\n\nDefter vardı, Excel vardı, telefon vardı; ama üretim anında herkes aynı gerçeğe bakmıyordu.\n\nBu yüzden ilk karar küçük kaldı: rapor değil, günlük akışı gösteren üç sütunlu pano.",
        'quote_text' => 'Bu proje büyürse, büyüme sebebi özellik isteği değil; sahada gerçekten tekrar eden sorunlar olacak.',
        'source_update_id' => $updates['cekirdek'],
        'sort_order' => 70,
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
