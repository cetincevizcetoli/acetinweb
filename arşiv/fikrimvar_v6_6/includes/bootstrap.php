<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const ROOT_DIR = __DIR__ . '/..';
const CONTENT_DIR = ROOT_DIR . '/content';
/* Kept for URL and file compatibility. Every folder is now a project record. */
const STORY_DIR = CONTENT_DIR . '/stories';
const DATA_DIR = ROOT_DIR . '/data';
const BACKUP_DIR = DATA_DIR . '/backups';
const ADMIN_AUTH_PATH = DATA_DIR . '/admin-auth.json';

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

function ensure_directory(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('Klasör oluşturulamadı: ' . $path);
    }
}

function backup_existing_file(string $path): ?string
{
    if (!is_file($path)) {
        return null;
    }

    $relative = str_replace(['\\', ROOT_DIR . '/'], ['/', ''], $path);
    $stamp = date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    $target = BACKUP_DIR . '/' . $stamp . '/' . $relative;
    ensure_directory(dirname($target));
    if (!copy($path, $target)) {
        throw new RuntimeException('Yedek alınamadı: ' . $relative);
    }
    return $target;
}

function atomic_write_text(string $path, string $content, bool $backup = true): void
{
    ensure_directory(dirname($path));
    if ($backup && is_file($path)) {
        backup_existing_file($path);
    }

    $temp = $path . '.tmp-' . bin2hex(random_bytes(5));
    if (file_put_contents($temp, $content, LOCK_EX) === false) {
        throw new RuntimeException('Geçici dosya yazılamadı: ' . $path);
    }

    if (!@rename($temp, $path)) {
        @unlink($path);
        if (!@rename($temp, $path)) {
            @unlink($temp);
            throw new RuntimeException('Dosya yerine taşınamadı: ' . $path);
        }
    }
}

function atomic_write_json(string $path, array $data, bool $backup = true): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    /* Validate the exact payload before replacing the current file. */
    json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    atomic_write_text($path, $json, $backup);
}

function load_site(): array
{
    return load_json_path(CONTENT_DIR . '/site.json');
}

function save_site(array $site): void
{
    atomic_write_json(CONTENT_DIR . '/site.json', $site);
}

function safe_slug(string $slug): string
{
    $slug = strtolower(trim($slug));
    $slug = strtr($slug, [
        'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u',
        'Ç' => 'c', 'Ğ' => 'g', 'İ' => 'i', 'Ö' => 'o', 'Ş' => 's', 'Ü' => 'u',
    ]);
    $slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug) ?? '';
    return trim(preg_replace('/-+/', '-', $slug) ?? '', '-');
}

function project_dir(string $slug): string
{
    return STORY_DIR . '/' . safe_slug($slug);
}

function project_path(string $slug): string
{
    return project_dir($slug) . '/project.json';
}

function story_path(string $slug): string
{
    return project_dir($slug) . '/story.json';
}

function update_dir(string $slug): string
{
    return project_dir($slug) . '/updates';
}

function media_dir(string $slug): string
{
    return project_dir($slug) . '/media';
}

function legacy_project_from_story(array $story, string $slug): array
{
    $isAtelier = (($story['kind'] ?? 'story') === 'atelier');
    return [
        'schema_version' => 2,
        'slug' => $slug,
        'title' => (string) ($story['title'] ?? $slug),
        'question' => (string) ($story['question'] ?? $story['title'] ?? ''),
        'summary' => (string) ($story['summary'] ?? ''),
        'category' => (string) ($story['category'] ?? 'kod-sistem'),
        'category_label' => (string) ($story['category_label'] ?? ''),
        'status' => (string) ($story['status'] ?? 'suruyor'),
        'status_label' => (string) ($story['status_label'] ?? 'Kayıt'),
        'type_label' => (string) ($story['type_label'] ?? 'Proje'),
        'order' => (float) ($story['order'] ?? 50),
        'started_at' => $story['started_at'] ?? null,
        'updated_at' => $story['updated_at'] ?? null,
        'cover' => (string) ($story['cover'] ?? 'media/cover.svg'),
        'tags' => is_array($story['tags'] ?? null) ? $story['tags'] : [],
        'homepage' => (bool) ($story['homepage'] ?? false),
        'public' => true,
        'workshop_question' => $story['workshop_question'] ?? null,
        'workshop' => [
            'status' => $isAtelier ? 'open' : 'none',
            'started_at' => $story['started_at'] ?? null,
            'ended_at' => null,
            'closing_state' => null,
            'closing_note' => '',
        ],
        'story' => [
            'status' => $isAtelier ? 'none' : 'published',
            'published_at' => $isAtelier ? null : ($story['updated_at'] ?? null),
            'generated_from_updates' => [],
        ],
    ];
}

