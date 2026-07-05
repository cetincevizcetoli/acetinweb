<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$slug = (string) filter_input(INPUT_GET, 'slug', FILTER_UNSAFE_RAW);
$story = load_story($slug);
if (!$story || ($story['kind'] ?? '') !== 'atelier') {
    http_response_code(404);
}

$siteData = load_site();
$site = $siteData['site'] ?? [];
$updates = $story ? load_updates($story) : [];
$activeIndex = max(0, count($updates) - 1);
$active = $updates[$activeIndex] ?? null;
$activeMedia = ($story && $active) ? story_asset($story, (string) ($active['media']['src'] ?? $story['cover'] ?? '')) : '';
$milestones = array_values(array_filter($updates, static fn(array $update): bool => (bool) ($update['milestone'] ?? false)));
if ($milestones === [] && $updates !== []) {
    $milestones = array_values(array_filter($updates, static fn(array $update, int $index): bool => $index === 0 || $index === count($updates) - 1, ARRAY_FILTER_USE_BOTH));
}
$updateGroups = group_atelier_updates($updates);
$activePhase = (string) ($active['phase'] ?? '');

function atelier_entry_attributes(array $story, array $update): string
{
    $media = story_asset($story, (string) ($update['media']['src'] ?? $story['cover'] ?? ''));
    $attrs = [
        'data-update-id' => (string) ($update['_id'] ?? ''),
        'data-media' => $media,
        'data-alt' => (string) ($update['media']['alt'] ?? ''),
        'data-day' => (string) ($update['date_label'] ?? $update['day'] ?? ''),
        'data-title' => (string) ($update['title'] ?? ''),
        'data-summary' => (string) ($update['summary'] ?? ''),
        'data-tried' => (string) ($update['tried'] ?? ''),
        'data-failed' => (string) ($update['failed'] ?? ''),
        'data-decision' => (string) ($update['decision'] ?? ''),
        'data-next' => (string) ($update['next'] ?? ''),
        'data-instagram-url' => (string) ($update['instagram_url'] ?? ''),
        'data-youtube-url' => (string) ($update['youtube_url'] ?? ''),
        'data-github-url' => (string) ($update['github_url'] ?? ''),
    ];
    $parts = [];
    foreach ($attrs as $name => $value) {
        $parts[] = $name . '="' . e($value) . '"';
    }
    return implode(' ', $parts);
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d1014">
    <meta name="description" content="<?= e($story['summary'] ?? 'Canlı atölye günlüğü') ?>">
    <title><?= e($story['title'] ?? 'Atölye') ?> | #FikrimVar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body class="atelier-page">
<a class="skip-link" href="#atelier-main">İçeriğe geç</a>
<header class="inner-header inner-header--dark">
    <div class="shell inner-header-row">
        <a class="brand" href="index.php"><span class="brand-name">AHMET ÇETİN</span><span class="brand-mark">#FikrimVar</span></a>
        <nav aria-label="Atölye menüsü"><a href="hikayeler.php">Bütün hikâyeler</a><a href="#tum-kayitlar">Kayıt arşivi</a><a href="index.php">Ana sayfa</a></nav>
    </div>
</header>

<main id="atelier-main">
<?php if (!$story): ?>
    <section class="not-found"><div class="shell"><p class="eyebrow">404</p><h1>Atölye kaydı bulunamadı.</h1><a class="button button-dark" href="hikayeler.php">Bütün hikâyelere dön <?= icon('arrow') ?></a></div></section>
<?php else: ?>
    <section class="atelier-hero atelier-hero--v65">
        <div class="shell atelier-hero-grid">
            <div data-reveal>
                <p class="eyebrow">CANLI ATÖLYE · <?= e(status_label($story)) ?></p>
                <h1><?= e($story['title'] ?? '') ?></h1>
            </div>
            <div data-reveal>
                <p class="atelier-question"><?= e($story['workshop_question'] ?? $story['question'] ?? '') ?></p>
                <p><?= e($story['summary'] ?? '') ?></p>
                <div class="atelier-meta"><span><?= count($updates) ?> kayıt</span><span><?= count($milestones) ?> dönüm noktası</span><span>Ahmet “bitti” dediğinde kapanır</span></div>
            </div>
        </div>
    </section>

    <?php if ($active): ?>
    <section class="atelier-workbench" data-atelier-console>
        <div class="atelier-stage-wrap">
            <div class="atelier-stage-sticky">
                <div class="atelier-live-label"><span></span> ATÖLYEDE ŞİMDİ</div>
                <figure class="atelier-stage-media">
                    <img src="<?= e($activeMedia) ?>" alt="<?= e($active['media']['alt'] ?? $story['title'] ?? '') ?>" data-atelier-media>
                </figure>
                <div class="atelier-stage-copy">
                    <p data-atelier-day><?= e($active['date_label'] ?? $active['day'] ?? '') ?></p>
                    <h2 data-atelier-title><?= e($active['title'] ?? '') ?></h2>
                    <p data-atelier-summary><?= e($active['summary'] ?? '') ?></p>
                    <dl>
                        <div><dt>Denediğim</dt><dd data-atelier-tried><?= e($active['tried'] ?? '') ?></dd></div>
                        <div><dt>Çalışmayan</dt><dd data-atelier-failed><?= e($active['failed'] ?? '') ?></dd></div>
                        <div><dt>Kararım</dt><dd data-atelier-decision><?= e($active['decision'] ?? '') ?></dd></div>
                        <div><dt>Sıradaki</dt><dd data-atelier-next><?= e($active['next'] ?? '') ?></dd></div>
                    </dl>
                    <nav class="atelier-social-links" aria-label="Bu atölye kaydının sosyal bağlantıları" data-atelier-social>
                        <?php foreach (['instagram_url' => 'Instagram', 'youtube_url' => 'YouTube', 'github_url' => 'GitHub'] as $field => $label):
                            $url = trim((string) ($active[$field] ?? ''));
                        ?>
                            <a href="<?= e($url !== '' ? $url : '#') ?>" target="_blank" rel="noopener noreferrer" data-social-field="<?= e($field) ?>" <?= $url === '' ? 'hidden' : '' ?>><?= e($label) ?></a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            </div>
        </div>

        <div class="atelier-milestones" aria-label="Atölyenin dönüm noktaları">
            <header>
                <p class="eyebrow">DÖNÜM NOKTALARI</p>
                <h2>Her gün değil, yönü değiştiren anlar.</h2>
                <p>Uzun bir atölyede onlarca küçük kayıt olabilir. Burada yalnızca fikri, yöntemi veya hedefi değiştiren adımlar görünüyor.</p>
            </header>
            <div class="milestone-list">
            <?php foreach ($milestones as $index => $update):
                $isActive = ($update['_id'] ?? '') === ($active['_id'] ?? '');
            ?>
                <button class="milestone-entry <?= $isActive ? 'is-active' : '' ?>" type="button"
                    data-atelier-entry <?= atelier_entry_attributes($story, $update) ?>>
                    <span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <small><?= e($update['phase'] ?? $update['date_label'] ?? $update['day'] ?? '') ?></small>
                    <strong><?= e($update['title'] ?? '') ?></strong>
                    <em><?= e($update['summary'] ?? '') ?></em>
                </button>
            <?php endforeach; ?>
            </div>
            <a class="atelier-archive-jump" href="#tum-kayitlar">Tüm çalışma günlüğünü aç · <?= count($updates) ?> kayıt <?= icon('arrow') ?></a>
        </div>
    </section>

    <section class="atelier-archive" id="tum-kayitlar">
        <div class="shell atelier-archive-layout">
            <header>
                <p class="eyebrow">HAM GÜNLÜK</p>
                <h2>Bütün kayıtlar burada; ama hepsi aynı anda önünde değil.</h2>
                <p>Dönemleri açarak küçük denemelere, ara kararlara ve sosyal medya bağlantılarına ulaşabilirsin.</p>
            </header>
            <div class="atelier-phase-groups">
                <?php foreach ($updateGroups as $phase => $phaseUpdates): ?>
                <details class="atelier-phase" <?= $phase === $activePhase ? 'open' : '' ?>>
                    <summary><span><?= e($phase) ?></span><small><?= count($phaseUpdates) ?> kayıt</small></summary>
                    <div class="atelier-phase-list">
                        <?php foreach ($phaseUpdates as $update): ?>
                        <button type="button" class="atelier-archive-entry" id="update-<?= e($update['_id'] ?? '') ?>" data-atelier-entry <?= atelier_entry_attributes($story, $update) ?>>
                            <time datetime="<?= e($update['date'] ?? '') ?>"><?= e($update['date_label'] ?? $update['day'] ?? '') ?></time>
                            <strong><?= e($update['title'] ?? '') ?></strong>
                            <span><?= e($update['summary'] ?? '') ?></span>
                            <?php if ((bool) ($update['milestone'] ?? false)): ?><em>Dönüm noktası</em><?php endif; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="atelier-rule">
        <div class="shell atelier-rule-grid">
            <p class="eyebrow">ATÖLYENİN KAPANIŞ KURALI</p>
            <h2>Bir projenin tamamlanması şart değil.</h2>
            <p>Bu günlük, hedeflediğim yere geldiğimde, öğrenmek istediğim şeyi öğrendiğimde veya artık devam etmek istemediğimde kapanır. “Bu hâliyle bitti” de bir sonuçtur; “yarım bıraktım” da.</p>
        </div>
    </section>

    <section class="story-signature story-signature--dark">
        <div class="shell story-signature-grid">
            <p class="eyebrow">#FikrimVar</p>
            <blockquote>Kusursuz olmak değil; denemek, yanılmak, öğrenmek ve fikri hayata geçirmek.</blockquote>
            <nav><a href="hikayeler.php">Bütün hikâyeler <?= icon('arrow') ?></a><a href="index.php">Ana sayfa <?= icon('arrow') ?></a></nav>
        </div>
    </section>
<?php endif; ?>
</main>
<footer class="site-footer site-footer--dark"><div class="shell footer-inner"><p>© <?= e($site['year'] ?? date('Y')) ?> Ahmet Çetin · #FikrimVar</p><a href="index.php">Ana sayfaya dön</a></div></footer>
</body>
</html>
