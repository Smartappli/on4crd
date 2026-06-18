<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$configPath = (string) (getenv('SELENIUM_CONFIG_PATH') ?: ($root . '/storage/auth/selenium-config.php'));
$schemaPath = $root . '/schema/schema.sql';

if (!is_file($schemaPath)) {
    throw new RuntimeException('Missing schema/schema.sql.');
}

$config = require $root . '/config/config.sample.php';
if (!is_array($config)) {
    throw new RuntimeException('config/config.sample.php must return an array.');
}

$dbDsn = (string) (getenv('SELENIUM_DB_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=on4crd_test;charset=utf8mb4');
$dbUser = (string) (getenv('SELENIUM_DB_USER') ?: 'root');
$dbPass = (string) (getenv('SELENIUM_DB_PASS') ?: 'root');

$config['db']['dsn'] = $dbDsn;
$config['db']['user'] = $dbUser;
$config['db']['pass'] = $dbPass;
$config['app']['env'] = 'development';
$config['app']['base_url'] = (string) (getenv('SELENIUM_APP_BASE_URL') ?: 'http://127.0.0.1:8080');
$config['app']['allow_install'] = false;
$config['app']['disable_login_in_development'] = false;
$config['app']['auth_bypass_member_id'] = 0;
$config['app']['bypass_member_modules_auth'] = false;
$config['security']['csrf_key'] = (string) (getenv('SELENIUM_CSRF_KEY') ?: 'selenium-ci-csrf-key-32-byte-secret');
$config['cache']['enabled'] = false;
$config['observability']['display_error_details'] = true;

$configDirectory = dirname($configPath);
if (!is_dir($configDirectory) && !mkdir($configDirectory, 0700, true) && !is_dir($configDirectory)) {
    throw new RuntimeException('Unable to create Selenium config directory.');
}

$configPhp = "<?php\n";
$configPhp .= "declare(strict_types=1);\n\n";
$configPhp .= 'return ' . var_export($config, true) . ";\n";
file_put_contents($configPath, $configPhp, LOCK_EX);
@chmod($configPath, 0600);

/**
 * @return list<string>
 */
function selenium_ci_sql_statements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $quote = null;
    $length = strlen($sql);

    for ($index = 0; $index < $length; $index++) {
        $char = $sql[$index];
        $buffer .= $char;

        if ($quote !== null) {
            if ($char === '\\') {
                if ($index + 1 < $length) {
                    $index++;
                    $buffer .= $sql[$index];
                }
                continue;
            }
            if ($char === $quote) {
                $quote = null;
            }
            continue;
        }

        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
            continue;
        }

        if ($char === ';') {
            $statement = trim(substr($buffer, 0, -1));
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
        }
    }

    $tail = trim($buffer);
    if ($tail !== '') {
        $statements[] = $tail;
    }

    return $statements;
}

function selenium_ci_dsn_value(string $dsn, string $key): ?string
{
    foreach (explode(';', $dsn) as $part) {
        if (!str_contains($part, '=')) {
            continue;
        }
        [$partKey, $value] = explode('=', $part, 2);
        if (strcasecmp(trim($partKey), $key) === 0) {
            return trim($value);
        }
    }

    return null;
}

function selenium_ci_without_dbname(string $dsn): string
{
    $parts = [];
    foreach (explode(';', $dsn) as $part) {
        if (stripos($part, 'dbname=') === 0) {
            continue;
        }
        $parts[] = $part;
    }

    return implode(';', $parts);
}

function selenium_ci_pdo(string $dsn, string $user, string $pass): PDO
{
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function selenium_ci_database(string $dsn, string $user, string $pass): PDO
{
    try {
        return selenium_ci_pdo($dsn, $user, $pass);
    } catch (PDOException $exception) {
        $database = selenium_ci_dsn_value($dsn, 'dbname');
        if ($database === null || $database === '') {
            throw $exception;
        }

        $server = selenium_ci_pdo(selenium_ci_without_dbname($dsn), $user, $pass);
        $server->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $database) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        return selenium_ci_pdo($dsn, $user, $pass);
    }
}

$pdo = selenium_ci_database($dbDsn, $dbUser, $dbPass);
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach (selenium_ci_sql_statements((string) file_get_contents($schemaPath)) as $statement) {
    $pdo->exec($statement);
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

putenv('ON4CRD_CONFIG_FILE=' . $configPath);
$_ENV['ON4CRD_CONFIG_FILE'] = $configPath;
$_SERVER['ON4CRD_CONFIG_FILE'] = $configPath;

require_once $root . '/app/bootstrap.php';

echo json_encode([
    'ok' => true,
    'config' => str_replace('\\', '/', $configPath),
    'schema' => str_replace('\\', '/', substr($schemaPath, strlen($root) + 1)),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
