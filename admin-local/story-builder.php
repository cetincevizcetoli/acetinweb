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

function story_builder_scope_options(): array
{
    return [
        'short' => [
            'label' => 'Kısa hikâye',
            'help' => '3-4 bölüm. Uzun Atölyeden yalnızca ana kırılmaları alır.',
            'narrative_limit' => 3,
            'evidence_limit' => 1,
            'status_limit' => 1,
            'closing_limit' => 1,
        ],
        'standard' => [
            'label' => 'Orta hikâye',
            'help' => '4-5 bölüm. Çoğu proje için güvenli varsayılan.',
            'narrative_limit' => 5,
            'evidence_limit' => 2,
            'status_limit' => 1,
            'closing_limit' => 1,
        ],
        'detailed' => [
            'label' => 'Detaylı hikâye',
            'help' => '5-7 bölüm. Daha çok kanıt ve dönüm noktası taşır.',
            'narrative_limit' => 8,
            'evidence_limit' => 4,
            'status_limit' => 2,
            'closing_limit' => 1,
        ],
    ];
}

function story_builder_scope_config(string $scope): array
{
    $options = story_builder_scope_options();
    return $options[$scope] ?? $options['standard'];
}

function story_builder_update_text(array $update, string $mode, string $purpose = 'narrative'): string
{
    $blockText = story_builder_update_block_text($update, $purpose);
    if ($blockText !== '') {
        return $blockText;
    }

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

function story_builder_update_block_text(array $update, string $purpose = 'narrative'): string
{
    $blocks = atelier_update_blocks($update);
    if ($blocks === []) return '';

    $parts = [];
    $fallback = [];
    foreach ($blocks as $block) {
        $body = trim((string)($block['body'] ?? ''));
        if ($body === '') continue;

        $title = trim((string)($block['title'] ?? ''));
        $type = UpdateBlockRepository::validType((string)($block['block_type'] ?? 'field_note'));
        $label = UpdateBlockRepository::typeLabel($type);
        $head = $title !== '' ? $title : $label;
        $summary = story_builder_block_summary($type, $head, $body, $purpose);
        if (story_builder_block_allowed_for_purpose($type, $purpose)) {
            $parts[] = $summary;
        } else {
            $fallback[] = $summary;
        }
    }

    if ($parts === []) {
        $parts = array_slice($fallback, 0, 2);
    }

    return implode("\n\n", $parts);
}

function story_builder_block_allowed_for_purpose(string $type, string $purpose): bool
{
    $type = UpdateBlockRepository::validType($type);

    return match ($purpose) {
        'opening' => in_array($type, ['field_note', 'observation', 'source', 'decision', 'story_note'], true),
        'evidence' => in_array($type, ['prompt', 'code', 'output', 'error', 'evidence', 'source', 'observation'], true),
        'status' => in_array($type, ['next', 'decision', 'observation', 'error', 'field_note', 'story_note'], true),
        'closing' => in_array($type, ['decision', 'next', 'story_note', 'observation'], true),
        default => in_array($type, ['field_note', 'observation', 'error', 'decision', 'evidence', 'next', 'story_note'], true),
    };
}

function story_builder_trim_text(string $text, int $limit = 420): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    if ($text === '') return '';
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $limit, '...', 'UTF-8');
    }
    return strlen($text) > $limit ? substr($text, 0, $limit - 3) . '...' : $text;
}

