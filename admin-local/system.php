<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
admin_require_login();

$integrity = 'Calistirilamadi';
$foreignKeyCount = -1;
try {
    $integrity = (string)db()->query('PRAGMA integrity_check')->fetchColumn();
    $foreignKeyCount = count(db()->query('PRAGMA foreign_key_check')->fetchAll());
} catch (Throwable $e) {
    $integrity = admin_error_message($e, 'admin.system');
}

$checks = [
    ['PDO SQLite', extension_loaded('pdo_sqlite'), 'XAMPP php.ini icinde pdo_sqlite etkin olmali.'],
    ['Veritabani', is_file(FV7_DB), FV7_DB],
    ['Storage yazilabilir', is_writable(FV7_STORAGE), FV7_STORAGE],
    ['Uploads yazilabilir', is_writable(FV7_UPLOAD_ROOT), FV7_UPLOAD_ROOT],
    ['Admin yerel kilit', FV7_ADMIN_LOCAL_ONLY, 'Admin panel canlida public kokte tutulmaz.'],
    ['SQLite integrity_check', $integrity === 'ok', $integrity],
    ['SQLite foreign_key_check', $foreignKeyCount === 0, $foreignKeyCount < 0 ? 'Calistirilamadi' : (string)$foreignKeyCount],
];

admin_head('Sistem kontrolu'); ?>
<div class="page-head">
    <div>
        <p class="eyebrow">SISTEM</p>
        <h1>Kontrol ve butunluk</h1>
        <p>Bu kontrol yerel admin panelindedir; public site kokunde ayrica system-check dosyasi tutulmaz.</p>
    </div>
</div>
<div class="panel">
    <div class="list">
        <?php foreach ($checks as [$name, $ok, $detail]): ?>
            <div class="list-row">
                <span><?= e($name) ?></span>
                <strong style="color:<?= $ok ? '#67c587' : '#f07b6a' ?>"><?= $ok ? 'Tamam' : 'Sorun' ?></strong>
                <small><?= e((string)$detail) ?></small>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_foot(); ?>
