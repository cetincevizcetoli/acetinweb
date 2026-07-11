<?php
declare(strict_types=1);

final class AppErrorService
{
    public static function log(Throwable $error, string $context = '', array $extra = []): string
    {
        $reference = date('YmdHis') . '-' . bin2hex(random_bytes(3));
        $dir = FV7_STORAGE . '/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $row = [
            'created_at' => date('c'),
            'reference' => $reference,
            'context' => $context,
            'type' => get_class($error),
            'message' => $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'extra' => $extra,
        ];

        file_put_contents($dir . '/app-errors.jsonl', json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
        error_log('[FikrimVar][' . $reference . '] ' . ($context !== '' ? $context . ': ' : '') . $error->getMessage());

        return $reference;
    }

    public static function adminMessage(Throwable $error, string $context = ''): string
    {
        $reference = self::log($error, $context);
        if (FV7_DEBUG) {
            return $error->getMessage() . ' [' . $reference . ']';
        }
        if ($error instanceof PDOException) {
            return 'Veritabanı işlemi tamamlanamadı. Hata kodu: ' . $reference;
        }
        return $error->getMessage();
    }
}
