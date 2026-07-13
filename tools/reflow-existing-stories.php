<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$sessionPath = __DIR__ . '/../.codex-tmp';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0775, true);
}
session_save_path($sessionPath);

require __DIR__ . '/../app/bootstrap.php';

$apply = in_array('--apply', $argv, true);
$publicVisibleOnly = in_array('--public-visible', $argv, true);
$includeNoUpdates = in_array('--include-no-updates', $argv, true);
$onlySlug = '';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--story=')) {
        $onlySlug = trim(substr($arg, 8));
    }
}

function reflow_mode_for_project(array $project): string
{
    $category = strtolower((string)($project['category_slug'] ?? ''));
    $type = strtolower((string)($project['type_label'] ?? ''));
    $status = strtolower((string)($project['status'] ?? ''));

    if (str_contains($category, 'yz') || str_contains($category, 'yontem')) return 'discovery';
    if (str_contains($type, 'ders') || str_contains($category, 'ogrenme')) return 'reflection';
    if (in_array($status, ['yarim', 'fikir', 'not'], true)) return 'scene';
    return 'balanced';
}

function reflow_mode_config(string $mode): array
{
    return match ($mode) {
        'discovery' => [
            'opening_label' => 'MERAK',
            'quote' => 'Bu hikaye sonucu degil, sorunun nasil sekil degistirdigini de gosterir.',
            'middle_type' => 'questions',
            'middle_label' => 'KESIF NOTLARI',
            'middle_title' => 'Sorular degistikce proje de degisti.',
            'middle_intro' => 'Secilen kayitlar cevap listesi degil; ararken fark edilen donemecler.',
            'item_type' => 'question',
            'closing_label' => 'BAGLANTI',
            'closing_title' => 'Bugun bu kesif neye baglaniyor?',
        ],
        'reflection' => [
            'opening_label' => 'KIRILMA',
            'quote' => 'Bu hikaye yalnizca ne yaptigimi degil, calisma bicimimin nasil degistigini de kaydeder.',
            'middle_type' => 'lesson',
            'middle_label' => 'OGRENDIKLERIM',
            'middle_title' => 'Bu calisma bende neyi degistirdi?',
            'middle_intro' => 'Kayitlar karar sirasi gibi degil, geriye kalan dersler gibi okunur.',
            'item_type' => 'lesson',
            'closing_label' => 'SIMDI',
            'closing_title' => 'Bundan sonra neye dikkat edecegim?',
        ],
        'scene' => [
            'opening_label' => 'SAHNE',
            'quote' => 'Bu hikaye bitmis bir vitrin degil; masada biriken denemelerin izi.',
            'middle_type' => 'timeline',
            'middle_label' => 'SAHNELER',
            'middle_title' => 'Masadaki kayitlar boyle birikti.',
            'middle_intro' => 'Kayitlar kronoloji gibi degil, gorunen calisma sahneleri gibi dizilir.',
            'item_type' => 'timeline',
            'closing_label' => 'MASADA KALAN',
            'closing_title' => 'Bu sahneden geriye ne kaldi?',
        ],
        default => [
            'opening_label' => 'BASLANGIC',
            'quote' => 'Bu hikaye, Atolye kayitlarinin icinden secilen donum noktalarindan kuruldu.',
            'middle_type' => 'timeline',
            'middle_label' => 'DONUM NOKTALARI',
            'middle_title' => 'Atolyede yonu degistiren kararlar.',
            'middle_intro' => 'Ham kayitlarin tamami degil; secilmis kirilmalar.',
            'item_type' => 'timeline',
            'closing_label' => 'BUGUN',
            'closing_title' => 'Bugun nerede duruyor?',
        ],
    };
}

function reflow_update_text(array $update, string $mode): string
{
    $fields = [
        'summary' => trim((string)($update['summary'] ?? '')),
        'tried' => trim((string)($update['tried'] ?? '')),
        'failed' => trim((string)($update['failed'] ?? '')),
        'decision' => trim((string)($update['decision'] ?? '')),
        'next_step' => trim((string)($update['next_step'] ?? '')),
    ];

    $order = match ($mode) {
        'discovery' => ['tried', 'failed', 'decision', 'summary'],
        'reflection' => ['decision', 'next_step', 'summary'],
        'scene' => ['summary', 'tried', 'decision'],
        default => ['decision', 'summary', 'next_step'],
    };

    $parts = [];
    foreach ($order as $key) {
        if ($fields[$key] !== '') $parts[] = $fields[$key];
    }
    return implode("\n\n", array_values(array_unique($parts)));
}

