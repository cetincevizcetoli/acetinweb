<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$siteData = load_site();
$site = $siteData['site'] ?? [];
$hero = $siteData['hero'] ?? [];
$manifesto = $siteData['manifesto'] ?? [];
$origin = $siteData['origin'] ?? [];
$home = $siteData['homepage'] ?? [];
$channels = $siteData['channels'] ?? [];
$stories = load_stories();
$notes = load_json_path(DATA_DIR . '/notes.json');
$noteStatus = (string) ($_GET['note'] ?? '');

$atelierSlug = (string) ($home['active_atelier'] ?? '');
$atelier = $stories[$atelierSlug] ?? null;
$atelierUpdates = $atelier ? load_updates($atelier) : [];
$latestAtelier = $atelierUpdates !== [] ? end($atelierUpdates) : null;
if ($atelierUpdates !== []) reset($atelierUpdates);

$focusStories = [];
foreach (($home['focus_stories'] ?? []) as $slug) {
    if (isset($stories[$slug])) $focusStories[] = $stories[$slug];
}
$traceStories = [];
foreach (($home['trace_stories'] ?? []) as $slug) {
    if (isset($stories[$slug])) $traceStories[] = $stories[$slug];
}

$storyCount = count($stories);
$atelierCount = count(array_filter($stories, static fn(array $story): bool => ($story['kind'] ?? '') === 'atelier'));
$methodCount = count(array_filter($stories, static fn(array $story): bool => ($story['category'] ?? '') === 'yz-yontem'));
$unfinishedCount = count(array_filter($stories, static fn(array $story): bool => in_array(($story['status'] ?? ''), ['yarim', 'fikir', 'not'], true)));
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($site['description'] ?? '') ?>">
    <meta name="theme-color" content="#0c0f13">
    <title><?= e($site['title'] ?? 'Ahmet Çetin | #fikrimvar') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600&family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body class="home-page">
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
            <a href="#manifesto">Manifesto</a>
            <a href="#atolye">Atölye</a>
            <a href="#hikayeler">Hikâyeler</a>
            <a href="hikayeler.php">Bütün kayıtlar</a>
            <button class="notes-trigger" type="button" data-notes-open>Kenar notları</button>
        </nav>
    </div>
</header>

