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
    return UpdateRepository::forProject($projectId, $publishedOnly);
}

function update_media(int $updateId): array
{
    return MediaRepository::forUpdate($updateId);
}

function owner_links(string $type, int $ownerId): array
{
    return LinkRepository::findByOwner($type, $ownerId);
}

function recent_updates(int $limit=4): array
{
    return UpdateRepository::recent($limit);
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
        'stories' => (int)db()->query("SELECT COUNT(*) FROM stories s JOIN projects p ON p.id=s.project_id WHERE s.status='published' AND s.visibility='public' AND s.deleted_at IS NULL AND p.deleted_at IS NULL AND p.visibility='public' AND p.show_in_archive=1")->fetchColumn(),
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
