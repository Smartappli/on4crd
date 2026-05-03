<?php
declare(strict_types=1);

require_permission('admin.access');
$locale = current_locale();
$t = admin_dashboard_translations($locale);

$adminSearch = trim((string) ($_GET['q'] ?? ''));
$adminSearchNeedle = $adminSearch !== '' ? mb_safe_strtolower($adminSearch) : '';

$userId = (int) (current_user()['id'] ?? 0);
$cards = admin_cards_for_dashboard($locale, $userId, $adminSearchNeedle);

ob_start();
?>
<div class="stack">
    <section class="card">
        <h1><?= e((string) $t['title']) ?></h1>
        <p class="help"><?= e((string) $t['lead']) ?></p>
        <form method="get" action="<?= e(route_url('admin')) ?>" class="mt-2">
            <label><?= e((string) $t['search_label']) ?>
                <input type="search" name="q" value="<?= e($adminSearch) ?>" placeholder="<?= e((string) $t['search_placeholder']) ?>">
            </label>
            <div class="actions mt-2">
                <button type="submit" class="button"><?= e((string) $t['search_cta']) ?></button>
                <a class="button secondary" href="<?= e(route_url('admin')) ?>"><?= e((string) $t['search_reset']) ?></a>
            </div>
        </form>
    </section>
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
