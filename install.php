<?php
declare(strict_types=1);

$rootDir = __DIR__;
$configFile = $rootDir . '/config/config.php';
$installLockFile = $rootDir . '/storage/install.lock';

/**
 * @param array<string, mixed> $payload
 */
function installer_render_html(string $title, string $body, array $payload = []): void
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . $safeTitle . '</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:24px}.card{max-width:820px;margin:0 auto;background:#111827;border-radius:12px;padding:20px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}label{display:block;margin:8px 0;font-size:14px}input{width:100%;padding:10px;border-radius:8px;border:1px solid #334155;background:#0b1220;color:#f8fafc}.full{grid-column:1/-1}button{padding:10px 16px;background:#2563eb;color:#fff;border:0;border-radius:8px;cursor:pointer}.help{font-size:13px;color:#93c5fd}pre{background:#020617;padding:10px;border-radius:8px;overflow:auto}</style>';
    echo '</head><body><div class="card"><h1>' . $safeTitle . '</h1>' . $body . '</div></body></html>';
}

function installer_generate_csrf_key(): string
{
    return bin2hex(random_bytes(24));
}

function installer_apply_schema(PDO $pdo, string $schema): void
{
    $statements = preg_split('/;\s*(?:\r\n|\r|\n|$)/', $schema) ?: [];
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }

        try {
            $pdo->exec($statement);
        } catch (PDOException $exception) {
            if (str_contains($exception->getMessage(), 'Duplicate key name')) {
                continue;
            }

            throw $exception;
        }
    }
}

/**
 * @param array<string, string> $values
 */
function installer_build_config_php(array $values): string
{
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $values['db_host'],
        $values['db_port'],
        $values['db_name']
    );

    $config = [
        'db' => [
            'dsn' => $dsn,
            'user' => $values['db_user'],
            'pass' => $values['db_pass'],
        ],
        'app' => [
            'site_name' => $values['site_name'],
            'base_url' => $values['base_url'],
            'default_locale' => 'fr',
            'supported_locales' => ['fr', 'en', 'de', 'nl'],
            'session_name' => 'on4crd_session',
            'allow_install' => true,
            'maintenance' => [
                'enabled' => false,
                'secret' => '',
                'allowed_routes' => ['login', 'robots.txt', 'sitemap.xml'],
            ],
        ],
        'security' => [
            'csrf_key' => $values['csrf_key'],
        ],
        'cache' => [
            'enabled' => true,
            'default_ttl' => 300,
            'directory' => __DIR__ . '/../storage/cache/data',
        ],
        'tracking' => [
            'matomo_url' => '',
            'matomo_site_id' => '',
        ],
        'social' => [
            'album_webhooks' => [],
        ],
        'translation' => [
            'provider' => 'deepl',
            'deepl_api_key' => '',
            'cache_ttl' => 604800,
        ],
        'radio_data' => [
            'cache_ttl' => 900,
            'noaa_scales_url' => 'https://services.swpc.noaa.gov/products/noaa-scales.json',
            'noaa_kp_url' => 'https://services.swpc.noaa.gov/products/noaa-planetary-k-index.json',
            'noaa_flux_url' => 'https://services.swpc.noaa.gov/json/solar-radio-flux.json',
            'noaa_alerts_url' => 'https://services.swpc.noaa.gov/products/alerts.json',
            'hamqth_dx_url' => 'https://www.hamqth.com/dxc_csv.php?limit=12',
            'satnogs_tle_url' => 'https://db.satnogs.org/api/tle/',
            'contest_rss_url' => 'https://www.contestcalendar.com/weeklycont.php/calendar.rss',
        ],
        'chatbot' => [
            'provider' => 'local',
            'external_api_url' => '',
            'external_api_key' => '',
        ],
        'observability' => [
            'enabled' => true,
        ],
    ];

    return "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($config, true) . ";\n";
}

