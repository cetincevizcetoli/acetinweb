<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$siteData = load_json('site.json');
$categories = load_json('categories.json');
$projects = load_json('projects.json');
$notes = load_json('notes.json');
by_order($categories);
by_order($projects);

$site = $siteData['site'] ?? [];
$hero = $siteData['hero'] ?? [];
$origin = $siteData['origin'] ?? [];
$mapItems = $siteData['map'] ?? [];
$currentData = $siteData['current'] ?? [];
$journalData = $siteData['journal'] ?? [];
$closing = $siteData['closing'] ?? [];
$channels = $siteData['channels'] ?? [];
$noteStatus = (string) ($_GET['note'] ?? '');

$categoryMap = [];
$projectMap = [];
foreach ($categories as $category) {
    $categoryMap[(string) ($category['id'] ?? '')] = $category;
}
foreach ($projects as $project) {
    $projectMap[(string) ($project['slug'] ?? '')] = $project;
}

$currentSlugs = array_values(array_filter(
    $hero['current_slugs'] ?? ['webbordro', 'gorselden-harekete', 'ai-context'],
    static fn(mixed $slug): bool => is_string($slug) && $slug !== ''
));
$current = [];
foreach ($currentSlugs as $slug) {
    if (isset($projectMap[$slug])) {
        $current[$slug] = $projectMap[$slug];
    }
}
$webbordro = $current['webbordro'] ?? [];
$motion = $current['gorselden-harekete'] ?? [];
$context = $current['ai-context'] ?? [];

$otherSlugs = array_values(array_filter(
    $hero['other_slugs'] ?? [],
    static fn(mixed $slug): bool => is_string($slug) && $slug !== ''
));
$otherStories = [];
foreach ($otherSlugs as $slug) {
    if (isset($projectMap[$slug])) {
        $otherStories[] = $projectMap[$slug];
    }
}

$unfinishedCount = count(array_filter($projects, static fn(array $project): bool => in_array((string) ($project['status'] ?? ''), ['Yarım kaldı', 'Fikir', 'Not'], true)));
$methodCount = count(array_filter($projects, static fn(array $project): bool => (string) ($project['category'] ?? '') === 'yz-yontem'));
$visualCount = count(array_filter($projects, static fn(array $project): bool => in_array((string) ($project['category'] ?? ''), ['gorsel-render', 'video-hareket'], true)));
$mapCounts = [count($projects), $methodCount, $visualCount, $unfinishedCount];
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($site['description'] ?? '') ?>">
    <meta name="theme-color" content="#0d1013">
    <title><?= e($site['title'] ?? 'Ahmet Çetin | #fikrimvar') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body>
<a class="skip-link" href="#main">İçeriğe geç</a>

<header class="site-header" data-header>
    <div class="shell header-inner">
        <a class="brand" href="index.php" aria-label="Ahmet Çetin ana sayfa">
            <span class="brand-name">AHMET ÇETİN</span>
            <span class="brand-mark">#fikrimvar</span>
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav" data-nav-toggle>
            <?= icon('menu') ?><span class="sr-only">Menüyü aç</span>
        </button>

        <nav class="main-nav" id="main-nav" aria-label="Ana menü" data-nav>
            <a href="#gunluk">Ahmet</a>
            <a href="#acik-dosyalar">Açık dosyalar</a>
            <a href="hikayeler.php">Bütün hikâyeler</a>
            <button class="notes-trigger" type="button" data-notes-open>Kenar notları</button>
            <div class="socials" aria-label="Sosyal bağlantılar">
                <?php foreach ($channels as $channel):
                    $channelUrl = trim((string) ($channel['url'] ?? ''));
                    if ($channelUrl === '' || $channelUrl === '#') continue;
                ?>
                <a href="<?= e($channelUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($channel['title'] ?? '') ?>">
                    <?= icon((string) ($channel['id'] ?? '')) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </nav>
    </div>
</header>

