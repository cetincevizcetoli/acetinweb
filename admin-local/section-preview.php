<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admin_require_login();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    exit('Bolum id eksik.');
}

$st = db()->prepare(
    'SELECT ss.*, s.project_id, p.slug project_slug, p.title project_title
     FROM story_sections ss
     JOIN stories s ON s.id = ss.story_id
     JOIN projects p ON p.id = s.project_id
     WHERE ss.id=? AND ss.deleted_at IS NULL'
);
$st->execute([$id]);
$sectionRow = $st->fetch();
if (!$sectionRow) {
    http_response_code(404);
    exit('Bolum yok.');
}

$sections = story_sections((int)$sectionRow['story_id']);
$section = null;
$sectionIndex = 0;
foreach ($sections as $index => $candidate) {
    if ((int)$candidate['id'] === $id) {
        $section = $candidate;
        $sectionIndex = $index;
        break;
    }
}

if (!$section) {
    http_response_code(404);
    exit('Bolum yuklenemedi.');
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <base href="../">
    <title>Bolum onizleme</title>
    <?= public_theme_boot_script() ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/story.css')) ?>">
</head>
<body class="story-page admin-section-preview-page">
<main class="story-composition admin-section-preview-shell">
    <?php render_story_section($section, $sectionIndex, count($sections)); ?>
</main>
</body>
</html>
