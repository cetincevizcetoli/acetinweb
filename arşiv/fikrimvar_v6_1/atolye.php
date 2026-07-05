<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$slug = (string) filter_input(INPUT_GET, 'slug', FILTER_UNSAFE_RAW);
$story = load_story($slug);
if (!$story || ($story['kind'] ?? '') !== 'atelier') {
    http_response_code(404);
}
$siteData = load_site();
$site = $siteData['site'] ?? [];
$updates = $story ? load_updates($story) : [];
$activeIndex = max(0, count($updates) - 1);
$active = $updates[$activeIndex] ?? null;
$activeMedia = ($story && $active) ? story_asset($story, (string) ($active['media']['src'] ?? $story['cover'] ?? '')) : '';
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d1014">
    <meta name="description" content="<?= e($story['summary'] ?? 'Canlı atölye günlüğü') ?>">
    <title><?= e($story['title'] ?? 'Atölye') ?> | #FikrimVar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600&family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body class="atelier-page">
<a class="skip-link" href="#atelier-main">İçeriğe geç</a>
<header class="inner-header inner-header--dark">
    <div class="shell inner-header-row">
        <a class="brand" href="index.php"><span class="brand-name">AHMET ÇETİN</span><span class="brand-mark">#FikrimVar</span></a>
        <nav aria-label="Atölye menüsü"><a href="hikayeler.php">Bütün hikâyeler</a><a href="index.php#atolye">Ana sayfadaki atölye</a><a href="index.php">Ana sayfa</a></nav>
    </div>
</header>

<main id="atelier-main">
<?php if (!$story): ?>
    <section class="not-found"><div class="shell"><p class="eyebrow">404</p><h1>Atölye kaydı bulunamadı.</h1><a class="button button-dark" href="hikayeler.php">Bütün hikâyelere dön <?= icon('arrow') ?></a></div></section>
<?php else: ?>
    <section class="atelier-hero">
        <div class="shell atelier-hero-grid">
            <div data-reveal>
                <p class="eyebrow">CANLI ATÖLYE · <?= e(status_label($story)) ?></p>
                <h1><?= e($story['title'] ?? '') ?></h1>
            </div>
            <div data-reveal>
                <p class="atelier-question"><?= e($story['workshop_question'] ?? $story['question'] ?? '') ?></p>
                <p><?= e($story['summary'] ?? '') ?></p>
                <div class="atelier-meta"><span><?= count($updates) ?> günlük kayıt</span><span>Sonuç zorunlu değil</span><span>Ahmet “bitti” dediğinde kapanır</span></div>
            </div>
        </div>
    </section>

    <?php if ($active): ?>
    <section class="atelier-console" data-atelier-console>
        <div class="atelier-stage-wrap">
            <div class="atelier-stage-sticky">
                <div class="atelier-live-label"><span></span> ATÖLYEDE ŞİMDİ</div>
                <figure class="atelier-stage-media">
                    <img src="<?= e($activeMedia) ?>" alt="<?= e($active['media']['alt'] ?? $story['title'] ?? '') ?>" data-atelier-media>
                </figure>
                <div class="atelier-stage-copy">
                    <p data-atelier-day><?= e($active['day'] ?? '') ?></p>
                    <h2 data-atelier-title><?= e($active['title'] ?? '') ?></h2>
                    <p data-atelier-summary><?= e($active['summary'] ?? '') ?></p>
                    <dl>
                        <div><dt>Denediğim</dt><dd data-atelier-tried><?= e($active['tried'] ?? '') ?></dd></div>
                        <div><dt>Çalışmayan</dt><dd data-atelier-failed><?= e($active['failed'] ?? '') ?></dd></div>
                        <div><dt>Kararım</dt><dd data-atelier-decision><?= e($active['decision'] ?? '') ?></dd></div>
                        <div><dt>Sıradaki</dt><dd data-atelier-next><?= e($active['next'] ?? '') ?></dd></div>
                    </dl>
                </div>
            </div>
        </div>

        <div class="atelier-log" aria-label="Atölye günlüğü">
            <header>
                <p class="eyebrow">GÜN GÜN KAYIT</p>
                <h2>Sonuçtan önce kararların izi.</h2>
                <p>Her kayıt kısa tutuluyor: ne denendi, ne olmadı, hangi karar değişti ve sırada ne var?</p>
            </header>
            <?php foreach ($updates as $index => $update):
                $media = story_asset($story, (string) ($update['media']['src'] ?? $story['cover'] ?? ''));
            ?>
            <button class="atelier-log-entry <?= $index === $activeIndex ? 'is-active' : '' ?>" type="button"
                data-atelier-entry
                data-media="<?= e($media) ?>"
                data-alt="<?= e($update['media']['alt'] ?? '') ?>"
                data-day="<?= e($update['day'] ?? '') ?>"
                data-title="<?= e($update['title'] ?? '') ?>"
                data-summary="<?= e($update['summary'] ?? '') ?>"
                data-tried="<?= e($update['tried'] ?? '') ?>"
                data-failed="<?= e($update['failed'] ?? '') ?>"
                data-decision="<?= e($update['decision'] ?? '') ?>"
                data-next="<?= e($update['next'] ?? '') ?>">
                <span><?= e($update['day'] ?? '') ?></span>
                <strong><?= e($update['title'] ?? '') ?></strong>
                <small><?= e($update['summary'] ?? '') ?></small>
            </button>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="atelier-rule">
        <div class="shell atelier-rule-grid" data-reveal>
            <p class="eyebrow">ATÖLYENİN KAPANIŞ KURALI</p>
            <h2>Bir projenin tamamlanması şart değil.</h2>
            <p>Bu günlük, hedeflediğim yere geldiğimde, öğrenmek istediğim şeyi öğrendiğimde veya artık devam etmek istemediğimde kapanır. “Bu hâliyle bitti” de bir sonuçtur; “yarım bıraktım” da.</p>
        </div>
    </section>

    <section class="story-signature story-signature--dark">
        <div class="shell story-signature-grid" data-reveal>
            <p class="eyebrow">#FikrimVar</p>
            <blockquote>Kusursuz olmak değil; denemek, yanılmak, öğrenmek ve fikri hayata geçirmek.</blockquote>
            <nav><a href="hikayeler.php">Bütün hikâyeler <?= icon('arrow') ?></a><a href="index.php">Ana sayfa <?= icon('arrow') ?></a></nav>
        </div>
    </section>
<?php endif; ?>
</main>
<footer class="site-footer site-footer--dark"><div class="shell footer-inner"><p>© <?= e($site['year'] ?? date('Y')) ?> Ahmet Çetin · #FikrimVar</p><a href="index.php">Ana sayfaya dön</a></div></footer>
</body>
</html>
