<?php
declare(strict_types=1);

final class MediaRepository
{
    public static function forUpdate(int $updateId): array
    {
        $stmt = db()->prepare("SELECT um.*,m.relative_path,m.alt_text,m.caption,m.media_type,m.title,m.mime_type
          FROM update_media um JOIN media m ON m.id=um.media_id AND m.deleted_at IS NULL
          WHERE um.update_id=? ORDER BY um.sort_order,um.id");
        $stmt->execute([$updateId]);
        return $stmt->fetchAll();
    }

    public static function forProjectAdmin(int $projectId): array
    {
        $stmt = db()->prepare('SELECT * FROM media WHERE project_id=? AND deleted_at IS NULL ORDER BY created_at DESC,id DESC');
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }

    public static function latestAdmin(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        return db()->query('SELECT m.*,p.title project_title FROM media m LEFT JOIN projects p ON p.id=m.project_id WHERE m.deleted_at IS NULL ORDER BY m.created_at DESC,m.id DESC LIMIT ' . $limit)->fetchAll();
    }

    public static function assertProjectMediaIds(int $projectId, array $mediaIds, string $context = 'Medya'): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $mediaIds), fn($id) => $id > 0)));
        if (!$ids) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare("SELECT id,project_id FROM media WHERE id IN ($placeholders) AND deleted_at IS NULL");
        $stmt->execute($ids);

        $found = [];
        foreach ($stmt->fetchAll() as $row) {
            $found[(int)$row['id']] = (int)$row['project_id'];
        }

        foreach ($ids as $mediaId) {
            if (!array_key_exists($mediaId, $found)) {
                throw new RuntimeException($context . ' bulunamadi veya silinmis: #' . $mediaId);
            }
            if ($found[$mediaId] !== $projectId) {
                throw new RuntimeException($context . ' bu projeye ait degil: #' . $mediaId);
            }
        }

        return $ids;
    }

    public static function attachToUpdate(int $updateId, int $projectId, array $mediaIds): void
    {
        $mediaIds = self::assertProjectMediaIds($projectId, $mediaIds, 'Atolye medyasi');
        $max = (int)(db()->query('SELECT COALESCE(MAX(sort_order),-1) FROM update_media WHERE update_id=' . (int)$updateId)->fetchColumn());

        foreach (array_unique(array_map('intval', $mediaIds)) as $mediaId) {
            if ($mediaId <= 0) {
                continue;
            }
            $stmt = db()->prepare('INSERT OR IGNORE INTO update_media(update_id,media_id,role,sort_order) VALUES (?,?,' . db()->quote('gallery') . ',?)');
            $stmt->execute([$updateId, $mediaId, ++$max]);
        }
    }
}
