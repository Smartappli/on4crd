<?php
declare(strict_types=1);

$adId = (int) ($_GET['id'] ?? 0);
$ad = ad_fetch_by_id($adId);
if ($ad === null) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>Publicité introuvable</h1></div>', 'Publicité');
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
