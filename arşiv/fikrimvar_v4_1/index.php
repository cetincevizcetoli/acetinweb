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
$ack = $siteData['acknowledgement'] ?? [];
$about = $siteData['about'] ?? [];
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
    $hero['featured_slugs'] ?? ['webbordro', 'gorselden-harekete', 'ai-context'],
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
    $hero['other_slugs'] ?? ['kaplumbaga-kabugu', 'isik-catlakli-siluet', 'tablet-krita', 'cyrillic-flow', 'neyzen-3d', 'aof-sinav-v2'],
    static fn(mixed $slug): bool => is_string($slug) && $slug !== ''
));
$otherStories = [];
foreach ($otherSlugs as $slug) {
    if (isset($projectMap[$slug])) {
        $otherStories[] = $projectMap[$slug];
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($site['description'] ?? '') ?>">
    <meta name="theme-color" content="#101214">
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
            <a href="#masada">Masadakiler</a>
            <a href="hikayeler.php">Bütün hikâyeler</a>
            <a href="#baslangic">Başlangıç</a>
            <a href="#ahmet">Ahmet</a>
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
            <img class="hero-core" src="assets/img/hero/hero-core.png" alt="" data-parallax data-depth="0.18">
            <div class="hero-orbit hero-orbit-one" data-parallax data-depth="0.12"></div>
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
                    <a class="button button-light" href="#masada">Şu sıralar masamda <?= icon('arrow') ?></a>
                    <a class="text-button" href="hikayeler.php">Bütün hikâyelere git</a>
                </div>
            </div>

            <div class="hero-project-index" aria-label="Şu sıralar masada açık üç hikâye" data-reveal>
                <p>MASADA AÇIK</p>
                <?php foreach ([$webbordro, $motion, $context] as $index => $project): if ($project === []) continue; ?>
                <a href="#<?= e((string) ($project['slug'] ?? '')) ?>">
                    <span>0<?= $index + 1 ?></span>
                    <strong><?= e($project['title'] ?? '') ?></strong>
                    <small><?= e($project['status'] ?? '') ?></small>
                </a>
                <?php endforeach; ?>
            </div>

            <a class="hero-scroll" href="#masada" aria-label="Şu sıralar masadaki hikâyelere kaydır">
                <span></span> Aşağı kaydır
            </a>
        </div>
    </section>

    <section class="selected-intro" id="masada">
        <div class="shell intro-grid" data-reveal>
            <div>
                <p class="eyebrow">ŞU SIRALAR MASAMDA</p>
                <h2>Üç açık dosya,<br>aynı merak.</h2>
            </div>
            <p>Bunlar “en iyi üç iş” değil; bugün üzerinde düşündüğüm, kullandığım veya geliştirdiğim üç hikâye. Yarın masadaki sıra değişebilir. Bitenler, bekleyenler ve yarım kalanlar ise arşivde birlikte durur.</p>
        </div>
    </section>

    <?php if ($webbordro !== []): ?>
    <section class="feature feature-webbordro" id="webbordro">
        <div class="shell feature-grid">
            <div class="feature-copy" data-reveal>
                <p class="feature-number">01 / ŞU ANDA GELİŞİYOR</p>
                <h2>Bir bordro fikrinin<br><em>üç farklı gövdesi.</em></h2>
                <p class="feature-lead"><?= e($webbordro['summary'] ?? '') ?></p>
                <div class="version-list" aria-label="WebBordro sürümleri">
                    <span>Masaüstü</span><span>Web</span><span>Python</span>
                </div>
                <p><?= e($webbordro['process'] ?? '') ?></p>
                <a class="feature-link" href="<?= e(project_url($webbordro)) ?>">WebBordro hikâyesini aç <?= icon('arrow') ?></a>
            </div>
            <figure class="feature-visual webbordro-visual" data-reveal data-parallax-root>
                <img src="assets/img/featured/webbordro-system.svg" alt="WebBordro masaüstü, web ve Python sürümlerini temsil eden arayüz kompozisyonu" data-parallax data-depth="0.08" loading="lazy">
                <figcaption>Masaüstü, web ve Python sürümleri aynı hesaplama fikrinin farklı gövdeleri.</figcaption>
            </figure>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($motion !== []): ?>
    <section class="feature feature-motion" id="gorselden-harekete" data-parallax-root>
        <div class="motion-lines" aria-hidden="true" data-parallax data-depth="0.07"></div>
        <div class="shell feature-grid feature-grid-reverse">
            <figure class="feature-visual motion-visual" data-reveal>
                <img src="assets/img/featured/invoke-flow.svg" alt="Teknik katmanlardan InvokeAI görseline, ardından Flow ve Meta AI ile hareketli karelere uzanan üretim zinciri" data-parallax data-depth="0.12" loading="lazy">
            </figure>
            <div class="feature-copy" data-reveal>
                <p class="feature-number">02 / ÜZERİNDE ÇALIŞIYORUM</p>
                <h2>Katmanlardan<br><em>harekete.</em></h2>
                <p class="feature-lead"><?= e($motion['summary'] ?? '') ?></p>
                <ol class="pipeline-list">
                    <li><span>01</span><div><strong>Teknik katmanlar</strong><small>Depth, line, normal ve referanslar</small></div></li>
                    <li><span>02</span><div><strong>Görsel üretim</strong><small>InvokeAI, ControlNet ve Flux</small></div></li>
                    <li><span>03</span><div><strong>Hareket ve kurgu</strong><small>Flow, Meta AI ve montaj</small></div></li>
                </ol>
                <a class="feature-link" href="<?= e(project_url($motion)) ?>">Üretim zincirini aç <?= icon('arrow') ?></a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($context !== []): ?>
    <section class="feature feature-context" id="ai-context">
        <div class="shell context-layout">
            <div class="context-heading" data-reveal>
                <p class="feature-number">03 / ÇALIŞIYOR VE KULLANILIYOR</p>
                <h2>Bağlamı elle taşımaktan<br><em>sıkılınca.</em></h2>
            </div>
            <div class="context-copy" data-reveal>
                <p class="feature-lead"><?= e($context['summary'] ?? '') ?></p>
                <p><?= e($context['process'] ?? '') ?></p>
                <a class="feature-link" href="<?= e(project_url($context)) ?>">ai-context kaydını aç <?= icon('arrow') ?></a>
            </div>
            <figure class="context-terminal" data-reveal data-parallax-root>
                <img src="assets/img/featured/ai-context-terminal.svg" alt="ai-context komut satırında WebBordro klasörünü tararken" data-parallax data-depth="0.06" loading="lazy">
            </figure>
        </div>
    </section>
    <?php endif; ?>

    <section class="other-stories" id="diger-hikayeler">
        <div class="shell">
            <div class="other-heading" data-reveal>
                <div>
                    <p class="eyebrow">MASANIN DİĞER TARAFI</p>
                    <h2>Bu üç hikâyeden<br>ibaret değil.</h2>
                </div>
                <p>Kimi çalışıyor, kimi biçim değiştiriyor, kimi bekliyor. Ana sayfada hepsini üst üste yığmadan, atölyenin geri kalanına açık bir kapı bırakıyorum.</p>
            </div>

            <div class="story-peek-list" data-reveal>
                <?php foreach ($otherStories as $index => $project):
                    $category = $categoryMap[(string) ($project['category'] ?? '')] ?? [];
                ?>
                <article class="story-peek">
                    <a href="<?= e(project_url($project)) ?>">
                        <span class="story-peek-number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <div class="story-peek-copy">
                            <p><?= e($category['title'] ?? '') ?> · <?= e($project['status'] ?? '') ?></p>
                            <h3><?= e($project['title'] ?? '') ?></h3>
                            <span><?= e($project['summary'] ?? '') ?></span>
                        </div>
                        <span class="story-peek-arrow" aria-hidden="true"><?= icon('arrow') ?></span>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>

            <a class="stories-gateway" href="hikayeler.php" data-reveal>
                <span>
                    <small><?= count($projects) ?> KAYIT · PROJE · DENEY · YÖNTEM · ARŞİV</small>
                    <strong>Bütün hikâyelere git</strong>
                    <em>Çalışanları, yarım kalanları ve henüz fikir hâlinde olanları aynı yerde gör.</em>
                </span>
                <span class="stories-gateway-arrow"><?= icon('arrow') ?></span>
            </a>
        </div>
    </section>

    <section class="acknowledgement" id="baslangic">
        <div class="shell ack-grid">
            <div class="ack-copy" data-reveal>
                <p class="eyebrow"><?= e($ack['label'] ?? 'BAŞLANGIÇ NOKTASI') ?></p>
                <h2>Mehmet Fırat<br><em>hocama teşekkür.</em></h2>
                <p><?= e($ack['text'] ?? '') ?></p>
                <a class="feature-link" href="<?= e($ack['url'] ?? '#') ?>" target="_blank" rel="noopener noreferrer"><?= e($ack['link_text'] ?? 'Akademik profili aç') ?> <?= icon('arrow') ?></a>
            </div>
            <figure class="ack-image" data-reveal>
                <img src="assets/img/mentor/mehmet-firat-ders.webp" alt="Mehmet Fırat hocanın çevrim içi dersinden arşiv görüntüsü" loading="lazy">
                <figcaption><span>ARŞİVDEN</span> İlk projelerin ve yapay zekâ merakının başlangıç noktası.</figcaption>
            </figure>
        </div>
    </section>

    <section class="about" id="ahmet">
        <div class="shell about-grid" data-reveal>
            <div>
                <p class="eyebrow">AHMET ÇETİN</p>
                <h2><?= e($about['title'] ?? '') ?></h2>
            </div>
            <div class="about-copy">
                <p><?= e($about['text'] ?? '') ?></p>
                <div class="channel-grid">
                    <?php foreach ($channels as $channel):
                        $channelUrl = trim((string) ($channel['url'] ?? ''));
                        $isLinked = $channelUrl !== '' && $channelUrl !== '#';
                        $tag = $isLinked ? 'a' : 'div';
                    ?>
                    <<?= $tag ?> class="channel-item<?= $isLinked ? '' : ' is-pending' ?>"<?= $isLinked ? ' href="' . e($channelUrl) . '" target="_blank" rel="noopener noreferrer"' : '' ?>>
                        <span><?= icon((string) ($channel['id'] ?? '')) ?></span>
                        <div>
                            <strong><?= e($channel['title'] ?? '') ?></strong>
                            <small><?= e($channel['text'] ?? '') ?></small>
                            <?php if (!$isLinked): ?><small class="pending-label">Bağlantı eklenecek</small><?php endif; ?>
                        </div>
                    </<?= $tag ?>>
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
