<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
admin_require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$storyId = (int)($_GET['story_id'] ?? $_POST['story_id'] ?? 0);
$section = null;

if ($id) {
    $st = db()->prepare('SELECT * FROM story_sections WHERE id=? AND deleted_at IS NULL');
    $st->execute([$id]);
    $section = $st->fetch();
    if (!$section) { http_response_code(404); exit('Bölüm yok.'); }
    $storyId = (int)$section['story_id'];
}

$st = db()->prepare('SELECT s.*,p.id project_id,p.title project_title,p.slug project_slug FROM stories s JOIN projects p ON p.id=s.project_id WHERE s.id=?');
$st->execute([$storyId]);
$story = $st->fetch();
if (!$story) { http_response_code(404); exit('Hikâye yok.'); }

$projectId = (int)$story['project_id'];
$projectMedia = project_media_admin($projectId);
$parts = story_parts($storyId);
$items = [];
$links = [];
$selectedGallery = [];

if ($section) {
    $st = db()->prepare('SELECT * FROM story_section_items WHERE section_id=? ORDER BY sort_order,id');
    $st->execute([$id]);
    $items = $st->fetchAll();
    $st = db()->prepare('SELECT media_id FROM story_section_media WHERE section_id=? ORDER BY sort_order');
    $st->execute([$id]);
    $selectedGallery = array_map('intval', array_column($st->fetchAll(), 'media_id'));
    $links = owner_links('story_section', $id);
}

$error = '';

function admin_section_item_has_content(array $item): bool
{
    foreach (['title', 'text', 'state', 'subtitle', 'step', 'value', 'url'] as $key) {
        if (trim((string)($item[$key] ?? '')) !== '') return true;
    }
    return false;
}

function admin_section_has_post_items(array $items): bool
{
    foreach ($items as $item) {
        if (is_array($item) && admin_section_item_has_content($item)) return true;
    }
    return false;
}

function admin_section_normalize_choice(string $type, string $layout, bool $hasItems, bool $hasMedia, bool $hasCode, bool $hasText, string $title): array
{
    $messages = [];
    $originalType = $type;
    $originalLayout = $layout;
    $itemTypes = ['timeline', 'questions', 'compare', 'roles', 'status', 'lesson'];

    if ($type === '') $type = 'text';
    if ($layout === '') $layout = 'default';

    if (in_array($type, $itemTypes, true) && !$hasItems) {
        if ($hasCode) {
            $type = 'code';
        } elseif ($hasMedia && $hasText) {
            $type = 'split';
        } elseif ($hasMedia) {
            $type = 'gallery';
        } else {
            $type = 'text';
        }
        $messages[] = 'Seçilen bölüm tipi satır gerektiriyordu; satır olmadığı için güvenli görünüme alındı.';
    }

    if (in_array($type, ['gallery', 'video'], true) && !$hasMedia) {
        $type = $hasCode ? 'code' : 'text';
        $messages[] = 'Medya odaklı bölümde medya olmadığı için bölüm güvenli görünüme alındı.';
    }

    if ($type === 'code' && !$hasCode) {
        $type = $hasMedia && $hasText ? 'split' : ($hasMedia ? 'gallery' : 'text');
        $messages[] = 'Kod bölümü için kod alanı boştu; bölüm güvenli görünüme alındı.';
    }

    if ($type === 'split' && !$hasMedia) {
        $type = 'text';
        $messages[] = 'Metin + medya yerleşiminde medya olmadığı için metin görünümüne alındı.';
    }

    $titleLength = function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title);
    if (!$hasMedia && in_array($layout, ['hero-split', 'full-bleed', 'diagonal'], true)) {
        $layout = 'default';
        $messages[] = 'Seçilen yerleşim medya ister; medya olmadığı için dengeli akışa alındı.';
    } elseif ($titleLength > 76 && in_array($layout, ['hero-split', 'diagonal'], true)) {
        $layout = 'default';
        $messages[] = 'Başlık uzun olduğu için taşma riskli yerleşim yerine dengeli akış kullanıldı.';
    } elseif (in_array($type, ['timeline', 'questions', 'compare', 'roles', 'status', 'lesson', 'code'], true)) {
        $layout = 'default';
    }

    if ($originalType !== $type || $originalLayout !== $layout) {
        $messages[] = 'Public sayfada bozuk blok oluşmaması için bölüm tipi/yerleşimi kayıtta normalize edildi.';
    }

    return [$type, $layout, array_values(array_unique($messages))];
}

