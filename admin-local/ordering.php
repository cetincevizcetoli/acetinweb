<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php'; admin_require_login();
if(is_post()){
 verify_csrf();
 try{
  db()->beginTransaction();
  foreach($_POST['projects'] ?? [] as $id=>$row){$st=db()->prepare('UPDATE projects SET sort_order=?,show_on_home=?,show_in_archive=?,home_section=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');$st->execute([(float)($row['order']??999),!empty($row['home'])?1:0,!empty($row['archive'])?1:0,(string)($row['section']??'none'),(int)$id]);}
  db()->commit();flash('success','Yayın ve sıralama kaydedildi.');redirect('ordering.php');
 }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();flash('error',$e->getMessage());}
}
$projects=admin_projects();admin_head('Yayın ve sıra'); ?>
<div class="page-head"><div><p class="eyebrow">YAYIN KONTROLÜ</p><h1>Ne görünsün, nerede dursun?</h1><p>Projeyi silmeden ana sayfadan veya Hikâyeler sayfasından kaldırabilirsin. Satırları sürükleyerek sırala.</p></div></div>
<form method="post"><?= csrf_field() ?><div class="sortable" data-sortable><?php foreach($projects as $i=>$p): ?><article class="sortable-item" draggable="true"><span class="drag-handle">⠿</span><div><strong><?= e($p['title']) ?></strong><small><?= e($p['category_title'] ?? '') ?> · <?= e($p['story_status'] ?? 'hikâye yok') ?></small><input type="hidden" name="projects[<?= (int)$p['id'] ?>][order]" value="<?= $i+1 ?>" data-order-input></div><div class="check-row"><label class="check"><input type="checkbox" name="projects[<?= (int)$p['id'] ?>][home]" <?= $p['show_on_home']?'checked':'' ?>> Ana sayfada göster</label><label class="check"><input type="checkbox" name="projects[<?= (int)$p['id'] ?>][archive]" <?= $p['show_in_archive']?'checked':'' ?>> Hikâyeler sayfasında göster</label><select name="projects[<?= (int)$p['id'] ?>][section]"><option value="none" <?= $p['home_section']==='none'?'selected':'' ?>>Ana sayfadaki yeri: Kapalı</option><option value="focus" <?= $p['home_section']==='focus'?'selected':'' ?>>Öne çıkan büyük kart</option><option value="trace" <?= $p['home_section']==='trace'?'selected':'' ?>>Alt şerit / küçük kayıt</option></select></div></article><?php endforeach; ?></div><div class="form-actions"><button class="accent" type="submit">Sırayı kaydet</button></div></form><?php admin_foot(); ?>
