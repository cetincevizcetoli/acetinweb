<?php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

function migration_column_exists(string $table, string $column): bool
{
    $st = db()->query('PRAGMA table_info(' . $table . ')');
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (($row['name'] ?? '') === $column) return true;
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
        throw new RuntimeException('Veritabani yedegi olusturulamadi: ' . $path);
    }
    return $path;
}

function migration_story_shape_for(array $row): array
{
    $kind = atelier_entry_kind($row);
    $role = match ($kind) {
        'problem' => 'problem',
        'experiment' => 'experiment',
        'decision' => 'decision',
        'media' => 'media',
        'source' => 'source',
        default => (int)($row['is_milestone'] ?? 0) === 1 ? 'decision' : 'experiment',
    };

    $type = match ($role) {
        'problem', 'source' => 'questions',
        'decision' => 'lesson',
        'media' => 'split',
        default => 'timeline',
    };

    $layout = in_array($type, ['timeline', 'questions', 'lesson', 'status'], true) ? 'default' : 'wide';

    return [$role, $type, $layout, ''];
}

$backup = migration_backup_db('updates-story-shape');

db()->beginTransaction();
try {
    if (!migration_column_exists('updates', 'story_role')) {
        db()->exec("ALTER TABLE updates ADD COLUMN story_role TEXT NOT NULL DEFAULT 'auto'");
    }
    if (!migration_column_exists('updates', 'story_section_type')) {
        db()->exec("ALTER TABLE updates ADD COLUMN story_section_type TEXT NOT NULL DEFAULT 'auto'");
    }
    if (!migration_column_exists('updates', 'story_layout')) {
        db()->exec("ALTER TABLE updates ADD COLUMN story_layout TEXT NOT NULL DEFAULT 'auto'");
    }
    if (!migration_column_exists('updates', 'story_label')) {
        db()->exec("ALTER TABLE updates ADD COLUMN story_label TEXT NOT NULL DEFAULT ''");
    }

    $rows = db()->query(
        "SELECT * FROM updates
         WHERE deleted_at IS NULL
           AND (story_role='auto' OR story_section_type='auto' OR story_layout='auto')"
    )->fetchAll(PDO::FETCH_ASSOC);
    $st = db()->prepare(
        "UPDATE updates
         SET story_role=?, story_section_type=?, story_layout=?, story_label=CASE WHEN story_label='' THEN ? ELSE story_label END,
             updated_at=CURRENT_TIMESTAMP
         WHERE id=?"
    );
    foreach ($rows as $row) {
        [$role, $type, $layout, $label] = migration_story_shape_for($row);
        $st->execute([$role, $type, $layout, $label, (int)$row['id']]);
    }

    db()->commit();
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    throw $e;
}

echo 'Migration completed.' . PHP_EOL;
echo 'Backup: ' . $backup . PHP_EOL;
