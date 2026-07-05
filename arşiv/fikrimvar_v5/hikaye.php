<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$stories = load_json('stories.json');
$projects = load_json('projects.json');
$slug = (string) filter_input(INPUT_GET, 'slug', FILTER_UNSAFE_RAW);
$story = $stories[$slug] ?? null;
$project = null;
foreach ($projects as $item) {
    if (($item['slug'] ?? '') === $slug) {
        $project = $item;
        break;
    }
}
if (!$story) {
    http_response_code(404);
}
$quickFacts = is_array($story['quick_facts'] ?? null) ? $story['quick_facts'] : [];
$versions = is_array($story['versions'] ?? null) ? $story['versions'] : [];
$questions = is_array($story['questions'] ?? null) ? $story['questions'] : [];
$today = is_array($story['today'] ?? null) ? $story['today'] : [];
$lessons = is_array($story['lessons'] ?? null) ? $story['lessons'] : [];
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#121519">
    <meta name="description" content="<?= e($story['dek'] ?? 'Çalışma hikâyesi') ?>">
    <title><?= e($story['title'] ?? 'Hikâye bulunamadı') ?> | #fikrimvar</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body class="story-page">
<a class="skip-link" href="#story-main">İçeriğe geç</a>
<header class="story-header">
    <div class="shell story-header-inner">
        <a class="brand" href="index.php"><span class="brand-name">AHMET ÇETİN</span><span class="brand-mark">#fikrimvar</span></a>
        <nav aria-label="Hikâye menüsü"><a href="hikayeler.php">Bütün hikâyeler</a><a href="index.php">Ana sayfa</a></nav>
    </div>
</header>

<main id="story-main">
<?php if (!$story): ?>
    <section class="story-not-found"><div class="shell"><h1>Hikâye henüz hazırlanmadı.</h1><p>Bu kayıt arşivde duruyor, ancak görsel hikâyesi henüz yazılmadı.</p><a href="hikayeler.php">Bütün hikâyelere dön</a></div></section>