function normalize_project(array $project, array $storyData, string $slug, string $projectFile, string $storyFile): array
{
    $merged = array_replace($storyData, $project);
    $merged['blocks'] = is_array($storyData['blocks'] ?? null) ? $storyData['blocks'] : [];
    $merged['slug'] = $slug;
    $merged['_slug'] = $slug;
    $merged['_dir'] = 'content/stories/' . $slug;
    $merged['_project_path'] = $projectFile;
    $merged['_story_path'] = $storyFile;
    $merged['_project'] = $project;
    $merged['_story'] = $storyData;

    $workshop = is_array($project['workshop'] ?? null) ? $project['workshop'] : [];
    $story = is_array($project['story'] ?? null) ? $project['story'] : [];
    $workshopStatus = (string) ($workshop['status'] ?? 'none');
    $storyStatus = (string) ($story['status'] ?? 'none');
    $merged['workshop'] = array_replace([
        'status' => 'none', 'started_at' => null, 'ended_at' => null,
        'closing_state' => null, 'closing_note' => '',
    ], $workshop);
    $merged['story'] = array_replace([
        'status' => 'none', 'published_at' => null, 'generated_from_updates' => [],
    ], $story);
    $merged['workshop_status'] = $workshopStatus;
    $merged['story_status'] = $storyStatus;
    $merged['kind'] = in_array($workshopStatus, ['open', 'paused'], true) ? 'atelier' : 'story';
    return $merged;
}

function load_project(string $slug): ?array
{
    $safe = safe_slug($slug);
    if ($safe === '') {
        return null;
    }

    $projectFile = project_path($safe);
    $storyFile = story_path($safe);
    $storyData = load_json_path($storyFile);
    $project = load_json_path($projectFile);

    if ($project === [] && $storyData === []) {
        return null;
    }
    if ($project === []) {
        $project = legacy_project_from_story($storyData, $safe);
    }
    return normalize_project($project, $storyData, $safe, $projectFile, $storyFile);
}

function workshop_status(array $project): string
{
    return (string) ($project['workshop']['status'] ?? $project['workshop_status'] ?? 'none');
}

function story_status(array $project): string
{
    return (string) ($project['story']['status'] ?? $project['story_status'] ?? 'none');
}

function workshop_is_active(array $project): bool
{
    return in_array(workshop_status($project), ['open', 'paused'], true);
}

function workshop_exists(array $project): bool
{
    return workshop_status($project) !== 'none' || is_dir(update_dir((string) ($project['slug'] ?? '')));
}

function story_is_published(array $project): bool
{
    return story_status($project) === 'published';
}

function project_is_public(array $project): bool
{
    if (($project['public'] ?? true) !== true) {
        return false;
    }
    return workshop_is_active($project) || story_is_published($project) || workshop_status($project) === 'closed';
}

