<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_update_form.php';
admin_require_login();
$id=(int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st=db()->prepare('SELECT u.*,p.title project_title,p.slug project_slug,p.id project_id FROM updates u JOIN projects p ON p.id=u.project_id WHERE u.id=?');$st->execute([$id]);$update=$st->fetch();
if(!$update){http_response_code(404);exit('Kayıt bulunamadı.');}
$project=['id'=>$update['project_id'],'title'=>$update['project_title'],'slug'=>$update['project_slug']];
$attached=update_media($id);$links=owner_links('update',$id);$error='';
if(is_post()){
  verify_csrf();$action=(string)($_POST['action'] ?? 'save');
  try{
    if($action==='delete'){db()->prepare('UPDATE updates SET deleted_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$id]);flash('success','Kayıt çöp kutusuna taşındı.');redirect('project-edit.php?id='.(int)$project['id']);}
    if($action==='restore'){db()->prepare('UPDATE updates SET deleted_at=NULL,updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$id]);flash('success','Kayıt geri yüklendi.');redirect('update-edit.php?id='.$id);}
    db()->beginTransaction();
    $title=trim(old('title'));if($title==='')throw new RuntimeException('Başlık gerekli.');
    $slug=unique_update_slug((int)$project['id'],old('slug') ?: $title,$id);$status=(string)($_POST['status'] ?? 'draft');$workDate=old('work_date')?:null;$display=trim(old('display_label'));if($display===''&&$workDate)$display=date('d.m.Y',strtotime($workDate));
    $st=db()->prepare("UPDATE updates SET slug=?,work_date=?,display_label=?,title=?,summary=?,tried=?,failed=?,decision=?,next_step=?,phase=?,is_milestone=?,status=?,visibility=?,show_in_recent=?,sort_order=?,published_at=CASE WHEN ?='published' AND published_at IS NULL THEN CURRENT_TIMESTAMP ELSE published_at END,updated_at=CURRENT_TIMESTAMP WHERE id=?");
    $st->execute([$slug,$workDate,$display,$title,trim(old('summary')),trim(old('tried')),trim(old('failed')),trim(old('decision')),trim(old('next_step')),trim(old('phase')) ?: 'Genel',checkbox('is_milestone'),$status,(string)($_POST['visibility'] ?? 'public'),checkbox('show_in_recent'),(float)($_POST['sort_order'] ?? 999),$status,$id]);
    $existing=assert_project_media_ids((int)$project['id'],$_POST['existing_media_ids'] ?? [],'Mevcut medya');$uploaded=save_uploaded_files((int)$project['id'],(string)$project['slug']);attach_update_media($id,(int)$project['id'],array_merge($uploaded,$existing));
    foreach(array_map('intval',$_POST['remove_media_ids'] ?? []) as $mid)db()->prepare('DELETE FROM update_media WHERE update_id=? AND media_id=?')->execute([$id,$mid]);
    save_update_links($id,is_array($_POST['links'] ?? null)?$_POST['links']:[]);db()->prepare('UPDATE projects SET updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$project['id']]);
    db()->commit();admin_audit('update','update',$id,$title);flash('success','Kayıt güncellendi.');redirect('update-edit.php?id='.$id);
  }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();$error=$e->getMessage();}
}
$st=db()->prepare('SELECT * FROM updates WHERE id=?');$st->execute([$id]);$update=$st->fetch();$attached=update_media($id);$links=owner_links('update',$id);
admin_head('Atölye kaydını düzenle');
?><div class="page-head"><div><p class="eyebrow"><?= e($project['title']) ?></p><h1><?= e($update['title']) ?></h1></div><a class="button secondary" href="../atolye.php?slug=<?= e(rawurlencode((string)$project['slug'])) ?>#update-<?= e($update['slug']) ?>" target="_blank">Sitede aç</a></div><?php if($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?><form method="post" enctype="multipart/form-data"><input type="hidden" name="id" value="<?= $id ?>"><?= csrf_field() ?><?php render_update_form($project,$update,$attached,$links); ?><div class="form-actions"><button class="accent" type="submit" name="action" value="save">Kaydı kaydet</button><a class="button secondary" href="project-edit.php?id=<?= (int)$project['id'] ?>">Projeye dön</a><button class="danger" type="submit" name="action" value="delete" data-confirm="Kayıt çöp kutusuna taşınsın mı?">Kaydı sil</button></div></form><?php admin_foot(); ?>
