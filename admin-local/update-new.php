<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_update_form.php';
admin_require_login();

$projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
$st = db()->prepare('SELECT * FROM projects WHERE id=? AND deleted_at IS NULL');
$st->execute([$projectId]);
$project = $st->fetch();
if (!$project) {
    http_response_code(404);
    exit('Proje bulunamadı.');
}

$error = '';
if (is_post()) {
    verify_csrf();
    try {
        db()->beginTransaction();
        $title = trim(old('title'));
        if ($title === '') throw new RuntimeException('Başlık gerekli.');

        $entryKind = (string)($_POST['entry_kind'] ?? 'journal');
        if (!atelier_entry_kind_is_valid($entryKind)) {
            $entryKind = 'journal';
        }
        $storyRole = (string)($_POST['story_role'] ?? 'auto');
        if (!atelier_story_role_is_valid($storyRole)) $storyRole = 'auto';
        $storySectionType = (string)($_POST['story_section_type'] ?? 'auto');
        if (!atelier_story_section_type_is_valid($storySectionType)) $storySectionType = 'auto';
        $storyLayout = (string)($_POST['story_layout'] ?? 'auto');
        if (!atelier_story_layout_is_valid($storyLayout)) $storyLayout = 'auto';
        $storyLabel = trim(old('story_label'));

        $slug = unique_update_slug($projectId, old('slug') ?: $title);
        $status = (string)($_POST['status'] ?? 'draft');
        $visibility = (string)($_POST['visibility'] ?? 'public');
        $workDate = old('work_date') ?: null;
        $display = trim(old('display_label'));
        if ($display === '' && $workDate) $display = date('d.m.Y', strtotime($workDate));
        $sortOrder = trim((string)($_POST['sort_order'] ?? '')) !== '' ? (float)$_POST['sort_order'] : admin_next_update_sort_order($projectId);

        $st = db()->prepare("INSERT INTO updates(project_id,slug,work_date,display_label,title,summary,entry_kind,story_role,story_section_type,story_layout,story_label,tried,failed,decision,next_step,phase,is_milestone,status,visibility,show_in_recent,sort_order,published_at)
          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $st->execute([
            $projectId,
            $slug,
            $workDate,
            $display,
            $title,
            trim(old('summary')),
            $entryKind,
            $storyRole,
            $storySectionType,
            $storyLayout,
            $storyLabel,
            trim(old('tried')),
            trim(old('failed')),
            trim(old('decision')),
            trim(old('next_step')),
            trim(old('phase')) ?: 'Genel',
            checkbox('is_milestone'),
            $status,
            $visibility,
            checkbox('show_in_recent'),
            $sortOrder,
            $status === 'published' ? now_sql() : null,
        ]);

        $id = (int)db()->lastInsertId();
        $existing = assert_project_media_ids($projectId, $_POST['existing_media_ids'] ?? [], 'Mevcut medya');
        $uploaded = save_uploaded_files($projectId, (string)$project['slug']);
        attach_update_media($id, $projectId, array_merge($uploaded, $existing));
        save_update_links($id, is_array($_POST['links'] ?? null) ? $_POST['links'] : []);
        db()->prepare('UPDATE projects SET updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$projectId]);

        db()->commit();
        admin_audit('create', 'update', $id, $title);
        flash('success', 'Atölye kaydı eklendi.');
        redirect('update-edit.php?id=' . $id);
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        $error = admin_error_message($e, 'admin.update_new');
    }
}

admin_head('Yeni Atölye kaydı');
$newUpdateDefaults = ['sort_order' => admin_next_update_sort_order($projectId)];
?>
<div class="page-head">
    <div>
        <p class="eyebrow"><?= e($project['title']) ?></p>
        <h1>Yeni çalışma kaydı</h1>
        <p>Bugün girmek zorunda değilsin. Yalnızca kaybetmek istemediğin bir karar veya deneme olduğunda ekle.</p>
    </div>
</div>
<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="project_id" value="<?= $projectId ?>">
    <?= csrf_field() ?>
    <?php render_update_form($project, $newUpdateDefaults); ?>
    <div class="form-actions"><button class="accent" type="submit">Kaydı oluştur</button><a class="button secondary" href="project-edit.php?id=<?= $projectId ?>">Vazgeç</a></div>
</form>
<?php admin_foot(); ?>
