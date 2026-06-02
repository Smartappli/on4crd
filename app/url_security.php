<?php
declare(strict_types=1);

function normalize_http_url(string $url, bool $allowRelative = false): ?string
{
    $trimmed = trim($url);
    if ($trimmed === '') {
        return null;
    }

    if (preg_match('/[\r\n]/', $trimmed) === 1) {
        throw new RuntimeException(upload_i18n_message('invalid_url'));
    }

    if ($allowRelative && str_starts_with($trimmed, '//')) {
        throw new RuntimeException(upload_i18n_message('invalid_relative_url'));
    }

    if ($allowRelative && preg_match('~^(?:/|\./|\../|\?|#)~', $trimmed) === 1) {
        return $trimmed;
    }

    if (filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
        throw new RuntimeException(upload_i18n_message('invalid_url'));
    }

    $scheme = strtolower((string) parse_url($trimmed, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        throw new RuntimeException(upload_i18n_message('only_http_https_allowed'));
    }

    return $trimmed;
}

function is_private_or_reserved_ip(string $ip): bool
{
    if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
        return false;
    }

    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
}

function host_resolves_to_private_network(string $host): bool
{
    $normalizedHost = strtolower(rtrim(trim($host), '.'));
    if ($normalizedHost === '') {
        return true;
    }

    if (in_array($normalizedHost, ['localhost'], true) || str_ends_with($normalizedHost, '.local') || str_ends_with($normalizedHost, '.internal')) {
        return true;
    }

    if (filter_var($normalizedHost, FILTER_VALIDATE_IP) !== false) {
        return is_private_or_reserved_ip($normalizedHost);
    }

    if (function_exists('gethostbynamel')) {
        $ips = @gethostbynamel($normalizedHost);
        if (is_array($ips) && $ips !== []) {
            foreach ($ips as $ip) {
                if (is_private_or_reserved_ip($ip)) {
                    return true;
                }
            }
        }
    }

    return false;
}

function validate_outbound_url(string $url): ?string
{
    return normalize_http_url($url);
}

function validate_public_profile_url(string $url): ?string
{
    return normalize_http_url($url);
}

function validate_remote_feed_url(string $url): ?string
{
    $normalized = normalize_http_url($url);
    if ($normalized === null) {
        return null;
    }

    $host = strtolower((string) parse_url($normalized, PHP_URL_HOST));
    if ($host === '' || host_resolves_to_private_network($host)) {
        throw new RuntimeException("L'URL distante pointe vers un réseau privé ou réservé.");
    }

    $dnsRecords = @dns_get_record($host, DNS_A + DNS_AAAA);
    if (is_array($dnsRecords)) {
        foreach ($dnsRecords as $record) {
            $ip = (string) ($record['ip'] ?? $record['ipv6'] ?? '');
            if ($ip !== '' && is_private_or_reserved_ip($ip)) {
                throw new RuntimeException("L'URL distante résout vers une IP privée/réservée.");
            }
        }
    }

    return $normalized;
}
