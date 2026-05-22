<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$t = i18n_domain_translator('auction_bid', $locale);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit($t('method_not_allowed'));
}

try {
    verify_csrf();
    $lotId = (int) ($_POST['lot_id'] ?? 0);
    $amountCents = parse_price_to_cents((string) ($_POST['amount'] ?? '0'));
    place_auction_bid($lotId, (int) $user['id'], $amountCents);
    $lot = auction_lot_by_id($lotId);
    set_flash('success', $t('bid_saved'));
    redirect_url($lot ? route_url('auction_view', ['slug' => (string) $lot['slug']]) : route_url('auctions'));
} catch (Throwable $throwable) {
    set_flash('error', $throwable->getMessage());
    $lot = auction_lot_by_id((int) ($_POST['lot_id'] ?? 0));
    redirect_url($lot ? route_url('auction_view', ['slug' => (string) $lot['slug']]) : route_url('auctions'));
}
