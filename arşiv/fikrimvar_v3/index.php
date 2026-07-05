<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$siteData = load_json('site.json');
$categories = load_json('categories.json');
$projects = load_json('projects.json');
$notes = load_json('notes.json');
by_order($categories);
by_order($projects);

$categoryMap = [];
$projectMap = [];
foreach ($categories as $category) {
    $categoryMap[(string) ($category['id'] ?? '')] = $category;
}
foreach ($projects as $project) {
    $projectMap[(string) ($project['slug'] ?? '')] = $project;
}

$site = $siteData['site'] ?? [];
$hero = $siteData['hero'] ?? [];
$ack = $siteData['acknowledgement'] ?? [];
$about = $siteData['about'] ?? [];
$channels = $siteData['channels'] ?? [];
$noteStatus = $_GET['note'] ?? '';

$featuredSlug = (string) ($hero['featured_slug'] ?? 'kaplumbaga-kabugu');
$featuredProject = $projectMap[$featuredSlug] ?? ($projects[0] ?? []);
$featuredCategory = $categoryMap[(string) ($featuredProject['category'] ?? '')] ?? [];

$latestSlugs = array_values(array_filter(
    $hero['latest_slugs'] ?? [],
    static fn(mixed $slug): bool => is_string($slug) && $slug !== ''
));
$latestProjects = [];
foreach ($latestSlugs as $slug) {
    if (isset($projectMap[$slug])) {
        $latestProjects[] = $projectMap[$slug];
    }
}
if ($latestProjects === []) {
    $latestProjects = array_slice($projects, 0, 4);
}

$excludedSlugs = array_merge([$featuredSlug], $latestSlugs);
$archiveProjects = array_values(array_filter(
    $projects,
    static fn(array $project): bool => !in_array((string) ($project['slug'] ?? ''), $excludedSlugs, true)
));

