<?php
declare(strict_types=1);

/**
 * @param array<string, mixed>|null $settings
 */
function cache_is_enabled(?array $settings = null): bool
{
    $settings = $settings ?? (array) config('cache', []);

    return (bool) ($settings['enabled'] ?? true);
}

/**
 * @param array<string, mixed>|null $settings
 */
function cache_default_ttl(?array $settings = null): int
{
    $settings = $settings ?? (array) config('cache', []);

    return max(1, (int) ($settings['default_ttl'] ?? 300));
}

/**
 * @param array<string, mixed>|null $settings
 */
function cache_storage_dir(?array $settings = null): string
{
    $settings = $settings ?? (array) config('cache', []);
    $configured = trim((string) ($settings['directory'] ?? ''));

    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    return rtrim(__DIR__ . '/../storage/cache/data', '/');
}

function cache_key_normalize(string $key): string
{
    $normalized = preg_replace('/[^a-zA-Z0-9_\-.]+/', '_', trim($key)) ?? '';

    return $normalized !== '' ? $normalized : 'cache_key';
}

/**
 * @param array<string, mixed>|null $settings
 */
function cache_file_path(string $key, ?array $settings = null): string
{
    return cache_storage_dir($settings) . '/' . cache_key_normalize($key) . '.cache.php';
}

/**
 * @param array<string, mixed>|null $settings
 */
function cache_get(string $key, mixed $default = null, ?array $settings = null): mixed
{
    if (!cache_is_enabled($settings)) {
        return $default;
    }

    $path = cache_file_path($key, $settings);
    if (!is_file($path)) {
        return $default;
    }

    $payload = @include $path;
    if (!is_array($payload) || !array_key_exists('expires_at', $payload) || !array_key_exists('value', $payload)) {
        @unlink($path);

        return $default;
    }

    if ((int) $payload['expires_at'] < time()) {
        @unlink($path);

        return $default;
    }

    return $payload['value'];
}

/**
 * @param array<string, mixed>|null $settings
 */
function cache_set(string $key, mixed $value, int $ttlSeconds, ?array $settings = null): bool
{
    if (!cache_is_enabled($settings)) {
        return false;
    }

    $ttlSeconds = max(1, $ttlSeconds);
    $directory = cache_storage_dir($settings);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        return false;
    }

    $path = cache_file_path($key, $settings);
    $payload = [
        'expires_at' => time() + $ttlSeconds,
        'value' => $value,
    ];

    $content = '<?php return ' . var_export($payload, true) . ';';

    return file_put_contents($path, $content, LOCK_EX) !== false;
}

/**
 * @param array<string, mixed>|null $settings
 */
function cache_forget(string $key, ?array $settings = null): void
{
    $path = cache_file_path($key, $settings);
    if (is_file($path)) {
        @unlink($path);
    }
}

/**
 * @param callable():mixed $resolver
 * @param array<string, mixed>|null $settings
 */
function cache_remember(string $key, int $ttlSeconds, callable $resolver, ?array $settings = null): mixed
{
    $cacheMiss = new stdClass();
    $cached = cache_get($key, $cacheMiss, $settings);
    if ($cached !== $cacheMiss) {
        return $cached;
    }

    $resolved = $resolver();
    cache_set($key, $resolved, $ttlSeconds > 0 ? $ttlSeconds : cache_default_ttl($settings), $settings);

    return $resolved;
}
