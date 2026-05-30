<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('auction_view', $locale);

$slug = trim((string) ($_GET['slug'] ?? ''));
$lot = $slug !== '' ? auction_lot_by_slug($slug) : null;
if ($lot === null) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e((string) $t['lot_not_found']) . '</h1><p>' . e((string) $t['lot_not_exists']) . '</p></div>', (string) $t['lot_not_found']);
    return;
}

if (in_array((string) ($lot['status'] ?? ''), ['draft', 'cancelled'], true) && !has_permission('auctions.manage')) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e((string) $t['lot_not_found']) . '</h1><p>' . e((string) $t['lot_not_public']) . '</p></div>', (string) $t['lot_not_found']);
    return;
}

$runtime = auction_runtime_status($lot);
$bids = auction_bids_for_lot((int) $lot['id'], 20);
$minimumBid = auction_minimum_bid_cents($lot);
$reserveCents = (int) ($lot['reserve_price_cents'] ?? 0);
$displayPriceCents = max((int) $lot['current_price_cents'], (int) $lot['starting_price_cents']);
$reserveReached = auction_reserve_met($lot, $displayPriceCents);
$auctionUrl = route_url_with_locale('auction_view', $locale, ['slug' => (string) $lot['slug']]);
$auctionDescription = (string) ($lot['summary'] ?: (string) $t['default_desc']);
set_page_meta([
    'title' => (string) $lot['title'],
    'description' => $auctionDescription,
    'canonical' => $auctionUrl,
    'schema_type' => 'Product',
    'modified_time' => !empty($lot['updated_at']) ? date('c', strtotime((string) $lot['updated_at'])) : null,
    'json_ld' => [
        [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => (string) $lot['title'],
            'description' => $auctionDescription,
            'url' => $auctionUrl,
            'category' => 'amateur radio equipment',
            'brand' => [
                '@type' => 'Brand',
                'name' => 'ON4CRD',
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $auctionUrl,
                'priceCurrency' => 'EUR',
                'price' => number_format($displayPriceCents / 100, 2, '.', ''),
                'availability' => $runtime === 'closed' ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock',
                'availabilityEnds' => !empty($lot['ends_at']) ? date('c', strtotime((string) $lot['ends_at'])) : null,
                'seller' => [
                    '@type' => 'Organization',
                    'name' => 'Radio Club Durnal ON4CRD',
                    'url' => route_url_with_locale('home', $locale),
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $auctionUrl,
            ],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'ON4CRD',
                    'item' => route_url_with_locale('home', $locale),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => (string) $t['bid'],
                    'item' => route_url_with_locale('auctions', $locale),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => (string) $lot['title'],
                    'item' => $auctionUrl,
                ],
            ],
        ],
    ],
]);

ob_start();
?>
<div class="split auction-detail-layout">
    <article class="card auction-detail-main">
        <div class="badge <?= $runtime === 'closed' ? 'muted' : '' ?>"><?= e(auction_status_label($runtime)) ?></div>
        <h1><?= e((string) $lot['title']) ?></h1>
        <p class="hero-lead"><?= e((string) ($lot['summary'] ?: (string) $t['default_summary'])) ?></p>
        <div class="catalog auction-meta-list">
            <span class="pill"><?= e((string) $t['current_price']) ?> <?= e(format_price_eur($displayPriceCents)) ?></span>
            <span class="pill"><?= e((string) $t['min_step']) ?> <?= e(format_price_eur((int) $lot['min_increment_cents'])) ?></span>
            <span class="pill"><?= e((string) $t['end']) ?> <?= e(date('d/m/Y H:i', strtotime((string) $lot['ends_at']))) ?></span>
            <?php if ($reserveCents > 0): ?>
                <span class="pill"><?= e((string) $t['reserve']) ?> <?= e(format_price_eur($reserveCents)) ?> (<?= e((string) ($reserveReached ? $t['reserve_met'] : $t['reserve_not_met'])) ?>)</span>
            <?php endif; ?>
        </div>
        <div class="inner-card"><?= sanitize_rich_html((string) ($lot['description'] ?? '')) ?></div>
        <?php if ($runtime === 'closed' && !empty($lot['winner_callsign'])): ?>
            <p><strong><?= e((string) $t['provisional_winner']) ?></strong> <?= e((string) $lot['winner_callsign']) ?></p>
        <?php endif; ?>
    </article>
    <aside class="card auction-detail-side">
        <h2><?= e((string) $t['bid']) ?></h2>
        <?php if ($runtime !== 'active'): ?>
            <p class="help"><?= e((string) $t['inactive']) ?></p>
        <?php elseif (!current_user()): ?>
            <p class="help"><?= e((string) $t['login_needed']) ?></p>
            <p><a class="button" href="<?= e(route_url('login')) ?>"><?= e((string) $t['member_login']) ?></a></p>
        <?php else: ?>
            <form method="post" action="<?= e(route_url('auction_bid')) ?>" class="stack">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="lot_id" value="<?= (int) $lot['id'] ?>">
                <label><?= e((string) $t['your_bid']) ?>
                    <input type="text" name="amount" value="<?= e(number_format($minimumBid / 100, 2, ',', '')) ?>">
                </label>
                <p class="help"><?= e((string) $t['min_offer']) ?> <?= e(format_price_eur($minimumBid)) ?></p>
                <button class="button"><?= e((string) $t['place_bid']) ?></button>
            </form>
        <?php endif; ?>

        <div class="inner-card">
            <h3><?= e((string) $t['history']) ?></h3>
            <?php if ($bids === []): ?>
                <p class="help"><?= e((string) $t['no_bids']) ?></p>
            <?php else: ?>
                <ul class="list-clean list-spaced">
                    <?php foreach ($bids as $bid): ?>
                        <li><strong><?= e((string) $bid['callsign']) ?></strong><span class="help"><?= e(format_price_eur((int) $bid['amount_cents'])) ?> — <?= e(date('d/m/Y H:i', strtotime((string) $bid['created_at']))) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </aside>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $lot['title']);
