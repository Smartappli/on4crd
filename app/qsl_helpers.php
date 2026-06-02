<?php
declare(strict_types=1);

function safe_storage_public_path(string $path, array $allowedPrefixes = ['storage/press/']): string
{
    $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');
    if ($normalized === '' || str_contains($normalized, '..')) {
        throw new RuntimeException('Chemin de stockage invalide.');
    }

    foreach ($allowedPrefixes as $prefix) {
        $prefix = ltrim(str_replace('\\', '/', trim($prefix)), '/');
        if ($prefix !== '' && str_starts_with($normalized, $prefix)) {
            return $normalized;
        }
    }

    throw new RuntimeException('Chemin de stockage non autorisé.');
}

function safe_storage_public_path_or_null(string $path, array $allowedPrefixes = ['storage/press/']): ?string
{
    try {
        return safe_storage_public_path($path, $allowedPrefixes);
    } catch (Throwable) {
        return null;
    }
}

function qsl_normalize_callsign(string $value): string
{
    $upper = mb_safe_strtoupper(trim($value));
    $upper = preg_replace('/\s*\/\s*/', '/', $upper) ?? '';
    $upper = preg_replace('/[^A-Z0-9\/]/', '', $upper) ?? '';

    return trim($upper, '/');
}

function qsl_normalize_date(string $value): string
{
    $digits = preg_replace('/[^0-9]/', '', trim($value)) ?? '';
    if (strlen($digits) >= 8) {
        return substr($digits, 0, 8);
    }

    return '';
}

function qsl_normalize_time(string $value): string
{
    $trimmed = trim($value);
    if (preg_match('/^(\d{1,2})\D+(\d{1,2})(?:\D+\d{1,2})?$/', $trimmed, $matches) === 1) {
        $hours = max(0, min(23, (int) $matches[1]));
        $minutes = max(0, min(59, (int) $matches[2]));
        return str_pad((string) $hours, 2, '0', STR_PAD_LEFT) . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT);
    }

    $digits = preg_replace('/[^0-9]/', '', $trimmed) ?? '';
    if ($digits === '') {
        return '';
    }
    if (strlen($digits) <= 2) {
        return str_pad($digits, 2, '0', STR_PAD_LEFT) . '00';
    }

    return str_pad(substr($digits, 0, 4), 4, '0', STR_PAD_LEFT);
}

function qsl_normalize_comment(string $value): string
{
    $clean = trim(preg_replace('/\s+/', ' ', $value) ?? '');

    return mb_safe_substr($clean, 0, 180);
}

function qsl_normalize_qsl_status(string $value): string
{
    $normalized = mb_safe_strtoupper(trim($value));
    if ($normalized === '') {
        return '';
    }

    $allowed = ['Y', 'N', 'R', 'Q', 'I', 'V'];
    $status = mb_safe_substr($normalized, 0, 1);
    return in_array($status, $allowed, true) ? $status : '';
}

function parse_adif(string $content): array
{
    $rows = [];
    if (trim($content) === '') {
        return $rows;
    }

    preg_match_all('/<([A-Z0-9_]+):(\d+)[^>]*>(.*?)((?=<[A-Z0-9_]+:\d+)|<EOR>|$)/is', $content, $matches, PREG_SET_ORDER);

    $record = [];
    foreach ($matches as $match) {
        $field = strtolower((string) $match[1]);
        $length = (int) $match[2];
        $raw = (string) $match[3];
        $value = substr($raw, 0, $length);
        $value = trim($value);

        if ($field === 'call') {
            $record['call'] = qsl_normalize_callsign($value);
        } elseif ($field === 'qso_date') {
            $record['qso_date'] = qsl_normalize_date($value);
        } elseif ($field === 'time_on') {
            $record['time_on'] = qsl_normalize_time($value);
        } elseif ($field === 'band') {
            $record['band'] = mb_safe_strtoupper($value);
        } elseif ($field === 'mode') {
            $record['mode'] = mb_safe_strtoupper($value);
        } elseif ($field === 'rst_sent') {
            $record['rst_sent'] = mb_safe_substr(trim($value), 0, 16);
        } elseif ($field === 'rst_rcvd') {
            $record['rst_recv'] = mb_safe_substr(trim($value), 0, 16);
        } elseif ($field === 'comment') {
            $record['comment'] = qsl_normalize_comment($value);
        } elseif ($field === 'eqsl_qsl_sent') {
            $record['eqsl_qsl_sent'] = qsl_normalize_qsl_status($value);
        } elseif ($field === 'eqsl_qsl_rcvd') {
            $record['eqsl_qsl_rcvd'] = qsl_normalize_qsl_status($value);
        } elseif ($field === 'qsl_sent') {
            $record['qsl_sent'] = qsl_normalize_qsl_status($value);
        } elseif ($field === 'qsl_rcvd') {
            $record['qsl_rcvd'] = qsl_normalize_qsl_status($value);
        } elseif ($field === 'lotw_qsl_sent') {
            $record['lotw_qsl_sent'] = qsl_normalize_qsl_status($value);
        } elseif ($field === 'lotw_qsl_rcvd') {
            $record['lotw_qsl_rcvd'] = qsl_normalize_qsl_status($value);
        }

        if (stripos((string) $match[4], '<EOR>') !== false) {
            if (($record['call'] ?? '') !== '') {
                $rows[] = $record;
            }
            $record = [];
        }
    }

    if ($record !== [] && ($record['call'] ?? '') !== '') {
        $rows[] = $record;
    }

    return $rows;
}

