<?php
declare(strict_types=1);

function config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;
    static $pathCache = [];
    if ($config === null) {
        $configFile = __DIR__ . '/../config/config.php';
        if (!is_file($configFile)) {
            $sampleConfigFile = __DIR__ . '/../config/config.sample.php';
            if (PHP_SAPI !== 'cli' || !is_file($sampleConfigFile)) {
                throw new RuntimeException('Missing config/config.php. Copy config.sample.php first.');
            }
            $configFile = $sampleConfigFile;
        }
        $config = require $configFile;
    }

    if ($key === null) {
        return $config;
    }

    if (array_key_exists($key, $pathCache)) {
        return $pathCache[$key];
    }

    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    $pathCache[$key] = $value;
    return $pathCache[$key];
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = (string) config('db.dsn', '');
    $user = (string) config('db.user', '');
    $pass = (string) config('db.pass', '');
    if ($dsn === '') {
        throw new RuntimeException('Configuration DB manquante (db.dsn).');
    }

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function auth(): ?\Delight\Auth\Auth
{
    static $auth = false;

    if ($auth instanceof \Delight\Auth\Auth) {
        return $auth;
    }
    if ($auth === null) {
        return null;
    }

    if (!class_exists(\Delight\Auth\Auth::class)) {
        $auth = null;
        return null;
    }

    $pdo = db();
    try {
        $auth = new \Delight\Auth\Auth($pdo);
    } catch (Throwable $throwable) {
        $auth = null;
        return null;
    }

    return $auth;
}

function table_exists(string $table): bool
{
    static $cache = null;
    static $fallbackCache = [];
    $normalized = strtolower(trim($table));
    if ($normalized === '') {
        return false;
    }
    if (is_array($cache)) {
        if (isset($cache[$normalized])) {
            return true;
        }
        if (array_key_exists($normalized, $fallbackCache)) {
            return $fallbackCache[$normalized];
        }

        try {
            $stmt = db()->prepare(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
            );
            $stmt->execute([$normalized]);
            $exists = (int) $stmt->fetchColumn() > 0;
            $fallbackCache[$normalized] = $exists;
            if ($exists) {
                $cache[$normalized] = true;
            }

            return $exists;
        } catch (Throwable) {
            return false;
        }
    }

    try {
        $stmt = db()->query('SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()');
        $loadedTables = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $tableName) {
            $loadedTables[strtolower((string) $tableName)] = true;
        }
        $cache = $loadedTables;

        return isset($cache[$normalized]);
    } catch (Throwable) {
        if (array_key_exists($normalized, $fallbackCache)) {
            return $fallbackCache[$normalized];
        }

        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$normalized]);
        $fallbackCache[$normalized] = (int) $stmt->fetchColumn() > 0;

        return $fallbackCache[$normalized];
    }
}

function table_has_column(string $table, string $column): bool
{
    static $cache = [];

    $table = strtolower(trim($table));
    $column = strtolower(trim($column));
    if ($table === '' || $column === '') {
        return false;
    }

    $cacheKey = $table . '.' . $column;
    if (array_key_exists($cacheKey, $cache)) {
        if ($cache[$cacheKey]) {
            return true;
        }
        // A runtime migration may have added this column after an earlier miss.
    }

    try {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $stmt->execute([$table, $column]);
        $cache[$cacheKey] = (int) $stmt->fetchColumn() > 0;
    } catch (Throwable) {
        $cache[$cacheKey] = false;
    }

    return $cache[$cacheKey];
}

function table_has_index(string $table, string $index): bool
{
    static $cache = [];

    $table = strtolower(trim($table));
    $index = strtolower(trim($index));
    if ($table === '' || $index === '') {
        return false;
    }

    $cacheKey = $table . '.' . $index;
    if (array_key_exists($cacheKey, $cache)) {
        if ($cache[$cacheKey]) {
            return true;
        }
        // A runtime migration may have added this index after an earlier miss.
    }

    try {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?'
        );
        $stmt->execute([$table, $index]);
        $cache[$cacheKey] = (int) $stmt->fetchColumn() > 0;
    } catch (Throwable) {
        $cache[$cacheKey] = false;
    }

    return $cache[$cacheKey];
}
