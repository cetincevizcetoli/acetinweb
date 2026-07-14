<?php
declare(strict_types=1);

function atelier_entry_kind_options(): array
{
    return [
        'journal' => [
            'label' => 'Çalışma notu',
            'short' => 'Not',
            'story_label' => 'ÇALIŞMA NOTU',
            'item_type' => 'timeline',
            'help' => 'Ham gözlem, kısa iş notu veya gün sonu kaydı.',
            'seed' => 'Bağlam veya ara geçiş kaydı olarak seçilebilir.',
            'summary_prompt' => 'Bu kaydın kısa iş özeti ne?',
            'tried_prompt' => 'Bugün yapılan iş / ham not ne?',
            'failed_prompt' => 'Ek bağlam, eksik kalan veya dikkat çeken şey ne?',
            'decision_prompt' => 'Bu kayıttan çıkan karar/not ne?',
            'next_prompt' => 'Sonraki kaynak kontrolü ne?',
        ],
        'experiment' => [
            'label' => 'Test / çıktı',
            'short' => 'Test',
            'story_label' => 'DENEME',
            'item_type' => 'timeline',
            'help' => 'Prompt, komut, araç denemesi, kod parçası veya test çıktısı.',
            'seed' => 'Deneme sahnesi veya teknik çıktı olarak seçilebilir.',
            'summary_prompt' => 'Testin kısa amacı veya sonucu ne?',
            'tried_prompt' => 'Girdi / prompt / komut / kod parçası neydi?',
            'failed_prompt' => 'Çıktı / log / ekranda görülen sonuç ne oldu?',
            'decision_prompt' => 'Bu çıktıya göre alınan teknik not/karar ne?',
            'next_prompt' => 'Sıradaki test veya kontrol ne?',
        ],
        'problem' => [
            'label' => 'Sorun / hata',
            'short' => 'Hata',
            'story_label' => 'HATA KAYDI',
            'item_type' => 'question',
            'help' => 'Hata mesajı, kırılan davranış, yanlış çıktı veya engel.',
            'seed' => 'Sorun/çatışma bölümü için ham kanıt olur.',
            'summary_prompt' => 'Hata/sorun kısa olarak ne?',
            'tried_prompt' => 'Hata nerede, hangi işlemden sonra çıktı?',
            'failed_prompt' => 'Log / belirti / yanlış sonuç ne?',
            'decision_prompt' => 'Çözüm denemesi veya teknik karar ne?',
            'next_prompt' => 'Kontrol edilecek sonraki şey ne?',
        ],
        'decision' => [
            'label' => 'Karar / not',
            'short' => 'Karar',
            'story_label' => 'DÖNÜM NOKTASI',
            'item_type' => 'lesson',
            'help' => 'İşin yönünü, kapsamını veya teknik yaklaşımı değiştiren karar.',
            'seed' => 'Dönüm noktası veya ders bölümü için seçilebilir.',
            'summary_prompt' => 'Kararın kısa özeti ne?',
            'tried_prompt' => 'Karardan önceki seçenekler veya girdiler neydi?',
            'failed_prompt' => 'Bu kararı zorunlu yapan kanıt/ne oldu?',
            'decision_prompt' => 'Net karar ne?',
            'next_prompt' => 'Bu karardan sonra yapılacak iş ne?',
        ],
        'media' => [
            'label' => 'Medya / görsel inceleme',
            'short' => 'Medya',
            'story_label' => 'GÖRSEL KANIT',
            'item_type' => 'timeline',
            'help' => 'Görsel, video, ses veya ekran görüntüsü üzerinden anlatılan kayıt.',
            'seed' => 'Kanıt, örnek veya sahne görseli olarak seçilebilir.',
            'summary_prompt' => 'Bu medya neyi gösteriyor?',
            'tried_prompt' => 'Medyanın kaynağı / üretim promptu / dosya notu ne?',
            'failed_prompt' => 'Görüntüde/seste görülen çıktı veya sorun ne?',
            'decision_prompt' => 'Bu medyadan çıkan kullanım kararı ne?',
            'next_prompt' => 'Bir sonraki medya/çıktı ne olacak?',
        ],
        'source' => [
            'label' => 'Bağlantı / kaynak notu',
            'short' => 'Kaynak',
            'story_label' => 'KAYNAK',
            'item_type' => 'question',
            'help' => 'Dış bağlantı, kaynak, demo, referans veya okuma notu.',
            'seed' => 'Dayanak veya bağlantı notu olarak seçilebilir.',
            'summary_prompt' => 'Bu kaynak neden eklendi?',
            'tried_prompt' => 'Bağlantı/kaynak nerede kullanılacak?',
            'failed_prompt' => 'Kaynağın gösterdiği çıktı, sınır veya not ne?',
            'decision_prompt' => 'Bu kaynaktan çıkan karar/not ne?',
            'next_prompt' => 'Bu kaynakla ilgili sıradaki işlem ne?',
        ],
    ];
}

