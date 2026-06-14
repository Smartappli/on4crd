<?php
declare(strict_types=1);

/**
 * Lightweight source duplication gate.
 *
 * The metric intentionally targets application code, not translated dictionaries,
 * dependency trees, generated reports or SQL fixtures.
 */

if (!function_exists('duplication_default_roots')) {
function duplication_default_roots(): array
{
    return ['app', 'pages', 'tools', 'scripts', 'assets/js/modules', 'assets/css/modules'];
}
}

if (!function_exists('duplication_default_excluded_segments')) {
function duplication_default_excluded_segments(): array
{
    return [
        '.git',
        '.idea',
        '.phpunit.cache',
        'node_modules',
        'vendor',
        'storage',
        'playwright-report',
        'test-results',
        'app/i18n',
        'assets/sql',
    ];
}
}

if (!function_exists('duplication_default_excluded_files')) {
function duplication_default_excluded_files(): array
{
    return [
        'tools/update_admin_wave_translations.php',
    ];
}
}

if (!function_exists('duplication_normalize_path')) {
function duplication_normalize_path(string $path): string
{
    return trim(str_replace('\\', '/', $path), '/');
}
}

if (!function_exists('duplication_is_excluded_path')) {
function duplication_is_excluded_path(string $path, array $excludedSegments, array $excludedFiles): bool
{
    $normalizedPath = duplication_normalize_path($path);

    foreach ($excludedFiles as $excludedFile) {
        if ($normalizedPath === duplication_normalize_path((string) $excludedFile)) {
            return true;
        }
    }

    foreach ($excludedSegments as $segment) {
        $segment = duplication_normalize_path((string) $segment);
        if ($segment !== '' && ($normalizedPath === $segment || str_starts_with($normalizedPath, $segment . '/'))) {
            return true;
        }
    }

    return false;
}
}

if (!function_exists('duplication_collect_files')) {
function duplication_collect_files(string $root, array $roots, array $extensions, array $excludedSegments, array $excludedFiles): array
{
    $files = [];
    $root = rtrim($root, '/\\');
    foreach ($roots as $relativeRoot) {
        $absoluteRoot = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string) $relativeRoot);
        if (!is_dir($absoluteRoot)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absoluteRoot, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
                continue;
            }
            $extension = strtolower($fileInfo->getExtension());
            if (!isset($extensions[$extension])) {
                continue;
            }
            $relativePath = duplication_normalize_path(substr($fileInfo->getPathname(), strlen($root) + 1));
            if (duplication_is_excluded_path($relativePath, $excludedSegments, $excludedFiles)) {
                continue;
            }
            $files[] = $relativePath;
        }
    }

    sort($files);

    return array_values(array_unique($files));
}
}

if (!function_exists('duplication_strip_comments')) {
function duplication_strip_comments(string $source, string $extension): string
{
    if ($extension === 'php') {
        $clean = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                $clean .= str_repeat("\n", substr_count((string) $token[1], "\n"));
                continue;
            }
            $clean .= is_array($token) ? (string) $token[1] : (string) $token;
        }

        return $clean;
    }

    $source = preg_replace('~/\*.*?\*/~s', '', $source) ?? $source;
    $source = preg_replace('~(^|\s)//.*$~m', '$1', $source) ?? $source;

    return $source;
}
}

if (!function_exists('duplication_normalize_lines')) {
function duplication_normalize_lines(string $source, string $extension): array
{
    $source = duplication_strip_comments($source, $extension);
    $lines = preg_split('/\R/u', $source);
    if (!is_array($lines)) {
        return [];
    }

    $normalized = [];
    foreach ($lines as $lineNumber => $line) {
        $line = trim((string) $line);
        $line = preg_replace('/\s+/u', ' ', $line) ?? $line;
        if ($line === '' || $line === '<?php' || $line === '?>' || $line === 'declare(strict_types=1);') {
            continue;
        }
        if (preg_match('/^[{}\[\](),;:]+$/', $line) === 1) {
            continue;
        }
        $normalized[] = ['line' => $lineNumber + 1, 'code' => $line];
    }

    return $normalized;
}
}

