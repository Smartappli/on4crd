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

function apply_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    $nonce = csp_nonce();
    $scriptSrc = ["'self'", "'nonce-" . $nonce . "'"];
    $imgSrc = ["'self'", 'data:', 'https:'];
    $styleSrc = ["'self'", "'unsafe-inline'"];
    $connectSrc = ["'self'"];
    $frameSrc = ["'self'", 'https://www.google.com', 'https://maps.google.com'];

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
        "frame-ancestors 'none'",
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
    header('X-Frame-Options: DENY');
    header('Permissions-Policy: camera=(), geolocation=(), microphone=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');

    if (!empty($_SESSION['member_id'])) {
        header('Cache-Control: private, no-store, max-age=0');
        header('Pragma: no-cache');
    }
}
