<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php'; admin_require_login();
$filter=safe_slug((string)($_GET['filter'] ?? 'all')) ?: 'all'; $projects=admin_projects($filter);
$stats=[
 'Toplam'=>(int)db()->query('SELECT COUNT(*) FROM projects WHERE deleted_at IS NULL')->fetchColumn(),
 'Açık atölye'=>(int)db()->query("SELECT COUNT(*) FROM projects WHERE deleted_at IS NULL AND workshop_status='open'")->fetchColumn(),
 'Hikâye taslağı'=>(int)db()->query("SELECT COUNT(*) FROM stories WHERE deleted_at IS NULL AND status='draft'")->fetchColumn(),
 'Yayımlanmış'=>(int)db()->query("SELECT COUNT(*) FROM stories WHERE deleted_at IS NULL AND status='published'")->fetchColumn(),
];
admin_head('Projeler'); ?>
<div class="page-head"><div><p class="eyebrow">İÇERİK MERKEZİ</p><h1>Projeler, Atölyeler ve Hikâyeler</h1><p>Her proje tek kayıt. Atölye çalışma hâli, Hikâye ise seçilmiş ve düzenlenmiş anlatımı.</p></div><a class="button accent" href="project-new.php">+ Yeni proje</a></div>
<div class="stat-grid"><?php foreach($stats as $k=>$v): ?><div class="stat"><strong><?= $v ?></strong><span><?= e($k) ?></span></div><?php endforeach; ?></div>
<div class="tabs"><?php foreach(['all'=>'Tümü','workshop'=>'Atölyeler','draft'=>'Taslaklar','published'=>'Yayımlananlar','trash'=>'Çöp kutusu'] as $k=>$v): ?><a class="<?= $filter===$k?'active':'' ?>" href="?filter=<?= e($k) ?>"><?= e($v) ?></a><?php endforeach; ?></div>
<?php if(!$projects): ?><div class="empty">Bu görünümde kayıt yok.</div><?php else: ?><div class="cards"><?php foreach($projects as $p): ?><article class="card"><?php if($p['cover_path']): ?><figure><img src="../<?= e($p['cover_path']) ?>" alt=""></figure><?php endif; ?><div class="card-body"><div class="meta"><span class="chip"><?= e($p['category_title'] ?? 'Kategori yok') ?></span><span class="chip <?= $p['workshop_status']==='open'?'ok':'' ?>">Atölye: <?= e($p['workshop_status']) ?></span><span class="chip <?= $p['story_status']==='published'?'ok':($p['story_status']==='draft'?'warn':'') ?>">Hikâye: <?= e($p['story_status'] ?? 'yok') ?></span><span class="chip"><?= (int)$p['update_count'] ?> kayıt</span></div><h2><?= e($p['title']) ?></h2><p><?= e($p['summary']) ?></p><div class="card-actions"><a class="button secondary" href="project-edit.php?id=<?= (int)$p['id'] ?>">Projeyi yönet</a><?php if($p['workshop_status']!=='none'): ?><a class="button secondary" href="update-new.php?project_id=<?= (int)$p['id'] ?>">Kayıt ekle</a><?php endif; ?><?php if($p['story_id']): ?><a class="button secondary" href="story-edit.php?project_id=<?= (int)$p['id'] ?>">Hikâye</a><?php endif; ?><?php if(!$p['deleted_at']): ?><a class="button secondary" href="../<?= $p['workshop_status']==='open'?'atolye.php':'hikaye.php' ?>?slug=<?= e(rawurlencode($p['slug'])) ?>" target="_blank">Ön izle</a><?php endif; ?></div></div></article><?php endforeach; ?></div><?php endif; ?>
<?php admin_foot(); ?>
