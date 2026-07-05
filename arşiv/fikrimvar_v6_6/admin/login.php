<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
if (!admin_is_configured()) admin_redirect('setup.php');
if (admin_logged_in()) admin_redirect('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $error = 'Oturum doğrulaması başarısız.';
    } elseif (!admin_login((string) ($_POST['password'] ?? ''))) {
        usleep(350000);
        $error = 'Şifre doğru değil.';
    } else {
        admin_redirect('index.php');
    }
}
admin_layout_start('Giriş');
?>
<div class="admin-auth"><div class="admin-card">
    <p class="admin-eyebrow">YÖNETİM</p><h1>Atölyeye gir</h1>
    <p class="admin-muted">Projeleri, güncellemeleri ve hikâye taslaklarını buradan yönetebilirsin.</p>
    <?php if ($error !== ''): ?><div class="admin-flash admin-flash--error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="admin-form">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="admin-field admin-field--full"><label for="password">Şifre</label><input id="password" name="password" type="password" required autofocus autocomplete="current-password"></div>
        <div class="admin-form-actions"><button class="admin-button admin-button--primary" type="submit">Giriş yap</button></div>
    </form>
</div></div>
<?php admin_layout_end(); ?>
