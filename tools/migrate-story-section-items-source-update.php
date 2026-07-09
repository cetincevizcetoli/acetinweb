<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This migration can only run from CLI.\n");
}

require __DIR__ . '/../_bootstrap.php';

$version = 2026070901;

$hasColumn = false;
$st = db()->query('PRAGMA table_info(story_section_items)');
foreach ($st->fetchAll() as $row) {
    if (($row['name'] ?? '') === 'source_update_id') {
        $hasColumn = true;
        break;
    }
}

if (!$hasColumn) {
    db()->exec('ALTER TABLE story_section_items ADD COLUMN source_update_id INTEGER');
    echo "Added story_section_items.source_update_id\n";
} else {
    echo "story_section_items.source_update_id already exists\n";
}

db()->prepare('INSERT OR IGNORE INTO schema_migrations(version) VALUES (?)')->execute([$version]);
echo "Migration {$version} recorded\n";
