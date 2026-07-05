<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php'; admin_require_login();
$checks=[['PDO SQLite',extension_loaded('pdo_sqlite')],['Veritabanı',is_file(FV7_DB)],['Storage yazılabilir',is_writable(FV7_STORAGE)],['Uploads yazılabilir',is_writable(FV7_UPLOAD_ROOT)],['Admin yerel kilit',FV7_ADMIN_LOCAL_ONLY]];
$integrity='Çalıştırılamadı'; try{$integrity=(string)db()->query('PRAGMA integrity_check')->fetchColumn();}catch(Throwable $e){$integrity=$e->getMessage();}
admin_head('Sistem kontrolü'); ?><div class="page-head"><div><p class="eyebrow">SİSTEM</p><h1>Kontrol ve bütünlük</h1></div></div><div class="panel"><ul><?php foreach($checks as [$n,$ok]): ?><li><?= e($n) ?>: <strong style="color:<?= $ok?'#67c587':'#f07b6a' ?>"><?= $ok?'Tamam':'Sorun' ?></strong></li><?php endforeach; ?></ul><p>SQLite bütünlük: <strong><?= e($integrity) ?></strong></p></div><?php admin_foot(); ?>
