<?php
$path = __DIR__ . '/data/projects.json';
$projects = [];

if (is_readable($path)) {
    $decoded = json_decode(file_get_contents($path), true);
    if (is_array($decoded)) {
        $featured_projects = array_filter($decoded, function($p) {
            return isset($p['featured']) && $p['featured'] === true;
        });
        usort($featured_projects, function($a, $b) {
            $orderA = $a['order'] ?? 999;
            $orderB = $b['order'] ?? 999;
            return $orderA <=> $orderB;
        });
        $projects = $featured_projects;
    }
}

function safe_html($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ahmet Çetin | #fikrimvar</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
    <a href="/" class="logo">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        ACETİN
    </a>
    <nav>
        <a href="https://youtube.com/..." target="_blank" rel="noopener noreferrer">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
            YouTube
        </a>
        <a href="https://instagram.com/..." target="_blank" rel="noopener noreferrer">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
            Instagram
        </a>
    </nav>
</header>

<main>
    <section class="hero" aria-label="Giriş">
        <div class="hero-text">
            <h1>#fikrimvar</h1>
            <p class="lead">Aklıma takılanları deniyorum.</p>
            <p class="body">Bazen programa, bazen çizime, bazen videoya dönüşüyor. Çalışanları, değişenleri ve yarım kalanları burada topluyorum.</p>
            <p class="closing">Belki buradaki bir deneme, sizin fikrinizin başlangıcı olur.</p>
            <a href="#denemeler" class="cta">Denemelere Bak</a>
        </div>
        <div class="hero-visual" aria-hidden="true">
            <img src="assets/img/hero-core.png" alt="">
        </div>
    </section>

    <section id="denemeler" class="projects">
        <div class="container">
            <h2>Denemeler</h2>
            <?php if (empty($projects)): ?>
                <p class="empty">İçerikler hazırlanıyor.</p>
            <?php else: ?>
                <div class="grid">
                    <?php foreach($projects as $project): ?>
                    <a href="proje.php?slug=<?= safe_html($project['slug']) ?>" class="card">
                        <div class="thumb">
                            <img src="<?= safe_html($project['thumbnail']) ?>" alt="<?= safe_html($project['title']) ?>" onerror="this.style.display='none'">
                        </div>
                        <div class="info">
                            <span class="status"><?= safe_html($project['status']) ?></span>
                            <h3><?= safe_html($project['title']) ?></h3>
                            <p><?= safe_html($project['summary']) ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<footer class="site-footer">
    <p>&copy; <?= date("Y"); ?> Ahmet Çetin</p>
</footer>

</body>
</html>