function atelier_work_field_labels(array $update): array
{
    return match (atelier_entry_kind($update)) {
        'experiment' => [
            'tried' => 'Girdi / prompt / kod',
            'failed' => 'Çıktı / log',
            'decision' => 'Teknik not',
            'next_step' => 'Sıradaki test',
        ],
        'problem' => [
            'tried' => 'Hata nerede çıktı?',
            'failed' => 'Belirti / log',
            'decision' => 'Çözüm denemesi',
            'next_step' => 'Kontrol',
        ],
        'decision' => [
            'tried' => 'Seçenekler / veri',
            'failed' => 'Gerekçe / kanıt',
            'decision' => 'Net karar',
            'next_step' => 'Uygulanacak iş',
        ],
        'media' => [
            'tried' => 'Medya kaynağı',
            'failed' => 'Gözlenen çıktı',
            'decision' => 'Kullanım notu',
            'next_step' => 'Sonraki çıktı',
        ],
        'source' => [
            'tried' => 'Kaynak / bağlantı',
            'failed' => 'Kaynağın gösterdiği',
            'decision' => 'Not / karar',
            'next_step' => 'Sonraki kaynak kontrolü',
        ],
        default => [
            'tried' => 'Saha / çalışma notu',
            'failed' => 'Gözlem / eksik veri',
            'decision' => 'Kayıttan çıkan not',
            'next_step' => 'Sıradaki iş',
        ],
    };
}

function atelier_work_fields(array $update): array
{
    $labels = atelier_work_field_labels($update);
    $fields = [];
    foreach ($labels as $key => $label) {
        $value = trim((string)($update[$key] ?? ''));
        if ($value === '') continue;
        $fields[] = ['key' => $key, 'label' => $label, 'value' => $value];
    }
    return $fields;
}

function atelier_block_type_for_legacy_field(string $kind, string $field): string
{
    if ($field === 'summary') return 'field_note';
    if ($field === 'next_step') return 'next';

    return match ($kind) {
        'experiment' => match ($field) {
            'tried' => 'prompt',
            'failed' => 'output',
            'decision' => 'decision',
            default => 'field_note',
        },
        'problem' => match ($field) {
            'tried' => 'error',
            'failed' => 'evidence',
            'decision' => 'decision',
            default => 'field_note',
        },
        'decision' => match ($field) {
            'tried' => 'field_note',
            'failed' => 'evidence',
            'decision' => 'decision',
            default => 'field_note',
        },
        'media' => match ($field) {
            'tried' => 'source',
            'failed' => 'observation',
            'decision' => 'decision',
            default => 'field_note',
        },
        'source' => match ($field) {
            'tried' => 'source',
            'failed' => 'observation',
            'decision' => 'decision',
            default => 'field_note',
        },
        default => match ($field) {
            'failed' => 'observation',
            'decision' => 'decision',
            default => 'field_note',
        },
    };
}

function atelier_legacy_update_blocks(array $update): array
{
    $kind = atelier_entry_kind($update);
    $labels = atelier_work_field_labels($update);
    $fields = ['summary' => 'Kisa is ozeti'] + $labels;
    $blocks = [];
    $sort = 1;

    foreach ($fields as $key => $label) {
        $body = trim((string)($update[$key] ?? ''));
        if ($body === '') continue;
        $blocks[] = [
            'block_type' => atelier_block_type_for_legacy_field($kind, $key),
            'title' => (string)$label,
            'body' => $body,
            'sort_order' => $sort++,
            '_legacy' => true,
        ];
    }

    return $blocks;
}

function atelier_update_blocks(array $update): array
{
    $blocks = $update['blocks'] ?? [];
    if (is_array($blocks) && $blocks !== []) {
        return UpdateBlockRepository::normalizeRows($blocks);
    }

    return atelier_legacy_update_blocks($update);
}

function atelier_artifact_title(string $fallback, string $value): string
{
    $lines = preg_split('/\R/u', trim($value)) ?: [];
    foreach ($lines as $line) {
        $line = trim($line, " \t\n\r\0\x0B:-");
        if ($line !== '') {
            return function_exists('mb_strimwidth') ? mb_strimwidth($line, 0, 86, '...', 'UTF-8') : substr($line, 0, 86);
        }
    }
    return $fallback;
}

