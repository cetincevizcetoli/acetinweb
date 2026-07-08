<?php
declare(strict_types=1);

final class UpdateRepository
{
    public static function forProject(int $projectId, bool $publishedOnly = true): array
    {
        $sql = 'SELECT * FROM updates WHERE project_id=? AND deleted_at IS NULL';
        if ($publishedOnly) {
            $sql .= " AND status='published' AND visibility IN ('public','unlisted')";
        }
        $sql .= ' ORDER BY COALESCE(work_date,created_at),sort_order,id';

        $stmt = db()->prepare($sql);
        $stmt->execute([$projectId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['media'] = MediaRepository::forUpdate((int)$row['id']);
            $row['links'] = LinkRepository::findByOwner('update', (int)$row['id']);
            $row['_id'] = $row['slug'];
            $row['date_label'] = $row['display_label'] ?: ($row['work_date'] ? date('d.m.Y', strtotime($row['work_date'])) : '');
            $row['day'] = $row['display_label'];
            $row['next'] = $row['next_step'];
        }
        unset($row);

        return $rows;
    }

    public static function recent(int $limit = 4): array
    {
        $limit = max(1, min(20, $limit));
        $sql = "SELECT u.*, p.slug project_slug,p.title project_title,p.question project_question,p.summary project_summary,
                       p.status_label,p.type_label,p.workshop_status,p.visibility,p.id project_id,
                       c.slug category_slug,c.title category_title,m.relative_path cover_path,
                       s.status story_status,s.reading_time
                FROM updates u JOIN projects p ON p.id=u.project_id AND p.deleted_at IS NULL
                LEFT JOIN categories c ON c.id=p.category_id
                LEFT JOIN media m ON m.id=p.cover_media_id AND m.deleted_at IS NULL
                LEFT JOIN stories s ON s.project_id=p.id AND s.deleted_at IS NULL
                WHERE u.deleted_at IS NULL AND u.status='published' AND u.visibility='public' AND u.show_in_recent=1 AND p.visibility='public'
                ORDER BY COALESCE(u.work_date,u.published_at,u.created_at) DESC,u.id DESC LIMIT " . $limit;

        $rows = db()->query($sql)->fetchAll();
        $out = [];
        foreach ($rows as $update) {
            $project = ProjectRepository::rowToView([
                'id' => $update['project_id'],
                'slug' => $update['project_slug'],
                'title' => $update['project_title'],
                'question' => $update['project_question'],
                'summary' => $update['project_summary'],
                'status_label' => $update['status_label'],
                'type_label' => $update['type_label'],
                'workshop_status' => $update['workshop_status'],
                'visibility' => $update['visibility'],
                'category_slug' => $update['category_slug'],
                'category_title' => $update['category_title'],
                'cover_path' => $update['cover_path'],
                'story_status' => $update['story_status'],
                'reading_time' => $update['reading_time'],
            ]);

            $update['links'] = LinkRepository::findByOwner('update', (int)$update['id']);
            $update['_id'] = $update['slug'];
            $update['date_label'] = $update['display_label'] ?: ($update['work_date'] ? date('d.m.Y', strtotime($update['work_date'])) : '');
            $out[] = ['update' => $update, 'story' => $project];
        }

        return $out;
    }
}
