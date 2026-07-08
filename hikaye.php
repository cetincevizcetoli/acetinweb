<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

function story_reading_mode(array $sections, array $parts): string
{
    $count = count($sections);
    if ($parts !== []) return 'parts';
    if ($count >= 6) return 'contents';
    return 'simple';
}

function story_section_anchor(array $section, int $index): string
{
    return 'bolum-' . str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT);
}

function story_part_anchor(array $part): string
{
    $anchor = trim((string)($part['anchor'] ?? ''));
    if ($anchor !== '') return $anchor;
    return 'etap-' . (int)($part['id'] ?? 0);
}

function story_part_ranges(array $parts, array $sections): array
{
    $ranges = [];
    foreach ($parts as $part) {
        $indexes = [];
        foreach ($sections as $i => $section) {
            if ((int)($section['part_id'] ?? 0) === (int)$part['id']) $indexes[] = $i + 1;
        }
        if ($indexes) {
            $ranges[(int)$part['id']] = 'Bölüm ' . str_pad((string)min($indexes), 2, '0', STR_PAD_LEFT) . '-' . str_pad((string)max($indexes), 2, '0', STR_PAD_LEFT);
        } else {
            $ranges[(int)$part['id']] = 'Henüz bölüm yok';
        }
    }
    return $ranges;
}

