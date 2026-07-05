<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Bu araç yalnızca komut satırından çalışır.\n");
    exit(1);
}

$slug = strtolower(trim((string) ($argv[1] ?? '')));
$title = trim((string) ($argv[2] ?? 'Yeni Hikâye'));
$kind = trim((string) ($argv[3] ?? 'story'));
$slug = preg_replace('/[^a-z0-9\-]/', '-', $slug) ?? '';
$slug = trim(preg_replace('/-+/', '-', $slug) ?? '', '-');
if ($slug === '') {
    fwrite(STDERR, "Kullanım: php tools/new_story.php yeni-hikaye \"Başlık\" [story|atelier]\n");
    exit(1);
}
if (!in_array($kind, ['story', 'atelier'], true)) {
    fwrite(STDERR, "Tür story veya atelier olmalı.\n");
    exit(1);
}

$root = dirname(__DIR__) . '/content/stories/' . $slug;
if (is_dir($root)) {
    fwrite(STDERR, "Bu slug zaten var: {$slug}\n");
    exit(1);
}
mkdir($root . '/media', 0775, true);
mkdir($root . '/updates', 0775, true);

$story = [
    'slug' => $slug,
    'title' => $title,
    'question' => $title,
    'summary' => 'Bu hikâyenin kısa açıklamasını yaz.',
    'kind' => $kind,
    'category' => 'kod-sistem',
    'category_label' => 'Kod ve sistemler',
    'status' => 'suruyor',
    'status_label' => 'Üzerinde çalışıyorum',
    'type_label' => $kind === 'atelier' ? 'Canlı atölye' : 'Proje hikâyesi',
    'order' => 50,
    'started_at' => null,
    'updated_at' => null,
    'cover' => 'media/cover.svg',
    'tags' => [],
    'homepage' => false,
    'workshop_question' => $kind === 'atelier' ? 'Bu fikir gün gün nasıl değişecek?' : null,
    'blocks' => [[
        'type' => 'opening',
        'layout' => 'hero-split',
        'label' => 'NEDEN BAŞLADI?',
        'title' => 'İlk soruyu buraya yaz.',
        'paragraphs' => ['İlk paragrafı yaz.'],
        'image' => 'media/cover.svg',
        'caption' => $title
    ]]
];

$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 720"><rect width="1200" height="720" fill="#11151a"/><path d="M80 590 C310 150 710 760 1120 180" fill="none" stroke="#ad5535" stroke-width="8"/><circle cx="820" cy="330" r="170" fill="none" stroke="#efe6da" stroke-opacity=".6" stroke-width="3"/></svg>';
file_put_contents($root . '/media/cover.svg', $svg);
file_put_contents($root . '/story.json', json_encode($story, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

if ($kind === 'atelier') {
    $update = [
        'order' => 1,
        'slug' => 'gun-01',
        'date' => null,
        'day' => 'Gün 01',
        'title' => 'İlk günün başlığını yaz.',
        'summary' => 'Bugün ne olduğunu iki veya üç cümleyle anlat.',
        'tried' => 'Neyi denedin?',
        'failed' => 'Ne çalışmadı?',
        'decision' => 'Hangi kararı verdin?',
        'next' => 'Sırada ne var?',
        'media' => [
            'type' => 'image',
            'src' => 'media/cover.svg',
            'alt' => $title
        ],
        'instagram_url' => '',
        'youtube_url' => '',
        'github_url' => ''
    ];
    file_put_contents($root . '/updates/001.json', json_encode($update, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

fwrite(STDOUT, "Oluşturuldu: content/stories/{$slug}
");
