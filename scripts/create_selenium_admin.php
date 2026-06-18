<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

/**
 * @return array<string, string>
 */
function selenium_admin_env_file_values(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $values = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim((string) $line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $values[trim($key)] = trim($value);
    }

    return $values;
}

function selenium_admin_secret(): string
{
    $raw = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');

    return $raw . 'aA1!';
}

function selenium_admin_write_env_file(string $path, string $username, string $password): void
{
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create storage/auth directory.');
    }

    file_put_contents(
        $path,
        "SELENIUM_ADMIN_USER={$username}\nSELENIUM_ADMIN_PASSWORD={$password}\n",
        LOCK_EX
    );
}

/**
 * @return list<string>
 */
function selenium_admin_visibility_columns(): array
{
    return [
        'visibility_photo',
        'visibility_email',
        'visibility_phone',
        'visibility_full_name',
        'visibility_first_name',
        'visibility_last_name',
        'visibility_country',
        'visibility_address',
        'visibility_postal_code',
        'visibility_qth',
        'visibility_locator',
        'visibility_licence_class',
        'visibility_operator_since',
        'visibility_qsl',
        'visibility_qrz',
        'visibility_uba',
        'visibility_favourite_bands',
        'visibility_favourite_modes',
        'visibility_station',
        'visibility_antennas',
        'visibility_interests',
        'visibility_online',
    ];
}

/**
 * @param array<string, mixed> $values
 */
function selenium_admin_member_upsert(int $authUserId, string $username, string $password, array $values): int
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM members WHERE auth_user_id = ? OR UPPER(callsign) = ? ORDER BY id ASC LIMIT 1');
    $stmt->execute([$authUserId, $username]);
    $memberId = (int) ($stmt->fetchColumn() ?: 0);

    $columns = [];
    foreach ($values as $column => $value) {
        if (table_has_column('members', (string) $column)) {
            $columns[(string) $column] = $value;
        }
    }

    foreach (selenium_admin_visibility_columns() as $column) {
        if (table_has_column('members', $column)) {
            $columns[$column] = 'private';
        }
    }

    if ($memberId > 0) {
        $assignments = [];
        $params = [];
        foreach ($columns as $column => $value) {
            $assignments[] = '`' . str_replace('`', '``', $column) . '` = ?';
            $params[] = $value;
        }
        $params[] = $memberId;

        $pdo->prepare('UPDATE members SET ' . implode(', ', $assignments) . ' WHERE id = ? LIMIT 1')->execute($params);

        return $memberId;
    }

    $columns['auth_user_id'] = $authUserId;
    $columns['callsign'] = $username;
    $columns['full_name'] = (string) ($columns['full_name'] ?? 'Selenium Admin');
    $columns['password_hash'] = password_hash($password, PASSWORD_DEFAULT);

    $insertColumns = array_keys($columns);
    $quotedColumns = array_map(static fn(string $column): string => '`' . str_replace('`', '``', $column) . '`', $insertColumns);
    $placeholders = implode(', ', array_fill(0, count($insertColumns), '?'));
    $params = array_map(static fn(string $column): mixed => $columns[$column], $insertColumns);
    $pdo->prepare('INSERT INTO members (' . implode(', ', $quotedColumns) . ') VALUES (' . $placeholders . ')')->execute($params);

    return (int) $pdo->lastInsertId();
}

$envFile = __DIR__ . '/../storage/auth/selenium-admin.env';
$envValues = selenium_admin_env_file_values($envFile);
$username = strtoupper(trim((string) (getenv('SELENIUM_ADMIN_USER') ?: ($envValues['SELENIUM_ADMIN_USER'] ?? 'SELENIUMADMIN'))));
$password = (string) (getenv('SELENIUM_ADMIN_PASSWORD') ?: ($envValues['SELENIUM_ADMIN_PASSWORD'] ?? ''));

if ($username === '' || preg_match('/^[A-Z0-9]{3,32}$/', $username) !== 1) {
    throw new RuntimeException('SELENIUM_ADMIN_USER must contain 3 to 32 uppercase letters or digits.');
}
if ($password === '') {
    $password = selenium_admin_secret();
}

