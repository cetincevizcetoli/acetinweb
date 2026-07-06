<?php
declare(strict_types=1);

function render_paragraphs_text(string $text): void
{
    foreach (preg_split('/\R{2,}/u', trim($text)) ?: [] as $p) {
        $p = trim($p);
        if ($p !== '') echo '<p>' . e($p) . '</p>';
    }
}

function story_section_primary_media(array $section): ?array
{
    $path = trim((string)($section['media_path'] ?? ''));
    if ($path === '') return null;

    return [
        'media_id' => (int)($section['media_id'] ?? 0),
        'relative_path' => $path,
        'media_type' => (string)($section['media_type'] ?? 'image'),
        'mime_type' => (string)($section['media_mime_type'] ?? ''),
        'title' => (string)($section['media_title'] ?? ''),
        'original_name' => (string)($section['media_original_name'] ?? ''),
        'alt_text' => (string)($section['media_alt'] ?? ''),
        'caption' => (string)($section['media_caption'] ?? ''),
        'caption_override' => '',
    ];
}

function story_section_all_media(array $section): array
{
    $all = [];
    $primary = story_section_primary_media($section);

    if ($primary) {
        $key = $primary['media_id'] > 0 ? 'id:' . $primary['media_id'] : 'path:' . $primary['relative_path'];
        $all[$key] = $primary;
    }

    foreach (($section['media'] ?? []) as $media) {
        $id = (int)($media['media_id'] ?? 0);
        $path = (string)($media['relative_path'] ?? '');
        if ($path === '') continue;
        $key = $id > 0 ? 'id:' . $id : 'path:' . $path;
        $all[$key] = $media;
    }

    return array_values($all);
}

function render_story_media_item(array $media, string $figureClass = ''): void
{
    $src = media_url((string)($media['relative_path'] ?? ''));
    if ($src === '') return;

    $type = (string)($media['media_type'] ?? 'file');
    $mime = (string)($media['mime_type'] ?? '');
    $title = trim((string)($media['title'] ?? $media['original_name'] ?? ''));
    $alt = trim((string)($media['alt_text'] ?? $title));
    $caption = trim((string)($media['caption_override'] ?? '')) ?: trim((string)($media['caption'] ?? ''));
    $class = $figureClass !== '' ? ' class="' . e($figureClass) . '"' : '';

    echo '<figure' . $class . '>';
    if ($type === 'video') {
        echo '<video controls playsinline preload="metadata">';
        echo '<source src="' . e($src) . '"' . ($mime !== '' ? ' type="' . e($mime) . '"' : '') . '>';
        echo 'Tarayıcınız bu videoyu oynatamıyor. <a href="' . e($src) . '">Videoyu aç</a>';
        echo '</video>';
    } elseif ($type === 'audio') {
        echo '<audio controls preload="metadata">';
        echo '<source src="' . e($src) . '"' . ($mime !== '' ? ' type="' . e($mime) . '"' : '') . '>';
        echo 'Tarayıcınız bu sesi oynatamıyor. <a href="' . e($src) . '">Sesi aç</a>';
        echo '</audio>';
    } elseif ($type === 'image') {
        echo '<img src="' . e($src) . '" alt="' . e($alt) . '" loading="lazy">';
    } else {
        $label = $title !== '' ? $title : 'Dosyayı aç';
        echo '<a class="story-file-link" href="' . e($src) . '" target="_blank" rel="noopener">' . e($label) . ' ' . icon('arrow') . '</a>';
    }

    if ($caption !== '') echo '<figcaption>' . e($caption) . '</figcaption>';
    echo '</figure>';
}

function render_story_media_collection(array $media, string $class = 'story-extra-media'): void
{
    if ($media === []) return;
    echo '<div class="' . e($class) . '">';
    foreach ($media as $item) render_story_media_item($item);
    echo '</div>';
}

