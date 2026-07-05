<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

if (admin_is_configured()) {
    admin_redirect('login.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_is_local_request()) {
        $error = 'İlk şifre kurulumu yalnızca localhost üzerinden yapılabilir.';
    } elseif (!verify_csrf($_POST['csrf'] ?? null)) {
        $error = 'Oturum doğrulaması başarısız.';
    } else {
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');
        if (strlen($password) < 10) {
            $error = 'Şifre en az 10 karakter olmalı.';
        } elseif ($password !== $confirm) {
            $error = 'Şifreler eşleşmiyor.';
        } else {
            atomic_write_json(ADMIN_AUTH_PATH, [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'created_at' => date(DATE_ATOM),
                'updated_at' => date(DATE_ATOM),
            ], false);
            admin_login($password);
            admin_flash('success', 'Yönetim şifresi oluşturuldu.');
            admin_redirect('index.php');
        }
    }
}
admin_layout_start('İlk kurulum');
?>
<div class="admin-auth">
    <div class="admin-card">
        <p class="admin-eyebrow">İLK KURULUM</p>
        <h1>Yönetim şifresi</h1>
        <p class="admin-muted">Bu ekran yalnızca ilk kurulumda ve localhost üzerinde çalışır. Şifre özeti <code>data/admin-auth.json</code> dosyasına yazılır.</p>
        <?php if ($error !== ''): ?><div class="admin-flash admin-flash--error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="admin-form">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <div class="admin-field admin-field--full"><label for="password">Yeni şifre</label><input id="password" name="password" type="password" minlength="10" required autocomplete="new-password"></div>
            <div class="admin-field admin-field--full"><label for="confirm">Şifreyi tekrar yaz</label><input id="confirm" name="confirm" type="password" minlength="10" required autocomplete="new-password"></div>
            <div class="admin-form-actions"><button class="admin-button admin-button--primary" type="submit">Kurulumu tamamla</button></div>
        </form>
    </div>
</div>
<?php admin_layout_end(); ?>
