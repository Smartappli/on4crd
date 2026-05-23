<?php
declare(strict_types=1);

require_module_enabled('dashboard');
$user = require_login();
$locale = current_locale();
$t = i18n_domain_translator('dashboard', $locale);
set_page_meta(['title' => $t('meta_title'), 'description' => $t('meta_desc'), 'schema_type' => 'WebPage']);
$availableWidgets = enabled_widget_catalog();
$userId = (int) ($user['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'mark_notifications_read') {
    verify_csrf();
    member_notifications_mark_all_read($userId);
    redirect_url(route_url('dashboard'));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'toggle_recommendations') {
    verify_csrf();
    $enabled = ((string) ($_POST['recommendations_enabled'] ?? '1')) === '1';
    set_member_preference_bool($userId, 'personalized_recommendations_enabled', $enabled);
    set_flash('success', $t('recommendations_pref_saved'));
    redirect_url(route_url('dashboard'));
}
$unreadNotifications = member_notifications_unread_count($userId);
$recentNotifications = member_notifications_recent($userId, 6);
$recentFavorites = member_favorites_recent($userId, 6);
$recommendationsEnabled = member_preference_bool($userId, 'personalized_recommendations_enabled', true);
$recommendations = $recommendationsEnabled ? member_personalized_recommendations($userId, 6) : [];
$dashboardPersistenceEnabled = table_exists('dashboard_widgets');
$selected = [];
if ($dashboardPersistenceEnabled) {
    $userWidgets = db()->prepare('SELECT widget_key, config_json, position FROM dashboard_widgets WHERE member_id = ? ORDER BY position ASC');
    $userWidgets->execute([(int) $user['id']]);
    $selected = $userWidgets->fetchAll();
}
$selectedWidgets = [];
$seenSelected = [];
foreach ($selected as $row) {
    $widgetKey = (string) ($row['widget_key'] ?? '');
    if ($widgetKey === '' || !array_key_exists($widgetKey, $availableWidgets) || isset($seenSelected[$widgetKey])) {
        continue;
    }
    $decodedConfig = json_decode((string) ($row['config_json'] ?? ''), true);
    $seenSelected[$widgetKey] = true;
    $selectedWidgets[] = [
        'key' => $widgetKey,
        'config' => is_array($decodedConfig) ? $decodedConfig : [],
    ];
}
if ($selectedWidgets === []) {
    $defaultWidgetKeys = array_values(array_intersect(['welcome', 'propagation', 'club_status', 'chatbot'], array_keys($availableWidgets)));
    if ($defaultWidgetKeys === []) {
        $defaultWidgetKeys = array_slice(array_keys($availableWidgets), 0, 4);
    }
    $selectedWidgets = array_map(static fn(string $key): array => ['key' => $key, 'config' => []], $defaultWidgetKeys);
}
$selectedKeys = array_map(static fn(array $widget): string => (string) $widget['key'], $selectedWidgets);
$availableToAdd = array_filter($availableWidgets, static fn(string $key): bool => !in_array($key, $selectedKeys, true), ARRAY_FILTER_USE_KEY);

$safeRenderWidget = static function (string $widgetKey, array $currentUser) use ($t): string {
    try {
        return render_widget($widgetKey, $currentUser);
    } catch (Throwable) {
        return '<p class="help">' . e($t('widget_unavailable')) . '</p>';
    }
};

$dashboardConfig = [
    'renderBase' => base_url('index.php?route=widget_render&widget='),
    'saveUrl' => base_url('index.php?route=save_dashboard'),
    'saveEnabled' => $dashboardPersistenceEnabled,
    'refreshMs' => 90000,
    'csrf' => csrf_token(),
];

ob_start();
?>
<div class="dashboard-fullwidth" id="dashboard-shell">
  <section class="card">
    <div class="row-between">
      <div>
        <h1 class="dashboard-heading"><?= e($t('title')) ?></h1>
      </div>
      <div class="actions">
        <button class="button secondary small" id="open-widgets-panel" type="button" aria-controls="dashboard-widgets-panel" aria-expanded="false">🧩 <?= e($t('available_widgets')) ?></button>
        <button class="button secondary small" id="dashboard-fullscreen-toggle" type="button">⛶ <?= e($t('fullscreen')) ?></button>
        <a class="button secondary small" href="<?= e(route_url('notifications')) ?>">🔔 <?= e($t('notifications')) ?></a>
        <a class="button secondary small" href="<?= e(route_url('chatbot')) ?>">🤖 <?= e($t('chatbot')) ?></a>
        <button class="button secondary small" id="save-dashboard" type="button" <?= $dashboardPersistenceEnabled ? '' : 'disabled' ?>>💾 <?= e($t('save_layout')) ?></button>
        <span class="help" id="dashboard-save-status" role="status" aria-live="polite"></span>
      </div>
    </div>
    <?php if (!$dashboardPersistenceEnabled): ?>
      <p class="flash flash-error"><?= e($t('table_missing')) ?></p>
    <?php endif; ?>
    <div id="dashboard-grid" class="widget-grid" data-config='<?= e(json_encode($dashboardConfig, JSON_UNESCAPED_SLASHES)) ?>'>
      <?php if ($selectedWidgets === []): ?>
        <p class="help"><?= e($t('no_widgets')) ?></p>
      <?php endif; ?>
      <?php foreach ($selectedWidgets as $selectedWidget): ?>
        <?php
          $widgetKey = (string) $selectedWidget['key'];
          $widgetTitle = (string) ($availableWidgets[$widgetKey]['title'] ?? $widgetKey);
          $widgetConfig = (array) ($selectedWidget['config'] ?? []);
          $widgetBodyHtml = $safeRenderWidget($widgetKey, $user);
          include __DIR__ . '/dashboard_widget_card.php';
        ?>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="card">
    <div class="row-between">
      <h2 style="margin:0;"><?= e($t('notifications')) ?></h2>
      <div class="actions">
        <span class="badge muted"><?= $unreadNotifications ?> <?= e($t('unread')) ?></span>
        <form method="post" class="inline-form">
          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="mark_notifications_read">
          <button class="button secondary small" type="submit"><?= e($t('mark_all_read')) ?></button>
        </form>
      </div>
    </div>
    <?php if ($recentNotifications === []): ?>
      <p class="help"><?= e($t('no_notifications')) ?></p>
    <?php else: ?>
      <ul class="stack" style="list-style:none;padding:0;margin:.8rem 0 0 0;">
        <?php foreach ($recentNotifications as $item): ?>
          <li class="card" style="margin:0;">
            <strong><?= e((string) ($item['title'] ?? '')) ?></strong>
            <?php if (trim((string) ($item['body'] ?? '')) !== ''): ?><p class="help" style="margin:.35rem 0;"><?= e((string) $item['body']) ?></p><?php endif; ?>
            <?php if (trim((string) ($item['url'] ?? '')) !== ''): ?><a href="<?= e((string) $item['url']) ?>"><?= e($t('open')) ?></a><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
  <section class="card">
    <h2 style="margin-top:0;"><?= e($t('recent_favorites')) ?></h2>
    <?php if ($recentFavorites === []): ?>
      <p class="help"><?= e($t('no_favorites')) ?></p>
    <?php else: ?>
      <ul class="stack" style="list-style:none;padding:0;margin:0;">
        <?php foreach ($recentFavorites as $favorite): ?>
          <li class="row-between" style="gap:.8rem;">
            <span><?= e((string) ($favorite['title'] !== '' ? $favorite['title'] : $favorite['target_type'])) ?></span>
            <?php if (trim((string) ($favorite['url'] ?? '')) !== ''): ?><a class="button secondary small" href="<?= e((string) $favorite['url']) ?>"><?= e($t('open')) ?></a><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
  <section class="card">
    <h2 style="margin-top:0;"><?= e($t('recommendations_title')) ?></h2>
    <form method="post" class="inline-form" style="margin:.25rem 0 1rem;">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="toggle_recommendations">
      <label style="display:flex;align-items:center;gap:.45rem;">
        <input type="checkbox" name="recommendations_enabled" value="1" <?= $recommendationsEnabled ? 'checked' : '' ?>>
        <span><?= e($t('recommendations_opt_in_label')) ?></span>
      </label>
      <button class="button secondary small" type="submit"><?= e($t('save_layout')) ?></button>
    </form>
    <p class="help"><?= e($t('recommendations_opt_in_help')) ?></p>
    <?php if ($recommendations === []): ?>
      <p class="help"><?= e($t('recommendations_empty')) ?></p>
    <?php else: ?>
      <ul class="stack" style="list-style:none;padding:0;margin:0;">
        <?php foreach ($recommendations as $item): ?>
          <?php $reasonKey = (string) ($item['reason_key'] ?? ''); ?>
          <li class="row-between" style="gap:.8rem;align-items:flex-start;">
            <span>
              <span><?= e((string) ($item['title'] ?? '')) ?></span><br>
              <small class="help"><?= e($t('recommendations_why')) ?>: <?= e($t($reasonKey !== '' ? $reasonKey : 'recommendation_reason_default')) ?></small>
            </span>
            <?php if (trim((string) ($item['url'] ?? '')) !== ''): ?><a class="button secondary small" href="<?= e((string) $item['url']) ?>"><?= e($t('open')) ?></a><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>
<div class="dashboard-offcanvas-backdrop" id="dashboard-widgets-backdrop" hidden></div>
<aside class="dashboard-offcanvas" id="dashboard-widgets-panel" aria-hidden="true" data-widget-unavailable="<?= e($t('widget_unavailable')) ?>">
  <header class="dashboard-offcanvas-header">
    <h2><?= e($t('available_widgets')) ?></h2>
    <button class="ghost" type="button" id="close-widgets-panel" aria-label="<?= e($t('close')) ?>">✕</button>
  </header>
  <p class="help"><?= e($t('widgets_help')) ?></p>
  <div class="stack">
    <?php foreach ($availableToAdd as $widgetKey => $widget): ?>
      <article class="widget-card">
        <header>
          <strong><?= e((string) $widget['title']) ?></strong>
        </header>
        <p class="help"><?= e((string) ($widget['description'] ?? '')) ?></p>
        <div class="widget-body widget-preview" data-widget-preview="<?= e((string) $widgetKey) ?>"><p class="help">...</p></div>
        <button class="button small add-widget" type="button" data-widget="<?= e($widgetKey) ?>" data-title="<?= e((string) $widget['title']) ?>"><?= e($t('add')) ?></button>
      </article>
    <?php endforeach; ?>
  </div>
</aside>
<?php include __DIR__ . '/dashboard_script.js.php'; ?>
<?php
echo render_layout((string) ob_get_clean(), $t('title'));
