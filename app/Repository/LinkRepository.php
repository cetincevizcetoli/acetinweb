<?php
declare(strict_types=1);

final class LinkRepository
{
    private const OWNER_TYPES = ['project', 'update', 'story_section'];

    public static function findByOwner(string $ownerType, int $ownerId): array
    {
        self::assertOwnerType($ownerType);
        $st = db()->prepare('SELECT * FROM links WHERE owner_type=? AND owner_id=? ORDER BY sort_order,id');
        $st->execute([$ownerType, $ownerId]);
        return $st->fetchAll();
    }

    public static function replaceForOwner(string $ownerType, int $ownerId, array $links, ?callable $titleResolver = null): int
    {
        self::assertOwnerType($ownerType);
        db()->prepare('DELETE FROM links WHERE owner_type=? AND owner_id=?')->execute([$ownerType, $ownerId]);

        $saved = 0;
        foreach ($links as $i => $link) {
            if (!is_array($link)) continue;
            $rawUrl = trim((string)($link['url'] ?? ''));
            if ($rawUrl === '') continue;

            $url = validated_external_url($rawUrl, 'Baglanti ' . ($i + 1));
            $type = self::normalizeType((string)($link['type'] ?? 'external'));
            $title = trim((string)($link['title'] ?? ''));
            if ($title === '' && $titleResolver) {
                $title = trim((string)$titleResolver($title, $type, $url, $i, $link));
            }
            if ($title === '') $title = 'Baglanti';

            $st = db()->prepare('INSERT INTO links(owner_type,owner_id,link_type,title,url,sort_order) VALUES (?,?,?,?,?,?)');
            $st->execute([$ownerType, $ownerId, $type, $title, $url, (int)$i]);
            $saved++;
        }

        return $saved;
    }

    private static function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        return $type !== '' ? $type : 'external';
    }

    private static function assertOwnerType(string $ownerType): void
    {
        if (!in_array($ownerType, self::OWNER_TYPES, true)) {
            throw new InvalidArgumentException('Gecersiz baglanti sahibi: ' . $ownerType);
        }
    }
}
