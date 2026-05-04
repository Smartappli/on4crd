<?php
declare(strict_types=1);

require_permission('admin.access');
$locale = current_locale();
$t = admin_dashboard_translations($locale);
$openLabel = ['fr' => 'Ouvrir', 'en' => 'Open', 'de' => 'Öffnen', 'nl' => 'Openen'][$locale] ?? 'Ouvrir';
$adminCardIcons = [
    'admin_modules' => '🧩',
    'admin_permissions' => '🔐',
    'admin_news' => '📰',
    'admin_articles' => '🛠️',
    'admin_committee' => '👥',
    'admin_press' => '🗞️',
    'admin_events' => '📅',
    'admin_dinner_reservations' => '🍽️',
    'admin_shop' => '🛒',
    'admin_auctions' => '🏷️',
    'admin_editorial' => '🌐',
    'admin_translation_reviews' => '✅',
    'admin_live_feeds' => '📡',
    'admin_newsletters' => '✉️',
    'admin_wiki' => '📚',
    'admin_albums' => '🖼️',
    'admin_ads' => '📢',
];

$adminSearch = trim((string) ($_GET['q'] ?? ''));
$userId = (int) (current_user()['id'] ?? 0);
$cards = admin_dashboard_cards($locale, $userId, $adminSearch);

ob_start();
?>
<div class="stack">
    <?php if ($cards === []): ?>
        <section class="card empty-state"><p><?= e((string) $t['empty']) ?></p></section>
    <?php else: ?>
    <div class="grid-3">
        <?php foreach ($cards as $card): ?>
            <?php $cardRoute = (string) $card['route']; $cardIcon = (string) ($adminCardIcons[$cardRoute] ?? '📦'); ?>
            <a class="card admin-link" href="<?= e(route_url($cardRoute)) ?>">
                <div class="row-between">
                    <h2><?= e((string) $card['title']) ?></h2>
                    <span aria-hidden="true"><?= e($cardIcon) ?></span>
                </div>
                <p><?= e((string) $card['desc']) ?></p>
                <span class="button secondary" style="margin-top:.5rem;display:inline-flex;"><?= e($openLabel) ?> →</span>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['layout']);
