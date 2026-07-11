<?php
declare(strict_types=1);
require_once __DIR__ . '/../_private.php';
fv7_require_private('app/bootstrap.php');

function admin_is_local(): bool
{
    $ip=(string)($_SERVER['REMOTE_ADDR'] ?? '');
    return $ip==='::1' || $ip==='127.0.0.1' || str_starts_with($ip,'127.');
}
if(FV7_ADMIN_LOCAL_ONLY && !admin_is_local()) { http_response_code(403); exit('Bu yönetim paneli yalnızca yerel bilgisayarda çalışacak şekilde kilitlidir.'); }

function admin_count_users(): int { return (int)db()->query('SELECT COUNT(*) FROM admin_users WHERE is_active=1')->fetchColumn(); }
function admin_logged_in(): bool { return !empty($_SESSION['admin_user_id']); }
function admin_require_login(): void { if(!admin_logged_in()) redirect('login.php'); }
function admin_user(): ?array {
    if(!admin_logged_in()) return null; $st=db()->prepare('SELECT * FROM admin_users WHERE id=? AND is_active=1'); $st->execute([(int)$_SESSION['admin_user_id']]); return $st->fetch()?:null;
}
function admin_audit(string $action,string $entityType,int $entityId=0,string $details=''): void {
    $st=db()->prepare('INSERT INTO audit_log(user_id,action,entity_type,entity_id,details) VALUES (?,?,?,?,?)');
    $st->execute([$_SESSION['admin_user_id'] ?? null,$action,$entityType,$entityId?:null,$details]);
}
function admin_head(string $title): void { $u=admin_user(); $flashes=pull_flashes(); ?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($title) ?> · FikrimVar V7</title><link rel="stylesheet" href="assets/admin.css"><script src="assets/admin.js" defer></script></head><body><header class="admin-header"><a class="admin-brand" href="index.php"><strong>#FikrimVar</strong><span>V7 · SQLite içerik yönetimi</span></a><?php if($u): ?><nav><a href="index.php">Projeler</a><a href="project-new.php">Yeni proje</a><a href="ordering.php">Yayın ve sıra</a><a href="media.php">Medya</a><a href="deploy.php">Yayın Merkezi</a><a href="notes.php">Notlar</a><a href="settings.php">Ayarlar</a><a href="backup.php">Yedek</a><a href="../" target="_blank">Siteyi aç</a><a href="logout.php">Çıkış</a></nav><?php endif; ?></header><main class="admin-main"><?php foreach($flashes as $f): ?><div class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></div><?php endforeach; ?>
<?php }
function admin_foot(): void { ?></main><footer class="admin-footer"><span>Yerel panel</span><a href="system.php">Sistem kontrolü</a></footer></body></html><?php }