if (is_post()) {
    verify_csrf();
    try {
        db()->beginTransaction();
        $savedLinkCount = 0;
        $normalizationMessages = [];
        $type = (string)($_POST['type'] ?? 'text');
        $layout = (string)($_POST['layout'] ?? 'default');
        $postedItems = is_array($_POST['items'] ?? null) ? $_POST['items'] : [];
        $partId = ((int)($_POST['part_id'] ?? 0)) ?: null;
        if ($partId !== null) {
            $st = db()->prepare('SELECT COUNT(*) FROM story_parts WHERE id=? AND story_id=?');
            $st->execute([$partId, $storyId]);
            if ((int)$st->fetchColumn() !== 1) {
                throw new RuntimeException('Secilen surec parcasi bu hikayeye ait degil.');
            }
        }
        $selectedPrimary = (int)($_POST['primary_media_id'] ?? 0);
        assert_project_media_ids($projectId, $selectedPrimary ? [$selectedPrimary] : [], 'Birincil medya');
        $selectedGalleryIds = assert_project_media_ids($projectId, $_POST['gallery_media_ids'] ?? [], 'Galeri medyası');
        $mediaId = $selectedPrimary ?: null;
        $uploaded = save_uploaded_files($projectId, (string)$story['project_slug'], 'media_files');
        if ($uploaded && !$mediaId) $mediaId = $uploaded[0];
        $hasText = trim(old('body_text')) !== ''
            || trim(old('intro_text')) !== ''
            || trim(old('quote_text')) !== ''
            || trim(old('note_text')) !== '';
        $hasCode = trim(old('code_text')) !== '';
        $hasItems = admin_section_has_post_items($postedItems);
        $hasMedia = (bool)$mediaId || $selectedGalleryIds !== [] || $uploaded !== [];
        [$type, $layout, $normalizationMessages] = admin_section_normalize_choice(
            $type,
            $layout,
            $hasItems,
            $hasMedia,
            $hasCode,
            $hasText,
            trim(old('title'))
        );

        if ($id) {
            $st = db()->prepare('UPDATE story_sections SET part_id=?,section_kind=?,type=?,layout=?,label=?,title=?,body_text=?,quote_text=?,intro_text=?,note_text=?,code_text=?,media_id=?,sort_order=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
            $st->execute([
                $partId, trim((string)($_POST['section_kind'] ?? '')),
                $type, $layout, trim(old('label')), trim(old('title')), trim(old('body_text')),
                trim(old('quote_text')), trim(old('intro_text')), trim(old('note_text')),
                old('code_text'), $mediaId, (int)($_POST['sort_order'] ?? 999), $id
            ]);
            $sectionId = $id;
        } else {
            $st = db()->prepare('INSERT INTO story_sections(story_id,part_id,section_kind,type,layout,label,title,body_text,quote_text,intro_text,note_text,code_text,media_id,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute([
                $storyId, $partId, trim((string)($_POST['section_kind'] ?? '')),
                $type, $layout, trim(old('label')), trim(old('title')), trim(old('body_text')),
                trim(old('quote_text')), trim(old('intro_text')), trim(old('note_text')),
                old('code_text'), $mediaId, (int)($_POST['sort_order'] ?? 999)
            ]);
            $sectionId = (int)db()->lastInsertId();
        }

        db()->prepare('DELETE FROM story_section_items WHERE section_id=?')->execute([$sectionId]);
        foreach ($postedItems as $i => $it) {
            $hasContent = trim((string)($it['title'] ?? '')) !== ''
                || trim((string)($it['text'] ?? '')) !== ''
                || trim((string)($it['state'] ?? '')) !== ''
                || trim((string)($it['subtitle'] ?? '')) !== '';
            if (!$hasContent) continue;
            $st = db()->prepare('INSERT INTO story_section_items(section_id,group_key,item_type,step,title,subtitle,text,state,value,url,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute([
                $sectionId,
                trim((string)($it['group'] ?? '')),
                trim((string)($it['item_type'] ?? 'item')),
                trim((string)($it['step'] ?? '')),
                trim((string)($it['title'] ?? '')),
                trim((string)($it['subtitle'] ?? '')),
                trim((string)($it['text'] ?? '')),
                trim((string)($it['state'] ?? '')),
                trim((string)($it['value'] ?? '')),
                validated_external_url((string)($it['url'] ?? ''), 'Tekrarlanan satır URL’si'),
                (int)$i,
            ]);
        }

        db()->prepare('DELETE FROM story_section_media WHERE section_id=?')->execute([$sectionId]);
        $gallery = array_values(array_unique(array_merge($uploaded, $selectedGalleryIds)));
        foreach ($gallery as $i => $mid) {
            if (!$mid) continue;
            db()->prepare("INSERT INTO story_section_media(section_id,media_id,role,sort_order) VALUES (?,?,'gallery',?)")->execute([$sectionId, $mid, $i]);
            if ($type === 'gallery') {
                db()->prepare("INSERT INTO story_section_items(section_id,item_type,media_id,sort_order) VALUES (?,'gallery',?,?)")->execute([$sectionId, $mid, $i]);
            }
        }

        if (class_exists(LinkRepository::class)) {
            $savedLinkCount = LinkRepository::replaceForOwner('story_section', $sectionId, is_array($_POST['links'] ?? null) ? $_POST['links'] : [], fn($title,$type,$url) => admin_link_title((string)$title, (string)$type, (string)$url));
        } else {
        db()->prepare("DELETE FROM links WHERE owner_type='story_section' AND owner_id=?")->execute([$sectionId]);
        foreach ($_POST['links'] ?? [] as $i => $l) {
            $rawUrl = trim((string)($l['url'] ?? ''));
            if ($rawUrl === '') continue;
            $url = validated_external_url($rawUrl, 'Bağlantı ' . ($i + 1));
            db()->prepare("INSERT INTO links(owner_type,owner_id,link_type,title,url,sort_order) VALUES ('story_section',?,?,?,?,?)")
                ->execute([
                    $sectionId,
                    trim((string)($l['type'] ?? 'external')),
                    admin_link_title((string)($l['title'] ?? ''), (string)($l['type'] ?? 'external'), $url),
                    $url,
                    (int)$i,
                ]);
            $savedLinkCount++;
        }
        }

        db()->commit();
        admin_audit($id ? 'update' : 'create', 'story_section', $sectionId);
        $mediaCount = count($gallery) + ($mediaId ? 1 : 0);
        flash('success', 'Hikâye bölümü kaydedildi · ' . $mediaCount . ' medya · ' . $savedLinkCount . ' bağlantı.');
        foreach ($normalizationMessages as $message) {
            flash('warning', $message);
        }
        redirect('section-edit.php?id=' . $sectionId);
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        $error = admin_error_message($e, 'admin.section_edit');
    }
}