function reflow_story_rows(string $onlySlug = '', bool $publicVisibleOnly = false): array
{
    $sql = "SELECT s.*, p.slug project_slug, p.title project_title, p.question project_question,
                   p.summary project_summary, p.status project_status, p.status_label,
                   p.type_label, p.cover_media_id, p.closing_note, p.closing_state,
                   c.slug category_slug, c.title category_title
            FROM stories s
            JOIN projects p ON p.id=s.project_id AND p.deleted_at IS NULL
            LEFT JOIN categories c ON c.id=p.category_id
            WHERE s.deleted_at IS NULL";
    $params = [];
    if ($onlySlug !== '') {
        $sql .= ' AND p.slug=?';
        $params[] = $onlySlug;
    }
    if ($publicVisibleOnly) {
        $sql .= " AND s.status='published'
                  AND s.visibility='public'
                  AND p.visibility='public'
                  AND p.show_in_archive=1
                  AND p.workshop_status NOT IN ('open','paused')";
    }
    $sql .= ' ORDER BY p.sort_order,p.id';

    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function reflow_updates_for_project(int $projectId): array
{
    $st = db()->prepare(
        "SELECT * FROM updates
         WHERE project_id=? AND deleted_at IS NULL AND status='published' AND visibility IN ('public','unlisted')
         ORDER BY COALESCE(work_date,created_at),sort_order,id"
    );
    $st->execute([$projectId]);
    $updates = $st->fetchAll();
    if ($updates) return $updates;

    $st = db()->prepare(
        "SELECT * FROM updates
         WHERE project_id=? AND deleted_at IS NULL
         ORDER BY COALESCE(work_date,created_at),sort_order,id"
    );
    $st->execute([$projectId]);
    return $st->fetchAll();
}

function reflow_selected_updates(array $updates): array
{
    $milestones = array_values(array_filter($updates, static fn(array $u): bool => (int)($u['is_milestone'] ?? 0) === 1));
    $selected = $milestones ?: $updates;
    return array_slice($selected, 0, 6);
}

function reflow_media_ids_for_updates(array $updates): array
{
    if (!$updates) return [];
    $ids = array_map(static fn(array $u): int => (int)$u['id'], $updates);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $st = db()->prepare(
        "SELECT DISTINCT um.media_id
         FROM update_media um
         JOIN media m ON m.id=um.media_id AND m.deleted_at IS NULL
         WHERE um.update_id IN ($placeholders)
         ORDER BY um.sort_order,um.media_id"
    );
    $st->execute($ids);
    return array_map('intval', array_column($st->fetchAll(), 'media_id'));
}

function reflow_existing_sections(int $storyId): array
{
    $st = db()->prepare(
        'SELECT * FROM story_sections
         WHERE story_id=? AND deleted_at IS NULL
         ORDER BY sort_order,id'
    );
    $st->execute([$storyId]);
    $sections = $st->fetchAll();

    $itemStmt = db()->prepare('SELECT * FROM story_section_items WHERE section_id=? ORDER BY sort_order,id');
    $mediaStmt = db()->prepare(
        'SELECT media_id FROM story_section_media WHERE section_id=? ORDER BY sort_order,id'
    );

    foreach ($sections as &$section) {
        $itemStmt->execute([(int)$section['id']]);
        $section['items'] = $itemStmt->fetchAll();
        $mediaStmt->execute([(int)$section['id']]);
        $section['media_ids'] = array_map('intval', array_column($mediaStmt->fetchAll(), 'media_id'));
        if ((int)($section['media_id'] ?? 0) > 0) {
            array_unshift($section['media_ids'], (int)$section['media_id']);
            $section['media_ids'] = array_values(array_unique($section['media_ids']));
        }
    }
    unset($section);

    return $sections;
}

function reflow_text_from_existing_section(array $section): string
{
    $parts = [];
    foreach (['body_text', 'intro_text', 'quote_text', 'note_text'] as $key) {
        $value = trim((string)($section[$key] ?? ''));
        if ($value !== '') $parts[] = $value;
    }

    if (!$parts && !empty($section['items'])) {
        foreach ($section['items'] as $item) {
            $title = trim((string)($item['title'] ?? ''));
            $text = trim((string)($item['text'] ?? ''));
            if ($title !== '' && $text !== '') {
                $parts[] = $title . ': ' . $text;
            } elseif ($title !== '') {
                $parts[] = $title;
            } elseif ($text !== '') {
                $parts[] = $text;
            }
        }
    }

    if (!$parts && trim((string)($section['code_text'] ?? '')) !== '') {
        $parts[] = 'Bu bölüm kod veya terminal çıktısı olarak duruyordu.';
    }

    if (!$parts && !empty($section['media_ids'])) {
        $parts[] = 'Bu bölüm görsel veya medya kanıtı olarak duruyordu.';
    }

    return implode("\n\n", array_values(array_unique($parts)));
}

function reflow_media_ids_for_sections(array $sections): array
{
    $ids = [];
    foreach ($sections as $section) {
        foreach (($section['media_ids'] ?? []) as $mediaId) {
            if ((int)$mediaId > 0) $ids[] = (int)$mediaId;
        }
    }
    return array_values(array_unique($ids));
}

function reflow_insert_section(int $storyId, array $data, int $sortOrder): int
{
    $st = db()->prepare(
        'INSERT INTO story_sections(story_id,type,layout,section_kind,label,title,body_text,quote_text,intro_text,note_text,media_id,sort_order)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    $st->execute([
        $storyId,
        $data['type'] ?? 'text',
        $data['layout'] ?? 'default',
        $data['section_kind'] ?? '',
        $data['label'] ?? '',
        $data['title'] ?? '',
        $data['body_text'] ?? '',
        $data['quote_text'] ?? '',
        $data['intro_text'] ?? '',
        $data['note_text'] ?? '',
        $data['media_id'] ?? null,
        $sortOrder,
    ]);
    return (int)db()->lastInsertId();
}