if (!function_exists('duplication_analyze')) {
function duplication_analyze(string $root, array $options = []): array
{
    $blockSize = max(4, (int) ($options['block_size'] ?? 10));
    $extensions = array_fill_keys((array) ($options['extensions'] ?? ['php', 'js', 'css']), true);
    $excludedSegments = (array) ($options['excluded_segments'] ?? duplication_default_excluded_segments());
    $excludedFiles = (array) ($options['excluded_files'] ?? duplication_default_excluded_files());
    $files = duplication_collect_files(
        $root,
        (array) ($options['roots'] ?? duplication_default_roots()),
        $extensions,
        $excludedSegments,
        $excludedFiles
    );

    $records = [];
    $chunks = [];
    foreach ($files as $relativePath) {
        $absolutePath = rtrim($root, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $source = file_get_contents($absolutePath);
        if (!is_string($source)) {
            continue;
        }
        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        $lines = duplication_normalize_lines($source, $extension);
        $records[$relativePath] = $lines;
        $limit = count($lines) - $blockSize;
        for ($offset = 0; $offset <= $limit; $offset++) {
            $block = array_slice($lines, $offset, $blockSize);
            $key = hash('sha256', implode("\n", array_column($block, 'code')));
            $chunks[$key][] = ['file' => $relativePath, 'offset' => $offset, 'line' => (int) $block[0]['line']];
        }
    }

    $duplicateLines = [];
    $duplicateBlocks = [];
    foreach ($chunks as $key => $locations) {
        if (count($locations) < 2) {
            continue;
        }
        $distinctLocations = [];
        foreach ($locations as $location) {
            $distinctLocations[$location['file'] . ':' . $location['offset']] = true;
        }
        if (count($distinctLocations) < 2) {
            continue;
        }
        $duplicateBlocks[] = ['key' => $key, 'locations' => $locations];
        foreach ($locations as $location) {
            for ($lineOffset = 0; $lineOffset < $blockSize; $lineOffset++) {
                $lineRecord = $records[$location['file']][$location['offset'] + $lineOffset] ?? null;
                if (is_array($lineRecord)) {
                    $duplicateLines[$location['file'] . ':' . $lineRecord['line']] = true;
                }
            }
        }
    }

    $totalLines = 0;
    foreach ($records as $lines) {
        $totalLines += count($lines);
    }
    $duplicatedLineCount = count($duplicateLines);
    $percentage = $totalLines > 0 ? ($duplicatedLineCount / $totalLines) * 100 : 0.0;

    usort($duplicateBlocks, static fn(array $a, array $b): int => count($b['locations']) <=> count($a['locations']));

    return [
        'files' => $files,
        'block_size' => $blockSize,
        'total_lines' => $totalLines,
        'duplicated_lines' => $duplicatedLineCount,
        'percentage' => $percentage,
        'duplicate_blocks' => $duplicateBlocks,
    ];
}
}

if (!function_exists('duplication_cli')) {
function duplication_cli(array $argv): int
{
    $threshold = 3.0;
    $blockSize = 10;
    $root = dirname(__DIR__);
    foreach (array_slice($argv, 1) as $arg) {
        if (str_starts_with((string) $arg, '--threshold=')) {
            $threshold = (float) substr((string) $arg, strlen('--threshold='));
        } elseif (str_starts_with((string) $arg, '--block-size=')) {
            $blockSize = (int) substr((string) $arg, strlen('--block-size='));
        } elseif (str_starts_with((string) $arg, '--root=')) {
            $root = (string) substr((string) $arg, strlen('--root='));
        }
    }

    $result = duplication_analyze($root, ['block_size' => $blockSize]);
    printf(
        "Code duplication: %.2f%% (%d duplicated / %d significant lines, block=%d)\n",
        (float) $result['percentage'],
        (int) $result['duplicated_lines'],
        (int) $result['total_lines'],
        (int) $result['block_size']
    );

    foreach (array_slice((array) $result['duplicate_blocks'], 0, 5) as $block) {
        $locations = array_map(
            static fn(array $location): string => $location['file'] . ':' . $location['line'],
            array_slice((array) $block['locations'], 0, 4)
        );
        echo '- duplicate block: ' . implode(', ', $locations) . PHP_EOL;
    }

    if ((float) $result['percentage'] >= $threshold) {
        fwrite(STDERR, sprintf("Duplication %.2f%% exceeds threshold %.2f%%.\n", (float) $result['percentage'], $threshold));

        return 1;
    }

    return 0;
}
}

if (PHP_SAPI === 'cli' && realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    $cliArgv = is_array($_SERVER['argv'] ?? null)
        ? array_map(static fn(mixed $arg): string => (string) $arg, $_SERVER['argv'])
        : [];
    exit(duplication_cli($cliArgv));
}
