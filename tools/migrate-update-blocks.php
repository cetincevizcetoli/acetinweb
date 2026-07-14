<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("This migration can only run from CLI.\n");
}

require __DIR__ . '/../app/bootstrap.php';

$version = 2026071401;
$exists = db()->prepare('SELECT 1 FROM schema_migrations WHERE version=?');
$exists->execute([$version]);
if ($exists->fetchColumn()) {
    echo "Migration {$version} already applied\n";
    exit(0);
}

db()->beginTransaction();
try {
    db()->exec(
        "CREATE TABLE IF NOT EXISTS update_blocks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            update_id INTEGER NOT NULL,
            block_type TEXT NOT NULL,
            title TEXT NOT NULL DEFAULT '',
            body TEXT NOT NULL DEFAULT '',
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(update_id) REFERENCES updates(id) ON DELETE CASCADE
        )"
    );
    db()->exec('CREATE INDEX IF NOT EXISTS idx_update_blocks_update ON update_blocks(update_id, sort_order, id)');
    db()->prepare('INSERT OR IGNORE INTO schema_migrations(version) VALUES (?)')->execute([$version]);
    db()->commit();
    echo "Migration {$version} recorded\n";
} catch (Throwable $e) {
    db()->rollBack();
    throw $e;
}
