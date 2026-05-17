<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['not_found' => 'Publicité introuvable', 'title' => 'Publicité'],
    'en' => ['not_found' => 'Ad not found', 'title' => 'Advertisement'],
    'de' => ['not_found' => 'Anzeige nicht gefunden', 'title' => 'Anzeige'],
    'es' => ['not_found' => 'Publicidad no encontrada', 'title' => 'Publicidad'],
    'it' => ['not_found' => 'Pubblicità non trovata', 'title' => 'Pubblicità'],
    'pt' => ['not_found' => 'Publicidade não encontrada', 'title' => 'Publicidade'],
    'nl' => ['not_found' => 'Advertentie niet gevonden', 'title' => 'Advertentie'],
];
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $pool = [];
    foreach ($i18n as $lang => $translations) {
        if (isset($translations[$key]) && is_string($translations[$key])) {
            $pool[$lang] = $translations[$key];
        }
    }
    $t[$key] = i18n_localized_value($pool, $locale, 'fr');
}

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