<?php else: ?>
    <section class="story-hero" data-parallax-root>
        <div class="story-hero-grid" aria-hidden="true" data-parallax data-depth="0.05"></div>
        <div class="shell story-hero-layout">
            <div class="story-hero-copy" data-reveal>
                <p class="eyebrow"><?= e($story['project_title'] ?? '') ?> · <?= e($story['status'] ?? '') ?></p>
                <h1><?= e($story['title'] ?? '') ?></h1>
                <p class="story-dek"><?= e($story['dek'] ?? '') ?></p>
                <div class="reading-path" aria-label="Hikâye okuma seçenekleri">
                    <a href="#hizli-bakis"><strong>30 sn</strong><span>Başlıklar ve görseller</span></a>
                    <a href="#hikaye"><strong><?= e($story['reading_time'] ?? '3 dk') ?></strong><span>Hikâyenin tamamı</span></a>
                    <a href="#teknik"><strong>Teknik</strong><span>Açılır notlar</span></a>
                </div>
            </div>
            <figure class="story-hero-visual" data-reveal>
                <img src="assets/img/story/webbordro-opening.svg" alt="WebBordro hikâyesini temsil eden bordro ekranı ve kod kompozisyonu">
            </figure>
        </div>
    </section>

    <section class="quick-facts" id="hizli-bakis">
        <div class="shell quick-facts-grid">
            <?php foreach ($quickFacts as $fact): ?>
            <div data-reveal><small><?= e($fact['label'] ?? '') ?></small><strong><?= e($fact['value'] ?? '') ?></strong></div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="story-opening" id="hikaye">
        <div class="shell story-opening-grid">
            <div class="story-section-title" data-reveal>
                <p class="eyebrow"><?= e($story['opening']['label'] ?? '') ?></p>
                <h2><?= e($story['opening']['title'] ?? '') ?></h2>
            </div>
            <div class="story-prose" data-reveal>
                <?php foreach (($story['opening']['paragraphs'] ?? []) as $paragraph): ?><p><?= e((string) $paragraph) ?></p><?php endforeach; ?>
            </div>
            <blockquote class="story-quote" data-reveal>“Gerçekten kullanılan, hata kabul etmeyen bir programı kendim yapabilir miydim?”</blockquote>
        </div>
    </section>

    <section class="evolution-spread">
        <div class="shell">
            <div class="evolution-heading" data-reveal><p class="eyebrow">GELİŞİM HARİTASI</p><h2>Tek bir doğru yol yoktu.<br>Her sürüm başka bir şeyi öğretti.</h2></div>
            <figure class="evolution-visual" data-reveal><img src="assets/img/story/webbordro-evolution.svg" alt="WebBordro'nun dört farklı sürüme dönüşüm haritası"></figure>
            <div class="version-stories">
                <?php foreach ($versions as $version): ?>
                <article data-reveal>
                    <span><?= e($version['step'] ?? '') ?></span>
                    <p><?= e($version['role'] ?? '') ?></p>
                    <h3><?= e($version['name'] ?? '') ?></h3>
                    <div><?= e($version['text'] ?? '') ?></div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="turning-spread">
        <div class="shell turning-grid">
            <div class="turning-title" data-reveal>
                <p class="eyebrow"><?= e($story['turning_point']['label'] ?? '') ?></p>
                <h2><?= e($story['turning_point']['title'] ?? '') ?></h2>
                <p><?= e($story['turning_point']['text'] ?? '') ?></p>
            </div>
            <div class="worked-changed" data-reveal>
                <div><small>İŞE YARAYANLAR</small><?php foreach (($story['turning_point']['worked'] ?? []) as $item): ?><p><?= e((string) $item) ?></p><?php endforeach; ?></div>
                <div><small>DEĞİŞENLER</small><?php foreach (($story['turning_point']['changed'] ?? []) as $item): ?><p><?= e((string) $item) ?></p><?php endforeach; ?></div>
            </div>
        </div>
    </section>

    <section class="question-spread" id="teknik">
        <div class="shell question-layout">
            <div class="question-intro" data-reveal><p class="eyebrow">KODDAN DAHA ZOR OLANLAR</p><h2>Hikâyeyi özellikler değil, değişen kararlar taşıdı.</h2><p>Teknik ayrıntılar ana metni boğmasın diye soruların içinde açılıyor. İsteyen hızlıca geçer, isteyen derine iner.</p></div>
            <div class="question-list">
                <?php foreach ($questions as $index => $question): ?>
                <details data-reveal<?= $index === 0 ? ' open' : '' ?>>
                    <summary><span>0<?= $index + 1 ?></span><?= e($question['question'] ?? '') ?></summary>
                    <p><?= e($question['answer'] ?? '') ?></p>
                </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="ai-spread">
        <div class="shell ai-grid">
            <div class="ai-title" data-reveal><p class="eyebrow">YZ VE BEN</p><h2><?= e($story['ai_role']['title'] ?? '') ?></h2><p><?= e($story['ai_role']['note'] ?? '') ?></p></div>
            <div class="ai-columns" data-reveal>
                <div><small>YAPAY ZEKÂ</small><?php foreach (($story['ai_role']['ai'] ?? []) as $item): ?><p><?= e((string) $item) ?></p><?php endforeach; ?></div>
                <div><small>AHMET</small><?php foreach (($story['ai_role']['ahmet'] ?? []) as $item): ?><p><?= e((string) $item) ?></p><?php endforeach; ?></div>
            </div>
        </div>
    </section>

    <section class="today-spread">
        <div class="shell">
            <div class="today-heading" data-reveal><p class="eyebrow">BUGÜN NEREDE?</p><h2>Dört sürüm, tek bir “en iyi” cevap değil.</h2></div>
            <div class="today-grid">
                <?php foreach ($today as $item): ?>
                <article data-reveal><small><?= e($item['state'] ?? '') ?></small><h3><?= e($item['name'] ?? '') ?></h3><p><?= e($item['text'] ?? '') ?></p></article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="lessons-spread">
        <div class="shell lessons-grid">
            <div data-reveal><p class="eyebrow">BENDE KALANLAR</p><h2>Bu hikâyeyi okumaya değecek şey, programın özellikleri değil.</h2></div>
            <ol data-reveal><?php foreach ($lessons as $lesson): ?><li><?= e((string) $lesson) ?></li><?php endforeach; ?></ol>
        </div>
    </section>

    <nav class="story-end-nav shell" aria-label="Hikâye sonu bağlantıları">
        <a href="hikayeler.php"><small>ATÖLYE DEFTERİ</small><strong>Diğer hikâyeleri aç</strong></a>
        <?php if ($project && !empty($project['url'])): ?><a href="<?= e($project['url']) ?>" target="_blank" rel="noopener noreferrer"><small>DIŞ BAĞLANTI</small><strong>Çalışan projeyi aç</strong></a><?php endif; ?>
        <a href="index.php"><small>ANA SAYFA</small><strong>#fikrimvar’a dön</strong></a>
    </nav>
<?php endif; ?>
</main>
</body>
</html>