$counts = [];
foreach ($projects as $project) {
    $id = (string) ($project['category'] ?? '');
    $counts[$id] = ($counts[$id] ?? 0) + 1;
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($site['description'] ?? '') ?>">
    <title><?= e($site['title'] ?? 'Ahmet Çetin | #fikrimvar') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body>
<a class="skip-link" href="#main">İçeriğe geç</a>

<header class="site-header" data-header>
    <div class="container header-inner">
        <a class="brand" href="index.php" aria-label="Ahmet Çetin ana sayfa">
            <span class="brand-name">Ahmet Çetin</span>
            <span class="brand-project">#fikrimvar</span>
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav" data-nav-toggle>
            <?= icon('menu') ?><span class="sr-only">Menüyü aç</span>
        </button>

        <nav class="main-nav" id="main-nav" aria-label="Ana menü" data-nav>
            <a href="#son-kayitlar">Projeler</a>
            <a href="#alanlar">Alanlar</a>
            <a href="#tesekkur">Başlangıç</a>
            <a href="#ahmet">Hakkımda</a>
            <div class="socials" aria-label="Sosyal bağlantılar">
                <?php foreach ($channels as $channel): ?>
                <a href="<?= e($channel['url'] ?? '#') ?>" <?= ($channel['url'] ?? '#') !== '#' ? 'target="_blank" rel="noopener noreferrer"' : '' ?> aria-label="<?= e($channel['title'] ?? '') ?>">
                    <?= icon((string) ($channel['id'] ?? '')) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </nav>
    </div>
</header>

<main id="main">
    <section class="hero">
        <div class="paper-grid" aria-hidden="true"></div>
        <div class="container hero-layout">
            <div class="hero-copy">
                <p class="stamp"><?= e($hero['stamp'] ?? '') ?></p>
                <h1><?= e($hero['title'] ?? '#fikrimvar') ?></h1>
                <p class="hero-lead"><?= e($hero['lead'] ?? '') ?></p>
                <p class="hero-body"><?= e($hero['body'] ?? '') ?></p>

                <div class="hero-actions">
                    <a class="button button-primary" href="#son-kayitlar">Projeleri gör <?= icon('arrow') ?></a>
                    <a class="button button-ghost" href="#tesekkur">Bu yol nerede başladı?</a>
                </div>

                <dl class="hero-stats" aria-label="İçerik özeti">
                    <div><dt><?= count($projects) ?></dt><dd>kayıt ve proje</dd></div>
                    <div><dt><?= count($categories) ?></dt><dd>çalışma alanı</dd></div>
                    <div><dt>1</dt><dd>kişisel atölye</dd></div>
                </dl>
            </div>

            <?php if ($featuredProject !== []): ?>
            <article class="featured-project">
                <a class="featured-media" href="<?= e(project_url($featuredProject)) ?>">
                    <img src="<?= e($featuredProject['thumbnail'] ?? '') ?>" alt="<?= e($featuredProject['title'] ?? '') ?> için geçici proje görseli" loading="eager">
                    <span class="featured-status"><?= e($featuredProject['status'] ?? '') ?></span>
                </a>
                <div class="featured-content">
                    <div class="featured-kicker">
                        <span>ŞU AN ÜZERİNDE ÇALIŞIYORUM</span>
                        <span><?= e($featuredProject['type'] ?? '') ?></span>
                    </div>
                    <h2><a href="<?= e(project_url($featuredProject)) ?>"><?= e($featuredProject['title'] ?? '') ?></a></h2>
                    <p><?= e($featuredProject['summary'] ?? '') ?></p>
                    <div class="featured-bottom">
                        <ul aria-label="Kullanılan araçlar">
                            <?php foreach (array_slice($featuredProject['tools'] ?? [], 0, 3) as $tool): ?>
                            <li><?= e((string) $tool) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?= e(project_url($featuredProject)) ?>" aria-label="<?= e($featuredProject['title'] ?? '') ?> kaydını aç"><?= icon('arrow') ?></a>
                    </div>
                </div>
            </article>
            <?php endif; ?>
        </div>
    </section>

    <section class="latest-records" id="son-kayitlar">
        <div class="container">
            <div class="section-heading-row">
                <div>
                    <p class="eyebrow">SON KAYITLAR</p>
                    <h2>Masada şu an bunlar var.</h2>
                </div>
                <p>Koddan görsele, sesten iş araçlarına uzanan dört farklı çalışma. Görseller şimdilik yer tutucu; içeriklerin kendisi gerçek.</p>
            </div>

            <div class="latest-grid">
                <?php foreach ($latestProjects as $project): $cat = $categoryMap[(string) ($project['category'] ?? '')] ?? []; ?>
                <article class="latest-card">
                    <a class="latest-image" href="<?= e(project_url($project)) ?>">
                        <img src="<?= e($project['thumbnail'] ?? '') ?>" alt="<?= e($project['title'] ?? '') ?> için geçici proje görseli" loading="lazy">
                    </a>
                    <div class="latest-info">
                        <div class="record-meta">
                            <span><?= e($project['status'] ?? '') ?></span>
                            <span><?= e($cat['title'] ?? '') ?></span>
                        </div>
                        <h3><a href="<?= e(project_url($project)) ?>"><?= e($project['title'] ?? '') ?></a></h3>
                        <p><?= e($project['summary'] ?? '') ?></p>
                        <a class="card-link" href="<?= e(project_url($project)) ?>">Kaydı aç <?= icon('arrow') ?></a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="categories" id="alanlar">
        <div class="container">
            <div class="section-intro">
                <div>
                    <p class="eyebrow">ÇALIŞMA ALANLARI</p>
                    <h2>Merak tek bir biçime sığmıyor.</h2>
                </div>
                <p>Projeler yaptığım işin türüne göre ayrılıyor. InvokeAI, Krita, PHP veya Python gibi araçlar ise içeriklerin içinde etiket olarak yer alıyor.</p>
            </div>

            <div class="category-grid">
                <?php foreach ($categories as $category): ?>
                <a class="category-card" href="<?= e(category_url($category)) ?>" style="--category-accent:<?= e($category['accent'] ?? '#a24b2a') ?>">
                    <span class="category-number"><?= e($category['number'] ?? '') ?></span>
                    <div>
                        <h3><?= e($category['title'] ?? '') ?></h3>
                        <p><?= e($category['short'] ?? '') ?></p>
                    </div>
                    <strong><?= (int) ($counts[$category['id'] ?? ''] ?? 0) ?> kayıt</strong>
                    <span class="category-arrow"><?= icon('arrow') ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="acknowledgement" id="tesekkur">
        <div class="container ack-layout">
            <div class="ack-copy">
                <div class="ack-heading">
                    <span class="ack-seal" aria-hidden="true">MF</span>
                    <p class="eyebrow"><?= e($ack['label'] ?? '') ?></p>
                </div>
                <h2><?= e($ack['title'] ?? '') ?></h2>
                <p><?= e($ack['text'] ?? '') ?></p>
                <a href="<?= e($ack['url'] ?? '#') ?>" target="_blank" rel="noopener noreferrer"><?= e($ack['link_text'] ?? '') ?> <?= icon('arrow') ?></a>
            </div>

            <figure class="ack-visual">
                <img src="assets/img/mentor/mehmet-firat-ders.webp" alt="Mehmet Fırat hocanın çevrim içi dersinden bir ekran görüntüsü" loading="lazy">
                <figcaption>
                    <span>ARŞİVDEN GERÇEK KAYIT</span>
                    <strong>Yapay zekâ yolculuğumun başlangıcına eşlik eden derslerden biri.</strong>
                </figcaption>
            </figure>
        </div>
    </section>

    <section class="records" id="kayitlar">
        <div class="container">
            <div class="section-intro">
                <div>
                    <p class="eyebrow">KAYIT ARŞİVİ</p>
                    <h2>Çalışanlar, değişenler, yarım kalanlar.</h2>
                </div>
                <p>Her şey bitmiş bir ürün olmak zorunda değil. Bazı kayıtlar çalışan araç, bazıları yöntem notu, bazıları da yeniden dönülecek bir fikir.</p>
            </div>

            <div class="filter-row" role="group" aria-label="Kayıtları filtrele">
                <button class="filter is-active" type="button" data-filter="all">Tümü</button>
                <?php foreach ($categories as $category): ?>
                <button class="filter" type="button" data-filter="<?= e($category['id'] ?? '') ?>"><?= e($category['title'] ?? '') ?></button>
                <?php endforeach; ?>
            </div>

            <div class="record-grid" data-record-grid>
                <?php foreach ($archiveProjects as $project): $cat = $categoryMap[(string) ($project['category'] ?? '')] ?? []; ?>
                <article class="record-card" data-category="<?= e($project['category'] ?? '') ?>">
                    <a class="record-image" href="<?= e(project_url($project)) ?>">
                        <img src="<?= e($project['thumbnail'] ?? '') ?>" alt="<?= e($project['title'] ?? '') ?> için geçici proje görseli" loading="lazy">
                    </a>
                    <div class="record-info">
                        <div class="record-meta">
                            <span><?= e($project['status'] ?? '') ?></span>
                            <span><?= e($cat['title'] ?? '') ?></span>
                        </div>
                        <h3><a href="<?= e(project_url($project)) ?>"><?= e($project['title'] ?? '') ?></a></h3>
                        <p><?= e($project['summary'] ?? '') ?></p>
                        <ul>
                            <?php foreach (array_slice($project['tools'] ?? [], 0, 3) as $tool): ?>
                            <li><?= e((string) $tool) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="method-strip">
        <div class="container">
            <div class="section-intro compact">
                <div>
                    <p class="eyebrow">YÖNTEM NOTLARI</p>
                    <h2>Küçük ayarlar, gerçek farklar.</h2>
                </div>
            </div>
            <div class="method-grid">
                <?php foreach (array_filter($projects, static fn(array $p): bool => in_array($p['slug'] ?? '', ['tablet-krita','controlnet-butce','dinamik-mufredat','patlama-fizigi'], true)) as $project): ?>
                <a class="method-card" href="<?= e(project_url($project)) ?>">
                    <span><?= e($project['type'] ?? '') ?></span>
                    <h3><?= e($project['title'] ?? '') ?></h3>
                    <p><?= e($project['summary'] ?? '') ?></p>
                    <?= icon('arrow') ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="about-notes" id="ahmet">
        <div class="container about-notes-layout">
            <div class="about-block">
                <p class="eyebrow">AHMET ÇETİN</p>
                <h2><?= e($about['title'] ?? '') ?></h2>
                <p><?= e($about['text'] ?? '') ?></p>

                <div class="channel-grid">
                    <?php foreach ($channels as $channel): ?>
                    <a class="channel" href="<?= e($channel['url'] ?? '#') ?>" <?= ($channel['url'] ?? '#') !== '#' ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                        <span><?= icon((string) ($channel['id'] ?? '')) ?></span>
                        <div>
                            <h3><?= e($channel['title'] ?? '') ?></h3>
                            <p><?= e($channel['text'] ?? '') ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <aside class="notes" id="notlar">
                <div class="notes-head">
                    <div>
                        <p class="eyebrow">KENAR NOTLARI</p>
                        <h2>Ziyaretçi defteri.</h2>
                    </div>
                    <button type="button" class="text-button" data-dialog-open="note-dialog">Not bırak</button>
                </div>

                <?php if ($noteStatus === 'ok'): ?>
                <p class="notice success">Notun kaydedildi. Yayına alınmadan önce kontrol edilecek.</p>
                <?php elseif ($noteStatus === 'error'): ?>
                <p class="notice error">Not kaydedilemedi. Alanları kontrol edip yeniden dene.</p>
                <?php endif; ?>

                <div class="note-list">
                    <?php foreach (array_slice($notes, 0, 3) as $note): ?>
                    <blockquote>
                        <p>“<?= e($note['message'] ?? '') ?>”</p>
                        <footer><?= e($note['name'] ?? '') ?> · <?= e($note['date'] ?? '') ?></footer>
                    </blockquote>
                    <?php endforeach; ?>
                </div>
            </aside>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <span>© <?= date('Y') ?> Ahmet Çetin</span>
        <a href="#main">Başa dön ↑</a>
    </div>
</footer>

<dialog class="note-dialog" id="note-dialog">
    <form method="post" action="yorum-kaydet.php">
        <div class="dialog-head">
            <div><p class="eyebrow">KENAR NOTU</p><h2>Bir şey bırak.</h2></div>
            <button type="button" aria-label="Kapat" data-dialog-close><?= icon('close') ?></button>
        </div>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label>Adın <input type="text" name="name" minlength="2" maxlength="60" required></label>
        <label>Notun <textarea name="message" minlength="3" maxlength="300" rows="5" required></textarea></label>
        <label class="honeypot" aria-hidden="true">Web sitesi <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        <p>Notlar doğrudan yayımlanmaz; önce kontrol edilir.</p>
        <button class="button button-primary" type="submit">Kaydet <?= icon('arrow') ?></button>
    </form>
</dialog>
</body>
</html>
