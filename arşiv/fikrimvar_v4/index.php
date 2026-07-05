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

$featuredSlugs = array_values(array_filter(
    $hero['featured_slugs'] ?? ['webbordro', 'gorselden-harekete', 'ai-context'],
    static fn(mixed $slug): bool => is_string($slug) && $slug !== ''
));
$featured = [];
foreach ($featuredSlugs as $slug) {
    if (isset($projectMap[$slug])) {
        $featured[$slug] = $projectMap[$slug];
    }
}
$webbordro = $featured['webbordro'] ?? [];
$motion = $featured['gorselden-harekete'] ?? [];
$context = $featured['ai-context'] ?? [];

$projectsByCategory = [];
foreach ($categories as $category) {
    $id = (string) ($category['id'] ?? '');
    $projectsByCategory[$id] = array_values(array_filter(
        $projects,
        static fn(array $project): bool => (string) ($project['category'] ?? '') === $id
    ));
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
            <a href="#secili-isler">Seçili işler</a>
            <a href="#arsiv">Arşiv</a>
            <a href="#baslangic">Başlangıç</a>
            <a href="#ahmet">Ahmet</a>
            <button class="notes-trigger" type="button" data-notes-open>Kenar notları</button>
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
                    <a class="button button-light" href="#secili-isler">Seçili çalışmaları gör <?= icon('arrow') ?></a>
                    <button class="text-button" type="button" data-notes-open>Kenar notu bırak</button>
                </div>
            </div>

            <div class="hero-project-index" aria-label="Öne çıkan üç çalışma" data-reveal>
                <p>BU SAYIDA</p>
                <?php foreach ([$webbordro, $motion, $context] as $index => $project): if ($project === []) continue; ?>
                <a href="#<?= e((string) ($project['slug'] ?? '')) ?>">
                    <span>0<?= $index + 1 ?></span>
                    <strong><?= e($project['title'] ?? '') ?></strong>
                    <small><?= e($project['type'] ?? '') ?></small>
                </a>
                <?php endforeach; ?>
            </div>

            <a class="hero-scroll" href="#secili-isler" aria-label="Seçili işlere kaydır">
                <span></span> Aşağı kaydır
            </a>
        </div>
    </section>

    <section class="selected-intro" id="secili-isler">
        <div class="shell intro-grid" data-reveal>
            <div>
                <p class="eyebrow">SEÇİLİ ÜÇ İŞ</p>
                <h2>Üç farklı fikir,<br>üç farklı gövde.</h2>
            </div>
            <p>Ön sayfada bütün arşivi sıralamak yerine, gerçekten emek verdiğim üç işi kendi hikâyesi ve kendi görsel diliyle öne çıkarıyorum.</p>
        </div>
    </section>

    <?php if ($webbordro !== []): ?>
    <section class="feature feature-webbordro" id="webbordro">
        <div class="shell feature-grid">
            <div class="feature-copy" data-reveal>
                <p class="feature-number">01 / KOD VE SİSTEM</p>
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
                <figcaption>Gerçek ekran görüntüleri yerleştirilene kadar projeye özel hazırlanmış görsel taslak.</figcaption>
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
                <p class="feature-number">02 / GÖRSEL VE HAREKET</p>
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
                <p class="feature-number">03 / KÜÇÜK ARAÇ, GERÇEK İHTİYAÇ</p>
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

    <section class="archive" id="arsiv">
        <div class="shell">
            <div class="archive-heading" data-reveal>
                <div>
                    <p class="eyebrow">ATÖLYENİN DİĞER RAFLARI</p>
                    <h2>İstediğin alandan içeri gir.</h2>
                </div>
                <p>Ana sayfada yalnızca üç çalışma öne çıkıyor. Diğer proje, deney ve yöntem kayıtlarına alanlar üzerinden ulaşabilirsin.</p>
            </div>

            <div class="tab-shell" data-tabs data-reveal>
                <div class="tab-list" role="tablist" aria-label="Çalışma alanları">
                    <?php foreach ($categories as $index => $category): ?>
                    <button
                        type="button"
                        role="tab"
                        id="tab-<?= e($category['id'] ?? '') ?>"
                        aria-controls="panel-<?= e($category['id'] ?? '') ?>"
                        aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
                        tabindex="<?= $index === 0 ? '0' : '-1' ?>"
                        data-tab="<?= e($category['id'] ?? '') ?>"
                    ><?= e($category['title'] ?? '') ?></button>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($categories as $index => $category):
                    $id = (string) ($category['id'] ?? '');
                    $categoryProjects = array_slice($projectsByCategory[$id] ?? [], 0, 3);
                ?>
                <section
                    class="tab-panel"
                    role="tabpanel"
                    id="panel-<?= e($id) ?>"
                    aria-labelledby="tab-<?= e($id) ?>"
                    <?= $index === 0 ? '' : 'hidden' ?>
                    data-panel="<?= e($id) ?>"
                >
                    <div class="archive-cards">
                        <?php foreach ($categoryProjects as $project): ?>
                        <article class="archive-card">
                            <a class="archive-card-image" href="<?= e(project_url($project)) ?>">
                                <img src="<?= e($project['thumbnail'] ?? '') ?>" alt="" loading="lazy">
                            </a>
                            <div class="archive-card-copy">
                                <div class="record-meta"><span><?= e($project['status'] ?? '') ?></span><span><?= e($project['type'] ?? '') ?></span></div>
                                <h3><a href="<?= e(project_url($project)) ?>"><?= e($project['title'] ?? '') ?></a></h3>
                                <p><?= e($project['summary'] ?? '') ?></p>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                    <a class="archive-all" href="<?= e(category_url($category)) ?>">Bu alandaki tüm kayıtları gör <?= icon('arrow') ?></a>
                </section>
                <?php endforeach; ?>
            </div>
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
                    <?php foreach ($channels as $channel): ?>
                    <a href="<?= e($channel['url'] ?? '#') ?>" <?= ($channel['url'] ?? '#') !== '#' ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                        <span><?= icon((string) ($channel['id'] ?? '')) ?></span>
                        <div><strong><?= e($channel['title'] ?? '') ?></strong><small><?= e($channel['text'] ?? '') ?></small></div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="shell footer-inner">
        <p>© <?= e($site['year'] ?? date('Y')) ?> Ahmet Çetin · #fikrimvar</p>
        <div><button type="button" data-notes-open>Kenar notları</button><a href="#main">Başa dön ↑</a></div>
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
