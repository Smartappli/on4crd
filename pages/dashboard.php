<?php
declare(strict_types=1);

require_module_enabled('dashboard');
$user = require_login();
$locale = current_locale();
$t = i18n_domain_translator('dashboard', $locale);
set_page_meta(['title' => $t('meta_title'), 'description' => $t('meta_desc'), 'schema_type' => 'WebPage']);
$availableWidgets = enabled_widget_catalog();
unset($availableWidgets['chatbot']);
$userId = (int) ($user['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'mark_notifications_read') {
    verify_csrf();
    member_notifications_mark_all_read($userId);
    redirect_url(route_url('dashboard'));
}
$unreadNotifications = member_notifications_unread_count($userId);
$recentNotifications = member_notifications_recent($userId, 6);
$recentFavorites = member_favorites_recent($userId, 6);
$recommendationsEnabled = member_preference_bool($userId, 'personalized_recommendations_enabled', true);

$timelineItems = [];
if (table_exists('articles')) {
    $rows = db()->query('SELECT id, title, slug, published_at, created_at, updated_at FROM articles WHERE status = "published" ORDER BY ' . article_publication_sort_expression() . ' DESC, id DESC LIMIT 6')->fetchAll() ?: [];
    foreach ($rows as $row) {
        $row = localized_article_row($row);
        $timelineItems[] = [
            'title' => (string) ($row['title_localized'] ?? $row['title'] ?? ''),
            'date' => (string) (article_publication_datetime($row) ?? ''),
            'url' => route_url('article', ['slug' => (string) ($row['slug'] ?? '')]),
            'type' => (string) ($t('signal_article')),
        ];
    }
}
if (module_enabled('classifieds') && module_visible_for_current_user('classifieds') && table_exists('classified_ads')) {
    $rows = db()->query('SELECT title, created_at FROM classified_ads WHERE ' . classifieds_active_where_sql() . ' ORDER BY created_at DESC LIMIT 6')->fetchAll() ?: [];
    foreach ($rows as $row) {
        $title = trim((string) ($row['title'] ?? ''));
        $timelineItems[] = [
            'title' => $title,
            'date' => (string) ($row['created_at'] ?? ''),
            'url' => route_url('classifieds', ['q' => $title]),
            'type' => (string) ($t('signal_classified')),
        ];
    }
}
if (table_exists('albums')) {
    $rows = db()->query('SELECT id, title, created_at FROM albums WHERE is_public = 1 ORDER BY id DESC LIMIT 6')->fetchAll() ?: [];
    foreach ($rows as $row) {
        $timelineItems[] = [
            'title' => (string) ($row['title'] ?? ''),
            'date' => (string) ($row['created_at'] ?? ''),
            'url' => route_url('album', ['id' => (int) ($row['id'] ?? 0)]),
            'type' => (string) ($t('signal_album')),
        ];
    }
}
if (table_exists('member_library_documents')) {
    $rows = db()->query('SELECT title, uploaded_at, category FROM member_library_documents ORDER BY uploaded_at DESC LIMIT 6')->fetchAll() ?: [];
    foreach ($rows as $row) {
        $title = trim((string) ($row['title'] ?? ''));
        $timelineItems[] = [
            'title' => $title,
            'date' => (string) ($row['uploaded_at'] ?? ''),
            'url' => route_url_clean('members_library', ['q' => $title, 'category' => (string) ($row['category'] ?? '')]),
            'type' => (string) ($t('signal_library')),
        ];
    }
}
usort($timelineItems, static fn(array $a, array $b): int => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));
$timelineItems = array_slice($timelineItems, 0, 12);

$nudgeItems = [];
if ($recentFavorites === []) {
    $nudgeItems[] = (string) ($t('nudge_add_favorites'));
}
if ((int) $unreadNotifications > 0) {
    $nudgeItems[] = (string) ($t('nudge_review_notifications'));
}
if (!$recommendationsEnabled) {
    $nudgeItems[] = (string) ($t('nudge_enable_recommendations'));
}
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
    $defaultWidgetKeys = array_values(array_intersect(
        array_merge(['welcome', 'ham_weather_advice'], array_keys(hamqsl_widget_catalog())),
        array_keys($availableWidgets)
    ));
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
<?php
echo render_layout((string) ob_get_clean(), $t('title'));
