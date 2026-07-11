<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php'; admin_require_login();
if(is_post()){
 verify_csrf();
 try{
  $stamp=date('Ymd-His');$dest=FV7_STORAGE.'/backups/fikrimvar-'.$stamp.'.sqlite';if(!is_dir(dirname($dest)))mkdir(dirname($dest),0775,true);
  db()->exec('PRAGMA wal_checkpoint(FULL)');if(!copy(FV7_DB,$dest))throw new RuntimeException('Yedek kopyalanamadı.');flash('success','SQLite yedeği oluşturuldu: '.basename($dest));redirect('backup.php');
 }catch(Throwable $e){flash('error',admin_error_message($e,'admin.backup'));redirect('backup.php');}
}
$files=glob(FV7_STORAGE.'/backups/*.sqlite') ?: [];rsort($files);admin_head('Yedekleme'); ?><div class="page-head"><div><p class="eyebrow">YEDEK</p><h1>Tek dosyalık veritabanı</h1><p>SQLite seçiminin güzel tarafı: içerik veritabanını tek dosya olarak taşıyabilirsin. Medya klasörünü ayrıca yedekle.</p></div></div><form class="panel" method="post"><?= csrf_field() ?><button class="accent" type="submit">Şimdi SQLite yedeği oluştur</button></form><div class="list" style="margin-top:20px"><?php foreach($files as $f): ?><div class="list-row"><span>●</span><strong><?= e(basename($f)) ?></strong><small><?= number_format(filesize($f)/1024,1) ?> KB</small></div><?php endforeach; ?></div><?php admin_foot(); ?>
