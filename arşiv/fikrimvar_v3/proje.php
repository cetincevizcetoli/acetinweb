<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$projects = load_json('projects.json');
$categories = load_json('categories.json');
$slug = (string) filter_input(INPUT_GET, 'slug', FILTER_UNSAFE_RAW);
$project = null;
foreach ($projects as $item) { if (($item['slug'] ?? '') === $slug) { $project = $item; break; } }
$cat = null;
if ($project) { foreach ($categories as $item) { if (($item['id'] ?? '') === ($project['category'] ?? '')) { $cat = $item; break; } } }
if (!$project) { http_response_code(404); }
?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($project['title'] ?? 'Proje bulunamadı') ?> | #fikrimvar</title><link rel="stylesheet" href="assets/css/style.css"></head><body class="inner-page">
<header class="inner-header"><div class="container"><a class="brand" href="index.php"><strong>ACETİN</strong><span>#fikrimvar</span></a><a href="index.php#kayitlar">← Kayıtlara dön</a></div></header>
<main class="project-main"><div class="container"><?php if(!$project): ?><h1>Proje bulunamadı.</h1><?php else: ?>
<div class="project-heading"><p class="stamp"><?= e($project['status'] ?? '') ?> · <?= e($cat['title'] ?? '') ?></p><h1><?= e($project['title'] ?? '') ?></h1><p><?= e($project['summary'] ?? '') ?></p></div>
<img class="project-cover" src="<?= e($project['thumbnail'] ?? '') ?>" alt="">
<div class="project-detail-grid"><section><p class="eyebrow">FİKİR</p><h2>Nereden çıktı?</h2><p><?= e($project['idea'] ?? '') ?></p></section><section><p class="eyebrow">SÜREÇ</p><h2>Ne denendi?</h2><p><?= e($project['process'] ?? '') ?></p></section><section><p class="eyebrow">SONUÇ</p><h2>Şu an nerede?</h2><p><?= e($project['result'] ?? '') ?></p></section></div>
<?php if(!empty($project['url'])): ?><p class="project-external"><a class="button button-primary" target="_blank" rel="noopener noreferrer" href="<?= e($project['url']) ?>">Dış bağlantıyı aç <?= icon('arrow') ?></a></p><?php endif; ?>
<?php endif; ?></div></main></body></html>
