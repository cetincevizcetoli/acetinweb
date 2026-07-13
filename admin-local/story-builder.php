<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admin_require_login();

$projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
$st = db()->prepare('SELECT * FROM projects WHERE id=? AND deleted_at IS NULL');
$st->execute([$projectId]);
$project = $st->fetch();
if (!$project) {
    http_response_code(404);
    exit('Proje yok.');
}

$updates = project_updates($projectId, false);
$story = story_by_project($projectId, true);
$error = '';

function story_builder_has_column(string $table, string $column): bool
{
    $st = db()->query('PRAGMA table_info(' . $table . ')');
    foreach ($st->fetchAll() as $row) {
        if (($row['name'] ?? '') === $column) return true;
    }
    return false;
}

function story_builder_mode_config(string $mode): array
{
    $configs = [
        'balanced' => [
            'label' => 'Dengeli hikâye',
            'help' => 'Başlangıç, seçilmiş dönüm noktaları ve bugünkü durum.',
            'opening_label' => 'BAŞLANGIÇ',
            'opening_layout' => 'hero-split',
            'middle_type' => 'timeline',
            'middle_label' => 'DÖNÜM NOKTALARI',
            'middle_title' => 'Atölyede yönü değiştiren kararlar.',
            'middle_intro' => 'Ham kayıtların tamamı değil; seçilmiş kırılmalar.',
            'ending_label' => 'BUGÜN NEREDE?',
            'ending_title' => 'Bu proje benim için bugün ne ifade ediyor?',
        ],
        'discovery' => [
            'label' => 'Keşif akışı',
            'help' => 'Merak, deneme, fark etme ve bağlama üzerinden ilerler.',
            'opening_label' => 'MERAK',
            'opening_layout' => 'wide',
            'middle_type' => 'questions',
            'middle_label' => 'KEŞİF NOTLARI',
            'middle_title' => 'Sorular değiştikçe proje de değişti.',
            'middle_intro' => 'Bu taslak sonucu değil, ararken fark edilenleri öne alır.',
            'ending_label' => 'BAĞLANTI',
            'ending_title' => 'Bugün bu keşif neye bağlanıyor?',
        ],
        'scene' => [
            'label' => 'Sahne akışı',
            'help' => 'Okuru çalışma anına yaklaştıran, daha atmosferli bir iskelet.',
            'opening_label' => 'SAHNE',
            'opening_layout' => 'wide',
            'middle_type' => 'timeline',
            'middle_label' => 'SAHNELER',
            'middle_title' => 'Bu iş masada böyle ilerledi.',
            'middle_intro' => 'Kayıtlar kronoloji gibi değil, görünen çalışma sahneleri gibi dizilir.',
            'ending_label' => 'MASADA KALAN',
            'ending_title' => 'Bu sahneden geriye ne kaldi?',
        ],
        'reflection' => [
            'label' => 'Yansıma akışı',
            'help' => 'Duygu, düşünce, değişim ve ders çıkarma merkezli taslak.',
            'opening_label' => 'KIRILMA',
            'opening_layout' => 'wide',
            'middle_type' => 'lesson',
            'middle_label' => 'ÖĞRENDİKLERİM',
            'middle_title' => 'Bu çalışma bende neyi değiştirdi?',
            'middle_intro' => 'Seçilen kayıtlar karar listesi gibi değil, dönüşen düşünceler gibi okunur.',
            'ending_label' => 'ŞİMDİ',
            'ending_title' => 'Bundan sonra neye dikkat edeceğim?',
        ],
    ];

    return $configs[$mode] ?? $configs['balanced'];
}

function story_builder_update_text(array $update, string $mode): string
{
    $entryKind = atelier_entry_kind($update);
    $tried = trim((string)($update['tried'] ?? ''));
    $failed = trim((string)($update['failed'] ?? ''));
    $decision = trim((string)($update['decision'] ?? ''));
    $next = trim((string)($update['next_step'] ?? ''));
    $summary = trim((string)($update['summary'] ?? ''));

    $parts = [];
    if ($entryKind === 'problem') {
        foreach ([$summary, $failed, $decision, $next] as $part) {
            if ($part !== '') $parts[] = $part;
        }
    } elseif ($entryKind === 'decision') {
        foreach ([$decision, $summary, $next] as $part) {
            if ($part !== '') $parts[] = $part;
        }
    } elseif ($entryKind === 'media') {
        foreach ([$summary, $tried, $decision] as $part) {
            if ($part !== '') $parts[] = $part;
        }
    } elseif ($entryKind === 'source') {
        foreach ([$summary, $tried, $next] as $part) {
            if ($part !== '') $parts[] = $part;
        }
    } elseif ($mode === 'discovery') {
        foreach ([$tried, $failed, $decision ?: $summary] as $part) {
            if ($part !== '') $parts[] = $part;
        }
    } elseif ($mode === 'scene') {
        foreach ([$summary, $tried, $decision] as $part) {
            if ($part !== '') $parts[] = $part;
        }
    } elseif ($mode === 'reflection') {
        foreach ([$decision, $next, $summary] as $part) {
            if ($part !== '') $parts[] = $part;
        }
    } else {
        foreach ([$decision, $summary, $next] as $part) {
            if ($part !== '') $parts[] = $part;
        }
    }

    return implode("\n\n", array_values(array_unique($parts)));
}

