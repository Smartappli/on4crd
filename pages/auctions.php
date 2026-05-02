<?php
declare(strict_types=1);

$lots = cache_remember('auction_public_lots_60_v1', 60, static fn(): array => auction_public_lots(60));
set_page_meta([
    'title' => 'Enchères',
    'description' => 'Enchères en cours, à venir et terminées.',
]);

$groupedLots = [
    'active' => [],
    'scheduled' => [],
    'closed' => [],
];

foreach ($lots as $lot) {
    $runtime = auction_runtime_status($lot);
    if (isset($groupedLots[$runtime])) {
        $groupedLots[$runtime][] = $lot;
    }
}

$sections = [
    'active' => ['title' => 'Enchères en cours', 'empty' => 'Aucune enchère en cours.'],
    'scheduled' => ['title' => 'Enchères à venir', 'empty' => 'Aucune enchère à venir.'],
    'closed' => ['title' => 'Enchères terminées', 'empty' => 'Aucune enchère terminée.'],
];

ob_start();
?>
<section class="stack auctions-page">
    <?php foreach ($sections as $status => $meta): ?>
        <section class="inner-card auctions-section auctions-section-<?= e($status) ?>">
            <div class="section-header">
                <h1><?= e($meta['title']) ?></h1>
                <span class="badge"><?= count($groupedLots[$status]) ?> lot<?= count($groupedLots[$status]) > 1 ? 's' : '' ?></span>
            </div>
            <?php if ($groupedLots[$status] === []): ?>
                <div class="card empty-state"><p><?= e($meta['empty']) ?></p></div>
            <?php else: ?>
                <div class="grid-3">
                    <?php foreach ($groupedLots[$status] as $lot): ?>
                        <article class="card feature-card auction-lot-card">
                            <div class="section-header">
                                <h2><?= e((string) $lot['title']) ?></h2>
                                <strong class="price-tag"><?= e(format_price_eur(max((int) $lot['current_price_cents'], (int) $lot['starting_price_cents']))) ?></strong>
                            </div>
                            <p><?= e((string) ($lot['summary'] ?: 'Lot d’enchère du club.')) ?></p>
                            <ul class="list-clean list-spaced">
                                <li><span class="help">Début : <?= e(date('d/m/Y H:i', strtotime((string) $lot['starts_at']))) ?></span></li>
                                <li><span class="help">Fin : <?= e(date('d/m/Y H:i', strtotime((string) $lot['ends_at']))) ?></span></li>
                                <li><span class="help">Pas : <?= e(format_price_eur((int) $lot['min_increment_cents'])) ?></span></li>
                            </ul>
                            <div class="actions">
                                <a class="button" href="<?= e(route_url('auction_view', ['slug' => (string) $lot['slug']])) ?>">Voir le lot</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), 'Enchères');
