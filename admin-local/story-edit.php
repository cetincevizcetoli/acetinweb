<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admin_require_login();

$projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
$st = db()->prepare('SELECT * FROM projects WHERE id=?');
$st->execute([$projectId]);
$project = $st->fetch();
if (!$project) {
    http_response_code(404);
    exit('Proje yok.');
}

$story = story_by_project($projectId, true);
if (!$story) {
    flash('error', 'Once hikaye taslagi olusturmalisin.');
    redirect('story-builder.php?project_id=' . $projectId);
}

function admin_part_anchor(array $part): string
{
    $anchor = trim((string)($part['anchor'] ?? ''));
    if ($anchor !== '') {
        return safe_slug($anchor);
    }
    return safe_slug((string)($part['title'] ?? 'bolum'));
}

$error = '';

if (is_post()) {
    verify_csrf();
    $action = (string)($_POST['action'] ?? 'save');

    try {
        if (str_starts_with($action, 'move_')) {
            [, $dir, $sid] = explode('_', $action);
            $sid = (int)$sid;
            $sections = story_sections((int)$story['id']);
            $ids = array_column($sections, 'id');
            $pos = array_search($sid, $ids, true);
            if ($pos !== false) {
                $target = $dir === 'up' ? $pos - 1 : $pos + 1;
                if (isset($ids[$target])) {
                    $a = $sections[$pos];
                    $b = $sections[$target];
                    db()->beginTransaction();
                    db()->prepare('UPDATE story_sections SET sort_order=? WHERE id=?')->execute([$b['sort_order'], $a['id']]);
                    db()->prepare('UPDATE story_sections SET sort_order=? WHERE id=?')->execute([$a['sort_order'], $b['id']]);
                    db()->commit();
                }
            }
            redirect('story-edit.php?project_id=' . $projectId);
        }

        if (str_starts_with($action, 'delete_')) {
            $sid = (int)substr($action, 7);
            db()->prepare('UPDATE story_sections SET deleted_at=CURRENT_TIMESTAMP WHERE id=? AND story_id=?')->execute([$sid, $story['id']]);
            flash('success', 'Bolum silindi.');
            redirect('story-edit.php?project_id=' . $projectId);
        }

        if ($action === 'save_parts') {
            db()->beginTransaction();
            $parts = $_POST['parts'] ?? [];
            foreach ($parts as $rawPart) {
                $part = is_array($rawPart) ? $rawPart : [];
                $partId = (int)($part['id'] ?? 0);
                $delete = isset($part['delete']);
                $title = trim((string)($part['title'] ?? ''));
                $subtitle = trim((string)($part['subtitle'] ?? ''));
                $description = trim((string)($part['description'] ?? ''));
                $sortOrder = (float)($part['sort_order'] ?? 999);
                $anchor = admin_part_anchor([
                    'anchor' => (string)($part['anchor'] ?? ''),
                    'title' => $title,
                ]);

                if ($partId > 0 && $delete) {
                    db()->prepare('DELETE FROM story_parts WHERE id=? AND story_id=?')->execute([$partId, $story['id']]);
                    continue;
                }
                if ($title === '') {
                    continue;
                }
                if ($partId > 0) {
                    $st = db()->prepare('UPDATE story_parts SET title=?,subtitle=?,description=?,anchor=?,sort_order=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND story_id=?');
                    $st->execute([$title, $subtitle, $description, $anchor, $sortOrder, $partId, $story['id']]);
                } else {
                    $st = db()->prepare('INSERT INTO story_parts(story_id,title,subtitle,description,anchor,sort_order) VALUES(?,?,?,?,?,?)');
                    $st->execute([$story['id'], $title, $subtitle, $description, $anchor, $sortOrder]);
                }
            }
            db()->commit();
            admin_audit('update', 'story_parts', (int)$story['id']);
            flash('success', 'Surec parcalari kaydedildi.');
            redirect('story-edit.php?project_id=' . $projectId);
        }

        $status = (string)($_POST['status'] ?? 'draft');
        $st = db()->prepare("UPDATE stories SET title=?,question=?,summary=?,reading_time=?,status=?,visibility=?,show_on_home=?,show_in_archive=?,is_pinned=?,sort_order=?,published_at=CASE WHEN ?='published' AND published_at IS NULL THEN CURRENT_TIMESTAMP ELSE published_at END,updated_at=CURRENT_TIMESTAMP WHERE id=?");
        $st->execute([
            trim((string)($_POST['title'] ?? '')),
            trim((string)($_POST['question'] ?? '')),
            trim((string)($_POST['summary'] ?? '')),
            trim((string)($_POST['reading_time'] ?? '')),
            $status,
            (string)($_POST['visibility'] ?? 'public'),
            checkbox('show_on_home'),
            checkbox('show_in_archive'),
            checkbox('is_pinned'),
            (float)($_POST['sort_order'] ?? 999),
            $status,
            $story['id'],
        ]);
        admin_audit('update', 'story', (int)$story['id']);
        flash('success', 'Hikaye ayarlari kaydedildi.');
        redirect('story-edit.php?project_id=' . $projectId);
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        $error = admin_error_message($e, 'admin.story_edit');
    }
}

