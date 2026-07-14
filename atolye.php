<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$slug = safe_slug((string)($_GET['slug'] ?? ''));
$project = project_by_slug($slug);
$site = setting('site', []);
$allUpdates = $project ? project_updates((int)$project['id'], !VisibilityService::isAdminViewingFrontend()) : [];
$updates = array_values(array_filter($allUpdates, static fn(array $u): bool => !atelier_is_story_restore_copy($u)));
$active = $updates ? end($updates) : null;
if ($updates) {
    reset($updates);
}

$selectedCount = count(array_filter($updates, fn($u) => (int)$u['is_milestone'] === 1));

$story = $project ? story_by_project((int)$project['id']) : null;
$storySections = $story ? story_sections((int)$story['id']) : [];
$updatesById = [];
foreach ($updates as $u) {
    $updatesById[(int)$u['id']] = $u;
}
$storySourceSections = [];
$storyReferenceSections = [];
foreach ($storySections as $section) {
    $sourceId = (int)($section['source_update_id'] ?? 0);
    if ($sourceId > 0 && isset($updatesById[$sourceId])) {
        $storySourceSections[] = $section;
    } else {
        $storyReferenceSections[] = $section;
    }
}
$atelierActive = $project && in_array((string)$project['workshop_status'], ['open', 'paused'], true);
$atelierAvailable = $project && (in_array((string)$project['workshop_status'], ['open', 'paused', 'closed'], true) || $updates);
if (!$atelierAvailable) {
    http_response_code(404);
}

function first_stage_media(array $u): ?array
{
    foreach ($u['media'] ?? [] as $m) {
        if (($m['media_type'] ?? '') === 'image') return $m;
    }
    return $u['media'][0] ?? null;
}

function atelier_is_story_restore_copy(array $u): bool
{
    $slug = (string)($u['slug'] ?? '');
    $phase = (string)($u['phase'] ?? '');
    $next = (string)($u['next_step'] ?? '');

    return str_starts_with($slug, 'hikaye-')
        && ($phase === 'Hikayeden gelen' || str_contains($next, 'eski hikaye bolumunden Atolye akisine geri alindi'));
}

function atelier_story_excerpt(array $section): string
{
    $text = trim(strip_tags((string)($section['body_text'] ?: $section['intro_text'] ?: $section['quote_text'] ?: $section['note_text'])));
    if ($text === '') {
        return 'Bu hareket mevcut hikâyede ayrıntılı olarak duruyor.';
    }
    return function_exists('mb_strimwidth') ? mb_strimwidth($text, 0, 150, '...', 'UTF-8') : substr($text, 0, 150);
}

function atelier_kind_label(array $u): string
{
    return (string)atelier_entry_kind_config($u)['label'];
}

function atelier_kind_short(array $u): string
{
    return (string)atelier_entry_kind_config($u)['short'];
}

function atelier_kind_seed(array $u): string
{
    return (string)atelier_entry_kind_config($u)['seed'];
}

function atelier_story_bridge_text(array $u): string
{
    $bridge = atelier_story_bridge($u);
    $lower = static fn(string $value): string => function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    $role = $lower((string)$bridge['reader_label']);
    $type = $lower((string)$bridge['type_label']);
    return 'Hikâyeye seçilirse ' . $role . ' olarak kullanılır; ilk taslakta ' . $type . ' bölümüne dönüşür.';
}

