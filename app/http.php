<?php
declare(strict_types=1);

if (!function_exists('base_url')) {
function base_url(string $path = ''): string
{
    static $base = null;

    if ($base === null) {
        $configured = rtrim((string) config('app.base_url', ''), '/');
        if ($configured !== '') {
            $base = $configured;
        } else {
            $scheme = is_https_request() ? 'https' : 'http';
            $forwardedHostHeader = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''));
            $hostHeader = $forwardedHostHeader !== ''
                ? trim(explode(',', $forwardedHostHeader)[0])
                : (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

            $host = strtolower(trim($hostHeader));
            if ($host === '' || preg_match('/[^a-z0-9\\-\\.:\\[\\]]/i', $host) !== 0) {
                $host = 'localhost';
            }

            $forwardedPortHeader = trim((string) ($_SERVER['HTTP_X_FORWARDED_PORT'] ?? ''));
            $forwardedPort = $forwardedPortHeader !== ''
                ? trim(explode(',', $forwardedPortHeader)[0])
                : '';
            if ($forwardedPort !== '' && ctype_digit($forwardedPort)) {
                $port = (int) $forwardedPort;
                if (($scheme === 'https' && $port !== 443) || ($scheme === 'http' && $port !== 80)) {
                    $hostWithoutPort = preg_replace('/:\\d+$/', '', $host);
                    $host = ($hostWithoutPort ?: $host) . ':' . $port;
                }
            }

            $base = $scheme . '://' . $host;
        }
    }

    if ($path === '') {
        return $base;
    }

    return $base . '/' . ltrim($path, '/');
}
}

if (!function_exists('asset_url')) {
function asset_url(string $path): string
{
    static $versionCache = [];

    $normalizedPath = ltrim($path, '/');
    if ($normalizedPath === '') {
        return base_url($path);
    }

    if (!array_key_exists($normalizedPath, $versionCache)) {
        $absolutePath = dirname(__DIR__) . '/' . $normalizedPath;
        $versionCache[$normalizedPath] = is_file($absolutePath) ? (string) filemtime($absolutePath) : '';
    }

    $assetUrl = base_url($normalizedPath);
    $version = $versionCache[$normalizedPath];
    if ($version === '') {
        return $assetUrl;
    }

    $separator = str_contains($assetUrl, '?') ? '&' : '?';
    return $assetUrl . $separator . 'v=' . rawurlencode($version);
}
}

if (!function_exists('route_url')) {
function route_url(string $route, array $query = []): string
{
    static $directPhpRoutes = ['install.php' => true, 'sitemap.xml' => true, 'robots.txt' => true, 'llms.txt' => true];

    $route = trim($route);
    if ($route === '' || $route === 'home') {
        if ($query === []) {
            return base_url('/');
        }

        return base_url('/?' . http_build_query($query));
    }

    if (str_ends_with($route, '.php')) {
        $normalizedRoute = ltrim($route, '/');
        if (isset($directPhpRoutes[$normalizedRoute])) {
            $suffix = $query === [] ? '' : ('?' . http_build_query($query));
            return base_url('/' . $normalizedRoute . $suffix);
        }

        $route = pathinfo($normalizedRoute, PATHINFO_FILENAME);
    }

    $extra = [];
    if (str_contains($route, '&')) {
        [$route, $tail] = explode('&', $route, 2);
        parse_str($tail, $extra);
    }

    $params = array_merge(['route' => $route], $extra, $query);
    return base_url('/index.php?' . http_build_query($params));
}
}

if (!function_exists('clean_query_params')) {
/**
 * Drop empty query values while preserving meaningful numeric and boolean values.
 *
 * @param array<string, mixed> $query
 * @return array<string, mixed>
 */
function clean_query_params(array $query): array
{
    return array_filter($query, static fn(mixed $value): bool => $value !== '' && $value !== null && $value !== false);
}
}

if (!function_exists('route_url_clean')) {
/**
 * Build a route URL without leaking empty filter values into the query string.
 *
 * @param array<string, mixed> $query
 */
function route_url_clean(string $route, array $query = []): string
{
    return route_url($route, clean_query_params($query));
}
}

if (!function_exists('login_next_url_for_route')) {
/**
 * Build a safe internal target URL for the login `next` parameter.
 *
 * @param array<string, mixed> $query
 */
function login_next_url_for_route(string $route, array $query = []): ?string
{
    $route = trim($route);
    if ($route === '' || preg_match('/^[a-z0-9_]+$/', $route) !== 1) {
        return null;
    }

    unset($query['route'], $query['next'], $query['_csrf']);

    return route_url_clean($route, $query);
}
}

if (!function_exists('safe_login_next_url')) {
function safe_login_next_url(string $next): ?string
{
    $next = trim($next);
    if ($next === '' || preg_match('/[\r\n]/', $next) === 1) {
        return null;
    }

    $parts = parse_url($next);
    if (!is_array($parts)) {
        return null;
    }

    $baseParts = parse_url(base_url('/'));
    if (!is_array($baseParts)) {
        return null;
    }

    if (isset($parts['scheme']) || isset($parts['host'])) {
        $nextScheme = strtolower((string) ($parts['scheme'] ?? ''));
        $baseScheme = strtolower((string) ($baseParts['scheme'] ?? ''));
        $nextHost = strtolower((string) ($parts['host'] ?? ''));
        $baseHost = strtolower((string) ($baseParts['host'] ?? ''));
        $nextPort = (int) ($parts['port'] ?? 0);
        $basePort = (int) ($baseParts['port'] ?? 0);
        if ($nextScheme !== $baseScheme || $nextHost !== $baseHost || $nextPort !== $basePort) {
            return null;
        }
    } elseif (!str_starts_with($next, '/') || str_starts_with($next, '//')) {
        return null;
    }

    $query = [];
    if (isset($parts['query'])) {
        parse_str((string) $parts['query'], $query);
    }
    $route = trim((string) ($query['route'] ?? ''));
    if ($route === '' || preg_match('/^[a-z0-9_]+$/', $route) !== 1) {
        return null;
    }

    $blockedRoutes = [
        'login',
        'logout',
        'register',
        'forgot_password',
        'reset_password',
        'toggle_theme',
        'set_language',
        'set_accent',
        'set_theme',
    ];
    if (in_array($route, $blockedRoutes, true)) {
        return null;
    }

    unset($query['route'], $query['next'], $query['_csrf']);

    return route_url_clean($route, $query);
}
}
