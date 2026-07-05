<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
admin_require_login();

$siteData = load_site();
$projects = load_projects(true);
$activeWorkshops = array_filter($projects, static fn(array $p): bool => workshop_is_active($p));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_require_csrf();
    try {
        $pinned = safe_slug((string) ($_POST['pinned_atelier'] ?? ''));
        if ($pinned !== '' && (!isset($activeWorkshops[$pinned]) || !workshop_is_active($activeWorkshops[$pinned]))) {
            throw new RuntimeException('Sabitlemek istediğin proje açık bir Atölye değil.');
        }
        $siteData['homepage']['pinned_atelier'] = $pinned;
        $siteData['homepage']['recent_updates_limit'] = max(1, min(12, (int) ($_POST['recent_updates_limit'] ?? 4)));
        $siteData['atelier_widget'] = array_replace(is_array($siteData['atelier_widget'] ?? null) ? $siteData['atelier_widget'] : [], [
            'enabled' => isset($_POST['widget_enabled']),
            'floating' => isset($_POST['widget_floating']),
            'auto_open' => isset($_POST['widget_auto_open']),
        ]);
        $siteData['site']['title'] = trim((string) ($_POST['site_title'] ?? $siteData['site']['title'] ?? ''));
        $siteData['site']['description'] = trim((string) ($_POST['site_description'] ?? $siteData['site']['description'] ?? ''));
        save_site($siteData);
        admin_flash('success', 'Site ve Atölye ayarları kaydedildi.');
        admin_redirect('settings.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

admin_layout_start('Ayarlar', 'settings');
?>
<section class="admin-hero"><div><p class="admin-eyebrow">SİTE DAVRANIŞI</p><h1>Atölye ve ana sayfa</h1><p class="admin-muted">Açık Atölyeler arasından hangisinin sayfa boyunca erişilebilir olacağını buradan seç.</p></div></section>
<?php if ($error !== ''): ?><div class="admin-flash admin-flash--error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="admin-form admin-card">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="admin-field admin-field--full"><label for="site_title">Site başlığı</label><input id="site_title" name="site_title" value="<?= e($siteData['site']['title'] ?? '') ?>"></div>
    <div class="admin-field admin-field--full"><label for="site_description">Site açıklaması</label><textarea id="site_description" name="site_description"><?= e($siteData['site']['description'] ?? '') ?></textarea></div>
    <div class="admin-field"><label for="pinned_atelier">Sabitlenen açık Atölye</label><select id="pinned_atelier" name="pinned_atelier"><option value="">Hiçbiri</option><?php foreach ($activeWorkshops as $slug => $project): ?><option value="<?= e($slug) ?>" <?= safe_slug((string)($siteData['homepage']['pinned_atelier'] ?? ''))===$slug?'selected':'' ?>><?= e($project['title'] ?? $slug) ?></option><?php endforeach; ?></select></div>
    <div class="admin-field"><label for="recent_updates_limit">Ana sayfadaki son hareket sayısı</label><input id="recent_updates_limit" name="recent_updates_limit" type="number" min="1" max="12" value="<?= e((string)($siteData['homepage']['recent_updates_limit'] ?? 4)) ?>"></div>
    <div class="admin-field admin-field--full"><label class="admin-check"><input type="checkbox" name="widget_enabled" <?= !empty($siteData['atelier_widget']['enabled'])?'checked':'' ?>> Atölye penceresi etkin</label><label class="admin-check"><input type="checkbox" name="widget_floating" <?= !empty($siteData['atelier_widget']['floating'])?'checked':'' ?>> Sayfa köşesinde sabit düğme göster</label><label class="admin-check"><input type="checkbox" name="widget_auto_open" <?= !empty($siteData['atelier_widget']['auto_open'])?'checked':'' ?>> İlk ziyarette otomatik aç <small>(şimdilik tavsiye edilmez)</small></label></div>
    <div class="admin-form-actions"><button class="admin-button admin-button--primary" type="submit">Ayarları kaydet</button></div>
</form>

<section class="admin-section admin-card"><p class="admin-eyebrow">DOSYA YAZMA KONTROLÜ</p><h2>Sunucuya taşırken</h2><p class="admin-muted">PHP kullanıcısının <code>content/stories</code> ve <code>data</code> klasörlerine yazabilmesi gerekir. Yerelde XAMPP genellikle hazırdır. Canlı sunucuda 775 veya sağlayıcının önerdiği izinleri kullan.</p></section>
<?php admin_layout_end(); ?>
