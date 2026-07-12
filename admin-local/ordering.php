<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
admin_require_login();

function ordering_story_from_project(array $project): ?array
{
    if (empty($project['story_id'])) return null;
    return [
        'id' => $project['story_id'],
        'status' => $project['story_status'] ?? '',
        'visibility' => $project['story_visibility'] ?? '',
        'published_at' => $project['story_published_at'] ?? null,
        'deleted_at' => null,
    ];
}

function ordering_result_chip(bool $visible): string
{
    return $visible ? 'chip ok' : 'chip warn';
}

function ordering_story_label(array $project): string
{
    if (empty($project['story_id'])) return 'Hikaye yok';
    $status = (string)($project['story_status'] ?? 'bilinmiyor');
    $visibility = (string)($project['story_visibility'] ?? 'bilinmiyor');
    return $status . ' / ' . $visibility;
}

function ordering_sort_scope_label(string $scope): string
{
    return match ($scope) {
        'home:focus' => 'Ana sayfa / One cikan buyuk kart',
        'home:trace' => 'Ana sayfa / Alt serit / kucuk kayit',
        'archive' => 'Hikayeler sayfasi',
        default => $scope,
    };
}

function ordering_pending_sort_conflicts(array $pending): array
{
    $groups = [];
    foreach ($pending as $project) {
        if ($project['visibility'] !== 'public') continue;

        $orderKey = rtrim(rtrim(sprintf('%.3F', (float)$project['sort_order']), '0'), '.');
        if ($project['show_on_home'] && VisibilityService::homeSectionIsVisible($project['home_section'])) {
            $groups['home:' . $project['home_section']][$orderKey][] = $project['title'];
        }
        if ($project['show_in_archive']) {
            $groups['archive'][$orderKey][] = $project['title'];
        }
    }

    $messages = [];
    foreach ($groups as $scope => $orders) {
        foreach ($orders as $order => $titles) {
            if (count($titles) < 2) continue;
            $messages[] = ordering_sort_scope_label($scope) . ' alaninda ' . $order . ' sira numarasi birden fazla projede kullaniliyor: ' . implode(', ', $titles);
        }
    }
    return $messages;
}

function ordering_sort_key(float $sortOrder): string
{
    return sprintf('%.3F', $sortOrder);
}

function ordering_project_sort_scopes(array $project): array
{
    if ($project['visibility'] !== 'public') return [];

    $scopes = [];
    if ($project['show_on_home'] && VisibilityService::homeSectionIsVisible($project['home_section'])) {
        $scopes[] = 'home:' . $project['home_section'];
    }
    if ($project['show_in_archive']) {
        $scopes[] = 'archive';
    }
    return $scopes;
}

function ordering_sort_candidate_is_used(array $pending, int $projectId, array $project, float $candidate): bool
{
    $scopes = ordering_project_sort_scopes($project);
    if (!$scopes) return false;

    $candidateKey = ordering_sort_key($candidate);
    foreach ($pending as $otherId => $other) {
        if ((int)$otherId === $projectId || $other['sort_order'] === null) continue;
        if (ordering_sort_key((float)$other['sort_order']) !== $candidateKey) continue;
        if (array_intersect($scopes, ordering_project_sort_scopes($other))) return true;
    }
    return false;
}

function ordering_fill_auto_sort_orders(array $pending): array
{
    foreach ($pending as $projectId => $project) {
        if ($project['sort_order'] !== null) continue;
        if (!ordering_project_sort_scopes($project)) {
            $pending[$projectId]['sort_order'] = 999.0;
            continue;
        }

        $candidate = 10.0;
        while (ordering_sort_candidate_is_used($pending, (int)$projectId, $project, $candidate)) {
            $candidate += 10.0;
        }
        $pending[$projectId]['sort_order'] = $candidate;
    }
    return $pending;
}

