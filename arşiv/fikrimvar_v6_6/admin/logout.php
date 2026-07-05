<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
admin_logout();
admin_redirect('login.php');
