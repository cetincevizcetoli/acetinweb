<?php
declare(strict_types=1);

return [
    // Copy this file to config/local.php and adjust paths on the target machine.
    // Keep config/local.php out of Git and public httpdocs deployments.
    // This file belongs next to the private config/ directory, outside web root.
    'public_path' => '/absolute/httpdocs',
    'storage_path' => '/absolute/acetinweb_private/storage',
    'db_path' => '/absolute/acetinweb_private/storage/fikrimvar.sqlite',
    'debug' => false,
    'allow_system_check' => false,
    'deploy_remote_manifest_url' => 'https://www.example.com/deploy-manifest.json',
    'deploy_live_db_target' => '/absolute/acetinweb_private/storage/fikrimvar.sqlite',
];
