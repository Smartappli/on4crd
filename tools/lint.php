<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$errors = 0;
foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $path = $file->getPathname();
    if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
        continue;
    }

    if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
        continue;
    }

    $command = 'php -l ' . escapeshellarg($path) . ' 2>&1';
    exec($command, $output, $exitCode);
    if ($exitCode !== 0) {
        fwrite(STDERR, implode(PHP_EOL, $output) . PHP_EOL);
        $errors++;
    }
}

if ($errors > 0) {
    fwrite(STDERR, sprintf("%d fichier(s) PHP invalide(s).\n", $errors));
    exit(1);
}

fwrite(STDOUT, "Lint PHP OK.\n");
