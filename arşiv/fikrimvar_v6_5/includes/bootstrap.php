<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const ROOT_DIR = __DIR__ . '/..';
const CONTENT_DIR = ROOT_DIR . '/content';
const STORY_DIR = CONTENT_DIR . '/stories';
const DATA_DIR = ROOT_DIR . '/data';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function load_json_path(string $path, array $fallback = []): array
{
    if (!is_readable($path)) {
        return $fallback;
    }

    try {
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        return is_array($decoded) ? $decoded : $fallback;
    } catch (Throwable) {
        return $fallback;
    }
}

function load_site(): array
{
    return load_json_path(CONTENT_DIR . '/site.json');
}

function safe_slug(string $slug): string
{
    return preg_replace('/[^a-z0-9\-]/', '', strtolower($slug)) ?? '';
}

function load_story(string $slug): ?array
{
    $safe = safe_slug($slug);
    if ($safe === '') {
        return null;
    }

    $path = STORY_DIR . '/' . $safe . '/story.json';
    $story = load_json_path($path);
    if ($story === []) {
        return null;
    }

    $story['_dir'] = 'content/stories/' . $safe;
    $story['_path'] = $path;
    $story['_slug'] = $safe;
    return $story;
}

function load_stories(): array
{
    $stories = [];
    if (!is_dir(STORY_DIR)) {
        return $stories;
    }

    foreach (glob(STORY_DIR . '/*/story.json') ?: [] as $path) {
        $story = load_json_path($path);
        if ($story === []) {
            continue;
        }

        $slug = safe_slug((string) ($story['slug'] ?? basename(dirname($path))));
        if ($slug === '') {
            continue;
        }

        $story['_dir'] = 'content/stories/' . $slug;
        $story['_path'] = $path;
        $story['_slug'] = $slug;
        $stories[$slug] = $story;
    }

    uasort($stories, static function (array $a, array $b): int {
        $dateA = trim((string) ($a['updated_at'] ?? ''));
        $dateB = trim((string) ($b['updated_at'] ?? ''));
        if ($dateA !== '' || $dateB !== '') {
            if ($dateA === '') return 1;
            if ($dateB === '') return -1;
            $dateCompare = strcmp($dateB, $dateA);
            if ($dateCompare !== 0) return $dateCompare;
        }

        return ((float) ($a['order'] ?? 999)) <=> ((float) ($b['order'] ?? 999));
    });

    return $stories;
}

function load_updates(array $story): array
{
    $slug = safe_slug((string) ($story['slug'] ?? $story['_slug'] ?? ''));
    if ($slug === '') {
        return [];
    }

    $updates = [];
    foreach (glob(STORY_DIR . '/' . $slug . '/updates/*.json') ?: [] as $path) {
        $update = load_json_path($path);
        if ($update === []) {
            continue;
        }
        $update['_file'] = basename($path);
        $update['_id'] = safe_slug((string) ($update['slug'] ?? pathinfo($path, PATHINFO_FILENAME)));
        $updates[] = $update;
    }

    usort($updates, static fn(array $a, array $b): int => ((float) ($a['order'] ?? 999)) <=> ((float) ($b['order'] ?? 999)));
    return $updates;
}

function load_recent_updates(int $limit = 4): array
{
    $items = [];
    foreach (load_stories() as $story) {
        if (($story['kind'] ?? '') !== 'atelier') {
            continue;
        }

        foreach (load_updates($story) as $update) {
            $date = trim((string) ($update['date'] ?? ''));
            $timestamp = $date !== '' ? strtotime($date) : false;
            if ($timestamp === false) {
                $storyDate = trim((string) ($story['updated_at'] ?? $story['started_at'] ?? ''));
                $timestamp = $storyDate !== '' ? strtotime($storyDate) : false;
            }
            if ($timestamp === false) {
                $timestamp = 0;
            }

            $items[] = [
                'story' => $story,
                'update' => $update,
                '_timestamp' => $timestamp,
                '_order' => (float) ($update['order'] ?? 999),
            ];
        }
    }

    usort($items, static function (array $a, array $b): int {
        $byDate = ($b['_timestamp'] ?? 0) <=> ($a['_timestamp'] ?? 0);
        if ($byDate !== 0) return $byDate;
        return ($b['_order'] ?? 0) <=> ($a['_order'] ?? 0);
    });

    return array_slice($items, 0, max(1, $limit));
}

function story_asset(array $story, ?string $asset): string
{
    $asset = trim((string) $asset);
    if ($asset === '') {
        return '';
    }

    if (preg_match('~^(https?:)?//~i', $asset) || str_starts_with($asset, 'data:')) {
        return $asset;
    }

    $slug = safe_slug((string) ($story['slug'] ?? $story['_slug'] ?? ''));
    $clean = ltrim(str_replace(['..\\', '../', '\\'], ['', '', '/'], $asset), '/');
    return 'content/stories/' . rawurlencode($slug) . '/' . $clean;
}

function story_url(array $story): string
{
    $slug = rawurlencode((string) ($story['slug'] ?? $story['_slug'] ?? ''));
    if (($story['kind'] ?? 'story') === 'atelier') {
        return 'atolye.php?slug=' . $slug;
    }
    return 'hikaye.php?slug=' . $slug;
}

