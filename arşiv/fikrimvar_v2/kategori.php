<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$categories = load_json('categories.json');
$projects = load_json('projects.json');
$id = (string) filter_input(INPUT_GET, 'k', FILTER_UNSAFE_RAW);
$category = null;
foreach ($categories as $item) { if (($item['id'] ?? '') === $id) { $category = $item; break; } }
if (!$category) { http_response_code(404); }
$list = array_values(array_filter($projects, static fn(array $p): bool => ($p['category'] ?? '') === $id));
by_order($list);
?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($category['title'] ?? 'Kategori bulunamadı') ?> | #fikrimvar</title><link rel="stylesheet" href="assets/css/style.css"></head><body class="inner-page">
<header class="inner-header"><div class="container"><a class="brand" href="index.php"><strong>ACETİN</strong><span>#fikrimvar</span></a><a href="index.php#alanlar">← Ana sayfaya dön</a></div></header>
<main class="listing-main"><div class="container">
<?php if (!$category): ?><h1>Kategori bulunamadı.</h1><?php else: ?><div class="listing-head"><p class="eyebrow"><?= e($category['number'] ?? '') ?> · ÇALIŞMA ALANI</p><h1><?= e($category['title'] ?? '') ?></h1><p><?= e($category['short'] ?? '') ?></p></div><div class="record-grid"><?php foreach($list as $project): ?><article class="record-card"><a class="record-image" href="<?= e(project_url($project)) ?>"><img src="<?= e($project['thumbnail'] ?? '') ?>" alt=""></a><div class="record-info"><div class="record-meta"><span><?= e($project['status'] ?? '') ?></span><span><?= e($project['type'] ?? '') ?></span></div><h3><a href="<?= e(project_url($project)) ?>"><?= e($project['title'] ?? '') ?></a></h3><p><?= e($project['summary'] ?? '') ?></p></div></article><?php endforeach; ?></div><?php endif; ?>
</div></main></body></html>
