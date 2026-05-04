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
    <style>
        .admin-open-cta{display:inline-flex;align-items:center;justify-content:center;padding:.35rem .7rem;border-radius:999px;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;font-size:.8rem;font-weight:600;transition:all .15s ease}
        .admin-link:hover .admin-open-cta{background:#dbeafe;border-color:#93c5fd;color:#1e40af}
    </style>
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
                <div class="row-between" style="margin-top:.75rem;">
                    <span></span>
                    <span class="admin-open-cta"><?= e($openLabel) ?> →</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['layout']);