try {
    db()->query('SELECT 1');
} catch (Throwable $throwable) {
    throw new RuntimeException('Database connection unavailable for Selenium admin creation: ' . $throwable->getMessage(), 0, $throwable);
}

if (!table_exists('users') || !table_exists('members')) {
    throw new RuntimeException('Required users/members tables are unavailable.');
}

ensure_core_roles_permissions();

$email = member_auth_email_for_shared_contact($username);
$pdo = db();
$userStmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$userStmt->execute([$username]);
$authUserId = (int) ($userStmt->fetchColumn() ?: 0);
$authPasswordHash = \Delight\Auth\PasswordHash::from($password);
if (!is_string($authPasswordHash) || $authPasswordHash === '') {
    throw new RuntimeException('Unable to hash Selenium admin password.');
}

if ($authUserId <= 0) {
    $pdo->prepare(
        'INSERT INTO users (email, password, username, status, verified, resettable, roles_mask, registered, force_logout)
         VALUES (?, ?, ?, 0, 1, 1, 0, ?, 0)'
    )->execute([$email, $authPasswordHash, $username, time()]);
    $authUserId = (int) $pdo->lastInsertId();
} else {
    $pdo->prepare('UPDATE users SET email = ?, password = ?, status = 0, verified = 1, resettable = 1, force_logout = force_logout + 1 WHERE id = ? LIMIT 1')
        ->execute([$email, $authPasswordHash, $authUserId]);
}

$memberId = selenium_admin_member_upsert($authUserId, $username, $password, [
    'auth_user_id' => $authUserId,
    'callsign' => $username,
    'first_name' => 'Selenium',
    'last_name' => 'Admin',
    'full_name' => 'Selenium Admin',
    'email' => null,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'password_change_required' => 0,
    'password_reset_forced_at' => null,
    'country' => null,
    'address' => null,
    'postal_code' => null,
    'phone' => null,
    'qth' => null,
    'locator' => null,
    'licence_class' => null,
    'operator_since' => null,
    'cq_zone' => null,
    'itu_zone' => null,
    'qsl_via' => null,
    'lotw_username' => null,
    'eqsl_username' => null,
    'qrz_url' => null,
    'website' => null,
    'is_uba_member' => 0,
    'uba_member_number' => null,
    'favourite_bands' => null,
    'favourite_modes' => null,
    'station_equipment' => null,
    'antennas' => null,
    'interests' => null,
    'is_active' => 1,
    'is_committee' => 0,
    'directory_hidden' => 1,
]);

$roleStmt = $pdo->prepare('SELECT id FROM roles WHERE code = ? LIMIT 1');
$roleStmt->execute(['admin']);
$adminRoleId = (int) ($roleStmt->fetchColumn() ?: 0);
if ($adminRoleId <= 0) {
    throw new RuntimeException('Admin role unavailable.');
}
$pdo->prepare('INSERT IGNORE INTO member_roles (member_id, role_id) VALUES (?, ?)')->execute([$memberId, $adminRoleId]);

selenium_admin_write_env_file($envFile, $username, $password);

$permissionStmt = $pdo->prepare(
    'SELECT 1
     FROM permissions p
     INNER JOIN role_permissions rp ON rp.permission_id = p.id
     INNER JOIN member_roles mr ON mr.role_id = rp.role_id
     WHERE mr.member_id = ? AND p.code = ?
     LIMIT 1'
);
$permissionStmt->execute([$memberId, 'admin.access']);
$hasAdminAccess = (bool) $permissionStmt->fetchColumn();

$hiddenStmt = $pdo->prepare('SELECT directory_hidden FROM members WHERE id = ? LIMIT 1');
$hiddenStmt->execute([$memberId]);
$directoryHidden = (int) ($hiddenStmt->fetchColumn() ?: 0) === 1;

echo json_encode([
    'username' => $username,
    'auth_user_id' => $authUserId,
    'member_id' => $memberId,
    'admin_access' => $hasAdminAccess,
    'directory_hidden' => $directoryHidden,
    'env_file' => str_replace('\\', '/', substr($envFile, strlen(dirname(__DIR__)) + 1)),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
