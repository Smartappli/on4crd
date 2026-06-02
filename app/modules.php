<?php
declare(strict_types=1);

if (!function_exists('module_row')) {
function module_row(string $module): ?array
{
    static $cache = [];

    $module = trim($module);
    if ($module === '' || !table_exists('modules')) {
        return null;
    }

    if (array_key_exists($module, $cache)) {
        return $cache[$module];
    }

    $columns = ['is_enabled'];
    if (table_has_column('modules', 'visibility')) {
        $columns[] = 'visibility';
    }

    try {
        $stmt = db()->prepare('SELECT ' . implode(', ', $columns) . ' FROM modules WHERE code = ? LIMIT 1');
        $stmt->execute([$module]);
        $row = $stmt->fetch();
    } catch (Throwable) {
        $row = false;
    }
    $cache[$module] = is_array($row) ? $row : null;

    return $cache[$module];
}
}

if (!function_exists('module_enabled')) {
function module_enabled(string $module): bool
{
    if ($module === '') {
        return true;
    }

    $row = module_row($module);
    if ($row === null) {
        return true;
    }

    return (int) $row['is_enabled'] === 1;
}
}


if (!function_exists('module_visible_for_current_user')) {
function module_visible_for_current_user(string $module): bool
{
    if ($module === '') {
        return true;
    }

    $row = module_row($module);
    $visibility = (string) ($row['visibility'] ?? 'public');

    if ($visibility === 'public') {
        return true;
    }

    $user = current_user();
    if ($user === null) {
        return false;
    }

    if ($visibility === 'members') {
        return true;
    }

    if ($visibility === 'admin') {
        return has_permission('admin.access') || has_permission('modules.manage');
    }

    return false;
}
}

if (!function_exists('require_module_enabled')) {
function require_module_enabled(string $module, ?string $nextRoute = null): void
{
    if (module_enabled($module) && module_visible_for_current_user($module)) {
        return;
    }

    $row = module_row($module);
    $visibility = (string) ($row['visibility'] ?? 'public');
    if (module_enabled($module) && current_user() === null && in_array($visibility, ['members', 'admin'], true)) {
        require_login(login_next_url_for_route($nextRoute ?? (string) ($_GET['route'] ?? ''), $_GET));
    }

    http_response_code(404);
    $locale = current_locale();
    $moduleUnavailable = match ($locale) {
        'en' => 'Module unavailable.',
        'de' => 'Modul nicht verfügbar.',
        'nl' => 'Module niet beschikbaar.',
        'es' => 'Módulo no disponible.',
        'it' => 'Modulo non disponibile.',
        'pt' => 'Módulo indisponível.',
        'ar' => 'الوحدة غير متاحة.',
        'hi' => 'मॉड्यूल उपलब्ध नहीं है।',
        'ja' => 'モジュールは利用できません。',
        'zh' => '模块不可用。',
        'bn' => 'মডিউলটি উপলভ্য নয়।',
        'ru' => 'Модуль недоступен.',
        'id' => 'Modul tidak tersedia.',
        default => 'Module indisponible.',
    };
    echo render_layout('<div class="card"><h1>404</h1><p>' . e($moduleUnavailable) . '</p></div>', '404');
    exit;
}
}
