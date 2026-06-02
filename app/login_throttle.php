<?php
declare(strict_types=1);

function client_ip_address(): string
{
    return trim((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')) ?: '0.0.0.0';
}

function cache_dir_path(): string
{
    $dir = __DIR__ . '/../storage/cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    return $dir;
}

function login_throttle_file(): string
{
    return cache_dir_path() . '/login-' . hash('sha256', client_ip_address()) . '.json';
}

function login_throttle_state(): array
{
    $file = login_throttle_file();
    if (!is_file($file)) {
        return ['attempts' => 0, 'first_attempt_at' => 0, 'locked_until' => 0];
    }

    $decoded = json_decode((string) file_get_contents($file), true);
    return is_array($decoded)
        ? array_merge(['attempts' => 0, 'first_attempt_at' => 0, 'locked_until' => 0], $decoded)
        : ['attempts' => 0, 'first_attempt_at' => 0, 'locked_until' => 0];
}

function write_login_throttle_state(array $state): void
{
    file_put_contents(login_throttle_file(), json_encode($state, JSON_THROW_ON_ERROR));
}

function enforce_login_throttle(): void
{
    $state = login_throttle_state();
    if ((int) ($state['locked_until'] ?? 0) > time()) {
        throw new RuntimeException(upload_i18n_message('too_many_login_attempts'));
    }
}

function record_login_failure(): void
{
    $state = login_throttle_state();
    $now = time();
    $window = 900;

    if (($now - (int) ($state['first_attempt_at'] ?? 0)) > $window) {
        $state = ['attempts' => 0, 'first_attempt_at' => $now, 'locked_until' => 0];
    }

    $state['first_attempt_at'] = (int) ($state['first_attempt_at'] ?: $now);
    $state['attempts'] = (int) ($state['attempts'] ?? 0) + 1;
    if ($state['attempts'] >= 5) {
        $state['locked_until'] = $now + 900;
    }

    write_login_throttle_state($state);
}

function clear_login_failures(): void
{
    $file = login_throttle_file();
    if (is_file($file)) {
        unlink($file);
    }
}
