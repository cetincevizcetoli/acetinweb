<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
admin_require_login();

$cats = categories();
$error = '';

if (is_post()) {
    verify_csrf();
    $title = trim(old('title'));
    $slug = safe_slug(old('slug') ?: $title);
    $summary = trim(old('summary'));
    $question = trim(old('question'));
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $mode = (string)($_POST['mode'] ?? 'workshop');

    if ($title === '' || $slug === '') {
        $error = 'Baslik ve slug gerekli.';
    } else {
        try {
            db()->beginTransaction();

            $workshop = $mode === 'workshop' ? 'open' : 'none';
            $visibility = (string)($_POST['visibility'] ?? 'private');
            $showHome = checkbox('show_on_home');
            $showArchive = checkbox('show_in_archive');
            $homeSection = (string)($_POST['home_section'] ?? 'none');
            $normalized = admin_normalize_project_publication((bool)$showHome, $homeSection, (bool)$showArchive, (bool)checkbox('show_in_widget'), $workshop);
            $showHome = $normalized['show_home'] ? 1 : 0;
            $showArchive = $normalized['show_archive'] ? 1 : 0;
            $showWidget = $normalized['show_widget'] ? 1 : 0;
            $homeSection = (string)$normalized['home_section'];
            $workshop = (string)$normalized['workshop_status'];
            $sortOrder = admin_resolve_project_sort_order((string)($_POST['sort_order'] ?? ''), (bool)$showHome, $homeSection, (bool)$showArchive);

            $st = db()->prepare("INSERT INTO projects(slug,title,question,summary,category_id,status,status_label,type_label,visibility,workshop_status,workshop_question,show_on_home,show_in_archive,show_in_widget,home_section,sort_order,started_at)
              VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $st->execute([
                $slug,
                $title,
                $question,
                $summary,
                $categoryId ?: null,
                'fikir',
                'Yeni',
                'Proje',
                $visibility,
                $workshop,
                trim(old('workshop_question')),
                $showHome,
                $showArchive,
                $showWidget,
                $homeSection,
                $sortOrder,
                $workshop === 'open' ? date('Y-m-d') : null,
            ]);
            $id = (int)db()->lastInsertId();

            if ($mode === 'story') {
                $s = db()->prepare("INSERT INTO stories(project_id,title,question,summary,status,visibility,show_on_home,show_in_archive,sort_order) VALUES (?,?,?,?, 'draft', ?,?,?,?)");
                $s->execute([$id, $title, $question, $summary, $visibility, $showHome, $showArchive, $sortOrder]);
            }

            db()->commit();
            admin_audit('create', 'project', $id, $title);
            flash('success', 'Proje olusturuldu.');
            foreach ($normalized['warnings'] as $warning) flash('warning', $warning);
            foreach (admin_project_sort_conflicts($id, $sortOrder, (bool)$showHome, $homeSection, (bool)$showArchive) as $warning) flash('warning', $warning);
            redirect('project-edit.php?id=' . $id);
        } catch (Throwable $e) {
            if (db()->inTransaction()) db()->rollBack();
            $error = 'Proje olusturulamadi: ' . admin_error_message($e, 'admin.project_new');
        }
    }
}

admin_head('Yeni proje');
?>
<div class="page-head">
    <div>
        <p class="eyebrow">YENI PROJE</p>
        <h1>Once proje, sonra calisma bicimi.</h1>
        <p>Atolye ve Hikaye ayri icerikler degil; ayni projenin iki gorunumu.</p>
    </div>
</div>
<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
<form class="panel" method="post">
    <?= csrf_field() ?>
    <div class="form-grid">
        <div class="field"><label>Proje basligi</label><input name="title" value="<?= e(old('title')) ?>" required></div>
        <div class="field"><label>Slug</label><input name="slug" value="<?= e(old('slug')) ?>" placeholder="Bos birakilirsa basliktan olusur"></div>
        <div class="field full"><label>Merak sorusu</label><input name="question" value="<?= e(old('question')) ?>" placeholder="Bu fikir neden dogdu?"></div>
        <div class="field full"><label>Kisa ozet</label><textarea name="summary"><?= e(old('summary')) ?></textarea></div>
        <div class="field"><label>Kategori</label><select name="category_id"><option value="">Secin</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['title']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Baslangic bicimi</label><select name="mode"><option value="workshop">Atolye acik baslat</option><option value="story">Hikaye taslagi baslat</option><option value="draft">Yalnizca proje taslagi</option></select></div>
        <div class="field full"><label>Atolye sorusu</label><input name="workshop_question" value="<?= e(old('workshop_question')) ?>" placeholder="Bu denemede neyi cozmeye calisiyorum?"></div>
        <div class="field"><label>Gorunurluk</label><select name="visibility"><option value="private">Gizli</option><option value="unlisted">Baglantiya sahip olanlar</option><option value="public">Herkese acik</option></select></div>
        <div class="field"><label>Ana sayfadaki yeri</label><select name="home_section"><option value="none">Kapali</option><option value="focus">One cikan buyuk kart</option><option value="trace">Alt serit / kucuk kayit</option></select></div>
        <div class="field"><label>Yayin sirasi</label><input type="number" step="0.1" name="sort_order" value="" placeholder="Bos ise sistem uygun numarayi verir"></div>
        <div class="field full check-row">
            <label class="check"><input type="checkbox" name="show_on_home"> Ana sayfada goster</label>
            <label class="check"><input type="checkbox" name="show_in_archive" checked> Hikayeler sayfasinda goster</label>
            <label class="check"><input type="checkbox" name="show_in_widget" checked> Atolye penceresinde goster</label>
        </div>
        <p class="help">Yayin sirasi ana sayfa ve Hikayeler listesi icindir; bos birakirsan sistem ayni alanlarda kullanilmayan uygun numarayi verir.</p>
        <p class="help">Atolye penceresinde gorunmesi icin proje public olmali, Atolye durumu Acik veya Beklemede olmali ve bu secenek isaretli olmali.</p>
    </div>
    <div class="form-actions"><button class="accent" type="submit">Projeyi olustur</button><a class="button secondary" href="index.php">Vazgec</a></div>
</form>
<?php admin_foot(); ?>
