<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$siteData = load_site();
$site = $siteData['site'] ?? [];
$stories = load_stories();
$categories = category_map($siteData);
$statuses = [];
foreach ($stories as $story) {
    $key = (string) ($story['status'] ?? '');
    $label = status_label($story);
    if ($key !== '' && !isset($statuses[$key])) $statuses[$key] = $label;
}
$initialCategory = safe_slug((string) ($_GET['kategori'] ?? 'all')) ?: 'all';
if ($initialCategory !== 'all' && !isset($categories[$initialCategory])) $initialCategory = 'all';
$initialStatus = safe_slug((string) ($_GET['durum'] ?? 'all')) ?: 'all';
if ($initialStatus !== 'all' && !isset($statuses[$initialStatus])) $initialStatus = 'all';
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Ahmet Çetin'in çalışan, gelişen, yarım kalan ve kapanan proje, atölye ve yöntem kayıtları.">
    <meta name="theme-color" content="#eee5d4">
    <title>Bütün Hikâyeler | #FikrimVar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600&family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body class="stories-page">
<a class="skip-link" href="#stories-main">İçeriğe geç</a>
<header class="inner-header">
    <div class="shell inner-header-row">
        <a class="brand" href="index.php"><span class="brand-name">AHMET ÇETİN</span><span class="brand-mark">#FikrimVar</span></a>
        <nav aria-label="Hikâyeler sayfası menüsü"><a href="index.php#manifesto">Manifesto</a><a href="index.php#atolye">Atölye</a><a href="index.php">Ana sayfa</a></nav>
    </div>
</header>

<main id="stories-main">
    <section class="stories-hero-v6">
        <div class="shell stories-hero-v6-grid">
            <div data-reveal><p class="eyebrow">ATÖLYE DEFTERİ · <?= count($stories) ?> KAYIT</p><h1>Bütün<br><em>hikâyeler.</em></h1></div>
            <div data-reveal>
                <p>Bu sayfa bir başarı sıralaması değil. Çalışan sistemler, canlı atölyeler, yöntem notları ve yarım kalan yollar aynı kayıt defterinde duruyor.</p>
                <p>Bir hikâyeyi açtığında yalnızca sonucu değil; neden başladığını, nerede zorlandığını, neyin değiştiğini ve bugün nerede durduğunu görürsün.</p>
            </div>
        </div>
    </section>

    <section class="stories-browser" data-story-index data-initial-category="<?= e($initialCategory) ?>" data-initial-status="<?= e($initialStatus) ?>">
        <div class="shell">
            <div class="story-filter-bar">
                <div class="category-filter" role="group" aria-label="Çalışma alanı">
                    <button class="<?= $initialCategory === 'all' ? 'is-active' : '' ?>" type="button" data-story-category="all" aria-pressed="<?= $initialCategory === 'all' ? 'true' : 'false' ?>">Tümü</button>
                    <?php foreach ($categories as $id => $title): ?>
                    <button class="<?= $initialCategory === $id ? 'is-active' : '' ?>" type="button" data-story-category="<?= e($id) ?>" aria-pressed="<?= $initialCategory === $id ? 'true' : 'false' ?>"><?= e($title) ?></button>
                    <?php endforeach; ?>
                </div>
                <div class="story-filter-tools">
                    <label><span>Durum</span><select data-story-status>
                        <option value="all">Tüm durumlar</option>
                        <?php foreach ($statuses as $key => $label): ?><option value="<?= e($key) ?>" <?= $initialStatus === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                    </select></label>
                    <label><span>Ara</span><input type="search" placeholder="Başlık, araç veya soru" data-story-search autocomplete="off"></label>
                </div>
            </div>

            <div class="story-results-bar"><p><strong data-story-count><?= count($stories) ?></strong> kayıt</p><p>Yeni tarih eklenen kayıtlar öne gelir; diğerleri kendi düzen sırasını korur.</p></div>

            <div class="stories-mosaic">
                <?php foreach (array_values($stories) as $index => $story):
                    $cover = story_asset($story, (string) ($story['cover'] ?? ''));
                    $searchText = story_search_text($story);
                    $shape = ['wide','tall','compact','offset'][$index % 4];
                ?>
                <article class="mosaic-story mosaic-story--<?= e($shape) ?>" data-story-card data-category="<?= e($story['category'] ?? '') ?>" data-status="<?= e($story['status'] ?? '') ?>" data-search="<?= e($searchText) ?>">
                    <a href="<?= e(story_url($story)) ?>">
                        <?php if ($cover !== ''): ?><figure><img src="<?= e($cover) ?>" alt="<?= e($story['title'] ?? '') ?>" loading="lazy"></figure><?php endif; ?>
                        <div class="mosaic-story-copy">
                            <p><?= e($story['category_label'] ?? '') ?> · <?= e(status_label($story)) ?></p>
                            <h2><?= e($story['question'] ?? $story['title'] ?? '') ?></h2>
                            <span><?= e($story['summary'] ?? '') ?></span>
                            <small><?= e($story['type_label'] ?? '') ?> <?= icon('arrow') ?></small>
                        </div>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
            <p class="story-no-results" data-story-empty hidden>Bu filtrelerle eşleşen bir kayıt bulunamadı.</p>
        </div>
    </section>
</main>
<footer class="site-footer"><div class="shell footer-inner"><p>© <?= e($site['year'] ?? date('Y')) ?> Ahmet Çetin · #FikrimVar</p><a href="index.php">Ana sayfaya dön <?= icon('arrow') ?></a></div></footer>
</body>
</html>
