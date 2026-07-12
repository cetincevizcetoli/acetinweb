<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admin_require_login();

$site = setting('site', []);
$hero = setting('hero', []);
$manifesto = setting('manifesto', []);
$origin = setting('origin', []);
$widget = setting('atelier_widget', []);
$channels = db()->query('SELECT * FROM channels ORDER BY sort_order,id')->fetchAll();
$error = '';

if (is_post()) {
    verify_csrf();

    try {
        save_setting('site', [
            'title' => trim(old('site_title')),
            'description' => trim(old('site_description')),
            'year' => trim(old('site_year')) ?: date('Y'),
        ]);

        save_setting('hero', [
            'eyebrow' => trim(old('hero_eyebrow')),
            'lead' => trim(old('hero_lead')),
            'body' => trim(old('hero_body')),
            'primary_label' => trim(old('hero_primary')),
            'secondary_label' => trim(old('hero_secondary')),
        ]);

        save_setting('manifesto', [
            'label' => trim(old('manifesto_label')),
            'title' => trim(old('manifesto_title')),
            'paragraphs' => array_values(array_filter(array_map('trim', preg_split('/\R{2,}/u', old('manifesto_paragraphs')) ?: []))),
            'short' => trim(old('manifesto_short')),
            'words' => array_values(array_filter(array_map('trim', explode(',', old('manifesto_words'))))),
        ]);

        save_setting('origin', [
            'label' => trim(old('origin_label')),
            'title' => trim(old('origin_title')),
            'mentor_title' => trim(old('mentor_title')),
            'mentor_text' => trim(old('mentor_text')),
            'mentor_url' => trim(old('mentor_url')),
            'project_title' => trim(old('origin_project_title')),
            'project_text' => trim(old('origin_project_text')),
        ]);

        save_setting('atelier_widget', [
            'enabled' => checkbox('widget_enabled') === 1,
            'status' => 'open',
            'floating' => true,
            'auto_open' => checkbox('widget_auto_open') === 1,
        ]);

        save_setting('recent_updates_limit', (int)($_POST['recent_updates_limit'] ?? 4));

        foreach ($_POST['channels'] ?? [] as $id => $ch) {
            $st = db()->prepare('UPDATE channels SET title=?,url=?,is_active=?,sort_order=? WHERE id=?');
            $st->execute([
                trim((string)($ch['title'] ?? '')),
                safe_external_url((string)($ch['url'] ?? '')),
                !empty($ch['active']) ? 1 : 0,
                (int)($ch['order'] ?? 0),
                (int)$id,
            ]);
        }

        flash('success', 'Site ayarları kaydedildi.');
        redirect('settings.php');
    } catch (Throwable $e) {
        $error = admin_error_message($e, 'admin.settings');
    }
}

$manifestoText = implode("\n\n", $manifesto['paragraphs'] ?? []);
$manifestoWords = implode(', ', $manifesto['words'] ?? []);

admin_head('Ayarlar');
?>

<div class="page-head settings-head">
    <div>
        <p class="eyebrow">SİTE AYARLARI</p>
        <h1>Metinler, pencere ve kanallar</h1>
        <p>Bu sayfa site genelindeki sabit metinleri ve küçük davranış ayarlarını yönetir. Proje yayını, hikâye sırası ve medya işleri kendi ekranlarında kalır.</p>
    </div>
    <div class="settings-summary">
        <span>Bu sayfa neyi değiştirir?</span>
        <strong>Ana sayfa metinleri ve bağlantılar</strong>
        <small>Hero görsel sistemi, proje listeleri ve hikâye içerikleri buradan yönetilmez.</small>
    </div>
</div>

