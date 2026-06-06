<?php
declare(strict_types=1);

$configFile = __DIR__ . '/../config/config.php';
if (!is_file($configFile)) {
    echo json_encode([
        'snapshot_at' => date('c'),
        'error' => 'missing_config',
        'message' => 'config/config.php is required to run observability snapshot.',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

require_once __DIR__ . '/../app/bootstrap.php';

function table_count_if_exists(string $table, string $where = '1=1'): int
{
    if (!table_exists($table)) {
        return -1;
    }
    $stmt = db()->query('SELECT COUNT(*) FROM ' . $table . ' WHERE ' . $where);
    return (int) ($stmt ? $stmt->fetchColumn() : 0);
}

function percentile(array $values, float $p): float
{
    if ($values === []) {
        return 0.0;
    }
    sort($values);
    $idx = (int) floor((count($values) - 1) * max(0.0, min(1.0, $p)));
    return (float) $values[$idx];
}

$snapshotAt = date('c');
$windowHours = 24;
$since = date('Y-m-d H:i:s', time() - ($windowHours * 3600));

$modules = [
    'articles' => table_count_if_exists('articles', 'status = "published"'),
    'wiki' => table_count_if_exists('wiki_pages'),
    'library_docs' => table_count_if_exists('member_library_documents'),
    'classified_ads_active' => table_count_if_exists('classified_ads', classifieds_active_where_sql()),
    'rag_chunks' => table_count_if_exists('rag_chunks'),
];

$chatbot = [
    'requests_24h' => 0,
    'errors_24h' => 0,
    'error_rate' => 0.0,
    'latency_p50_ms' => 0.0,
    'latency_p95_ms' => 0.0,
];

if (table_exists('chatbot_logs')) {
    $rows = db()->prepare('SELECT latency_ms, had_error FROM chatbot_logs WHERE created_at >= ? ORDER BY id DESC LIMIT 5000');
    $rows->execute([$since]);
    $logs = $rows->fetchAll() ?: [];
    $latencies = [];
    $errors = 0;
    foreach ($logs as $row) {
        if (!is_array($row)) {
            continue;
        }
        $lat = (int) ($row['latency_ms'] ?? 0);
        if ($lat > 0) {
            $latencies[] = $lat;
        }
        if ((int) ($row['had_error'] ?? 0) === 1) {
            $errors++;
        }
    }
    $total = count($logs);
    $chatbot['requests_24h'] = $total;
    $chatbot['errors_24h'] = $errors;
    $chatbot['error_rate'] = $total > 0 ? round($errors / $total, 4) : 0.0;
    $chatbot['latency_p50_ms'] = round(percentile($latencies, 0.50), 1);
    $chatbot['latency_p95_ms'] = round(percentile($latencies, 0.95), 1);
}

$slo = [
    'chatbot_error_rate_max' => 0.05,
    'chatbot_latency_p95_ms_max' => 2500.0,
    'rag_chunks_min' => 50,
];

$alerts = [];
if ($chatbot['requests_24h'] > 0 && $chatbot['error_rate'] > $slo['chatbot_error_rate_max']) {
    $alerts[] = 'chatbot_error_rate_high';
}
if ($chatbot['requests_24h'] > 0 && $chatbot['latency_p95_ms'] > $slo['chatbot_latency_p95_ms_max']) {
    $alerts[] = 'chatbot_latency_p95_high';
}
if ($modules['rag_chunks'] >= 0 && $modules['rag_chunks'] < $slo['rag_chunks_min']) {
    $alerts[] = 'rag_chunks_low';
}

$output = [
    'snapshot_at' => $snapshotAt,
    'window_hours' => $windowHours,
    'modules' => $modules,
    'chatbot' => $chatbot,
    'slo' => $slo,
    'alerts' => $alerts,
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
