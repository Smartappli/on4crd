<?php
declare(strict_types=1);

require_permission('admin.access');
$locale = current_locale();
$t = admin_dashboard_translations($locale);

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
            <a class="card admin-link" href="<?= e(route_url((string) $card['route'])) ?>"><h2><?= e((string) $card['title']) ?></h2><p><?= e((string) $card['desc']) ?></p></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['layout']);
