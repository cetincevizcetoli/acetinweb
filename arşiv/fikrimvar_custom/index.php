<?php
$path = __DIR__ . '/data/projects.json';
$projects = [];

if (is_readable($path)) {
    $decoded = json_decode((string) file_get_contents($path), true);
    if (is_array($decoded)) {
        $projects = array_values(array_filter($decoded, static fn(array $p): bool => ($p['featured'] ?? false) === true));
        usort($projects, static fn(array $a, array $b): int => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));
    }
}

$current = null;
foreach ($projects as $project) {
    if (($project['current'] ?? false) === true) {
        $current = $project;
        break;
    }
}
$current ??= $projects[0] ?? null;

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function icon(string $name): string {
    $icons = [
        'youtube' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.6 7.2a2.8 2.8 0 0 0-2-2C17.8 4.7 12 4.7 12 4.7s-5.8 0-7.6.5a2.8 2.8 0 0 0-2 2A29 29 0 0 0 2 12a29 29 0 0 0 .4 4.8 2.8 2.8 0 0 0 2 2c1.8.5 7.6.5 7.6.5s5.8 0 7.6-.5a2.8 2.8 0 0 0 2-2A29 29 0 0 0 22 12a29 29 0 0 0-.4-4.8ZM10 15.2V8.8l5.5 3.2-5.5 3.2Z"/></svg>',
        'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.5 2h9A5.5 5.5 0 0 1 22 7.5v9a5.5 5.5 0 0 1-5.5 5.5h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2Zm0 2A3.5 3.5 0 0 0 4 7.5v9A3.5 3.5 0 0 0 7.5 20h9a3.5 3.5 0 0 0 3.5-3.5v-9A3.5 3.5 0 0 0 16.5 4h-9ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2.1a2.9 2.9 0 1 0 0 5.8 2.9 2.9 0 0 0 0-5.8ZM17.6 5.5a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4Z"/></svg>',
        'github' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-3.16 19.49c.5.09.68-.22.68-.48v-1.87c-2.78.6-3.37-1.18-3.37-1.18-.45-1.16-1.11-1.47-1.11-1.47-.91-.62.07-.61.07-.61 1 .07 1.53 1.03 1.53 1.03.9 1.53 2.35 1.09 2.92.83.09-.65.35-1.09.64-1.34-2.22-.25-4.56-1.11-4.56-4.94 0-1.09.39-1.98 1.03-2.68-.1-.25-.45-1.27.1-2.64 0 0 .84-.27 2.75 1.02A9.5 9.5 0 0 1 12 6.82a9.5 9.5 0 0 1 2.5.34c1.91-1.29 2.75-1.02 2.75-1.02.55 1.37.2 2.39.1 2.64.64.7 1.03 1.59 1.03 2.68 0 3.84-2.34 4.68-4.57 4.93.36.31.68.92.68 1.86V21c0 .27.18.58.69.48A10 10 0 0 0 12 2Z"/></svg>'
    ];
    return $icons[$name] ?? '';
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Ahmet Çetin'in kod, görsel, video ve yapay zekâ denemelerini bir araya getiren kişisel çalışma alanı.">
    <title>Ahmet Çetin | #fikrimvar</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body>
<a class="skip-link" href="#main">İçeriğe geç</a>
<header class="site-header" data-header>
    <div class="container header-inner">
        <a class="brand" href="index.php" aria-label="Ana sayfa">
            <strong>ACETİN</strong>
            <span>#fikrimvar</span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav" data-nav-toggle>
            <span></span><span></span><span></span><span class="sr-only">Menüyü aç</span>
        </button>
        <nav class="main-nav" id="main-nav" aria-label="Ana menü" data-nav>
            <a href="#denemeler">Denemeler</a>
            <a href="#surec">Süreç</a>
            <a href="#hakkimda">Ahmet</a>
            <div class="socials" aria-label="Sosyal bağlantılar">
                <a href="#" aria-label="YouTube"><?= icon('youtube') ?></a>
                <a href="#" aria-label="Instagram"><?= icon('instagram') ?></a>
                <a href="https://github.com/cetincevizcetoli" target="_blank" rel="noopener noreferrer" aria-label="GitHub"><?= icon('github') ?></a>
            </div>
        </nav>
    </div>
</header>

<main id="main">
    <section class="hero">
        <div class="hero-grid" aria-hidden="true"></div>
        <div class="container hero-layout">
            <div class="hero-copy">
                <p class="stamp">KİŞİSEL ÇALIŞMA ALANI · 2026</p>
                <h1>#fikrimvar</h1>
                <p class="hero-lead">Ben Ahmet Çetin. Üretim dünyasından gelen merakımı yazılım, yapay zekâ, çizim, video ve 3D araçlarıyla deniyorum.</p>
                <p class="hero-body">Burada yalnızca bitmiş işler yok. Çalışan araçlar, değişen yöntemler, yarım kalan fikirler ve onları ilerletirken öğrendiklerim de var.</p>
                <div class="hero-actions">
                    <a class="button button-primary" href="#denemeler">Denemelere bak <span aria-hidden="true">→</span></a>
                    <a class="button button-ghost" href="#surec">Nasıl ilerliyor?</a>
                </div>
                <ul class="topic-list" aria-label="Çalışma alanları">
                    <li>Kod</li><li>Görsel</li><li>Video</li><li>3D</li><li>Yöntem</li>
                </ul>
            </div>

            <?php if ($current): ?>
            <article class="current-card">
                <div class="current-image">
                    <img src="assets/img/hero-core.png" alt="Çatlak bir çekirdekten farklı yönlere açılan çizgiler">
                    <div class="current-badge"><span></span> ŞU AN ÜZERİNDE ÇALIŞIYORUM</div>
                </div>
                <div class="current-content">
                    <div>
                        <p class="eyebrow"><?= e($current['category'] ?? '') ?></p>
                        <h2><?= e($current['title'] ?? '') ?></h2>
                        <p><?= e($current['summary'] ?? '') ?></p>
                    </div>
                    <div class="current-footer">
                        <ul class="tool-list">
                            <?php foreach (($current['tools'] ?? []) as $tool): ?><li><?= e((string) $tool) ?></li><?php endforeach; ?>
                        </ul>
                        <a href="proje.php?slug=<?= e($current['slug'] ?? '') ?>">Deney kaydını aç →</a>
                    </div>
                </div>
            </article>
            <?php endif; ?>
        </div>
    </section>

    <section class="featured" id="denemeler">
        <div class="container">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">VİTRİN</p>
                    <h2>Son denemeler</h2>
                </div>
                <p>Koddan görsele, bitmiş araçlardan rafa kalkmış fikirlere kadar seçilmiş çalışmalar.</p>
            </div>
            <?php if (!$projects): ?>
                <p class="empty-state">İçerikler hazırlanıyor.</p>
            <?php else: ?>
            <div class="project-grid">
                <?php foreach (array_slice($projects, 0, 3) as $project): ?>
                <article class="project-card">
                    <a class="project-image" href="proje.php?slug=<?= e($project['slug'] ?? '') ?>">
                        <img src="<?= e($project['thumbnail'] ?? '') ?>" alt="" loading="lazy">
                    </a>
                    <div class="project-info">
                        <div class="project-meta"><span><?= e($project['status'] ?? '') ?></span><span><?= e($project['category'] ?? '') ?></span></div>
                        <h3><a href="proje.php?slug=<?= e($project['slug'] ?? '') ?>"><?= e($project['title'] ?? '') ?></a></h3>
                        <p><?= e($project['summary'] ?? '') ?></p>
                        <a class="text-link" href="proje.php?slug=<?= e($project['slug'] ?? '') ?>">İncele →</a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="process" id="surec">
        <div class="container process-layout">
            <div class="section-heading vertical">
                <p class="eyebrow">BİR FİKİR NE OLUYOR?</p>
                <h2>Sonuca değil, ilerleyişe de bakıyorum.</h2>
                <p>Her deneme aynı yere varmıyor. Bu yüzden yalnızca sonucu değil, kararları ve yol ayrımlarını da kaydediyorum.</p>
            </div>
            <ol class="process-steps">
                <li><span>01</span><div><h3>Fikir</h3><p>Aklıma bir şey takılıyor.</p></div></li>
                <li><span>02</span><div><h3>Deneme</h3><p>Uygun araçlarla ilk hâlini kuruyorum.</p></div></li>
                <li><span>03</span><div><h3>Düzeltme</h3><p>Çalışmayan tarafları değiştiriyorum.</p></div></li>
                <li><span>04</span><div><h3>Kayıt</h3><p>Çalışanı da yarım kalanı da saklıyorum.</p></div></li>
            </ol>
        </div>
    </section>

    <section class="about" id="hakkimda">
        <div class="container about-layout">
            <div>
                <p class="eyebrow">AHMET ÇETİN</p>
                <h2>Farklı araçlar, aynı merak.</h2>
            </div>
            <div class="about-copy">
                <p>Uzun yıllardır üretim ve iş dünyasının içindeyim. Son yıllarda buna yazılım, yapay zekâ, dijital çizim, video ve 3D de eklendi.</p>
                <p>Bu site, yaptıklarımı olduğundan büyük göstermeden ama kaybolmalarına da izin vermeden bir araya getirdiğim kişisel çalışma alanım.</p>
            </div>
        </div>
    </section>

    <section class="ecosystem">
        <div class="container">
            <div class="section-heading">
                <div><p class="eyebrow">EKOSİSTEM</p><h2>Aynı fikir, farklı biçimler.</h2></div>
            </div>
            <div class="channel-grid">
                <a class="channel" href="#"><span><?= icon('youtube') ?></span><div><h3>YouTube</h3><p>Süreç videoları ve uzun anlatımlar.</p></div></a>
                <a class="channel" href="#"><span><?= icon('instagram') ?></span><div><h3>Instagram</h3><p>Kısa görsel notlar ve deneme parçaları.</p></div></a>
                <a class="channel" href="https://github.com/cetincevizcetoli" target="_blank" rel="noopener noreferrer"><span><?= icon('github') ?></span><div><h3>GitHub</h3><p>Çalışan kodlar ve açık kaynak araçlar.</p></div></a>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container footer-inner"><span>© <?= date('Y') ?> Ahmet Çetin</span><a href="#main">Başa dön ↑</a></div>
</footer>
</body>
</html>
