<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
admin_require_login();

$slug = safe_slug((string) ($_GET['slug'] ?? $_POST['slug'] ?? ''));
$project = load_project($slug);
if (!$project) { http_response_code(404); exit('Proje bulunamadı.'); }
$id = safe_slug((string) ($_GET['id'] ?? $_POST['id'] ?? ''));
$existing = null;
if ($id !== '') {
    foreach (load_updates($project) as $item) {
        if (($item['_id'] ?? '') === $id) { $existing = $item; break; }
    }
    if (!$existing) { http_response_code(404); exit('Atölye kaydı bulunamadı.'); }
}
$next = next_update_number($slug);
$values = [
    'date' => (string) ($existing['date'] ?? date('Y-m-d')),
    'title' => (string) ($existing['title'] ?? ''),
    'summary' => (string) ($existing['summary'] ?? ''),
    'phase' => (string) ($existing['phase'] ?? 'Başlangıç'),
    'milestone' => (bool) ($existing['milestone'] ?? false),
    'tried' => (string) ($existing['tried'] ?? ''),
    'failed' => (string) ($existing['failed'] ?? ''),
    'decision' => (string) ($existing['decision'] ?? ''),
    'next' => (string) ($existing['next'] ?? ''),
    'media_src' => (string) ($existing['media']['src'] ?? ''),
    'media_alt' => (string) ($existing['media']['alt'] ?? $project['title'] ?? ''),
    'instagram_url' => (string) ($existing['instagram_url'] ?? ''),
    'youtube_url' => (string) ($existing['youtube_url'] ?? ''),
    'github_url' => (string) ($existing['github_url'] ?? ''),
    'homepage' => (bool) ($existing['homepage'] ?? true),
];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_require_csrf();
    foreach (['date','title','summary','phase','tried','failed','decision','next','media_src','media_alt','instagram_url','youtube_url','github_url'] as $key) {
        $values[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    $values['milestone'] = isset($_POST['milestone']);
    $values['homepage'] = isset($_POST['homepage']);
    if ($values['title'] === '') {
        $error = 'Kayıt başlığı gerekli.';
    } else {
        try {
            $uploaded = admin_upload_media($slug, 'media_file');
            if ($uploaded !== null) $values['media_src'] = $uploaded;
            if ($values['media_src'] === '') $values['media_src'] = (string) ($project['cover'] ?? 'media/cover.svg');
            $extension = strtolower(pathinfo($values['media_src'], PATHINFO_EXTENSION));
            $mediaType = in_array($extension, ['mp4','webm'], true) ? 'video' : (in_array($extension, ['mp3','ogg','wav'], true) ? 'audio' : 'image');
            $order = (float) ($existing['order'] ?? $next);
            $update = [
                'order' => $order,
                'slug' => safe_slug((string) ($existing['slug'] ?? ('kayit-' . str_pad((string) ((int) $order), 2, '0', STR_PAD_LEFT) . '-' . $values['title']))),
                'date' => $values['date'] !== '' ? $values['date'] : date('Y-m-d'),
                'date_label' => format_tr_date($values['date'] !== '' ? $values['date'] : date('Y-m-d')),
                'day' => 'Kayıt ' . str_pad((string) ((int) $order), 2, '0', STR_PAD_LEFT),
                'phase' => $values['phase'] !== '' ? $values['phase'] : 'Günlük kayıtlar',
                'milestone' => (bool) $values['milestone'],
                'homepage' => (bool) $values['homepage'],
                'title' => $values['title'],
                'summary' => $values['summary'],
                'tried' => $values['tried'],
                'failed' => $values['failed'],
                'decision' => $values['decision'],
                'next' => $values['next'],
                'media' => ['type' => $mediaType, 'src' => $values['media_src'], 'alt' => $values['media_alt']],
                'instagram_url' => $values['instagram_url'],
                'youtube_url' => $values['youtube_url'],
                'github_url' => $values['github_url'],
            ];
            save_update_record($slug, $update, $existing['_id'] ?? null);
            $record = $project['_project'];
            $record['updated_at'] = $update['date'];
            if (workshop_status($project) === 'none') {
                $record['workshop']['status'] = 'open';
                $record['workshop']['started_at'] = $update['date'];
            }
            save_project_record($record);
            admin_flash('success', $existing ? 'Atölye kaydı güncellendi.' : 'Yeni atölye kaydı eklendi.');
            admin_redirect('project-edit.php?slug=' . rawurlencode($slug));
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}
admin_layout_start(($existing ? 'Kaydı düzenle' : 'Yeni atölye kaydı'), 'projects');
?>
<section class="admin-hero"><div><p class="admin-eyebrow"><?= $existing ? 'KAYDI DÜZENLE' : 'ATÖLYEYE EKLE' ?></p><h1><?= e($project['title'] ?? '') ?></h1><p class="admin-muted">Her gün yazmak zorunda değilsin. Gerçek bir deneme, karar veya yön değişikliği olduğunda kayıt ekle.</p></div></section>
<?php if ($error !== ''): ?><div class="admin-flash admin-flash--error"><?= e($error) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="admin-form admin-card">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="slug" value="<?= e($slug) ?>"><input type="hidden" name="id" value="<?= e($id) ?>">
    <div class="admin-field admin-field--third"><label for="date">Tarih</label><input id="date" name="date" type="date" value="<?= e($values['date']) ?>"></div>
    <div class="admin-field admin-field--third"><label for="phase">Dönem / aşama</label><input id="phase" name="phase" value="<?= e($values['phase']) ?>" placeholder="Örn. İlk denemeler"></div>
    <div class="admin-field admin-field--third"><label class="admin-check"><input type="checkbox" name="milestone" <?= $values['milestone']?'checked':'' ?>> Bu kayıt dönüm noktası</label><label class="admin-check"><input type="checkbox" name="homepage" <?= $values['homepage']?'checked':'' ?>> Son hareketlerde görünebilir</label></div>
    <div class="admin-field admin-field--full"><label for="title">Kayıt başlığı</label><input id="title" name="title" value="<?= e($values['title']) ?>" required></div>
    <div class="admin-field admin-field--full"><label for="summary">Kısa özet</label><textarea id="summary" name="summary"><?= e($values['summary']) ?></textarea></div>
    <div class="admin-field"><label for="tried">Denediğim</label><textarea id="tried" name="tried"><?= e($values['tried']) ?></textarea></div>
    <div class="admin-field"><label for="failed">Çalışmayan / zorlayan</label><textarea id="failed" name="failed"><?= e($values['failed']) ?></textarea></div>
    <div class="admin-field"><label for="decision">Kararım</label><textarea id="decision" name="decision"><?= e($values['decision']) ?></textarea></div>
    <div class="admin-field"><label for="next">Sıradaki</label><textarea id="next" name="next"><?= e($values['next']) ?></textarea></div>
    <div class="admin-field"><label for="media_file">Görsel, video veya ses yükle</label><input id="media_file" name="media_file" type="file" accept="image/png,image/jpeg,image/webp,image/gif,video/mp4,video/webm,audio/mpeg,audio/ogg,audio/wav"></div>
    <div class="admin-field"><label for="media_src">Ya da mevcut medya yolu</label><input id="media_src" name="media_src" value="<?= e($values['media_src']) ?>" placeholder="media/ornek.webp"></div>
    <div class="admin-field admin-field--full"><label for="media_alt">Görsel açıklaması</label><input id="media_alt" name="media_alt" value="<?= e($values['media_alt']) ?>"></div>
    <div class="admin-field admin-field--third"><label for="instagram_url">Instagram bağlantısı</label><input id="instagram_url" name="instagram_url" type="url" value="<?= e($values['instagram_url']) ?>"></div>
    <div class="admin-field admin-field--third"><label for="youtube_url">YouTube bağlantısı</label><input id="youtube_url" name="youtube_url" type="url" value="<?= e($values['youtube_url']) ?>"></div>
    <div class="admin-field admin-field--third"><label for="github_url">GitHub bağlantısı</label><input id="github_url" name="github_url" type="url" value="<?= e($values['github_url']) ?>"></div>
    <div class="admin-form-actions"><button class="admin-button admin-button--primary" type="submit"><?= $existing ? 'Kaydı güncelle' : 'Atölyeye ekle' ?></button><a class="admin-button" href="project-edit.php?slug=<?= e(rawurlencode($slug)) ?>">Vazgeç</a></div>
</form>
<?php admin_layout_end(); ?>
