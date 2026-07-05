<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
admin_require_login();

$siteData = load_site();
$categories = admin_category_options($siteData);
$values = [
    'slug' => '', 'title' => '', 'question' => '', 'summary' => '',
    'category' => array_key_first($categories) ?: 'kod-sistem',
    'status' => 'suruyor', 'status_label' => 'Üzerinde çalışıyorum',
    'type_label' => 'Proje', 'tags' => '', 'workshop_question' => '',
    'start_mode' => 'workshop', 'homepage' => false, 'public' => true, 'pin' => false,
];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_require_csrf();
    foreach ($values as $key => $default) {
        if (is_bool($default)) $values[$key] = isset($_POST[$key]);
        else $values[$key] = trim((string) ($_POST[$key] ?? $default));
    }
    $slug = safe_slug($values['slug'] !== '' ? $values['slug'] : $values['title']);
    $title = trim($values['title']);
    if ($title === '') {
        $error = 'Proje başlığı gerekli.';
    } elseif ($slug === '') {
        $error = 'Geçerli bir slug üretilemedi.';
    } elseif (is_dir(project_dir($slug))) {
        $error = 'Bu slug ile bir proje zaten var.';
    } else {
        try {
            ensure_directory(media_dir($slug));
            ensure_directory(update_dir($slug));
            $cover = admin_write_default_cover($slug, $title);
            $uploaded = admin_upload_media($slug, 'cover_file');
            if ($uploaded !== null) $cover = $uploaded;
            $category = (string) $values['category'];
            $mode = in_array($values['start_mode'], ['workshop', 'story', 'draft'], true) ? $values['start_mode'] : 'workshop';
            $project = [
                'schema_version' => 2,
                'slug' => $slug,
                'title' => $title,
                'question' => $values['question'] !== '' ? $values['question'] : $title,
                'summary' => $values['summary'],
                'category' => $category,
                'category_label' => $categories[$category] ?? $category,
                'status' => $values['status'],
                'status_label' => $values['status_label'],
                'type_label' => $mode === 'workshop' ? 'Canlı atölye' : 'Proje hikâyesi',
                'order' => 50,
                'started_at' => date('Y-m-d'),
                'updated_at' => date('Y-m-d'),
                'cover' => $cover,
                'tags' => admin_tags_from_input($values['tags']),
                'homepage' => (bool) $values['homepage'],
                'public' => (bool) $values['public'],
                'workshop_question' => $values['workshop_question'] !== '' ? $values['workshop_question'] : null,
                'workshop' => [
                    'status' => $mode === 'workshop' ? 'open' : 'none',
                    'started_at' => $mode === 'workshop' ? date('Y-m-d') : null,
                    'ended_at' => null,
                    'closing_state' => null,
                    'closing_note' => '',
                ],
                'story' => [
                    'status' => $mode === 'story' ? 'draft' : 'none',
                    'published_at' => null,
                    'generated_from_updates' => [],
                ],
            ];
            save_project_record($project);
            save_story_record($slug, admin_story_skeleton($project));

            if ((bool) $values['pin'] && $mode === 'workshop') {
                $siteData['homepage']['pinned_atelier'] = $slug;
                save_site($siteData);
            }
            admin_flash('success', 'Proje oluşturuldu.');
            admin_redirect('project-edit.php?slug=' . rawurlencode($slug));
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

admin_layout_start('Yeni proje', 'new');
?>
<section class="admin-hero"><div><p class="admin-eyebrow">YENİ KAYIT</p><h1>Tek proje oluştur</h1><p class="admin-muted">Atölye ve hikâye daha sonra aynı proje klasöründe gelişir.</p></div></section>
<?php if ($error !== ''): ?><div class="admin-flash admin-flash--error"><?= e($error) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="admin-field"><label for="title">Proje adı</label><input id="title" name="title" value="<?= e($values['title']) ?>" required></div>
    <div class="admin-field"><label for="slug">Slug <small>(boşsa başlıktan üretilir)</small></label><input id="slug" name="slug" value="<?= e($values['slug']) ?>" pattern="[a-z0-9-]*"></div>
    <div class="admin-field admin-field--full"><label for="question">Merak sorusu / hikâye başlığı</label><input id="question" name="question" value="<?= e($values['question']) ?>"></div>
    <div class="admin-field admin-field--full"><label for="summary">Kısa açıklama</label><textarea id="summary" name="summary"><?= e($values['summary']) ?></textarea></div>
    <div class="admin-field admin-field--third"><label for="category">Alan</label><select id="category" name="category"><?php foreach ($categories as $id => $label): ?><option value="<?= e($id) ?>" <?= $values['category'] === $id ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
    <div class="admin-field admin-field--third"><label for="start_mode">Başlangıç biçimi</label><select id="start_mode" name="start_mode"><option value="workshop" <?= $values['start_mode']==='workshop'?'selected':'' ?>>Atölyeyi aç</option><option value="story" <?= $values['start_mode']==='story'?'selected':'' ?>>Hikâye taslağı</option><option value="draft" <?= $values['start_mode']==='draft'?'selected':'' ?>>Yalnızca taslak proje</option></select></div>
    <div class="admin-field admin-field--third"><label for="cover_file">Kapak görseli / videosu</label><input id="cover_file" name="cover_file" type="file" accept="image/png,image/jpeg,image/webp,image/gif,video/mp4,video/webm"></div>
    <div class="admin-field"><label for="status_label">Ziyaretçiye görünen durum</label><input id="status_label" name="status_label" value="<?= e($values['status_label']) ?>"></div>
    <div class="admin-field"><label for="workshop_question">Atölye sorusu</label><input id="workshop_question" name="workshop_question" value="<?= e($values['workshop_question']) ?>"></div>
    <div class="admin-field admin-field--full"><label for="tags">Etiketler <small>(virgülle ayır)</small></label><input id="tags" name="tags" value="<?= e($values['tags']) ?>"></div>
    <div class="admin-field admin-field--full"><label class="admin-check"><input type="checkbox" name="public" <?= $values['public'] ? 'checked' : '' ?>> Proje kamusal listelerde görünebilir</label><label class="admin-check"><input type="checkbox" name="homepage" <?= $values['homepage'] ? 'checked' : '' ?>> Ana sayfa için uygun</label><label class="admin-check"><input type="checkbox" name="pin" <?= $values['pin'] ? 'checked' : '' ?>> Atölye olarak ana sayfaya sabitle</label></div>
    <div class="admin-form-actions"><button class="admin-button admin-button--primary" type="submit">Projeyi oluştur</button><a class="admin-button" href="index.php">Vazgeç</a></div>
</form>
<?php admin_layout_end(); ?>
