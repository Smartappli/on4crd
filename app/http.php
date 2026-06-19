<?php
declare(strict_types=1);

if (!function_exists('base_url')) {
function app_is_production_environment(): bool
{
    $environment = strtolower(trim((string) config('app.env', 'production')));

    return $environment === 'production';
}

function app_normalized_host(string $hostHeader): string
{
    $host = trim(explode(',', $hostHeader)[0] ?? '');
    if ($host === '' || preg_match('/[\r\n]/', $host) === 1) {
        return '';
    }

    if (str_contains($host, '://')) {
        $parts = parse_url($host);
        if (!is_array($parts) || empty($parts['host'])) {
            return '';
        }
        $host = (string) $parts['host'];
        if (!empty($parts['port'])) {
            $host .= ':' . (int) $parts['port'];
        }
    }

    $host = strtolower(rtrim(trim($host), '.'));
    if ($host === '' || preg_match('/[^a-z0-9\\-\\.:\\[\\]]/i', $host) !== 0) {
        return '';
    }

    return $host;
}

function app_host_without_port(string $host): string
{
    $host = app_normalized_host($host);
    if ($host === '') {
        return '';
    }

    if (str_starts_with($host, '[')) {
        $end = strpos($host, ']');
        return $end === false ? $host : substr($host, 1, $end - 1);
    }

    return (string) preg_replace('/:\\d+$/', '', $host);
}

/**
 * @return list<string>
 */
function app_allowed_hosts(): array
{
    $hosts = [];
    foreach ((array) config('security.allowed_hosts', []) as $configuredHost) {
        $host = app_normalized_host((string) $configuredHost);
        if ($host !== '') {
            $hosts[] = app_host_without_port($host);
        }
    }

    $baseUrl = trim((string) config('app.base_url', ''));
    if ($baseUrl !== '') {
        $parts = parse_url($baseUrl);
        if (is_array($parts) && !empty($parts['host'])) {
            $hosts[] = strtolower((string) $parts['host']);
        }
    }

    return array_values(array_unique(array_filter($hosts)));
}

function app_first_allowed_host(): string
{
    $configuredBaseUrl = trim((string) config('app.base_url', ''));
    if ($configuredBaseUrl !== '') {
        $parts = parse_url($configuredBaseUrl);
        if (is_array($parts) && !empty($parts['host'])) {
            $host = strtolower((string) $parts['host']);
            if (!empty($parts['port'])) {
                $host .= ':' . (int) $parts['port'];
            }

            return $host;
        }
    }

    foreach ((array) config('security.allowed_hosts', []) as $configuredHost) {
        $host = app_normalized_host((string) $configuredHost);
        if ($host !== '') {
            return $host;
        }
    }

    return '';
}

function request_is_from_trusted_proxy(): bool
{
    $remoteAddress = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($remoteAddress === '') {
        return false;
    }

    $trustedProxies = array_map('trim', array_map('strval', (array) config('security.trusted_proxies', [])));

    return in_array($remoteAddress, $trustedProxies, true);
}

function request_host_header(): string
{
    if (request_is_from_trusted_proxy()) {
        $forwardedHost = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''));
        if ($forwardedHost !== '') {
            return $forwardedHost;
        }
    }

    return (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

function request_forwarded_port(): string
{
    if (!request_is_from_trusted_proxy()) {
        return '';
    }

    $forwardedPortHeader = trim((string) ($_SERVER['HTTP_X_FORWARDED_PORT'] ?? ''));
    if ($forwardedPortHeader === '') {
        return '';
    }

    return trim(explode(',', $forwardedPortHeader)[0] ?? '');
}

function request_host_is_allowed(string $host): bool
{
    $allowedHosts = app_allowed_hosts();
    if ($allowedHosts === []) {
        return !app_is_production_environment();
    }

    return in_array(app_host_without_port($host), $allowedHosts, true);
}

function base_url(string $path = ''): string
{
    static $base = null;

    if ($base === null) {
        $configured = rtrim((string) config('app.base_url', ''), '/');
        if ($configured !== '') {
            $base = $configured;
        } else {
            $scheme = is_https_request() ? 'https' : 'http';
            $host = app_normalized_host(request_host_header());
            if ($host === '' || !request_host_is_allowed($host)) {
                if (app_is_production_environment()) {
                    error_log('ON4CRD app.base_url or security.allowed_hosts must be configured for production absolute URLs.');
                }
                $host = app_first_allowed_host() ?: 'localhost';
                if (app_is_production_environment()) {
                    $scheme = 'https';
                }
            }

            $forwardedPort = request_forwarded_port();
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

if (!function_exists('fullcalendar_locale_code')) {
function fullcalendar_locale_code(string $locale): string
{
    $normalized = strtolower(str_replace('_', '-', trim($locale)));
    $base = explode('-', $normalized)[0] ?? $normalized;
    $map = [
        'en' => 'en-gb',
        'zh' => 'zh-cn',
        'bn' => 'en-gb',
        'ga' => 'en-gb',
        'hi' => 'en-gb',
        'mt' => 'en-gb',
    ];
    $candidate = $map[$base] ?? $base;
    $path = dirname(__DIR__) . '/assets/vendor/fullcalendar/7.0.0-rc.2/locales/' . $candidate . '.global.js';

    return is_file($path) ? $candidate : 'en-gb';
}
}

if (!function_exists('fullcalendar_locale_asset_url')) {
function fullcalendar_locale_asset_url(string $locale): string
{
    return asset_url('assets/vendor/fullcalendar/7.0.0-rc.2/locales/' . fullcalendar_locale_code($locale) . '.global.js');
}
}

if (!function_exists('route_url')) {
function route_url(string $route, array $query = []): string
{
    static $directRoutes = [
        'install.php' => true,
        'sitemap.xml' => true,
        'robots.txt' => true,
        'llms.txt' => true,
        'ai-index.json' => true,
        'knowledge-graph.jsonld' => true,
    ];

    $route = trim($route);
    if ($route === '' || $route === 'home') {
        if ($query === []) {
            return base_url('/');
        }

        return base_url('/?' . http_build_query($query));
    }

    $normalizedRoute = ltrim($route, '/');
    if (isset($directRoutes[$normalizedRoute])) {
        $suffix = $query === [] ? '' : ('?' . http_build_query($query));
        return base_url('/' . $normalizedRoute . $suffix);
    }

    if (str_ends_with($route, '.php')) {
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
        'idea_submit',
    ];
    if (in_array($route, $blockedRoutes, true)) {
        return null;
    }

    unset($query['route'], $query['next'], $query['_csrf']);

    $safeUrl = route_url_clean($route, $query);
    $fragment = trim((string) ($parts['fragment'] ?? ''));
    if ($fragment !== '' && preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,79}$/', $fragment) === 1) {
        $safeUrl .= '#' . rawurlencode($fragment);
    }

    return $safeUrl;
}
}
