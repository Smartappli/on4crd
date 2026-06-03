<?php
declare(strict_types=1);

if (!function_exists('core_permission_catalog')) {
/**
 * @return array<string, string>
 */
function core_permission_catalog(): array
{
    return [
        'admin.access' => 'Acces administration',
        'members.manage' => 'Gerer les membres',
        'news.submit' => 'Proposer des actualites',
        'news.moderate' => 'Moderer et publier les actualites',
        'articles.manage' => 'Gerer les articles techniques',
        'wiki.edit' => 'Contribuer au wiki',
        'wiki.moderate' => 'Valider le wiki',
        'albums.manage' => 'Gerer les albums',
        'albums.sync' => 'Synchroniser les albums publics',
        'dashboard.manage' => 'Gerer le tableau de bord',
        'qsl.manage' => 'Utiliser le module QSL',
        'chatbot.manage' => 'Voir les logs du chatbot',
        'ads.submit' => 'Gerer ses publicites',
        'ads.moderate' => 'Moderer les publicites',
        'ads.manage_all' => 'Gerer toutes les publicites et statistiques',
        'modules.manage' => 'Gerer les modules du site',
        'press.manage' => 'Gerer les contacts et communiques de presse',
        'editorial.manage' => 'Gerer les contenus editoriaux multilingues',
        'translations.review' => 'Relire et valider les traductions',
        'live_feeds.manage' => 'Gerer finement les flux live',
        'events.manage' => 'Gerer l agenda et les evenements',
        'shop.manage' => 'Gerer la boutique',
        'auctions.manage' => 'Gerer les encheres',
    ];
}
}

if (!function_exists('configured_administrator_callsigns')) {
/**
 * @return list<string>
 */
function configured_administrator_callsigns(): array
{
    return ['ON4DG', 'ON5MDB'];
}
}

if (!function_exists('ensure_core_roles_permissions')) {
function ensure_core_roles_permissions(): bool
{
    try {
        $permissions = core_permission_catalog();
        $permissionStmt = db()->prepare(
            'INSERT INTO permissions (code, label)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE label = VALUES(label)'
        );
        foreach ($permissions as $code => $label) {
            $permissionStmt->execute([$code, $label]);
        }

        $roles = [
            'admin' => 'Administrateur',
            'super_admin' => 'Super Admin',
            'member' => 'Membre',
        ];
        $roleStmt = db()->prepare(
            'INSERT INTO roles (code, label)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE label = VALUES(label)'
        );
        foreach ($roles as $code => $label) {
            $roleStmt->execute([$code, $label]);
        }

        $roleMap = [];
        foreach (db()->query('SELECT id, code FROM roles')->fetchAll() ?: [] as $role) {
            $roleMap[(string) $role['code']] = (int) $role['id'];
        }
        $permissionMap = [];
        foreach (db()->query('SELECT id, code FROM permissions')->fetchAll() ?: [] as $permission) {
            $permissionMap[(string) $permission['code']] = (int) $permission['id'];
        }

        $rolePermissions = [
            'admin' => array_keys($permissions),
            'super_admin' => array_keys($permissions),
            'member' => ['dashboard.manage', 'wiki.edit', 'qsl.manage'],
        ];
        $rolePermissionStmt = db()->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
        foreach ($rolePermissions as $roleCode => $permissionCodes) {
            $roleId = (int) ($roleMap[$roleCode] ?? 0);
            if ($roleId <= 0) {
                continue;
            }
            foreach ($permissionCodes as $permissionCode) {
                $permissionId = (int) ($permissionMap[$permissionCode] ?? 0);
                if ($permissionId > 0) {
                    $rolePermissionStmt->execute([$roleId, $permissionId]);
                }
            }
        }
    } catch (Throwable) {
        return false;
    }

    return true;
}
}

if (!function_exists('ensure_configured_administrator_roles')) {
/**
 * @param list<string>|null $callsigns
 */
function ensure_configured_administrator_roles(?array $callsigns = null): bool
{
    if (!ensure_core_roles_permissions()) {
        return false;
    }

    $callsigns = $callsigns ?? configured_administrator_callsigns();
    $callsigns = array_values(array_unique(array_filter(array_map(
        static fn(string $callsign): string => strtoupper(trim($callsign)),
        $callsigns
    ))));
    if ($callsigns === []) {
        return true;
    }

    try {
        $roleStmt = db()->prepare('SELECT id FROM roles WHERE code = ? LIMIT 1');
        $roleStmt->execute(['admin']);
        $roleId = (int) $roleStmt->fetchColumn();
        if ($roleId <= 0) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($callsigns), '?'));
        $memberStmt = db()->prepare('SELECT id FROM members WHERE UPPER(callsign) IN (' . $placeholders . ')');
        $memberStmt->execute($callsigns);

        $assignmentStmt = db()->prepare('INSERT IGNORE INTO member_roles (member_id, role_id) VALUES (?, ?)');
        foreach ($memberStmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $memberId) {
            $memberId = (int) $memberId;
            if ($memberId > 0) {
                $assignmentStmt->execute([$memberId, $roleId]);
            }
        }
    } catch (Throwable) {
        return false;
    }

    return true;
}
}

if (!function_exists('has_permission')) {
function has_permission(string $permission): bool
{
    static $permissionCache = [];
    static $schemaReady = null;

    $user = current_user();
    if ($user === null || $permission === '') {
        return false;
    }

    $userId = (int) $user['id'];
    $cacheKey = $userId . '|' . $permission;
    if (array_key_exists($cacheKey, $permissionCache)) {
        return $permissionCache[$cacheKey];
    }

    if ($schemaReady === null) {
        $schemaReady = table_exists('permissions')
            && table_exists('roles')
            && table_exists('member_roles')
            && table_exists('member_permissions')
            && table_exists('role_permissions');
    }
    if (!$schemaReady) {
        $permissionCache[$cacheKey] = false;
        return $permissionCache[$cacheKey];
    }

    $stmt = db()->prepare(
        'SELECT 1
         FROM permissions p
         LEFT JOIN member_permissions mp ON mp.permission_id = p.id AND mp.member_id = ?
         LEFT JOIN role_permissions rp ON rp.permission_id = p.id
         LEFT JOIN member_roles mr ON mr.role_id = rp.role_id AND mr.member_id = ?
         WHERE p.code = ?
           AND (mp.member_id IS NOT NULL OR mr.member_id IS NOT NULL)
         LIMIT 1'
    );
    $stmt->execute([$userId, $userId, $permission]);

    $permissionCache[$cacheKey] = (bool) $stmt->fetchColumn();
    return $permissionCache[$cacheKey];
}
}

if (!function_exists('require_permission')) {
function require_permission(string $permission): void
{
    require_login();
    if (has_permission($permission)) {
        return;
    }

    http_response_code(403);
    echo render_layout('<div class="card"><h1>403</h1><p>Accès refusé.</p></div>', 'Accès refusé');
    exit;
}
}
