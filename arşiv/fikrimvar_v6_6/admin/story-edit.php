<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
admin_require_login();

$slug = safe_slug((string) ($_GET['slug'] ?? $_POST['slug'] ?? ''));
$project = load_project($slug);
if (!$project) { http_response_code(404); exit('Proje bulunamadı.'); }
$story = is_array($project['_story'] ?? null) && $project['_story'] !== [] ? $project['_story'] : admin_story_skeleton($project);
$values = [
    'title' => (string) ($story['title'] ?? $project['title'] ?? ''),
    'question' => (string) ($story['question'] ?? $project['question'] ?? ''),
    'summary' => (string) ($story['summary'] ?? $project['summary'] ?? ''),
    'reading_time' => (string) ($story['reading_time'] ?? '3 dakika'),
    'blocks_json' => json_encode(is_array($story['blocks'] ?? null) ? $story['blocks'] : [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    'publish' => story_status($project) === 'published',
];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_require_csrf();
    foreach (['title','question','summary','reading_time','blocks_json'] as $key) {
        $values[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    $values['publish'] = isset($_POST['publish']);
    if ($values['title'] === '' || $values['question'] === '') {
        $error = 'Hikâye adı ve merak sorusu boş olamaz.';
    } else {
        try {
            $blocks = json_decode($values['blocks_json'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($blocks) || !array_is_list($blocks)) {
                throw new RuntimeException('Blok JSON’u bir liste olmalı.');
            }
            foreach ($blocks as $index => $block) {
                if (!is_array($block) || empty($block['type'])) {
                    throw new RuntimeException(($index + 1) . '. blokta type alanı eksik.');
                }
            }
            $newStory = array_replace($story, [
                'slug' => $slug,
                'title' => $values['title'],
                'question' => $values['question'],
                'summary' => $values['summary'],
                'category' => (string) ($project['category'] ?? ''),
                'category_label' => (string) ($project['category_label'] ?? ''),
                'status' => (string) ($project['status'] ?? 'suruyor'),
                'status_label' => (string) ($project['status_label'] ?? 'Kayıt'),
                'type_label' => 'Proje hikâyesi',
                'order' => (float) ($project['order'] ?? 50),
                'started_at' => $project['started_at'] ?? null,
                'updated_at' => date('Y-m-d'),
                'cover' => (string) ($project['cover'] ?? 'media/cover.svg'),
                'tags' => is_array($project['tags'] ?? null) ? $project['tags'] : [],
                'homepage' => (bool) ($project['homepage'] ?? false),
                'reading_time' => $values['reading_time'],
                'blocks' => $blocks,
            ]);
            save_story_record($slug, $newStory);
            $record = $project['_project'];
            $record['question'] = $values['question'];
            $record['summary'] = $values['summary'];
            $record['updated_at'] = date('Y-m-d');
            $record['story'] = array_replace($record['story'] ?? [], [
                'status' => $values['publish'] ? 'published' : 'draft',
                'published_at' => $values['publish'] ? (($record['story']['published_at'] ?? null) ?: date(DATE_ATOM)) : null,
            ]);
            save_project_record($record);
            admin_flash('success', $values['publish'] ? 'Hikâye kaydedildi ve yayına alındı.' : 'Hikâye taslağı kaydedildi.');
            admin_redirect('story-edit.php?slug=' . rawurlencode($slug));
        } catch (JsonException $exception) {
            $error = 'Blok JSON’u geçerli değil: ' . $exception->getMessage();
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

$project = load_project($slug) ?? $project;
admin_layout_start('Hikâye: ' . ($project['title'] ?? ''), 'projects');
?>
<section class="admin-hero"><div><p class="admin-eyebrow">DÜZENLENMİŞ ANLATI</p><h1><?= e($project['title'] ?? '') ?></h1><p class="admin-muted">Ham Atölye günlüğü burada kopyalanmaz. Seçilen dönüm noktaları, kısa anlatılar ve görsel bloklarla okunabilir bir hikâyeye dönüşür.</p></div><div class="admin-actions"><?php if (story_is_published($project)): ?><a href="../hikaye.php?slug=<?= e(rawurlencode($slug)) ?>" target="_blank" rel="noopener">Yayındaki hikâye</a><?php endif; ?><a href="project-edit.php?slug=<?= e(rawurlencode($slug)) ?>">Projeye dön</a></div></section>
<?php if ($error !== ''): ?><div class="admin-flash admin-flash--error"><?= e($error) ?></div><?php endif; ?>
<div class="admin-split">
<form method="post" class="admin-form admin-card">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="slug" value="<?= e($slug) ?>">
    <div class="admin-field admin-field--full"><label for="title">Hikâye adı</label><input id="title" name="title" value="<?= e($values['title']) ?>" required></div>
    <div class="admin-field admin-field--full"><label for="question">Okuyucuyu çağıran soru / başlık</label><input id="question" name="question" value="<?= e($values['question']) ?>" required></div>
    <div class="admin-field admin-field--full"><label for="summary">Kısa özet</label><textarea id="summary" name="summary"><?= e($values['summary']) ?></textarea></div>
    <div class="admin-field"><label for="reading_time">Okuma süresi etiketi</label><input id="reading_time" name="reading_time" value="<?= e($values['reading_time']) ?>"></div>
    <div class="admin-field"><label class="admin-check"><input type="checkbox" name="publish" <?= $values['publish'] ? 'checked' : '' ?>> Hikâyeyi yayımla</label></div>
    <div class="admin-field admin-field--full"><label for="blocks_json">Hikâye blokları</label><textarea class="admin-json" id="blocks_json" name="blocks_json" spellcheck="false"><?= e($values['blocks_json']) ?></textarea></div>
    <div class="admin-form-actions"><button class="admin-button admin-button--primary" type="submit">Hikâyeyi kaydet</button><a class="admin-button" href="project-edit.php?slug=<?= e(rawurlencode($slug)) ?>">Vazgeç</a></div>
</form>
<aside class="admin-aside admin-card">
    <p class="admin-eyebrow">BLOK KILAVUZU</p><h2>Mevcut motoru koruyoruz</h2>
    <p class="admin-muted">Bloklar sırayla görünür. Atölyeyi kapatma sihirbazı başlangıç, zaman çizgisi, kapanış ve öğrenilenler bloklarını otomatik oluşturur.</p>
    <ul class="admin-muted"><li><code>opening</code>: giriş metni ve görsel</li><li><code>timeline</code>: dönüm noktaları</li><li><code>questions</code>: açılır teknik sorular</li><li><code>compare</code>: iki yaklaşımı karşılaştırma</li><li><code>roles</code>: YZ / Ahmet ayrımı</li><li><code>gallery</code>, <code>video</code>, <code>code</code></li><li><code>status</code>, <code>lesson</code></li></ul>
    <p class="admin-help">JSON bozuksa kayıt yapılmaz; mevcut dosyanın yerine yazılmadan önce doğrulanır ve eski dosya yedeklenir.</p>
</aside>
</div>
<?php admin_layout_end(); ?>
