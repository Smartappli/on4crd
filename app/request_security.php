<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf']) || strlen($_SESSION['_csrf']) !== 64) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function preferred_locale_from_accept_language(string $header, ?array $supportedLocales = null): string
{
    $supportedLocales ??= supported_locales();
    $normalized = strtolower(trim($header));
    if ($normalized === '') {
        return 'en';
    }
    $chunks = preg_split('/\s*,\s*/', $normalized) ?: [];
    foreach ($chunks as $chunk) {
        if ($chunk === '') {
            continue;
        }
        $localePart = explode(';', $chunk)[0] ?? '';
        $localePart = trim($localePart);
        if ($localePart === '') {
            continue;
        }
        $base = explode('-', $localePart)[0] ?? $localePart;
        if (in_array($base, $supportedLocales, true)) {
            return $base;
        }
    }

    return 'en';
}

function preferred_locale_from_host(string $host, ?array $supportedLocales = null): string
{
    $supportedLocales ??= supported_locales();
    $normalizedHost = strtolower(trim($host));
    if ($normalizedHost === '') {
        return '';
    }

    $hostname = explode(':', $normalizedHost)[0] ?? $normalizedHost;
    $firstLabel = explode('.', $hostname)[0] ?? '';
    if ($firstLabel !== '' && in_array($firstLabel, $supportedLocales, true)) {
        return $firstLabel;
    }

    return '';
}

function initialize_user_preferences(): void
{
    $supportedLocales = supported_locales();
    $supportedThemes = ['light', 'dark'];
    $supportedAccents = ['blue', 'emerald', 'violet', 'red', 'amber', 'orange'];

    $cookieLocale = strtolower((string) ($_COOKIE['on4crd_locale'] ?? ''));
    $cookieTheme = strtolower((string) ($_COOKIE['on4crd_theme'] ?? ''));
    $cookieAccent = strtolower((string) ($_COOKIE['on4crd_accent'] ?? ''));

    if (!isset($_SESSION['locale'])) {
        $hostLocale = preferred_locale_from_host((string) ($_SERVER['HTTP_HOST'] ?? ''), $supportedLocales);
        if ($hostLocale !== '') {
            $_SESSION['locale'] = $hostLocale;
        } elseif (in_array($cookieLocale, $supportedLocales, true)) {
            $_SESSION['locale'] = $cookieLocale;
        } else {
            $_SESSION['locale'] = preferred_locale_from_accept_language((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''), $supportedLocales);
        }
    }
    if (!isset($_SESSION['theme'])) {
        $_SESSION['theme'] = in_array($cookieTheme, $supportedThemes, true) ? $cookieTheme : 'dark';
    }
    if (!isset($_SESSION['accent'])) {
        $_SESSION['accent'] = in_array($cookieAccent, $supportedAccents, true) ? $cookieAccent : 'blue';
    }
}

function verify_csrf(): void
{
    $sessionToken = (string) ($_SESSION['_csrf'] ?? '');
    $postToken = (string) ($_POST['_csrf'] ?? '');
    $headerToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $submittedToken = $postToken !== '' ? $postToken : $headerToken;
    if ($sessionToken === '' || $submittedToken === '' || !hash_equals($sessionToken, $submittedToken)) {
        throw new RuntimeException(upload_i18n_message('invalid_csrf_token'));
    }
}

function public_form_client_ip(): string
{
    if (function_exists('request_is_from_trusted_proxy') && request_is_from_trusted_proxy()) {
        $forwardedFor = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($forwardedFor !== '') {
            $candidate = trim(explode(',', $forwardedFor)[0] ?? '');
            if ($candidate !== '') {
                return $candidate;
            }
        }
    }

    return trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) ?: 'unknown';
}

function public_form_rate_limit(string $scope, int $limit = 5, int $windowSeconds = 900): void
{
    $scope = preg_replace('/[^a-z0-9_\\-]/i', '', $scope) ?: 'form';
    $ipHash = hash('sha256', public_form_client_ip());
    $sessionHash = hash('sha256', session_id() ?: csrf_token());
    $directory = storage_path('cache/rate-limit');
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        return;
    }

    $file = rtrim($directory, '/') . '/' . $scope . '-' . hash('sha256', $ipHash . '|' . $sessionHash) . '.json';
    $now = time();
    $events = [];
    $raw = is_file($file) ? @file_get_contents($file) : false;
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $events = array_values(array_filter(array_map('intval', $decoded), static fn(int $timestamp): bool => $timestamp > $now - $windowSeconds));
        }
    }

    if (count($events) >= $limit) {
        throw new RuntimeException('too_many_requests');
    }

    $events[] = $now;
    @file_put_contents($file, json_encode($events, JSON_THROW_ON_ERROR), LOCK_EX);
}

/**
 * @return array{left:int,right:int,answer:int}
 */