function load_projects(bool $includeDrafts = false): array
{
    $projects = [];
    if (!is_dir(STORY_DIR)) {
        return $projects;
    }

    $dirs = glob(STORY_DIR . '/*', GLOB_ONLYDIR) ?: [];
    foreach ($dirs as $dir) {
        $slug = safe_slug(basename($dir));
        if ($slug === '' || str_starts_with($slug, '_')) {
            continue;
        }
        $project = load_project($slug);
        if (!$project) {
            continue;
        }
        if (!$includeDrafts && !project_is_public($project)) {
            continue;
        }
        $projects[$slug] = $project;
    }

    uasort($projects, static function (array $a, array $b): int {
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

    return $projects;
}

/* Backwards-compatible public API used by existing templates. */
function load_story(string $slug): ?array { return load_project($slug); }
function load_stories(): array { return load_projects(false); }

function save_project_record(array $project): void
{
    $slug = safe_slug((string) ($project['slug'] ?? ''));
    if ($slug === '') {
        throw new InvalidArgumentException('Geçerli bir proje slug değeri gerekli.');
    }
    unset($project['_slug'], $project['_dir'], $project['_project_path'], $project['_story_path'], $project['_project'], $project['_story'], $project['blocks'], $project['kind'], $project['workshop_status'], $project['story_status']);
    $project['schema_version'] = 2;
    $project['slug'] = $slug;
    atomic_write_json(project_path($slug), $project);
}

function save_story_record(string $slug, array $story): void
{
    $safe = safe_slug($slug);
    if ($safe === '') {
        throw new InvalidArgumentException('Geçerli bir proje slug değeri gerekli.');
    }
    $story['slug'] = $safe;
    atomic_write_json(story_path($safe), $story);
}

function load_updates(array $project): array
{
    $slug = safe_slug((string) ($project['slug'] ?? $project['_slug'] ?? ''));
    if ($slug === '') {
        return [];
    }

    $updates = [];
    foreach (glob(update_dir($slug) . '/*.json') ?: [] as $path) {
        $update = load_json_path($path);
        if ($update === []) {
            continue;
        }
        $update['_file'] = basename($path);
        $update['_id'] = safe_slug((string) ($update['slug'] ?? pathinfo($path, PATHINFO_FILENAME)));
        $update['_path'] = $path;
        $updates[] = $update;
    }

    usort($updates, static function (array $a, array $b): int {
        $order = ((float) ($a['order'] ?? 999)) <=> ((float) ($b['order'] ?? 999));
        if ($order !== 0) return $order;
        return strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
    });
    return $updates;
}

function next_update_number(string $slug): int
{
    $files = glob(update_dir($slug) . '/*.json') ?: [];
    $max = 0;
    foreach ($files as $file) {
        if (preg_match('/^(\d+)/', basename($file), $match)) {
            $max = max($max, (int) $match[1]);
        }
    }
    return $max + 1;
}

function update_path(string $slug, string $id): string
{
    $safeId = safe_slug($id);
    foreach (glob(update_dir($slug) . '/*.json') ?: [] as $path) {
        $data = load_json_path($path);
        $candidate = safe_slug((string) ($data['slug'] ?? pathinfo($path, PATHINFO_FILENAME)));
        if ($candidate === $safeId || pathinfo($path, PATHINFO_FILENAME) === $id) {
            return $path;
        }
    }
    return update_dir($slug) . '/' . str_pad((string) next_update_number($slug), 3, '0', STR_PAD_LEFT) . '.json';
}

function save_update_record(string $slug, array $update, ?string $existingId = null): string
{
    $safe = safe_slug($slug);
    ensure_directory(update_dir($safe));
    $number = $existingId === null ? next_update_number($safe) : (int) preg_replace('/\D+/', '', basename(update_path($safe, $existingId)));
    if ($number <= 0) $number = next_update_number($safe);
    $path = $existingId !== null ? update_path($safe, $existingId) : update_dir($safe) . '/' . str_pad((string) $number, 3, '0', STR_PAD_LEFT) . '.json';
    $update['order'] = (float) ($update['order'] ?? $number);
    $update['slug'] = safe_slug((string) ($update['slug'] ?? $update['title'] ?? ('kayit-' . $number)));
    atomic_write_json($path, $update);
    return $path;
}

function load_recent_updates(int $limit = 4): array
{
    $items = [];
    foreach (load_projects(false) as $project) {
        if (!workshop_is_active($project)) {
            continue;
        }
        foreach (load_updates($project) as $update) {
            $date = trim((string) ($update['date'] ?? ''));
            $timestamp = $date !== '' ? strtotime($date) : false;
            if ($timestamp === false) {
                $projectDate = trim((string) ($project['updated_at'] ?? $project['started_at'] ?? ''));
                $timestamp = $projectDate !== '' ? strtotime($projectDate) : false;
            }
            $items[] = [
                'story' => $project,
                'project' => $project,
                'update' => $update,
                '_timestamp' => $timestamp ?: 0,
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

function story_asset(array $project, ?string $asset): string
{
    $asset = trim((string) $asset);
    if ($asset === '') return '';
    if (preg_match('~^(https?:)?//~i', $asset) || str_starts_with($asset, 'data:')) return $asset;
    $slug = safe_slug((string) ($project['slug'] ?? $project['_slug'] ?? ''));
    $clean = ltrim(str_replace(['..\\', '../', '\\'], ['', '', '/'], $asset), '/');
    return 'content/stories/' . rawurlencode($slug) . '/' . $clean;
}

function story_url(array $project): string
{
    $slug = rawurlencode((string) ($project['slug'] ?? $project['_slug'] ?? ''));
    if (workshop_is_active($project)) return 'atolye.php?slug=' . $slug;
    if (story_is_published($project)) return 'hikaye.php?slug=' . $slug;
    if (workshop_exists($project)) return 'atolye.php?slug=' . $slug;
    return 'hikaye.php?slug=' . $slug;
}

function category_map(array $site): array
{
    $map = [];
    foreach (($site['categories'] ?? []) as $category) {
        $id = (string) ($category['id'] ?? '');
        if ($id !== '') $map[$id] = (string) ($category['title'] ?? $id);
    }
    return $map;
}

function status_label(array $project): string
{
    return (string) ($project['status_label'] ?? $project['status'] ?? 'Kayıt');
}

function story_search_text(array $project): string
{
    return implode(' ', [
        (string) ($project['title'] ?? ''),
        (string) ($project['question'] ?? ''),
        (string) ($project['summary'] ?? ''),
        (string) ($project['category_label'] ?? ''),
        status_label($project),
        implode(' ', array_map('strval', is_array($project['tags'] ?? null) ? $project['tags'] : [])),
    ]);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return (string) $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    return is_string($token) && hash_equals(csrf_token(), $token);
}

function format_tr_date(?string $date): string
{
    if (!$date) return '';
    $timestamp = strtotime($date);
    if ($timestamp === false) return $date;
    $months = [1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'];
    return date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
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


function pinned_atelier_context(array $siteData, ?array $projects = null): array
{
    $projects ??= load_projects(false);
    $home = is_array($siteData['homepage'] ?? null) ? $siteData['homepage'] : [];
    $slug = safe_slug((string) ($home['pinned_atelier'] ?? $home['active_atelier'] ?? ''));
    $project = $slug !== '' ? ($projects[$slug] ?? null) : null;
    if (!$project || !workshop_is_active($project)) {
        return ['story' => null, 'project' => null, 'updates' => [], 'latest' => null];
    }
    $updates = load_updates($project);
    $latest = $updates !== [] ? $updates[array_key_last($updates)] : null;
    return ['story' => $project, 'project' => $project, 'updates' => $updates, 'latest' => $latest];
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