if ($id) {
    $st = db()->prepare('SELECT * FROM story_sections WHERE id=?');
    $st->execute([$id]);
    $section = $st->fetch();
    $st = db()->prepare('SELECT * FROM story_section_items WHERE section_id=? ORDER BY sort_order,id');
    $st->execute([$id]);
    $items = $st->fetchAll();
    $st = db()->prepare('SELECT media_id FROM story_section_media WHERE section_id=? ORDER BY sort_order');
    $st->execute([$id]);
    $selectedGallery = array_map('intval', array_column($st->fetchAll(), 'media_id'));
    $links = owner_links('story_section', $id);
}

$types = [
    'opening' => ['label' => 'Açılış / büyük giriş', 'note' => 'Hikâyenin ilk kuvvetli girişi. Bir hikâyede az kullan.'],
    'text' => ['label' => 'Metin bölümü', 'note' => 'Ana anlatım için en güvenli seçim. Başlık, metin, alıntı ve not dengeli çalışır.'],
    'split' => ['label' => 'Metin + birincil medya', 'note' => 'Metin ile tek ana görsel/video yan yana durur. Medya seçiliyse kullan.'],
    'timeline' => ['label' => 'Zaman çizgisi', 'note' => 'Adım adım ilerleyen süreç veya tarih akışı için. Satırlar bölümün ana içeriğidir.'],
    'questions' => ['label' => 'Soru / cevap', 'note' => 'Açılır teknik notlar veya SSS için. Satır başlıkları soru gibi yazılmalı.'],
    'compare' => ['label' => 'Karşılaştırma', 'note' => 'Sol/sağ gruplu maddeler için. Satırlarda grup alanını kullan.'],
    'roles' => ['label' => 'YZ / insan ayrımı', 'note' => 'YZ ve Ahmet taraflarını ayırmak için. Satır grupları ai/human olmalı.'],
    'status' => ['label' => 'Güncel durum', 'note' => 'Birden fazla durum kartı için. Satırlarda durum ve metin kullan.'],
    'lesson' => ['label' => 'Ders / çıkarım listesi', 'note' => 'Kısa öğrenilenler listesi için. Uzun paragraf yerine maddeler daha iyi durur.'],
    'gallery' => ['label' => 'Galeri', 'note' => 'Birden fazla görsel/video aynı bölümün ana içeriği olacaksa.'],
    'video' => ['label' => 'Video / medya odaklı', 'note' => 'Video veya medya bölümün ana sahnesiyse. Metin kısa tutulmalı.'],
    'code' => ['label' => 'Kod / terminal', 'note' => 'Kod veya terminal çıktıları için. Kod alanı ana içerik olur.']
];