<?php if ($error): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" class="settings-form">
    <?= csrf_field() ?>

    <section class="settings-section">
        <div class="settings-section-title">
            <p class="eyebrow">GENEL</p>
            <h2>Site kimliği</h2>
            <p>Tarayıcı başlığı, açıklama ve yıl bilgisi. İçerik kartlarını veya hikâyeleri etkilemez.</p>
        </div>
        <div class="panel settings-panel">
            <div class="form-grid">
                <div class="field full">
                    <label>Site başlığı</label>
                    <input name="site_title" value="<?= e($site['title'] ?? '') ?>">
                </div>
                <div class="field full">
                    <label>Site açıklaması</label>
                    <textarea name="site_description"><?= e($site['description'] ?? '') ?></textarea>
                </div>
                <div class="field">
                    <label>Yıl</label>
                    <input name="site_year" value="<?= e((string)($site['year'] ?? date('Y'))) ?>">
                </div>
            </div>
        </div>
    </section>

    <section class="settings-section">
        <div class="settings-section-title">
            <p class="eyebrow">ANA SAYFA</p>
            <h2>Hero metinleri</h2>
            <p>Hero görsel ve animasyon sistemi kod tarafında kalır. Burada yalnızca metin ve düğme adları değişir.</p>
        </div>
        <div class="panel settings-panel">
            <div class="form-grid">
                <div class="field full">
                    <label>Üst etiket</label>
                    <input name="hero_eyebrow" value="<?= e($hero['eyebrow'] ?? '') ?>">
                </div>
                <div class="field full">
                    <label>Ana cümle</label>
                    <textarea name="hero_lead"><?= e($hero['lead'] ?? '') ?></textarea>
                </div>
                <div class="field full">
                    <label>Açıklama</label>
                    <textarea name="hero_body"><?= e($hero['body'] ?? '') ?></textarea>
                </div>
                <div class="field">
                    <label>Birinci düğme</label>
                    <input name="hero_primary" value="<?= e($hero['primary_label'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>İkinci düğme</label>
                    <input name="hero_secondary" value="<?= e($hero['secondary_label'] ?? '') ?>">
                </div>
            </div>
        </div>
    </section>

    <section class="settings-section">
        <div class="settings-section-title">
            <p class="eyebrow">ANA SAYFA</p>
            <h2>Manifesto</h2>
            <p>#FikrimVar’ın “bu site ne?” cevabını veren bölüm. Uzun metni boş satırlarla parçalara ayır.</p>
        </div>
        <div class="panel settings-panel">
            <div class="form-grid">
                <div class="field">
                    <label>Etiket</label>
                    <input name="manifesto_label" value="<?= e($manifesto['label'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Başlık</label>
                    <input name="manifesto_title" value="<?= e($manifesto['title'] ?? '') ?>">
                </div>
                <div class="field full">
                    <label>Paragraflar</label>
                    <textarea rows="10" name="manifesto_paragraphs"><?= e($manifestoText) ?></textarea>
                    <small>Paragrafları boş satırla ayır.</small>
                </div>
                <div class="field full">
                    <label>Kısa hâli</label>
                    <textarea name="manifesto_short"><?= e($manifesto['short'] ?? '') ?></textarea>
                </div>
                <div class="field full">
                    <label>Ana sözcükler</label>
                    <input name="manifesto_words" value="<?= e($manifestoWords) ?>">
                    <small>Virgülle ayır: kod, görsel, deneme gibi.</small>
                </div>
            </div>
        </div>
    </section>

    <section class="settings-section">
        <div class="settings-section-title">
            <p class="eyebrow">ANA SAYFA</p>
            <h2>Başlangıç hikâyesi</h2>
            <p>Mehmet Fırat hocaya ve ilk gerçek proje deneyimine ayrılan sabit anlatı alanı.</p>
        </div>
        <div class="panel settings-panel">
            <div class="form-grid">
                <div class="field">
                    <label>Etiket</label>
                    <input name="origin_label" value="<?= e($origin['label'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Başlık</label>
                    <input name="origin_title" value="<?= e($origin['title'] ?? '') ?>">
                </div>
                <div class="field full">
                    <label>Mehmet Fırat başlığı</label>
                    <input name="mentor_title" value="<?= e($origin['mentor_title'] ?? '') ?>">
                </div>
                <div class="field full">
                    <label>Mehmet Fırat metni</label>
                    <textarea name="mentor_text"><?= e($origin['mentor_text'] ?? '') ?></textarea>
                </div>
                <div class="field full">
                    <label>Akademik profil bağlantısı</label>
                    <input name="mentor_url" value="<?= e($origin['mentor_url'] ?? '') ?>" placeholder="https://">
                </div>
                <div class="field full">
                    <label>İlk proje başlığı</label>
                    <input name="origin_project_title" value="<?= e($origin['project_title'] ?? '') ?>">
                </div>
                <div class="field full">
                    <label>İlk proje metni</label>
                    <textarea name="origin_project_text"><?= e($origin['project_text'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </section>

    <section class="settings-section">
        <div class="settings-section-title">
            <p class="eyebrow">KÜÇÜK DAVRANIŞLAR</p>
            <h2>Atölye penceresi</h2>
            <p>Sağ alttaki Atölye panelinin genel davranışı. Hangi projenin görüneceği proje yönetimi ve yayın/sıra ekranından belirlenir.</p>
        </div>
        <div class="panel settings-panel">
            <div class="check-row">
                <label class="check"><input type="checkbox" name="widget_enabled" <?= ($widget['enabled'] ?? true) ? 'checked' : '' ?>> Atölye penceresi etkin</label>
                <label class="check"><input type="checkbox" name="widget_auto_open" <?= ($widget['auto_open'] ?? false) ? 'checked' : '' ?>> Sayfa açıldığında otomatik aç</label>
            </div>
            <div class="field settings-narrow">
                <label>Son hareket sayısı</label>
                <input type="number" min="1" max="20" name="recent_updates_limit" value="<?= (int)setting('recent_updates_limit', 4) ?>">
                <small>Atölye penceresinde kaç son hareketin gösterileceğini belirler.</small>
            </div>
        </div>
    </section>

    <section class="settings-section">
        <div class="settings-section-title">
            <p class="eyebrow">BAĞLANTILAR</p>
            <h2>Kanallar</h2>
            <p>Site içinde gösterilen dış kanal bağlantıları. URL boş veya geçersizse kayıt sırasında güvenli biçimde temizlenir.</p>
        </div>
        <div class="panel settings-panel">
            <div class="settings-channel-list">
                <?php foreach ($channels as $ch): ?>
                    <div class="settings-channel-row">
                        <div class="field">
                            <label>Kanal adı</label>
                            <input name="channels[<?= (int)$ch['id'] ?>][title]" value="<?= e($ch['title']) ?>">
                        </div>
                        <div class="field">
                            <label>URL</label>
                            <input name="channels[<?= (int)$ch['id'] ?>][url]" value="<?= e($ch['url']) ?>" placeholder="https://">
                        </div>
                        <div class="field">
                            <label>Sıra</label>
                            <input type="number" name="channels[<?= (int)$ch['id'] ?>][order]" value="<?= (int)$ch['sort_order'] ?>">
                        </div>
                        <label class="check settings-channel-active">
                            <input type="checkbox" name="channels[<?= (int)$ch['id'] ?>][active]" <?= $ch['is_active'] ? 'checked' : '' ?>>
                            Aktif
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <div class="form-actions settings-actions">
        <button class="accent" type="submit">Ayarları kaydet</button>
    </div>
</form>

<?php admin_foot(); ?>