function atelier_add_artifact(array &$artifacts, string $key, string $type, string $label, string $title, string $body): void
{
    $body = trim($body);
    if ($body === '') return;
    $artifacts[] = [
        'key' => $key,
        'type' => $type,
        'label' => $label,
        'title' => $title,
        'body' => $body,
    ];
}

function atelier_work_artifacts(array $update): array
{
    $blocks = atelier_update_blocks($update);
    if ($blocks !== []) {
        $artifacts = [];
        foreach ($blocks as $i => $block) {
            $body = trim((string)($block['body'] ?? ''));
            if ($body === '') continue;
            $type = UpdateBlockRepository::validType((string)($block['block_type'] ?? 'field_note'));
            $label = UpdateBlockRepository::typeLabel($type);
            $title = trim((string)($block['title'] ?? ''));
            atelier_add_artifact(
                $artifacts,
                $type . '-' . (string)($i + 1),
                $type,
                $label,
                $title !== '' ? $title : atelier_artifact_title($label, $body),
                $body
            );
        }
        return $artifacts;
    }

    $kind = atelier_entry_kind($update);
    $tried = trim((string)($update['tried'] ?? ''));
    $failed = trim((string)($update['failed'] ?? ''));
    $decision = trim((string)($update['decision'] ?? ''));
    $next = trim((string)($update['next_step'] ?? ''));
    $artifacts = [];

    switch ($kind) {
        case 'experiment':
            atelier_add_artifact($artifacts, 'input', 'prompt', 'Girdi / prompt', atelier_artifact_title('Prompt veya komut', $tried), $tried);
            atelier_add_artifact($artifacts, 'output', 'output', 'Çıktı / yanıt', atelier_artifact_title('Alınan çıktı', $failed), $failed);
            atelier_add_artifact($artifacts, 'decision', 'decision', 'Teknik not', atelier_artifact_title('Bu denemeden çıkan not', $decision), $decision);
            atelier_add_artifact($artifacts, 'next', 'next', 'Sonraki kontrol', atelier_artifact_title('Sıradaki test', $next), $next);
            break;
        case 'problem':
            atelier_add_artifact($artifacts, 'repro', 'problem', 'Hata / belirti', atelier_artifact_title('Sorunun görüldüğü yer', $tried), $tried);
            atelier_add_artifact($artifacts, 'log', 'log', 'Log / kanıt', atelier_artifact_title('Kanıt', $failed), $failed);
            atelier_add_artifact($artifacts, 'fix', 'decision', 'Çözüm denemesi', atelier_artifact_title('Denenecek düzeltme', $decision), $decision);
            atelier_add_artifact($artifacts, 'next', 'next', 'Kontrol', atelier_artifact_title('Sonraki kontrol', $next), $next);
            break;
        case 'decision':
            atelier_add_artifact($artifacts, 'options', 'note', 'Seçenekler', atelier_artifact_title('Masadaki seçenekler', $tried), $tried);
            atelier_add_artifact($artifacts, 'evidence', 'evidence', 'Gerekçe / kanıt', atelier_artifact_title('Kararı zorlayan kanıt', $failed), $failed);
            atelier_add_artifact($artifacts, 'decision', 'decision', 'Net karar', atelier_artifact_title('Alınan karar', $decision), $decision);
            atelier_add_artifact($artifacts, 'next', 'next', 'Uygulama', atelier_artifact_title('Uygulanacak iş', $next), $next);
            break;
        case 'media':
            atelier_add_artifact($artifacts, 'source', 'media', 'Medya / kaynak', atelier_artifact_title('Medya kaynağı', $tried), $tried);
            atelier_add_artifact($artifacts, 'observation', 'evidence', 'Gözlem', atelier_artifact_title('Görülen çıktı', $failed), $failed);
            atelier_add_artifact($artifacts, 'decision', 'decision', 'Kullanım notu', atelier_artifact_title('Kullanım kararı', $decision), $decision);
            atelier_add_artifact($artifacts, 'next', 'next', 'Sonraki çıktı', atelier_artifact_title('Sonraki medya', $next), $next);
            break;
        case 'source':
            atelier_add_artifact($artifacts, 'source', 'source', 'Kaynak / bağlantı', atelier_artifact_title('Kaynak', $tried), $tried);
            atelier_add_artifact($artifacts, 'finding', 'evidence', 'Kaynağın gösterdiği', atelier_artifact_title('Kaynak notu', $failed), $failed);
            atelier_add_artifact($artifacts, 'decision', 'decision', 'Not / karar', atelier_artifact_title('Alınan not', $decision), $decision);
            atelier_add_artifact($artifacts, 'next', 'next', 'Sonraki kaynak kontrolü', atelier_artifact_title('Sonraki kaynak kontrolü', $next), $next);
            break;
        default:
            atelier_add_artifact($artifacts, 'note', 'field-note', 'Saha / çalışma notu', atelier_artifact_title('Ham not', $tried), $tried);
            atelier_add_artifact($artifacts, 'context', 'evidence', 'Gözlem', atelier_artifact_title('Dikkat çeken şey', $failed), $failed);
            atelier_add_artifact($artifacts, 'decision', 'decision', 'Not / karar', atelier_artifact_title('Kayıttan çıkan not', $decision), $decision);
            atelier_add_artifact($artifacts, 'next', 'next', 'Sıradaki iş', atelier_artifact_title('Sıradaki iş', $next), $next);
            break;
    }

    return $artifacts;
}