function atelier_work_data_attrs(array $u): string
{
    $json = json_encode(atelier_work_artifacts($u), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return ' data-artifacts="' . e($json ?: '[]') . '"';
}

function atelier_gallery_items(array $u): array
{
    $items = [];
    foreach (($u['media'] ?? []) as $m) {
        $path = trim((string)($m['relative_path'] ?? ''));
        if ($path === '') continue;
        $items[] = [
            'url' => media_url($path),
            'type' => (string)($m['media_type'] ?? 'file'),
            'title' => trim((string)($m['title'] ?? $m['original_name'] ?? '')),
            'alt' => trim((string)($m['alt_text'] ?? $m['title'] ?? '')),
            'caption' => trim((string)($m['caption'] ?? $m['title'] ?? $m['original_name'] ?? '')),
        ];
    }
    return $items;
}

function atelier_gallery_data_attrs(array $u): string
{
    $json = json_encode(atelier_gallery_items($u), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return ' data-gallery="' . e($json ?: '[]') . '"';
}

function atelier_link_models(array $links): array
{
    $models = [];
    foreach ($links as $link) {
        $model = LinkRenderer::fromRow(is_array($link) ? $link : []);
        if (!$model) continue;
        $models[] = [
            'url' => $model->url,
            'title' => $model->title,
            'provider' => $model->provider,
            'providerLabel' => $model->host !== '' ? $model->host : $model->provider,
            'displayUrl' => $model->displayUrl,
            'embedKind' => $model->embedKind,
            'embedUrl' => $model->embedUrl,
        ];
    }
    return $models;
}

function atelier_links_data_attrs(array $u): string
{
    $json = json_encode(atelier_link_models($u['links'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return ' data-links="' . e($json ?: '[]') . '"';
}

function atelier_artifact_is_code(array $artifact): bool
{
    return in_array((string)($artifact['type'] ?? ''), ['prompt', 'code', 'output', 'log', 'error'], true)
        || preg_match('/(<[a-z][\s\S]*>|```|\$ |PS |PROMPT:|SQLSTATE|Fatal error|SELECT |UPDATE |INSERT |function |class )/i', (string)($artifact['body'] ?? ''));
}

function render_atelier_artifacts(array $update, string $class = ''): void
{
    $artifacts = atelier_work_artifacts($update);
    if (!$artifacts) return;
    $className = trim('atelier-artifacts ' . $class);
    echo '<div class="' . e($className) . '">';
    foreach ($artifacts as $artifact) {
        $type = preg_replace('/[^a-z0-9_-]/i', '', (string)($artifact['type'] ?? 'note')) ?: 'note';
        echo '<article class="atelier-artifact atelier-artifact--' . e($type) . '">';
        echo '<span>' . e((string)$artifact['label']) . '</span>';
        echo '<h4>' . e((string)$artifact['title']) . '</h4>';
        if (atelier_artifact_is_code($artifact)) {
            echo '<pre><code>' . e((string)$artifact['body']) . '</code></pre>';
        } else {
            echo '<p>' . nl2br(e((string)$artifact['body'])) . '</p>';
        }
        echo '</article>';
    }
    echo '</div>';
}

function render_atelier_gallery(array $update): void
{
    $items = atelier_gallery_items($update);
    if ($items === []) return;

    echo '<div class="atelier-evidence-gallery">';
    echo '<p class="atelier-material-label">Medya / görsel kanıt</p>';
    echo '<div class="atelier-media-grid">';
    foreach ($items as $item) {
        $type = (string)$item['type'];
        echo '<figure>';
        if ($type === 'image') {
            echo '<img src="' . e($item['url']) . '" alt="' . e($item['alt']) . '">';
        } elseif ($type === 'video') {
            echo '<video controls playsinline preload="metadata"><source src="' . e($item['url']) . '"></video>';
        } elseif ($type === 'audio') {
            echo '<audio controls preload="metadata"><source src="' . e($item['url']) . '"></audio>';
        } else {
            echo '<a href="' . e($item['url']) . '" target="_blank" rel="noopener noreferrer">' . e($item['title'] ?: 'Dosyayı aç') . '</a>';
        }
        if ($item['caption'] !== '') {
            echo '<figcaption>' . e($item['caption']) . '</figcaption>';
        }
        echo '</figure>';
    }
    echo '</div>';
    echo '</div>';
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d1014">
    <meta name="description" content="<?= e($project['summary'] ?? 'Canlı atölye günlüğü') ?>">
    <title><?= e($project['title'] ?? 'Atölye') ?> | #FikrimVar</title>
    <link rel="canonical" href="https://www.acetin.com.tr/atolye.php?slug=<?= e(rawurlencode($slug)) ?>">
    <?= public_theme_boot_script() ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600&family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/atelier.css')) ?>">
    <script src="<?= e(asset_url('assets/js/app.js')) ?>" defer></script>
</head>
<body class="atelier-page">
<a class="skip-link" href="#atelier-main">İçeriğe geç</a>
<header class="inner-header inner-header--dark">
    <div class="shell inner-header-row">
        <a class="brand" href="index.php"><span class="brand-name">AHMET ÇETİN</span><span class="brand-mark">#FikrimVar</span></a>
        <nav>
            <a href="hikayeler.php">Bütün hikâyeler</a>
            <?php if ($story && !$atelierActive): ?><a href="hikaye.php?slug=<?= e(rawurlencode($slug)) ?>">Düzenlenmiş hikâye</a><?php endif; ?>
            <a href="index.php">Ana sayfa</a>
            <?= public_theme_toggle() ?>
        </nav>
    </div>
</header>

<main id="atelier-main">
<?php if (!$atelierAvailable): ?>
    <section class="not-found"><div class="shell"><p class="eyebrow">404</p><h1>Atölye kaydı bulunamadı.</h1><a class="button button-rust" href="hikayeler.php">Hikâyelere dön <?= icon('arrow') ?></a></div></section>
<?php else: ?>
    <section class="atelier-hero">
        <div class="shell atelier-hero-grid">
            <div data-reveal>
                <p class="eyebrow"><?= $project['workshop_status'] === 'closed' ? 'ATÖLYE KAPANDI' : 'CANLI ATÖLYE' ?> · <?= e($project['status_label']) ?></p>
                <h1><?= e($project['title']) ?></h1>
            </div>
            <div data-reveal>
                <p class="atelier-question"><?= e($project['workshop_question'] ?: $project['question']) ?></p>
                <p><?= e($project['summary']) ?></p>
                <div class="atelier-meta">
                    <span><?= count($updates) ?> çalışma kaydı</span>
                    <span><?= $selectedCount ?> hikâyeye seçili kayıt</span>
                    <span>Kayıt her gün zorunlu değil</span>
                </div>
            </div>
        </div>
    </section>

    <?php if ($project['workshop_status'] === 'closed'): ?>
        <section class="atelier-closed-note"><div class="shell"><p class="eyebrow">ATÖLYE KAPALI</p><h2><?= e($project['closing_state'] ?: 'Bu hâliyle bitti') ?></h2><p><?= e($project['closing_note']) ?></p><?php if ($story): ?><a class="button button-rust" href="hikaye.php?slug=<?= e(rawurlencode($slug)) ?>">Hikâyeyi oku <?= icon('arrow') ?></a><?php endif; ?></div></section>
    <?php endif; ?>

    <?php if ($storyReferenceSections): ?>
        <section class="atelier-archive">
            <div class="shell atelier-archive-layout">
                <header data-reveal>
                    <p class="eyebrow">DÜZENLENMİŞ HİKÂYE REFERANSI</p>
                    <h2>Bu projenin yayındaki anlatısı ayrı durur.</h2>
                    <p>Bu bölümler ham Atölye kaydı değildir; daha önce düzenlenmiş hikâyeyi gösterir. Atölyeye yeni kayıt girildikçe yeni malzeme aşağıdaki çalışma akışında birikir.</p>
                    <?php if ($story): ?><a class="atelier-archive-jump" href="hikaye.php?slug=<?= e(rawurlencode($slug)) ?>">Hikâyeyi oku <?= icon('arrow') ?></a><?php endif; ?>
                </header>
                <div>
                    <?php foreach ($storyReferenceSections as $i => $section): ?>
                        <a class="atelier-archive-entry" href="hikaye.php?slug=<?= e(rawurlencode($slug)) ?>#hikaye"><time><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></time><strong><?= e($section['title'] ?: 'Başlıksız hikâye bölümü') ?></strong><span><?= e(atelier_story_excerpt($section)) ?></span><em><?= e($section['label'] ?: $section['type']) ?></em></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($storySourceSections): ?>
        <section class="atelier-archive atelier-archive--source">
            <div class="shell atelier-archive-layout">
                <header data-reveal>
                    <p class="eyebrow">HİKÂYEYE KAYNAK OLAN ATÖLYE KAYITLARI</p>
                    <h2>Hikâyenin dayandığı iş kayıtları kaybolmaz.</h2>
                    <p>Bu bölümler Atölye kayıtlarından seçilerek hikâyeye taşındı. Yeni çalışma kayıtları eklenirse hikâyeleştirme ekranında yeniden seçilebilir.</p>
                </header>
                <div>
                    <?php foreach ($storySourceSections as $i => $section): ?>
                        <?php
                        $sourceId = (int)($section['source_update_id'] ?? 0);
                        $source = $updatesById[$sourceId] ?? null;
                        $href = $source ? '#update-' . rawurlencode((string)$source['slug']) : 'hikaye.php?slug=' . rawurlencode($slug) . '#hikaye';
                        ?>
                        <a class="atelier-archive-entry" href="<?= e($href) ?>"><time><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></time><strong><?= e($section['title'] ?: ($source['title'] ?? 'Kaynak kayit')) ?></strong><span><?= e($source ? (string)$source['summary'] : atelier_story_excerpt($section)) ?></span><em><?= e($source ? atelier_kind_short($source) : ($section['label'] ?: $section['type'])) ?></em></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!$active): ?>
        <section class="atelier-rule"><div class="shell atelier-rule-grid" data-reveal><p class="eyebrow">ATÖLYE YENİDEN AÇILDI</p><h2>Bu dosya tekrar masada.</h2><p><?= e($story ? 'Bu projede yayında bir hikâye var; yeni atölye kayıtları eklendikçe süreç buradan takip edilecek.' : 'Bu atölye için henüz ayrı çalışma kaydı girilmedi. İlk kayıt eklendiğinde burada görünecek.') ?></p><?php if ($story && !$atelierActive): ?><a class="button button-rust" href="hikaye.php?slug=<?= e(rawurlencode($slug)) ?>">Mevcut hikâyeyi oku <?= icon('arrow') ?></a><?php endif; ?></div></section>
    <?php endif; ?>

    <?php if ($active): $stage = first_stage_media($active); ?>
        <section class="atelier-console" data-atelier-console>
            <div class="atelier-stage-wrap">
                <div class="atelier-stage-sticky">
                    <div class="atelier-live-label"><span></span> ATÖLYEDE ŞİMDİ</div>
                    <figure class="atelier-stage-media" data-atelier-stage>
                        <?php if ($stage && ($stage['media_type'] ?? '') === 'image'): ?>
                            <img src="<?= e(media_url($stage['relative_path'])) ?>" alt="<?= e($stage['alt_text'] ?? $project['title']) ?>" data-atelier-media>
                        <?php elseif ($stage && ($stage['media_type'] ?? '') === 'video'): ?>
                            <video controls preload="metadata"><source src="<?= e(media_url($stage['relative_path'])) ?>"></video>
                        <?php elseif ($project['cover'] !== ''): ?>
                            <img src="<?= e($project['cover']) ?>" alt="<?= e($project['title']) ?>" data-atelier-media>
                        <?php endif; ?>
                    </figure>
                    <div class="atelier-stage-copy">
                        <p data-atelier-day><?= e(trim((string)$active['date_label'] . ' · ' . atelier_kind_label($active), ' ·')) ?></p>
                        <h2 data-atelier-title><?= e($active['title']) ?></h2>
                        <p data-atelier-summary><?= e($active['summary']) ?></p>
                        <div class="atelier-story-seed">
                            <span data-atelier-seed-label><?= e(atelier_kind_short($active)) ?> · <?= e(atelier_story_bridge($active)['reader_label']) ?></span>
                            <p data-atelier-seed-text><?= e(atelier_story_bridge_text($active)) ?></p>
                        </div>
                        <p class="atelier-material-label">İş kanıtları</p>
                        <div data-atelier-artifacts>
                            <?php render_atelier_artifacts($active, 'atelier-artifacts--stage'); ?>
                        </div>
                        <div data-atelier-gallery>
                            <?php render_atelier_gallery($active); ?>
                        </div>
                        <div data-atelier-links>
                            <?php render_external_links($active['links']); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="atelier-log">
                <header>
                    <p class="eyebrow">ÇALIŞMA AKIŞI</p>
                    <h2>İş kayıtları hikâyeyi besleyen malzemedir.</h2>
                    <p>Her kayıt yapılan işe ait blokları taşır: saha notu, prompt, kod, çıktı, hata, medya, bağlantı ve karar. Dönüm noktası olanlar hikâye taslağına seçilir; ham kalanlar çalışma kanıtı olarak kalır.</p>
                </header>
                <?php foreach ($updates as $u): $sm = first_stage_media($u); $bridge = atelier_story_bridge($u); ?>
                    <button class="atelier-log-entry <?= $u['id'] === $active['id'] ? 'is-active' : '' ?>" id="update-<?= e($u['slug']) ?>" type="button" data-atelier-entry data-media="<?= e($sm ? media_url($sm['relative_path']) : $project['cover']) ?>" data-media-type="<?= e($sm['media_type'] ?? 'image') ?>" data-alt="<?= e($sm['alt_text'] ?? '') ?>" data-update-id="<?= e($u['slug']) ?>" data-day="<?= e(trim((string)$u['date_label'] . ' · ' . atelier_kind_label($u), ' ·')) ?>" data-title="<?= e($u['title']) ?>" data-summary="<?= e($u['summary']) ?>" data-seed-label="<?= e(atelier_kind_short($u) . ' · ' . $bridge['reader_label']) ?>" data-seed-text="<?= e(atelier_story_bridge_text($u)) ?>"<?= atelier_work_data_attrs($u) ?><?= atelier_gallery_data_attrs($u) ?><?= atelier_links_data_attrs($u) ?>>
                        <span><?= e($u['date_label']) ?><b><?= e(atelier_kind_short($u)) ?></b></span>
                        <strong><?= e($u['title']) ?></strong>
                        <small><?= e($u['summary']) ?></small>
                        <em><?= (int)$u['is_milestone'] === 1 ? 'Hikâyeye seçili' : 'Ham kayıt' ?> · <?= e($bridge['type_label']) ?></em>
                    </button>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!$atelierActive): ?>
        <section class="atelier-rule"><div class="shell atelier-rule-grid" data-reveal><p class="eyebrow">ATÖLYENİN KAPANIŞ KURALI</p><h2>Bir projenin tamamlanması şart değil.</h2><p>Hedeflediğim yere geldiğimde, öğrenmek istediğimi öğrendiğimde veya artık devam etmek istemediğimde kapanır. Ham kayıtlar kalır; seçilen dönüm noktaları düzenlenmiş hikâyeye dönüşür.</p></div></section>
    <?php endif; ?>

    <section class="story-signature story-signature--dark"><div class="shell story-signature-grid" data-reveal><p class="eyebrow">#FikrimVar</p><blockquote>Kusursuz olmak değil; denemek, yanılmak, öğrenmek ve fikri hayata geçirmek.</blockquote><nav><a href="hikayeler.php">Bütün hikâyeler <?= icon('arrow') ?></a><a href="index.php">Ana sayfa <?= icon('arrow') ?></a></nav></div></section>
<?php endif; ?>
</main>
<footer class="site-footer site-footer--dark"><div class="shell footer-inner"><p>© <?= e((string)($site['year'] ?? date('Y'))) ?> Ahmet Çetin · #FikrimVar</p><a href="index.php">Ana sayfaya dön</a></div></footer>
</body>
</html>