function render_story_section_extras(array $section, string $type, bool $primaryRendered): void
{
    if ($type !== 'compare' && trim((string)($section['intro_text'] ?? '')) !== '') {
        echo '<div class="story-section-intro">';
        render_paragraphs_text((string)$section['intro_text']);
        echo '</div>';
    }

    if (!in_array($type, ['roles', 'code'], true) && trim((string)($section['note_text'] ?? '')) !== '') {
        echo '<aside class="story-section-note"><strong>Kenar notu</strong>';
        render_paragraphs_text((string)$section['note_text']);
        echo '</aside>';
    }

    if ($type !== 'code' && trim((string)($section['code_text'] ?? '')) !== '') {
        echo '<pre class="code-window story-section-code"><code>' . e((string)$section['code_text']) . '</code></pre>';
    }

    $media = story_section_all_media($section);
    if ($primaryRendered) {
        $primaryId = (int)($section['media_id'] ?? 0);
        $primaryPath = (string)($section['media_path'] ?? '');
        $media = array_values(array_filter($media, static function (array $item) use ($primaryId, $primaryPath): bool {
            if ($primaryId > 0 && (int)($item['media_id'] ?? 0) === $primaryId) return false;
            return $primaryPath === '' || (string)($item['relative_path'] ?? '') !== $primaryPath;
        }));
    }

    if (!in_array($type, ['gallery', 'video'], true) && $media !== []) {
        render_story_media_collection($media, 'story-extra-media');
    }

    render_external_links(is_array($section['links'] ?? null) ? $section['links'] : []);
}

