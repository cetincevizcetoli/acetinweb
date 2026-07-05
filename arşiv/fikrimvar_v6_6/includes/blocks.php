<?php
declare(strict_types=1);

function render_paragraphs(array $paragraphs): void
{
    foreach ($paragraphs as $paragraph) {
        $text = trim((string) $paragraph);
        if ($text !== '') {
            echo '<p>' . e($text) . '</p>';
        }
    }
}

function render_story_block(array $block, array $story, int $index): void
{
    $type = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($block['type'] ?? 'text'))) ?: 'text';
    $layout = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($block['layout'] ?? 'default'))) ?: 'default';
    $classes = 'story-block story-block--' . $type . ' story-layout--' . $layout;
    $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);

    $blockId = $type === 'questions' ? ' id="teknik"' : '';
    echo '<section class="' . e($classes) . '"' . $blockId . ' data-reveal>';
    echo '<span class="story-block-number" aria-hidden="true">' . e($number) . '</span>';

    switch ($type) {
        case 'opening':
            $image = story_asset($story, (string) ($block['image'] ?? ''));
            echo '<div class="story-block-copy">';
            echo '<p class="eyebrow">' . e($block['label'] ?? '') . '</p>';
            echo '<h2>' . e($block['title'] ?? '') . '</h2>';
            render_paragraphs(is_array($block['paragraphs'] ?? null) ? $block['paragraphs'] : []);
            if (!empty($block['quote'])) {
                echo '<blockquote>' . e((string) $block['quote']) . '</blockquote>';
            }
            echo '</div>';
            if ($image !== '') {
                echo '<figure class="story-block-media"><img src="' . e($image) . '" alt="' . e((string) ($block['caption'] ?? $story['title'] ?? '')) . '" loading="lazy"><figcaption>' . e($block['caption'] ?? '') . '</figcaption></figure>';
            }
            break;

        case 'split':
            echo '<div class="story-block-copy">';
            echo '<p class="eyebrow">' . e($block['label'] ?? '') . '</p>';
            echo '<h2>' . e($block['title'] ?? '') . '</h2>';
            render_paragraphs(is_array($block['paragraphs'] ?? null) ? $block['paragraphs'] : []);
            echo '</div>';
            if (!empty($block['image'])) {
                $image = story_asset($story, (string) $block['image']);
                echo '<figure class="story-block-media"><img src="' . e($image) . '" alt="' . e((string) ($block['caption'] ?? '')) . '" loading="lazy"><figcaption>' . e($block['caption'] ?? '') . '</figcaption></figure>';
            }
            break;

        case 'timeline':
            echo '<header class="story-block-heading"><p class="eyebrow">' . e($block['label'] ?? '') . '</p><h2>' . e($block['title'] ?? '') . '</h2></header>';
            if (!empty($block['image'])) {
                $image = story_asset($story, (string) $block['image']);
                echo '<figure class="timeline-map"><img src="' . e($image) . '" alt="' . e((string) ($block['title'] ?? 'Gelişim haritası')) . '" loading="lazy"></figure>';
            }
            echo '<div class="timeline-track">';
            foreach (($block['items'] ?? []) as $item) {
                echo '<article><span>' . e($item['step'] ?? '') . '</span><small>' . e($item['subtitle'] ?? '') . '</small><h3>' . e($item['title'] ?? '') . '</h3><p>' . e($item['text'] ?? '') . '</p></article>';
            }
            echo '</div>';
            break;

        case 'compare':
            echo '<header class="story-block-heading"><p class="eyebrow">' . e($block['label'] ?? '') . '</p><h2>' . e($block['title'] ?? '') . '</h2><p>' . e($block['intro'] ?? '') . '</p></header>';
            echo '<div class="compare-plane">';
            foreach (['left', 'right'] as $side) {
                $column = is_array($block[$side] ?? null) ? $block[$side] : [];
                echo '<div class="compare-column compare-column--' . e($side) . '"><h3>' . e($column['title'] ?? '') . '</h3>';
                foreach (($column['items'] ?? []) as $item) {
                    echo '<p>' . e((string) $item) . '</p>';
                }
                echo '</div>';
            }
            echo '</div>';
            break;

        case 'questions':
            echo '<header class="story-block-heading"><p class="eyebrow">' . e($block['label'] ?? '') . '</p><h2>' . e($block['title'] ?? '') . '</h2></header>';
            echo '<div class="question-stack">';
            foreach (($block['items'] ?? []) as $qIndex => $item) {
                echo '<details' . ($qIndex === 0 ? ' open' : '') . '><summary><span>' . str_pad((string) ($qIndex + 1), 2, '0', STR_PAD_LEFT) . '</span>' . e($item['question'] ?? '') . '</summary><p>' . e($item['answer'] ?? '') . '</p></details>';
            }
            echo '</div>';
            break;

        case 'roles':
            echo '<header class="story-block-heading"><p class="eyebrow">' . e($block['label'] ?? '') . '</p><h2>' . e($block['title'] ?? '') . '</h2><p>' . e($block['note'] ?? '') . '</p></header>';
            echo '<div class="role-cross">';
            echo '<div><small>YAPAY ZEKÂ</small>';
            foreach (($block['ai'] ?? []) as $item) echo '<p>' . e((string) $item) . '</p>';
            echo '</div><div><small>AHMET</small>';
            foreach (($block['human'] ?? []) as $item) echo '<p>' . e((string) $item) . '</p>';
            echo '</div></div>';
            break;

        case 'status':
            echo '<header class="story-block-heading"><p class="eyebrow">' . e($block['label'] ?? '') . '</p><h2>' . e($block['title'] ?? '') . '</h2></header>';
            echo '<div class="status-orbit">';
            foreach (($block['items'] ?? []) as $item) {
                echo '<article><small>' . e($item['state'] ?? '') . '</small><h3>' . e($item['title'] ?? '') . '</h3><p>' . e($item['text'] ?? '') . '</p></article>';
            }
            echo '</div>';
            break;

        case 'lesson':
            echo '<header class="story-block-heading"><p class="eyebrow">' . e($block['label'] ?? '') . '</p><h2>' . e($block['title'] ?? '') . '</h2></header>';
            echo '<ol class="lesson-list">';
            foreach (($block['items'] ?? []) as $item) echo '<li>' . e((string) $item) . '</li>';
            echo '</ol>';
            break;

        case 'code':
            echo '<div class="story-block-copy"><p class="eyebrow">' . e($block['label'] ?? '') . '</p><h2>' . e($block['title'] ?? '') . '</h2><p>' . e($block['note'] ?? '') . '</p></div>';
            echo '<pre class="code-window"><code>' . e($block['code'] ?? '') . '</code></pre>';
            break;

        case 'gallery':
            echo '<header class="story-block-heading"><p class="eyebrow">' . e($block['label'] ?? '') . '</p><h2>' . e($block['title'] ?? '') . '</h2></header>';
            echo '<div class="story-gallery">';
            foreach (($block['items'] ?? []) as $item) {
                $image = story_asset($story, (string) ($item['src'] ?? ''));
                echo '<figure><img src="' . e($image) . '" alt="' . e($item['alt'] ?? '') . '" loading="lazy"><figcaption>' . e($item['caption'] ?? '') . '</figcaption></figure>';
            }
            echo '</div>';
            break;

        case 'video':
            $src = story_asset($story, (string) ($block['src'] ?? ''));
            $poster = story_asset($story, (string) ($block['poster'] ?? ''));
            echo '<div class="story-block-copy"><p class="eyebrow">' . e($block['label'] ?? '') . '</p><h2>' . e($block['title'] ?? '') . '</h2><p>' . e($block['text'] ?? '') . '</p></div>';
            if ($src !== '') {
                echo '<video controls preload="metadata"' . ($poster !== '' ? ' poster="' . e($poster) . '"' : '') . '><source src="' . e($src) . '"></video>';
            }
            break;

        default:
            echo '<div class="story-block-copy"><p class="eyebrow">' . e($block['label'] ?? '') . '</p><h2>' . e($block['title'] ?? '') . '</h2>';
            render_paragraphs(is_array($block['paragraphs'] ?? null) ? $block['paragraphs'] : []);
            echo '</div>';
            break;
    }

    echo '</section>';
}