function atelier_entry_kind(array $update): string
{
    $kind = (string)($update['entry_kind'] ?? '');
    if (array_key_exists($kind, atelier_entry_kind_options())) {
        return $kind;
    }
    if (!empty($update['is_milestone'])) {
        return 'decision';
    }
    if (trim((string)($update['failed'] ?? '')) !== '') {
        return 'problem';
    }
    if (trim((string)($update['tried'] ?? '')) !== '') {
        return 'experiment';
    }
    return 'journal';
}

function atelier_entry_kind_config(array|string $updateOrKind): array
{
    $options = atelier_entry_kind_options();
    $kind = is_array($updateOrKind) ? atelier_entry_kind($updateOrKind) : (string)$updateOrKind;
    return $options[$kind] ?? $options['journal'];
}

function atelier_entry_kind_is_valid(string $kind): bool
{
    return array_key_exists($kind, atelier_entry_kind_options());
}

function atelier_story_role_options(): array
{
    return [
        'auto' => ['label' => 'Otomatik belirle', 'help' => 'Sistem kayıt türüne göre en güvenli rolü seçer.'],
        'opening' => ['label' => 'Açılış sahnesi', 'help' => 'Hikâyenin neden başladığını kuran ilk güçlü sahne.'],
        'problem' => ['label' => 'Sorun / çatışma', 'help' => 'Projeyi zorlayan hata, eksik veya gerilim noktası.'],
        'experiment' => ['label' => 'Deneme', 'help' => 'Bir fikri, yöntemi veya aracı sınayan çalışma anı.'],
        'decision' => ['label' => 'Karar / dönüm noktası', 'help' => 'Yön değiştiren veya projeyi başka yere taşıyan karar.'],
        'media' => ['label' => 'Görsel kanıt', 'help' => 'Görsel, video, ses veya ekran çıktısı üzerinden anlatılan kanıt.'],
        'source' => ['label' => 'Kaynak / bağlantı', 'help' => 'Dış bağlantı, referans, demo veya kaynak notu.'],
        'lesson' => ['label' => 'Ders / çıkarım', 'help' => 'Okura aktarılacak öğrenme, kural veya sonuç.'],
        'status' => ['label' => 'Bugünkü durum', 'help' => 'Projenin şu anda nerede durduğunu anlatır.'],
        'closing' => ['label' => 'Kapanış', 'help' => 'Atölyeden hikâyeye geçerken dosyayı bağlayan son bölüm.'],
    ];
}

function atelier_story_section_type_options(): array
{
    return [
        'auto' => ['label' => 'Otomatik güvenli', 'help' => 'Rol ve kayıt türünden en uygun hikâye bölümünü seçer.'],
        'opening' => ['label' => 'Açılış / büyük giriş', 'help' => 'Kısa, güçlü giriş bölümü.'],
        'text' => ['label' => 'Metin bölümü', 'help' => 'Anlatı için en güvenli düz bölüm.'],
        'split' => ['label' => 'Metin + medya', 'help' => 'Tek ana medya ile açıklama birlikte okunur.'],
        'timeline' => ['label' => 'Zaman çizgisi', 'help' => 'Adım, kayıt veya süreç akışı.'],
        'questions' => ['label' => 'Soru / cevap', 'help' => 'Sorun, merak veya teknik soru açıklaması.'],
        'compare' => ['label' => 'Karşılaştırma', 'help' => 'Önce/sonra veya iki yaklaşım karşılaştırması.'],
        'roles' => ['label' => 'YZ / insan ayrımı', 'help' => 'YZ ve insan katkısı ayrı okunacaksa.'],
        'status' => ['label' => 'Güncel durum', 'help' => 'Durum kartları veya proje özeti.'],
        'lesson' => ['label' => 'Ders / çıkarım listesi', 'help' => 'Öğrenilenleri maddeler halinde gösterir.'],
        'gallery' => ['label' => 'Galeri', 'help' => 'Birden fazla medya ana içerik olduğunda.'],
        'video' => ['label' => 'Video / medya odaklı', 'help' => 'Video veya medya bölümü ana sahneyse.'],
        'code' => ['label' => 'Kod / terminal', 'help' => 'Kod, komut veya terminal çıktısı ana içerikse.'],
    ];
}

