<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
admin_require_login();

$projects = load_projects(true);
$open = array_filter($projects, static fn(array $p): bool => workshop_is_active($p));
$draftStories = array_filter($projects, static fn(array $p): bool => story_status($p) === 'draft');
$published = array_filter($projects, static fn(array $p): bool => story_is_published($p));
$withoutStory = array_filter($projects, static fn(array $p): bool => story_status($p) === 'none');

admin_layout_start('Projeler', 'projects');
?>
<section class="admin-hero">
    <div><p class="admin-eyebrow">TEK PROJE · İKİ GÖRÜNÜM</p><h1>Atölye ve hikâyeler</h1><p class="admin-muted">Atölye sürerken ham kayıtlar eklenir. İş kapandığında seçtiğin dönüm noktalarından hikâye taslağı oluşur.</p></div>
    <a class="admin-button admin-button--primary" href="project-new.php">Yeni proje oluştur</a>
</section>

<div class="admin-grid">
    <article class="admin-card admin-stat"><strong><?= count($projects) ?></strong><span>Toplam proje</span></article>
    <article class="admin-card admin-stat"><strong><?= count($open) ?></strong><span>Açık atölye</span></article>
    <article class="admin-card admin-stat"><strong><?= count($draftStories) ?></strong><span>Hikâye taslağı</span></article>
    <article class="admin-card admin-stat"><strong><?= count($published) ?></strong><span>Yayındaki hikâye</span></article>
</div>

<section class="admin-section">
    <div class="admin-section-head"><div><p class="admin-eyebrow">PROJE YAŞAM DÖNGÜSÜ</p><h2>Bütün kayıtlar</h2></div><p class="admin-muted">Aynı proje hem açık bir atölyeye hem de yayımlanmış bir hikâyeye sahip olabilir.</p></div>
    <?php if ($projects === []): ?>
        <div class="admin-empty">Henüz proje yok.</div>
    <?php else: ?>
    <div class="admin-table-wrap"><table class="admin-table">
        <thead><tr><th>Proje</th><th>Atölye</th><th>Hikâye</th><th>Güncelleme</th><th>Son tarih</th><th>İşlemler</th></tr></thead>
        <tbody>
        <?php foreach ($projects as $project): $updates = load_updates($project); $last = $updates !== [] ? $updates[array_key_last($updates)] : null; ?>
            <tr>
                <td class="admin-title-cell"><strong><?= e($project['title'] ?? '') ?></strong><small><?= e($project['slug'] ?? '') ?> · <?= e($project['category_label'] ?? '') ?></small></td>
                <td><span class="badge <?= workshop_is_active($project) ? 'badge--ok' : 'badge--off' ?>"><?= e(admin_workshop_label($project)) ?></span></td>
                <td><span class="badge <?= story_is_published($project) ? 'badge--ok' : (story_status($project) === 'draft' ? 'badge--draft' : 'badge--off') ?>"><?= e(admin_story_label($project)) ?></span></td>
                <td><?= count($updates) ?><?php if ($last): ?><br><small class="admin-muted"><?= e($last['title'] ?? '') ?></small><?php endif; ?></td>
                <td><?= e(format_tr_date((string) ($project['updated_at'] ?? $project['started_at'] ?? ''))) ?: '—' ?></td>
                <td><div class="admin-actions">
                    <a href="project-edit.php?slug=<?= e(rawurlencode((string) $project['slug'])) ?>">Düzenle</a>
                    <?php if (workshop_status($project) !== 'none'): ?><a href="update-new.php?slug=<?= e(rawurlencode((string) $project['slug'])) ?>">Kayıt ekle</a><?php endif; ?>
                    <?php if (workshop_is_active($project)): ?><a href="workshop-close.php?slug=<?= e(rawurlencode((string) $project['slug'])) ?>">Atölyeyi kapat</a><?php endif; ?>
                    <a href="story-edit.php?slug=<?= e(rawurlencode((string) $project['slug'])) ?>">Hikâye</a>
                    <?php if (project_is_public($project)): ?><a href="../<?= e(story_url($project)) ?>" target="_blank" rel="noopener">Görüntüle</a><?php endif; ?>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</section>

<section class="admin-section admin-card">
    <p class="admin-eyebrow">MANTIĞIN ÖZÜ</p>
    <h2>Atölye ve hikâye iki ayrı proje değil.</h2>
    <p class="admin-muted">Atölye projenin sürerken görünen ham çalışma alanıdır. Hikâye, aynı projenin seçilmiş dönüm noktalarıyla düzenlenmiş anlatımıdır. Atölyeyi kapatınca ham kayıtlar silinmez; yalnızca hikâye taslağı için malzemeye dönüşür.</p>
</section>
<?php admin_layout_end(); ?>
