<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$siteData = load_json('site.json');
$categories = load_json('categories.json');
$projects = load_json('projects.json');
by_order($categories);
by_order($projects);

$site = $siteData['site'] ?? [];
$categoryMap = [];
foreach ($categories as $category) {
    $categoryMap[(string) ($category['id'] ?? '')] = $category;
}
$statuses = [];
foreach ($projects as $project) {
    $status = trim((string) ($project['status'] ?? ''));
    if ($status !== '' && !in_array($status, $statuses, true)) $statuses[] = $status;
}
$initialCategory = trim((string) ($_GET['kategori'] ?? 'all'));
if ($initialCategory !== 'all' && !isset($categoryMap[$initialCategory])) $initialCategory = 'all';
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Ahmet Çetin'in çalışan, gelişen, yarım kalan ve arşivlenen proje, deney ve yöntem kayıtları.">
    <meta name="theme-color" content="#eee5d4">
    <title>Bütün Hikâyeler | #fikrimvar</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body class="stories-page">
<a class="skip-link" href="#stories-main">İçeriğe geç</a>
<header class="inner-header">
    <div class="shell inner-header-row">
        <a class="brand" href="index.php"><span class="brand-name">AHMET ÇETİN</span><span class="brand-mark">#fikrimvar</span></a>
        <nav aria-label="Hikâyeler sayfası menüsü"><a href="index.php#gunluk">Ahmet</a><a href="index.php#acik-dosyalar">Açık dosyalar</a><a href="index.php">Ana sayfa</a></nav>
    </div>
</header>

<main id="stories-main">
    <section class="stories-hero">
        <div class="shell stories-hero-grid">
            <div data-reveal><p class="eyebrow">ATÖLYE DEFTERİ · <?= count($projects) ?> KAYIT</p><h1>Bütün<br><em>hikâyeler.</em></h1></div>
            <div class="stories-hero-copy" data-reveal><p>Burada yalnızca bitmiş işler yok. Çalışan araçlar, değişen sistemler, görsel denemeler, yöntem notları ve yarım kalan yollar aynı kayıt defterinde duruyor.</p><p>Liste bir başarı sıralaması değil. Her kayıt, o sırada aklımı kurcalayan başka bir sorunun izi.</p></div>
        </div>
    </section>

    <section class="stories-index" data-story-index data-initial-category="<?= e($initialCategory) ?>">
        <div class="shell">
            <div class="story-filters" aria-label="Hikâyeleri filtrele">
                <div class="category-filter" role="group" aria-label="Çalışma alanı">
                    <button class="<?= $initialCategory === 'all' ? 'is-active' : '' ?>" type="button" data-story-category="all" aria-pressed="<?= $initialCategory === 'all' ? 'true' : 'false' ?>">Tümü</button>
                    <?php foreach ($categories as $category): $id = (string) ($category['id'] ?? ''); ?>
                    <button class="<?= $initialCategory === $id ? 'is-active' : '' ?>" type="button" data-story-category="<?= e($id) ?>" aria-pressed="<?= $initialCategory === $id ? 'true' : 'false' ?>"><?= e($category['title'] ?? '') ?></button>
                    <?php endforeach; ?>
                </div>
                <div class="story-filter-tools">
                    <label><span>Durum</span><select data-story-status><option value="all">Tüm durumlar</option><?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>"><?= e($status) ?></option><?php endforeach; ?></select></label>
                    <label><span>Ara</span><input type="search" placeholder="Başlık, araç veya soru" data-story-search autocomplete="off"></label>
                </div>
            </div>

            <div class="story-results-bar"><p><strong data-story-count><?= count($projects) ?></strong> kayıt</p><p>Çalışan, bekleyen ve yarım kalan aynı defterde.</p></div>

            <div class="story-index-list">
                <?php foreach ($projects as $index => $project):
                    $categoryId = (string) ($project['category'] ?? '');
                    $category = $categoryMap[$categoryId] ?? [];
                    $tools = is_array($project['tools'] ?? null) ? $project['tools'] : [];
                    $searchText = implode(' ', [(string) ($project['title'] ?? ''), (string) ($project['question'] ?? ''), (string) ($project['summary'] ?? ''), implode(' ', array_map('strval', $tools)), (string) ($category['title'] ?? '')]);
                ?>
                <article class="story-index-card" data-story-card data-category="<?= e($categoryId) ?>" data-status="<?= e($project['status'] ?? '') ?>" data-search="<?= e($searchText) ?>">
                    <a href="<?= e(story_url($project)) ?>">
                        <span class="story-index-number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <div class="story-index-copy">
                            <p><?= e($category['title'] ?? '') ?> · <?= e($project['status'] ?? '') ?> · <?= e($project['type'] ?? '') ?></p>
                            <h2><?= e($project['question'] ?? $project['title'] ?? '') ?></h2>
                            <span><?= e($project['title'] ?? '') ?></span>
                        </div>
                        <div class="story-index-tools"><?php foreach (array_slice($tools, 0, 4) as $tool): ?><span><?= e((string) $tool) ?></span><?php endforeach; ?></div>
                        <span class="story-index-arrow" aria-hidden="true"><?= icon('arrow') ?></span>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
            <p class="story-no-results" data-story-empty hidden>Bu filtrelerle eşleşen bir kayıt bulunamadı.</p>
        </div>
    </section>
</main>
<footer class="stories-footer"><div class="shell"><p>© <?= e($site['year'] ?? date('Y')) ?> Ahmet Çetin · #fikrimvar</p><a href="index.php">Ana sayfaya dön <?= icon('arrow') ?></a></div></footer>
</body>
</html>