<main id="main">
    <section class="hero" data-parallax-root>
        <div class="hero-atmosphere" aria-hidden="true">
            <div class="hero-grid" data-parallax data-depth="0.05"></div>
            <img class="hero-core" src="assets/img/hero/hero-core.png" alt="" data-parallax data-depth="0.16">
            <div class="hero-orbit hero-orbit-one" data-parallax data-depth="0.10"></div>
            <div class="hero-orbit hero-orbit-two" data-parallax data-depth="0.06"></div>
            <div class="hero-shade"></div>
        </div>

        <div class="shell hero-inner">
            <div class="hero-copy" data-reveal>
                <p class="hero-eyebrow"><?= e($hero['eyebrow'] ?? '') ?></p>
                <h1><span>#fikrim</span><strong>var</strong></h1>
                <p class="hero-lead"><?= e($hero['lead'] ?? '') ?></p>
                <p class="hero-body"><?= e($hero['body'] ?? '') ?></p>
                <div class="hero-actions">
                    <a class="button button-light" href="#manifesto"><?= e($hero['primary_label'] ?? 'Günlüğe gir') ?> <?= icon('arrow') ?></a>
                    <a class="text-button" href="hikayeler.php"><?= e($hero['secondary_label'] ?? 'Bütün hikâyeler') ?></a>
                </div>
            </div>

            <div class="hero-compass" aria-label="Sitenin içerik yapısı" data-reveal>
                <a href="hikayeler.php"><strong><?= str_pad((string) $storyCount, 2, '0', STR_PAD_LEFT) ?></strong><span>Hikâye</span></a>
                <a href="#atolye"><strong><?= str_pad((string) max(1, $atelierCount), 2, '0', STR_PAD_LEFT) ?></strong><span>Canlı atölye</span></a>
                <a href="hikayeler.php?kategori=yz-yontem"><strong><?= str_pad((string) $methodCount, 2, '0', STR_PAD_LEFT) ?></strong><span>Yöntem notu</span></a>
                <a href="hikayeler.php?durum=yarim"><strong><?= str_pad((string) $unfinishedCount, 2, '0', STR_PAD_LEFT) ?></strong><span>Açık / yarım dosya</span></a>
            </div>

            <a class="hero-scroll" href="#manifesto"><span></span> Hikâyenin içine gir</a>
        </div>
    </section>

    <div class="home-field" id="manifesto">
        <div class="shell home-field-grid">
            <article class="manifesto-main" data-reveal>
                <p class="eyebrow"><?= e($manifesto['label'] ?? '') ?></p>
                <h2><?= e($manifesto['title'] ?? '') ?></h2>
                <?php foreach (($manifesto['paragraphs'] ?? []) as $paragraph): ?>
                    <p><?= e((string) $paragraph) ?></p>
                <?php endforeach; ?>
            </article>

            <aside class="manifesto-short" data-reveal>
                <span>kısa hâli</span>
                <blockquote><?= e($manifesto['short'] ?? '') ?></blockquote>
            </aside>

            <div class="manifesto-words" aria-hidden="true" data-reveal>
                <?php foreach (($manifesto['words'] ?? []) as $word): ?><span><?= e((string) $word) ?></span><?php endforeach; ?>
            </div>

            <figure class="origin-photo" data-reveal>
                <img src="assets/img/mentor/mehmet-firat-ders.webp" alt="Mehmet Fırat hocanın çevrim içi dersinden arşiv görüntüsü" loading="lazy">
                <figcaption>ARŞİVDEN · İlk cesaretin geldiği ders ekranı</figcaption>
            </figure>

            <article class="origin-mentor" data-reveal>
                <p class="eyebrow"><?= e($origin['label'] ?? '') ?></p>
                <h2><?= e($origin['title'] ?? '') ?></h2>
                <h3><?= e($origin['mentor_title'] ?? '') ?></h3>
                <p><?= e($origin['mentor_text'] ?? '') ?></p>
                <a href="<?= e($origin['mentor_url'] ?? '#') ?>" target="_blank" rel="noopener noreferrer">Akademik profil <?= icon('arrow') ?></a>
            </article>

            <article class="origin-project" data-reveal>
                <span class="origin-number">01</span>
                <p class="eyebrow">İLK YAŞAYAN PROJE</p>
                <h3><?= e($origin['project_title'] ?? '') ?></h3>
                <p><?= e($origin['project_text'] ?? '') ?></p>
                <?php if (isset($stories['uretim-takip'])): ?>
                    <a href="<?= e(story_url($stories['uretim-takip'])) ?>">Başlangıç hikâyesini aç <?= icon('arrow') ?></a>
                <?php endif; ?>
            </article>

            <?php if ($atelier && $latestAtelier):
                $atelierMedia = story_asset($atelier, (string) (($latestAtelier['media']['src'] ?? $atelier['cover'] ?? '')));
            ?>
            <section class="live-workshop" id="atolye" data-reveal data-parallax-root>
                <div class="workshop-stage">
                    <img src="<?= e($atelierMedia) ?>" alt="<?= e($latestAtelier['media']['alt'] ?? $atelier['title'] ?? '') ?>" data-parallax data-depth="0.035" loading="lazy">
                    <span class="live-dot">CANLI KAYIT</span>
                </div>
                <div class="workshop-copy">
                    <p class="eyebrow">ATÖLYEDE ŞİMDİ</p>
                    <span class="workshop-day"><?= e($latestAtelier['day'] ?? '') ?></span>
                    <h2><?= e($latestAtelier['title'] ?? '') ?></h2>
                    <p><?= e($latestAtelier['summary'] ?? '') ?></p>
                    <dl>
                        <div><dt>Denediğim</dt><dd><?= e($latestAtelier['tried'] ?? '') ?></dd></div>
                        <div><dt>Kararım</dt><dd><?= e($latestAtelier['decision'] ?? '') ?></dd></div>
                        <div><dt>Sıradaki</dt><dd><?= e($latestAtelier['next'] ?? '') ?></dd></div>
                    </dl>
                    <a class="button button-rust" href="<?= e(story_url($atelier)) ?>">Atölyeyi gün gün aç <?= icon('arrow') ?></a>
                </div>
            </section>
            <?php endif; ?>

            <div class="story-zone-heading" id="hikayeler" data-reveal>
                <p class="eyebrow">GÜNLÜKTEN İKİ KESİT</p>
                <h2>Projeyi değil, başına gelenleri anlatmak.</h2>
                <p>Ana sayfada hikâyenin özünü görürsün. Ayrıntıya geçtiğinde ilk fikir, kırılma noktaları, yanlış yollar ve bende kalanlar açılır.</p>
            </div>

            <?php foreach ($focusStories as $index => $story):
                $cover = story_asset($story, (string) ($story['cover'] ?? ''));
                $isWide = $index === 0;
            ?>
            <article class="focus-story <?= $isWide ? 'focus-story--wide' : 'focus-story--compact' ?>" data-reveal>
                <a href="<?= e(story_url($story)) ?>">
                    <?php if ($cover !== ''): ?><figure><img src="<?= e($cover) ?>" alt="<?= e($story['title'] ?? '') ?>" loading="lazy"></figure><?php endif; ?>
                    <div>
                        <p><?= e($story['category_label'] ?? '') ?> · <?= e(status_label($story)) ?></p>
                        <h3><?= e($story['question'] ?? $story['title'] ?? '') ?></h3>
                        <span><?= e($story['summary'] ?? '') ?></span>
                        <strong>Hikâyeyi aç · <?= e($story['reading_time'] ?? 'kısa okuma') ?> <?= icon('arrow') ?></strong>
                    </div>
                </a>
            </article>
            <?php endforeach; ?>

            <aside class="story-traces" data-reveal>
                <p class="eyebrow">MASANIN DİĞER TARAFI</p>
                <div>
                    <?php foreach ($traceStories as $index => $story): ?>
                        <a href="<?= e(story_url($story)) ?>">
                            <span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <strong><?= e($story['question'] ?? $story['title'] ?? '') ?></strong>
                            <small><?= e(status_label($story)) ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>

            <a class="all-stories-gateway" href="hikayeler.php" data-reveal>
                <span>BÜTÜN HİKÂYELER</span>
                <strong><?= $storyCount ?> kayıt; çalışan, değişen, yarım kalan ve kapanan aynı defterde.</strong>
                <?= icon('arrow') ?>
            </a>
        </div>
    </div>

    <section class="home-ending">
        <div class="shell home-ending-grid">
            <div data-reveal>
                <p class="eyebrow">AHMET ÇETİN</p>
                <h2>Bir unvan aramıyorum. Aklıma takılanın gerçekten çalışıp çalışmayacağını görmek istiyorum.</h2>
            </div>
            <p data-reveal>Uzun yıllardır üretim ve iş dünyasının içindeyim. Kod, çizim, yapay zekâ, ses ve hareket hayatıma daha sonra girdi. Bu site, öğrendiklerimi tamamlanmış göstermek için değil; nasıl değiştiklerini kaybetmemek için var.</p>
            <div class="home-channels" data-reveal>
                <?php foreach ($channels as $channel):
                    $url = trim((string) ($channel['url'] ?? ''));
                    if ($url === '') continue;
                ?>
                    <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"><?= icon((string) ($channel['id'] ?? 'github')) ?><span><?= e($channel['title'] ?? '') ?></span></a>
                <?php endforeach; ?>
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
        <?php if ($notes === []): ?><p class="empty-note">Henüz yayımlanmış bir kenar notu yok.</p><?php else: ?>
            <?php foreach (array_slice(array_reverse($notes), 0, 5) as $note): ?><blockquote><p><?= e($note['message'] ?? '') ?></p><footer><?= e($note['name'] ?? '') ?></footer></blockquote><?php endforeach; ?>
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
