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
    if ($status !== '' && !in_array($status, $statuses, true)) {
        $statuses[] = $status;
    }
}
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
<body class="inner-page stories-page">
<a class="skip-link" href="#stories-main">İçeriğe geç</a>

<header class="inner-header stories-header">
    <div class="container">
        <a class="brand" href="index.php" aria-label="Ahmet Çetin ana sayfa">
            <strong>AHMET ÇETİN</strong><span>#fikrimvar</span>
        </a>
        <nav aria-label="Hikâyeler sayfası menüsü">
            <a href="index.php#masada">Masadakiler</a>
            <a href="index.php#baslangic">Başlangıç</a>
            <a href="index.php">Ana sayfa</a>
        </nav>
    </div>
</header>

<main id="stories-main" class="stories-main">
    <section class="stories-hero">
        <div class="container stories-hero-grid">
            <div>
                <p class="eyebrow">ATÖLYE ARŞİVİ · <?= count($projects) ?> KAYIT</p>
                <h1>Bütün<br><em>hikâyeler.</em></h1>
            </div>
            <div class="stories-hero-copy">
                <p>Burada yalnızca bitmiş işler yok. Çalışan araçlar, gelişen sistemler, kısa denemeler, yöntem notları, bekleyen fikirler ve yarım kalan yollar aynı arşivde duruyor.</p>
                <p>Liste bir başarı sıralaması değil; atölyenin kayıt defteri.</p>
            </div>
        </div>
    </section>

    <section class="stories-index" data-story-index>
        <div class="container">
            <div class="story-filters" aria-label="Hikâyeleri filtrele">
                <div class="category-filter" role="group" aria-label="Çalışma alanı">
                    <button class="is-active" type="button" data-story-category="all" aria-pressed="true">Tümü</button>
                    <?php foreach ($categories as $category): ?>
                    <button type="button" data-story-category="<?= e($category['id'] ?? '') ?>" aria-pressed="false"><?= e($category['title'] ?? '') ?></button>
                    <?php endforeach; ?>
                </div>

                <div class="story-filter-tools">
                    <label>
                        <span>Durum</span>
                        <select data-story-status>
                            <option value="all">Tüm durumlar</option>
                            <?php foreach ($statuses as $status): ?>
                            <option value="<?= e($status) ?>"><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="story-search-label">
                        <span>Ara</span>
                        <input type="search" placeholder="Başlık, araç veya konu" data-story-search autocomplete="off">
                    </label>
                </div>
            </div>

            <div class="story-results-bar">
                <p><strong data-story-count><?= count($projects) ?></strong> kayıt gösteriliyor</p>
                <p>Kayıt sırası · yeni eklenenler ve güncel tutulanlar üstte</p>
            </div>

            <div class="story-index-list">
                <?php foreach ($projects as $index => $project):
                    $categoryId = (string) ($project['category'] ?? '');
                    $category = $categoryMap[$categoryId] ?? [];
                    $tools = is_array($project['tools'] ?? null) ? $project['tools'] : [];
                    $searchText = implode(' ', [
                        (string) ($project['title'] ?? ''),
                        (string) ($project['summary'] ?? ''),
                        (string) ($project['type'] ?? ''),
                        implode(' ', array_map('strval', $tools)),
                        (string) ($category['title'] ?? '')
                    ]);
                ?>
                <article
                    class="story-index-card"
                    data-story-card
                    data-category="<?= e($categoryId) ?>"
                    data-status="<?= e($project['status'] ?? '') ?>"
                    data-search="<?= e($searchText) ?>"
                >
                    <a href="<?= e(project_url($project)) ?>">
                        <span class="story-index-number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <div class="story-index-copy">
                            <p class="story-index-meta"><?= e($category['title'] ?? '') ?> · <?= e($project['status'] ?? '') ?> · <?= e($project['type'] ?? '') ?></p>
                            <h2><?= e($project['title'] ?? '') ?></h2>
                            <p><?= e($project['summary'] ?? '') ?></p>
                        </div>
                        <div class="story-index-tools">
                            <?php foreach (array_slice($tools, 0, 4) as $tool): ?><span><?= e((string) $tool) ?></span><?php endforeach; ?>
                        </div>
                        <span class="story-index-arrow" aria-hidden="true"><?= icon('arrow') ?></span>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>

            <p class="story-no-results" data-story-empty hidden>Bu filtrelerle eşleşen bir kayıt bulunamadı.</p>
        </div>
    </section>
</main>

<footer class="stories-footer">
    <div class="container">
        <p>© <?= e($site['year'] ?? date('Y')) ?> Ahmet Çetin · #fikrimvar</p>
        <a href="index.php">Ana sayfaya dön <?= icon('arrow') ?></a>
    </div>
</footer>
</body>
</html>