if (!is_file($configFile)) {
    $error = '';
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $values = [
                'db_host' => trim((string) ($_POST['db_host'] ?? 'localhost')),
                'db_port' => trim((string) ($_POST['db_port'] ?? '3306')),
                'db_name' => trim((string) ($_POST['db_name'] ?? 'on4crd')),
                'db_user' => trim((string) ($_POST['db_user'] ?? 'on4crd')),
                'db_pass' => (string) ($_POST['db_pass'] ?? ''),
                'site_name' => trim((string) ($_POST['site_name'] ?? 'ON4CRD')),
                'base_url' => trim((string) ($_POST['base_url'] ?? '')),
                'csrf_key' => trim((string) ($_POST['csrf_key'] ?? installer_generate_csrf_key())),
            ];

            if ($values['db_host'] === '' || $values['db_name'] === '' || $values['db_user'] === '') {
                throw new RuntimeException('Les paramètres DB host/name/user sont requis.');
            }

            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $values['db_host'], $values['db_port'], $values['db_name']);
            new PDO($dsn, $values['db_user'], $values['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            $configPhp = installer_build_config_php($values);
            if (!is_dir(dirname($configFile)) && !mkdir(dirname($configFile), 0755, true) && !is_dir(dirname($configFile))) {
                throw new RuntimeException('Impossible de créer le dossier config/.');
            }
            if (file_put_contents($configFile, $configPhp, LOCK_EX) === false) {
                throw new RuntimeException('Écriture de config/config.php impossible.');
            }

            $success = 'Configuration créée avec succès. Passez maintenant à l\'initialisation de la base ci-dessous.';
        } catch (Throwable $throwable) {
            $error = $throwable->getMessage();
        }
    }

    $csrfSuggestion = installer_generate_csrf_key();
    $body = '';
    if ($success !== '') {
        $body .= '<p style="color:#22c55e"><strong>' . htmlspecialchars($success, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong></p>';
        $body .= '<p><a href="install.php" style="color:#93c5fd">Continuer l\'installation</a></p>';
    }
    if ($error !== '') {
        $body .= '<p style="color:#f87171"><strong>' . htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong></p>';
    }

    $body .= '<p class="help">Étape 1/2 — création automatique de <code>config/config.php</code>.</p>';
    $body .= '<form method="post"><div class="grid">';
    $fields = [
        ['db_host', 'DB Host', 'localhost'],
        ['db_port', 'DB Port', '3306'],
        ['db_name', 'DB Name', 'on4crd'],
        ['db_user', 'DB User', 'on4crd'],
        ['db_pass', 'DB Password', ''],
        ['site_name', 'Nom du site', 'ON4CRD v3.6.1'],
        ['base_url', 'Base URL (https://example.org)', ''],
        ['csrf_key', 'Clé CSRF', $csrfSuggestion],
    ];

    foreach ($fields as [$name, $label, $value]) {
        $type = $name === 'db_pass' ? 'password' : 'text';
        $full = in_array($name, ['base_url', 'csrf_key'], true) ? ' full' : '';
        $body .= '<label class="' . $full . '">' . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $body .= '<input type="' . $type . '" name="' . htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" value="' . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"></label>';
    }

    $body .= '</div><p><button type="submit">Créer la configuration</button></p></form>';
    $body .= '<p class="help">Astuce: cette étape valide la connexion MySQL avant écriture de la config.</p>';

    installer_render_html('Assistant de déploiement ON4CRD', $body);
    return;
}

require_once $rootDir . '/app/bootstrap.php';

