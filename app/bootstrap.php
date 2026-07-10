<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(FV7_SESSION_NAME);
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    if (!extension_loaded('pdo_sqlite')) {
        throw new RuntimeException('PDO_SQLite etkin değil. XAMPP php.ini içinde extension=pdo_sqlite satırını etkinleştirip Apache’yi yeniden başlatın.');
    }
    if (!is_dir(FV7_STORAGE)) mkdir(FV7_STORAGE, 0775, true);
    $pdo = new PDO('sqlite:' . FV7_DB, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys=ON');
    $pdo->exec('PRAGMA busy_timeout=5000');
    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function safe_slug(string $value): string
{
    $lower = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    $value = trim($lower);
    $map = ['ç'=>'c','ğ'=>'g','ı'=>'i','ö'=>'o','ş'=>'s','ü'=>'u'];
    $value = strtr($value, $map);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}


function safe_external_url(?string $url): string
{
    $url = trim((string)$url);
    if ($url === '') return '';

    // "youtube.com/..." gibi şemasız adresleri kullanıcı dostu biçimde tamamla.
    if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) return '';
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true) ? $url : '';
}

function validated_external_url(?string $url, string $fieldLabel = 'Bağlantı'): string
{
    $raw = trim((string)$url);
    if ($raw === '') return '';

    $safe = safe_external_url($raw);
    if ($safe === '') {
        throw new InvalidArgumentException($fieldLabel . ' geçerli bir web adresi değil: ' . $raw);
    }

    return $safe;
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function now_sql(): string { return date('Y-m-d H:i:s'); }
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }
function redirect(string $url): never { header('Location: ' . $url); exit; }
function old(string $key, string $default=''): string { return (string)($_POST[$key] ?? $default); }
function checkbox(string $key): int { return isset($_POST[$key]) ? 1 : 0; }

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return (string)$_SESSION['csrf_token'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="'.e(csrf_token()).'">'; }
function verify_csrf(): void
{
    $sent = (string)($_POST['csrf_token'] ?? '');
    if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.');
    }
}

function flash(string $type, string $message): void { $_SESSION['flash'][] = ['type'=>$type,'message'=>$message]; }
function pull_flashes(): array { $v=$_SESSION['flash'] ?? []; unset($_SESSION['flash']); return is_array($v)?$v:[]; }

function setting(string $key, mixed $default=[]): mixed
{
    return SettingsRepository::get($key, $default);
}
function save_setting(string $key, mixed $value): void
{
    SettingsRepository::save($key, $value);
}

function media_url(?string $path): string
{
    $path=trim((string)$path); if ($path==='') return '';
    if (preg_match('~^(https?:)?//~i',$path)) return $path;
    return ltrim($path,'/');
}

function asset_url(string $path): string
{
    $path = ltrim($path, '/');
    $file = FV7_PUBLIC . '/' . $path;
    $version = is_file($file) ? (string)filemtime($file) : (string)time();
    return $path . '?v=' . rawurlencode($version);
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

require_once __DIR__ . '/Service/VisibilityService.php';
require_once __DIR__ . '/Repository/ProjectRepository.php';
require_once __DIR__ . '/Repository/StoryRepository.php';
require_once __DIR__ . '/Repository/LinkRepository.php';
require_once __DIR__ . '/Repository/MediaRepository.php';
require_once __DIR__ . '/Repository/UpdateRepository.php';
require_once __DIR__ . '/Repository/SettingsRepository.php';
require_once __DIR__ . '/repositories.php';
require_once __DIR__ . '/LinkRenderer.php';
require_once __DIR__ . '/render.php';
