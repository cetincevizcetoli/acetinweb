<?php
declare(strict_types=1);

final class UpdateBlockRepository
{
    private static ?bool $tableExists = null;

    public static function typeOptions(): array
    {
        return [
            'field_note' => 'Saha / çalışma notu',
            'prompt' => 'Prompt / girdi',
            'code' => 'Kod / komut',
            'output' => 'Çıktı / cevap',
            'error' => 'Hata / sürtüşme',
            'observation' => 'Gözlem',
            'decision' => 'Karar',
            'evidence' => 'Kanıt',
            'next' => 'Sonraki iş',
            'story_note' => 'Hikâye notu',
            'source' => 'Kaynak / bağlantı',
        ];
    }

    public static function typeLabel(string $type): string
    {
        $options = self::typeOptions();
        return $options[$type] ?? 'Not';
    }

    public static function validType(string $type): string
    {
        return array_key_exists($type, self::typeOptions()) ? $type : 'field_note';
    }

    public static function normalizeRows(mixed $rows): array
    {
        if (!is_array($rows)) return [];

        $out = [];
        foreach ($rows as $i => $row) {
            if (!is_array($row)) continue;

            $type = self::validType((string)($row['block_type'] ?? 'field_note'));
            $title = trim((string)($row['title'] ?? ''));
            $body = trim((string)($row['body'] ?? ''));
            if ($title === '' && $body === '') continue;

            $out[] = [
                'block_type' => $type,
                'title' => $title,
                'body' => $body,
                'sort_order' => (int)($row['sort_order'] ?? ($i + 1)),
            ];
        }

        usort($out, static fn(array $a, array $b): int => [$a['sort_order'], $a['title']] <=> [$b['sort_order'], $b['title']]);
        return array_values($out);
    }

    public static function forUpdate(int $updateId): array
    {
        if (!self::tableExists()) return [];

        $st = db()->prepare('SELECT * FROM update_blocks WHERE update_id=? ORDER BY sort_order,id');
        $st->execute([$updateId]);
        return $st->fetchAll();
    }

    public static function saveForUpdate(int $updateId, array $blocks): void
    {
        if (!self::tableExists()) {
            throw new RuntimeException('update_blocks tablosu bulunamadı. Önce tools/migrate-update-blocks.php çalıştırılmalı.');
        }

        db()->prepare('DELETE FROM update_blocks WHERE update_id=?')->execute([$updateId]);

        $st = db()->prepare(
            'INSERT INTO update_blocks(update_id,block_type,title,body,sort_order) VALUES (?,?,?,?,?)'
        );
        foreach (array_values($blocks) as $i => $block) {
            $st->execute([
                $updateId,
                self::validType((string)$block['block_type']),
                trim((string)$block['title']),
                trim((string)$block['body']),
                (int)($block['sort_order'] ?? ($i + 1)),
            ]);
        }
    }

    private static function tableExists(): bool
    {
        if (self::$tableExists !== null) return self::$tableExists;

        $st = db()->prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name='update_blocks'");
        $st->execute();
        self::$tableExists = (bool)$st->fetchColumn();
        return self::$tableExists;
    }
}
