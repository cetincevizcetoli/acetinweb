<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

function story_reading_time(array $sections): int
{
    $text = '';
    foreach ($sections as $section) {
        $text .= ($section['body_text'] ?? '') . ' ' . ($section['intro_text'] ?? '') . ' ' . ($section['quote_text'] ?? '') . ' ' . ($section['note_text'] ?? '') . ' ';
    }
    $wordCount = str_word_count(strip_tags($text));
    return (int)ceil($wordCount / 200) ?: 1;
}

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
// Redirect was removed. If a user (or admin) can read the story, they should be able to view it.
if (!$project || !$story || !VisibilityService::storyDetailReadable($project, $story)) {
    http_response_code(404);
}

$site = setting('site', []);
$sections = $story ? story_sections((int)$story['id']) : [];
$parts = $story ? story_parts((int)$story['id']) : [];
$readingMode = story_reading_mode($sections, $parts);
$partRanges = story_part_ranges($parts, $sections);
$hasWorkshopUpdates = $project ? project_has_updates((int)$project['id']) : false;
$hasWorkshopPage = $project && ($hasWorkshopUpdates || in_array($project['workshop_status'], ['open', 'paused', 'closed'], true));
$workshopLinkLabel = '';
if ($project && $hasWorkshopPage) {
    $workshopLinkLabel = in_array($project['workshop_status'], ['open', 'paused'], true)
        ? 'Canlı Atölye'
        : ($project['workshop_status'] === 'closed' ? 'Atölye Arşivi' : 'Atölye Kayıtları');
}
$links = $project ? owner_links('project', (int)$project['id']) : [];
$atelierCount = $project ? count(project_updates((int)$project['id'])) : 0;
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101319">
    <meta name="description" content="<?= e($story['summary'] ?? 'Çalışma hikâyesi') ?>">
    <title><?= e($story['question'] ?? $project['title'] ?? 'Hikâye') ?> | #FikrimVar</title>
    <meta property="og:title" content="<?= e($story['question'] ?? $project['title'] ?? 'Hikâye') ?> | #FikrimVar">
    <meta property="og:description" content="<?= e($story['summary'] ?? 'Çalışma hikâyesi') ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="https://www.acetin.com.tr/hikaye.php?slug=<?= e(rawurlencode($slug)) ?>">
    <meta property="og:image" content="<?= e(!empty($story['cover']) ? $story['cover'] : (!empty($project['cover']) ? $project['cover'] : asset_url('assets/img/hero/hero-core.png'))) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="https://www.acetin.com.tr/hikaye.php?slug=<?= e(rawurlencode($slug)) ?>">
    <?= public_theme_boot_script() ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600&family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/story.css')) ?>">
    <script src="<?= e(asset_url('assets/js/app.js')) ?>" defer></script>