$layouts = [
    'default' => ['label' => 'Dengeli akış', 'note' => 'Çoğu bölüm için güvenli varsayılan yerleşim.'],
    'wide' => ['label' => 'Geniş okuma', 'note' => 'Metin veya galeri daha geniş nefes alsın istendiğinde.'],
    'hero-split' => ['label' => 'Büyük iki kolon', 'note' => 'Kuvvetli başlık + tek medya için. Uzun başlıkta dikkatli kullan.'],
    'full-bleed' => ['label' => 'Tam geniş vurgu', 'note' => 'Özel görsel/vurgu bölümü için. Her bölümde kullanılmaz.'],
    'offset' => ['label' => 'Kaydırılmış ritim', 'note' => 'Akışı kırmak için hafif kaydırmalı görünüm.'],
    'cross' => ['label' => 'Çapraz karşılaştırma', 'note' => 'Karşılaştırma/rol ayrımı gibi iki taraflı içeriklerde daha uygun.'],
    'diagonal' => ['label' => 'Diyagonal vurgu', 'note' => 'Kısa ve vurucu bölümlerde kullan; uzun metinde yorabilir.'],
];

$currentType = (string)($section['type'] ?? 'text');
$currentLayout = (string)($section['layout'] ?? 'default');

$sectionKinds = [
    '' => 'Otomatik belirle',
    'Kivilcim' => 'Kivilcim',
    'Karar' => 'Karar',
    'Donum noktasi' => 'Donum noktasi',
    'Deney' => 'Deney',
    'Sonuc' => 'Sonuc',
    'Ders' => 'Ders',
    'Not' => 'Not',
];