function category_map(array $site): array
{
    $map = [];
    foreach (($site['categories'] ?? []) as $category) {
        $id = (string) ($category['id'] ?? '');
        if ($id !== '') {
            $map[$id] = (string) ($category['title'] ?? $id);
        }
    }
    return $map;
}

function status_label(array $story): string
{
    return (string) ($story['status_label'] ?? $story['status'] ?? 'Kayıt');
}

function story_search_text(array $story): string
{
    $parts = [
        (string) ($story['title'] ?? ''),
        (string) ($story['question'] ?? ''),
        (string) ($story['summary'] ?? ''),
        (string) ($story['category_label'] ?? ''),
        (string) ($story['status_label'] ?? ''),
        implode(' ', array_map('strval', is_array($story['tags'] ?? null) ? $story['tags'] : []))
    ];
    return implode(' ', $parts);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return (string) $_SESSION['csrf_token'];
}

function icon(string $name): string
{
    $icons = [
        'youtube' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.6 7.2a2.8 2.8 0 0 0-2-2C17.8 4.7 12 4.7 12 4.7s-5.8 0-7.6.5a2.8 2.8 0 0 0-2 2A29 29 0 0 0 2 12a29 29 0 0 0 .4 4.8 2.8 2.8 0 0 0 2 2c1.8.5 7.6.5 7.6.5s5.8 0 7.6-.5a2.8 2.8 0 0 0 2-2A29 29 0 0 0 22 12a29 29 0 0 0-.4-4.8ZM10 15.2V8.8l5.5 3.2-5.5 3.2Z"/></svg>',
        'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.5 2h9A5.5 5.5 0 0 1 22 7.5v9a5.5 5.5 0 0 1-5.5 5.5h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2Zm0 2A3.5 3.5 0 0 0 4 7.5v9A3.5 3.5 0 0 0 7.5 20h9a3.5 3.5 0 0 0 3.5-3.5v-9A3.5 3.5 0 0 0 16.5 4h-9ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2.1a2.9 2.9 0 1 0 0 5.8 2.9 2.9 0 0 0 0-5.8ZM17.6 5.5a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4Z"/></svg>',
        'github' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-3.16 19.49c.5.09.68-.22.68-.48v-1.87c-2.78.6-3.37-1.18-3.37-1.18-.45-1.16-1.11-1.47-1.11-1.47-.91-.62.07-.61.07-.61 1 .07 1.53 1.03 1.53 1.03.9 1.53 2.35 1.09 2.92.83.09-.65.35-1.09.64-1.34-2.22-.25-4.56-1.11-4.56-4.94 0-1.09.39-1.98 1.03-2.68-.1-.25-.45-1.27.1-2.64 0 0 .84-.27 2.75 1.02A9.5 9.5 0 0 1 12 6.82a9.5 9.5 0 0 1 2.5.34c1.91-1.29 2.75-1.02 2.75-1.02.55 1.37.2 2.39.1 2.64.64.7 1.03 1.59 1.03 2.68 0 3.84-2.34 4.68-4.57 4.93.36.31.68.92.68 1.86V21c0 .27.18.58.69.48A10 10 0 0 0 12 2Z"/></svg>',
        'github2' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-3.16 19.49c.5.09.68-.22.68-.48v-1.87c-2.78.6-3.37-1.18-3.37-1.18-.45-1.16-1.11-1.47-1.11-1.47-.91-.62.07-.61.07-.61 1 .07 1.53 1.03 1.53 1.03.9 1.53 2.35 1.09 2.92.83.09-.65.35-1.09.64-1.34-2.22-.25-4.56-1.11-4.56-4.94 0-1.09.39-1.98 1.03-2.68-.1-.25-.45-1.27.1-2.64 0 0 .84-.27 2.75 1.02A9.5 9.5 0 0 1 12 6.82a9.5 9.5 0 0 1 2.5.34c1.91-1.29 2.75-1.02 2.75-1.02.55 1.37.2 2.39.1 2.64.64.7 1.03 1.59 1.03 2.68 0 3.84-2.34 4.68-4.57 4.93.36.31.68.92.68 1.86V21c0 .27.18.58.69.48A10 10 0 0 0 12 2Z"/></svg>',
        'arrow' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'menu' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'close' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'play' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7L8 5Z"/></svg>',
        'pause' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 5h4v14H7zM13 5h4v14h-4z"/></svg>',
    ];
    return $icons[$name] ?? '';
}

function pinned_atelier_context(array $siteData, ?array $stories = null): array
{
    $stories ??= load_stories();
    $home = is_array($siteData['homepage'] ?? null) ? $siteData['homepage'] : [];
    $slug = safe_slug((string) ($home['pinned_atelier'] ?? $home['active_atelier'] ?? ''));
    $story = $slug !== '' ? ($stories[$slug] ?? null) : null;
    if (!$story || ($story['kind'] ?? '') !== 'atelier') {
        return ['story' => null, 'updates' => [], 'latest' => null];
    }

    $updates = load_updates($story);
    $latest = $updates !== [] ? $updates[array_key_last($updates)] : null;
    return ['story' => $story, 'updates' => $updates, 'latest' => $latest];
}

function group_atelier_updates(array $updates): array
{
    $groups = [];
    foreach ($updates as $update) {
        $phase = trim((string) ($update['phase'] ?? 'Günlük kayıtlar')) ?: 'Günlük kayıtlar';
        $groups[$phase] ??= [];
        $groups[$phase][] = $update;
    }
    return $groups;
}
