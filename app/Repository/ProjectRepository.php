<?php
declare(strict_types=1);

final class ProjectRepository
{
    public static function rowToView(array $row): array
    {
        $row['slug'] = (string)$row['slug'];
        $row['project_title'] = $row['project_title'] ?? $row['title'] ?? '';
        $row['category'] = $row['category_slug'] ?? '';
        $row['category_label'] = $row['category_title'] ?? '';
        $row['cover'] = media_url($row['cover_path'] ?? '');
        $row['kind'] = ($row['workshop_status'] ?? 'none') === 'open' ? 'atelier' : 'story';
        $row['public'] = ($row['visibility'] ?? '') === 'public';
        $row['homepage'] = (bool)($row['show_on_home'] ?? false);
        $row['order'] = $row['sort_order'] ?? 999;
        return $row;
    }

    public static function publicList(bool $archiveOnly = false): array
    {
        $placement = $archiveOnly ? 'p.show_in_archive=1' : 'p.show_on_home=1';
        $projectVisibility = VisibilityService::publicProjectSql('p');
        $storyVisibility = VisibilityService::publishedPublicStorySql('s');
        $sql = "SELECT p.*, c.slug category_slug, c.title category_title, m.relative_path cover_path,
                       s.id story_id, s.status story_status, s.visibility story_visibility,
                       s.reading_time, s.title story_title, s.question story_question, s.summary story_summary,
                       s.show_on_home story_show_on_home, s.show_in_archive story_show_in_archive, s.sort_order story_sort_order
                FROM projects p
                LEFT JOIN categories c ON c.id=p.category_id
                LEFT JOIN media m ON m.id=p.cover_media_id AND m.deleted_at IS NULL
                LEFT JOIN stories s ON s.project_id=p.id AND s.deleted_at IS NULL
                WHERE $projectVisibility AND $placement ";
        if ($archiveOnly) {
            $sql .= " AND p.workshop_status NOT IN ('open','paused')";
        }
        // Public placement is canonical on projects.*; story show_* columns are kept only for legacy compatibility.
        $sql .= " AND $storyVisibility";
        $sql .= ' ORDER BY p.is_pinned DESC, p.sort_order ASC, COALESCE(p.updated_at,p.created_at) DESC';

        $rows = db()->query($sql)->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $row['project_title'] = $row['title'];
            if (($row['story_status'] ?? '') === 'published') {
                $row['title'] = $row['story_title'] ?: $row['title'];
                $row['question'] = $row['story_question'] ?: $row['question'];
                $row['summary'] = $row['story_summary'] ?: $row['summary'];
            }
            $out[$row['slug']] = self::rowToView($row);
        }

        return $out;
    }

    public static function findBySlug(string $slug, bool $admin = false): ?array
    {
        $sql = "SELECT p.*, c.slug category_slug, c.title category_title, m.relative_path cover_path,
                       s.id story_id, s.status story_status, s.visibility story_visibility,
                       s.title story_title, s.question story_question, s.summary story_summary,
                       s.reading_time, s.show_on_home story_show_on_home, s.show_in_archive story_show_in_archive,
                       s.is_pinned story_is_pinned, s.sort_order story_sort_order, s.published_at story_published_at
                FROM projects p
                LEFT JOIN categories c ON c.id=p.category_id
                LEFT JOIN media m ON m.id=p.cover_media_id AND m.deleted_at IS NULL
                LEFT JOIN stories s ON s.project_id=p.id AND s.deleted_at IS NULL
                WHERE p.slug=? AND p.deleted_at IS NULL";
        if (!$admin) {
            $sql .= " AND " . VisibilityService::publicReadableProjectSql('p');
        }

        $stmt = db()->prepare($sql);
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $row['project_title'] = $row['title'];
        $workshopStatus = (string)($row['workshop_status'] ?? 'none');
        if (($row['story_status'] ?? '') === 'published' && !in_array($workshopStatus, ['open', 'paused'], true)) {
            $row['title'] = $row['story_title'] ?: $row['title'];
            $row['question'] = $row['story_question'] ?: $row['question'];
            $row['summary'] = $row['story_summary'] ?: $row['summary'];
        }

        $row = self::rowToView($row);
        $row['tags'] = self::tags((int)$row['id']);
        return $row;
    }

    public static function tags(int $projectId): array
    {
        $stmt = db()->prepare('SELECT t.name FROM tags t JOIN project_tags pt ON pt.tag_id=t.id WHERE pt.project_id=? ORDER BY t.name');
        $stmt->execute([$projectId]);
        return array_column($stmt->fetchAll(), 'name');
    }

    public static function adminList(string $filter = 'all'): array
    {
        $where = 'p.deleted_at IS NULL';
        if ($filter === 'trash') {
            $where = 'p.deleted_at IS NOT NULL';
        } elseif ($filter === 'workshop') {
            $where .= " AND p.workshop_status IN ('open','paused')";
        } elseif ($filter === 'published') {
            $where .= " AND p.visibility='public' AND p.show_in_archive=1 AND s.status='published' AND s.visibility='public'";
        } elseif ($filter === 'story-published') {
            $where .= " AND s.status='published'";
        } elseif ($filter === 'draft') {
            $where .= " AND (s.status='draft' OR s.id IS NULL)";
        }

        $sql = "SELECT p.*,c.title category_title,m.relative_path cover_path,s.id story_id,s.status story_status,s.visibility story_visibility,s.published_at story_published_at,
                       (SELECT COUNT(*) FROM updates u WHERE u.project_id=p.id AND u.deleted_at IS NULL) update_count
                FROM projects p
                LEFT JOIN categories c ON c.id=p.category_id
                LEFT JOIN media m ON m.id=p.cover_media_id
                LEFT JOIN stories s ON s.project_id=p.id AND s.deleted_at IS NULL
                WHERE $where
                ORDER BY p.is_pinned DESC,p.sort_order,p.updated_at DESC";

        return db()->query($sql)->fetchAll();
    }
}
