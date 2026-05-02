<?php
declare(strict_types=1);

$user = require_login();
$availableWidgets = widget_catalog();
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
    $selectedWidgets = array_map(static fn(string $key): array => ['key' => $key, 'config' => []], ['welcome', 'propagation', 'club_status', 'chatbot']);
}
$selectedKeys = array_map(static fn(array $widget): string => (string) $widget['key'], $selectedWidgets);
$availableToAdd = array_filter($availableWidgets, static fn(string $key): bool => !in_array($key, $selectedKeys, true), ARRAY_FILTER_USE_KEY);

$dashboardConfig = [
    'renderBase' => base_url('index.php?route=widget_render&widget='),
    'saveUrl' => base_url('index.php?route=save_dashboard'),
    'saveEnabled' => $dashboardPersistenceEnabled,
    'refreshMs' => 90000,
    'csrf' => csrf_token(),
];

ob_start();
?>
<div class="split-home split">
  <section class="card">
    <div class="row-between">
      <div>
        <h1>Tableau de bord membre</h1>
      </div>
      <div class="actions">
        <a class="button secondary" href="<?= e(route_url('newsletter')) ?>">Newsletter</a>
        <a class="button secondary" href="<?= e(route_url('chatbot')) ?>">Raymond vous répond</a>
        <button class="button secondary" id="save-dashboard" type="button" <?= $dashboardPersistenceEnabled ? '' : 'disabled' ?>>Enregistrer la disposition</button>
        <span class="help" id="dashboard-save-status" role="status" aria-live="polite"></span>
      </div>
    </div>
    <?php if (!$dashboardPersistenceEnabled): ?>
      <p class="flash flash-error">La table <code>dashboard_widgets</code> est absente : la disposition des widgets ne peut pas être enregistrée.</p>
    <?php endif; ?>
    <div id="dashboard-grid" class="widget-grid" data-config='<?= e(json_encode($dashboardConfig, JSON_UNESCAPED_SLASHES)) ?>'>
      <?php foreach ($selectedWidgets as $selectedWidget): ?>
        <?php $widgetKey = (string) $selectedWidget['key']; ?>
        <article class="widget-card" draggable="true" aria-grabbed="false" data-widget="<?= e($widgetKey) ?>" data-widget-config='<?= e(json_encode($selectedWidget['config'], JSON_UNESCAPED_SLASHES)) ?>'>
          <header>
            <strong><?= e($availableWidgets[$widgetKey]['title'] ?? $widgetKey) ?></strong>
            <button class="ghost remove-widget" type="button">✕</button>
          </header>
          <div class="widget-body"><?= render_widget($widgetKey, $user) ?></div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
  <aside class="card">
    <h2>Widgets disponibles</h2>
    <p class="help">Installez vos widgets, puis glissez-déposez pour réordonner la grille.</p>
    <div class="stack">
      <?php foreach ($availableToAdd as $widgetKey => $widget): ?>
        <article class="widget-card">
          <header>
            <strong><?= e((string) $widget['title']) ?></strong>
          </header>
          <p class="help"><?= e((string) ($widget['description'] ?? '')) ?></p>
          <div class="widget-body widget-preview"><?= render_widget((string) $widgetKey, $user) ?></div>
          <button class="button small add-widget" type="button" data-widget="<?= e($widgetKey) ?>" data-title="<?= e((string) $widget['title']) ?>">Ajouter</button>
        </article>
      <?php endforeach; ?>
    </div>
  </aside>
</div>
<script nonce="<?= e(csp_nonce()) ?>">window.dashboardConfig = <?= json_encode($dashboardConfig, JSON_UNESCAPED_SLASHES) ?>;</script>
<?php
echo render_layout((string) ob_get_clean(), 'Tableau de bord');
