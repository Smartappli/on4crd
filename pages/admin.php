<?php
declare(strict_types=1);

require_permission('admin.access');
$locale = current_locale();
$t = admin_dashboard_translations($locale);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'update_content_proposal_status') {
            admin_update_content_proposal_status(
                (int) ($_POST['proposal_id'] ?? 0),
                (string) ($_POST['proposal_status'] ?? 'pending'),
                trim((string) ($_POST['moderation_note'] ?? '')),
                $locale
            );
            set_flash('success', (string) $t['proposal_status_saved']);
            redirect_url(route_url('admin') . '#pending-proposals');
        }
        throw new RuntimeException((string) $t['invalid_action']);
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect_url(route_url('admin') . '#pending-proposals');
    }
}

$adminCardIcons = [
    'admin_modules' => '🧩',
    'admin_permissions' => '🔐',
    'admin_members' => '👤',
    'admin_news' => '📰',
    'admin_articles' => '🛠️',
    'admin_committee' => '👥',
    'admin_press' => '🗞️',
    'admin_events' => '📅',
    'admin_dinner_reservations' => '🍽️',
    'admin_auctions' => '🏷️',
    'admin_editorial' => '🌐',
    'admin_translation_reviews' => '✅',
    'admin_live_feeds' => '📡',
    'admin_newsletters' => '✉️',
    'admin_wiki' => '📚',
    'admin_albums' => '🖼️',
    'admin_library' => '📖',
    'admin_ads' => '📢',
];

$adminSearch = trim((string) ($_GET['q'] ?? ''));
$userId = (int) (current_user()['id'] ?? 0);
$cards = admin_dashboard_cards($locale, $userId, $adminSearch);
$pendingProposals = admin_pending_content_proposals_for_dashboard($locale);
$proposalStatusLabels = admin_pending_content_proposal_status_labels($locale);

