<?php
declare(strict_types=1);

function update_story_readiness(array $update): array
{
    $kind = atelier_entry_kind_config($update);
    $workLabels = atelier_work_field_labels($update);
    $blocks = atelier_update_blocks($update);
    $checks = [
        'summary' => ['label' => 'Kısa iş özeti', 'help' => $kind['summary_prompt'], 'ok' => trim((string)($update['summary'] ?? '')) !== ''],
        'blocks' => ['label' => 'İş blokları', 'help' => 'Prompt, çıktı, hata, kanıt, karar veya kaynak bloklarından en az biri dolu olmalı.', 'ok' => $blocks !== []],
        'story_role' => ['label' => 'Hikâye karşılığı', 'help' => 'Bu kaydın hikâyede hangi role dönüşeceği belirlenmeli.', 'ok' => atelier_story_role_is_valid((string)($update['story_role'] ?? 'auto'))],
        'next_step' => ['label' => $workLabels['next_step'], 'help' => $kind['next_prompt'], 'ok' => trim((string)($update['next_step'] ?? '')) !== ''],
    ];

    return $checks;
}

function update_block_recipe(string $entryKind): array
{
    return match ($entryKind) {
        'experiment' => [
            ['type' => 'prompt', 'title' => 'Prompt / komut / girdi', 'help' => 'YZ’ye verilen prompt, çalıştırılan komut veya test girdisi.'],
            ['type' => 'output', 'title' => 'YZ cevabı / çıktı', 'help' => 'Alınan cevap, terminal çıktısı, ekran sonucu veya üretilen dosya.'],
            ['type' => 'error', 'title' => 'Hata / beklenmeyen sonuç', 'help' => 'Başarısızsa belirtiyi, başarılıysa kontrol edilen sınırı yaz.'],
            ['type' => 'decision', 'title' => 'Teknik karar', 'help' => 'Bu denemeden sonra ne değişti, ne sabit kaldı?'],
        ],
        'problem' => [
            ['type' => 'error', 'title' => 'Hata / belirti', 'help' => 'Hata mesajı, bozuk davranış veya ekranda görülen sorun.'],
            ['type' => 'evidence', 'title' => 'Kanıt / log / ekran', 'help' => 'Log, ekran görüntüsü, HTTP kodu veya tekrar üretme koşulu.'],
            ['type' => 'prompt', 'title' => 'Sorulan soru / denenen çözüm', 'help' => 'YZ’ye ne sordun, hangi çözümü denedin?'],
            ['type' => 'decision', 'title' => 'Sonuç / karar', 'help' => 'Sorun çözüldü mü, ertelendi mi, başka yola mı dönüldü?'],
        ],
        'decision' => [
            ['type' => 'field_note', 'title' => 'Masadaki seçenekler', 'help' => 'Hangi yollar vardı, neden karar gerekti?'],
            ['type' => 'evidence', 'title' => 'Kararı zorlayan kanıt', 'help' => 'Veri, ekran, kullanıcı yorumu, hata veya sınır.'],
            ['type' => 'decision', 'title' => 'Net karar', 'help' => 'Bundan sonra sistem nasıl davranacak?'],
            ['type' => 'next', 'title' => 'Uygulanacak iş', 'help' => 'Bu karardan sonra yapılacak somut adım.'],
        ],
        'media' => [
            ['type' => 'source', 'title' => 'Medya kaynağı / üretim promptu', 'help' => 'Görsel, video veya ses nasıl üretildi ya da nereden geldi?'],
            ['type' => 'observation', 'title' => 'Görülen sonuç', 'help' => 'Medya neyi kanıtlıyor, neresi güçlü veya zayıf?'],
            ['type' => 'decision', 'title' => 'Kullanım kararı', 'help' => 'Hikâyede kapak, kanıt, arşiv veya dış link olarak mı kullanılacak?'],
        ],
        'source' => [
            ['type' => 'source', 'title' => 'Kaynak / bağlantı', 'help' => 'URL, repo, demo, video veya referans.'],
            ['type' => 'observation', 'title' => 'Kaynağın söylediği', 'help' => 'Bu kaynak hangi bilgiyi, yöntemi veya sınırı gösterdi?'],
            ['type' => 'decision', 'title' => 'Kullanım notu', 'help' => 'Bu kaynak projede neyi değiştirdi?'],
        ],
        default => [
            ['type' => 'field_note', 'title' => 'Saha / çalışma notu', 'help' => 'Bugün görülen gerçek durum veya iş akışı.'],
            ['type' => 'observation', 'title' => 'Gözlem / eksik veri', 'help' => 'Neyi fark ettin, hangi bilgi eksik kaldı?'],
            ['type' => 'decision', 'title' => 'Kayıttan çıkan not', 'help' => 'Bu kayıttan sonra yön, kapsam veya öncelik değişti mi?'],
            ['type' => 'next', 'title' => 'Sıradaki iş', 'help' => 'Bir sonraki somut kontrol veya üretim adımı.'],
        ],
    };
}

