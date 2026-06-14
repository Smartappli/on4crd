<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$skipDirectories = [
    '.git' => true,
    '.idea' => true,
    '.phpunit.cache' => true,
    'node_modules' => true,
    'playwright-report' => true,
    'test-results' => true,
    'vendor' => true,
];

$autoload = $root . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

$parser = null;
if (class_exists(\PhpParser\ParserFactory::class)) {
    $parser = (new \PhpParser\ParserFactory())->createForHostVersion();
}

$directoryIterator = new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS);
$filterIterator = new RecursiveCallbackFilterIterator(
    $directoryIterator,
    static function (SplFileInfo $current) use ($skipDirectories): bool {
        if (!$current->isDir()) {
            return true;
        }

        return !isset($skipDirectories[$current->getFilename()]);
    }
);
$iterator = new RecursiveIteratorIterator(
    $filterIterator,
    RecursiveIteratorIterator::LEAVES_ONLY
);

$errors = 0;
foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $path = $file->getPathname();
    if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
        continue;
    }

    if ($parser !== null) {
        $source = file_get_contents($path);
        if ($source === false) {
            fwrite(STDERR, sprintf("Impossible de lire %s\n", $path));
            $errors++;
            continue;
        }

        try {
            $parser->parse($source);
        } catch (\PhpParser\Error $error) {
            fwrite(STDERR, sprintf("%s:%d: %s\n", $path, $error->getStartLine(), $error->getMessage()));
            $errors++;
        }
    } else {
        $command = 'php -l ' . escapeshellarg($path) . ' 2>&1';
        exec($command, $output, $exitCode);
        if ($exitCode !== 0) {
            fwrite(STDERR, implode(PHP_EOL, $output) . PHP_EOL);
            $errors++;
        }
    }
}

if ($errors > 0) {
    fwrite(STDERR, sprintf("%d fichier(s) PHP invalide(s).\n", $errors));
    exit(1);
}

fwrite(STDOUT, "Lint PHP OK.\n");
