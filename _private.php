<?php
declare(strict_types=1);

function fv7_private_roots(): array
{
    $roots = [
        getenv('FV7_PRIVATE_ROOT') ?: '',
        'C:/xampp/acetinweb_private',
        __DIR__ . '/../../acetinweb_private',
        __DIR__ . '/../acetinweb_private',
        __DIR__ . '/../../../acetinweb_private',
        __DIR__,
        dirname(__DIR__),
    ];

    return array_values(array_unique(array_filter(array_map(
        static fn($path) => rtrim(str_replace('\\', '/', (string)$path), '/'),
        $roots
    ))));
}

function fv7_require_private(string $relative): void
{
    $relative = ltrim(str_replace('\\', '/', $relative), '/');
    foreach (fv7_private_roots() as $root) {
        $path = $root . '/' . $relative;
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }

    http_response_code(500);
    exit('Application private file not found.');
}
