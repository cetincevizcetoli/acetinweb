<?php
declare(strict_types=1);

final class StoryRepository
{
    public static function findByProject(int $projectId, bool $admin = false): ?array
    {
        $sql = 'SELECT * FROM stories WHERE project_id=? AND deleted_at IS NULL';
        if (!$admin) {
            $sql .= " AND " . VisibilityService::publishedReadableStorySql('stories');
        }

        $stmt = db()->prepare($sql);
        $stmt->execute([$projectId]);
        return $stmt->fetch() ?: null;
    }

    public static function sections(int $storyId): array
    {
        $stmt = db()->prepare("SELECT ss.*, sp.title part_title, sp.subtitle part_subtitle, sp.description part_description, sp.anchor part_anchor, sp.sort_order part_sort_order,
          m.relative_path media_path, m.alt_text media_alt, m.caption media_caption,
          m.media_type, m.mime_type media_mime_type, m.title media_title, m.original_name media_original_name
          FROM story_sections ss LEFT JOIN media m ON m.id=ss.media_id AND m.deleted_at IS NULL
          LEFT JOIN story_parts sp ON sp.id=ss.part_id
          WHERE ss.story_id=? AND ss.deleted_at IS NULL ORDER BY ss.sort_order,ss.id");
        $stmt->execute([$storyId]);
        $sections = $stmt->fetchAll();

        $itemStmt = db()->prepare("SELECT i.*, m.relative_path media_path, m.alt_text media_alt, m.caption media_caption, m.media_type
          FROM story_section_items i LEFT JOIN media m ON m.id=i.media_id AND m.deleted_at IS NULL
          WHERE i.section_id=? ORDER BY i.sort_order,i.id");
        $mediaStmt = db()->prepare("SELECT sm.*,m.relative_path,m.alt_text,m.caption,m.media_type,m.title
          FROM story_section_media sm JOIN media m ON m.id=sm.media_id AND m.deleted_at IS NULL
          WHERE sm.section_id=? ORDER BY sm.sort_order,sm.id");
        $linkStmt = db()->prepare("SELECT * FROM links WHERE owner_type='story_section' AND owner_id=? ORDER BY sort_order,id");

        foreach ($sections as &$section) {
            $itemStmt->execute([$section['id']]);
            $section['items'] = $itemStmt->fetchAll();

            $mediaStmt->execute([$section['id']]);
            $section['media'] = $mediaStmt->fetchAll();

            $linkStmt->execute([$section['id']]);
            $section['links'] = $linkStmt->fetchAll();
        }
        unset($section);

        return $sections;
    }

    public static function parts(int $storyId): array
    {
        $stmt = db()->prepare('SELECT * FROM story_parts WHERE story_id=? ORDER BY sort_order,id');
        $stmt->execute([$storyId]);
        return $stmt->fetchAll();
    }
}
