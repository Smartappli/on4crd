<?php
declare(strict_types=1);

$pagesDir = __DIR__ . '/../pages';
$files = glob($pagesDir . '/*.php') ?: [];

$adminFiles = array_values(array_filter($files, static fn(string $f): bool => str_contains(basename($f), 'admin')));
$publicFiles = array_values(array_filter($files, static fn(string $f): bool => !str_contains(basename($f), 'admin')));

$noLayout = [];
$hardcodedAdminTitles = [];
foreach ($files as $file) {
    $content = (string) file_get_contents($file);
    if (!str_contains($content, 'render_layout(')) {
        $noLayout[] = basename($file);
    }
    if (preg_match('/render_layout\([^\n]+,\s*\'[^\']+[À-ÿA-Za-z][^\']*\'\)/u', $content) === 1 && str_contains(basename($file), 'admin')) {
        $hardcodedAdminTitles[] = basename($file);
    }
}

$cmd = 'php ' . escapeshellarg(__DIR__ . '/i18n_audit.php');
$all = shell_exec($cmd) ?? '';
$public = shell_exec($cmd . ' --public-only') ?? '';
$allLines = substr_count(trim($all), "\n") + (trim($all) === '' ? 0 : 1);
$publicLines = substr_count(trim($public), "\n") + (trim($public) === '' ? 0 : 1);

$result = [
    'timestamp_utc' => gmdate('c'),
    'translation_findings_all_lines' => $allLines,
    'translation_findings_public_lines' => $publicLines,
    'pages_without_render_layout' => $noLayout,
    'admin_pages_with_hardcoded_layout_title' => $hardcodedAdminTitles,
    'admin_pages_count' => count($adminFiles),
    'public_pages_count' => count($publicFiles),
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
