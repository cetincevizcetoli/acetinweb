<?php
declare(strict_types=1);

function categories(bool $activeOnly=true): array
{
    $sql='SELECT * FROM categories';
    if ($activeOnly) $sql.=' WHERE is_active=1';
    $sql.=' ORDER BY sort_order, title';
    return db()->query($sql)->fetchAll();
}

function channels(): array
{
    return db()->query('SELECT * FROM channels WHERE is_active=1 AND url<>\'\' ORDER BY sort_order,id')->fetchAll();
}

function project_row_to_view(array $row): array
{
    return ProjectRepository::rowToView($row);
}

function public_projects(bool $archiveOnly=false): array
{
    return ProjectRepository::publicList($archiveOnly);
}

function project_by_slug(string $slug, bool $admin=false): ?array
{
    return ProjectRepository::findBySlug($slug, $admin);
}

function project_tags(int $projectId): array
{
    return ProjectRepository::tags($projectId);
}

function story_url(array $project): string
{
    $slug=rawurlencode((string)$project['slug']);
    if (($project['workshop_status'] ?? 'none')==='open' && ($project['story_status'] ?? '')!=='published') return 'atolye.php?slug='.$slug;
    return 'hikaye.php?slug='.$slug;
}

function story_by_project(int $projectId, bool $admin=false): ?array
{
    return StoryRepository::findByProject($projectId, $admin);
}

function story_sections(int $storyId): array
{
    return StoryRepository::sections($storyId);
}

function story_parts(int $storyId): array
{
    return StoryRepository::parts($storyId);
}

function project_updates(int $projectId, bool $publishedOnly=true): array
{
    $sql='SELECT * FROM updates WHERE project_id=? AND deleted_at IS NULL';
    if ($publishedOnly) $sql.=" AND status='published' AND visibility IN ('public','unlisted')";
    $sql.=' ORDER BY COALESCE(work_date,created_at),sort_order,id';
    $st=db()->prepare($sql); $st->execute([$projectId]); $rows=$st->fetchAll();
    foreach ($rows as &$r) {
        $r['media']=update_media((int)$r['id']);
        $r['links']=owner_links('update',(int)$r['id']);
        $r['_id']=$r['slug'];
        $r['date_label']=$r['display_label'] ?: ($r['work_date'] ? date('d.m.Y',strtotime($r['work_date'])) : '');
        $r['day']=$r['display_label'];
        $r['next']=$r['next_step'];
    }
    unset($r); return $rows;
}

function update_media(int $updateId): array
{
    $st=db()->prepare("SELECT um.*,m.relative_path,m.alt_text,m.caption,m.media_type,m.title,m.mime_type
      FROM update_media um JOIN media m ON m.id=um.media_id AND m.deleted_at IS NULL
      WHERE um.update_id=? ORDER BY um.sort_order,um.id");
    $st->execute([$updateId]); return $st->fetchAll();
}

function owner_links(string $type, int $ownerId): array
{
    return LinkRepository::findByOwner($type, $ownerId);
}

function recent_updates(int $limit=4): array
{
    $limit=max(1,min(20,$limit));
    $sql="SELECT u.*, p.slug project_slug,p.title project_title,p.question project_question,p.summary project_summary,
                  p.status_label,p.type_label,p.workshop_status,p.visibility,p.id project_id,
                  c.slug category_slug,c.title category_title,m.relative_path cover_path,
                  s.status story_status,s.reading_time
      FROM updates u JOIN projects p ON p.id=u.project_id AND p.deleted_at IS NULL
      LEFT JOIN categories c ON c.id=p.category_id
      LEFT JOIN media m ON m.id=p.cover_media_id AND m.deleted_at IS NULL
      LEFT JOIN stories s ON s.project_id=p.id AND s.deleted_at IS NULL
      WHERE u.deleted_at IS NULL AND u.status='published' AND u.visibility='public' AND u.show_in_recent=1 AND p.visibility='public'
      ORDER BY COALESCE(u.work_date,u.published_at,u.created_at) DESC,u.id DESC LIMIT ".$limit;
    $rows=db()->query($sql)->fetchAll(); $out=[];
    foreach ($rows as $u) {
        $p=project_row_to_view([
            'id'=>$u['project_id'],'slug'=>$u['project_slug'],'title'=>$u['project_title'],'question'=>$u['project_question'],
            'summary'=>$u['project_summary'],'status_label'=>$u['status_label'],'type_label'=>$u['type_label'],
            'workshop_status'=>$u['workshop_status'],'visibility'=>$u['visibility'],'category_slug'=>$u['category_slug'],
            'category_title'=>$u['category_title'],'cover_path'=>$u['cover_path'],'story_status'=>$u['story_status'],'reading_time'=>$u['reading_time']
        ]);
        $u['links']=owner_links('update',(int)$u['id']);
        $u['_id']=$u['slug']; $u['date_label']=$u['display_label'] ?: ($u['work_date'] ? date('d.m.Y',strtotime($u['work_date'])) : '');
        $out[]=['update'=>$u,'story'=>$p];
    }
    return $out;
}

function widget_workshops(): array
{
    $st=db()->query("SELECT slug FROM projects WHERE " . VisibilityService::widgetProjectSql('projects') . " ORDER BY is_pinned DESC,sort_order");
    $out=[];
    foreach($st->fetchAll() as $row){
        $project=project_by_slug((string)$row['slug']);
        if(!$project) continue;
        $updates=project_updates((int)$project['id']);
        $latest=$updates ? end($updates) : [
            'slug'=>'',
            'title'=>$project['workshop_question'] ?: ($project['question'] ?: $project['title']),
            'summary'=>$project['summary'] ?: 'Bu atölye için henüz ayrı çalışma kaydı girilmedi.',
            'decision'=>$project['status_label'] ?? '',
            'next_step'=>$project['workshop_status']==='paused' ? 'Beklemede' : 'Atölye kaydı bekliyor.',
            'date_label'=>$project['started_at'] ? date('d.m.Y',strtotime((string)$project['started_at'])) : 'Başlangıç',
            'media'=>[],
            'links'=>[],
        ];
        $project['update_count']=count($updates);
        $out[]=['project'=>$project,'latest'=>$latest];
    }
    return $out;
}

function pinned_workshop(): ?array
{
    $workshops=widget_workshops();
    return $workshops[0]['project'] ?? null;
}

function public_notes(): array
{
    return db()->query("SELECT name,message,created_at FROM notes WHERE status='published' ORDER BY published_at DESC,id DESC LIMIT 50")->fetchAll();
}

function count_public(string $kind): int
{
    return match($kind) {
        'stories' => (int)db()->query("SELECT COUNT(*) FROM stories s JOIN projects p ON p.id=s.project_id WHERE s.status='published' AND s.visibility='public' AND s.deleted_at IS NULL AND p.deleted_at IS NULL AND p.visibility='public'")->fetchColumn(),
        'workshops' => (int)db()->query("SELECT COUNT(*) FROM projects WHERE workshop_status='open' AND visibility='public' AND deleted_at IS NULL")->fetchColumn(),
        'methods' => (int)db()->query("SELECT COUNT(*) FROM projects p JOIN categories c ON c.id=p.category_id WHERE c.slug='yz-yontem' AND p.visibility='public' AND p.deleted_at IS NULL")->fetchColumn(),
        'unfinished' => (int)db()->query("SELECT COUNT(*) FROM projects WHERE status IN ('yarim','fikir','not') AND visibility='public' AND deleted_at IS NULL")->fetchColumn(),
        default => 0,
    };
}

function admin_projects(string $filter='all'): array
{
    return ProjectRepository::adminList($filter);
}