if (is_post()) {
    verify_csrf();
    try {
        $projectTitles = [];
        foreach (admin_projects() as $project) {
            $projectTitles[(int)$project['id']] = (string)$project['title'];
        }

        $pending = [];
        $normalizationWarnings = [];
        foreach ($_POST['projects'] ?? [] as $id => $row) {
            $projectId = (int)$id;
            if (!isset($projectTitles[$projectId])) continue;

            $visibility = (string)($row['visibility'] ?? 'private');
            $workshopStatus = (string)($row['workshop_status'] ?? 'none');
            $homeSection = (string)($row['section'] ?? 'none');
            $showHome = !empty($row['home']) ? 1 : 0;
            $showArchive = !empty($row['archive']) ? 1 : 0;
            $showWidget = !empty($row['widget']) ? 1 : 0;
            $isPinned = !empty($row['pinned']) ? 1 : 0;
            $sortRaw = trim((string)($row['order'] ?? ''));
            $sortOrder = $sortRaw !== '' ? (float)$sortRaw : null;

            $normalized = admin_normalize_project_publication((bool)$showHome, $homeSection, (bool)$showArchive, (bool)$showWidget, $workshopStatus);
            $showHome = $normalized['show_home'] ? 1 : 0;
            $homeSection = (string)$normalized['home_section'];
            $showArchive = $normalized['show_archive'] ? 1 : 0;
            $showWidget = $normalized['show_widget'] ? 1 : 0;
            $workshopStatus = (string)$normalized['workshop_status'];
            foreach ($normalized['warnings'] as $warning) $normalizationWarnings[] = '#' . $projectId . ': ' . $warning;

            $pending[$projectId] = [
                'id' => $projectId,
                'title' => $projectTitles[$projectId],
                'visibility' => $visibility,
                'workshop_status' => $workshopStatus,
                'sort_order' => $sortOrder,
                'show_on_home' => (bool)$showHome,
                'show_in_archive' => (bool)$showArchive,
                'show_in_widget' => (bool)$showWidget,
                'is_pinned' => (bool)$isPinned,
                'home_section' => $homeSection,
            ];
        }

        $pending = ordering_fill_auto_sort_orders($pending);
        $sortConflicts = ordering_pending_sort_conflicts($pending);
        if ($sortConflicts) {
            throw new RuntimeException('Sira cakismasi var. Kayit yapilmadi. ' . implode(' ', $sortConflicts));
        }

        db()->beginTransaction();
        foreach ($pending as $projectId => $project) {
            $st = db()->prepare('UPDATE projects SET visibility=?, workshop_status=?, sort_order=?, show_on_home=?, show_in_archive=?, show_in_widget=?, is_pinned=?, home_section=?, updated_at=CURRENT_TIMESTAMP WHERE id=?');
            $st->execute([
                $project['visibility'],
                $project['workshop_status'],
                $project['sort_order'],
                $project['show_on_home'] ? 1 : 0,
                $project['show_in_archive'] ? 1 : 0,
                $project['show_in_widget'] ? 1 : 0,
                $project['is_pinned'] ? 1 : 0,
                $project['home_section'],
                $projectId,
            ]);
            if ($project['is_pinned']) db()->prepare('UPDATE projects SET is_pinned=0 WHERE id<>?')->execute([$projectId]);
        }
        db()->commit();
        admin_audit('update', 'publishing', 0, 'Toplu yayin ve sira ayarlari guncellendi.');
        flash('success', 'Yayin ve sira ayarlari kaydedildi. Yayin Merkezi bunu SQLite DB degisikligi olarak algilar.');
        foreach (array_unique($normalizationWarnings) as $warning) flash('warning', $warning);
        redirect('ordering.php');
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        flash('error', admin_error_message($e, 'admin.ordering'));
    }
}

$projects = admin_projects();
$currentSortConflicts = ordering_pending_sort_conflicts($projects);
admin_head('Yayin ve sira');
?>
<div class="page-head">
    <div>
        <p class="eyebrow">YAYIN KONTROLU</p>
        <h1>Ne yayinlansin, nerede gorunsun?</h1>
        <p>Bu ekran proje icindeki yayin ayarlarinin toplu halidir. Projeyi silmeden ana sayfa, Hikayeler sayfasi ve Atolye penceresi gorunumunu buradan yonetebilirsin.</p>
    </div>
    <a class="button secondary" href="deploy.php">Yayin Merkezi</a>
</div>

<?php foreach ($currentSortConflicts as $warning): ?>
    <div class="flash flash-warning"><?= e($warning) ?></div>
<?php endforeach; ?>

<section class="panel publish-guide">
    <h2>Yayin zinciri</h2>
    <div class="list">
        <div class="list-row"><span>Ana sayfa</span><strong>Proje public + Ana sayfada goster + yer secili + hikaye yayinda/public</strong><small>Yer: One cikan buyuk kart veya Alt serit / kucuk kayit.</small></div>
        <div class="list-row"><span>Hikayeler</span><strong>Proje public + Hikayeler sayfasinda goster + hikaye yayinda/public</strong><small>Bu sayfa ham atolye kaydi degil, duzenlenmis hikaye listesidir.</small></div>
        <div class="list-row"><span>Atolye penceresi</span><strong>Proje public + Atolye Acik/Beklemede + Atolye penceresinde goster</strong><small>Hikayesi olup olmamasi Atolye penceresinden cikarmamalidir.</small></div>
        <div class="list-row"><span>Yayin Merkezi</span><strong>Bu ekrandaki her kayit SQLite DB'yi degistirir</strong><small>Canliya giderken fikrimvar.sqlite ve deploy-manifest.json birlikte kontrol edilir.</small></div>
    </div>
</section>

