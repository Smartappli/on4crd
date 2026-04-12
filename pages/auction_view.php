<?php
declare(strict_types=1);

$slug = trim((string) ($_GET['slug'] ?? ''));
$lot = $slug !== '' ? auction_lot_by_slug($slug) : null;
if ($lot === null) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>Lot introuvable</h1><p>Ce lot n’existe pas.</p></div>', 'Lot introuvable');
    return;
}

if (in_array((string) ($lot['status'] ?? ''), ['draft', 'cancelled'], true) && !has_permission('auctions.manage')) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>Lot introuvable</h1><p>Ce lot n’est pas public.</p></div>', 'Lot introuvable');
    return;
}

$runtime = auction_runtime_status($lot);
$bids = auction_bids_for_lot((int) $lot['id'], 20);
$minimumBid = auction_minimum_bid_cents($lot);
set_page_meta([
    'title' => (string) $lot['title'],
    'description' => (string) ($lot['summary'] ?: 'Lot aux enchères ON4CRD.'),
]);

ob_start();
?>
<div class="split">
    <article class="card">
        <div class="badge <?= $runtime === 'closed' ? 'muted' : '' ?>"><?= e(auction_status_label($runtime)) ?></div>
        <h1><?= e((string) $lot['title']) ?></h1>
        <p class="hero-lead"><?= e((string) ($lot['summary'] ?: 'Lot aux enchères du club.')) ?></p>
        <div class="catalog">
            <span class="pill">Prix actuel : <?= e(format_price_eur(max((int) $lot['current_price_cents'], (int) $lot['starting_price_cents']))) ?></span>
            <span class="pill">Pas minimal : <?= e(format_price_eur((int) $lot['min_increment_cents'])) ?></span>
            <span class="pill">Fin : <?= e(date('d/m/Y H:i', strtotime((string) $lot['ends_at']))) ?></span>
        </div>
        <div class="inner-card"><?= sanitize_rich_html((string) ($lot['description'] ?? '')) ?></div>
        <?php if ($runtime === 'closed' && !empty($lot['winner_callsign'])): ?>
            <p><strong>Gagnant provisoire :</strong> <?= e((string) $lot['winner_callsign']) ?></p>
        <?php endif; ?>
    </article>
    <aside class="card">
        <h2>Enchérir</h2>
        <?php if ($runtime !== 'active'): ?>
            <p class="help">Le lot n’est pas actuellement en phase d’enchère active.</p>
        <?php elseif (!current_user()): ?>
            <p class="help">Il faut être connecté pour enchérir.</p>
            <p><a class="button" href="<?= e(route_url('login')) ?>">Connexion membre</a></p>
        <?php else: ?>
            <form method="post" action="<?= e(route_url('auction_bid')) ?>" class="stack">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="lot_id" value="<?= (int) $lot['id'] ?>">
                <label>Votre offre
                    <input type="text" name="amount" value="<?= e(number_format($minimumBid / 100, 2, ',', '')) ?>">
                </label>
                <p class="help">Offre minimale actuelle : <?= e(format_price_eur($minimumBid)) ?></p>
                <button class="button">Placer l’offre</button>
            </form>
        <?php endif; ?>

        <div class="inner-card">
            <h3>Historique des offres</h3>
            <?php if ($bids === []): ?>
                <p class="help">Aucune offre pour le moment.</p>
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
