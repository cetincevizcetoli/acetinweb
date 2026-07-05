<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php'; admin_require_login();
$cats=categories(); $error='';
if(is_post()){
    verify_csrf();
    $title=trim(old('title')); $slug=safe_slug(old('slug') ?: $title); $summary=trim(old('summary')); $question=trim(old('question'));
    $categoryId=(int)($_POST['category_id'] ?? 0); $mode=(string)($_POST['mode'] ?? 'workshop');
    if($title==='' || $slug==='') $error='Başlık ve slug gerekli.';
    else{
        try{
            db()->beginTransaction();
            $workshop=$mode==='workshop'?'open':'none'; $visibility=(string)($_POST['visibility'] ?? 'private');
            $st=db()->prepare("INSERT INTO projects(slug,title,question,summary,category_id,status,status_label,type_label,visibility,workshop_status,workshop_question,show_on_home,show_in_archive,show_in_widget,home_section,sort_order,started_at)
              VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $st->execute([$slug,$title,$question,$summary,$categoryId?:null,'fikir','Yeni','Proje',$visibility,$workshop,trim(old('workshop_question')),checkbox('show_on_home'),checkbox('show_in_archive'),checkbox('show_in_widget'),(string)($_POST['home_section'] ?? 'none'),(float)($_POST['sort_order'] ?? 999),$workshop==='open'?date('Y-m-d'):null]);
            $id=(int)db()->lastInsertId();
            if($mode==='story'){
                $s=db()->prepare("INSERT INTO stories(project_id,title,question,summary,status,visibility,show_on_home,show_in_archive,sort_order) VALUES (?,?,?,?, 'draft', ?,?,?,?)");
                $s->execute([$id,$title,$question,$summary,$visibility,checkbox('show_on_home'),checkbox('show_in_archive'),(float)($_POST['sort_order'] ?? 999)]);
            }
            db()->commit(); admin_audit('create','project',$id,$title); flash('success','Proje oluşturuldu.'); redirect('project-edit.php?id='.$id);
        }catch(Throwable $e){ if(db()->inTransaction()) db()->rollBack(); $error='Proje oluşturulamadı: '.$e->getMessage(); }
    }
}
admin_head('Yeni proje'); ?>
<div class="page-head"><div><p class="eyebrow">YENİ PROJE</p><h1>Önce proje, sonra çalışma biçimi.</h1><p>Atölye ve Hikâye ayrı içerikler değil; aynı projenin iki görünümü.</p></div></div><?php if($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
<form class="panel" method="post"><?= csrf_field() ?><div class="form-grid"><div class="field"><label>Proje başlığı</label><input name="title" value="<?= e(old('title')) ?>" required></div><div class="field"><label>Slug</label><input name="slug" value="<?= e(old('slug')) ?>" placeholder="boş bırakılırsa başlıktan oluşur"></div><div class="field full"><label>Merak sorusu</label><input name="question" value="<?= e(old('question')) ?>" placeholder="Bu fikir neden doğdu?"></div><div class="field full"><label>Kısa özet</label><textarea name="summary"><?= e(old('summary')) ?></textarea></div><div class="field"><label>Kategori</label><select name="category_id"><option value="">Seçin</option><?php foreach($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['title']) ?></option><?php endforeach; ?></select></div><div class="field"><label>Başlangıç biçimi</label><select name="mode"><option value="workshop">Atölye açık başlat</option><option value="story">Hikâye taslağı başlat</option><option value="draft">Yalnızca proje taslağı</option></select></div><div class="field full"><label>Atölye sorusu</label><input name="workshop_question" value="<?= e(old('workshop_question')) ?>" placeholder="Bu denemede neyi çözmeye çalışıyorum?"></div><div class="field"><label>Görünürlük</label><select name="visibility"><option value="private">Gizli</option><option value="unlisted">Bağlantıya sahip olanlar</option><option value="public">Herkese açık</option></select></div><div class="field"><label>Ana sayfa alanı</label><select name="home_section"><option value="none">Gösterme</option><option value="focus">Büyük hikâye</option><option value="trace">Masanın diğer tarafı</option></select></div><div class="field"><label>Sıra</label><input type="number" step="0.1" name="sort_order" value="999"></div><div class="field full check-row"><label class="check"><input type="checkbox" name="show_on_home"> Ana sayfada göster</label><label class="check"><input type="checkbox" name="show_in_archive" checked> Arşivde göster</label><label class="check"><input type="checkbox" name="show_in_widget"> Atölye penceresinde göster</label></div></div><div class="form-actions"><button class="accent" type="submit">Projeyi oluştur</button><a class="button secondary" href="index.php">Vazgeç</a></div></form>
<?php admin_foot(); ?>
