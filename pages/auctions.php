<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('auctions', $locale);
set_page_meta([
    'title' => (string) $t['meta_title'],
    'description' => (string) $t['meta_desc'],
]);

$user = current_user();
$canManageAuctions = has_permission('auctions.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user = require_login(route_url('auctions'));
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        if ($action !== 'propose_lot') {
            throw new RuntimeException('Demande invalide.');
        }
        $proposalTitle = (string) ($_POST['proposal_title'] ?? '');
        $proposalSummary = (string) ($_POST['proposal_summary'] ?? '');
        $proposalDescription = (string) ($_POST['proposal_description'] ?? '');
        $proposalPrice = (string) ($_POST['proposal_price'] ?? '0');
        $proposalContact = (string) ($_POST['proposal_contact'] ?? '');
        $title = content_proposal_clean_single_line($proposalTitle, 190);
        $summary = content_proposal_clean_multiline($proposalSummary, 1000);
        $descriptionText = content_proposal_clean_multiline($proposalDescription, 5000);
        $contact = content_proposal_clean_single_line($proposalContact, 220);
        if ($title === '') {
            throw new RuntimeException('Demande invalide.');
        }

        if (has_permission('auctions.manage')) {
            if (!table_exists('auction_lots')) {
                throw new RuntimeException('Stockage des encheres indisponible.');
            }
            $slug = auction_unique_slug($title);
            $startingPrice = max(0, parse_price_to_cents($proposalPrice));
            $descriptionHtml = $descriptionText !== '' ? '<p>' . nl2br(e($descriptionText), false) . '</p>' : '';
            if ($contact !== '') {
                $descriptionHtml .= '<p><strong>Contact:</strong> ' . e($contact) . '</p>';
            }
            $startsAt = time();
            $endsAt = strtotime('+7 days', $startsAt);
            db()->prepare('INSERT INTO auction_lots (slug, title, summary, description, image_url, starting_price_cents, reserve_price_cents, min_increment_cents, buy_now_price_cents, starts_at, ends_at, status) VALUES (?, ?, ?, ?, "", ?, NULL, 100, NULL, ?, ?, "active")')
                ->execute([
                    $slug,
                    $title,
                    $summary !== '' ? $summary : mb_safe_strimwidth($descriptionText, 0, 280, '...'),
                    sanitize_rich_html($descriptionHtml),
                    $startingPrice,
                    date('Y-m-d H:i:s', $startsAt),
                    date('Y-m-d H:i:s', $endsAt ?: ($startsAt + 7 * 86400)),
                ]);
            cache_forget('auction_public_lots_60_v1');
            set_flash('success', 'Lot cree et valide directement.');
            redirect_url(route_url('auction_view', ['slug' => $slug]));
        }

        $proposalDetails = content_proposal_details_text([
            'Resume' => $summary,
            'Prix de depart' => $proposalPrice,
            'Description' => $descriptionText,
        ]);
        $proposalId = content_proposal_create((int) $user['id'], 'auctions', 'content', $title, $proposalDetails, $contact);
        content_proposal_notify_site('Proposition de lot ON4CRD', [
            'area' => 'auctions',
            'proposal_type' => 'content',
            'title' => $title,
            'summary' => $proposalDetails,
            'contact' => $contact,
            'source_ref' => 'content_proposals#' . $proposalId,
        ]);
        set_flash('success', 'Proposition enregistree dans vos contenus.');
        redirect('my_requests');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect_url(route_url('auctions'));
    }
}

$lots = cache_remember('auction_public_lots_60_v1', 60, static fn(): array => auction_public_lots(60));

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
$auctionSubscribeUrl = $user !== null ? route_url('newsletter') : route_url('newsletter_public');
$proposalContactDefault = '';
if ($user !== null) {
    $proposalContactDefault = trim((string) ($user['email'] ?? ''));
    if ($proposalContactDefault === '') {
        $proposalContactDefault = trim((string) ($user['callsign'] ?? ''));
    }
}
$showLotProposalForm = $user !== null && (string) ($_GET['propose_lot'] ?? '') === '1';
$lotProposalUrl = $user !== null ? route_url('auctions', ['propose_lot' => '1']) : route_url('login', ['next' => route_url('auctions')]);

ob_start();
?>
<section class="auctions-page">
    <header class="page-hero auctions-hero">
        <div class="auctions-hero-copy">
            <p class="directory-eyebrow"><?= e((string) $t['meta_title']) ?></p>
            <h1><?= e((string) $t['meta_title']) ?></h1>
            <p class="directory-lead"><?= e((string) $t['meta_desc']) ?></p>
        </div>
        <div class="auctions-hero-side">
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
            <p class="actions">
                <a class="button" href="<?= e($lotProposalUrl) ?>"><?= e($canManageAuctions ? 'Creer un lot' : 'Proposer un lot') ?></a>
                <a class="button secondary auctions-subscribe-button" href="<?= e($auctionSubscribeUrl) ?>"><?= e((string) ($t['subscribe_auctions'] ?? "M'abonner aux encheres")) ?></a>
            </p>
        </div>
    </header>

    <?php if ($showLotProposalForm): ?>
    <section class="card">
        <h2><?= e($canManageAuctions ? 'Creer un lot' : 'Proposer un lot') ?></h2>
        <p class="help"><?= e($canManageAuctions ? 'Le lot sera active directement pour 7 jours.' : 'Votre proposition sera envoyee en validation et visible dans Mes contenus.') ?></p>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="propose_lot">
            <label><span>Titre</span><input type="text" name="proposal_title" maxlength="190" required></label>
            <label><span>Resume</span><input type="text" name="proposal_summary" maxlength="1000"></label>
            <label><span>Prix de depart</span><input type="text" name="proposal_price" placeholder="0,00"></label>
            <label><span>Description</span><textarea name="proposal_description" rows="5" maxlength="5000"></textarea></label>
            <label><span>Contact</span><input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required></label>
            <p class="actions">
                <button class="button" type="submit"><?= e($canManageAuctions ? 'Creer' : 'Envoyer la proposition') ?></button>
                <a class="button secondary" href="<?= e(route_url('auctions')) ?>">Annuler</a>
            </p>
        </form>
    </section>
    <?php endif; ?>

    <?php foreach ($sections as $status => $meta): ?>
        <section class="auctions-section auctions-section-<?= e($status) ?>">
            <div class="auctions-section-header">
                <div>
                    <h2 class="auctions-section-title"><?= e($meta['title']) ?></h2>
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
