<?php
declare(strict_types=1);

$lots = cache_remember('auction_public_lots_60_v1', 60, static fn(): array => auction_public_lots(60));
$locale = current_locale();
$t = i18n_domain_locale('auctions', $locale);
set_page_meta([
    'title' => (string) $t['meta_title'],
    'description' => (string) $t['meta_desc'],
]);

$groupedLots = [
    'active' => [],
    'scheduled' => [],
    'closed' => [],
];
$perPage = 9;

foreach ($lots as $lot) {
    $runtime = auction_runtime_status($lot);
    if (isset($groupedLots[$runtime])) {
        $groupedLots[$runtime][] = $lot;
    }
}

$sectionPages = [
    'active' => max(1, (int) ($_GET['active_page'] ?? 1)),
    'scheduled' => max(1, (int) ($_GET['scheduled_page'] ?? 1)),
    'closed' => max(1, (int) ($_GET['closed_page'] ?? 1)),
];
$sectionMaxPages = [];
$pagedGroupedLots = [];
foreach ($groupedLots as $status => $items) {
    $pagination = pagination_state(count($items), $sectionPages[$status], $perPage);
    $sectionPages[$status] = $pagination['page'];
    $sectionMaxPages[$status] = $pagination['total_pages'];
    $pagedGroupedLots[$status] = array_slice($items, $pagination['offset'], $perPage);
}

$sections = [
    'active' => ['title' => (string) $t['active_title'], 'empty' => (string) $t['active_empty']],
    'scheduled' => ['title' => (string) $t['scheduled_title'], 'empty' => (string) $t['scheduled_empty']],
    'closed' => ['title' => (string) $t['closed_title'], 'empty' => (string) $t['closed_empty']],
];
$totalLots = count($lots);
$formatAuctionDate = static function (mixed $value): string {
    $timestamp = strtotime((string) $value);
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : '';
};

ob_start();
?>
<section class="auctions-page">
    <header class="auctions-hero">
        <div class="auctions-hero-copy">
            <p class="directory-eyebrow"><?= e((string) $t['meta_title']) ?></p>
            <h1><?= e((string) $t['meta_title']) ?></h1>
            <p class="directory-lead"><?= e((string) $t['meta_desc']) ?></p>
        </div>
        <div class="auctions-stats">
            <div class="auctions-stat">
                <span><?= (int) count($groupedLots['active']) ?></span>
                <p><?= e((string) $t['active_title']) ?></p>
            </div>
            <div class="auctions-stat">
                <span><?= (int) count($groupedLots['scheduled']) ?></span>
                <p><?= e((string) $t['scheduled_title']) ?></p>
            </div>
            <div class="auctions-stat">
                <span><?= (int) $totalLots ?></span>
                <p><?= e((string) $t['lots']) ?></p>
            </div>
        </div>
    </header>

    <?php foreach ($sections as $status => $meta): ?>
        <section class="auctions-section auctions-section-<?= e($status) ?>">
            <div class="auctions-section-header">
                <div>
                    <h2><?= e($meta['title']) ?></h2>
                    <p class="help"><?= count($groupedLots[$status]) ?> <?= count($groupedLots[$status]) > 1 ? e((string) $t['lots']) : e((string) $t['lot']) ?></p>
                </div>
                <span class="badge"><?= count($groupedLots[$status]) ?> <?= count($groupedLots[$status]) > 1 ? e((string) $t['lots']) : e((string) $t['lot']) ?></span>
            </div>
            <?php if ($groupedLots[$status] === []): ?>
                <div class="auctions-empty"><h3><?= e($meta['empty']) ?></h3></div>
            <?php else: ?>
                <div class="auctions-grid">
                    <?php foreach ($pagedGroupedLots[$status] as $lot): ?>
                        <?php
                        $lotTitle = trim((string) ($lot['title'] ?? ''));
                        $lotSummary = trim((string) ($lot['summary'] ?? ''));
                        $lotPrice = max((int) ($lot['current_price_cents'] ?? 0), (int) ($lot['starting_price_cents'] ?? 0));
                        ?>
                        <article class="auction-lot-card">
                            <div class="auction-lot-top">
                                <span class="badge muted"><?= e($meta['title']) ?></span>
                                <strong class="price-tag"><?= e(format_price_eur($lotPrice)) ?></strong>
                            </div>
                            <h3><?= e($lotTitle !== '' ? $lotTitle : (string) $t['lot']) ?></h3>
                            <p><?= e($lotSummary !== '' ? $lotSummary : (string) $t['default_summary']) ?></p>
                            <dl class="auction-lot-meta">
                                <div>
                                    <dt><?= e((string) $t['start']) ?></dt>
                                    <dd><?= e($formatAuctionDate($lot['starts_at'] ?? '')) ?></dd>
                                </div>
                                <div>
                                    <dt><?= e((string) $t['end']) ?></dt>
                                    <dd><?= e($formatAuctionDate($lot['ends_at'] ?? '')) ?></dd>
                                </div>
                                <div>
                                    <dt><?= e((string) $t['step']) ?></dt>
                                    <dd><?= e(format_price_eur((int) ($lot['min_increment_cents'] ?? 0))) ?></dd>
                                </div>
                            </dl>
                            <div class="auction-lot-actions">
                                <a class="button" href="<?= e(route_url('auction_view', ['slug' => (string) $lot['slug']])) ?>"><?= e((string) $t['view_lot']) ?></a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if ($sectionMaxPages[$status] > 1): ?>
                    <div class="auctions-pagination">
                        <?php if ($sectionPages[$status] > 1): ?>
                            <a class="button secondary" href="<?= e(route_url_clean('auctions', $sectionPages + [$status . '_page' => $sectionPages[$status] - 1])) ?>"><?= e((string) $t['previous']) ?></a>
                        <?php endif; ?>
                        <span class="pill"><?= e((string) $t['page']) ?> <?= $sectionPages[$status] ?> / <?= $sectionMaxPages[$status] ?></span>
                        <?php if ($sectionPages[$status] < $sectionMaxPages[$status]): ?>
                            <a class="button secondary" href="<?= e(route_url_clean('auctions', $sectionPages + [$status . '_page' => $sectionPages[$status] + 1])) ?>"><?= e((string) $t['next']) ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['meta_title']);
