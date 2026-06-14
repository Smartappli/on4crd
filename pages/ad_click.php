<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/ad_click.php');
$i18n = i18n_expand_supported_locales($i18n);
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
