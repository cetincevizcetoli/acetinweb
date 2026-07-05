<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
admin_require_login();

$slug = safe_slug((string) ($_GET['slug'] ?? $_POST['slug'] ?? ''));
$project = load_project($slug);
if (!$project) { http_response_code(404); exit('Proje bulunamadı.'); }
$updates = load_updates($project);
$error = '';
$selectedDefault = array_map(static fn(array $u): string => (string) ($u['_id'] ?? ''), array_filter($updates, static fn(array $u): bool => (bool) ($u['milestone'] ?? false)));
if ($selectedDefault === [] && $updates !== []) {
    $selectedDefault = array_values(array_filter([(string)($updates[0]['_id'] ?? ''), (string)($updates[array_key_last($updates)]['_id'] ?? '')]));
}
$values = [
    'closing_state' => 'completed',
    'ended_at' => date('Y-m-d'),
    'closing_note' => '',
    'story_question' => (string) ($project['question'] ?? $project['title'] ?? ''),
    'story_summary' => (string) ($project['summary'] ?? ''),
    'intro' => (string) ($project['summary'] ?? ''),
    'lessons' => '',
    'selected' => $selectedDefault,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_require_csrf();
    foreach (['closing_state','ended_at','closing_note','story_question','story_summary','intro','lessons'] as $key) {
        $values[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    $values['selected'] = array_values(array_filter(array_map('safe_slug', is_array($_POST['selected'] ?? null) ? $_POST['selected'] : [])));
    if ($values['story_question'] === '') {
        $error = 'Hikâye başlığı / sorusu gerekli.';
    } else {
        try {
            $stateMap = [
                'completed' => ['status'=>'tamamlandi','label'=>'Bu hâliyle bitti'],
                'abandoned' => ['status'=>'yarim','label'=>'Yarım bıraktım'],
                'paused' => ['status'=>'beklemede','label'=>'Beklemeye aldım'],
                'transformed' => ['status'=>'donustu','label'=>'Başka projeye dönüştü'],
            ];
            $state = $stateMap[$values['closing_state']] ?? $stateMap['completed'];
            $selectedUpdates = array_values(array_filter($updates, static fn(array $u): bool => in_array((string)($u['_id'] ?? ''), $values['selected'], true)));
            $timeline = [];
            foreach ($selectedUpdates as $index => $update) {
                $timeline[] = [
                    'step' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'subtitle' => (string) ($update['date_label'] ?? format_tr_date((string)($update['date'] ?? ''))),
                    'title' => (string) ($update['title'] ?? ''),
                    'text' => trim((string) ($update['summary'] ?? '') . (($update['decision'] ?? '') !== '' ? ' Karar: ' . $update['decision'] : '')),
                ];
            }
            $blocks = [[
                'type' => 'opening',
                'layout' => 'hero-split',
                'label' => 'NEREDEN ÇIKTI?',
                'title' => $values['story_question'],
                'paragraphs' => array_values(array_filter([$values['intro'], $values['closing_note']])),
                'image' => (string) ($project['cover'] ?? 'media/cover.svg'),
                'caption' => (string) ($project['title'] ?? ''),
            ]];
            if ($timeline !== []) {
                $blocks[] = [
                    'type' => 'timeline',
                    'label' => 'DÖNÜM NOKTALARI',
                    'title' => 'Atölyede yönü değiştiren anlar',
                    'items' => $timeline,
                ];
            }
            $blocks[] = [
                'type' => 'status',
                'label' => 'KAPANIŞ KARARI',
                'title' => $state['label'],
                'items' => [[
                    'state' => $state['label'],
                    'title' => (string) ($project['title'] ?? ''),
                    'text' => $values['closing_note'] !== '' ? $values['closing_note'] : 'Atölye bu kararla kapandı. Ham günlük kayıtları arşivde korunuyor.',
                ]],
            ];
            $lessons = array_values(array_filter(array_map('trim', preg_split('/\R+/u', $values['lessons']) ?: [])));
            if ($lessons !== []) {
                $blocks[] = ['type'=>'lesson','label'=>'BENDE KALAN','title'=>'Bu çalışmadan öğrendiklerim','items'=>$lessons];
            }
            $story = [
                'slug' => $slug,
                'title' => (string) ($project['title'] ?? ''),
                'question' => $values['story_question'],
                'summary' => $values['story_summary'],
                'category' => (string) ($project['category'] ?? ''),
                'category_label' => (string) ($project['category_label'] ?? ''),
                'status' => $state['status'],
                'status_label' => $state['label'],
                'type_label' => 'Proje hikâyesi',
                'order' => (float) ($project['order'] ?? 50),
                'started_at' => $project['started_at'] ?? null,
                'updated_at' => $values['ended_at'],
                'cover' => (string) ($project['cover'] ?? 'media/cover.svg'),
                'tags' => is_array($project['tags'] ?? null) ? $project['tags'] : [],
                'homepage' => (bool) ($project['homepage'] ?? false),
                'reading_time' => max(2, count($blocks)) . ' dakika',
                'generated_by_admin' => true,
                'generated_from_updates' => $values['selected'],
                'blocks' => $blocks,
            ];
            save_story_record($slug, $story);

            $record = $project['_project'];
            $record['status'] = $state['status'];
            $record['status_label'] = $state['label'];
            $record['type_label'] = 'Proje hikâyesi';
            $record['updated_at'] = $values['ended_at'];
            $record['workshop'] = array_replace($record['workshop'] ?? [], [
                'status' => 'closed',
                'ended_at' => $values['ended_at'],
                'closing_state' => $values['closing_state'],
                'closing_note' => $values['closing_note'],
            ]);
            $record['story'] = array_replace($record['story'] ?? [], [
                'status' => 'draft',
                'published_at' => null,
                'generated_from_updates' => $values['selected'],
            ]);
            save_project_record($record);

            $siteData = load_site();
            if (safe_slug((string)($siteData['homepage']['pinned_atelier'] ?? '')) === $slug) {
                $siteData['homepage']['pinned_atelier'] = '';
                save_site($siteData);
            }
            admin_flash('success', 'Atölye kapandı ve hikâye taslağı oluşturuldu. Ham günlük kayıtları korunuyor.');
            admin_redirect('story-edit.php?slug=' . rawurlencode($slug));
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

admin_layout_start('Atölyeyi kapat', 'projects');
?>
<section class="admin-hero"><div><p class="admin-eyebrow">ATÖLYEDEN HİKÂYEYE</p><h1><?= e($project['title'] ?? '') ?></h1><p class="admin-muted"><?= count($updates) ?> ham kayıttan yalnızca gerçekten yönü değiştirenleri seç. Ham günlük silinmeyecek.</p></div></section>
<?php if ($error !== ''): ?><div class="admin-flash admin-flash--error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="admin-form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="slug" value="<?= e($slug) ?>">
    <div class="admin-field admin-field--third"><label for="closing_state">Kapanış kararı</label><select id="closing_state" name="closing_state"><option value="completed">Bu hâliyle bitti</option><option value="abandoned">Yarım bıraktım</option><option value="paused">Beklemeye aldım</option><option value="transformed">Başka projeye dönüştü</option></select></div>
    <div class="admin-field admin-field--third"><label for="ended_at">Kapanış tarihi</label><input id="ended_at" name="ended_at" type="date" value="<?= e($values['ended_at']) ?>"></div>
    <div class="admin-field admin-field--full"><label for="closing_note">Neden burada kapandı?</label><textarea id="closing_note" name="closing_note"><?= e($values['closing_note']) ?></textarea></div>
    <div class="admin-field admin-field--full"><label for="story_question">Hikâyenin merak sorusu</label><input id="story_question" name="story_question" value="<?= e($values['story_question']) ?>" required></div>
    <div class="admin-field admin-field--full"><label for="story_summary">Hikâye özeti</label><textarea id="story_summary" name="story_summary"><?= e($values['story_summary']) ?></textarea></div>
    <div class="admin-field admin-field--full"><label for="intro">Açılış metni</label><textarea id="intro" name="intro"><?= e($values['intro']) ?></textarea></div>
    <div class="admin-field admin-field--full"><label>Dönüm noktaları</label><div class="admin-milestones"><?php if ($updates === []): ?><div class="admin-empty">Atölye kaydı yok. Yine de boş bir hikâye taslağı oluşturabilirsin.</div><?php endif; ?><?php foreach ($updates as $update): $id=(string)($update['_id']??''); ?><label class="admin-milestone"><input type="checkbox" name="selected[]" value="<?= e($id) ?>" <?= in_array($id,$values['selected'],true)?'checked':'' ?>><time><?= e($update['date_label'] ?? format_tr_date((string)($update['date']??''))) ?></time><span><strong><?= e($update['title']??'') ?></strong><small><?= e($update['summary']??'') ?></small></span></label><?php endforeach; ?></div></div>
    <div class="admin-field admin-field--full"><label for="lessons">Bende kalanlar <small>(her satır bir madde)</small></label><textarea id="lessons" name="lessons"><?= e($values['lessons']) ?></textarea></div>
    <div class="admin-form-actions"><button class="admin-button admin-button--rust" type="submit">Atölyeyi kapat ve taslak oluştur</button><a class="admin-button" href="project-edit.php?slug=<?= e(rawurlencode($slug)) ?>">Vazgeç</a></div>
</form>
<?php admin_layout_end(); ?>
