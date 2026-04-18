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

    $displayErrorDetails = (bool) ($config['display_error_details'] ?? false);

    if (session_status() === PHP_SESSION_ACTIVE && !isset($_SESSION['request_id'])) {
        $_SESSION['request_id'] = bin2hex(random_bytes(12));
    }

    set_exception_handler(static function (Throwable $exception) use ($displayErrorDetails): void {
        log_structured_event('php_exception', [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        $requestId = (string) ($_SESSION['request_id'] ?? '');
        $safeMessage = observability_build_safe_error_message($exception, $requestId, $displayErrorDetails);

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, '[on4crd] ' . $safeMessage . PHP_EOL);

            return;
        }

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            header('Cache-Control: no-store, max-age=0');
        }

        echo $safeMessage;
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

function observability_build_safe_error_message(
    Throwable $exception,
    string $requestId = '',
    bool $displayErrorDetails = false
): string {
    $safeMessage = 'Une erreur interne est survenue.';
    if ($requestId !== '') {
        $safeMessage .= ' Référence: ' . $requestId . '.';
    }

    if ($displayErrorDetails) {
        $safeMessage .= ' ' . $exception->getMessage();
    }

    return $safeMessage;
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
