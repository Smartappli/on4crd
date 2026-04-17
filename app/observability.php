<?php
declare(strict_types=1);

/**
 * @param array<string, mixed> $config
 */
function setup_observability(array $config): void
{
    if (!($config['enabled'] ?? true)) {
        return;
    }

    if (session_status() === PHP_SESSION_ACTIVE && !isset($_SESSION['request_id'])) {
        $_SESSION['request_id'] = bin2hex(random_bytes(12));
    }

    set_exception_handler(static function (Throwable $exception): void {
        log_structured_event('php_exception', [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    });

    set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
        log_structured_event('php_error', [
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line,
        ]);

        return false;
    });
}

/**
 * @param array<string, mixed> $context
 */
function log_structured_event(string $event, array $context = []): void
{
    $payload = [
        'ts' => gmdate('c'),
        'event' => $event,
        'request_id' => (string) ($_SESSION['request_id'] ?? ''),
        'route' => (string) ($_GET['route'] ?? 'home'),
        'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
    ];

    foreach ($context as $key => $value) {
        if (!is_scalar($value) && $value !== null) {
            continue;
        }
        $payload[$key] = $value;
    }

    error_log('[on4crd] ' . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}
