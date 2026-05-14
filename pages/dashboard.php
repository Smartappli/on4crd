<?php
declare(strict_types=1);

require_module_enabled('dashboard');
$user = require_login();
$locale = current_locale();
$i18n = [
    'fr' => ['meta_title' => 'Tableau de bord membre', 'meta_desc' => 'Personnalisez votre tableau de bord ON4CRD avec vos widgets favoris.', 'title' => 'Tableau de bord', 'notifications' => 'Notifications', 'chatbot' => 'Assistant', 'fullscreen' => 'Plein écran', 'save_layout' => 'Enregistrer', 'widget_unavailable' => 'Widget temporairement indisponible.', 'table_missing' => 'La table dashboard_widgets est absente : la disposition des widgets ne peut pas être enregistrée.', 'available_widgets' => 'Widgets', 'widgets_help' => 'Installez vos widgets, puis glissez-déposez pour réordonner la grille.', 'no_widgets' => 'Aucun widget activé pour le moment.', 'add' => 'Ajouter'],
    'en' => ['meta_title' => 'Member dashboard', 'meta_desc' => 'Customize your ON4CRD dashboard with your favorite widgets.', 'title' => 'Member dashboard', 'notifications' => 'Notifications', 'chatbot' => 'Assistant', 'fullscreen' => 'Fullscreen', 'save_layout' => 'Save', 'widget_unavailable' => 'Widget temporarily unavailable.', 'table_missing' => 'The dashboard_widgets table is missing: widget layout cannot be saved.', 'available_widgets' => 'Widgets', 'widgets_help' => 'Install your widgets, then drag and drop to reorder the grid.', 'no_widgets' => 'No widgets are currently enabled.', 'add' => 'Add'],
    'de' => ['meta_title' => 'Mitglieder-Dashboard', 'meta_desc' => 'Passen Sie Ihr ON4CRD-Dashboard mit Ihren bevorzugten Widgets an.', 'title' => 'Mitglieder-Dashboard', 'notifications' => 'Benachrichtigungen', 'chatbot' => 'Assistent', 'fullscreen' => 'Vollbild', 'save_layout' => 'Speichern', 'widget_unavailable' => 'Widget vorübergehend nicht verfügbar.', 'table_missing' => 'Die Tabelle dashboard_widgets fehlt: Das Widget-Layout kann nicht gespeichert werden.', 'available_widgets' => 'Widgets', 'widgets_help' => 'Installieren Sie Ihre Widgets und ordnen Sie das Raster per Drag-and-drop neu.', 'no_widgets' => 'Derzeit sind keine Widgets aktiviert.', 'add' => 'Hinzufügen'],
    'nl' => ['meta_title' => 'Leden-dashboard', 'meta_desc' => 'Pas je ON4CRD-dashboard aan met je favoriete widgets.', 'title' => 'Leden-dashboard', 'notifications' => 'Meldingen', 'chatbot' => 'Assistent', 'fullscreen' => 'Volledig scherm', 'save_layout' => 'Opslaan', 'widget_unavailable' => 'Widget tijdelijk niet beschikbaar.', 'table_missing' => 'De tabel dashboard_widgets ontbreekt: de widgetindeling kan niet worden opgeslagen.', 'available_widgets' => 'Widgets', 'widgets_help' => 'Installeer je widgets en herschik het raster met slepen en neerzetten.', 'no_widgets' => 'Er zijn momenteel geen widgets geactiveerd.', 'add' => 'Toevoegen'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};
set_page_meta(['title' => $t('meta_title'), 'description' => $t('meta_desc'), 'schema_type' => 'WebPage']);
$availableWidgets = enabled_widget_catalog();
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
    } catch (Throwable $throwable) {
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
        <a class="button secondary small" href="<?= e(route_url('news')) ?>">🔔 <?= e($t('notifications')) ?></a>
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
</div>
<div class="dashboard-offcanvas-backdrop" id="dashboard-widgets-backdrop" hidden></div>
<aside class="dashboard-offcanvas" id="dashboard-widgets-panel" aria-hidden="true">
  <header class="dashboard-offcanvas-header">
    <h2><?= e($t('available_widgets')) ?></h2>
    <button class="ghost" type="button" id="close-widgets-panel" aria-label="Fermer">✕</button>
  </header>
  <p class="help"><?= e($t('widgets_help')) ?></p>
  <div class="stack">
    <?php foreach ($availableToAdd as $widgetKey => $widget): ?>
      <article class="widget-card">
        <header>
          <strong><?= e((string) $widget['title']) ?></strong>
        </header>
        <p class="help"><?= e((string) ($widget['description'] ?? '')) ?></p>
        <div class="widget-body widget-preview"><?= $safeRenderWidget((string) $widgetKey, $user) ?></div>
        <button class="button small add-widget" type="button" data-widget="<?= e($widgetKey) ?>" data-title="<?= e((string) $widget['title']) ?>"><?= e($t('add')) ?></button>
      </article>
    <?php endforeach; ?>
  </div>
</aside>
<?php include __DIR__ . '/dashboard_script.js.php'; ?>
<?php
echo render_layout((string) ob_get_clean(), $t('title'));