function reflow_apply_story(array $story, array $updates, string $mode, array $existingSections = []): array
{
    $config = reflow_mode_config($mode);
    $selected = reflow_selected_updates($updates);
    $hasUpdates = $selected !== [];
    $mediaIds = $hasUpdates ? reflow_media_ids_for_updates($selected) : reflow_media_ids_for_sections($existingSections);
    $storyId = (int)$story['id'];
    $now = date('Y-m-d H:i:s');

    db()->prepare('UPDATE story_sections SET deleted_at=?, updated_at=CURRENT_TIMESTAMP WHERE story_id=? AND deleted_at IS NULL')
        ->execute([$now, $storyId]);

    $sort = 0;
    reflow_insert_section($storyId, [
        'type' => 'opening',
        'layout' => (int)($story['cover_media_id'] ?? 0) ? 'hero-split' : 'wide',
        'section_kind' => 'Başlangıç',
        'label' => $config['opening_label'],
        'title' => trim((string)($story['question'] ?: $story['project_question'] ?: $story['project_title'])),
        'body_text' => trim((string)($story['summary'] ?: $story['project_summary'])),
        'quote_text' => $config['quote'],
        'media_id' => (int)($story['cover_media_id'] ?? 0) ?: null,
    ], ++$sort);

    if ($hasUpdates) {
        $sectionId = reflow_insert_section($storyId, [
            'type' => $config['middle_type'],
            'layout' => 'default',
            'section_kind' => $mode === 'reflection' ? 'Ders' : 'Dönüm noktası',
            'label' => $config['middle_label'],
            'title' => $config['middle_title'],
            'intro_text' => $config['middle_intro'],
        ], ++$sort);

        $itemStmt = db()->prepare(
            "INSERT INTO story_section_items(section_id,group_key,item_type,step,title,subtitle,text,source_update_id,sort_order)
             VALUES (?,'',?,?,?,?,?,?,?)"
        );
        foreach ($selected as $index => $update) {
            $itemStmt->execute([
                $sectionId,
                $config['item_type'],
                str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT),
                trim((string)($update['title'] ?? 'Atölye kaydı')),
                trim((string)($update['display_label'] ?: $update['phase'] ?? '')),
                reflow_update_text($update, $mode),
                (int)$update['id'],
                $index + 1,
            ]);
        }
    } elseif ($existingSections) {
        $sectionId = reflow_insert_section($storyId, [
            'type' => $config['middle_type'],
            'layout' => 'default',
            'section_kind' => $mode === 'reflection' ? 'Ders' : 'Dönüm noktası',
            'label' => $config['middle_label'],
            'title' => $config['middle_title'],
            'intro_text' => 'Bu hikâyenin eski bölümleri yeni akışta okunabilir adımlar olarak toparlandı.',
        ], ++$sort);

        $itemStmt = db()->prepare(
            "INSERT INTO story_section_items(section_id,group_key,item_type,step,title,subtitle,text,sort_order)
             VALUES (?,'',?,?,?,?,?,?)"
        );
        $index = 0;
        foreach ($existingSections as $oldSection) {
            $title = trim((string)($oldSection['title'] ?? ''));
            $text = reflow_text_from_existing_section($oldSection);
            if ($title === '' && $text === '') continue;
            $itemStmt->execute([
                $sectionId,
                $config['item_type'],
                str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT),
                $title !== '' ? $title : 'Eski bölüm',
                trim((string)($oldSection['label'] ?? '')),
                $text,
                $index + 1,
            ]);
            $index++;
            if ($index >= 6) break;
        }
    }

    if ($mediaIds) {
        $galleryId = reflow_insert_section($storyId, [
            'type' => 'gallery',
            'layout' => 'wide',
            'section_kind' => 'Kanıt',
            'label' => 'GÖRÜNENLER',
            'title' => 'İşin görünen yüzü.',
            'body_text' => 'Atölye kayıtlarına eklenen görseller ve medya parçaları burada hikâyeyi destekler.',
        ], ++$sort);

        $mediaStmt = db()->prepare("INSERT INTO story_section_media(section_id,media_id,role,sort_order) VALUES (?,?,'gallery',?)");
        foreach (array_slice($mediaIds, 0, 8) as $index => $mediaId) {
            $mediaStmt->execute([$galleryId, $mediaId, $index + 1]);
        }
    }

    $closingText = trim((string)($story['closing_note'] ?? ''));
    if ($closingText === '') {
        $status = trim((string)($story['status_label'] ?? ''));
        $closingText = $status !== ''
            ? 'Bu dosya bugün "' . $status . '" durumunda duruyor. Hikâye, seçilmiş kayıtların okunabilir hâli olarak korunuyor.'
            : 'Bu dosya bugün seçilmiş kayıtların okunabilir hâli olarak korunuyor.';
    }

    reflow_insert_section($storyId, [
        'type' => 'text',
        'layout' => 'wide',
        'section_kind' => 'Sonuç',
        'label' => $config['closing_label'],
        'title' => $config['closing_title'],
        'body_text' => $closingText,
    ], ++$sort);

    db()->prepare('UPDATE stories SET updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$storyId]);

    return [
        'story_id' => $storyId,
        'sections_created' => $sort,
        'updates_used' => count($selected),
        'media_used' => min(8, count($mediaIds)),
    ];
}

