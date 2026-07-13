<?php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

function migration_column_exists(string $table, string $column): bool
{
    $st = db()->query('PRAGMA table_info(' . $table . ')');
    foreach ($st->fetchAll() as $row) {
        if (($row['name'] ?? '') === $column) {
            return true;
        }
    }
    return false;
}

function migration_backup_db(string $label): string
{
    $backupDir = FV7_STORAGE . '/backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0775, true);
    }
    $path = $backupDir . '/fikrimvar-' . $label . '-' . date('Ymd-His') . '.sqlite';
    if (!copy(FV7_DB, $path)) {
        throw new RuntimeException('Veritabanı yedeği oluşturulamadı: ' . $path);
    }
    return $path;
}

function migration_guess_entry_kind(array $row): string
{
    if ((int)($row['is_milestone'] ?? 0) === 1) {
        return 'decision';
    }
    if (trim((string)($row['failed'] ?? '')) !== '') {
        return 'problem';
    }
    if (trim((string)($row['tried'] ?? '')) !== '') {
        return 'experiment';
    }
    return 'journal';
}

$backup = migration_backup_db('updates-entry-kind');

db()->beginTransaction();
try {
    if (!migration_column_exists('updates', 'entry_kind')) {
        db()->exec("ALTER TABLE updates ADD COLUMN entry_kind TEXT NOT NULL DEFAULT 'journal' CHECK(entry_kind IN ('journal','experiment','problem','decision','media','source'))");
    }

    $rows = db()->query("SELECT id,is_milestone,tried,failed FROM updates WHERE entry_kind IS NULL OR entry_kind='' OR entry_kind='journal'")->fetchAll();
    $st = db()->prepare('UPDATE updates SET entry_kind=?, updated_at=CURRENT_TIMESTAMP WHERE id=?');
    foreach ($rows as $row) {
        $st->execute([migration_guess_entry_kind($row), (int)$row['id']]);
    }

    db()->commit();
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    throw $e;
}

echo 'Migration completed.' . PHP_EOL;
echo 'Backup: ' . $backup . PHP_EOL;
