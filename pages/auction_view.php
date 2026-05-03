<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['lot_not_found' => 'Lot introuvable', 'lot_not_exists' => 'Ce lot n’existe pas.', 'lot_not_public' => 'Ce lot n’est pas public.', 'default_desc' => 'Lot aux enchères ON4CRD.', 'default_summary' => 'Lot aux enchères du club.', 'current_price' => 'Prix actuel :', 'min_step' => 'Pas minimal :', 'end' => 'Fin :', 'provisional_winner' => 'Gagnant provisoire :', 'bid' => 'Enchérir', 'inactive' => 'Le lot n’est pas actuellement en phase d’enchère active.', 'login_needed' => 'Il faut être connecté pour enchérir.', 'member_login' => 'Connexion membre', 'your_bid' => 'Votre offre', 'min_offer' => 'Offre minimale actuelle :', 'place_bid' => 'Placer l’offre', 'history' => 'Historique des offres', 'no_bids' => 'Aucune offre pour le moment.'],
    'en' => ['lot_not_found' => 'Lot not found', 'lot_not_exists' => 'This lot does not exist.', 'lot_not_public' => 'This lot is not public.', 'default_desc' => 'ON4CRD auction lot.', 'default_summary' => 'Club auction lot.', 'current_price' => 'Current price:', 'min_step' => 'Minimum step:', 'end' => 'Ends:', 'provisional_winner' => 'Provisional winner:', 'bid' => 'Bid', 'inactive' => 'This lot is not currently in an active bidding phase.', 'login_needed' => 'You must be logged in to bid.', 'member_login' => 'Member login', 'your_bid' => 'Your bid', 'min_offer' => 'Current minimum offer:', 'place_bid' => 'Place bid', 'history' => 'Bid history', 'no_bids' => 'No bids yet.'],
    'de' => ['lot_not_found' => 'Los nicht gefunden', 'lot_not_exists' => 'Dieses Los existiert nicht.', 'lot_not_public' => 'Dieses Los ist nicht öffentlich.', 'default_desc' => 'ON4CRD-Auktionslos.', 'default_summary' => 'Auktionslos des Clubs.', 'current_price' => 'Aktueller Preis:', 'min_step' => 'Mindestschritt:', 'end' => 'Ende:', 'provisional_winner' => 'Vorläufiger Gewinner:', 'bid' => 'Bieten', 'inactive' => 'Dieses Los befindet sich derzeit nicht in einer aktiven Bietphase.', 'login_needed' => 'Zum Bieten müssen Sie angemeldet sein.', 'member_login' => 'Mitglieder-Login', 'your_bid' => 'Ihr Gebot', 'min_offer' => 'Aktuelles Mindestgebot:', 'place_bid' => 'Gebot abgeben', 'history' => 'Gebotsverlauf', 'no_bids' => 'Noch keine Gebote.'],
    'nl' => ['lot_not_found' => 'Kavel niet gevonden', 'lot_not_exists' => 'Deze kavel bestaat niet.', 'lot_not_public' => 'Deze kavel is niet openbaar.', 'default_desc' => 'ON4CRD-veilingkavel.', 'default_summary' => 'Veilingkavel van de club.', 'current_price' => 'Huidige prijs:', 'min_step' => 'Minimale stap:', 'end' => 'Einde:', 'provisional_winner' => 'Voorlopige winnaar:', 'bid' => 'Bieden', 'inactive' => 'Deze kavel bevindt zich momenteel niet in een actieve biedfase.', 'login_needed' => 'Je moet ingelogd zijn om te bieden.', 'member_login' => 'Ledenlogin', 'your_bid' => 'Jouw bod', 'min_offer' => 'Huidig minimumbod:', 'place_bid' => 'Bod plaatsen', 'history' => 'Biedgeschiedenis', 'no_bids' => 'Nog geen biedingen.'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

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
set_page_meta([
    'title' => (string) $lot['title'],
    'description' => (string) ($lot['summary'] ?: (string) $t['default_desc']),
]);

ob_start();
?>
<div class="split auction-detail-layout">
    <article class="card auction-detail-main">
        <div class="badge <?= $runtime === 'closed' ? 'muted' : '' ?>"><?= e(auction_status_label($runtime)) ?></div>
        <h1><?= e((string) $lot['title']) ?></h1>
        <p class="hero-lead"><?= e((string) ($lot['summary'] ?: (string) $t['default_summary'])) ?></p>
        <div class="catalog auction-meta-list">
            <span class="pill"><?= e((string) $t['current_price']) ?> <?= e(format_price_eur(max((int) $lot['current_price_cents'], (int) $lot['starting_price_cents']))) ?></span>
            <span class="pill"><?= e((string) $t['min_step']) ?> <?= e(format_price_eur((int) $lot['min_increment_cents'])) ?></span>
            <span class="pill"><?= e((string) $t['end']) ?> <?= e(date('d/m/Y H:i', strtotime((string) $lot['ends_at']))) ?></span>
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