</head>
<body class="story-page">
<a class="skip-link" href="#story-main">İçeriğe geç</a>
<header class="inner-header">
    <div class="shell inner-header-row">
        <a class="brand" href="index.php"><span class="brand-name">AHMET ÇETİN</span><span class="brand-mark">#FikrimVar</span></a>
        <nav>
            <a href="hikayeler.php">Bütün hikâyeler</a>
            <?php if ($workshopLinkLabel !== ''): ?>
                <a href="atolye.php?slug=<?= e(rawurlencode($slug)) ?>"><?= e($workshopLinkLabel) ?></a>
            <?php endif; ?>
            <a href="index.php">Ana sayfa</a>
            <?= public_theme_toggle() ?>
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
                    <?php
                        $calcTime = story_reading_time($sections);
                    ?>
                    <span><?= e((string)$calcTime) ?> dk okuma (Otomatik)</span>
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

    <section class="living-project-doors shell" id="bugunku-durum" data-reveal>
        <div class="living-status-head">
            <p class="eyebrow">Bugünkü Durum</p>
            <?php 
                $vStr = '';
                if (!empty($project['version_major']) || !empty($project['version_minor']) || !empty($project['version_patch'])) {
                    $vStr = ' (v' . (int)$project['version_major'] . '.' . (int)$project['version_minor'] . '.' . (int)$project['version_patch'] . ')';
                }
            ?>
            <?php 
                $statusTitle = trim((string)($story['status_title'] ?? ''));
                if ($statusTitle === '') {
                    $statusTitle = 'Hikâye burada bitiyor ama proje yaşıyor.';
                }
            ?>
            <h2><?= e($statusTitle) ?><?= $vStr ?></h2>
            
            <?php if (!empty($story['status_note'])): ?>
                <div class="story-block-copy" style="margin-top: 24px;">
                    <?php
                        $noteText = trim((string)$story['status_note']);
                        // Very simple regex-based parser for basic markdown needs
                        $noteText = htmlspecialchars($noteText, ENT_QUOTES, 'UTF-8');
                        // Bold
                        $noteText = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $noteText);
                        // Italic
                        $noteText = preg_replace('/\_(.*?)\_/', '<em>$1</em>', $noteText);
                        // Images: ![alt](url)
                        $noteText = preg_replace('/\!\[(.*?)\]\((.*?)\)/', '<img src="$2" alt="$1" style="max-width:100%; height:auto; margin:16px 0; border-radius:8px;">', $noteText);
                        // Links: [text](url)
                        $noteText = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" style="color:var(--c-accent); text-decoration:underline;">$1</a>', $noteText);
                        // YouTube: [youtube](url)
                        $noteText = preg_replace('/\[youtube\]\((.*?)\)/', '<div style="position:relative; padding-bottom:56.25%; height:0; margin:16px 0;"><iframe src="$1" style="position:absolute; top:0; left:0; width:100%; height:100%; border:none;" allowfullscreen></iframe></div>', $noteText);
                        
                        // Render paragraphs
                        foreach (preg_split('/\R{2,}/u', $noteText) ?: [] as $p) {
                            $p = trim($p);
                            if ($p !== '') echo '<p>' . $p . '</p>';
                        }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($project['last_activity_at'])): ?>
                <p style="margin-top: 16px;">Son Güncelleme: <time><?= date('d M Y', strtotime($project['last_activity_at'])) ?></time></p>
            <?php endif; ?>
        </div>
        
        <div class="doors-grid">
            <?php if ($hasWorkshopPage): ?>
                <a href="atolye.php?slug=<?= e(rawurlencode($slug)) ?>" class="door-card door-atelier">
                    <span class="door-icon">🚧</span>
                    <div class="door-content">
                        <strong>Çalışmaya Devam Et</strong>
                        <small>Atölye · <?= $atelierCount ?> kayıt</small>
                    </div>
                </a>
            <?php endif; ?>
            
            <?php foreach ($links as $link): 
                $icon = match($link['link_type']) {
                    'github' => '💻',
                    'demo', 'website' => '▶️',
                    'report' => '🤖',
                    'figma' => '🎨',
                    'video', 'youtube', 'vimeo' => '🎥',
                    'download' => '📥',
                    default => '📄'
                };
            ?>
                <a href="<?= e($link['url']) ?>" class="door-card door-<?= e($link['link_type']) ?>" target="_blank" rel="noopener">
                    <span class="door-icon"><?= $icon ?></span>
                    <div class="door-content">
                        <strong><?= e($link['title'] ?: 'Bağlantıya Git') ?></strong>
                        <small><?= e(ucfirst($link['link_type'])) ?></small>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="story-signature">
        <div class="shell story-signature-grid" data-reveal>
            <p class="eyebrow">Proje Notu</p>
            <?php $closingNote = trim((string)($project['closing_note'] ?? '')); ?>
            <blockquote><?= e($closingNote !== '' ? $closingNote : 'Bir fikri hayata geçirme serüveni.') ?></blockquote>
            <nav>
                <a href="hikayeler.php">Bütün hikâyeler <?= icon('arrow') ?></a>
                <?php if ($workshopLinkLabel !== ''): ?>
                    <a href="atolye.php?slug=<?= e(rawurlencode($slug)) ?>"><?= e($workshopLinkLabel) ?> <?= icon('arrow') ?></a>
                <?php endif; ?>
                <a href="index.php">Ana sayfa <?= icon('arrow') ?></a>
            </nav>
        </div>
    </section>
<?php endif; ?>
</main>
<footer class="site-footer">
    <div class="shell footer-inner">
        <p>© <?= e((string)($site['year'] ?? date('Y'))) ?> Ahmet Çetin · #FikrimVar<span class="ai-credit"> · Co-created with <a href="https://gemini.google.com/" target="_blank" rel="noopener noreferrer">Gemini</a> &amp; <a href="https://chatgpt.com/" target="_blank" rel="noopener noreferrer">ChatGPT</a></span></p>
        <nav>
            <a href="hikayeler.php">Bütün hikâyeler</a>
            <a href="index.php">Ana sayfaya dön</a>
            <a href="#hikaye-main">Başa dön ↑</a>
        </nav>
    </div>
</footer>
</body>
</html>