function story_builder_block_summary(string $type, string $head, string $body, string $purpose = 'narrative'): string
{
    $limit = match ($purpose) {
        'evidence' => in_array($type, ['prompt', 'code', 'output'], true) ? 360 : 460,
        'opening' => 280,
        default => in_array($type, ['prompt', 'code', 'output'], true) ? 220 : 360,
    };
    $body = story_builder_trim_text($body, $limit);
    if ($body === '') return '';

    return match ($type) {
        'prompt' => 'Prompt / girdi - ' . $head . ': ' . $body,
        'code' => 'Kod / komut - ' . $head . ': ' . $body,
        'output' => 'Çıktı / cevap - ' . $head . ': ' . $body,
        'error' => 'Hata / belirti - ' . $head . ': ' . $body,
        'evidence' => 'Kanıt - ' . $head . ': ' . $body,
        'source' => 'Kaynak - ' . $head . ': ' . $body,
        'decision' => 'Karar - ' . $head . ': ' . $body,
        'next' => 'Sonraki iş - ' . $head . ': ' . $body,
        default => $head . ': ' . $body,
    };
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

function story_builder_copy_update_media_to_section(int $sectionId, array $updates): void
{
    $sort = 0;
    $seen = [];
    $st = db()->prepare('SELECT media_id,caption_override,sort_order FROM update_media WHERE update_id=? ORDER BY sort_order,id');
    $insert = db()->prepare(
        "INSERT OR IGNORE INTO story_section_media(section_id,media_id,role,caption_override,sort_order)
         VALUES (?,?,'gallery',?,?)"
    );

    foreach ($updates as $update) {
        $st->execute([(int)$update['id']]);
        foreach ($st->fetchAll() as $row) {
            $mediaId = (int)$row['media_id'];
            if ($mediaId <= 0 || isset($seen[$mediaId])) continue;
            $seen[$mediaId] = true;
            $insert->execute([$sectionId, $mediaId, (string)($row['caption_override'] ?? ''), ++$sort]);
        }
    }
}

function story_builder_copy_update_links_to_section(int $sectionId, array $updates): void
{
    $sort = 0;
    $seen = [];
    $st = db()->prepare("SELECT link_type,title,url FROM links WHERE owner_type='update' AND owner_id=? ORDER BY sort_order,id");
    $insert = db()->prepare(
        "INSERT INTO links(owner_type,owner_id,link_type,title,url,sort_order)
         VALUES ('story_section',?,?,?,?,?)"
    );

    foreach ($updates as $update) {
        $st->execute([(int)$update['id']]);
        foreach ($st->fetchAll() as $row) {
            $url = safe_external_url((string)($row['url'] ?? ''));
            if ($url === '' || isset($seen[$url])) continue;
            $seen[$url] = true;
            $insert->execute([$sectionId, (string)($row['link_type'] ?? 'external'), (string)($row['title'] ?? ''), $url, ++$sort]);
        }
    }
}

function story_builder_group_updates(array $updates): array
{
    $groups = [
        'opening' => [],
        'narrative' => [],
        'evidence' => [],
        'status' => [],
        'closing' => [],
    ];

    foreach ($updates as $update) {
        $role = atelier_default_story_role($update);
        $type = atelier_default_story_section_type($update);
        $kind = atelier_entry_kind($update);

        if ($role === 'opening') {
            $groups['opening'][] = $update;
        } elseif ($role === 'closing') {
            $groups['closing'][] = $update;
        } elseif ($role === 'status') {
            $groups['status'][] = $update;
        } elseif (in_array($role, ['media', 'source'], true) || in_array($type, ['gallery', 'video', 'code'], true) || in_array($kind, ['media', 'source'], true)) {
            $groups['evidence'][] = $update;
        } else {
            $groups['narrative'][] = $update;
        }
    }

    if ($groups['opening'] === [] && $updates !== []) {
        $groups['opening'][] = array_shift($updates);
        $openingId = (int)$groups['opening'][0]['id'];
        foreach (['narrative', 'evidence', 'status', 'closing'] as $key) {
            $groups[$key] = array_values(array_filter(
                $groups[$key],
                static fn(array $update): bool => (int)$update['id'] !== $openingId
            ));
        }
    }

    return $groups;
}

function story_builder_update_strength(array $update): int
{
    $score = 0;
    if (!empty($update['is_milestone'])) $score += 120;

    $role = atelier_default_story_role($update);
    $kind = atelier_entry_kind($update);
    $score += match ($role) {
        'opening' => 90,
        'decision', 'lesson' => 80,
        'problem' => 70,
        'media', 'source' => 60,
        'status', 'closing' => 50,
        default => 35,
    };
    $score += match ($kind) {
        'decision' => 30,
        'problem' => 25,
        'experiment' => 18,
        'media', 'source' => 15,
        default => 8,
    };

    $blocks = atelier_update_blocks($update);
    $score += min(30, count($blocks) * 5);
    if (!empty($update['media'])) $score += 18;
    if (!empty($update['links'])) $score += 12;
    if (trim((string)($update['decision'] ?? '')) !== '') $score += 15;
    if (trim((string)($update['failed'] ?? '')) !== '') $score += 10;

    return $score;
}

function story_builder_limit_updates(array $updates, int $limit): array
{
    if ($limit <= 0 || $updates === []) return [];
    if (count($updates) <= $limit) return array_values($updates);

    $ranked = [];
    foreach (array_values($updates) as $index => $update) {
        $ranked[] = [
            'index' => $index,
            'score' => story_builder_update_strength($update),
            'update' => $update,
        ];
    }

    usort($ranked, static function (array $a, array $b): int {
        if ($a['score'] === $b['score']) {
            return $a['index'] <=> $b['index'];
        }
        return $b['score'] <=> $a['score'];
    });
    $picked = array_slice($ranked, 0, $limit);
    usort($picked, static fn(array $a, array $b): int => $a['index'] <=> $b['index']);

    return array_map(static fn(array $row): array => $row['update'], $picked);
}

function story_builder_apply_scope(array $groups, array $scope): array
{
    $groups['opening'] = story_builder_limit_updates($groups['opening'] ?? [], 1);
    $groups['narrative'] = story_builder_limit_updates($groups['narrative'] ?? [], (int)$scope['narrative_limit']);
    $groups['evidence'] = story_builder_limit_updates($groups['evidence'] ?? [], (int)$scope['evidence_limit']);
    $groups['status'] = story_builder_limit_updates($groups['status'] ?? [], (int)$scope['status_limit']);
    $groups['closing'] = story_builder_limit_updates($groups['closing'] ?? [], (int)$scope['closing_limit']);

    return $groups;
}

function story_builder_count_grouped_updates(array $groups): int
{
    $count = 0;
    foreach (['opening', 'narrative', 'evidence', 'status', 'closing'] as $key) {
        $count += count($groups[$key] ?? []);
    }
    return $count;
}

function story_builder_group_label(string $key): string
{
    return match ($key) {
        'opening' => 'Açılış',
        'narrative' => 'Hikâye omurgası',
        'evidence' => 'Kanıtlar',
        'status' => 'Durum / sonraki iş',
        'closing' => 'Kapanış',
        default => $key,
    };
}

function story_builder_attach_blocks(array $updates): array
{
    foreach ($updates as $i => $update) {
        $updates[$i]['blocks'] = UpdateBlockRepository::forUpdate((int)$update['id']);
    }
    return $updates;
}

function story_builder_mode_middle_type(string $mode, array $config): string
{
    if ($mode === 'reflection') return 'lesson';
    if ($mode === 'discovery') return 'questions';
    return (string)$config['middle_type'];
}

function story_builder_insert_grouped_items(int $sectionId, array $updates, string $mode, bool $hasItemSource, string $purpose = 'narrative'): void
{
    foreach (array_values($updates) as $i => $update) {
        story_builder_insert_item($sectionId, $update, $i, $mode, $hasItemSource, $purpose);
    }
}

function story_builder_insert_item(int $sectionId, array $update, int $index, string $mode, bool $hasItemSource, string $purpose = 'narrative'): void
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
    $text = story_builder_update_text($update, $mode, $purpose);

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

function story_builder_insert_opening_from_updates(int $storyId, array $updates, array $project, array $config, int $sortOrder): int
{
    $update = $updates[0] ?? null;
    if (!$update) {
        return story_builder_insert_section(
            $storyId,
            'opening',
            $config['opening_layout'],
            $config['opening_label'],
            trim((string)($project['question'] ?: $project['title'])),
            trim((string)($project['summary'] ?? '')),
            '',
            'Bu taslak, Atölye kayıtlarından seçilerek oluşturuldu.',
            $sortOrder
        );
    }

    $title = story_builder_update_title($update, 'scene');
    $summary = trim((string)($update['summary'] ?? $project['summary'] ?? ''));
    $body = story_builder_update_text($update, 'scene', 'opening');
    $mediaId = story_builder_primary_media_id_for_update((int)$update['id']);

    $sectionId = story_builder_insert_section(
        $storyId,
        'opening',
        atelier_default_story_layout($update),
        atelier_story_label($update),
        $title,
        $body,
        $summary,
        trim((string)($project['question'] ?? '')),
        $sortOrder,
        (int)$update['id'],
        atelier_story_label($update),
        '',
        '',
        $mediaId
    );
    story_builder_copy_update_media_to_section($sectionId, [$update]);
    story_builder_copy_update_links_to_section($sectionId, [$update]);
    return $sectionId;
}

function story_builder_insert_group_section(
    int $storyId,
    string $type,
    string $layout,
    string $label,
    string $title,
    string $intro,
    array $updates,
    int $sortOrder,
    string $mode,
    bool $hasItemSource,
    string $purpose = 'narrative'
): int {
    if ($updates === []) return 0;

    $mediaId = 0;
    foreach ($updates as $update) {
        $mediaId = story_builder_primary_media_id_for_update((int)$update['id']);
        if ($mediaId > 0) break;
    }

    $sectionId = story_builder_insert_section(
        $storyId,
        $type,
        $layout,
        $label,
        $title,
        '',
        $intro,
        '',
        $sortOrder,
        0,
        $label,
        '',
        '',
        $mediaId
    );
    story_builder_insert_grouped_items($sectionId, $updates, $mode, $hasItemSource, $purpose);
    story_builder_copy_update_media_to_section($sectionId, $updates);
    story_builder_copy_update_links_to_section($sectionId, $updates);

    return $sectionId;
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
            $layout,
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
        $scopeName = (string)($_POST['story_scope'] ?? 'standard');
        $scope = story_builder_scope_config($scopeName);
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
        $needsOpening = ($max === 0 || checkbox('replace_sections'));
        $q = db()->prepare('SELECT * FROM updates WHERE id=? AND project_id=?');
        $hasItemSource = story_builder_has_column('story_section_items', 'source_update_id');
        $selectedUpdates = [];
        foreach ($selected as $i => $uid) {
            $q->execute([$uid, $projectId]);
            $update = $q->fetch();
            if (!$update) continue;
            $update['blocks'] = UpdateBlockRepository::forUpdate((int)$update['id']);
            $selectedUpdates[] = $update;
        }
        if ($selectedUpdates === []) {
            throw new RuntimeException('Seçilen kayıtlar bu projede bulunamadı.');
        }

        $groups = story_builder_group_updates($selectedUpdates);
        $groups = story_builder_apply_scope($groups, $scope);
        $usedUpdateCount = story_builder_count_grouped_updates($groups);
        if ($needsOpening) {
            story_builder_insert_opening_from_updates($storyId, $groups['opening'], $project, $config, ++$max);
        }
        $narrativeUpdates = $needsOpening
            ? $groups['narrative']
            : array_merge($groups['opening'], $groups['narrative']);
        story_builder_insert_group_section(
            $storyId,
            story_builder_mode_middle_type($mode, $config),
            'default',
            $config['middle_label'],
            $config['middle_title'],
            $config['middle_intro'],
            $narrativeUpdates,
            ++$max,
            $mode,
            $hasItemSource,
            'narrative'
        );

        story_builder_insert_group_section(
            $storyId,
            'gallery',
            'wide',
            'KANITLAR',
            'Atölyeden hikâyeye taşınan kanıtlar.',
            'Görseller, videolar, bağlantılar, promptlar veya çıktılar burada hikâyenin dayanağı olarak toplanır.',
            $groups['evidence'],
            ++$max,
            $mode,
            $hasItemSource,
            'evidence'
        );

        story_builder_insert_group_section(
            $storyId,
            'status',
            'default',
            'DURUM',
            'Bugün bu iş nerede duruyor?',
            'Atölyede kalan durum notları ve sonraki adımlar.',
            $groups['status'],
            ++$max,
            $mode,
            $hasItemSource,
            'status'
        );

        $closingUpdates = $groups['closing'];
        $closingBody = trim((string)($_POST['closing_note'] ?? $project['closing_note']));
        if ($closingUpdates !== []) {
            $closingBody = trim($closingBody . "\n\n" . implode("\n\n", array_map(
                static fn(array $update): string => story_builder_update_text($update, $mode, 'closing'),
                $closingUpdates
            )));
        }
        story_builder_insert_section(
            $storyId,
            'text',
            'wide',
            $config['ending_label'],
            $config['ending_title'],
            $closingBody ?: 'Bu bölüm hikâye düzenleyicisinden tamamlanacak.',
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
        admin_audit('build_story', 'story', $storyId, 'Mode: ' . $mode . ' / Scope: ' . $scopeName . ' / Used updates: ' . $usedUpdateCount . ' / Selected updates: ' . implode(',', $selected));
        flash('success', 'Hikâye taslağı oluşturuldu. Seçilen ' . count($selectedUpdates) . ' kayıttan ' . $usedUpdateCount . ' kayıt taslağa taşındı. Kalanlar Atölye kaydı olarak duruyor.');
        redirect('story-edit.php?project_id=' . $projectId);
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        $error = admin_error_message($e, 'admin.story_builder');
    }
}

$draftModes = ['balanced', 'discovery', 'scene', 'reflection'];
$storyScopes = story_builder_scope_options();
$defaultSelectedUpdates = array_values(array_filter($updates, static fn(array $update): bool => !empty($update['is_milestone'])));
$defaultGroups = [];
$defaultUsedCount = 0;
if ($defaultSelectedUpdates !== []) {
    $defaultGroups = story_builder_apply_scope(
        story_builder_group_updates(story_builder_attach_blocks($defaultSelectedUpdates)),
        story_builder_scope_config('standard')
    );
    $defaultUsedCount = story_builder_count_grouped_updates($defaultGroups);
}

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

        <section class="panel">
            <h2>Hikâye yoğunluğu</h2>
            <p class="help">Uzun Atölye kayıtları doğrudan hikâyeye dökülmez. Bu seçim, kaç kaydın hikâye omurgasına taşınacağını belirler; kalan kayıtlar Atölye arşivinde kalır.</p>
            <div class="draft-mode-grid">
                <?php foreach ($storyScopes as $scopeKey => $scope): ?>
                    <label class="draft-mode-card">
                        <input type="radio" name="story_scope" value="<?= e($scopeKey) ?>" <?= $scopeKey === 'standard' ? 'checked' : '' ?>>
                        <strong><?= e($scope['label']) ?></strong>
                        <span><?= e($scope['help']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel story-plan-panel">
            <h2>Varsayılan taslak planı</h2>
            <?php if ($defaultSelectedUpdates === []): ?>
                <p class="help">Henüz dönüm noktası işaretli kayıt yok. Hikâye taslağı oluşturmadan önce aşağıdan hangi kayıtların omurgaya gireceğini seç.</p>
            <?php else: ?>
                <p class="help"><?= count($defaultSelectedUpdates) ?> dönüm noktasından orta yoğunlukta <?= $defaultUsedCount ?> kayıt hikâye iskeletine girer. Kalanlar Atölye kaydı olarak durur.</p>
                <div class="story-plan-grid">
                    <?php foreach (['opening', 'narrative', 'evidence', 'status', 'closing'] as $groupKey): $groupUpdates = $defaultGroups[$groupKey] ?? []; ?>
                        <div>
                            <strong><?= e(story_builder_group_label($groupKey)) ?></strong>
                            <?php if ($groupUpdates === []): ?>
                                <small>Kayıt yok</small>
                            <?php else: ?>
                                <?php foreach ($groupUpdates as $update): ?>
                                    <small><?= e((string)$update['title']) ?></small>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
            <p class="help">Burada seçilenler hikâyenin iskeletine girer. Prompt, çıktı, medya ve linkler kanıt olarak kalır; hikâye dili daha sonra Hikâye editöründe temizlenir.</p>
            <div class="list">
                <?php foreach ($updates as $update): $kind = atelier_entry_kind_config($update); $bridge = atelier_story_bridge($update); $blockCount = count(atelier_update_blocks($update)); $mediaCount = count(update_media((int)$update['id'])); $linkCount = count(owner_links('update', (int)$update['id'])); ?>
                    <label class="list-row">
                        <input type="checkbox" name="update_ids[]" value="<?= (int)$update['id'] ?>" <?= $update['is_milestone'] ? 'checked' : '' ?>>
                        <span>
                            <strong><?= e($update['title']) ?></strong>
                            <small><?= e($update['date_label']) ?> · <?= e($update['phase']) ?> · Hikâyede: <?= e($bridge['reader_label']) ?> / <?= e($bridge['type_label']) ?> / <?= e($bridge['layout_label']) ?></small>
                            <small><?= $blockCount ?> iş bloğu · <?= $mediaCount ?> medya · <?= $linkCount ?> bağlantı</small>
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
