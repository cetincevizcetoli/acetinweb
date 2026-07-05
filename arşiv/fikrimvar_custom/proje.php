<?php
$path = __DIR__ . '/data/projects.json';
$projects = [];
if (is_readable($path)) {
    $decoded = json_decode((string) file_get_contents($path), true);
    $projects = is_array($decoded) ? $decoded : [];
}
$slug = filter_input(INPUT_GET, 'slug', FILTER_UNSAFE_RAW) ?: '';
$project = null;
foreach ($projects as $item) {
    if (($item['slug'] ?? '') === $slug) { $project = $item; break; }
}
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
if (!$project) { http_response_code(404); }
?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $project ? e($project['title']) . ' | #fikrimvar' : 'Bulunamadı' ?></title><link rel="stylesheet" href="assets/css/style.css"></head><body class="project-page">
<header class="site-header is-solid"><div class="container header-inner"><a class="brand" href="index.php"><strong>ACETİN</strong><span>#fikrimvar</span></a><nav class="detail-nav"><a href="index.php#denemeler">← Denemelere dön</a></nav></div></header>
<main class="project-main container">
<?php if (!$project): ?><h1>Bu kayıt bulunamadı.</h1><?php else: ?>
<article class="project-detail">
    <div class="detail-heading"><p class="stamp"><?= e($project['status'] ?? '') ?> · <?= e($project['category'] ?? '') ?></p><h1><?= e($project['title'] ?? '') ?></h1><p><?= e($project['summary'] ?? '') ?></p></div>
    <img class="detail-image" src="<?= e($project['thumbnail'] ?? '') ?>" alt="">
    <div class="detail-grid">
        <section><p class="eyebrow">FİKİR</p><h2>Nereden çıktı?</h2><p><?= e($project['idea'] ?? '') ?></p></section>
        <section><p class="eyebrow">SÜREÇ</p><h2>Ne denendi?</h2><p><?= e($project['process'] ?? '') ?></p></section>
        <section><p class="eyebrow">SONUÇ</p><h2>Şu an nerede?</h2><p><?= e($project['result'] ?? '') ?></p></section>
    </div>
</article>
<?php endif; ?>
</main><footer class="site-footer"><div class="container footer-inner"><span>© <?= date('Y') ?> Ahmet Çetin</span><a href="index.php">Ana sayfa</a></div></footer></body></html>
