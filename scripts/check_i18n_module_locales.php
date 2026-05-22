<?php
declare(strict_types=1);

$base = __DIR__ . '/../app/i18n';
$expectedCount = 14;
$ok = true;

$dirs = array_filter(scandir($base) ?: [], static function (string $name) use ($base): bool {
    return $name !== '.' && $name !== '..' && is_dir($base . '/' . $name);
});

sort($dirs);

foreach ($dirs as $dir) {
    $files = glob($base . '/' . $dir . '/*.php') ?: [];
    $count = count($files);

    if ($count !== $expectedCount) {
        $ok = false;
        fwrite(STDERR, sprintf("[FAIL] %s: %d language files (expected %d)\n", $dir, $count, $expectedCount));
    }
}

if ($ok) {
    fwrite(STDOUT, sprintf("[OK] All i18n module directories contain %d language files.\n", $expectedCount));
    exit(0);
}

exit(1);
