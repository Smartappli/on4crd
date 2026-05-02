<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
$user = require_login();

try {
    verify_csrf();
    if (!table_exists('dashboard_widgets')) {
        throw new RuntimeException('La table dashboard_widgets est absente.');
    }
    $rawPayload = (string) file_get_contents('php://input');
    $payload = $rawPayload !== '' ? json_decode($rawPayload, true) : [];
    if (!is_array($payload)) {
        $payload = [];
    }
    $widgetsInput = is_array($payload['widgets'] ?? null) ? $payload['widgets'] : [];
    $catalog = widget_catalog();
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
