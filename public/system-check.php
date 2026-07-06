<?php
declare(strict_types=1);

require __DIR__ . '/../config/config.php';

$checks = [
    ['PDO SQLite', extension_loaded('pdo_sqlite'), 'XAMPP php.ini icinde extension=pdo_sqlite etkin olmali.'],
    ['Veritabani', is_file(FV7_DB), FV7_DB],
    ['Storage yazilabilir', is_writable(FV7_STORAGE), FV7_STORAGE],
    ['Uploads yazilabilir', is_writable(FV7_UPLOAD_ROOT), 'Medya yuklemek icin public/uploads yazilabilir olmali.'],
];
?><!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>FikrimVar V7 Sistem Kontrolu</title>
    <style>
        body{font-family:system-ui;background:#101318;color:#eee;padding:40px;max-width:800px;margin:auto}
        .ok{color:#81d49c}.bad{color:#ff8d7a}li{padding:12px;border-bottom:1px solid #333}
    </style>
</head>
<body>
<h1>FikrimVar V7 Sistem Kontrolu</h1>
<ul>
    <?php foreach ($checks as [$name, $ok, $note]): ?>
        <li class="<?= $ok ? 'ok' : 'bad' ?>">
            <strong><?= htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>:</strong>
            <?= $ok ? 'Tamam' : 'Sorun' ?><br>
            <small><?= htmlspecialchars($note, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small>
        </li>
    <?php endforeach; ?>
</ul>
</body>
</html>
