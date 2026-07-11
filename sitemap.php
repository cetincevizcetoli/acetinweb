<?php
declare(strict_types=1);

require __DIR__ . '/_private.php';
fv7_require_private('app/bootstrap.php');

header('Content-Type: application/xml; charset=UTF-8');
echo SitemapService::xml();
