<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
admin_require_login();

$slug = safe_slug((string) ($_GET['slug'] ?? $_POST['slug'] ?? ''));
$project = load_project($slug);
if (!$project) {
    http_response_code(404);
    exit('Proje bulunamadı.');
}
$siteData = load_site();
$categories = admin_category_options($siteData);
$values = admin_project_form_values($project);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_require_csrf();
    foreach (['title','question','summary','category','status','status_label','type_label','tags','workshop_question','workshop_status','story_status','cover'] as $key) {
        $values[$key] = trim((string) ($_POST[$key] ?? $values[$key]));
    }
    $values['homepage'] = isset($_POST['homepage']);
    $values['public'] = isset($_POST['public']);
    $pin = isset($_POST['pin']);

    if ($values['title'] === '') {
        $error = 'Proje adı boş olamaz.';
    } elseif ($pin && !in_array($values['workshop_status'], ['open', 'paused'], true)) {
        $error = 'Yalnızca açık veya beklemedeki bir Atölye ana sayfaya sabitlenebilir.';
    } else {
        try {
            $record = is_array($project['_project'] ?? null) ? $project['_project'] : [];
            $record = array_replace($record, [
                'schema_version' => 2,
                'slug' => $slug,
                'title' => $values['title'],
                'question' => $values['question'] !== '' ? $values['question'] : $values['title'],
                'summary' => $values['summary'],
                'category' => $values['category'],
                'category_label' => $categories[$values['category']] ?? $values['category'],
                'status' => $values['status'],
                'status_label' => $values['status_label'],
                'type_label' => $values['type_label'],
                'updated_at' => date('Y-m-d'),
                'cover' => $values['cover'] !== '' ? $values['cover'] : 'media/cover.svg',
                'tags' => admin_tags_from_input($values['tags']),
                'homepage' => (bool) $values['homepage'],
                'public' => (bool) $values['public'],
                'workshop_question' => $values['workshop_question'] !== '' ? $values['workshop_question'] : null,
            ]);
            $record['workshop'] = array_replace(is_array($record['workshop'] ?? null) ? $record['workshop'] : [], [
                'status' => in_array($values['workshop_status'], ['none','open','paused','closed'], true) ? $values['workshop_status'] : 'none',
            ]);
            if ($record['workshop']['status'] === 'open' && empty($record['workshop']['started_at'])) {
                $record['workshop']['started_at'] = date('Y-m-d');
            }
            $record['story'] = array_replace(is_array($record['story'] ?? null) ? $record['story'] : [], [
                'status' => in_array($values['story_status'], ['none','draft','published'], true) ? $values['story_status'] : 'none',
            ]);
            if ($record['story']['status'] === 'published' && empty($record['story']['published_at'])) {
                $record['story']['published_at'] = date(DATE_ATOM);
            }
            $uploaded = admin_upload_media($slug, 'cover_file');
            if ($uploaded !== null) $record['cover'] = $uploaded;
            save_project_record($record);

            $currentPinned = safe_slug((string) ($siteData['homepage']['pinned_atelier'] ?? ''));
            if ($pin) {
                $siteData['homepage']['pinned_atelier'] = $slug;
            } elseif ($currentPinned === $slug) {
                $siteData['homepage']['pinned_atelier'] = '';
            }
            save_site($siteData);
            admin_flash('success', 'Proje bilgileri kaydedildi.');
            admin_redirect('project-edit.php?slug=' . rawurlencode($slug));
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

$project = load_project($slug) ?? $project;
$updates = load_updates($project);
$pinned = safe_slug((string) ($siteData['homepage']['pinned_atelier'] ?? '')) === $slug;
admin_layout_start('Proje: ' . ($project['title'] ?? ''), 'projects');
?>
<section class="admin-hero"><div><p class="admin-eyebrow">PROJE MERKEZİ</p><h1><?= e($project['title'] ?? '') ?></h1><p class="admin-muted">Atölye durumu, hikâye durumu ve ham kayıtlar aynı proje altında tutulur.</p></div><div class="admin-actions"><?php if (workshop_status($project) !== 'none'): ?><a href="update-new.php?slug=<?= e(rawurlencode($slug)) ?>">Yeni kayıt</a><?php endif; ?><?php if (workshop_is_active($project)): ?><a href="workshop-close.php?slug=<?= e(rawurlencode($slug)) ?>">Atölyeyi kapat</a><?php endif; ?><a href="story-edit.php?slug=<?= e(rawurlencode($slug)) ?>">Hikâyeyi düzenle</a></div></section>
<?php if ($error !== ''): ?><div class="admin-flash admin-flash--error"><?= e($error) ?></div><?php endif; ?>

<div class="admin-grid" style="margin-bottom:24px">
    <article class="admin-card admin-stat"><strong><?= count($updates) ?></strong><span>Ham atölye kaydı</span></article>
    <article class="admin-card admin-stat"><strong><?= count(array_filter($updates, static fn(array $u): bool => (bool)($u['milestone'] ?? false))) ?></strong><span>Dönüm noktası</span></article>
    <article class="admin-card admin-stat"><strong><?= e(ucfirst(workshop_status($project))) ?></strong><span>Atölye durumu</span></article>
    <article class="admin-card admin-stat"><strong><?= e(ucfirst(story_status($project))) ?></strong><span>Hikâye durumu</span></article>
</div>

<form method="post" enctype="multipart/form-data" class="admin-form admin-card">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="slug" value="<?= e($slug) ?>">
    <div class="admin-field"><label for="title">Proje adı</label><input id="title" name="title" value="<?= e($values['title']) ?>" required></div>
    <div class="admin-field"><label>Slug</label><input value="<?= e($slug) ?>" disabled></div>
    <div class="admin-field admin-field--full"><label for="question">Merak sorusu / görünen başlık</label><input id="question" name="question" value="<?= e($values['question']) ?>"></div>
    <div class="admin-field admin-field--full"><label for="summary">Kısa açıklama</label><textarea id="summary" name="summary"><?= e($values['summary']) ?></textarea></div>
    <div class="admin-field admin-field--third"><label for="category">Alan</label><select id="category" name="category"><?php foreach ($categories as $id => $label): ?><option value="<?= e($id) ?>" <?= $values['category']===$id?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
    <div class="admin-field admin-field--third"><label for="workshop_status">Atölye durumu</label><select id="workshop_status" name="workshop_status"><?php foreach (['none'=>'Yok','open'=>'Açık','paused'=>'Beklemede','closed'=>'Kapandı'] as $id=>$label): ?><option value="<?= e($id) ?>" <?= $values['workshop_status']===$id?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
    <div class="admin-field admin-field--third"><label for="story_status">Hikâye durumu</label><select id="story_status" name="story_status"><?php foreach (['none'=>'Yok','draft'=>'Taslak','published'=>'Yayında'] as $id=>$label): ?><option value="<?= e($id) ?>" <?= $values['story_status']===$id?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
    <div class="admin-field"><label for="status_label">Ziyaretçiye görünen durum</label><input id="status_label" name="status_label" value="<?= e($values['status_label']) ?>"></div>
    <div class="admin-field"><label for="type_label">İçerik etiketi</label><input id="type_label" name="type_label" value="<?= e($values['type_label']) ?>"></div>
    <div class="admin-field admin-field--full"><label for="workshop_question">Atölye sorusu</label><input id="workshop_question" name="workshop_question" value="<?= e($values['workshop_question']) ?>"></div>
    <div class="admin-field"><label for="cover">Kapak yolu</label><input id="cover" name="cover" value="<?= e($values['cover']) ?>"></div>
    <div class="admin-field"><label for="cover_file">Yeni kapak yükle</label><input id="cover_file" name="cover_file" type="file" accept="image/png,image/jpeg,image/webp,image/gif,video/mp4,video/webm"></div>
    <div class="admin-field admin-field--full"><label for="tags">Etiketler</label><input id="tags" name="tags" value="<?= e($values['tags']) ?>"></div>
    <div class="admin-field admin-field--full"><label class="admin-check"><input type="checkbox" name="public" <?= $values['public']?'checked':'' ?>> Kamusal sayfalarda göster</label><label class="admin-check"><input type="checkbox" name="homepage" <?= $values['homepage']?'checked':'' ?>> Ana sayfa için uygun</label><label class="admin-check"><input type="checkbox" name="pin" <?= $pinned?'checked':'' ?>> Açık Atölyeyi ana sayfaya sabitle</label></div>
    <input type="hidden" name="status" value="<?= e($values['status']) ?>">
    <div class="admin-form-actions"><button class="admin-button admin-button--primary" type="submit">Bilgileri kaydet</button><a class="admin-button" href="<?= e('../' . story_url($project)) ?>" target="_blank" rel="noopener">Kamusal görünümü aç</a></div>
</form>

<section class="admin-section">
    <div class="admin-section-head"><div><p class="admin-eyebrow">HAM ÇALIŞMA GÜNLÜĞÜ</p><h2>Atölye kayıtları</h2></div><?php if (workshop_status($project) !== 'none'): ?><a class="admin-button admin-button--primary" href="update-new.php?slug=<?= e(rawurlencode($slug)) ?>">Yeni kayıt ekle</a><?php endif; ?></div>
    <?php if ($updates === []): ?><div class="admin-empty">Bu projede henüz atölye kaydı yok.</div><?php else: ?><div class="admin-update-list"><?php foreach (array_reverse($updates) as $update): ?><article class="admin-update"><time><?= e($update['date_label'] ?? format_tr_date((string)($update['date'] ?? ''))) ?></time><div><strong><?= e($update['title'] ?? '') ?></strong><small><?= e($update['phase'] ?? '') ?><?= !empty($update['milestone']) ? ' · Dönüm noktası' : '' ?></small></div><div class="admin-actions"><a href="update-edit.php?slug=<?= e(rawurlencode($slug)) ?>&id=<?= e(rawurlencode((string)($update['_id'] ?? ''))) ?>">Düzenle</a></div></article><?php endforeach; ?></div><?php endif; ?>
</section>
<?php admin_layout_end(); ?>
