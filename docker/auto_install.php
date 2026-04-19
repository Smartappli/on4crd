<?php
declare(strict_types=1);

$options = getopt('', [
    'db-host:',
    'db-port:',
    'db-name:',
    'db-user:',
    'db-pass:',
    'admin-callsign:',
    'admin-name:',
    'admin-email:',
    'admin-password:',
]);

$required = [
    'db-host', 'db-port', 'db-name', 'db-user', 'db-pass',
    'admin-callsign', 'admin-name', 'admin-email', 'admin-password',
];

foreach ($required as $key) {
    if (!isset($options[$key]) || trim((string) $options[$key]) === '') {
        fwrite(STDERR, "Missing required option --{$key}\n");
        exit(1);
    }
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $options['db-host'],
    $options['db-port'],
    $options['db-name']
);

$pdo = new PDO($dsn, (string) $options['db-user'], (string) $options['db-pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$schema = file_get_contents(__DIR__ . '/../schema/schema.sql');
if ($schema === false) {
    throw new RuntimeException('Unable to read schema/schema.sql');
}
$statements = preg_split('/;\s*(?:\r\n|\r|\n|$)/', $schema) ?: [];
foreach ($statements as $statement) {
    $statement = trim($statement);
    if ($statement === '') {
        continue;
    }

    try {
        $pdo->exec($statement);
    } catch (PDOException $exception) {
        $isDuplicateIndex = str_contains($exception->getMessage(), 'Duplicate key name');
        if ($isDuplicateIndex) {
            continue;
        }
        throw $exception;
    }
}

$permissions = [
    'admin.access' => 'Accès administration',
    'members.manage' => 'Gérer les membres',
    'news.submit' => 'Proposer des actualités',
    'news.moderate' => 'Modérer et publier les actualités',
    'articles.manage' => 'Gérer les articles techniques',
    'wiki.edit' => 'Contribuer au wiki',
    'wiki.moderate' => 'Valider le wiki',
    'albums.manage' => 'Gérer les albums',
    'albums.sync' => 'Synchroniser les albums publics',
    'dashboard.manage' => 'Gérer le tableau de bord',
    'qsl.manage' => 'Utiliser le module QSL',
    'chatbot.manage' => 'Voir les logs du chatbot',
    'ads.submit' => 'Gérer ses publicités',
    'ads.moderate' => 'Modérer les publicités',
    'ads.manage_all' => 'Gérer toutes les publicités et statistiques',
    'modules.manage' => 'Gérer les modules du site',
    'press.manage' => 'Gérer les contacts et communiqués de presse',
    'editorial.manage' => 'Gérer les contenus éditoriaux multilingues',
    'translations.review' => 'Relire et valider les traductions',
    'live_feeds.manage' => 'Gérer finement les flux live',
    'events.manage' => 'Gérer l’agenda et les événements',
    'shop.manage' => 'Gérer la boutique',
    'auctions.manage' => 'Gérer les enchères',
];

$pdo->beginTransaction();

$permStmt = $pdo->prepare('INSERT INTO permissions (code, label) VALUES (?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)');
foreach ($permissions as $code => $label) {
    $permStmt->execute([$code, $label]);
}

$pdo->prepare('INSERT INTO roles (code, label) VALUES (?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
    ->execute(['super_admin', 'Super Admin']);

$roleId = (int) $pdo->query("SELECT id FROM roles WHERE code = 'super_admin' LIMIT 1")->fetchColumn();
$fetchPermId = $pdo->prepare('SELECT id FROM permissions WHERE code = ? LIMIT 1');
$attachRolePerm = $pdo->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
foreach (array_keys($permissions) as $code) {
    $fetchPermId->execute([$code]);
    $permissionId = (int) $fetchPermId->fetchColumn();
    if ($permissionId > 0) {
        $attachRolePerm->execute([$roleId, $permissionId]);
    }
}

$callsign = strtoupper(trim((string) $options['admin-callsign']));
$name = trim((string) $options['admin-name']);
$email = trim((string) $options['admin-email']);
$passwordHash = password_hash((string) $options['admin-password'], PASSWORD_ARGON2ID);

$pdo->prepare(
    'INSERT INTO members (callsign, full_name, email, password_hash, is_active)
     VALUES (?, ?, ?, ?, 1)
     ON DUPLICATE KEY UPDATE
         full_name = VALUES(full_name),
         email = VALUES(email),
         password_hash = VALUES(password_hash),
         is_active = 1'
)->execute([$callsign, $name, $email !== '' ? $email : null, $passwordHash]);

$memberIdStmt = $pdo->prepare('SELECT id FROM members WHERE callsign = ? LIMIT 1');
$memberIdStmt->execute([$callsign]);
$memberId = (int) $memberIdStmt->fetchColumn();
if ($memberId <= 0) {
    throw new RuntimeException('Unable to fetch admin member after upsert');
}

$pdo->prepare('INSERT IGNORE INTO member_roles (member_id, role_id) VALUES (?, ?)')->execute([$memberId, $roleId]);

$pdo->commit();

$lockFile = __DIR__ . '/../storage/install.lock';
if (!is_file($lockFile)) {
    file_put_contents($lockFile, 'installed by docker ' . date(DATE_ATOM));
}

echo "Auto-install done. Admin: {$callsign}\n";
