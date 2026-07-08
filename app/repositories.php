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
    $row['slug']=(string)$row['slug'];
    $row['project_title']=$row['project_title'] ?? $row['title'] ?? '';
    $row['category']=$row['category_slug'] ?? '';
    $row['category_label']=$row['category_title'] ?? '';
    $row['cover']=media_url($row['cover_path'] ?? '');
    $row['kind']=($row['workshop_status'] ?? 'none')==='open' ? 'atelier' : 'story';
    $row['public'] = ($row['visibility'] ?? '')==='public';
    $row['homepage']=(bool)($row['show_on_home'] ?? false);
    $row['order']=$row['sort_order'] ?? 999;
    return $row;
}

function public_projects(bool $archiveOnly=false): array
{
    $placement = $archiveOnly ? 'p.show_in_archive=1' : 'p.show_on_home=1';
    $projectVisibility = VisibilityService::publicProjectSql('p');
    $storyVisibility = VisibilityService::publishedPublicStorySql('s');
    $sql="SELECT p.*, c.slug category_slug, c.title category_title, m.relative_path cover_path,
                  s.id story_id, s.status story_status, s.visibility story_visibility,
                  s.reading_time, s.title story_title, s.question story_question, s.summary story_summary,
                  s.show_on_home story_show_on_home, s.show_in_archive story_show_in_archive, s.sort_order story_sort_order
           FROM projects p
           LEFT JOIN categories c ON c.id=p.category_id
           LEFT JOIN media m ON m.id=p.cover_media_id AND m.deleted_at IS NULL
           LEFT JOIN stories s ON s.project_id=p.id AND s.deleted_at IS NULL
           WHERE $projectVisibility AND $placement ";
    // Public placement is canonical on projects.*; story show_* columns are kept only for legacy compatibility.
    $sql.=" AND $storyVisibility";
    $sql.=' ORDER BY p.is_pinned DESC, p.sort_order ASC, COALESCE(p.updated_at,p.created_at) DESC';
    $rows=db()->query($sql)->fetchAll();
    $out=[];
    foreach ($rows as $r) {
        $r['project_title']=$r['title'];
        if (($r['story_status'] ?? '')==='published') {
            $r['title']=$r['story_title'] ?: $r['title'];
            $r['question']=$r['story_question'] ?: $r['question'];
            $r['summary']=$r['story_summary'] ?: $r['summary'];
        }
        $out[$r['slug']]=project_row_to_view($r);
    }
    return $out;
}

function project_by_slug(string $slug, bool $admin=false): ?array
{
    $sql="SELECT p.*, c.slug category_slug, c.title category_title, m.relative_path cover_path,
                  s.id story_id, s.status story_status, s.visibility story_visibility,
                  s.title story_title, s.question story_question, s.summary story_summary,
                  s.reading_time, s.show_on_home story_show_on_home, s.show_in_archive story_show_in_archive,
                  s.is_pinned story_is_pinned, s.sort_order story_sort_order, s.published_at story_published_at
           FROM projects p
           LEFT JOIN categories c ON c.id=p.category_id
           LEFT JOIN media m ON m.id=p.cover_media_id AND m.deleted_at IS NULL
           LEFT JOIN stories s ON s.project_id=p.id AND s.deleted_at IS NULL
           WHERE p.slug=? AND p.deleted_at IS NULL";
    if (!$admin) $sql.=" AND " . VisibilityService::publicReadableProjectSql('p');
    $st=db()->prepare($sql); $st->execute([$slug]); $r=$st->fetch();
    if (!$r) return null;
    $r['project_title']=$r['title'];
    if (($r['story_status'] ?? '')==='published') {
        $r['title']=$r['story_title'] ?: $r['title'];
        $r['question']=$r['story_question'] ?: $r['question'];
        $r['summary']=$r['story_summary'] ?: $r['summary'];
    }
    $r=project_row_to_view($r);
    $r['tags']=project_tags((int)$r['id']);
    return $r;
}

function project_tags(int $projectId): array
{
    $st=db()->prepare('SELECT t.name FROM tags t JOIN project_tags pt ON pt.tag_id=t.id WHERE pt.project_id=? ORDER BY t.name');
    $st->execute([$projectId]); return array_column($st->fetchAll(),'name');
}

