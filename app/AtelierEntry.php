<?php
declare(strict_types=1);

function atelier_entry_kind_options(): array
{
    return [
        'journal' => [
            'label' => 'Günlük not',
            'short' => 'Not',
            'story_label' => 'ÇALIŞMA NOTU',
            'item_type' => 'timeline',
            'help' => 'Kısa bir ilerleme, gözlem veya gün sonu notu.',
            'seed' => 'Hikâyede bağlam veya ara geçiş olarak kullanılabilir.',
            'summary_prompt' => 'Bu notun tek cümlelik anlamı ne?',
            'tried_prompt' => 'Bugün neyle uğraştın?',
            'failed_prompt' => 'Nerede takıldın veya ne eksik kaldı?',
            'decision_prompt' => 'Bu nottan çıkan küçük karar ne?',
            'next_prompt' => 'Bir sonraki iz ne?',
        ],
        'experiment' => [
            'label' => 'Deneme / test',
            'short' => 'Deneme',
            'story_label' => 'DENEME',
            'item_type' => 'timeline',
            'help' => 'Bir fikri, aracı veya yöntemi denediğin kayıt.',
            'seed' => 'Hikâyede “ne denedim ve ne öğrendim?” sahnesine dönüşür.',
            'summary_prompt' => 'Bu denemenin amacı neydi?',
            'tried_prompt' => 'Neyi, nasıl denedin?',
            'failed_prompt' => 'Denemede ne çalışmadı veya ne şaşırttı?',
            'decision_prompt' => 'Denemeden sonra hangi kararı aldın?',
            'next_prompt' => 'Bu denemenin devamında ne yapılacak?',
        ],
        'problem' => [
            'label' => 'Sorun / hata',
            'short' => 'Sorun',
            'story_label' => 'SÜRTÜŞME',
            'item_type' => 'question',
            'help' => 'Bir engel, hata, kırılma veya kararsızlık noktası.',
            'seed' => 'Hikâyede gerilim ve neden-sonuç bağını kurar.',
            'summary_prompt' => 'Sorun tek cümlede ne?',
            'tried_prompt' => 'Sorunu nerede fark ettin?',
            'failed_prompt' => 'Ne bozuldu, ne beklediğin gibi olmadı?',
            'decision_prompt' => 'Bu soruna karşı hangi yön değişti?',
            'next_prompt' => 'Sorunu çözmek için sıradaki hamle ne?',
        ],
        'decision' => [
            'label' => 'Karar / yön değişimi',
            'short' => 'Karar',
            'story_label' => 'DÖNÜM NOKTASI',
            'item_type' => 'lesson',
            'help' => 'Projeyi başka yöne taşıyan karar veya fark ediş.',
            'seed' => 'Hikâyede dönüm noktası veya ders olarak kullanılabilir.',
            'summary_prompt' => 'Kararın özü ne?',
            'tried_prompt' => 'Bu karara gelmeden önce ne denendi?',
            'failed_prompt' => 'Eski yol neden yetmedi?',
            'decision_prompt' => 'Yeni karar ne?',
            'next_prompt' => 'Bu karar projeyi nereye götürecek?',
        ],
        'media' => [
            'label' => 'Medya / görsel inceleme',
            'short' => 'Medya',
            'story_label' => 'GÖRSEL KANIT',
            'item_type' => 'timeline',
            'help' => 'Görsel, video, ses veya ekran görüntüsü üzerinden anlatılan kayıt.',
            'seed' => 'Hikâyede kanıt, örnek veya sahne görseli olarak çalışır.',
            'summary_prompt' => 'Bu medya neyi gösteriyor?',
            'tried_prompt' => 'Bu görsel/video hangi denemeden çıktı?',
            'failed_prompt' => 'Görüntüde/seste ne eksik veya sorunlu?',
            'decision_prompt' => 'Bu medyaya bakınca neye karar verdin?',
            'next_prompt' => 'Bir sonraki medya/çıktı ne olacak?',
        ],
        'source' => [
            'label' => 'Bağlantı / kaynak notu',
            'short' => 'Kaynak',
            'story_label' => 'KAYNAK',
            'item_type' => 'question',
            'help' => 'Dış bağlantı, kaynak, demo, referans veya okuma notu.',
            'seed' => 'Hikâyede dayanak veya bağlantı notu olarak kullanılabilir.',
            'summary_prompt' => 'Bu kaynak neden eklendi?',
            'tried_prompt' => 'Kaynağı nerede kullandın?',
            'failed_prompt' => 'Kaynak neyi açıklamadı veya eksik bıraktı?',
            'decision_prompt' => 'Bu kaynaktan sonra neye karar verdin?',
            'next_prompt' => 'Bu kaynakla ilgili sıradaki adım ne?',
        ],
    ];
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
        'offset' => ['label' => 'Kaydırılmış ritim', 'help' => 'Akışı hafif kıran görsel düzen.'],
        'cross' => ['label' => 'Çapraz karşılaştırma', 'help' => 'Karşılaştırma ve rol ayrımı için.'],
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
