<?php
declare(strict_types=1);

define('FV7_ROOT', dirname(__DIR__));
define('FV7_PUBLIC', FV7_ROOT . '/public');

$localConfig = [];
$localConfigFile = __DIR__ . '/local.php';
if (is_file($localConfigFile)) {
    $loadedLocalConfig = require $localConfigFile;
    if (is_array($loadedLocalConfig)) {
        $localConfig = $loadedLocalConfig;
    }
}

$storagePath = (string)($localConfig['storage_path'] ?? (FV7_ROOT . '/storage'));
$dbPath = (string)($localConfig['db_path'] ?? ($storagePath . '/fikrimvar.sqlite'));

define('FV7_STORAGE', rtrim(str_replace('\\', '/', $storagePath), '/'));
define('FV7_DB', str_replace('\\', '/', $dbPath));
define('FV7_UPLOAD_ROOT', FV7_PUBLIC . '/uploads');
define('FV7_ADMIN_LOCAL_ONLY', true);
define('FV7_IMAGE_MAX_BYTES', 20 * 1024 * 1024);
define('FV7_VIDEO_MAX_BYTES', 250 * 1024 * 1024);
define('FV7_OTHER_MAX_BYTES', 40 * 1024 * 1024);
define('FV7_SESSION_NAME', 'fikrimvar_v7_admin');