$story = story_by_project($projectId, true);
$sections = story_sections((int)$story['id']);
$parts = story_parts((int)$story['id']);
$sectionCount = count($sections);
$needsParts = $sectionCount >= 13 && !$parts;
$strongNeedsParts = $sectionCount >= 30 && !$parts;

admin_head('Hikayeyi duzenle');
?>
<div class="page-head">
    <div>
        <p class="eyebrow"><?= e($project['title']) ?></p>
        <h1>Hikayeyi duzenle</h1>
        <p>Bolumleri ekle, sirala, duzenle ve uzun hikayelerde surec parcalarini tanimla.</p>
    </div>
    <a class="button secondary" href="../hikaye.php?slug=<?= e(rawurlencode($project['slug'])) ?>" target="_blank">On izle</a>
</div>

<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
<?php admin_render_visibility_summary($project, $story); ?>

<form class="panel" method="post">
    <input type="hidden" name="project_id" value="<?= $projectId ?>">
    <?= csrf_field() ?>
    <div class="form-grid">
        <div class="field">
            <label>Hikaye basligi</label>
            <input name="title" value="<?= e($story['title']) ?>">
            <small>Buyuk gorunen anlati basligidir; proje adi kartlarda ayrica gosterilir.</small>
        </div>
        <div class="field">
            <label>Okuma suresi</label>
            <input name="reading_time" value="<?= e($story['reading_time']) ?>" placeholder="3-6 dk">
        </div>
        <div class="field full">
            <label>Merak sorusu</label>
            <input name="question" value="<?= e($story['question']) ?>">
            <small>Doluysa kartlarda ana buyuk baslik olarak bu soru kullanilir.</small>
        </div>
        <div class="field full">
            <label>Ozet</label>
            <textarea name="summary"><?= e($story['summary']) ?></textarea>
        </div>
        <div class="field">
            <label>Durum</label>
            <select name="status">
                <option value="draft" <?= $story['status'] === 'draft' ? 'selected' : '' ?>>Taslak</option>
                <option value="published" <?= $story['status'] === 'published' ? 'selected' : '' ?>>Yayimlandi</option>
                <option value="archived" <?= $story['status'] === 'archived' ? 'selected' : '' ?>>Arsivde</option>
            </select>
        </div>
        <div class="field">
            <label>Gorunurluk</label>
            <select name="visibility">
                <option value="private" <?= $story['visibility'] === 'private' ? 'selected' : '' ?>>Gizli</option>
                <option value="unlisted" <?= $story['visibility'] === 'unlisted' ? 'selected' : '' ?>>Baglantiya sahip olanlar</option>
                <option value="public" <?= $story['visibility'] === 'public' ? 'selected' : '' ?>>Herkese acik</option>
            </select>
        </div>
        <div class="field">
            <label>Sira (eski story alani)</label>
            <input type="number" step="0.1" name="sort_order" value="<?= e((string)$story['sort_order']) ?>">
        </div>
        <div class="field full check-row">
            <label class="check"><input type="checkbox" name="show_on_home" <?= $story['show_on_home'] ? 'checked' : '' ?>> Story ana sayfa bayragi</label>
            <label class="check"><input type="checkbox" name="show_in_archive" <?= $story['show_in_archive'] ? 'checked' : '' ?>> Story arsiv bayragi</label>
            <label class="check"><input type="checkbox" name="is_pinned" <?= $story['is_pinned'] ? 'checked' : '' ?>> Sabitle</label>
        </div>
        <p class="help">Public ana sayfa ve Hikayeler sayfasindaki yerlesimi Projeyi yonet ekranindaki alanlar belirler. Bu story bayraklari eski uyumluluk icin tutulur.</p>
    </div>
    <div class="form-actions"><button class="accent" type="submit" name="action" value="save">Ayarlari kaydet</button></div>
</form>