function story_builder_update_title(array $update, string $mode): string
{
    $title = trim((string)($update['title'] ?? ''));
    if ($title !== '') return $title;
    $entryKind = atelier_entry_kind($update);
    if ($entryKind === 'problem') return 'Nerede takıldı?';
    if ($entryKind === 'decision') return 'Yön değiştiren karar';
    if ($entryKind === 'media') return 'Görünen kanıt';
    if ($entryKind === 'source') return 'Bağlantı notu';
    if ($mode === 'discovery') return 'Ne fark ettim?';
    if ($mode === 'reflection') return 'Bende kalan';
    return 'Atölye kaydı';
}

function story_builder_insert_section(
    int $storyId,
    string $type,
    string $layout,
    string $label,
    string $title,
    string $bodyText,
    string $introText,
    string $quoteText,
    int $sortOrder,
    int $sourceUpdateId = 0,
    string $sectionKind = '',
    string $noteText = '',
    string $codeText = '',
    int $mediaId = 0
): int {
    $st = db()->prepare(
        'INSERT INTO story_sections(story_id,source_update_id,section_kind,type,layout,label,title,body_text,intro_text,quote_text,note_text,code_text,media_id,sort_order)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    $st->execute([
        $storyId,
        $sourceUpdateId > 0 ? $sourceUpdateId : null,
        $sectionKind,
        $type,
        $layout,
        $label,
        $title,
        $bodyText,
        $introText,
        $quoteText,
        $noteText,
        $codeText,
        $mediaId > 0 ? $mediaId : null,
        $sortOrder,
    ]);
    return (int)db()->lastInsertId();
}

function story_builder_primary_media_id_for_update(int $updateId): int
{
    $media = update_media($updateId);
    return $media ? (int)$media[0]['media_id'] : 0;
}

function story_builder_insert_item(int $sectionId, array $update, int $index, string $mode, bool $hasItemSource): void
{
    $kindConfig = atelier_entry_kind_config($update);
    $itemType = (string)$kindConfig['item_type'];
    if ($itemType === 'timeline' && $mode === 'reflection') {
        $itemType = 'lesson';
    } elseif ($itemType === 'timeline' && $mode === 'discovery') {
        $itemType = 'question';
    }
    $step = str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT);
    $title = story_builder_update_title($update, $mode);
    $subtitleParts = array_filter([
        trim((string)($update['display_label'] ?: $update['phase'] ?? '')),
        (string)$kindConfig['short'],
    ]);
    $subtitle = implode(' · ', array_values(array_unique($subtitleParts)));
    $text = story_builder_update_text($update, $mode);

    if ($hasItemSource) {
        $st = db()->prepare(
            "INSERT INTO story_section_items(section_id,group_key,item_type,step,title,subtitle,text,source_update_id,sort_order)
             VALUES (?,'',?,?,?,?,?,?,?)"
        );
        $st->execute([$sectionId, $itemType, $step, $title, $subtitle, $text, (int)$update['id'], $index + 1]);
        return;
    }

    $st = db()->prepare(
        "INSERT INTO story_section_items(section_id,group_key,item_type,step,title,subtitle,text,sort_order)
         VALUES (?,'',?,?,?,?,?,?)"
    );
    $st->execute([$sectionId, $itemType, $step, $title, $subtitle, $text, $index + 1]);
}

function story_builder_section_item_type(string $sectionType, array $update, string $mode): string
{
    if ($sectionType === 'questions') return 'question';
    if ($sectionType === 'lesson') return 'lesson';
    if ($sectionType === 'status') return 'status';
    if ($sectionType === 'compare') return 'compare';
    if ($sectionType === 'timeline') return 'timeline';
    return (string)atelier_entry_kind_config($update)['item_type'];
}