$message = '';
$error = '';
$installAllowed = (bool) config('app.allow_install', false);
if (!$installAllowed || is_file($installLockFile)) {
    http_response_code(403);
    installer_render_html(
        'Installation verrouillée',
        '<p>Activez temporairement <code>app.allow_install</code> puis supprimez le verrou uniquement pour l\'installation initiale.</p>'
    );
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $schema = file_get_contents(__DIR__ . '/schema/schema.sql');
        if ($schema === false) {
            throw new RuntimeException('Impossible de lire le schéma SQL.');
        }
        installer_apply_schema(db(), $schema);

        $permissions = [
            'admin.access' => 'Accès administration','members.manage' => 'Gérer les membres','news.submit' => 'Proposer des actualités','news.moderate' => 'Modérer et publier les actualités','articles.manage' => 'Gérer les articles techniques','wiki.edit' => 'Contribuer au wiki','wiki.moderate' => 'Valider le wiki','albums.manage' => 'Gérer les albums','albums.sync' => 'Synchroniser les albums publics','dashboard.manage' => 'Gérer le tableau de bord','qsl.manage' => 'Utiliser le module QSL','chatbot.manage' => 'Voir les logs du chatbot','ads.submit' => 'Gérer ses publicités','ads.moderate' => 'Modérer les publicités','ads.manage_all' => 'Gérer toutes les publicités et statistiques','modules.manage' => 'Gérer les modules du site','press.manage' => 'Gérer les contacts et communiqués de presse','editorial.manage' => 'Gérer les contenus éditoriaux multilingues','translations.review' => 'Relire et valider les traductions','live_feeds.manage' => 'Gérer finement les flux live','events.manage' => 'Gérer l’agenda et les événements','shop.manage' => 'Gérer la boutique','auctions.manage' => 'Gérer les enchères',
        ];
        $roles = [
            'super_admin' => array_keys($permissions),
            'member' => ['dashboard.manage', 'wiki.edit', 'qsl.manage'],
        ];

        $permStmt = db()->prepare('INSERT IGNORE INTO permissions (code, label) VALUES (?, ?)');
        foreach ($permissions as $code => $label) { $permStmt->execute([$code, $label]); }
        $roleStmt = db()->prepare('INSERT IGNORE INTO roles (code, label) VALUES (?, ?)');
        foreach ($roles as $code => $codes) { $roleStmt->execute([$code, ucwords(str_replace('_', ' ', $code))]); }

        $roleMap = []; foreach (db()->query('SELECT id, code FROM roles')->fetchAll() as $role) { $roleMap[$role['code']] = (int) $role['id']; }
        $permMap = []; foreach (db()->query('SELECT id, code FROM permissions')->fetchAll() as $perm) { $permMap[$perm['code']] = (int) $perm['id']; }
        $rolePermStmt = db()->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
        foreach ($roles as $roleCode => $permCodes) { foreach ($permCodes as $permCode) { $rolePermStmt->execute([$roleMap[$roleCode], $permMap[$permCode]]); } }

        seed_modules(); seed_dashboard_widgets(); seed_ad_placements(); seed_live_feeds();

        $callsign = strtoupper(trim((string) ($_POST['callsign'] ?? 'ON4CRD')));
        $fullName = trim((string) ($_POST['full_name'] ?? 'Administrateur'));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($password === '') { throw new RuntimeException('Mot de passe requis.'); }
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        db()->prepare(
            'INSERT INTO members (callsign, full_name, email, password_hash, qth, locator, bio, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE
                 full_name = VALUES(full_name),
                 email = VALUES(email),
                 password_hash = VALUES(password_hash),
                 qth = VALUES(qth),
                 locator = VALUES(locator),
                 bio = VALUES(bio),
                 is_active = 1'
        )->execute([$callsign, $fullName, $email !== '' ? $email : null, $hash, 'Durnal', 'JO20', 'Administrateur principal du site']);

        $memberIdStmt = db()->prepare('SELECT id FROM members WHERE callsign = ? LIMIT 1');
        $memberIdStmt->execute([$callsign]);
        $memberId = (int) $memberIdStmt->fetchColumn();
        if ($memberId <= 0) {
            throw new RuntimeException('Impossible de retrouver le compte administrateur après création.');
        }

        db()->prepare('INSERT IGNORE INTO member_roles (member_id, role_id) VALUES (?, ?)')->execute([$memberId, $roleMap['super_admin']]);

        file_put_contents($installLockFile, 'installed ' . date('c'));
        $message = 'Installation terminée. Le verrou a été créé : désactivez maintenant app.allow_install puis connectez-vous.';
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$content = '<div class="card"><h1>Installation ON4CRD v3.6.1</h1><p class="help">Étape 2/2 — initialisation de la base et création du compte administrateur.</p>';
if ($message !== '') { $content .= '<div class="flash flash-success">' . e($message) . '</div>'; }
if ($error !== '') { $content .= '<div class="flash flash-error">' . e($error) . '</div>'; }
$content .= '<form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '"><label>Indicatif admin<input type="text" name="callsign" value="ON4CRD" required></label><label>Nom complet<input type="text" name="full_name" value="Administrateur" required></label><label>Email<input type="email" name="email"></label><label>Mot de passe<input type="password" name="password" required></label><button class="button">Installer</button></form></div>';

installer_render_html('Installation', $content);
