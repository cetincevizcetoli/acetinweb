<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
if(admin_count_users()===0) redirect('setup.php');
if(admin_logged_in()) redirect('index.php');
$error='';
if(is_post()){
    verify_csrf(); $username=trim((string)($_POST['username'] ?? '')); $password=(string)($_POST['password'] ?? '');
    $st=db()->prepare('SELECT * FROM admin_users WHERE username=? AND is_active=1'); $st->execute([$username]); $u=$st->fetch();
    if($u && password_verify($password,$u['password_hash'])){
        session_regenerate_id(true); $_SESSION['admin_user_id']=(int)$u['id'];
        db()->prepare('UPDATE admin_users SET last_login_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$u['id']]);
        admin_audit('login','admin_user',(int)$u['id']); redirect('index.php');
    }
    $error='Kullanıcı adı veya şifre yanlış.';
}
admin_head('Giriş');
?><div class="login-card"><p class="eyebrow">YEREL YÖNETİM</p><h1>#FikrimVar V7</h1><p>SQLite içerik, Atölye ve Hikâye yönetimi.</p><?php if($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?><form method="post"><?= csrf_field() ?><div class="field"><label>Kullanıcı adı</label><input name="username" autocomplete="username" required></div><div class="field"><label>Şifre</label><input type="password" name="password" autocomplete="current-password" required></div><div class="form-actions"><button type="submit">Giriş yap</button></div></form></div><?php admin_foot(); ?>