function story_builder_insert_update_section(int $storyId, array $update, int $sortOrder, string $mode, bool $hasItemSource): int
{
    $type = atelier_default_story_section_type($update);
    $layout = atelier_default_story_layout($update);
    $label = atelier_story_label($update);
    $title = story_builder_update_title($update, $mode);
    $summary = trim((string)($update['summary'] ?? ''));
    $body = story_builder_update_text($update, $mode);
    $decision = trim((string)($update['decision'] ?? ''));
    $next = trim((string)($update['next_step'] ?? ''));
    $mediaId = story_builder_primary_media_id_for_update((int)$update['id']);

    $itemDriven = ['timeline', 'questions', 'compare', 'status', 'lesson'];
    if (in_array($type, $itemDriven, true)) {
        $sectionId = story_builder_insert_section(
            $storyId,
            $type,
            'default',
            $label,
            $title,
            '',
            $summary,
            $decision,
            $sortOrder,
            (int)$update['id'],
            $label,
            $next,
            '',
            $mediaId
        );

        $kindConfig = atelier_entry_kind_config($update);
        $subtitle = implode(' · ', array_values(array_unique(array_filter([
            trim((string)($update['display_label'] ?: $update['phase'] ?? '')),
            (string)$kindConfig['short'],
        ]))));
        $itemType = story_builder_section_item_type($type, $update, $mode);
        $step = str_pad((string)$sortOrder, 2, '0', STR_PAD_LEFT);
        if ($hasItemSource) {
            $st = db()->prepare(
                "INSERT INTO story_section_items(section_id,group_key,item_type,step,title,subtitle,text,source_update_id,sort_order)
                 VALUES (?,'',?,?,?,?,?,?,1)"
            );
            $st->execute([$sectionId, $itemType, $step, $title, $subtitle, $body, (int)$update['id']]);
        } else {
            $st = db()->prepare(
                "INSERT INTO story_section_items(section_id,group_key,item_type,step,title,subtitle,text,sort_order)
                 VALUES (?,'',?,?,?,?,?,1)"
            );
            $st->execute([$sectionId, $itemType, $step, $title, $subtitle, $body]);
        }
        return $sectionId;
    }

    return story_builder_insert_section(
        $storyId,
        $type,
        $layout,
        $label,
        $title,
        $body,
        $summary,
        $decision,
        $sortOrder,
        (int)$update['id'],
        $label,
        $next,
        '',
        $mediaId
    );
}

if (is_post()) {
    verify_csrf();

    try {
        $mode = (string)($_POST['draft_mode'] ?? 'balanced');
        $config = story_builder_mode_config($mode);
        $selected = array_values(array_unique(array_map('intval', $_POST['update_ids'] ?? [])));
        if (!$selected) throw new RuntimeException('En az bir kayıt seçmelisin.');

        db()->beginTransaction();

        if (!$story) {
            $st = db()->prepare(
                "INSERT INTO stories(project_id,title,question,summary,status,visibility,show_on_home,show_in_archive,sort_order)
                 VALUES (?,?,?,?, 'draft', ?,?,?,?)"
            );
            $st->execute([
                $projectId,
                $project['title'],
                $project['question'],
                $project['summary'],
                $project['visibility'],
                $project['show_on_home'],
                $project['show_in_archive'],
                $project['sort_order'],
            ]);
            $storyId = (int)db()->lastInsertId();
        } else {
            $storyId = (int)$story['id'];
            if (checkbox('replace_sections')) {
                db()->prepare('DELETE FROM story_sections WHERE story_id=?')->execute([$storyId]);
            }
        }

        $max = (int)db()->query('SELECT COALESCE(MAX(sort_order),0) FROM story_sections WHERE story_id=' . $storyId)->fetchColumn();
        if ($max === 0 || checkbox('replace_sections')) {
            story_builder_insert_section(
                $storyId,
                'opening',
                $config['opening_layout'],
                $config['opening_label'],
                trim((string)($project['question'] ?: $project['title'])),
                trim((string)($project['summary'] ?? '')),
                '',
                'Bu taslak, Atölye kayıtlarından seçilerek oluşturuldu.',
                ++$max
            );
        }

        $q = db()->prepare('SELECT * FROM updates WHERE id=? AND project_id=?');
        $hasItemSource = story_builder_has_column('story_section_items', 'source_update_id');
        foreach ($selected as $i => $uid) {
            $q->execute([$uid, $projectId]);
            $update = $q->fetch();
            if (!$update) continue;
            story_builder_insert_update_section($storyId, $update, ++$max, $mode, $hasItemSource);
        }

        story_builder_insert_section(
            $storyId,
            'text',
            'wide',
            $config['ending_label'],
            $config['ending_title'],
            trim((string)($_POST['closing_note'] ?? $project['closing_note'])) ?: 'Bu bölüm hikâye düzenleyicisinden tamamlanacak.',
            '',
            '',
            ++$max
        );

        if (checkbox('close_workshop')) {
            db()->prepare(
                "UPDATE projects
                 SET workshop_status='closed',closing_state=?,closing_note=?,ended_at=?,updated_at=CURRENT_TIMESTAMP
                 WHERE id=?"
            )->execute([
                (string)($_POST['closing_state'] ?? 'Bu hâliyle bitti'),
                trim((string)($_POST['closing_note'] ?? '')),
                date('Y-m-d'),
                $projectId,
            ]);
        }

        db()->commit();
        admin_audit('build_story', 'story', $storyId, 'Mode: ' . $mode . ' / Selected updates: ' . implode(',', $selected));
        flash('success', 'Hikâye taslağı oluşturuldu. Mod: ' . $config['label']);
        redirect('story-edit.php?project_id=' . $projectId);
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        $error = admin_error_message($e, 'admin.story_builder');
    }
}

