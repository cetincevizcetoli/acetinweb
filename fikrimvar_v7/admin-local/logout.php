<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
if(admin_logged_in()) admin_audit('logout','admin_user',(int)$_SESSION['admin_user_id']);
$_SESSION=[]; session_destroy(); redirect('login.php');