<form class="panel" method="post">
    <input type="hidden" name="project_id" value="<?= $projectId ?>">
    <?= csrf_field() ?>
    <div class="page-head" style="margin-top:0">
        <div>
            <p class="eyebrow">OKUMA YAPISI</p>
            <h2>Surec parcalari</h2>
            <p>Uzun hikayelerde okura ustte bir surec haritasi gosterilir. Kisa hikayelerde bu alan bos kalabilir.</p>
        </div>
    </div>
    <?php if ($strongNeedsParts): ?>
        <div class="flash flash-error">Bu hikayede <?= $sectionCount ?> bolum var. Okunurluk icin surec parcalari eklemen onerilir.</div>
    <?php elseif ($needsParts): ?>
        <div class="flash">Bu hikayede <?= $sectionCount ?> bolum var. Surec parcalari eklersen okur hikayenin yolunu daha rahat takip eder.</div>
    <?php endif; ?>
    <div id="story-parts" class="repeat-list">
        <?php foreach ($parts as $i => $part): ?>
            <div class="item-editor">
                <input type="hidden" name="parts[<?= $i ?>][id]" value="<?= (int)$part['id'] ?>">
                <div class="field span-4"><label>Baslik</label><input name="parts[<?= $i ?>][title]" value="<?= e($part['title']) ?>"></div>
                <div class="field span-4"><label>Alt baslik</label><input name="parts[<?= $i ?>][subtitle]" value="<?= e($part['subtitle']) ?>"></div>
                <div class="field span-2"><label>Anchor</label><input name="parts[<?= $i ?>][anchor]" value="<?= e($part['anchor']) ?>"></div>
                <div class="field span-2"><label>Sira</label><input type="number" step="0.1" name="parts[<?= $i ?>][sort_order]" value="<?= e((string)$part['sort_order']) ?>"></div>
                <div class="field span-10"><label>Kisa aciklama</label><input name="parts[<?= $i ?>][description]" value="<?= e($part['description']) ?>"></div>
                <label class="check span-2"><input type="checkbox" name="parts[<?= $i ?>][delete]"> Sil</label>
            </div>
        <?php endforeach; ?>
    </div>
    <button class="secondary" type="button" data-repeat-add="#story-parts" data-template="#part-template">+ Surec parcasi ekle</button>
    <div class="form-actions"><button class="accent" type="submit" name="action" value="save_parts">Surec parcalarini kaydet</button></div>
</form>

<div class="page-head" style="margin-top:45px">
    <div>
        <p class="eyebrow">HIKAYE BOLUMLERI</p>
        <h2>Mevcut kayitlar</h2>
    </div>
    <a class="button accent" href="section-edit.php?story_id=<?= (int)$story['id'] ?>">+ Yeni bolum</a>
</div>

<?php if (!$sections): ?>
    <div class="empty">Henuz hikaye bolumu yok.</div>
<?php else: ?>
    <form method="post">
        <input type="hidden" name="project_id" value="<?= $projectId ?>">
        <?= csrf_field() ?>
        <div class="list">
            <?php foreach ($sections as $i => $s): ?>
                <article class="section-card">
                    <span class="num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <div>
                        <small><?= e(strtoupper((string)$s['type'])) ?> · <?= e((string)$s['layout']) ?><?php if (!empty($s['part_title'])): ?> · <?= e((string)$s['part_title']) ?><?php endif; ?><?php if (!empty($s['section_kind'])): ?> · <?= e((string)$s['section_kind']) ?><?php endif; ?></small>
                        <h3><?= e($s['title'] ?: 'Basliksiz bolum') ?></h3>
                        <p><?= e(mb_strimwidth(strip_tags((string)($s['body_text'] ?: $s['intro_text'])), 0, 150, '...', 'UTF-8')) ?></p>
                    </div>
                    <div class="card-actions">
                        <a class="button secondary" href="section-edit.php?id=<?= (int)$s['id'] ?>">Duzenle</a>
                        <button class="secondary" name="action" value="move_up_<?= (int)$s['id'] ?>">↑</button>
                        <button class="secondary" name="action" value="move_down_<?= (int)$s['id'] ?>">↓</button>
                        <button class="danger" name="action" value="delete_<?= (int)$s['id'] ?>" data-confirm="Bolum silinsin mi?">Sil</button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </form>
<?php endif; ?>

<template id="part-template">
    <div class="item-editor">
        <input type="hidden" name="parts[__INDEX__][id]" value="">
        <div class="field span-4"><label>Baslik</label><input name="parts[__INDEX__][title]"></div>
        <div class="field span-4"><label>Alt baslik</label><input name="parts[__INDEX__][subtitle]"></div>
        <div class="field span-2"><label>Anchor</label><input name="parts[__INDEX__][anchor]"></div>
        <div class="field span-2"><label>Sira</label><input type="number" step="0.1" name="parts[__INDEX__][sort_order]" value="999"></div>
        <div class="field span-10"><label>Kisa aciklama</label><input name="parts[__INDEX__][description]"></div>
        <label class="check span-2"><input type="checkbox" name="parts[__INDEX__][delete]"> Sil</label>
    </div>
</template>

<?php admin_foot(); ?>