$draftModes = ['balanced', 'discovery', 'scene', 'reflection'];

admin_head('Atölyeden Hikâye');
?>
<div class="page-head">
    <div>
        <p class="eyebrow"><?= e($project['title']) ?></p>
        <h1>Atölyeden Hikâye oluştur</h1>
        <p>Ham kayıtlar silinmez. Seçilen kayıtlar, seçtiğin anlatım akışıyla hikâye taslağına dönüşür.</p>
    </div>
</div>

<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

<form method="post">
    <input type="hidden" name="project_id" value="<?= $projectId ?>">
    <?= csrf_field() ?>

    <div class="grid grid-2">
        <section class="panel">
            <h2>Anlatım akışı</h2>
            <p class="help">Bu seçim veriyi silmez; yalnızca seçilen Atölye kayıtlarından kurulacak ilk hikâye iskeletini değiştirir. Sonra bölümleri tek tek düzenleyebilirsin.</p>
            <div class="draft-mode-grid">
                <?php foreach ($draftModes as $mode): $config = story_builder_mode_config($mode); ?>
                    <label class="draft-mode-card">
                        <input type="radio" name="draft_mode" value="<?= e($mode) ?>" <?= $mode === 'balanced' ? 'checked' : '' ?>>
                        <strong><?= e($config['label']) ?></strong>
                        <span><?= e($config['help']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="panel">
            <h2>Kapanis ve taslak</h2>
            <?php if ($story): ?>
                <p class="help">Bu projede zaten bir hikâye var. Mevcut bölümleri koruyabilir veya yeni taslakla değiştirebilirsin.</p>
                <label class="check"><input type="checkbox" name="replace_sections"> Mevcut hikâye bölümlerini silip yeniden oluştur</label>
            <?php endif; ?>
            <label class="check"><input type="checkbox" name="close_workshop" <?= $project['workshop_status'] === 'closed' ? 'checked' : '' ?>> Atölyeyi kapat</label>
            <div class="field">
                <label>Kapanış kararı</label>
                <select name="closing_state">
                    <?php foreach (['Bu hâliyle bitti', 'Yarım bıraktım', 'Beklemeye aldım', 'Başka projeye dönüştü'] as $value): ?>
                        <option><?= e($value) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Kapanış notu</label>
                <textarea name="closing_note"><?= e($project['closing_note']) ?></textarea>
            </div>
        </aside>

        <section class="panel">
            <h2>Kayıtları seç</h2>
            <div class="list">
                <?php foreach ($updates as $update): $kind = atelier_entry_kind_config($update); ?>
                    <label class="list-row">
                        <input type="checkbox" name="update_ids[]" value="<?= (int)$update['id'] ?>" <?= $update['is_milestone'] ? 'checked' : '' ?>>
                        <span>
                            <strong><?= e($update['title']) ?></strong>
                            <small><?= e($update['date_label']) ?> · <?= e($update['phase']) ?> · Hikâyede: <?= e(atelier_story_label($update)) ?> / <?= e(atelier_story_section_type_options()[atelier_default_story_section_type($update)]['label'] ?? atelier_default_story_section_type($update)) ?></small>
                        </span>
                        <span class="chip <?= $update['is_milestone'] ? 'ok' : '' ?>"><?= $update['is_milestone'] ? 'Dönüm noktası' : 'Ham kayıt' ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="panel">
            <h2>Ne değişir?</h2>
            <p>Hikâye başlığı ve proje bilgileri aynı kalır. Seçilen kayıtlar farklı anlatım kalıplarıyla bölüm satırlarına dönüşür.</p>
            <ul class="plain-list">
                <li><strong>Dengeli:</strong> kararlar ve sonuç duygusu.</li>
                <li><strong>Keşif:</strong> soru, deneme ve fark etme.</li>
                <li><strong>Sahne:</strong> çalışma anları ve atmosfer.</li>
                <li><strong>Yansıma:</strong> öğrenilenler ve değişen düşünce.</li>
            </ul>
        </aside>
    </div>

    <div class="form-actions">
        <button class="accent" type="submit">Taslağı oluştur</button>
        <a class="button secondary" href="project-edit.php?id=<?= $projectId ?>">Vazgeç</a>
    </div>
</form>
<?php admin_foot(); ?>