ob_start();
?>
<div class="stack admin-module admin-home">
    <section class="admin-home-hero" aria-labelledby="admin-home-title">
        <div class="admin-home-hero-copy">
            <p class="admin-section-kicker"><?= e((string) $t['layout']) ?></p>
            <h1 id="admin-home-title"><?= e((string) $t['title']) ?></h1>
            <p class="help"><?= e((string) $t['lead']) ?></p>
        </div>
        <form class="admin-home-search" method="get" action="<?= e(route_url('admin')) ?>" role="search">
            <label for="admin-dashboard-search">
                <span><?= e((string) $t['search_label']) ?></span>
                <input id="admin-dashboard-search" type="search" name="q" value="<?= e($adminSearch) ?>" placeholder="<?= e((string) $t['search_placeholder']) ?>">
            </label>
            <div class="admin-home-search-actions">
                <button class="button" type="submit"><?= e((string) $t['search_cta']) ?></button>
                <?php if ($adminSearch !== ''): ?>
                    <a class="button secondary" href="<?= e(route_url('admin')) ?>"><?= e((string) $t['search_reset']) ?></a>
                <?php endif; ?>
            </div>
        </form>
    </section>
    <?php if ($pendingProposals !== []): ?>
        <section class="card admin-pending-card" id="pending-proposals" aria-labelledby="pending-proposals-title">
            <div class="admin-section-head">
                <div>
                    <h2 id="pending-proposals-title"><?= e((string) $t['pending_content_title']) ?></h2>
                    <p class="help"><?= e((string) $t['pending_content_help']) ?></p>
                </div>
                <span class="admin-pending-badge"><?= count($pendingProposals) ?> <?= e((string) $t['pending_label']) ?></span>
            </div>
            <div class="admin-pending-list">
                <?php foreach ($pendingProposals as $proposal): ?>
                    <?php
                    $proposalType = (string) ($proposal['proposal_type'] ?? 'content');
                    $memberLabel = trim((string) ($proposal['callsign'] ?? ''));
                    if ($memberLabel === '') {
                        $memberLabel = trim((string) ($proposal['email'] ?? ''));
                    }
                    if ($memberLabel === '') {
                        $memberLabel = '#' . (int) ($proposal['member_id'] ?? 0);
                    }
                    $createdTimestamp = strtotime((string) ($proposal['created_at'] ?? 'now'));
                    if ($createdTimestamp === false) {
                        $createdTimestamp = time();
                    }
                    ?>
                    <article class="admin-pending-item">
                        <header class="admin-pending-item-head">
                            <div>
                                <p class="admin-meta-row">
                                    <span class="badge muted"><?= e((string) ($proposal['area_label'] ?? $proposal['area'] ?? '')) ?></span>
                                    <span class="badge muted"><?= e($proposalType) ?></span>
                                    <span class="badge muted"><?= e(date('d/m/Y H:i', $createdTimestamp)) ?></span>
                                </p>
                                <h3><?= e((string) ($proposal['title'] ?? $t['proposal_default_title'])) ?></h3>
                            </div>
                            <p class="help"><?= e((string) $t['proposal_author']) ?>: <?= e($memberLabel) ?></p>
                        </header>
                        <?php if (trim((string) ($proposal['summary'] ?? '')) !== ''): ?>
                            <p><?= nl2br(e((string) $proposal['summary'])) ?></p>
                        <?php endif; ?>
                        <?php if (trim((string) ($proposal['contact'] ?? '')) !== ''): ?>
                            <p class="help"><?= e((string) $t['proposal_contact']) ?>: <?= e((string) $proposal['contact']) ?></p>
                        <?php endif; ?>
                        <?php if (trim((string) ($proposal['source_ref'] ?? '')) !== ''): ?>
                            <p class="help"><?= e((string) $t['proposal_source']) ?>: <?= e((string) $proposal['source_ref']) ?></p>
                        <?php endif; ?>
                        <form method="post" class="stack">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="update_content_proposal_status">
                            <input type="hidden" name="proposal_id" value="<?= (int) ($proposal['id'] ?? 0) ?>">
                            <div class="grid-2">
                                <label><?= e((string) $t['proposal_status_label']) ?>
                                    <select name="proposal_status">
                                        <?php foreach ($proposalStatusLabels as $statusCode => $statusLabel): ?>
                                            <option value="<?= e($statusCode) ?>"><?= e($statusLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label><?= e((string) $t['proposal_moderation_note']) ?>
                                    <textarea name="moderation_note" rows="3"><?= e((string) ($proposal['moderation_note'] ?? '')) ?></textarea>
                                </label>
                            </div>
                            <div class="actions">
                                <a class="button secondary small" href="<?= e((string) ($proposal['area_url'] ?? route_url('admin'))) ?>"><?= e((string) $t['proposal_open_module']) ?></a>
                                <button class="button small" type="submit"><?= e((string) $t['proposal_save']) ?></button>
                            </div>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
    <?php if ($cards === []): ?>
        <section class="card empty-state"><p><?= e((string) $t['empty']) ?></p></section>
    <?php else: ?>
    <div class="admin-card-grid">
        <?php foreach ($cards as $card): ?>
            <?php $cardPendingCount = (int) ($card['pending_count'] ?? 0); $cardClass = 'card admin-link admin-card' . ($cardPendingCount > 0 ? ' has-pending' : ''); ?>
            <?php $cardRoute = (string) $card['route']; $cardIcon = (string) ($adminCardIcons[$cardRoute] ?? '📦'); ?>
            <a class="<?= e($cardClass) ?>" href="<?= e((string) ($card['url'] ?? route_url($cardRoute))) ?>">
                <span class="admin-card-icon" aria-hidden="true"><?= e($cardIcon) ?></span>
                <div class="admin-card-copy">
                    <h2><?= e((string) $card['title']) ?></h2>
                    <p><?= e((string) $card['desc']) ?></p>
                </div>
                <div class="admin-card-footer">
                    <?php if ($cardPendingCount > 0): ?>
                        <span class="admin-pending-badge"><?= $cardPendingCount ?> <?= e((string) $t['pending_label']) ?></span>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                    <span class="admin-open-cta"><span><?= e((string) $t['open']) ?></span><span aria-hidden="true">&rarr;</span></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['layout']);
