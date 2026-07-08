<?php
declare(strict_types=1);

final class SettingsRepository
{
    public static function get(string $key, mixed $default = []): mixed
    {
        $stmt = db()->prepare('SELECT value FROM settings WHERE key=?');
        $stmt->execute([$key]);

        $raw = $stmt->fetchColumn();
        if ($raw === false) {
            return $default;
        }

        $decoded = json_decode((string)$raw, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
    }

    public static function save(string $key, mixed $value): void
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $stmt = db()->prepare('INSERT INTO settings(key,value,updated_at) VALUES (?,?,CURRENT_TIMESTAMP) ON CONFLICT(key) DO UPDATE SET value=excluded.value, updated_at=CURRENT_TIMESTAMP');
        $stmt->execute([$key, $json]);
    }
}