function render_story_section(array $section, int $index): void
{
    $type = (string)$section['type'];
    $layout = (string)$section['layout'];
    $number = str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT);
    $id = $type === 'questions' ? ' id="teknik"' : '';
    $items = $section['items'] ?? [];
    $primary = story_section_primary_media($section);
    $primaryRendered = false;

    echo '<section class="story-block story-block--' . e($type) . ' story-layout--' . e($layout) . '"' . $id . ' data-reveal>';
    echo '<span class="story-block-number" aria-hidden="true">' . e($number) . '</span>';

    switch ($type) {
        case 'opening':
        case 'split':
            echo '<div class="story-block-copy"><p class="eyebrow">' . e($section['label']) . '</p><h2>' . e($section['title']) . '</h2>';
            render_paragraphs_text((string)$section['body_text']);
            if ($section['quote_text'] !== '') echo '<blockquote>' . e($section['quote_text']) . '</blockquote>';
            echo '</div>';
            if ($primary) {
                render_story_media_item($primary, 'story-block-media');
                $primaryRendered = true;
            }
            break;

        case 'timeline':
            echo '<header class="story-block-heading"><p class="eyebrow">' . e($section['label']) . '</p><h2>' . e($section['title']) . '</h2></header>';
            if ($primary) {
                echo '<div class="timeline-map">';
                render_story_media_item($primary);
                echo '</div>';
                $primaryRendered = true;
            }
            echo '<div class="timeline-track">';
            foreach ($items as $it) echo '<article><span>' . e($it['step']) . '</span><small>' . e($it['subtitle']) . '</small><h3>' . e($it['title']) . '</h3><p>' . e($it['text']) . '</p></article>';
            echo '</div>';
            break;

        case 'compare':
            echo '<header class="story-block-heading"><p class="eyebrow">' . e($section['label']) . '</p><h2>' . e($section['title']) . '</h2><p>' . e($section['intro_text']) . '</p></header><div class="compare-plane">';
            foreach (['left', 'right'] as $g) {
                $group = array_values(array_filter($items, fn($x) => $x['group_key'] === $g));
                $heading = '';
                foreach ($group as $it) if ($it['item_type'] === 'heading') $heading = $it['title'];
                echo '<div class="compare-column compare-column--' . e($g) . '"><h3>' . e($heading) . '</h3>';
                foreach ($group as $it) if ($it['item_type'] === 'bullet') echo '<p>' . e($it['text']) . '</p>';
                echo '</div>';
            }
            echo '</div>';
            break;

        case 'questions':
            echo '<header class="story-block-heading"><p class="eyebrow">' . e($section['label']) . '</p><h2>' . e($section['title']) . '</h2></header><div class="question-stack">';
            foreach (array_values($items) as $i => $it) echo '<details' . ($i === 0 ? ' open' : '') . '><summary><span>' . str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) . '</span>' . e($it['title']) . '</summary><p>' . e($it['text']) . '</p></details>';
            echo '</div>';
            break;

        case 'roles':
            echo '<header class="story-block-heading"><p class="eyebrow">' . e($section['label']) . '</p><h2>' . e($section['title']) . '</h2><p>' . e($section['note_text']) . '</p></header><div class="role-cross">';
            foreach (['ai' => 'YAPAY ZEKÂ', 'human' => 'AHMET'] as $g => $title) {
                echo '<div><small>' . $title . '</small>';
                foreach ($items as $it) if ($it['group_key'] === $g) echo '<p>' . e($it['text']) . '</p>';
                echo '</div>';
            }
            echo '</div>';
            break;

        case 'status':
            echo '<header class="story-block-heading"><p class="eyebrow">' . e($section['label']) . '</p><h2>' . e($section['title']) . '</h2></header><div class="status-orbit">';
            foreach ($items as $it) echo '<article><small>' . e($it['state']) . '</small><h3>' . e($it['title']) . '</h3><p>' . e($it['text']) . '</p></article>';
            echo '</div>';
            break;

        case 'lesson':
            echo '<header class="story-block-heading"><p class="eyebrow">' . e($section['label']) . '</p><h2>' . e($section['title']) . '</h2></header><ol class="lesson-list">';
            foreach ($items as $it) echo '<li>' . e($it['text']) . '</li>';
            echo '</ol>';
            break;

        case 'code':
            echo '<div class="story-block-copy"><p class="eyebrow">' . e($section['label']) . '</p><h2>' . e($section['title']) . '</h2><p>' . e($section['note_text']) . '</p></div><pre class="code-window"><code>' . e($section['code_text']) . '</code></pre>';
            break;

        case 'gallery':
            echo '<header class="story-block-heading"><p class="eyebrow">' . e($section['label']) . '</p><h2>' . e($section['title']) . '</h2></header>';
            render_story_media_collection(story_section_all_media($section), 'story-gallery story-gallery--mixed');
            $primaryRendered = true;
            break;

        case 'video':
            echo '<div class="story-block-copy"><p class="eyebrow">' . e($section['label']) . '</p><h2>' . e($section['title']) . '</h2>';
            render_paragraphs_text((string)$section['body_text']);
            echo '</div>';
            render_story_media_collection(story_section_all_media($section), 'story-video-media');
            $primaryRendered = true;
            break;

        default:
            echo '<div class="story-block-copy"><p class="eyebrow">' . e($section['label']) . '</p><h2>' . e($section['title']) . '</h2>';
            render_paragraphs_text((string)$section['body_text']);
            if ($section['quote_text'] !== '') echo '<blockquote>' . e($section['quote_text']) . '</blockquote>';
            echo '</div>';
            if ($primary) {
                render_story_media_item($primary, 'story-block-media');
                $primaryRendered = true;
            }
    }

    render_story_section_extras($section, $type, $primaryRendered);
    echo '</section>';
}

function render_media_items(array $media, string $class = 'atelier-media-grid'): void
{
    if ($media === []) return;
    echo '<div class="' . e($class) . '">';
    foreach ($media as $m) render_story_media_item($m);
    echo '</div>';
}

function render_external_links(array $links): void
{
    if ($links === []) return;
    echo '<nav class="content-links" aria-label="İlgili bağlantılar">';
    foreach ($links as $l) echo '<a href="' . e($l['url']) . '" target="_blank" rel="noopener noreferrer">' . e($l['title']) . ' ' . icon('arrow') . '</a>';
    echo '</nav>';
}