admin_head($id ? 'Bölümü düzenle' : 'Yeni bölüm');
?>
<div class="page-head">
    <div>
        <p class="eyebrow"><?= e($story['project_title']) ?></p>
        <h1><?= $id ? 'Bölümü düzenle' : 'Yeni hikâye bölümü' ?></h1>
        <p>Alanları doldur; veriler ilişkili SQLite tablolarına yazılır.</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a class="button secondary" href="story-edit.php?project_id=<?= $projectId ?>">Hikâyeye dön</a>
        <a class="button secondary" href="../hikaye.php?slug=<?= e(rawurlencode((string)$story['project_slug'])) ?>" target="_blank" rel="noopener">Önizle</a>
    </div>
</div>
<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="story_id" value="<?= $storyId ?>">
    <?= csrf_field() ?>

    <div class="grid grid-2">
        <section class="panel">
            <h2>Bölüm kimliği</h2>
            <div class="form-grid">
                <div class="field"><label>İçerik tipi</label><select name="type" data-section-type><?php foreach ($types as $k => $v): ?><option value="<?= e($k) ?>" data-note="<?= e($v['note']) ?>" <?= $currentType === $k ? 'selected' : '' ?>><?= e($v['label']) ?></option><?php endforeach; ?></select><small data-section-type-note><?= e($types[$currentType]['note'] ?? '') ?></small></div>
                <div class="field"><label>Sayfadaki yerleşim</label><select name="layout" data-section-layout><?php foreach ($layouts as $k => $v): ?><option value="<?= e($k) ?>" data-note="<?= e($v['note']) ?>" <?= $currentLayout === $k ? 'selected' : '' ?>><?= e($v['label']) ?></option><?php endforeach; ?></select><small data-section-layout-note><?= e($layouts[$currentLayout]['note'] ?? '') ?></small></div>
                <div class="field"><label>Etiket</label><input name="label" value="<?= e($section['label'] ?? '') ?>" placeholder="BAŞLANGIÇ"></div>
                <div class="field"><label>Sıra</label><input type="number" name="sort_order" value="<?= e((string)($section['sort_order'] ?? 999)) ?>"></div>
                <div class="field"><label>Süreç parçası</label><select name="part_id"><option value="">Yok / otomatik akış</option><?php foreach ($parts as $part): ?><option value="<?= (int)$part['id'] ?>" <?= (int)($section['part_id'] ?? 0) === (int)$part['id'] ? 'selected' : '' ?>><?= e($part['title']) ?></option><?php endforeach; ?></select><small>Uzun hikâyelerde bölümün süreç haritasındaki yerini belirler.</small></div>
                <div class="field"><label>Okur etiketi</label><select name="section_kind"><?php foreach ($sectionKinds as $k => $v): ?><option value="<?= e($k) ?>" <?= (string)($section['section_kind'] ?? '') === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select><small>Public sayfada görünen küçük bölüm kimliği. Boş bırakılırsa sistem içerik tipinden seçer.</small></div>
                <div class="field full"><label>Başlık</label><input name="title" value="<?= e($section['title'] ?? '') ?>"></div>
                <div class="field full"><label>Ana metin</label><textarea name="body_text" rows="8"><?= e($section['body_text'] ?? '') ?></textarea><small>Paragrafları boş satırla ayır.</small></div>
                <div class="field full"><label>Alıntı</label><textarea name="quote_text"><?= e($section['quote_text'] ?? '') ?></textarea></div>
                <div class="field full"><label>Giriş / açıklama</label><textarea name="intro_text"><?= e($section['intro_text'] ?? '') ?></textarea></div>
                <div class="field full"><label>Kenar notu</label><textarea name="note_text"><?= e($section['note_text'] ?? '') ?></textarea></div>
                <div class="field full"><label>Kod / terminal</label><textarea name="code_text" rows="10" style="font-family:monospace"><?= e($section['code_text'] ?? '') ?></textarea></div>
                <div class="section-preview full" data-section-preview>
                    <div>
                        <span>Yerleşim yardımı</span>
                        <strong data-preview-title>Seçimin davranışı</strong>
                        <p data-preview-body>Bu kutu gerçek hikâye metnini tekrar etmez. Yalnızca seçtiğin içerik tipi ve yerleşimin nasıl davranacağını anlatır.</p>
                    </div>
                    <ul>
                        <li><b>İçerik tipi</b><span data-preview-type><?= e($types[$currentType]['label'] ?? $currentType) ?></span></li>
                        <li><b>Yerleşim</b><span data-preview-layout><?= e($layouts[$currentLayout]['label'] ?? $currentLayout) ?></span></li>
                        <li><b>Not</b><span data-preview-advice>Uzun metinlerde sade okuma yerleşimleri daha güvenlidir.</span></li>
                    </ul>
                    <div class="section-rules">
                        <strong>Kısa kural</strong>
                        <p>Bu alan canlı önizleme değil, seçim rehberidir. Gerçek sonucu görmek için üstteki Önizle bağlantısını kullan.</p>
                    </div>
                </div>
            </div>
        </section>

        <aside class="panel">
            <h2>Medya</h2>
            <div class="field"><label>Yeni dosyalar</label><input type="file" name="media_files[]" multiple accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,audio/mpeg,audio/wav,audio/ogg,application/pdf"></div>
            <div class="field"><label>Birincil medya</label><select name="primary_media_id"><option value="">Yok</option><?php foreach ($projectMedia as $m): ?><option value="<?= (int)$m['id'] ?>" <?= (int)($section['media_id'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['original_name']) ?> · <?= e($m['media_type']) ?></option><?php endforeach; ?></select></div>
            <h3>Galeri / ek medya</h3>
            <div class="media-grid"><?php foreach ($projectMedia as $m): ?><label class="media-card"><input type="checkbox" name="gallery_media_ids[]" value="<?= (int)$m['id'] ?>" <?= in_array((int)$m['id'], $selectedGallery, true) ? 'checked' : '' ?>><?php if ($m['media_type'] === 'image'): ?><img src="../<?= e($m['relative_path']) ?>" alt=""><?php else: ?><div style="height:120px;display:grid;place-items:center;background:#090d11"><?= e(strtoupper($m['media_type'])) ?></div><?php endif; ?><small><?= e($m['original_name']) ?></small></label><?php endforeach; ?></div>
        </aside>

        <section class="panel">
            <h2>Tekrarlanan satırlar</h2>
            <p>Zaman çizgisi, soru-cevap, karşılaştırma, durum ve öğrenilenler için kullanılır.</p>
            <div id="section-items" class="repeat-list">
                <?php foreach ($items as $i => $it): ?>
                <div class="item-editor">
                    <div class="field span-2"><label>Grup</label><select name="items[<?= $i ?>][group]"><option value="">Genel</option><option value="left" <?= $it['group_key']==='left'?'selected':'' ?>>Sol</option><option value="right" <?= $it['group_key']==='right'?'selected':'' ?>>Sağ</option><option value="ai" <?= $it['group_key']==='ai'?'selected':'' ?>>YZ</option><option value="human" <?= $it['group_key']==='human'?'selected':'' ?>>Ahmet</option></select></div>
                    <div class="field span-2"><label>Satır türü</label><select name="items[<?= $i ?>][item_type]"><?php foreach (['item','timeline','question','bullet','heading','status','lesson'] as $t): ?><option <?= $it['item_type']===$t?'selected':'' ?>><?= $t ?></option><?php endforeach; ?></select></div>
                    <div class="field span-2"><label>Adım</label><input name="items[<?= $i ?>][step]" value="<?= e($it['step']) ?>" placeholder="01"></div>
                    <div class="field span-6"><label>Başlık / soru</label><input name="items[<?= $i ?>][title]" value="<?= e($it['title']) ?>"></div>
                    <div class="field span-4"><label>Alt başlık</label><input name="items[<?= $i ?>][subtitle]" value="<?= e($it['subtitle']) ?>"></div>
                    <div class="field span-4"><label>Durum</label><input name="items[<?= $i ?>][state]" value="<?= e($it['state']) ?>"></div>
                    <div class="field span-4"><label>Değer</label><input name="items[<?= $i ?>][value]" value="<?= e($it['value']) ?>"></div>
                    <div class="field span-8"><label>Metin / cevap</label><textarea name="items[<?= $i ?>][text]"><?= e($it['text']) ?></textarea></div>
                    <div class="field span-4"><label>URL</label><input name="items[<?= $i ?>][url]" value="<?= e($it['url']) ?>"></div>
                    <div class="remove-cell full"><button class="danger" type="button" data-repeat-remove>Satırı sil</button></div>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="secondary" type="button" data-repeat-add="#section-items" data-template="#item-template">+ Satır ekle</button>
        </section>

        <aside class="panel">
            <h2>Bağlantılar</h2>
            <p class="help">Baslik bos kalirsa sistem kaynagi tanir. YouTube, Vimeo, SoundCloud ve dogrudan MP4/MP3 linkleri public tarafta player olarak gorunur.</p>
            <div id="section-links" class="repeat-list"><?php foreach ($links as $i => $l): ?><div class="repeat-row"><select name="links[<?= $i ?>][type]"><?php foreach (['youtube','vimeo','soundcloud','instagram','github','website','download','external'] as $t): ?><option <?= $l['link_type'] === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select><input name="links[<?= $i ?>][title]" value="<?= e($l['title']) ?>"><input name="links[<?= $i ?>][url]" value="<?= e($l['url']) ?>"><button class="danger" type="button" data-repeat-remove>Sil</button></div><?php endforeach; ?></div>
            <button class="secondary" type="button" data-repeat-add="#section-links" data-template="#link-template">+ Bağlantı ekle</button>
        </aside>
    </div>

    <div class="form-actions"><button class="accent" type="submit">Bölümü kaydet</button><a class="button secondary" href="story-edit.php?project_id=<?= $projectId ?>">Vazgeç</a></div>
</form>

<template id="item-template">
    <div class="item-editor">
        <div class="field span-2"><label>Grup</label><select name="items[__INDEX__][group]"><option value="">Genel</option><option value="left">Sol</option><option value="right">Sağ</option><option value="ai">YZ</option><option value="human">Ahmet</option></select></div>
        <div class="field span-2"><label>Satır türü</label><select name="items[__INDEX__][item_type]"><option>item</option><option>timeline</option><option>question</option><option>bullet</option><option>heading</option><option>status</option><option>lesson</option></select></div>
        <div class="field span-2"><label>Adım</label><input name="items[__INDEX__][step]" placeholder="01"></div>
        <div class="field span-6"><label>Başlık / soru</label><input name="items[__INDEX__][title]"></div>
        <div class="field span-4"><label>Alt başlık</label><input name="items[__INDEX__][subtitle]"></div>
        <div class="field span-4"><label>Durum</label><input name="items[__INDEX__][state]"></div>
        <div class="field span-4"><label>Değer</label><input name="items[__INDEX__][value]"></div>
        <div class="field span-8"><label>Metin / cevap</label><textarea name="items[__INDEX__][text]"></textarea></div>
        <div class="field span-4"><label>URL</label><input name="items[__INDEX__][url]"></div>
        <div class="remove-cell full"><button class="danger" type="button" data-repeat-remove>Satırı sil</button></div>
    </div>
</template>

<template id="link-template"><div class="repeat-row"><select name="links[__INDEX__][type]"><option>youtube</option><option>vimeo</option><option>soundcloud</option><option>instagram</option><option>github</option><option>website</option><option>download</option><option>external</option></select><input name="links[__INDEX__][title]" placeholder="Başlık"><input name="links[__INDEX__][url]" placeholder="https://"><button class="danger" type="button" data-repeat-remove>Sil</button></div></template>
<?php admin_foot(); ?>