$stories = reflow_story_rows($onlySlug, $publicVisibleOnly);
if (!$stories) {
    echo "Hikaye bulunamadi.\n";
    exit(1);
}

echo ($apply ? "APPLY" : "DRY-RUN") . " story reflow\n";
echo "DB: " . FV7_DB . "\n";

$plan = [];
foreach ($stories as $story) {
    $updates = reflow_updates_for_project((int)$story['project_id']);
    $existingSections = reflow_existing_sections((int)$story['id']);
    $willSkip = !$includeNoUpdates && count($updates) === 0 && count($existingSections) === 0;
    $mode = reflow_mode_for_project([
        'category_slug' => $story['category_slug'],
        'type_label' => $story['type_label'],
        'status' => $story['project_status'],
    ]);
    $selected = reflow_selected_updates($updates);
    $plan[] = [
        'story' => $story,
        'updates' => $updates,
        'existing_sections' => $existingSections,
        'mode' => $mode,
        'skip' => $willSkip,
        'selected_count' => count($selected),
        'media_count' => count(reflow_media_ids_for_updates($selected)),
    ];
    $action = $willSkip ? 'skip:empty' : (count($updates) > 0 ? 'reflow:updates' : 'reflow:existing-sections');
    $mediaCount = count($updates) > 0
        ? $plan[array_key_last($plan)]['media_count']
        : count(reflow_media_ids_for_sections($existingSections));
    echo "- {$story['project_slug']} | action={$action} | mode={$mode} | updates=" . count($updates) . " | sections=" . count($existingSections) . " | selected=" . count($selected) . " | media={$mediaCount}\n";
}

if (!$apply) {
    echo "Uygulamak icin: php tools/reflow-existing-stories.php --apply --public-visible\n";
    exit(0);
}

$backupDir = rtrim(FV7_STORAGE, '/\\') . DIRECTORY_SEPARATOR . 'backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0775, true);
}
$backupPath = $backupDir . DIRECTORY_SEPARATOR . 'fikrimvar-story-reflow-' . date('Ymd-His') . '.sqlite';
if (!copy(FV7_DB, $backupPath)) {
    throw new RuntimeException('DB yedegi olusturulamadi: ' . $backupPath);
}

db()->beginTransaction();
try {
    foreach ($plan as $entry) {
        if ($entry['skip']) continue;
        reflow_apply_story($entry['story'], $entry['updates'], $entry['mode'], $entry['existing_sections']);
    }
    db()->commit();
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    throw $e;
}

echo "Yedek: {$backupPath}\n";
$appliedCount = count(array_filter($plan, static fn(array $entry): bool => !$entry['skip']));
echo "Tamamlandi. Reflow edilen hikaye sayisi: " . $appliedCount . "\n";