function render_update_form(array $project, array $update = [], array $attached = [], array $links = []): void
{
    $isEdit = !empty($update['id']);
    $media = project_media_admin((int)$project['id']);
    $v = fn(string $key, string $default = ''): string => e((string)($update[$key] ?? $default));
    $currentKind = atelier_entry_kind($update);
    $kindConfig = atelier_entry_kind_config($currentKind);
    $currentStoryRole = (string)($update['story_role'] ?? 'auto');
    if (!atelier_story_role_is_valid($currentStoryRole)) $currentStoryRole = 'auto';
    $currentStoryType = (string)($update['story_section_type'] ?? 'auto');
    if (!atelier_story_section_type_is_valid($currentStoryType)) $currentStoryType = 'auto';
    $currentStoryLayout = (string)($update['story_layout'] ?? 'auto');
    if (!atelier_story_layout_is_valid($currentStoryLayout)) $currentStoryLayout = 'auto';
    $readiness = update_story_readiness($update);
    $workLabels = atelier_work_field_labels($update);
    $readyCount = count(array_filter($readiness, static fn(array $row): bool => (bool)$row['ok']));
    $storyBridge = atelier_story_bridge($update);
    $blockRecipe = update_block_recipe($currentKind);
    $blockRows = is_post()
        ? UpdateBlockRepository::normalizeRows($_POST['blocks'] ?? [])
        : ($isEdit ? UpdateBlockRepository::forUpdate((int)$update['id']) : []);
    if ($blockRows === [] && $isEdit) {
        $blockRows = atelier_legacy_update_blocks($update);
    }
    if ($blockRows === [] && !$isEdit) {
        $blockRows = [
            ['block_type' => 'field_note', 'title' => '', 'body' => '', 'sort_order' => 1]
        ];
    }
    ?>
    <div class="grid grid-2">
        <section class="panel">
            <h2>Atölye kaydı</h2>
            <p class="help">Atölye ham çalışma masasıdır. Prompt, komut, kod parçası, çıktı, hata, medya ve bağlantı burada tutulur. Hikâye sonra bu kayıtlardan seçilerek kurulur.</p>
            <div class="form-grid">
                <div class="field full">
                    <label>Kayıt türü</label>
                    <select name="entry_kind">
                        <?php foreach (atelier_entry_kind_options() as $kind => $option): ?>
                            <option value="<?= e($kind) ?>" <?= $currentKind === $kind ? 'selected' : '' ?>><?= e($option['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small><?= e($kindConfig['help']) ?></small>
                </div>
                <div class="field">
                    <label>Hikâyedeki rolü</label>
                    <select name="story_role">
                        <?php foreach (atelier_story_role_options() as $role => $option): ?>
                            <option value="<?= e($role) ?>" <?= $currentStoryRole === $role ? 'selected' : '' ?>><?= e($option['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Bu Atölye kaydı hikâyeye taşınırsa okur onu hangi işlevle görecek?</small>
                </div>
                <div class="field">
                    <label>Hikâye bölüm tipi</label>
                    <select name="story_section_type">
                        <?php foreach (atelier_story_section_type_options() as $type => $option): ?>
                            <option value="<?= e($type) ?>" <?= $currentStoryType === $type ? 'selected' : '' ?>><?= e($option['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Hikâye editöründeki bölüm tipiyle aynı kavramdır. Otomatik kalırsa kayıt türü ve rolden seçilir.</small>
                </div>
                <div class="field">
                    <label>Hikâye yerleşimi</label>
                    <select name="story_layout">
                        <?php foreach (atelier_story_layout_options() as $layout => $option): ?>
                            <option value="<?= e($layout) ?>" <?= $currentStoryLayout === $layout ? 'selected' : '' ?>><?= e($option['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Hikâye editöründeki sayfa yerleşimiyle aynı kavramdır.</small>
                </div>
                <div class="field">
                    <label>Okur etiketi</label>
                    <input name="story_label" value="<?= $v('story_label') ?>" placeholder="Boş ise sistem belirler">
                    <small>Örn: İlk kırılma, Karar, Görsel kanıt, Ders.</small>
                </div>
                <div class="field">
                    <label>Çalışmanın tarihi</label>
                    <input type="date" name="work_date" value="<?= $v('work_date', date('Y-m-d')) ?>">
                </div>
                <div class="field">
                    <label>Görünen tarih / kısa etiket</label>
                    <input name="display_label" value="<?= $v('display_label') ?>" placeholder="Boş ise tarihten oluşur">
                </div>
                <div class="field full">
                    <label>Başlık</label>
                    <input name="title" value="<?= $v('title') ?>" required placeholder="Bu iş kaydının adı ne?">
                    <small>Kısa ve somut yaz: “Login hatası”, “Prompt 03 çıktısı”, “Kapak görseli denemesi”.</small>
                </div>
                <div class="field full">
                    <label>Kısa iş özeti</label>
                    <textarea name="summary" placeholder="<?= e($kindConfig['summary_prompt']) ?>"><?= $v('summary') ?></textarea>
                </div>
                <div class="field full">
                    <label>İş blokları</label>
                    <small>Hikâyeye gidecek gerçek malzeme burada tutulur. Prompt, YZ cevabı, kod, çıktı, hata, kanıt, medya notu ve kararı ayrı bloklara yaz.</small>
                    <div class="update-block-guide">
                        <strong>Bu kayıt türü için önerilen bloklar</strong>
                        <div>
                            <?php foreach ($blockRecipe as $recipe): ?>
                                <span>
                                    <b><?= e(UpdateBlockRepository::typeLabel($recipe['type'])) ?></b>
                                    <?= e($recipe['title']) ?>
                                    <small><?= e($recipe['help']) ?></small>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div id="update-blocks" class="repeat-list update-block-list">
                        <?php foreach ($blockRows as $i => $block): ?>
                            <div class="repeat-row update-block-row">
                                <input type="hidden" name="blocks[<?= $i ?>][sort_order]" value="<?= (int)($block['sort_order'] ?? ($i + 1)) ?>">
                                <select name="blocks[<?= $i ?>][block_type]">
                                    <?php foreach (UpdateBlockRepository::typeOptions() as $type => $label): ?>
                                        <option value="<?= e($type) ?>" <?= ($block['block_type'] ?? '') === $type ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input name="blocks[<?= $i ?>][title]" value="<?= e((string)($block['title'] ?? '')) ?>" placeholder="Örn: Prompt 02, hata çıktısı, karar notu">
                                <textarea name="blocks[<?= $i ?>][body]" placeholder="Gerçek içerik: prompt, YZ cevabı, komut, log, ekran notu, karar veya kaynak bağlantısı"><?= e((string)($block['body'] ?? '')) ?></textarea>
                                <button class="danger" type="button" data-repeat-remove>Sil</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="secondary" type="button" data-repeat-add="#update-blocks" data-template="#update-block-template">+ İş bloğu ekle</button>
                </div>
                <?php if (false): /* Eski alanlar artık yeni blok sistemine (update_blocks) otomatik taşındığı için kullanıcıyı yanıltmaması adına gizlendi. */ ?>
                <div class="field full legacy-work-fields">
                    <label>Eski kısa alanlar</label>
                    <p class="help">Bu alanlar eski kayıtlarla uyumluluk için duruyor. Yeni kayıtlarda asıl kaynak yukarıdaki iş bloklarıdır; burada yalnızca kısa özet veya hızlı not bırak.</p>
                </div>
                <div class="field full">
                    <label><?= e($workLabels['tried']) ?></label>
                    <textarea name="tried" placeholder="<?= e($kindConfig['tried_prompt']) ?>"><?= $v('tried') ?></textarea>
                </div>
                <div class="field full">
                    <label><?= e($workLabels['failed']) ?></label>
                    <textarea name="failed" placeholder="<?= e($kindConfig['failed_prompt']) ?>"><?= $v('failed') ?></textarea>
                </div>
                <div class="field full">
                    <label><?= e($workLabels['decision']) ?></label>
                    <textarea name="decision" placeholder="<?= e($kindConfig['decision_prompt']) ?>"><?= $v('decision') ?></textarea>
                </div>
                <div class="field full">
                    <label><?= e($workLabels['next_step']) ?></label>
                    <textarea name="next_step" placeholder="<?= e($kindConfig['next_prompt']) ?>"><?= $v('next_step') ?></textarea>
                </div>
                <?php endif; ?>
                <div class="field">
                    <label>Faz / dönem</label>
                    <input name="phase" value="<?= $v('phase', 'Genel') ?>" placeholder="Başlangıç, Denemeler, Yön değişikliği">
                    <small>Atölye sayfasında ham kayıtları gruplar.</small>
                </div>
                <div class="field">
                    <label>Slug</label>
                    <input name="slug" value="<?= $v('slug') ?>" placeholder="Boş ise başlıktan oluşur">
                </div>
            </div>
            <div class="story-bridge-box">
                <span>Hikâye karşılığı</span>
                <strong><?= e($storyBridge['reader_label']) ?> · <?= e($storyBridge['type_label']) ?></strong>
                <p><?= e($storyBridge['role_help']) ?></p>
                <dl>
                    <div><dt>Rol</dt><dd><?= e($storyBridge['role_label']) ?></dd></div>
                    <div><dt>Bölüm tipi</dt><dd><?= e($storyBridge['type_label']) ?></dd></div>
                    <div><dt>Yerleşim</dt><dd><?= e($storyBridge['layout_label']) ?></dd></div>
                </dl>
            </div>
        </section>

        <aside class="panel update-story-panel">
            <h2>Hikâyeye dönüşüm kontrolü</h2>
            <p>Bu kayıt hikâyeye seçilirse story-builder onu <strong><?= e($storyBridge['reader_label']) ?></strong> rolüyle, <strong><?= e($storyBridge['type_label']) ?></strong> bölüm tipinde ve <strong><?= e($storyBridge['layout_label']) ?></strong> yerleşiminde kullanır.</p>
            <div class="update-readiness">
                <?php foreach ($readiness as $row): ?>
                    <div class="<?= $row['ok'] ? 'is-ready' : '' ?>">
                        <span><?= $row['ok'] ? 'Hazır' : 'Eksik' ?></span>
                        <strong><?= e($row['label']) ?></strong>
                        <small><?= e($row['help']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="help">Dönüm noktası işaretlenen kayıtlar hikâye oluştururken otomatik seçilir. Her kayıt dönüm noktası olmak zorunda değil; ama seçilen her kayıt hikâye editöründeki aynı bölüm tipi ve yerleşim sistemine düşer.</p>
            <div class="update-readiness-score">
                <strong><?= $readyCount ?>/<?= count($readiness) ?></strong>
                <span>iş kaydı alanı dolu</span>
            </div>
        </aside>

        <aside class="panel">
            <h2>Yayın ve Atölye akışı</h2>
            <div class="field">
                <label>Durum</label>
                <select name="status">
                    <option value="draft" <?= ($update['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Taslak</option>
                    <option value="published" <?= ($update['status'] ?? '') === 'published' ? 'selected' : '' ?>>Yayımla</option>
                </select>
            </div>
            <div class="field">
                <label>Görünürlük</label>
                <select name="visibility">
                    <option value="private" <?= ($update['visibility'] ?? 'public') === 'private' ? 'selected' : '' ?>>Gizli</option>
                    <option value="unlisted" <?= ($update['visibility'] ?? '') === 'unlisted' ? 'selected' : '' ?>>Bağlantıya sahip olanlar</option>
                    <option value="public" <?= ($update['visibility'] ?? 'public') === 'public' ? 'selected' : '' ?>>Herkese açık</option>
                </select>
            </div>
            <div class="field">
                <label>Proje içi çalışma sırası</label>
                <input type="number" step="0.1" name="sort_order" value="<?= $v('sort_order') ?>" placeholder="Boş ise sistem sonraki sırayı verir">
                <small>Bu yayın sırası değil; yalnızca bu projenin Atölye kayıtları içindeki akıştır.</small>
            </div>
            <div class="check-row">
                <label class="check"><input type="checkbox" name="is_milestone" <?= !empty($update['is_milestone']) ? 'checked' : '' ?>> Dönüm noktası</label>
                <label class="check"><input type="checkbox" name="show_in_recent" <?= !isset($update['show_in_recent']) || $update['show_in_recent'] ? 'checked' : '' ?>> Son hareketlerde göster</label>
            </div>
        </aside>

        <section class="panel">
            <h2>Yeni medya yükle</h2>
            <div class="field">
                <label>Görsel, video, ses veya PDF</label>
                <input type="file" name="media_files[]" multiple accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,audio/mpeg,audio/wav,audio/ogg,application/pdf">
                <small>Bir kayda birden fazla dosya eklenebilir. Büyük video ve uzun ses dosyalarında bağlantı kullanmak daha sağlıklı olur.</small>
            </div>
        </section>

        <aside class="panel">
            <h2>Mevcut medyadan seç</h2>
            <div class="media-grid">
                <?php foreach ($media as $m): ?>
                    <label class="media-card">
                        <input type="checkbox" name="existing_media_ids[]" value="<?= (int)$m['id'] ?>">
                        <?php if ($m['media_type'] === 'image'): ?>
                            <img src="../<?= e($m['relative_path']) ?>" alt="">
                        <?php else: ?>
                            <div style="height:120px;display:grid;place-items:center;background:#090d11"><?= e(strtoupper($m['media_type'])) ?></div>
                        <?php endif; ?>
                        <small><?= e($m['original_name']) ?></small>
                    </label>
                <?php endforeach; ?>
            </div>
        </aside>

        <?php if ($isEdit && $attached): ?>
            <section class="panel">
                <h2>Bu kayda bağlı medya</h2>
                <div class="media-grid">
                    <?php foreach ($attached as $m): ?>
                        <label class="media-card">
                            <?php if ($m['media_type'] === 'image'): ?>
                                <img src="../<?= e($m['relative_path']) ?>" alt="">
                            <?php else: ?>
                                <div style="height:120px;display:grid;place-items:center;background:#090d11"><?= e(strtoupper($m['media_type'])) ?></div>
                            <?php endif; ?>
                            <small><?= e($m['original_name'] ?? $m['file_name']) ?></small>
                            <span class="check"><input type="checkbox" name="remove_media_ids[]" value="<?= (int)$m['media_id'] ?>"> Bağlantıyı kaldır</span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="panel">
            <h2>Bağlantılar</h2>
            <p>YouTube, Vimeo, SoundCloud, MP4/MP3, GitHub, çalışan demo veya başka bir kaynak. Başlık boş kalırsa sistem kaynağı tanıyıp anlamlı bir ad verir; desteklenen medya linkleri sitede player olarak görünür.</p>
            <div id="update-links" class="repeat-list">
                <?php foreach ($links as $i => $l): ?>
                    <div class="repeat-row">
                        <select name="links[<?= $i ?>][type]"><?php foreach (['youtube', 'vimeo', 'soundcloud', 'instagram', 'github', 'website', 'download', 'external'] as $t): ?><option <?= $l['link_type'] === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select>
                        <input name="links[<?= $i ?>][title]" value="<?= e($l['title']) ?>">
                        <input name="links[<?= $i ?>][url]" value="<?= e($l['url']) ?>">
                        <button class="danger" type="button" data-repeat-remove>Sil</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="secondary" type="button" data-repeat-add="#update-links" data-template="#update-link-template">+ Bağlantı ekle</button>
        </section>
    </div>
    <template id="update-link-template"><div class="repeat-row"><select name="links[__INDEX__][type]"><option>youtube</option><option>vimeo</option><option>soundcloud</option><option>instagram</option><option>github</option><option>website</option><option>download</option><option>external</option></select><input name="links[__INDEX__][title]" placeholder="Bağlantı başlığı"><input name="links[__INDEX__][url]" placeholder="https://"><button class="danger" type="button" data-repeat-remove>Sil</button></div></template>
    <template id="update-block-template">
        <div class="repeat-row update-block-row">
            <input type="hidden" name="blocks[__INDEX__][sort_order]" value="__INDEX__">
            <select name="blocks[__INDEX__][block_type]">
                <?php foreach (UpdateBlockRepository::typeOptions() as $type => $label): ?>
                    <option value="<?= e($type) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <input name="blocks[__INDEX__][title]" placeholder="Örn: Prompt 02, hata çıktısı, karar notu">
            <textarea name="blocks[__INDEX__][body]" placeholder="Gerçek içerik: prompt, YZ cevabı, komut, log, ekran notu, karar veya kaynak bağlantısı"></textarea>
            <button class="danger" type="button" data-repeat-remove>Sil</button>
        </div>
    </template>
    <?php
}
