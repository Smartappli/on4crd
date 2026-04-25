<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
$user = require_login();

try {
    verify_csrf();
    if (!table_exists('dashboard_widgets')) {
        throw new RuntimeException('La table dashboard_widgets est absente.');
    }
    $payload = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    $widgets = is_array($payload['widgets'] ?? null) ? $payload['widgets'] : [];
    $catalog = widget_catalog();
    $widgets = array_values(array_filter(array_map('strval', $widgets), static fn(string $key): bool => isset($catalog[$key])));

    db()->prepare('DELETE FROM dashboard_widgets WHERE member_id = ?')->execute([(int) $user['id']]);
    $insert = db()->prepare('INSERT INTO dashboard_widgets (member_id, widget_key, position) VALUES (?, ?, ?)');
    foreach ($widgets as $position => $widgetKey) {
        $insert->execute([(int) $user['id'], $widgetKey, $position]);
    }

    echo json_encode(['ok' => true, 'widgets' => $widgets], JSON_THROW_ON_ERROR);
} catch (Throwable $throwable) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $throwable->getMessage()], JSON_THROW_ON_ERROR);
}
