<?php
declare(strict_types=1);
$checks=[
 ['PDO SQLite',extension_loaded('pdo_sqlite'),'XAMPP php.ini içinde extension=pdo_sqlite etkin olmalı.'],
 ['Veritabanı',is_file(__DIR__.'/../storage/fikrimvar.sqlite'),'storage/fikrimvar.sqlite dosyası bulunmalı.'],
 ['Storage yazılabilir',is_writable(__DIR__.'/../storage'),'Admin kullanımı için storage klasörü yazılabilir olmalı.'],
 ['Uploads yazılabilir',is_writable(__DIR__.'/uploads'),'Medya yüklemek için public/uploads yazılabilir olmalı.'],
];
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><title>FikrimVar V7 Sistem Kontrolü</title><style>body{font-family:system-ui;background:#101318;color:#eee;padding:40px;max-width:800px;margin:auto}.ok{color:#81d49c}.bad{color:#ff8d7a}li{padding:12px;border-bottom:1px solid #333}</style></head><body><h1>FikrimVar V7 Sistem Kontrolü</h1><ul><?php foreach($checks as [$name,$ok,$note]): ?><li class="<?= $ok?'ok':'bad' ?>"><strong><?= htmlspecialchars($name) ?>:</strong> <?= $ok?'Tamam':'Sorun' ?><br><small><?= htmlspecialchars($note) ?></small></li><?php endforeach; ?></ul></body></html>