function upload_limits(): array { return ['image'=>FV7_IMAGE_MAX_BYTES,'video'=>FV7_VIDEO_MAX_BYTES,'audio'=>FV7_OTHER_MAX_BYTES,'file'=>FV7_OTHER_MAX_BYTES]; }
function detect_media_type(string $mime): string {
    if(str_starts_with($mime,'image/')) return 'image'; if(str_starts_with($mime,'video/')) return 'video'; if(str_starts_with($mime,'audio/')) return 'audio'; return 'file';
}
function allowed_mimes(): array { return [
 'image/jpeg','image/png','image/webp','image/gif','video/mp4','video/webm','audio/mpeg','audio/wav','audio/ogg','application/pdf','text/plain'
]; }
function save_uploaded_files(int $projectId,string $projectSlug,string $field='media_files'): array
{
    if(empty($_FILES[$field]) || !is_array($_FILES[$field]['name'])) return [];
    $files=$_FILES[$field]; $saved=[]; $finfo=new finfo(FILEINFO_MIME_TYPE); $limits=upload_limits();
    $dir=FV7_UPLOAD_ROOT.'/projects/'.$projectSlug; if(!is_dir($dir)) mkdir($dir,0775,true);
    foreach($files['name'] as $i=>$original) {
        if(($files['error'][$i] ?? UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) continue;
        if(($files['error'][$i] ?? UPLOAD_ERR_OK)!==UPLOAD_ERR_OK) throw new RuntimeException('Dosya yükleme hatası: '.$original);
        $tmp=$files['tmp_name'][$i]; $mime=$finfo->file($tmp) ?: 'application/octet-stream';
        if(!in_array($mime,allowed_mimes(),true)) throw new RuntimeException('Bu dosya türüne izin verilmiyor: '.$mime);
        $type=detect_media_type($mime); $size=(int)$files['size'][$i]; if($size>($limits[$type] ?? FV7_OTHER_MAX_BYTES)) throw new RuntimeException('Dosya boyutu sınırı aşıldı: '.$original);
        $extensionMap = [
            'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif',
            'video/mp4'=>'mp4','video/webm'=>'webm','audio/mpeg'=>'mp3','audio/wav'=>'wav',
            'audio/ogg'=>'ogg','application/pdf'=>'pdf','text/plain'=>'txt'
        ];
        $ext = $extensionMap[$mime] ?? 'bin';
        $safeBase=safe_slug(pathinfo((string)$original,PATHINFO_FILENAME)) ?: 'medya';
        $fileName=date('Ymd-His').'-'.$safeBase.'-'.bin2hex(random_bytes(3)).'.'.$ext; $dest=$dir.'/'.$fileName;
        if(!move_uploaded_file($tmp,$dest)) throw new RuntimeException('Dosya taşınamadı: '.$original);
        $width=$height=null; if($type==='image' && $mime!=='image/svg+xml'){ $info=@getimagesize($dest); if($info){$width=$info[0];$height=$info[1];} }
        $rel='uploads/projects/'.$projectSlug.'/'.$fileName; $sha=hash_file('sha256',$dest) ?: '';
        $st=db()->prepare('INSERT INTO media(project_id,file_name,original_name,relative_path,mime_type,media_type,size_bytes,width,height,checksum_sha256) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $st->execute([$projectId,$fileName,$original,$rel,$mime,$type,$size,$width,$height,$sha]); $saved[]=(int)db()->lastInsertId();
    }
    return $saved;
}
function project_media_admin(int $projectId): array { return MediaRepository::forProjectAdmin($projectId); }
function soft_delete_project(int $id): void { $st=db()->prepare('UPDATE projects SET deleted_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=?');$st->execute([$id]); }

function admin_home_section_label(?string $section): string
{
    return match((string)$section) {
        'focus' => 'Öne çıkan büyük kart',
        'trace' => 'Alt şerit / küçük kayıt',
        default => 'Kapalı',
    };
}

function admin_yes_no(bool $value): string { return $value ? 'Evet' : 'Hayır'; }

function admin_story_public(?array $story): bool
{
    return VisibilityService::storyIsPublishedPublic($story);
}

function admin_next_sort_order(string $scope, string $homeSection='none'): float
{
    if($scope==='home' && in_array($homeSection,['focus','trace'],true)){
        $st=db()->prepare("SELECT COALESCE(MAX(sort_order),0) FROM projects WHERE deleted_at IS NULL AND show_on_home=1 AND home_section=?");
        $st->execute([$homeSection]);
        return (float)$st->fetchColumn()+10;
    }
    if($scope==='archive'){
        return (float)db()->query("SELECT COALESCE(MAX(sort_order),0) FROM projects WHERE deleted_at IS NULL AND show_in_archive=1")->fetchColumn()+10;
    }
    return 999.0;
}

function admin_resolve_project_sort_order(string $raw,bool $showHome,string $homeSection,bool $showArchive): float
{
    if(trim($raw)!=='') return (float)$raw;
    if($showHome && in_array($homeSection,['focus','trace'],true)) return admin_next_sort_order('home',$homeSection);
    if($showArchive) return admin_next_sort_order('archive');
    return 999.0;
}

function admin_normalize_project_publication(bool $showHome,string $homeSection,bool $showArchive,bool $showWidget,string $workshopStatus): array
{
    $warnings=[];
    if(VisibilityService::homeSectionIsVisible($homeSection) && !$showHome){
        $showHome=true;
        $warnings[]='Ana sayfadaki yer secildigi icin Ana sayfada goster tiki otomatik acildi.';
    }
    if($showHome && !VisibilityService::homeSectionIsVisible($homeSection)){
        $homeSection='trace';
        $warnings[]='Ana sayfada goster acik oldugu icin ana sayfadaki yer Alt serit / kucuk kayit olarak ayarlandi.';
    }
    if($showWidget && !VisibilityService::workshopStatusAllowsWidget($workshopStatus)){
        $showWidget=false;
        $warnings[]='Atolye durumu Acik veya Beklemede olmadigi icin Atolye penceresi tiki otomatik kapatildi.';
    }

    return [
        'show_home'=>$showHome,
        'home_section'=>$homeSection,
        'show_archive'=>$showArchive,
        'show_widget'=>$showWidget,
        'warnings'=>$warnings,
    ];
}

function admin_project_sort_conflicts(int $projectId,float $sortOrder,bool $showHome,string $homeSection,bool $showArchive): array
{
    $messages=[];
    if($showHome && in_array($homeSection,['focus','trace'],true)){
        $st=db()->prepare("SELECT title FROM projects WHERE id<>? AND deleted_at IS NULL AND show_on_home=1 AND home_section=? AND sort_order=? ORDER BY title");
        $st->execute([$projectId,$homeSection,$sortOrder]);
        $titles=array_column($st->fetchAll(),'title');
        if($titles) $messages[]='Ana sayfa / '.admin_home_section_label($homeSection).' alanında aynı sıra numarasını kullanan proje var: '.implode(', ',$titles);
    }
    if($showArchive){
        $st=db()->prepare("SELECT title FROM projects WHERE id<>? AND deleted_at IS NULL AND show_in_archive=1 AND sort_order=? ORDER BY title");
        $st->execute([$projectId,$sortOrder]);
        $titles=array_column($st->fetchAll(),'title');
        if($titles) $messages[]='Hikâyeler sayfasında aynı sıra numarasını kullanan proje var: '.implode(', ',$titles);
    }
    return $messages;
}

function admin_render_visibility_summary(array $project, ?array $story=null): void
{
    $homeVisible = VisibilityService::homeVisible($project, $story);
    $archiveVisible = VisibilityService::archiveVisible($project, $story);
    $widgetVisible = VisibilityService::widgetVisible($project);
    ?>
    <section class="panel">
        <h2>Neden görünmüyor?</h2>
        <div class="list">
            <div class="list-row"><span>Ana sayfa</span><strong><?= e($homeVisible ? 'Görünür' : 'Görünmez') ?></strong><small><?= e(VisibilityService::homeReason($project, $story)) ?></small></div>
            <div class="list-row"><span>Hikâyeler sayfası</span><strong><?= e($archiveVisible ? 'Görünür' : 'Görünmez') ?></strong><small><?= e(VisibilityService::archiveReason($project, $story)) ?></small></div>
            <div class="list-row"><span>Atölye penceresi</span><strong><?= e($widgetVisible ? 'Görünür' : 'Görünmez') ?></strong><small><?= e(VisibilityService::widgetReason($project)) ?></small></div>
        </div>
        <p class="help">Ana sayfa ve Hikâyeler için hikâye yayımlanmış ve herkese açık olmalı. Atölye penceresi ise hikâye yayınından bağımsızdır.</p>
    </section>
    <?php
}

function unique_update_slug(int $projectId,string $base,int $ignoreId=0): string
{
    $base=safe_slug($base) ?: 'kayit-'.date('Ymd-His'); $slug=$base; $i=2;
    while(true){$st=db()->prepare('SELECT COUNT(*) FROM updates WHERE project_id=? AND slug=? AND id<>?');$st->execute([$projectId,$slug,$ignoreId]);if((int)$st->fetchColumn()===0)return $slug;$slug=$base.'-'.$i++;}
}
function admin_link_title(string $title,string $type,string $url): string
{
    $title=trim($title);
    if($title!=='') return $title;
    $model=LinkRenderer::fromRow(['title'=>'','link_type'=>$type,'url'=>$url]);
    return $model ? $model->title : 'Baglanti';
}
function save_update_links(int $updateId,array $links): void
{
    LinkRepository::replaceForOwner('update', $updateId, $links, fn($title,$type,$url) => admin_link_title((string)$title, (string)$type, (string)$url));
}
function assert_project_media_ids(int $projectId,array $mediaIds,string $context='Medya'): array
{
    return MediaRepository::assertProjectMediaIds($projectId, $mediaIds, $context);
}
function attach_update_media(int $updateId,int $projectId,array $mediaIds): void
{
    MediaRepository::attachToUpdate($updateId, $projectId, $mediaIds);
}
