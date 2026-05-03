<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['not_found' => 'Publicité introuvable', 'title' => 'Publicité'],
    'en' => ['not_found' => 'Ad not found', 'title' => 'Advertisement'],
    'de' => ['not_found' => 'Anzeige nicht gefunden', 'title' => 'Anzeige'],
    'nl' => ['not_found' => 'Advertentie niet gevonden', 'title' => 'Advertentie'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

$adId = (int) ($_GET['id'] ?? 0);
$ad = ad_fetch_by_id($adId);
if ($ad === null) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e((string) $t['not_found']) . '</h1></div>', (string) $t['title']);
    return;
}

log_ad_event((int) $ad['id'], 'click', (string) $ad['placement_code']);
try {
    $target = validate_outbound_url((string) ($ad['target_url'] ?? ''));
} catch (Throwable) {
    $target = null;
}

if ($target === null) {
    redirect('home');
}

redirect_url($target);