function qsl_format_display_date(string $value): string
{
    $normalized = qsl_normalize_date($value);
    if ($normalized === '' || strlen($normalized) !== 8) {
        return trim($value);
    }

    return substr($normalized, 6, 2) . '/' . substr($normalized, 4, 2) . '/' . substr($normalized, 0, 4);
}

function qsl_format_display_time(string $value): string
{
    $normalized = qsl_normalize_time($value);
    if ($normalized === '' || strlen($normalized) !== 4) {
        return trim($value);
    }

    return substr($normalized, 0, 2) . ':' . substr($normalized, 2, 2);
}

function build_qsl_svg_payload(array $user, array $data, string $comment = ''): array
{
    $ownCall = qsl_normalize_callsign((string) ($data['own_call'] ?? ($user['callsign'] ?? '')));
    $ownName = trim((string) ($data['own_name'] ?? ($user['full_name'] ?? '')));
    $ownQth = trim((string) ($data['own_qth'] ?? ($user['qth'] ?? '')));
    $qsoCall = qsl_normalize_callsign((string) ($data['qso_call'] ?? ($data['call'] ?? '')));
    $qsoDate = qsl_normalize_date((string) ($data['qso_date'] ?? ''));
    $timeOn = qsl_normalize_time((string) ($data['time_on'] ?? ''));
    $band = mb_safe_strtoupper(trim((string) ($data['band'] ?? '')));
    $mode = mb_safe_strtoupper(trim((string) ($data['mode'] ?? '')));
    $rstSent = mb_safe_substr(trim((string) ($data['rst_sent'] ?? '')), 0, 16);
    $rstRecv = mb_safe_substr(trim((string) ($data['rst_recv'] ?? '')), 0, 16);
    $payloadComment = qsl_normalize_comment($comment !== '' ? $comment : (string) ($data['comment'] ?? 'TNX QSO 73'));
    $templateName = trim((string) ($data['template_name'] ?? 'classic'));
    if ($templateName === '') {
        $templateName = 'classic';
    }
    $backgroundImage = trim((string) ($data['background_image_data_uri'] ?? ''));
    $backgroundPrimary = trim((string) ($data['background_primary'] ?? '#0b1f3a'));
    $backgroundSecondary = trim((string) ($data['background_secondary'] ?? '#1d4ed8'));

    return [
        'title' => (string) ($data['title'] ?? ''),
        'own_call' => $ownCall,
        'own_name' => $ownName,
        'own_qth' => $ownQth,
        'qso_call' => $qsoCall,
        'qso_date' => $qsoDate,
        'time_on' => $timeOn,
        'band' => $band,
        'mode' => $mode,
        'rst_sent' => $rstSent,
        'rst_recv' => $rstRecv,
        'comment' => $payloadComment,
        'template_name' => $templateName,
        'background_image_data_uri' => $backgroundImage,
        'background_primary' => preg_match('/^#[a-f0-9]{6}$/i', $backgroundPrimary) === 1 ? strtoupper($backgroundPrimary) : '#0B1F3A',
        'background_secondary' => preg_match('/^#[a-f0-9]{6}$/i', $backgroundSecondary) === 1 ? strtoupper($backgroundSecondary) : '#1D4ED8',
    ];
}