<main id="main">
    <section class="hero" data-parallax-root>
        <div class="hero-atmosphere" aria-hidden="true">
            <div class="hero-grid" data-parallax data-depth="0.08"></div>
            <img class="hero-core" src="assets/img/hero/hero-core.png" alt="" data-parallax data-depth="0.17">
            <div class="hero-orbit hero-orbit-one" data-parallax data-depth="0.11"></div>
            <div class="hero-orbit hero-orbit-two" data-parallax data-depth="0.05"></div>
            <div class="hero-shade"></div>
        </div>

        <div class="shell hero-inner">
            <div class="hero-copy" data-reveal>
                <p class="hero-eyebrow"><?= e($hero['eyebrow'] ?? '') ?></p>
                <h1><span>#fikrim</span><strong>var</strong></h1>
                <p class="hero-lead"><?= e($hero['lead'] ?? '') ?></p>
                <p class="hero-body"><?= e($hero['body'] ?? '') ?></p>
                <div class="hero-actions">
                    <a class="button button-light" href="#gunluk">Hikâyenin başladığı yere git <?= icon('arrow') ?></a>
                    <a class="text-button" href="hikayeler.php">Bütün kayıtları aç</a>
                </div>
            </div>

            <aside class="hero-map" aria-label="Sitenin içerik yapısı" data-reveal>
                <p class="hero-map-label">BU SİTEDE</p>
                <?php foreach ($mapItems as $index => $item): ?>
                <a href="hikayeler.php" class="hero-map-item">
                    <span class="hero-map-count"><?= str_pad((string) ($mapCounts[$index] ?? 0), 2, '0', STR_PAD_LEFT) ?></span>
                    <span><strong><?= e($item['title'] ?? '') ?></strong><small><?= e($item['text'] ?? '') ?></small></span>
                </a>
                <?php endforeach; ?>
            </aside>

            <a class="hero-scroll" href="#gunluk" aria-label="Ahmet'in hikâyesine kaydır">
                <span></span> Günlüğe gir
            </a>
        </div>
    </section>

    <section class="origin-spread" id="gunluk">
        <div class="shell origin-canvas">
            <div class="origin-title" data-reveal>
                <p class="eyebrow"><?= e($origin['label'] ?? '') ?></p>
                <h2><?= e($origin['title'] ?? '') ?></h2>
                <p class="origin-intro"><?= e($origin['intro'] ?? '') ?></p>
            </div>

            <figure class="origin-mentor" data-reveal>
                <img src="assets/img/mentor/mehmet-firat-ders.webp" alt="Mehmet Fırat hocanın çevrim içi dersinden arşiv görüntüsü" loading="lazy">
                <figcaption>ARŞİVDEN · İlk cesaretin geldiği ders ekranı</figcaption>
            </figure>

            <article class="origin-note origin-note-mentor" data-reveal>
                <span class="origin-step">01</span>
                <p class="origin-kicker">BAŞLANGIÇ NOKTASI</p>
                <h3><?= e($origin['mentor_title'] ?? '') ?></h3>
                <p><?= e($origin['mentor_text'] ?? '') ?></p>
                <a href="<?= e($origin['mentor_url'] ?? '#') ?>" target="_blank" rel="noopener noreferrer"><?= e($origin['mentor_link'] ?? '') ?> <?= icon('arrow') ?></a>
            </article>

            <figure class="origin-production" data-reveal data-parallax-root>
                <img src="assets/img/story/origin-production.svg" alt="Üretim takip sistemini temsil eden üretim akışı ve ekran şeması" data-parallax data-depth="0.06" loading="lazy">
            </figure>

            <article class="origin-note origin-note-project" data-reveal>
                <span class="origin-step">02</span>
                <p class="origin-kicker">İLK YAŞAYAN PROJE</p>
                <h3><?= e($origin['first_project_title'] ?? '') ?></h3>
                <p><?= e($origin['first_project_text'] ?? '') ?></p>
                <?php if (isset($projectMap['uretim-takip'])): ?>
                <a href="<?= e(story_url($projectMap['uretim-takip'])) ?>">Üretim projesi kaydını aç <?= icon('arrow') ?></a>
                <?php endif; ?>
            </article>

            <p class="origin-thread" aria-hidden="true">FİKİR → DENEME → GERÇEK KULLANIM → YENİ FİKİR</p>
        </div>
    </section>

    <section class="current-canvas" id="acik-dosyalar">
        <div class="shell current-heading" data-reveal>
            <div>
                <p class="eyebrow"><?= e($currentData['label'] ?? '') ?></p>
                <h2><?= e($currentData['title'] ?? '') ?></h2>
            </div>
            <p><?= e($currentData['note'] ?? '') ?></p>
        </div>

        <div class="shell story-stage">
            <?php if ($webbordro !== []): ?>
            <article class="stage-story stage-webbordro" data-reveal>
                <a href="<?= e(story_url($webbordro)) ?>">
                    <div class="stage-visual"><img src="assets/img/featured/webbordro-system.svg" alt="WebBordro'nun farklı sürümlerini temsil eden arayüz kompozisyonu" loading="lazy"></div>
                    <div class="stage-copy">
                        <p>01 · <?= e($webbordro['status'] ?? '') ?></p>
                        <h3><?= e($webbordro['question'] ?? $webbordro['title'] ?? '') ?></h3>
                        <span><?= e($webbordro['summary'] ?? '') ?></span>
                        <strong>Hikâyeyi gör · 3 dakika <?= icon('arrow') ?></strong>
                    </div>
                </a>
            </article>
            <?php endif; ?>

            <?php if ($motion !== []): ?>
            <article class="stage-story stage-motion" data-reveal data-parallax-root>
                <a href="<?= e(story_url($motion)) ?>">
                    <div class="stage-visual"><img src="assets/img/featured/invoke-flow.svg" alt="Teknik katmanlardan görüntüye ve harekete uzanan üretim zinciri" data-parallax data-depth="0.05" loading="lazy"></div>
                    <div class="stage-copy">
                        <p>02 · <?= e($motion['status'] ?? '') ?></p>
                        <h3><?= e($motion['question'] ?? $motion['title'] ?? '') ?></h3>
                        <span><?= e($motion['summary'] ?? '') ?></span>
                        <strong>Üretim zincirini aç <?= icon('arrow') ?></strong>
                    </div>
                </a>
            </article>
            <?php endif; ?>

            <?php if ($context !== []): ?>
            <article class="stage-story stage-context" data-reveal>
                <a href="<?= e(story_url($context)) ?>">
                    <div class="stage-copy">
                        <p>03 · <?= e($context['status'] ?? '') ?></p>
                        <h3><?= e($context['question'] ?? $context['title'] ?? '') ?></h3>
                        <span><?= e($context['summary'] ?? '') ?></span>
                        <strong>Teknik kaydı aç <?= icon('arrow') ?></strong>
                    </div>
                    <div class="stage-visual"><img src="assets/img/featured/ai-context-terminal.svg" alt="ai-context aracının terminal görünümü" loading="lazy"></div>
                </a>
            </article>
            <?php endif; ?>

            <aside class="stage-note" data-reveal>
                <p>Bu sayfada proje adı değil, önce soru görünür. Çünkü merak uyandıran şey ürünün etiketi değil, yaşanan problemdir.</p>
                <a href="hikayeler.php">Bütün hikâyeler · <?= count($projects) ?> kayıt <?= icon('arrow') ?></a>
            </aside>
        </div>
    </section>

    <section class="journal-strip" aria-labelledby="journal-title">
        <div class="shell journal-heading" data-reveal>
            <div>
                <p class="eyebrow"><?= e($journalData['label'] ?? '') ?></p>
                <h2 id="journal-title"><?= e($journalData['title'] ?? '') ?></h2>
            </div>
            <p><?= e($journalData['text'] ?? '') ?></p>
        </div>

        <div class="journal-rail" data-journal-rail>
            <div class="journal-track">
                <?php foreach ($otherStories as $index => $project):
                    $category = $categoryMap[(string) ($project['category'] ?? '')] ?? [];
                ?>
                <article class="journal-entry" data-reveal>
                    <a href="<?= e(story_url($project)) ?>">
                        <figure><img src="<?= e($project['thumbnail'] ?? '') ?>" alt="" loading="lazy"></figure>
                        <p><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?> · <?= e($category['title'] ?? '') ?> · <?= e($project['status'] ?? '') ?></p>
                        <h3><?= e($project['question'] ?? $project['title'] ?? '') ?></h3>
                        <span><?= e($project['lesson'] ?? '') ?></span>
                    </a>
                </article>
                <?php endforeach; ?>

                <a class="journal-all" href="hikayeler.php" data-reveal>
                    <small><?= count($projects) ?> KAYIT</small>
                    <strong>Bütün günlüğü aç</strong>
                    <span><?= icon('arrow') ?></span>
                </a>
            </div>
        </div>
    </section>

    <section class="closing-spread">
        <div class="shell closing-grid" data-reveal>
            <div>
                <p class="eyebrow"><?= e($closing['label'] ?? '') ?></p>
                <h2><?= e($closing['title'] ?? '') ?></h2>
            </div>
            <div class="closing-copy">
                <p><?= e($closing['text'] ?? '') ?></p>
                <div class="channel-lines">
                    <?php foreach ($channels as $channel):
                        $channelUrl = trim((string) ($channel['url'] ?? ''));
                        $isLinked = $channelUrl !== '' && $channelUrl !== '#';
                    ?>
                    <?php if ($isLinked): ?><a href="<?= e($channelUrl) ?>" target="_blank" rel="noopener noreferrer"><?php else: ?><span class="is-pending"><?php endif; ?>
                        <i><?= icon((string) ($channel['id'] ?? '')) ?></i>
                        <b><?= e($channel['title'] ?? '') ?></b>
                        <small><?= e($channel['text'] ?? '') ?></small>
                    <?php if ($isLinked): ?></a><?php else: ?></span><?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="shell footer-inner">
        <p>© <?= e($site['year'] ?? date('Y')) ?> Ahmet Çetin · #fikrimvar</p>
        <div><a href="hikayeler.php">Bütün hikâyeler</a><button type="button" data-notes-open>Kenar notları</button><a href="#main">Başa dön ↑</a></div>
    </div>