function public_form_captcha_challenge(string $scope): array
{
    $scope = preg_replace('/[^a-z0-9_\\-]/i', '', $scope) ?: 'form';
    if (!isset($_SESSION['_public_form_captchas']) || !is_array($_SESSION['_public_form_captchas'])) {
        $_SESSION['_public_form_captchas'] = [];
    }

    $existing = $_SESSION['_public_form_captchas'][$scope] ?? null;
    if (is_array($existing)
        && isset($existing['left'], $existing['right'], $existing['answer'])
        && (int) ($existing['expires_at'] ?? 0) > time()
    ) {
        return [
            'left' => (int) $existing['left'],
            'right' => (int) $existing['right'],
            'answer' => (int) $existing['answer'],
        ];
    }

    $left = random_int(2, 9);
    $right = random_int(2, 9);
    $_SESSION['_public_form_captchas'][$scope] = [
        'left' => $left,
        'right' => $right,
        'answer' => $left + $right,
        'expires_at' => time() + 1800,
    ];

    return ['left' => $left, 'right' => $right, 'answer' => $left + $right];
}

function public_form_captcha_label(array $challenge, string $locale = ''): string
{
    $left = (int) ($challenge['left'] ?? 0);
    $right = (int) ($challenge['right'] ?? 0);

    return match (strtolower($locale)) {
        'fr' => 'Anti-spam: combien font ' . $left . ' + ' . $right . ' ?',
        'nl' => 'Anti-spam: hoeveel is ' . $left . ' + ' . $right . '?',
        'de' => 'Anti-spam: wie viel ist ' . $left . ' + ' . $right . '?',
        default => 'Anti-spam: what is ' . $left . ' + ' . $right . '?',
    };
}

function public_form_verify_captcha(string $scope, string $answer): bool
{
    $scope = preg_replace('/[^a-z0-9_\\-]/i', '', $scope) ?: 'form';
    $challenge = $_SESSION['_public_form_captchas'][$scope] ?? null;
    unset($_SESSION['_public_form_captchas'][$scope]);
    if (!is_array($challenge) || (int) ($challenge['expires_at'] ?? 0) < time()) {
        return false;
    }

    $submitted = trim($answer);
    if ($submitted === '' || preg_match('/^-?\\d+$/', $submitted) !== 1) {
        return false;
    }

    return hash_equals((string) (int) $challenge['answer'], (string) (int) $submitted);
}

function public_form_honeypot_triggered(string $fieldName): bool
{
    return trim((string) ($_POST[$fieldName] ?? '')) !== '';
}

function matomo_origin(): ?string
{
    $matomoUrl = trim((string) config('tracking.matomo_url', ''));
    if ($matomoUrl === '') {
        return null;
    }

    $parts = parse_url($matomoUrl);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return null;
    }

    $origin = $parts['scheme'] . '://' . $parts['host'];
    if (!empty($parts['port'])) {
        $origin .= ':' . $parts['port'];
    }

    return $origin;
}

function security_header_current_route(): string
{
    $route = trim((string) ($_GET['route'] ?? ''));
    if ($route === '') {
        $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $path = is_string($requestPath) ? trim($requestPath, '/') : '';
        if ($path !== '' && $path !== 'index.php') {
            $route = strtolower(pathinfo($path, PATHINFO_FILENAME));
        }
    }

    $normalizedRoute = ltrim($route, '/');
    if (str_ends_with($normalizedRoute, '.php')) {
        return strtolower(pathinfo($normalizedRoute, PATHINFO_FILENAME));
    }

    return $route;
}

function apply_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    $nonce = csp_nonce();
    $scriptSrc = ["'self'", "'nonce-" . $nonce . "'"];
    $imgSrc = ["'self'", 'data:', 'https://www.hamqsl.com'];
    $styleSrc = ["'self'", "'unsafe-inline'"];
    $connectSrc = ["'self'"];
    $frameSrc = ["'self'", 'https://www.google.com', 'https://maps.google.com'];
    $frameAncestors = "'none'";
    $xFrameOptions = 'DENY';

    if (in_array(security_header_current_route(), ['member_library_preview', 'member_document_preview'], true)) {
        $frameAncestors = "'self'";
        $xFrameOptions = 'SAMEORIGIN';
    }

    $matomoOrigin = matomo_origin();
    if ($matomoOrigin !== null) {
        $scriptSrc[] = $matomoOrigin;
        $imgSrc[] = $matomoOrigin;
        $connectSrc[] = $matomoOrigin;
    }

    $csp = [
        "default-src 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        'frame-ancestors ' . $frameAncestors,
        "object-src 'none'",
        "manifest-src 'self'",
        "worker-src 'self'",
        'frame-src ' . implode(' ', array_unique($frameSrc)),
        "font-src 'self' data:",
        'script-src ' . implode(' ', array_unique($scriptSrc)),
        'style-src ' . implode(' ', array_unique($styleSrc)),
        'img-src ' . implode(' ', array_unique($imgSrc)),
        'connect-src ' . implode(' ', array_unique($connectSrc)),
    ];

    if (is_https_request()) {
        $csp[] = 'upgrade-insecure-requests';
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    header('Content-Security-Policy: ' . implode('; ', $csp));
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: ' . $xFrameOptions);
    header('Permissions-Policy: camera=(), geolocation=(), microphone=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');

    if (!empty($_SESSION['member_id'])) {
        header('Cache-Control: private, no-store, max-age=0');
        header('Pragma: no-cache');
    }
}
