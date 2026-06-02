<?php
declare(strict_types=1);

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