</footer>

<div class="notes-overlay" data-notes-overlay hidden></div>
<aside class="notes-drawer" id="notlar" aria-labelledby="notes-title" aria-hidden="true" data-notes-drawer>
    <div class="notes-head">
        <div><p class="eyebrow">ZİYARETÇİ DEFTERİ</p><h2 id="notes-title">Kenar notları</h2></div>
        <button type="button" class="notes-close" aria-label="Kenar notlarını kapat" data-notes-close><?= icon('close') ?></button>
    </div>

    <?php if ($noteStatus === 'ok'): ?><p class="form-status success">Notun kaydedildi. Yayımlanmadan önce gözden geçirilecek.</p><?php endif; ?>
    <?php if ($noteStatus === 'error'): ?><p class="form-status error">Not kaydedilemedi. Alanları kontrol edip yeniden dene.</p><?php endif; ?>

    <div class="published-notes">
        <?php if ($notes === []): ?>
        <p class="empty-note">Henüz yayımlanmış bir kenar notu yok. İlk notu sen bırakabilirsin.</p>
        <?php else: ?>
            <?php foreach (array_slice(array_reverse($notes), 0, 5) as $note): ?>
            <blockquote><p><?= e($note['message'] ?? '') ?></p><footer><?= e($note['name'] ?? '') ?></footer></blockquote>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form class="note-form" action="yorum-kaydet.php" method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label>Adın<input type="text" name="name" minlength="2" maxlength="60" required autocomplete="name"></label>
        <label>Notun<textarea name="message" minlength="3" maxlength="300" rows="5" required></textarea></label>
        <label class="honeypot" aria-hidden="true">Web sitesi<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        <button class="button button-dark" type="submit">Notu gönder <?= icon('arrow') ?></button>
        <small>Notlar doğrudan yayımlanmaz; önce moderasyon kuyruğuna alınır.</small>
    </form>
</aside>
</body>
</html>
