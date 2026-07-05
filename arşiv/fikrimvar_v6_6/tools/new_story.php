<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Bu araç yalnızca komut satırından çalışır. Yönetim paneli için /admin/ kullanın.\n");
    exit(1);
}

$slug = safe_slug((string) ($argv[1] ?? ''));
$title = trim((string) ($argv[2] ?? 'Yeni Proje'));
$mode = trim((string) ($argv[3] ?? 'workshop'));
if ($slug === '') {
    fwrite(STDERR, "Kullanım: php tools/new_story.php yeni-proje \"Başlık\" [workshop|story|draft]\n");
    exit(1);
}
if (!in_array($mode, ['workshop','story','draft'], true)) {
    fwrite(STDERR, "Tür workshop, story veya draft olmalı.\n");
    exit(1);
}
if (is_dir(project_dir($slug))) {
    fwrite(STDERR, "Bu slug zaten var: {$slug}\n");
    exit(1);
}

ensure_directory(media_dir($slug));
ensure_directory(update_dir($slug));
$cover = 'media/cover.svg';
$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 720"><rect width="1200" height="720" fill="#11151a"/><path d="M80 590 C310 150 710 760 1120 180" fill="none" stroke="#ad5535" stroke-width="8"/><circle cx="820" cy="330" r="170" fill="none" stroke="#efe6da" stroke-opacity=".6" stroke-width="3"/></svg>';
atomic_write_text(media_dir($slug) . '/cover.svg', $svg, false);

$project = [
    'schema_version' => 2,
    'slug' => $slug,
    'title' => $title,
    'question' => $title,
    'summary' => 'Bu projenin kısa açıklamasını yaz.',
    'category' => 'kod-sistem',
    'category_label' => 'Kod ve sistemler',
    'status' => 'suruyor',
    'status_label' => 'Üzerinde çalışıyorum',
    'type_label' => $mode === 'workshop' ? 'Canlı atölye' : 'Proje hikâyesi',
    'order' => 50,
    'started_at' => date('Y-m-d'),
    'updated_at' => date('Y-m-d'),
    'cover' => $cover,
    'tags' => [],
    'homepage' => false,
    'public' => $mode !== 'draft',
    'workshop_question' => $mode === 'workshop' ? 'Bu fikir çalışırken nasıl değişecek?' : null,
    'workshop' => ['status'=>$mode==='workshop'?'open':'none','started_at'=>$mode==='workshop'?date('Y-m-d'):null,'ended_at'=>null,'closing_state'=>null,'closing_note'=>''],
    'story' => ['status'=>$mode==='story'?'draft':'none','published_at'=>null,'generated_from_updates'=>[]],
];
save_project_record($project);
$story = [
    'slug'=>$slug,'title'=>$title,'question'=>$title,'summary'=>$project['summary'],
    'category'=>$project['category'],'category_label'=>$project['category_label'],
    'status'=>$project['status'],'status_label'=>$project['status_label'],'type_label'=>'Proje hikâyesi',
    'order'=>50,'started_at'=>$project['started_at'],'updated_at'=>$project['updated_at'],'cover'=>$cover,'tags'=>[],
    'homepage'=>false,'reading_time'=>'3 dakika','generated_by_admin'=>true,
    'blocks'=>[['type'=>'opening','layout'=>'hero-split','label'=>'NEDEN BAŞLADI?','title'=>$title,'paragraphs'=>['İlk paragrafı yaz.'],'image'=>$cover,'caption'=>$title]],
];
save_story_record($slug, $story);

fwrite(STDOUT, "Oluşturuldu: content/stories/{$slug}\nYönet: /admin/project-edit.php?slug={$slug}\n");
