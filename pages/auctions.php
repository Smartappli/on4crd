<?php
declare(strict_types=1);

$lots = cache_remember('auction_public_lots_60_v1', 60, static fn(): array => auction_public_lots(60));
$locale = current_locale();
$i18n = i18n_domain_messages('auctions');
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
    $max = max(1, (int) ceil(count($items) / $perPage));
    if ($sectionPages[$status] > $max) {
        $sectionPages[$status] = $max;
    }
    $sectionMaxPages[$status] = $max;
    $pagedGroupedLots[$status] = array_slice($items, ($sectionPages[$status] - 1) * $perPage, $perPage);
}

$sections = [
    'active' => ['title' => (string) $t['active_title'], 'empty' => (string) $t['active_empty']],
    'scheduled' => ['title' => (string) $t['scheduled_title'], 'empty' => (string) $t['scheduled_empty']],
    'closed' => ['title' => (string) $t['closed_title'], 'empty' => (string) $t['closed_empty']],
];

ob_start();
?>
<section class="stack auctions-page">
    <?php foreach ($sections as $status => $meta): ?>
        <section class="inner-card auctions-section auctions-section-<?= e($status) ?>">
            <div class="section-header">
                <h1><?= e($meta['title']) ?></h1>
                <span class="badge"><?= count($groupedLots[$status]) ?> <?= count($groupedLots[$status]) > 1 ? e((string) $t['lots']) : e((string) $t['lot']) ?></span>
            </div>
            <?php if ($groupedLots[$status] === []): ?>
                <div class="card empty-state"><p><?= e($meta['empty']) ?></p></div>
            <?php else: ?>
                <div class="grid-3">
                    <?php foreach ($pagedGroupedLots[$status] as $lot): ?>
                        <article class="card feature-card auction-lot-card">
                            <div class="section-header">
                                <h2><?= e((string) $lot['title']) ?></h2>
                                <strong class="price-tag"><?= e(format_price_eur(max((int) $lot['current_price_cents'], (int) $lot['starting_price_cents']))) ?></strong>
                            </div>
                            <p><?= e((string) ($lot['summary'] ?: (string) $t['default_summary'])) ?></p>
                            <ul class="list-clean list-spaced">
                                <li><span class="help"><?= e((string) $t['start']) ?> : <?= e(date('d/m/Y H:i', strtotime((string) $lot['starts_at']))) ?></span></li>
                                <li><span class="help"><?= e((string) $t['end']) ?> : <?= e(date('d/m/Y H:i', strtotime((string) $lot['ends_at']))) ?></span></li>
                                <li><span class="help"><?= e((string) $t['step']) ?> : <?= e(format_price_eur((int) $lot['min_increment_cents'])) ?></span></li>
                            </ul>
                            <div class="actions">
                                <a class="button" href="<?= e(route_url('auction_view', ['slug' => (string) $lot['slug']])) ?>"><?= e((string) $t['view_lot']) ?></a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if ($sectionMaxPages[$status] > 1): ?>
                    <div class="actions mt-3">
                        <?php if ($sectionPages[$status] > 1): ?>
                            <a class="button secondary" href="<?= e(route_url('auctions', $sectionPages + [$status . '_page' => $sectionPages[$status] - 1])) ?>"><?= e((string) $t['previous']) ?></a>
                        <?php endif; ?>
                        <span class="pill"><?= e((string) $t['page']) ?> <?= $sectionPages[$status] ?> / <?= $sectionMaxPages[$status] ?></span>
                        <?php if ($sectionPages[$status] < $sectionMaxPages[$status]): ?>
                            <a class="button secondary" href="<?= e(route_url('auctions', $sectionPages + [$status . '_page' => $sectionPages[$status] + 1])) ?>"><?= e((string) $t['next']) ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['meta_title']);
