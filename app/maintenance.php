<?php
declare(strict_types=1);

/**
 * @param array<string, mixed>|null $settings
 */
function maintenance_is_enabled(?array $settings = null): bool
{
    $settings = $settings ?? (array) config('app.maintenance', []);

    return (bool) ($settings['enabled'] ?? false);
}

/**
 * @param array<string, mixed>|null $settings
 * @return list<string>
 */
function maintenance_allowed_routes(?array $settings = null): array
{
    $settings = $settings ?? (array) config('app.maintenance', []);
    $allowed = $settings['allowed_routes'] ?? ['login', 'robots.txt', 'sitemap.xml'];

    if (!is_array($allowed)) {
        return ['login', 'robots.txt', 'sitemap.xml'];
    }

    $normalized = [];
    foreach ($allowed as $route) {
        if (!is_string($route)) {
            continue;
        }
        $route = trim($route);
        if ($route === '') {
            continue;
        }
        $normalized[] = $route;
    }

    return $normalized !== [] ? $normalized : ['login', 'robots.txt', 'sitemap.xml'];
}

/**
 * @param array<string, mixed>|null $settings
 */
function maintenance_secret(?array $settings = null): string
{
    $settings = $settings ?? (array) config('app.maintenance', []);

    return trim((string) ($settings['secret'] ?? ''));
}

function maintenance_has_bypass(): bool
{
    return (bool) ($_SESSION['maintenance_bypass'] ?? false);
}

/**
 * @param array<string, mixed>|null $settings
 */
function maintenance_try_query_bypass(?array $settings = null): bool
{
    $secret = maintenance_secret($settings);
    if ($secret === '') {
        return false;
    }

    $provided = trim((string) ($_GET['maintenance_bypass'] ?? ''));
    if ($provided === '' || !hash_equals($secret, $provided)) {
        return false;
    }

    $_SESSION['maintenance_bypass'] = true;

    return true;
}

/**
 * @param array<string, mixed>|null $settings
 */
function maintenance_should_block_route(string $route, ?array $settings = null): bool
{
    if (!maintenance_is_enabled($settings)) {
        return false;
    }

    maintenance_try_query_bypass($settings);
    if (maintenance_has_bypass()) {
        return false;
    }

    return !in_array($route, maintenance_allowed_routes($settings), true);
}

function maintenance_render_and_exit(): never
{
    http_response_code(503);
    header('Retry-After: 900');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    $offlineFile = __DIR__ . '/../offline.html';
    if (is_file($offlineFile)) {
        header('Content-Type: text/html; charset=UTF-8');
        readfile($offlineFile);
    } else {
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Service temporairement indisponible';
    }

    exit;
}