function render_story_process_map(array $parts, array $sections): void
{
    if ($parts === []) return;
    $ranges = story_part_ranges($parts, $sections);
    ?>
    <section class="process-map shell" id="surec-haritasi" data-reveal>
        <div class="process-map-head">
            <p class="eyebrow">Süreç haritası</p>
            <h2>Bu yazı düzenlenmiş bir süreç hikâyesi.</h2>
            <p>Bu sayfa ham günlük değil; seçilmiş bölümlerden kurulmuş hikâye akışı. Ham Atölye Günlüğü ayrı bir kayıt akışı olarak hazırlanacak.</p>
        </div>
        <nav class="process-map-links" aria-label="Etaplara hızlı geçiş">
            <?php foreach ($parts as $part): ?>
                <a href="#<?= e(story_part_anchor($part)) ?>">
                    <small><?= e($part['subtitle'] ?: 'Etap') ?></small>
                    <strong><?= e($part['title']) ?></strong>
                    <span><?= e($ranges[(int)$part['id']] ?? '') ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="process-map-cards">
            <?php foreach ($parts as $part): ?>
                <article>
                    <span><?= e($part['subtitle'] ?: 'Etap') ?></span>
                    <h3><?= e($part['title']) ?></h3>
                    <p><?= e($part['description']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function render_story_contents(array $sections): void
{
    if (count($sections) < 6) return;
    ?>
    <section class="process-map process-map--contents shell" id="surec-haritasi" data-reveal>
        <div class="process-map-head">
            <p class="eyebrow">İçindekiler</p>
            <h2>Bu hikâyede <?= count($sections) ?> bölüm var.</h2>
            <p>Bu sayfa düzenlenmiş hikâyeyi gösterir. Bölümler arasında hızlı geçmek için kısa listeyi kullanabilirsin.</p>
        </div>
        <nav class="process-map-links process-map-links--contents" aria-label="Bölümlere hızlı geçiş">
            <?php foreach ($sections as $i => $section): ?>
                <a href="#<?= e(story_section_anchor($section, $i)) ?>">
                    <small>Bölüm <?= e(str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT)) ?></small>
                    <strong><?= e($section['title'] ?: $section['label'] ?: 'Başlıksız bölüm') ?></strong>
                    <span><?= e(story_section_kind_label($section)) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </section>
    <?php
}

function render_story_stage_marker(array $part, string $range): void
{
    ?>
    <section class="story-stage-marker" id="<?= e(story_part_anchor($part)) ?>" data-reveal>
        <p class="eyebrow"><?= e($part['subtitle'] ?: 'Etap') ?></p>
        <h2><?= e($part['title']) ?></h2>
        <p><?= e($part['description']) ?></p>
        <span><?= e($range) ?></span>
    </section>
    <?php
}

$slug = safe_slug((string)($_GET['slug'] ?? ''));
$project = project_by_slug($slug);
$story = $project ? story_by_project((int)$project['id']) : null;
if (!$project || !$story || !VisibilityService::storyDetailReadable($project, $story)) {
    http_response_code(404);
}

$site = setting('site', []);
$sections = $story ? story_sections((int)$story['id']) : [];
$parts = $story ? story_parts((int)$story['id']) : [];
$readingMode = story_reading_mode($sections, $parts);
$partRanges = story_part_ranges($parts, $sections);
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101319">
    <meta name="description" content="<?= e($story['summary'] ?? 'Çalışma hikâyesi') ?>">
    <title><?= e($story['question'] ?? $project['title'] ?? 'Hikâye') ?> | #FikrimVar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600&family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body class="story-page">
<a class="skip-link" href="#story-main">İçeriğe geç</a>
<header class="inner-header">
    <div class="shell inner-header-row">
        <a class="brand" href="index.php"><span class="brand-name">AHMET ÇETİN</span><span class="brand-mark">#FikrimVar</span></a>
        <nav>
            <a href="hikayeler.php">Bütün hikâyeler</a>
            <?php if ($project && in_array($project['workshop_status'], ['open', 'paused'], true)): ?>
                <a href="atolye.php?slug=<?= e(rawurlencode($slug)) ?>">Atölye</a>
            <?php endif; ?>
            <a href="index.php">Ana sayfa</a>
        </nav>
    </div>
</header>
<main id="story-main">
<?php if (!$project || !$story): ?>
    <section class="not-found">
        <div class="shell">
            <p class="eyebrow">404</p>
            <h1>Bu hikâye henüz yayımlanmadı.</h1>
            <a class="button button-dark" href="hikayeler.php">Bütün hikâyelere dön <?= icon('arrow') ?></a>
        </div>
    </section>
<?php else: ?>
    <section class="story-hero-v6" data-parallax-root>
        <div class="story-hero-grid" aria-hidden="true" data-parallax data-depth="0.04"></div>
        <div class="shell story-hero-v6-layout">
            <div class="story-hero-v6-copy" data-reveal>
                <p class="eyebrow"><?= e($project['category_label']) ?> · <?= e($project['status_label']) ?> · <?= e($project['type_label']) ?></p>
                <h1><?= e($story['question'] ?: $story['title']) ?></h1>
                <p class="story-dek"><?= e($story['summary']) ?></p>
                <div class="story-meta-line">
                    <span><?= e($story['title']) ?></span>
                    <?php if ($story['reading_time'] !== ''): ?><span><?= e($story['reading_time']) ?></span><?php endif; ?>
                    <span><?= count($sections) ?> bölüm</span>
                </div>
                <div class="story-paths">
                    <a href="<?= $readingMode === 'simple' ? '#hikaye' : '#surec-haritasi' ?>"><strong>Hızlı bakış</strong><small><?= $readingMode === 'parts' ? 'Süreç haritası' : ($readingMode === 'contents' ? 'İçindekiler' : 'Başlıklar ve görseller') ?></small></a>
                    <a href="#hikaye"><strong>Hikâyeyi oku</strong><small>Kısa bölümler</small></a>
                    <a href="#teknik"><strong>Derine in</strong><small>Açılır teknik notlar</small></a>
                </div>
            </div>
            <?php if ($project['cover'] !== ''): ?>
                <figure class="story-hero-v6-media" data-reveal>
                    <img src="<?= e($project['cover']) ?>" alt="<?= e($project['title']) ?>" loading="eager">
                </figure>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($readingMode === 'parts') render_story_process_map($parts, $sections); ?>
    <?php if ($readingMode === 'contents') render_story_contents($sections); ?>

    <div class="story-composition shell" id="hikaye">
        <?php foreach ($sections as $i => $section): ?>
            <?php
            $partId = (int)($section['part_id'] ?? 0);
            $part = null;
            foreach ($parts as $candidate) {
                if ((int)$candidate['id'] === $partId) { $part = $candidate; break; }
            }
            $previousPartId = $i > 0 ? (int)($sections[$i - 1]['part_id'] ?? 0) : 0;
            if ($part && $partId !== $previousPartId) render_story_stage_marker($part, $partRanges[$partId] ?? '');
            ?>
            <div id="<?= e(story_section_anchor($section, $i)) ?>" class="story-section-anchor"></div>
            <?php render_story_section($section, $i, count($sections), [
                'stage_label' => $part ? (($part['subtitle'] ?: 'Etap') . ' · ' . $part['title']) : '',
            ]); ?>
        <?php endforeach; ?>
    </div>
    <section class="story-signature">
        <div class="shell story-signature-grid" data-reveal>
            <p class="eyebrow">#FikrimVar</p>
            <blockquote>Kusursuz olmak değil; denemek, yanılmak, öğrenmek ve fikri hayata geçirmek.</blockquote>
            <nav>
                <a href="hikayeler.php">Bütün hikâyeler <?= icon('arrow') ?></a>
                <?php if (in_array($project['workshop_status'], ['open', 'paused'], true)): ?>
                    <a href="atolye.php?slug=<?= e(rawurlencode($slug)) ?>">Canlı atölye <?= icon('arrow') ?></a>
                <?php endif; ?>
                <a href="index.php">Ana sayfa <?= icon('arrow') ?></a>
            </nav>
        </div>
    </section>
<?php endif; ?>
</main>
<footer class="site-footer">
    <div class="shell footer-inner">
        <p>© <?= e((string)($site['year'] ?? date('Y'))) ?> Ahmet Çetin · #FikrimVar</p>
        <a href="hikayeler.php">Hikâyelere dön</a>
    </div>
</footer>
</body>
</html>