function story_url(array $project): string
{
    $slug=rawurlencode((string)$project['slug']);
    if (($project['workshop_status'] ?? 'none')==='open' && ($project['story_status'] ?? '')!=='published') return 'atolye.php?slug='.$slug;
    return 'hikaye.php?slug='.$slug;
}

function story_by_project(int $projectId, bool $admin=false): ?array
{
    $sql='SELECT * FROM stories WHERE project_id=? AND deleted_at IS NULL';
    if (!$admin) $sql.=" AND " . VisibilityService::publishedReadableStorySql('stories');
    $st=db()->prepare($sql); $st->execute([$projectId]); return $st->fetch() ?: null;
}

function story_sections(int $storyId): array
{
    $st=db()->prepare("SELECT ss.*, sp.title part_title, sp.subtitle part_subtitle, sp.description part_description, sp.anchor part_anchor, sp.sort_order part_sort_order,
      m.relative_path media_path, m.alt_text media_alt, m.caption media_caption,
      m.media_type, m.mime_type media_mime_type, m.title media_title, m.original_name media_original_name
      FROM story_sections ss LEFT JOIN media m ON m.id=ss.media_id AND m.deleted_at IS NULL
      LEFT JOIN story_parts sp ON sp.id=ss.part_id
      WHERE ss.story_id=? AND ss.deleted_at IS NULL ORDER BY ss.sort_order,ss.id");
    $st->execute([$storyId]); $sections=$st->fetchAll();
    $itemSt=db()->prepare("SELECT i.*, m.relative_path media_path, m.alt_text media_alt, m.caption media_caption, m.media_type
      FROM story_section_items i LEFT JOIN media m ON m.id=i.media_id AND m.deleted_at IS NULL
      WHERE i.section_id=? ORDER BY i.sort_order,i.id");
    $mediaSt=db()->prepare("SELECT sm.*,m.relative_path,m.alt_text,m.caption,m.media_type,m.title
      FROM story_section_media sm JOIN media m ON m.id=sm.media_id AND m.deleted_at IS NULL
      WHERE sm.section_id=? ORDER BY sm.sort_order,sm.id");
    $linkSt=db()->prepare("SELECT * FROM links WHERE owner_type='story_section' AND owner_id=? ORDER BY sort_order,id");
    foreach ($sections as &$s) {
        $itemSt->execute([$s['id']]); $s['items']=$itemSt->fetchAll();
        $mediaSt->execute([$s['id']]); $s['media']=$mediaSt->fetchAll();
        $linkSt->execute([$s['id']]); $s['links']=$linkSt->fetchAll();
    }
    unset($s); return $sections;
}

function story_parts(int $storyId): array
{
    $st=db()->prepare('SELECT * FROM story_parts WHERE story_id=? ORDER BY sort_order,id');
    $st->execute([$storyId]);
    return $st->fetchAll();
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
    $st=db()->prepare('SELECT * FROM links WHERE owner_type=? AND owner_id=? ORDER BY sort_order,id');
    $st->execute([$type,$ownerId]); return $st->fetchAll();
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
    $where='p.deleted_at IS NULL';
    if ($filter==='trash') $where='p.deleted_at IS NOT NULL';
    elseif ($filter==='workshop') $where.=" AND p.workshop_status IN ('open','paused')";
    elseif ($filter==='published') $where.=" AND s.status='published'";
    elseif ($filter==='draft') $where.=" AND (s.status='draft' OR s.id IS NULL)";
    $sql="SELECT p.*,c.title category_title,m.relative_path cover_path,s.id story_id,s.status story_status,s.visibility story_visibility,s.published_at story_published_at,
      (SELECT COUNT(*) FROM updates u WHERE u.project_id=p.id AND u.deleted_at IS NULL) update_count
      FROM projects p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN media m ON m.id=p.cover_media_id
      LEFT JOIN stories s ON s.project_id=p.id AND s.deleted_at IS NULL WHERE $where ORDER BY p.is_pinned DESC,p.sort_order,p.updated_at DESC";
    return db()->query($sql)->fetchAll();
}
