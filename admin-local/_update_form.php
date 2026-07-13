<?php
declare(strict_types=1);

function update_story_readiness(array $update): array
{
    $kind = atelier_entry_kind_config($update);
    $checks = [
        'summary' => ['label' => 'Kısa özet', 'help' => $kind['summary_prompt']],
        'tried' => ['label' => 'Deneme / sahne', 'help' => $kind['tried_prompt']],
        'failed' => ['label' => 'Sorun / sürtüşme', 'help' => $kind['failed_prompt']],
        'decision' => ['label' => 'Karar / yön değişimi', 'help' => $kind['decision_prompt']],
        'next_step' => ['label' => 'Sonraki iz', 'help' => $kind['next_prompt']],
    ];

    foreach ($checks as $key => $check) {
        $checks[$key]['ok'] = trim((string)($update[$key] ?? '')) !== '';
    }

    return $checks;
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
    $readyCount = count(array_filter($readiness, static fn(array $row): bool => (bool)$row['ok']));
    ?>
    <div class="grid grid-2">
        <section class="panel">
            <h2>Atölye kaydı</h2>
            <p class="help">Atölye ham çalışma masasıdır. Buradaki kayıtlar daha sonra hikâyede sahne, sorun, karar, kaynak veya görsel kanıt olarak kullanılabilir.</p>
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
                    <small>Bu kayıt hikâyeye taşınırsa okur onu hangi işlevle görsün?</small>
                </div>
                <div class="field">
                    <label>Önerilen bölüm tipi</label>
                    <select name="story_section_type">
                        <?php foreach (atelier_story_section_type_options() as $type => $option): ?>
                            <option value="<?= e($type) ?>" <?= $currentStoryType === $type ? 'selected' : '' ?>><?= e($option['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Boş/otomatik kalırsa kayıt türü ve rolünden seçilir.</small>
                </div>
                <div class="field">
                    <label>Önerilen yerleşim</label>
                    <select name="story_layout">
                        <?php foreach (atelier_story_layout_options() as $layout => $option): ?>
                            <option value="<?= e($layout) ?>" <?= $currentStoryLayout === $layout ? 'selected' : '' ?>><?= e($option['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Hikâye bölümünün sayfadaki ilk yerleşim önerisi.</small>
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
                    <input name="title" value="<?= $v('title') ?>" required placeholder="Bu kayıtta ne oldu?">
                    <small>Hikâyeye taşınırsa bölüm satırının görünen başlığı olur.</small>
                </div>
                <div class="field full">
                    <label>Kısa özet</label>
                    <textarea name="summary" placeholder="<?= e($kindConfig['summary_prompt']) ?>"><?= $v('summary') ?></textarea>
                </div>
                <div class="field full">
                    <label>Deneme / sahne</label>
                    <textarea name="tried" placeholder="<?= e($kindConfig['tried_prompt']) ?>"><?= $v('tried') ?></textarea>
                </div>
                <div class="field full">
                    <label>Sorun / çalışmayan taraf</label>
                    <textarea name="failed" placeholder="<?= e($kindConfig['failed_prompt']) ?>"><?= $v('failed') ?></textarea>
                </div>
                <div class="field full">
                    <label>Karar / yön değişimi</label>
                    <textarea name="decision" placeholder="<?= e($kindConfig['decision_prompt']) ?>"><?= $v('decision') ?></textarea>
                </div>
                <div class="field full">
                    <label>Sonraki iz</label>
                    <textarea name="next_step" placeholder="<?= e($kindConfig['next_prompt']) ?>"><?= $v('next_step') ?></textarea>
                </div>
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
        </section>

        <aside class="panel update-story-panel">
            <h2>Hikâyeye hazırlık</h2>
            <p>Bu kayıt hikâyeye seçilirse <strong><?= e($kindConfig['story_label']) ?></strong> olarak okunur. <?= e($kindConfig['seed']) ?></p>
            <div class="update-readiness">
                <?php foreach ($readiness as $row): ?>
                    <div class="<?= $row['ok'] ? 'is-ready' : '' ?>">
                        <span><?= $row['ok'] ? 'Hazır' : 'Eksik' ?></span>
                        <strong><?= e($row['label']) ?></strong>
                        <small><?= e($row['help']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="help">Dönüm noktası işaretlenen kayıtlar hikâye oluştururken otomatik seçilir. Her kayıt dönüm noktası olmak zorunda değil.</p>
            <div class="update-readiness-score">
                <strong><?= $readyCount ?>/<?= count($readiness) ?></strong>
                <span>hikâye malzemesi dolu</span>
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
    <?php
}