<form method="post">
    <?= csrf_field() ?>
    <div class="sortable publish-board" data-sortable>
        <?php foreach ($projects as $i => $project):
            $story = ordering_story_from_project($project);
            $homeVisible = VisibilityService::homeVisible($project, $story);
            $archiveVisible = VisibilityService::archiveVisible($project, $story);
            $widgetVisible = VisibilityService::widgetVisible($project);
            $projectId = (int)$project['id'];
            $widgetCanBeChecked = VisibilityService::workshopStatusAllowsWidget((string)($project['workshop_status'] ?? 'none'));
            $widgetWasInconsistent = !empty($project['show_in_widget']) && !$widgetCanBeChecked;
        ?>
            <article class="sortable-item publish-row" draggable="true">
                <span class="drag-handle" title="Siralamak icin surukle">::</span>
                <div class="publish-main">
                    <div class="publish-title">
                        <strong><?= e($project['title']) ?></strong>
                        <small><?= e($project['category_title'] ?? 'Kategori yok') ?> · Hikaye: <?= e(ordering_story_label($project)) ?></small>
                    </div>
                    <div class="publish-state">
                        <span class="<?= e(ordering_result_chip($homeVisible)) ?>">Ana sayfa: <?= e($homeVisible ? 'Gorunur' : 'Gorunmez') ?></span>
                        <span class="<?= e(ordering_result_chip($archiveVisible)) ?>">Hikayeler: <?= e($archiveVisible ? 'Gorunur' : 'Gorunmez') ?></span>
                        <span class="<?= e(ordering_result_chip($widgetVisible)) ?>">Atolye: <?= e($widgetVisible ? 'Gorunur' : 'Gorunmez') ?></span>
                    </div>
                    <div class="publish-reasons">
                        <small><?= e(VisibilityService::homeReason($project, $story)) ?></small>
                        <small><?= e(VisibilityService::archiveReason($project, $story)) ?></small>
                        <small><?= e(VisibilityService::widgetReason($project)) ?></small>
                    </div>
                </div>
                <div class="publish-controls">
                    <div class="form-grid">
                        <div class="field">
                            <label>Proje gorunurlugu</label>
                            <select name="projects[<?= $projectId ?>][visibility]">
                                <option value="private" <?= $project['visibility'] === 'private' ? 'selected' : '' ?>>Gizli</option>
                                <option value="unlisted" <?= $project['visibility'] === 'unlisted' ? 'selected' : '' ?>>Baglantiya sahip olanlar</option>
                                <option value="public" <?= $project['visibility'] === 'public' ? 'selected' : '' ?>>Herkese acik</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Ana sayfadaki yeri</label>
                            <select name="projects[<?= $projectId ?>][section]">
                                <option value="none" <?= $project['home_section'] === 'none' ? 'selected' : '' ?>>Kapali</option>
                                <option value="focus" <?= $project['home_section'] === 'focus' ? 'selected' : '' ?>>One cikan buyuk kart</option>
                                <option value="trace" <?= $project['home_section'] === 'trace' ? 'selected' : '' ?>>Alt serit / kucuk kayit</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Atolye durumu</label>
                            <select name="projects[<?= $projectId ?>][workshop_status]">
                                <option value="none" <?= $project['workshop_status'] === 'none' ? 'selected' : '' ?>>Yok</option>
                                <option value="open" <?= $project['workshop_status'] === 'open' ? 'selected' : '' ?>>Acik</option>
                                <option value="paused" <?= $project['workshop_status'] === 'paused' ? 'selected' : '' ?>>Beklemede</option>
                                <option value="closed" <?= $project['workshop_status'] === 'closed' ? 'selected' : '' ?>>Kapandi</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Sira</label>
                            <input type="number" step="0.1" name="projects[<?= $projectId ?>][order]" value="<?= e((string)$project['sort_order']) ?>" data-order-input>
                        </div>
                    </div>
                    <div class="check-row">
                        <label class="check"><input type="checkbox" name="projects[<?= $projectId ?>][home]" <?= $project['show_on_home'] ? 'checked' : '' ?>> Ana sayfada goster</label>
                        <label class="check"><input type="checkbox" name="projects[<?= $projectId ?>][archive]" <?= $project['show_in_archive'] ? 'checked' : '' ?>> Hikayeler sayfasinda goster</label>
                        <label class="check"><input type="checkbox" name="projects[<?= $projectId ?>][widget]" <?= $project['show_in_widget'] && $widgetCanBeChecked ? 'checked' : '' ?>> Atolye penceresinde goster</label>
                        <label class="check"><input type="checkbox" name="projects[<?= $projectId ?>][pinned]" <?= $project['is_pinned'] ? 'checked' : '' ?>> Sabitle</label>
                    </div>
                    <?php if ($widgetWasInconsistent): ?><p class="help">Bu projede Atolye penceresi tiki eski kayittan acik kalmis; Atolye durumu Acik/Beklemede olmadigi icin sonraki kayitta otomatik kapatilacak.</p><?php endif; ?>
                    <div class="card-actions">
                        <a class="button secondary" href="project-edit.php?id=<?= $projectId ?>">Projeyi yonet</a>
                        <?php if (!empty($project['story_id'])): ?><a class="button secondary" href="story-edit.php?project_id=<?= $projectId ?>">Hikaye</a><?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="form-actions">
        <button class="accent" type="submit">Yayin kararlarini kaydet</button>
        <a class="button secondary" href="deploy.php">Yayin Merkezi'nde kontrol et</a>
    </div>
</form>
<?php admin_foot(); ?>
