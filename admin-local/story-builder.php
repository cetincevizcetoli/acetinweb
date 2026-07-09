<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php'; admin_require_login();
$projectId=(int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
$st=db()->prepare('SELECT * FROM projects WHERE id=? AND deleted_at IS NULL');$st->execute([$projectId]);$project=$st->fetch();if(!$project){http_response_code(404);exit('Proje yok.');}
$updates=project_updates($projectId,false);$story=story_by_project($projectId,true);$error='';
function story_builder_has_column(string $table, string $column): bool
{
  $st=db()->query('PRAGMA table_info('.$table.')');
  foreach($st->fetchAll() as $row) if(($row['name'] ?? '')===$column) return true;
  return false;
}
if(is_post()){
  verify_csrf();
  try{
    $selected=array_values(array_unique(array_map('intval',$_POST['update_ids'] ?? [])));
    if(!$selected) throw new RuntimeException('En az bir kayıt seçmelisin.');
    db()->beginTransaction();
    if(!$story){$st=db()->prepare("INSERT INTO stories(project_id,title,question,summary,status,visibility,show_on_home,show_in_archive,sort_order) VALUES (?,?,?,?, 'draft', ?,?,?,?)");$st->execute([$projectId,$project['title'],$project['question'],$project['summary'],$project['visibility'],$project['show_on_home'],$project['show_in_archive'],$project['sort_order']]);$storyId=(int)db()->lastInsertId();}
    else{$storyId=(int)$story['id'];if(checkbox('replace_sections')){db()->prepare('DELETE FROM story_sections WHERE story_id=?')->execute([$storyId]);}}
    $max=(int)db()->query('SELECT COALESCE(MAX(sort_order),0) FROM story_sections WHERE story_id='.(int)$storyId)->fetchColumn();
    // opening
    if($max===0 || checkbox('replace_sections')){
      $st=db()->prepare("INSERT INTO story_sections(story_id,type,layout,label,title,body_text,quote_text,sort_order) VALUES (?, 'opening','hero-split','BAŞLANGIÇ',?,?,?,?)");
      $st->execute([$storyId,$project['question'] ?: $project['title'],$project['summary'],'Bu hikâye, Atölye kayıtlarının içinden seçilerek oluşturuldu.',++$max]);
    }
    // timeline from selected updates
    $st=db()->prepare("INSERT INTO story_sections(story_id,type,layout,label,title,intro_text,sort_order) VALUES (?, 'timeline','full-bleed','DÖNÜM NOKTALARI','Atölyede yönü değiştiren kararlar.','Ham kayıtların tamamı değil; seçilmiş kırılmalar.',?)");$st->execute([$storyId,++$max]);$sectionId=(int)db()->lastInsertId();
    $q=db()->prepare('SELECT * FROM updates WHERE id=? AND project_id=?');
    $hasItemSource=story_builder_has_column('story_section_items','source_update_id');
    foreach($selected as $i=>$uid){$q->execute([$uid,$projectId]);$u=$q->fetch();if(!$u)continue;if($hasItemSource){$ins=db()->prepare("INSERT INTO story_section_items(section_id,group_key,item_type,step,title,subtitle,text,source_update_id,sort_order) VALUES (?,'','timeline',?,?,?,?,?,?)");$ins->execute([$sectionId,str_pad((string)($i+1),2,'0',STR_PAD_LEFT),$u['title'],$u['display_label'] ?: $u['phase'],$u['decision'] ?: $u['summary'],(int)$u['id'],$i+1]);}else{$ins=db()->prepare("INSERT INTO story_section_items(section_id,group_key,item_type,step,title,subtitle,text,sort_order) VALUES (?,'','timeline',?,?,?,?,?)");$ins->execute([$sectionId,str_pad((string)($i+1),2,'0',STR_PAD_LEFT),$u['title'],$u['display_label'] ?: $u['phase'],$u['decision'] ?: $u['summary'],$i+1]);}}
    // ending/lessons section
    $st=db()->prepare("INSERT INTO story_sections(story_id,type,layout,label,title,body_text,sort_order) VALUES (?, 'text','wide','BUGÜN NEREDE?','Bu proje benim için bugün ne ifade ediyor?',?,?)");$st->execute([$storyId,trim((string)($_POST['closing_note'] ?? $project['closing_note'])) ?: 'Bu bölüm hikâye düzenleyicisinden tamamlanacak.',++$max]);
    if(checkbox('close_workshop')){
      db()->prepare("UPDATE projects SET workshop_status='closed',closing_state=?,closing_note=?,ended_at=?,updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([(string)($_POST['closing_state'] ?? 'Bu hâliyle bitti'),trim((string)($_POST['closing_note'] ?? '')),date('Y-m-d'),$projectId]);
    }
    db()->commit();admin_audit('build_story','story',$storyId,'Selected updates: '.implode(',',$selected));flash('success','Hikâye taslağı oluşturuldu.');redirect('story-edit.php?project_id='.$projectId);
  }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();$error=$e->getMessage();}
}
admin_head('Atölyeden Hikâye'); ?>
<div class="page-head"><div><p class="eyebrow"><?= e($project['title']) ?></p><h1>Atölyeden Hikâye oluştur</h1><p>Ham kayıtlar silinmez. Hikâyeye yalnızca seçtiğin dönüm noktalarının özeti taşınır.</p></div></div><?php if($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="project_id" value="<?= $projectId ?>"><?= csrf_field() ?><div class="grid grid-2"><section class="panel"><h2>Kayıtları seç</h2><div class="list"><?php foreach($updates as $u): ?><label class="list-row"><input type="checkbox" name="update_ids[]" value="<?= (int)$u['id'] ?>" <?= $u['is_milestone']?'checked':'' ?>><span><strong><?= e($u['title']) ?></strong><small><?= e($u['date_label']) ?> · <?= e($u['phase']) ?></small></span><span class="chip <?= $u['is_milestone']?'ok':'' ?>"><?= $u['is_milestone']?'Dönüm noktası':'Ham kayıt' ?></span></label><?php endforeach; ?></div></section><aside class="panel"><h2>Kapanış ve taslak</h2><?php if($story): ?><p class="help">Bu projede zaten bir hikâye var. Mevcut bölümleri koruyabilir veya yeni taslakla değiştirebilirsin.</p><label class="check"><input type="checkbox" name="replace_sections"> Mevcut hikâye bölümlerini silip yeniden oluştur</label><?php endif; ?><label class="check"><input type="checkbox" name="close_workshop" <?= $project['workshop_status']==='closed'?'checked':'' ?>> Atölyeyi kapat</label><div class="field"><label>Kapanış kararı</label><select name="closing_state"><?php foreach(['Bu hâliyle bitti','Yarım bıraktım','Beklemeye aldım','Başka projeye dönüştü'] as $v): ?><option><?= e($v) ?></option><?php endforeach; ?></select></div><div class="field"><label>Kapanış notu</label><textarea name="closing_note"><?= e($project['closing_note']) ?></textarea></div></aside></div><div class="form-actions"><button class="accent" type="submit">Taslağı oluştur</button><a class="button secondary" href="project-edit.php?id=<?= $projectId ?>">Vazgeç</a></div></form><?php admin_foot(); ?>