function atelier_story_layout_options(): array
{
    return [
        'auto' => ['label' => 'Otomatik güvenli', 'help' => 'İçeriğe göre sade ve bozulmayan yerleşim seçilir.'],
        'default' => ['label' => 'Dengeli akış', 'help' => 'Çoğu bölüm için güvenli varsayılan.'],
        'wide' => ['label' => 'Geniş okuma', 'help' => 'Uzun metin veya geniş medya için.'],
        'hero-split' => ['label' => 'Büyük iki kolon', 'help' => 'Kısa başlık ve güçlü tek medya varsa.'],
        'full-bleed' => ['label' => 'Tam geniş vurgu', 'help' => 'Özel görsel/vurgu bölümü için.'],
        'offset' => ['label' => 'Kaydırılmış ritim', 'help' => 'Akışı hafif kıran görsel düzen.'],
        'cross' => ['label' => 'Çapraz karşılaştırma', 'help' => 'Karşılaştırma ve rol ayrımı için.'],
        'diagonal' => ['label' => 'Diyagonal vurgu', 'help' => 'Kısa ve vurucu bölümler için.'],
    ];
}

function atelier_story_role_is_valid(string $role): bool
{
    return array_key_exists($role, atelier_story_role_options());
}

function atelier_story_section_type_is_valid(string $type): bool
{
    return array_key_exists($type, atelier_story_section_type_options());
}

function atelier_story_layout_is_valid(string $layout): bool
{
    return array_key_exists($layout, atelier_story_layout_options());
}

function atelier_default_story_role(array $update): string
{
    $role = (string)($update['story_role'] ?? '');
    if ($role !== '' && $role !== 'auto' && atelier_story_role_is_valid($role)) return $role;

    return match (atelier_entry_kind($update)) {
        'problem' => 'problem',
        'experiment' => 'experiment',
        'decision' => 'decision',
        'media' => 'media',
        'source' => 'source',
        default => !empty($update['is_milestone']) ? 'decision' : 'experiment',
    };
}

function atelier_default_story_section_type(array $update): string
{
    $type = (string)($update['story_section_type'] ?? '');
    if ($type !== '' && $type !== 'auto' && atelier_story_section_type_is_valid($type)) return $type;

    return match (atelier_default_story_role($update)) {
        'opening' => 'opening',
        'problem', 'source' => 'questions',
        'decision', 'lesson' => 'lesson',
        'media' => 'split',
        'status' => 'status',
        'closing' => 'text',
        default => 'timeline',
    };
}

function atelier_default_story_layout(array $update): string
{
    $layout = (string)($update['story_layout'] ?? '');
    if ($layout !== '' && $layout !== 'auto' && atelier_story_layout_is_valid($layout)) return $layout;

    $type = atelier_default_story_section_type($update);
    if (in_array($type, ['timeline', 'questions', 'compare', 'status', 'lesson', 'code'], true)) {
        return 'default';
    }
    if ($type === 'opening') return 'wide';
    return 'default';
}

function atelier_story_label(array $update): string
{
    $label = trim((string)($update['story_label'] ?? ''));
    if ($label !== '') return $label;

    $role = atelier_default_story_role($update);
    $roles = atelier_story_role_options();
    return $roles[$role]['label'] ?? atelier_entry_kind_config($update)['story_label'];
}

function atelier_story_bridge(array $update): array
{
    $role = atelier_default_story_role($update);
    $type = atelier_default_story_section_type($update);
    $layout = atelier_default_story_layout($update);
    $roles = atelier_story_role_options();
    $types = atelier_story_section_type_options();
    $layouts = atelier_story_layout_options();

    return [
        'role' => $role,
        'role_label' => $roles[$role]['label'] ?? $role,
        'role_help' => $roles[$role]['help'] ?? '',
        'type' => $type,
        'type_label' => $types[$type]['label'] ?? $type,
        'type_help' => $types[$type]['help'] ?? '',
        'layout' => $layout,
        'layout_label' => $layouts[$layout]['label'] ?? $layout,
        'layout_help' => $layouts[$layout]['help'] ?? '',
        'reader_label' => atelier_story_label($update),
    ];
}
