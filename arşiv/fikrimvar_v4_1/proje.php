<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$projects = load_json('projects.json');
$categories = load_json('categories.json');
by_order($projects);
$slug = (string) filter_input(INPUT_GET, 'slug', FILTER_UNSAFE_RAW);
$project = null;
$projectIndex = null;
foreach ($projects as $index => $item) {
    if (($item['slug'] ?? '') === $slug) {
        $project = $item;
        $projectIndex = $index;
        break;
    }
}
$category = null;
if ($project) {
    foreach ($categories as $item) {
        if (($item['id'] ?? '') === ($project['category'] ?? '')) {
            $category = $item;
            break;
        }
    }
}
if (!$project) {
    http_response_code(404);
}
$previous = is_int($projectIndex) && $projectIndex > 0 ? $projects[$projectIndex - 1] : null;
$next = is_int($projectIndex) && $projectIndex < count($projects) - 1 ? $projects[$projectIndex + 1] : null;
$tools = is_array($project['tools'] ?? null) ? $project['tools'] : [];
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($project['title'] ?? 'Hikâye bulunamadı') ?> | #fikrimvar</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="inner-page project-page">
<header class="inner-header">
    <div class="container">
        <a class="brand" href="index.php"><strong>AHMET ÇETİN</strong><span>#fikrimvar</span></a>
        <nav class="inner-nav" aria-label="Hikâye menüsü"><a href="hikayeler.php">← Bütün hikâyeler</a><a href="index.php">Ana sayfa</a></nav>
    </div>
</header>
<main class="project-main">
    <div class="container">
        <?php if (!$project): ?>
            <h1>Hikâye bulunamadı.</h1>
        <?php else: ?>
        <div class="project-heading">
            <p class="stamp"><?= e($project['status'] ?? '') ?> · <?= e($category['title'] ?? '') ?> · <?= e($project['type'] ?? '') ?></p>
            <h1><?= e($project['title'] ?? '') ?></h1>
            <p><?= e($project['summary'] ?? '') ?></p>
            <?php if ($tools !== []): ?><div class="project-tools"><?php foreach ($tools as $tool): ?><span><?= e((string) $tool) ?></span><?php endforeach; ?></div><?php endif; ?>
        </div>

        <img class="project-cover" src="<?= e($project['thumbnail'] ?? '') ?>" alt="<?= e($project['title'] ?? '') ?> çalışmasının görseli">

        <div class="project-detail-grid">
            <section><p class="eyebrow">FİKİR</p><h2>Nereden çıktı?</h2><p><?= e($project['idea'] ?? '') ?></p></section>
            <section><p class="eyebrow">SÜREÇ</p><h2>Ne denendi?</h2><p><?= e($project['process'] ?? '') ?></p></section>
            <section><p class="eyebrow">ŞU AN</p><h2>Nerede duruyor?</h2><p><?= e($project['result'] ?? '') ?></p></section>
        </div>

        <?php if (!empty($project['url'])): ?>
        <p class="project-external"><a class="button button-primary" target="_blank" rel="noopener noreferrer" href="<?= e($project['url']) ?>">Dış bağlantıyı aç <?= icon('arrow') ?></a></p>
        <?php endif; ?>

        <nav class="story-neighbours" aria-label="Diğer hikâyeler">
            <?php if ($previous): ?><a href="<?= e(project_url($previous)) ?>"><small>ÖNCEKİ KAYIT</small><strong>← <?= e($previous['title'] ?? '') ?></strong></a><?php else: ?><span></span><?php endif; ?>
            <?php if ($next): ?><a class="next" href="<?= e(project_url($next)) ?>"><small>SONRAKİ KAYIT</small><strong><?= e($next['title'] ?? '') ?> →</strong></a><?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
