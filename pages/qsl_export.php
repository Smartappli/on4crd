<?php
declare(strict_types=1);

$user = require_login();
$id = (int) ($_GET['id'] ?? 0);
$side = ((string) ($_GET['side'] ?? 'front')) === 'back' ? 'back' : 'front';
$stmt = db()->prepare('SELECT title, qso_call, qso_date, time_on, band, mode, rst_sent, rst_recv, template_name, svg_content FROM qsl_cards WHERE id = ? AND member_id = ?');
$stmt->execute([$id, (int) $user['id']]);
$row = $stmt->fetch();
if (!$row) {
    http_response_code(404);
    exit('QSL introuvable');
}
$filename = slugify((string) ($row['title'] ?? 'qsl-card')) ?: 'qsl-card';
$isDownload = ((string) ($_GET['download'] ?? '') === '1');
$templateName = (string) ($row['template_name'] ?? 'classic');
$svgContent = (string) ($row['svg_content'] ?? '');
if ($side === 'back') {
    if (!qsl_template_supports_back($templateName)) {
        http_response_code(400);
        exit('Cette QSL est disponible en recto uniquement');
    }
    $payload = build_qsl_svg_payload($user, [
        'qso_call' => (string) ($row['qso_call'] ?? ''),
        'qso_date' => (string) ($row['qso_date'] ?? ''),
        'time_on' => (string) ($row['time_on'] ?? ''),
        'band' => (string) ($row['band'] ?? ''),
        'mode' => (string) ($row['mode'] ?? ''),
        'rst_sent' => (string) ($row['rst_sent'] ?? ''),
        'rst_recv' => (string) ($row['rst_recv'] ?? ''),
        'comment' => 'TNX QSO 73',
    ]);
    $svgContent = generate_qsl_back_svg($payload);
    $filename .= '-verso';
}
header('Content-Type: image/svg+xml; charset=utf-8');
header('Content-Disposition: ' . ($isDownload ? 'attachment' : 'inline') . '; filename="' . $filename . '.svg"');
echo sanitize_svg_document($svgContent);