function qsl_card_title(array $payload): string
{
    $call = qsl_normalize_callsign((string) ($payload['qso_call'] ?? ''));
    $date = qsl_normalize_date((string) ($payload['qso_date'] ?? ''));
    $band = mb_safe_strtoupper(trim((string) ($payload['band'] ?? '')));
    $mode = mb_safe_strtoupper(trim((string) ($payload['mode'] ?? '')));

    $chunks = ['QSL'];
    if ($call !== '') {
        $chunks[] = $call;
    }
    if ($date !== '') {
        $chunks[] = qsl_format_display_date($date);
    }
    if ($band !== '') {
        $chunks[] = $band;
    }
    if ($mode !== '') {
        $chunks[] = $mode;
    }

    return mb_safe_substr(implode(' • ', $chunks), 0, 190);
}

function import_adif_records(int $memberId, array $records): int
{
    if ($memberId <= 0 || $records === [] || !table_exists('qso_logs')) {
        return 0;
    }

    $existingStmt = db()->prepare(
        'SELECT id FROM qso_logs
         WHERE member_id = ? AND qso_call = ? AND COALESCE(qso_date, \'\') = ? AND COALESCE(time_on, \'\') = ?
         LIMIT 1'
    );
    $insertStmt = db()->prepare(
        'INSERT INTO qso_logs (member_id, qso_call, qso_date, time_on, band, mode, rst_sent, rst_recv, raw_payload)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $created = 0;
    foreach ($records as $row) {
        if (!is_array($row)) {
            continue;
        }

        $payload = build_qsl_svg_payload([], [
            'qso_call' => (string) ($row['call'] ?? ''),
            'qso_date' => (string) ($row['qso_date'] ?? ''),
            'time_on' => (string) ($row['time_on'] ?? ''),
            'band' => (string) ($row['band'] ?? ''),
            'mode' => (string) ($row['mode'] ?? ''),
            'rst_sent' => (string) ($row['rst_sent'] ?? ''),
            'rst_recv' => (string) ($row['rst_recv'] ?? ''),
            'comment' => (string) ($row['comment'] ?? ''),
        ]);

        if ($payload['qso_call'] === '') {
            continue;
        }

        $existingStmt->execute([$memberId, $payload['qso_call'], $payload['qso_date'], $payload['time_on']]);
        if ($existingStmt->fetchColumn()) {
            continue;
        }

        $insertStmt->execute([
            $memberId,
            $payload['qso_call'],
            $payload['qso_date'] !== '' ? $payload['qso_date'] : null,
            $payload['time_on'] !== '' ? $payload['time_on'] : null,
            $payload['band'] !== '' ? $payload['band'] : null,
            $payload['mode'] !== '' ? $payload['mode'] : null,
            $payload['rst_sent'] !== '' ? $payload['rst_sent'] : null,
            $payload['rst_recv'] !== '' ? $payload['rst_recv'] : null,
            json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $created++;
    }

    return $created;
}

function create_qsl_cards_from_qsos(int $memberId, array $qsoIds, string $templateName = 'classic'): int
{
    if ($memberId <= 0 || $qsoIds === [] || !table_exists('qso_logs') || !table_exists('qsl_cards')) {
        return 0;
    }
    $normalizedTemplate = strtolower(trim($templateName));
    if (!in_array($normalizedTemplate, ['classic', 'classic_duplex'], true)) {
        $normalizedTemplate = 'classic';
    }

    $ids = array_values(array_unique(array_filter(array_map('intval', $qsoIds), static fn (int $id): bool => $id > 0)));
    if ($ids === []) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge([$memberId], $ids);
    $qsoStmt = db()->prepare(
        "SELECT id, qso_call, qso_date, time_on, band, mode, rst_sent, rst_recv
         FROM qso_logs
         WHERE member_id = ? AND id IN ($placeholders)
         ORDER BY id DESC"
    );
    $qsoStmt->execute($params);
    $rows = $qsoStmt->fetchAll();
    if (!is_array($rows) || $rows === []) {
        return 0;
    }

    $memberStmt = db()->prepare('SELECT id, username, email, callsign, full_name, qth FROM users WHERE id = ? LIMIT 1');
    $memberStmt->execute([$memberId]);
    $member = $memberStmt->fetch();
    if (!is_array($member)) {
        $member = ['id' => $memberId, 'callsign' => '', 'full_name' => '', 'qth' => ''];
    }

    $existsStmt = db()->prepare(
        'SELECT id FROM qsl_cards
         WHERE member_id = ? AND qso_call = ? AND COALESCE(qso_date, \'\') = ? AND COALESCE(time_on, \'\') = ?
         LIMIT 1'
    );
    $insertStmt = db()->prepare(
        'INSERT INTO qsl_cards (member_id, title, qso_call, qso_date, time_on, band, mode, rst_sent, rst_recv, template_name, svg_content)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $created = 0;
    foreach ($rows as $row) {
        $payload = build_qsl_svg_payload($member, [
            'qso_call' => (string) ($row['qso_call'] ?? ''),
            'qso_date' => (string) ($row['qso_date'] ?? ''),
            'time_on' => (string) ($row['time_on'] ?? ''),
            'band' => (string) ($row['band'] ?? ''),
            'mode' => (string) ($row['mode'] ?? ''),
            'rst_sent' => (string) ($row['rst_sent'] ?? ''),
            'rst_recv' => (string) ($row['rst_recv'] ?? ''),
            'comment' => 'TNX QSO 73',
        ]);
        if ($payload['qso_call'] === '') {
            continue;
        }

        $existsStmt->execute([$memberId, $payload['qso_call'], $payload['qso_date'], $payload['time_on']]);
        if ($existsStmt->fetchColumn()) {
            continue;
        }

        $svg = generate_qsl_svg($payload);
        $insertStmt->execute([
            $memberId,
            qsl_card_title($payload),
            $payload['qso_call'],
            $payload['qso_date'] !== '' ? $payload['qso_date'] : null,
            $payload['time_on'] !== '' ? $payload['time_on'] : null,
            $payload['band'] !== '' ? $payload['band'] : null,
            $payload['mode'] !== '' ? $payload['mode'] : null,
            $payload['rst_sent'] !== '' ? $payload['rst_sent'] : null,
            $payload['rst_recv'] !== '' ? $payload['rst_recv'] : null,
            $normalizedTemplate,
            $svg,
        ]);
        $created++;
    }

    return $created;
}

function qsl_template_supports_back(string $templateName): bool
{
    return strtolower(trim($templateName)) === 'classic_duplex';
}

function sanitize_svg_document(string $svg): string
{
    $locale = current_locale();
    $qslUnavailableLabel = match ($locale) {
        'en' => 'Secure QSL unavailable',
        'de' => 'Sichere QSL nicht verfügbar',
        'nl' => 'Beveiligde QSL niet beschikbaar',
        'es' => 'QSL segura no disponible',
        'it' => 'QSL sicura non disponibile',
        'pt' => 'QSL segura indisponível',
        'ar' => 'QSL الآمنة غير متاحة',
        'hi' => 'सुरक्षित QSL उपलब्ध नहीं है',
        'ja' => '安全なQSLは利用できません',
        'zh' => '安全QSL不可用',
        'bn' => 'নিরাপদ QSL উপলভ্য নয়',
        'ru' => 'Безопасная QSL недоступна',
        'id' => 'QSL aman tidak tersedia',
        default => 'QSL sécurisée indisponible',
    };
    $safeFallbackSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="500" viewBox="0 0 900 500"><rect width="900" height="500" fill="#0f172a"/><text x="450" y="250" text-anchor="middle" fill="#f8fafc" font-family="Arial, sans-serif" font-size="28">' . e($qslUnavailableLabel) . '</text></svg>';

    $normalized = strtolower($svg);
    $dangerousPatterns = [
        '/<\s*script\b/i',
        '/<\s*(iframe|object|embed|foreignobject)\b/i',
        '/\s+on[a-z0-9:_-]+\s*=/i',
        '/(?:href|xlink:href)\s*=\s*["\']?\s*javascript:/i',
        '/style\s*=\s*["\'][^"\']*url\s*\(/i',
    ];

    foreach ($dangerousPatterns as $pattern) {
        if (preg_match($pattern, $svg) === 1) {
            return $safeFallbackSvg;
        }
    }

    if (str_contains($normalized, 'javascript:')) {
        return $safeFallbackSvg;
    }

    if (preg_match_all('/(?:href|xlink:href)\s*=\s*["\']([^"\']+)["\']/i', $svg, $matches) > 0 && isset($matches[1])) {
        foreach ($matches[1] as $href) {
            $candidate = strtolower(trim((string) $href));
            if (str_starts_with($candidate, 'data:image/')) {
                if (preg_match('/^data:image\/(?:png|jpe?g|webp);base64,[a-z0-9+\/=]+$/i', $candidate) !== 1) {
                    return $safeFallbackSvg;
                }
            } elseif (str_starts_with($candidate, 'data:')) {
                return $safeFallbackSvg;
            }
        }
    }

    return $svg;
}

function generate_qsl_svg(array $payload): string
{
    $ownCall = e(qsl_normalize_callsign((string) ($payload['own_call'] ?? '')));
    $qsoCall = e(qsl_normalize_callsign((string) ($payload['qso_call'] ?? '')));
    $ownName = e(trim((string) ($payload['own_name'] ?? '')));
    $ownQth = e(trim((string) ($payload['own_qth'] ?? '')));
    $date = e(qsl_normalize_date((string) ($payload['qso_date'] ?? '')));
    $time = e(qsl_normalize_time((string) ($payload['time_on'] ?? '')));
    $band = e(mb_safe_strtoupper(trim((string) ($payload['band'] ?? ''))));
    $mode = e(mb_safe_strtoupper(trim((string) ($payload['mode'] ?? ''))));
    $rstSent = e(trim((string) ($payload['rst_sent'] ?? '')));
    $rstRecv = e(trim((string) ($payload['rst_recv'] ?? '')));
    $comment = e(qsl_normalize_comment((string) ($payload['comment'] ?? 'TNX QSO 73')));
    $title = e(trim((string) ($payload['title'] ?? 'QSL Card')));
    $backgroundPrimary = e(trim((string) ($payload['background_primary'] ?? '#0B1F3A')));
    $backgroundSecondary = e(trim((string) ($payload['background_secondary'] ?? '#1D4ED8')));
    $backgroundImage = trim((string) ($payload['background_image_data_uri'] ?? ''));
    $templateName = trim((string) ($payload['template_name'] ?? 'classic'));
    $isDuplex = qsl_template_supports_back($templateName);
    $backgroundLayer = '<rect width="900" height="500" fill="url(#qsl-bg-gradient)"/>';
    if ($backgroundImage !== '') {
        $safeBackground = e($backgroundImage);
        $backgroundLayer = '<image href="' . $safeBackground . '" x="0" y="0" width="900" height="500" preserveAspectRatio="xMidYMid slice"/>'
            . '<rect width="900" height="500" fill="rgba(8, 15, 32, .38)"/>';
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="500" viewBox="0 0 900 500">'
        . '<defs><linearGradient id="qsl-bg-gradient" x1="0%" y1="0%" x2="100%" y2="100%">'
        . '<stop offset="0%" stop-color="' . $backgroundPrimary . '"/>'
        . '<stop offset="100%" stop-color="' . $backgroundSecondary . '"/>'
        . '</linearGradient></defs>'
        . $backgroundLayer
        . '<text x="40" y="70" fill="#e2e8f0" font-size="42" font-family="Arial, sans-serif" font-weight="700">' . $title . '</text>'
        . '<text x="40" y="130" fill="#f8fafc" font-size="30" font-family="Arial, sans-serif">DE: ' . $ownCall . '</text>'
        . '<text x="40" y="170" fill="#cbd5e1" font-size="22" font-family="Arial, sans-serif">' . $ownName . ' • ' . $ownQth . '</text>'
        . '<text x="40" y="250" fill="#f8fafc" font-size="34" font-family="Arial, sans-serif">TO: ' . $qsoCall . '</text>';

    if ($isDuplex) {
        $svg .= '<text x="40" y="395" fill="#e2e8f0" font-size="20" font-family="Arial, sans-serif">QSL recto — détails au verso</text>';
    } else {
        $svg .= '<text x="40" y="305" fill="#cbd5e1" font-size="22" font-family="Arial, sans-serif">DATE ' . $date . '  UTC ' . $time . '  BAND ' . $band . '  MODE ' . $mode . '</text>'
            . '<text x="40" y="345" fill="#cbd5e1" font-size="22" font-family="Arial, sans-serif">RST S/R: ' . $rstSent . ' / ' . $rstRecv . '</text>'
            . '<text x="40" y="395" fill="#f8fafc" font-size="20" font-family="Arial, sans-serif">' . $comment . '</text>';
    }

    $svg .= '</svg>';

    return sanitize_svg_document($svg);
}

function generate_qsl_back_svg(array $payload): string
{
    $ownCall = e(qsl_normalize_callsign((string) ($payload['own_call'] ?? '')));
    $qsoCall = e(qsl_normalize_callsign((string) ($payload['qso_call'] ?? '')));
    $ownName = e(trim((string) ($payload['own_name'] ?? '')));
    $ownQth = e(trim((string) ($payload['own_qth'] ?? '')));
    $date = e(qsl_normalize_date((string) ($payload['qso_date'] ?? '')));
    $time = e(qsl_normalize_time((string) ($payload['time_on'] ?? '')));
    $band = e(mb_safe_strtoupper(trim((string) ($payload['band'] ?? ''))));
    $mode = e(mb_safe_strtoupper(trim((string) ($payload['mode'] ?? ''))));
    $rstSent = e(trim((string) ($payload['rst_sent'] ?? '')));
    $rstRecv = e(trim((string) ($payload['rst_recv'] ?? '')));
    $comment = e(qsl_normalize_comment((string) ($payload['comment'] ?? 'TNX QSO 73')));

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="500" viewBox="0 0 900 500">'
        . '<rect width="900" height="500" fill="#f8fafc"/>'
        . '<rect x="18" y="18" width="864" height="464" fill="none" stroke="#1f2937" stroke-width="3"/>'
        . '<text x="40" y="70" fill="#0f172a" font-size="40" font-family="Arial, sans-serif" font-weight="700">QSL Confirmation (Verso)</text>'
        . '<text x="40" y="115" fill="#334155" font-size="20" font-family="Arial, sans-serif">DE: ' . $ownCall . ' • TO: ' . $qsoCall . '</text>'
        . '<text x="40" y="165" fill="#0f172a" font-size="22" font-family="Arial, sans-serif">Operator: ' . $ownName . '</text>'
        . '<text x="40" y="200" fill="#0f172a" font-size="22" font-family="Arial, sans-serif">QTH: ' . $ownQth . '</text>'
        . '<text x="40" y="250" fill="#0f172a" font-size="22" font-family="Arial, sans-serif">Date: ' . $date . '    UTC: ' . $time . '</text>'
        . '<text x="40" y="285" fill="#0f172a" font-size="22" font-family="Arial, sans-serif">Band: ' . $band . '    Mode: ' . $mode . '</text>'
        . '<text x="40" y="320" fill="#0f172a" font-size="22" font-family="Arial, sans-serif">RST S/R: ' . $rstSent . ' / ' . $rstRecv . '</text>'
        . '<text x="40" y="370" fill="#334155" font-size="20" font-family="Arial, sans-serif">' . $comment . '</text>'
        . '<text x="40" y="440" fill="#475569" font-size="18" font-family="Arial, sans-serif">Merci pour le contact — 73 !</text>'
        . '</svg>';

    return sanitize_svg_document($svg);
}

function qsl_background_upload_to_data_uri(?array $upload): string
{
    if (!is_array($upload)) {
        return '';
    }
    $errorCode = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($errorCode !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_i18n_message('qsl_bg_upload_failed'));
    }

    $tmpPath = (string) ($upload['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException(upload_i18n_message('qsl_bg_invalid'));
    }

    $mime = detect_uploaded_mime_type($tmpPath);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedMimes, true)) {
        throw new RuntimeException(upload_i18n_message('qsl_bg_not_supported'));
    }
    $size = (int) ($upload['size'] ?? 0);
    if ($size <= 0 || $size > 6 * 1024 * 1024) {
        throw new RuntimeException(upload_i18n_message('qsl_bg_too_large'));
    }

    $extension = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'jpg',
    };
    assert_upload_file_is_valid_signature($tmpPath, [$extension]);
    $sanitizedTmpPath = sanitize_uploaded_image_file($tmpPath, $extension);
    $raw = @file_get_contents($sanitizedTmpPath);
    if ($sanitizedTmpPath !== $tmpPath) {
        @unlink($sanitizedTmpPath);
    }
    if ($raw === false) {
        throw new RuntimeException(upload_i18n_message('qsl_bg_unreadable'));
    }

    return 'data:' . $mime . ';base64,' . base64_encode($raw);
}
