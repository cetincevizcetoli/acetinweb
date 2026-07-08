<?php
declare(strict_types=1);

define('FV7_ROOT', dirname(__DIR__));
define('FV7_PUBLIC', FV7_ROOT . '/public');

function fv7_path(string $path): string
{
    return rtrim(str_replace('\\', '/', $path), '/');
}

$localConfig = [];
$localConfigFile = __DIR__ . '/local.php';
if (is_file($localConfigFile)) {
    $loadedLocalConfig = require $localConfigFile;
    if (is_array($loadedLocalConfig)) {
        $localConfig = $loadedLocalConfig;
    }
}

$storageCandidates = [
    getenv('FV7_STORAGE_PATH') ?: '',
    (string)($localConfig['storage_path'] ?? ''),
    dirname(FV7_ROOT) . '/acetinweb_private/storage',
    'C:/xampp/acetinweb_private/storage',
    FV7_ROOT . '/storage',
];

$storagePath = '';
foreach ($storageCandidates as $candidate) {
    $candidate = fv7_path((string)$candidate);
    if ($candidate === '') continue;
    if ($storagePath === '') $storagePath = $candidate;
    if (is_file($candidate . '/fikrimvar.sqlite') || is_dir($candidate)) {
        $storagePath = $candidate;
        break;
    }
}

$dbCandidates = [
    getenv('FV7_DB_PATH') ?: '',
    (string)($localConfig['db_path'] ?? ''),
    $storagePath . '/fikrimvar.sqlite',
    dirname(FV7_ROOT) . '/acetinweb_private/storage/fikrimvar.sqlite',
    'C:/xampp/acetinweb_private/storage/fikrimvar.sqlite',
    FV7_ROOT . '/storage/fikrimvar.sqlite',
];

$dbPath = '';
foreach ($dbCandidates as $candidate) {
    $candidate = fv7_path((string)$candidate);
    if ($candidate === '') continue;
    if ($dbPath === '') $dbPath = $candidate;
    if (is_file($candidate)) {
        $dbPath = $candidate;
        break;
    }
}

define('FV7_STORAGE', fv7_path($storagePath));
define('FV7_DB', fv7_path($dbPath));
define('FV7_UPLOAD_ROOT', FV7_PUBLIC . '/uploads');
define('FV7_ADMIN_LOCAL_ONLY', true);
define('FV7_ALLOW_SYSTEM_CHECK', filter_var(getenv('FV7_ALLOW_SYSTEM_CHECK') ?: ($localConfig['allow_system_check'] ?? false), FILTER_VALIDATE_BOOL));
define('FV7_IMAGE_MAX_BYTES', 20 * 1024 * 1024);
define('FV7_VIDEO_MAX_BYTES', 250 * 1024 * 1024);
define('FV7_OTHER_MAX_BYTES', 40 * 1024 * 1024);
define('FV7_SESSION_NAME', 'fikrimvar_v7_admin');
