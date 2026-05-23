<?php
declare(strict_types=1);

$configFile = __DIR__ . '/../config/config.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "config/config.php is required.\n");
    exit(1);
}

require_once __DIR__ . '/../app/bootstrap.php';

if (!table_exists('member_library_documents')) {
    fwrite(STDERR, "member_library_documents table is missing.\n");
    exit(1);
}

function tag_norm(string $tag): string
{
    $clean = trim($tag);
    if ($clean === '') {
        return '';
    }
    $clean = mb_strtolower($clean, 'UTF-8');
    $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;
    return trim($clean);
}

function split_tags(string $value): array
{
    $parts = explode(',', $value);
    $out = [];
    foreach ($parts as $part) {
        $tag = trim($part);
        if ($tag !== '') {
            $out[] = $tag;
        }
    }
    return $out;
}

function unique_preserve_order(array $tags): array
{
    $seen = [];
    $out = [];
    foreach ($tags as $tag) {
        $key = mb_strtolower(trim((string) $tag), 'UTF-8');
        if ($key === '' || isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $out[] = trim((string) $tag);
    }
    return $out;
}

$from = '';
$to = '';
$apply = in_array('--apply', $argv, true);
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--from=')) {
        $from = trim((string) substr($arg, 7));
    } elseif (str_starts_with($arg, '--to=')) {
        $to = trim((string) substr($arg, 5));
    }
}

if ($apply && ($from === '' || $to === '')) {
    fwrite(STDERR, "When using --apply, provide both --from=... and --to=...\n");
    exit(1);
}

$rows = db()->query('SELECT id, tags FROM member_library_documents WHERE tags IS NOT NULL AND tags <> ""')->fetchAll() ?: [];

if (!$apply) {
    $variants = [];
    foreach ($rows as $row) {
        $tags = split_tags((string) ($row['tags'] ?? ''));
        foreach ($tags as $tag) {
            $norm = tag_norm($tag);
            if ($norm === '') {
                continue;
            }
            if (!isset($variants[$norm])) {
                $variants[$norm] = [];
            }
            $variants[$norm][$tag] = ($variants[$norm][$tag] ?? 0) + 1;
        }
    }

    ksort($variants);
    $report = [];
    foreach ($variants as $norm => $bucket) {
        if (count($bucket) < 2) {
            continue;
        }
        arsort($bucket);
        $report[] = [
            'normalized' => $norm,
            'variants' => $bucket,
        ];
    }

    echo json_encode([
        'mode' => 'report',
        'duplicates' => $report,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

$fromNorm = tag_norm($from);
$toNorm = tag_norm($to);
if ($fromNorm === '' || $toNorm === '') {
    fwrite(STDERR, "Invalid --from/--to value.\n");
    exit(1);
}

$updated = 0;
$stmt = db()->prepare('UPDATE member_library_documents SET tags = ? WHERE id = ?');
foreach ($rows as $row) {
    $id = (int) ($row['id'] ?? 0);
    if ($id <= 0) {
        continue;
    }
    $tags = split_tags((string) ($row['tags'] ?? ''));
    if ($tags === []) {
        continue;
    }
    $changed = false;
    foreach ($tags as $idx => $tag) {
        if (tag_norm($tag) === $fromNorm) {
            $tags[$idx] = $to;
            $changed = true;
        }
    }
    if (!$changed) {
        continue;
    }
    $tags = unique_preserve_order($tags);
    $stmt->execute([implode(',', $tags), $id]);
    $updated++;
}

echo json_encode([
    'mode' => 'apply',
    'from' => $from,
    'to' => $to,
    'updated_documents' => $updated,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
