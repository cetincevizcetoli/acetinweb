<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

const ADMIN_SESSION_KEY = 'fikrimvar_admin';
const ADMIN_SESSION_TTL = 28800;

function admin_is_local_request(): bool
{
    $address = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return in_array($address, ['127.0.0.1', '::1'], true) || PHP_SAPI === 'cli-server';
}

function admin_auth_config(): array
{
    return load_json_path(ADMIN_AUTH_PATH);
}

function admin_is_configured(): bool
{
    $auth = admin_auth_config();
    return !empty($auth['password_hash']);
}

function admin_logged_in(): bool
{
    $time = (int) ($_SESSION[ADMIN_SESSION_KEY]['time'] ?? 0);
    if (empty($_SESSION[ADMIN_SESSION_KEY]['ok']) || $time < time() - ADMIN_SESSION_TTL) {
        unset($_SESSION[ADMIN_SESSION_KEY]);
        return false;
    }
    $_SESSION[ADMIN_SESSION_KEY]['time'] = time();
    return true;
}

function admin_login(string $password): bool
{
    $auth = admin_auth_config();
    if (empty($auth['password_hash']) || !password_verify($password, (string) $auth['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION[ADMIN_SESSION_KEY] = ['ok' => true, 'time' => time()];
    return true;
}

function admin_logout(): void
{
    unset($_SESSION[ADMIN_SESSION_KEY]);
    session_regenerate_id(true);
}

function admin_require_login(): void
{
    if (!admin_is_configured()) {
        header('Location: setup.php');
        exit;
    }
    if (!admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function admin_require_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf'] ?? null)) {
        http_response_code(419);
        exit('Oturum doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.');
    }
}

function admin_flash(string $type, string $message): void
{
    $_SESSION['admin_flash'][] = ['type' => $type, 'message' => $message];
}

function admin_take_flashes(): array
{
    $items = is_array($_SESSION['admin_flash'] ?? null) ? $_SESSION['admin_flash'] : [];
    unset($_SESSION['admin_flash']);
    return $items;
}

function admin_redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function admin_workshop_label(array $project): string
{
    return match (workshop_status($project)) {
        'open' => 'Atölye açık',
        'paused' => 'Atölye beklemede',
        'closed' => 'Atölye kapandı',
        default => 'Atölye yok',
    };
}

function admin_story_label(array $project): string
{
    return match (story_status($project)) {
        'published' => 'Hikâye yayında',
        'draft' => 'Hikâye taslak',
        default => 'Hikâye yok',
    };
}

function admin_layout_start(string $title, string $active = ''): void
{
    $flashes = admin_take_flashes();
    ?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($title) ?> · FikrimVar Yönetim</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
<header class="admin-header">
    <a class="admin-brand" href="index.php"><strong>#FikrimVar</strong><span>İçerik yönetimi</span></a>
    <?php if (admin_logged_in()): ?>
    <nav>
        <a class="<?= $active === 'projects' ? 'is-active' : '' ?>" href="index.php">Projeler</a>
        <a class="<?= $active === 'new' ? 'is-active' : '' ?>" href="project-new.php">Yeni proje</a>
        <a class="<?= $active === 'settings' ? 'is-active' : '' ?>" href="settings.php">Ayarlar</a>
        <a href="../index.php" target="_blank" rel="noopener">Siteyi aç</a>
        <a href="logout.php">Çıkış</a>
    </nav>
    <?php endif; ?>
</header>
<main class="admin-shell">
    <?php foreach ($flashes as $flash): ?>
        <div class="admin-flash admin-flash--<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div>
    <?php endforeach; ?>
<?php
}

function admin_layout_end(): void
{
    ?>
</main>
<footer class="admin-footer"><span>V6.6 · JSON tabanlı yerel yönetim</span><span>Her kayıt öncesi otomatik yedek alınır.</span></footer>
</body>
</html>
<?php
}

function admin_upload_media(string $slug, string $field = 'media_file'): ?string
{
    if (empty($_FILES[$field]) || (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Dosya yüklenemedi. Hata kodu: ' . (int) $file['error']);
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 120 * 1024 * 1024) {
        throw new RuntimeException('Dosya boyutu 120 MB sınırını aşıyor veya dosya boş.');
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    $allowed = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif',
        'video/mp4' => 'mp4', 'video/webm' => 'webm',
        'audio/mpeg' => 'mp3', 'audio/ogg' => 'ogg', 'audio/wav' => 'wav', 'audio/x-wav' => 'wav',
    ];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Bu dosya türüne izin verilmiyor: ' . $mime);
    }

    $base = safe_slug(pathinfo((string) ($file['name'] ?? 'medya'), PATHINFO_FILENAME)) ?: 'medya';
    $name = date('Ymd-His') . '-' . $base . '.' . $allowed[$mime];
    $dir = media_dir($slug);
    ensure_directory($dir);
    $target = $dir . '/' . $name;
    if (!move_uploaded_file($tmp, $target)) {
        throw new RuntimeException('Yüklenen dosya proje klasörüne taşınamadı.');
    }
    return 'media/' . $name;
}

function admin_category_options(array $siteData): array
{
    return category_map($siteData);
}

function admin_project_form_values(?array $project = null): array
{
    return [
        'slug' => (string) ($project['slug'] ?? ''),
        'title' => (string) ($project['title'] ?? ''),
        'question' => (string) ($project['question'] ?? ''),
        'summary' => (string) ($project['summary'] ?? ''),
        'category' => (string) ($project['category'] ?? 'kod-sistem'),
        'category_label' => (string) ($project['category_label'] ?? ''),
        'status' => (string) ($project['status'] ?? 'suruyor'),
        'status_label' => (string) ($project['status_label'] ?? 'Üzerinde çalışıyorum'),
        'type_label' => (string) ($project['type_label'] ?? 'Proje'),
        'cover' => (string) ($project['cover'] ?? 'media/cover.svg'),
        'tags' => implode(', ', array_map('strval', is_array($project['tags'] ?? null) ? $project['tags'] : [])),
        'workshop_question' => (string) ($project['workshop_question'] ?? ''),
        'workshop_status' => workshop_status($project ?? []),
        'story_status' => story_status($project ?? []),
        'homepage' => (bool) ($project['homepage'] ?? false),
        'public' => (bool) ($project['public'] ?? true),
    ];
}


function admin_write_default_cover(string $slug, string $title): string
{
    ensure_directory(media_dir($slug));
    $safeTitle = e($title);
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 720">'
        . '<rect width="1200" height="720" fill="#11151a"/>'
        . '<path d="M80 590 C310 150 710 760 1120 180" fill="none" stroke="#ad5535" stroke-width="8"/>'
        . '<circle cx="820" cy="330" r="170" fill="none" stroke="#efe6da" stroke-opacity=".6" stroke-width="3"/>'
        . '<text x="80" y="120" fill="#efe6da" font-family="Georgia,serif" font-size="54">' . $safeTitle . '</text>'
        . '</svg>';
    atomic_write_text(media_dir($slug) . '/cover.svg', $svg, false);
    return 'media/cover.svg';
}

function admin_tags_from_input(string $value): array
{
    $items = preg_split('/[,\n]+/u', $value) ?: [];
    $items = array_values(array_filter(array_map(static fn(string $v): string => trim($v), $items), static fn(string $v): bool => $v !== ''));
    return array_values(array_unique($items));
}

function admin_story_skeleton(array $project): array
{
    return [
        'slug' => (string) ($project['slug'] ?? ''),
        'title' => (string) ($project['title'] ?? ''),
        'question' => (string) ($project['question'] ?? $project['title'] ?? ''),
        'summary' => (string) ($project['summary'] ?? ''),
        'category' => (string) ($project['category'] ?? ''),
        'category_label' => (string) ($project['category_label'] ?? ''),
        'status' => (string) ($project['status'] ?? 'suruyor'),
        'status_label' => (string) ($project['status_label'] ?? 'Kayıt'),
        'type_label' => 'Proje hikâyesi',
        'order' => (float) ($project['order'] ?? 50),
        'started_at' => $project['started_at'] ?? null,
        'updated_at' => $project['updated_at'] ?? null,
        'cover' => (string) ($project['cover'] ?? 'media/cover.svg'),
        'tags' => is_array($project['tags'] ?? null) ? $project['tags'] : [],
        'homepage' => (bool) ($project['homepage'] ?? false),
        'reading_time' => '3 dakika',
        'generated_by_admin' => true,
        'blocks' => [[
            'type' => 'opening',
            'layout' => 'hero-split',
            'label' => 'NEDEN BAŞLADI?',
            'title' => (string) ($project['question'] ?? $project['title'] ?? ''),
            'paragraphs' => [(string) ($project['summary'] ?? '')],
            'image' => (string) ($project['cover'] ?? 'media/cover.svg'),
            'caption' => (string) ($project['title'] ?? ''),
        ]],
    ];
}
