<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
if(admin_count_users()>0) redirect('login.php');
$error='';
if(is_post()){
    verify_csrf();
    $username=trim((string)($_POST['username'] ?? 'ahmet'));
    $display=trim((string)($_POST['display_name'] ?? 'Ahmet Çetin'));
    $password=(string)($_POST['password'] ?? ''); $confirm=(string)($_POST['password_confirm'] ?? '');
    if(strlen($username)<3) $error='Kullanıcı adı en az 3 karakter olmalı.';
    elseif(strlen($password)<10) $error='Şifre en az 10 karakter olmalı.';
    elseif($password!==$confirm) $error='Şifreler eşleşmiyor.';
    else{
        $st=db()->prepare('INSERT INTO admin_users(username,password_hash,display_name) VALUES (?,?,?)');
        $st->execute([$username,password_hash($password,PASSWORD_DEFAULT),$display]);
        flash('success','Yönetici hesabı oluşturuldu.'); redirect('login.php');
    }
}
admin_head('İlk kurulum');
?><div class="login-card"><p class="eyebrow">İLK KURULUM</p><h1>Yerel yönetici hesabı</h1><p>Bu panel yalnızca localhost üzerinden açılır. Hazır parola yoktur.</p><?php if($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?><form method="post"><?= csrf_field() ?><div class="field"><label>Kullanıcı adı</label><input name="username" value="ahmet" required></div><div class="field"><label>Görünen ad</label><input name="display_name" value="Ahmet Çetin" required></div><div class="field"><label>Şifre</label><input type="password" name="password" minlength="10" required></div><div class="field"><label>Şifre tekrar</label><input type="password" name="password_confirm" minlength="10" required></div><div class="form-actions"><button type="submit">Hesabı oluştur</button></div></form></div><?php admin_foot(); ?>
