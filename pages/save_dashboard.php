<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
$user = require_login();
$locale = current_locale();
$i18n = [
    'fr' => ['missing_table' => 'La table dashboard_widgets est absente.'],
    'en' => ['missing_table' => 'The dashboard_widgets table is missing.'],
    'de' => ['missing_table' => 'Die Tabelle dashboard_widgets fehlt.'],
    'nl' => ['missing_table' => 'De tabel dashboard_widgets ontbreekt.'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

try {
    verify_csrf();
    if (!table_exists('dashboard_widgets')) {
        throw new RuntimeException($t('missing_table'));
    }
    $rawPayload = (string) file_get_contents('php://input');
    $payload = $rawPayload !== '' ? json_decode($rawPayload, true) : [];
    if (!is_array($payload)) {
        $payload = [];
    }
    $widgetsInput = is_array($payload['widgets'] ?? null) ? $payload['widgets'] : [];
    $catalog = enabled_widget_catalog();
    $widgets = [];
    $seen = [];
    $maxWidgets = 24;
    foreach ($widgetsInput as $item) {
        if (count($widgets) >= $maxWidgets) {
            break;
        }

        if (is_string($item)) {
            $widgetKey = $item;
            $config = [];
        } elseif (is_array($item)) {
            $widgetKey = (string) ($item['key'] ?? '');
            $config = is_array($item['config'] ?? null) ? $item['config'] : [];
        } else {
            continue;
        }

        if ($widgetKey === '' || !isset($catalog[$widgetKey]) || isset($seen[$widgetKey])) {
            continue;
        }

        $seen[$widgetKey] = true;
        $widgets[] = ['key' => $widgetKey, 'config' => $config];
    }

    db()->prepare('DELETE FROM dashboard_widgets WHERE member_id = ?')->execute([(int) $user['id']]);
    $insert = db()->prepare('INSERT INTO dashboard_widgets (member_id, widget_key, config_json, position) VALUES (?, ?, ?, ?)');
    foreach ($widgets as $position => $widget) {
        $insert->execute([(int) $user['id'], $widget['key'], json_encode($widget['config'], JSON_UNESCAPED_SLASHES), $position]);
    }

    echo json_encode(['ok' => true, 'widgets' => $widgets], JSON_THROW_ON_ERROR);
} catch (Throwable $throwable) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $throwable->getMessage()], JSON_THROW_ON_ERROR);
}
