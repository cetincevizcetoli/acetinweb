<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/blocks.php';

$slug = (string) filter_input(INPUT_GET, 'slug', FILTER_UNSAFE_RAW);
$story = load_story($slug);
if ($story && ($story['kind'] ?? 'story') === 'atelier') {
    header('Location: atolye.php?slug=' . rawurlencode((string) ($story['slug'] ?? $slug)));
    exit;
}
if (!$story) {
    http_response_code(404);
}
$siteData = load_site();
$site = $siteData['site'] ?? [];
$blocks = is_array($story['blocks'] ?? null) ? $story['blocks'] : [];
$cover = $story ? story_asset($story, (string) ($story['cover'] ?? '')) : '';
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101319">
    <meta name="description" content="<?= e($story['summary'] ?? 'Çalışma hikâyesi') ?>">
    <title><?= e($story['question'] ?? $story['title'] ?? 'Hikâye bulunamadı') ?> | #FikrimVar</title>
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
        <nav aria-label="Hikâye menüsü"><a href="hikayeler.php">Bütün hikâyeler</a><a href="index.php#atolye">Atölye</a><a href="index.php">Ana sayfa</a></nav>
    </div>
</header>

<main id="story-main">
<?php if (!$story): ?>
    <section class="not-found"><div class="shell"><p class="eyebrow">404</p><h1>Bu hikâye henüz yok.</h1><p>Kayıt taşınmış, adı değişmiş veya henüz yazılmamış olabilir.</p><a class="button button-dark" href="hikayeler.php">Bütün hikâyelere dön <?= icon('arrow') ?></a></div></section>
<?php else: ?>
    <section class="story-hero-v6" data-parallax-root>
        <div class="story-hero-grid" aria-hidden="true" data-parallax data-depth="0.04"></div>
        <div class="shell story-hero-v6-layout">
            <div class="story-hero-v6-copy" data-reveal>
                <p class="eyebrow"><?= e($story['category_label'] ?? '') ?> · <?= e(status_label($story)) ?> · <?= e($story['type_label'] ?? '') ?></p>
                <h1><?= e($story['question'] ?? $story['title'] ?? '') ?></h1>
                <p class="story-dek"><?= e($story['summary'] ?? '') ?></p>
                <div class="story-meta-line">
                    <span><?= e($story['title'] ?? '') ?></span>
                    <?php if (!empty($story['reading_time'])): ?><span><?= e($story['reading_time']) ?></span><?php endif; ?>
                    <span><?= count($blocks) ?> bölüm</span>
                </div>
                <div class="story-paths">
                    <a href="#hikaye"><strong>Hızlı bakış</strong><small>Başlıklar ve görseller</small></a>
                    <a href="#hikaye"><strong>Hikâyeyi oku</strong><small>Kısa bölümler</small></a>
                    <a href="#teknik"><strong>Derine in</strong><small>Açılır teknik notlar</small></a>
                </div>
            </div>
            <?php if ($cover !== ''): ?>
            <figure class="story-hero-v6-media" data-reveal><img src="<?= e($cover) ?>" alt="<?= e($story['title'] ?? '') ?>" loading="eager"></figure>
            <?php endif; ?>
        </div>
    </section>

    <div class="story-composition shell" id="hikaye">
        <?php foreach ($blocks as $index => $block): ?>
            <?php render_story_block(is_array($block) ? $block : [], $story, $index); ?>
        <?php endforeach; ?>
    </div>

    <section class="story-signature">
        <div class="shell story-signature-grid" data-reveal>
            <p class="eyebrow">#FikrimVar</p>
            <blockquote>Kusursuz olmak değil; denemek, yanılmak, öğrenmek ve fikri hayata geçirmek.</blockquote>
            <nav aria-label="Hikâye sonu">
                <a href="hikayeler.php">Bütün hikâyeler <?= icon('arrow') ?></a>
                <a href="index.php#atolye">Canlı atölye <?= icon('arrow') ?></a>
                <a href="index.php">Ana sayfa <?= icon('arrow') ?></a>
            </nav>
        </div>
    </section>
<?php endif; ?>
</main>
<footer class="site-footer"><div class="shell footer-inner"><p>© <?= e($site['year'] ?? date('Y')) ?> Ahmet Çetin · #FikrimVar</p><a href="hikayeler.php">Atölye defterine dön</a></div></footer>
</body>
</html>
