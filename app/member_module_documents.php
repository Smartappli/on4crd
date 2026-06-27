<?php
declare(strict_types=1);

if (!function_exists('member_document_module_definitions')) {
function member_document_module_definitions(): array
{
    return [
        'presentations' => [
            'route' => 'presentations',
            'admin_route' => 'admin_presentations',
            'domain' => 'presentations',
            'legacy_categories' => ['presentations'],
        ],
        'videos' => [
            'route' => 'videos',
            'admin_route' => 'admin_videos',
            'domain' => 'videos',
            'legacy_categories' => ['videos', 'medias'],
        ],
        'fichiers' => [
            'route' => 'fichiers',
            'admin_route' => 'admin_fichiers',
            'domain' => 'fichiers',
            'legacy_categories' => ['fichiers', 'telechargements'],
        ],
        'pv' => [
            'route' => 'pv',
            'admin_route' => 'admin_pv',
            'domain' => 'pv',
            'legacy_categories' => ['pv'],
            'hidden_stats' => ['formats'],
            'latest_document_cta' => true,
        ],
    ];
}
}

if (!function_exists('member_document_module_normalize')) {
function member_document_module_normalize(string $module): string
{
    $module = preg_replace('/[^a-z0-9_]/', '', strtolower($module)) ?: '';

    return $module === 'telechargements' ? 'fichiers' : $module;
}
}

if (!function_exists('member_document_module_definition')) {
function member_document_module_definition(string $module): ?array
{
    $module = member_document_module_normalize($module);
    $definitions = member_document_module_definitions();

    return isset($definitions[$module]) && is_array($definitions[$module]) ? $definitions[$module] : null;
}
}

if (!function_exists('member_document_labels')) {
function member_document_labels(string $locale): array
{
    return i18n_domain_locale('members_library', $locale);
}
}

if (!function_exists('member_document_module_labels')) {
function member_document_module_labels(string $module, string $locale): array
{
    $labels = member_document_labels($locale);
    $definition = member_document_module_definition($module);
    if ($definition === null) {
        return $labels;
    }

    $domain = (string) ($definition['domain'] ?? $module);
    $moduleLabels = i18n_domain_locale($domain, $locale);
    unset($moduleLabels['title'], $moduleLabels['intro'], $moduleLabels['meta_desc']);

    return array_replace($labels, $moduleLabels);
}
}

if (!function_exists('member_document_module_text')) {
function member_document_module_text(string $module, string $locale): array
{
    $definition = member_document_module_definition($module);
    if ($definition === null) {
        return ['title' => ucfirst($module), 'intro' => '', 'meta_desc' => ''];
    }

    $domain = (string) ($definition['domain'] ?? $module);
    $messages = i18n_domain_locale($domain, $locale);
    $title = trim((string) ($messages['title'] ?? ''));
    $intro = trim((string) ($messages['intro'] ?? ''));
    $metaDesc = trim((string) ($messages['meta_desc'] ?? ''));

    if ($title === '') {
        $title = ucfirst(str_replace('_', ' ', $module));
    }
    if ($intro === '') {
        $intro = $title;
    }
    if ($metaDesc === '') {
        $metaDesc = $intro;
    }

    return ['title' => $title, 'intro' => $intro, 'meta_desc' => $metaDesc];
}
}

if (!function_exists('member_document_current_user_is_administrator')) {
function member_document_current_user_is_administrator(): bool
{
    $user = current_user();
    if (!is_array($user)) {
        return false;
    }
    if ((int) ($user['is_admin'] ?? 0) === 1) {
        return true;
    }

    $userId = (int) ($user['id'] ?? 0);
    if (
        $userId <= 0
        || !table_exists('roles')
        || !table_exists('member_roles')
    ) {
        return false;
    }

    try {
        $stmt = db()->prepare(
            'SELECT 1
             FROM member_roles mr
             INNER JOIN roles r ON r.id = mr.role_id
             WHERE mr.member_id = ?
               AND r.code IN ("admin", "super_admin")
             LIMIT 1'
        );
        $stmt->execute([$userId]);

        return (bool) $stmt->fetchColumn();
    } catch (Throwable) {
        return false;
    }
}
}

if (!function_exists('ensure_member_module_documents_table')) {
function ensure_member_module_documents_table(): bool
{
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS member_module_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            module_code VARCHAR(80) NOT NULL,
            member_id INT NOT NULL,
            category VARCHAR(120) NOT NULL DEFAULT "general",
            subcategory VARCHAR(120) NOT NULL DEFAULT "",
            tags VARCHAR(255) NOT NULL DEFAULT "",
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            file_path VARCHAR(255) NOT NULL,
            extracted_text LONGTEXT NULL,
            legacy_library_document_id INT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_module_uploaded (module_code, uploaded_at),
            INDEX idx_member_module (member_id, module_code),
            INDEX idx_module_category (module_code, category),
            INDEX idx_module_subcategory (module_code, category, subcategory),
            INDEX idx_module_tags (module_code, tags)
        )');

        if (!table_has_column('member_module_documents', 'category')) {
            db()->exec('ALTER TABLE member_module_documents ADD COLUMN category VARCHAR(120) NOT NULL DEFAULT "general" AFTER member_id');
        }
        if (!table_has_column('member_module_documents', 'subcategory')) {
            db()->exec('ALTER TABLE member_module_documents ADD COLUMN subcategory VARCHAR(120) NOT NULL DEFAULT "" AFTER category');
        }
        if (!table_has_column('member_module_documents', 'legacy_library_document_id')) {
            db()->exec('ALTER TABLE member_module_documents ADD COLUMN legacy_library_document_id INT NULL AFTER extracted_text');
        }
        if (!table_has_index('member_module_documents', 'idx_module_category')) {
            db()->exec('ALTER TABLE member_module_documents ADD INDEX idx_module_category (module_code, category)');
        }
        if (!table_has_index('member_module_documents', 'idx_module_subcategory')) {
            db()->exec('ALTER TABLE member_module_documents ADD INDEX idx_module_subcategory (module_code, category, subcategory)');
        }
        db()->exec('UPDATE member_module_documents SET category = "general" WHERE category IS NULL OR category = ""');
        db()->exec('UPDATE member_module_documents SET subcategory = "" WHERE subcategory IS NULL');

        member_document_migrate_library_categories();

        return table_exists('member_module_documents');
    } catch (Throwable) {
        return false;
    }
}
}

if (!function_exists('member_document_migrate_library_categories')) {
function member_document_migrate_library_categories(): void
{
    if (!table_exists('member_library_documents') || !table_exists('member_module_documents')) {
        return;
    }

    $allLegacyCategories = [];
    foreach (member_document_module_definitions() as $moduleCode => $definition) {
        $legacyCategories = (array) ($definition['legacy_categories'] ?? []);
        if ($legacyCategories === []) {
            continue;
        }
        foreach ($legacyCategories as $legacyCategory) {
            $legacyCategory = trim((string) $legacyCategory);
            if ($legacyCategory !== '') {
                $allLegacyCategories[$legacyCategory] = true;
            }
        }
        $placeholders = implode(',', array_fill(0, count($legacyCategories), '?'));
        $select = db()->prepare('SELECT * FROM member_library_documents WHERE category IN (' . $placeholders . ') ORDER BY id ASC');
        $select->execute(array_values($legacyCategories));
        $rows = $select->fetchAll() ?: [];
        $exists = db()->prepare('SELECT id FROM member_module_documents WHERE legacy_library_document_id = ? AND module_code = ? LIMIT 1');
        $insert = db()->prepare(
            'INSERT INTO member_module_documents (module_code, member_id, category, subcategory, tags, title, description, file_path, extracted_text, legacy_library_document_id, uploaded_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($rows as $row) {
            $legacyId = (int) ($row['id'] ?? 0);
            if ($legacyId <= 0) {
                continue;
            }
            $exists->execute([$legacyId, $moduleCode]);
            if ($exists->fetch()) {
                continue;
            }
            $insert->execute([
                $moduleCode,
                (int) ($row['member_id'] ?? 0),
                function_exists('member_library_category_slug') ? member_library_category_slug((string) ($row['category'] ?? 'general')) : 'general',
                function_exists('member_library_subcategory_slug') ? member_library_subcategory_slug((string) ($row['subcategory'] ?? '')) : '',
                (string) ($row['tags'] ?? ''),
                (string) ($row['title'] ?? ''),
                (string) ($row['description'] ?? ''),
                (string) ($row['file_path'] ?? ''),
                (string) ($row['extracted_text'] ?? ''),
                $legacyId,
                (string) ($row['uploaded_at'] ?? date('Y-m-d H:i:s')),
            ]);
        }
    }

    if ($allLegacyCategories !== []) {
        $legacyCategories = array_keys($allLegacyCategories);
        $placeholders = implode(',', array_fill(0, count($legacyCategories), '?'));
        db()->prepare('DELETE FROM member_library_documents WHERE category IN (' . $placeholders . ')')->execute($legacyCategories);
        if (table_exists('member_library_categories')) {
            db()->prepare('DELETE FROM member_library_categories WHERE code IN (' . $placeholders . ')')->execute($legacyCategories);
        }
    }
}
}

if (!function_exists('member_document_safe_path')) {
function member_document_safe_path(string $path): ?string
{
    return safe_storage_document_path_or_null($path, [
        'storage/private/member_modules/',
        'storage/uploads/member_modules/',
        'storage/private/library/',
        'storage/uploads/library/',
    ]);
}
}

if (!function_exists('member_document_module_allows_member_management')) {
function member_document_module_allows_member_management(string $moduleCode): bool
{
    return in_array(member_document_module_normalize($moduleCode), ['presentations', 'videos'], true);
}
}

if (!function_exists('member_document_category_code')) {
function member_document_category_code(string $value): string
{
    return content_proposal_category_code($value, 120, 'general');
}
}

if (!function_exists('member_document_subcategory_code')) {
function member_document_subcategory_code(string $value): string
{
    return content_taxonomy_code($value, 120, '', true);
}
}

if (!function_exists('member_document_subsubcategory_code')) {
function member_document_subsubcategory_code(string $value): string
{
    return member_document_subcategory_code($value);
}
}

if (!function_exists('member_document_subcategory_ref')) {
function member_document_subcategory_ref(string $categoryCode, string $subcategoryCode): string
{
    $categoryCode = member_document_category_code($categoryCode !== '' ? $categoryCode : 'general');
    $subcategoryCode = member_document_subcategory_code($subcategoryCode);

    return $subcategoryCode !== '' ? ($categoryCode . ':' . $subcategoryCode) : '';
}
}

if (!function_exists('member_document_subcategory_ref_parts')) {
/**
 * @return array{category:string,subcategory:string}
 */
function member_document_subcategory_ref_parts(string $value): array
{
    $value = trim($value);
    if ($value === '') {
        return ['category' => '', 'subcategory' => ''];
    }

    $parts = explode(':', $value, 2);
    if (count($parts) === 2) {
        return [
            'category' => member_document_category_code($parts[0] !== '' ? $parts[0] : 'general'),
            'subcategory' => member_document_subcategory_code($parts[1]),
        ];
    }

    return ['category' => '', 'subcategory' => member_document_subcategory_code($value)];
}
}

if (!function_exists('member_document_subsubcategory_ref')) {
function member_document_subsubcategory_ref(string $categoryCode, string $subcategoryCode, string $subsubcategoryCode): string
{
    $categoryCode = member_document_category_code($categoryCode !== '' ? $categoryCode : 'general');
    $subcategoryCode = member_document_subcategory_code($subcategoryCode);
    $subsubcategoryCode = member_document_subsubcategory_code($subsubcategoryCode);

    return $subcategoryCode !== '' && $subsubcategoryCode !== '' ? ($categoryCode . ':' . $subcategoryCode . ':' . $subsubcategoryCode) : '';
}
}

if (!function_exists('member_document_subsubcategory_ref_parts')) {
/**
 * @return array{category:string,subcategory:string,subsubcategory:string}
 */
function member_document_subsubcategory_ref_parts(string $value): array
{
    $value = trim($value);
    if ($value === '') {
        return ['category' => '', 'subcategory' => '', 'subsubcategory' => ''];
    }

    $parts = explode(':', $value);
    if (count($parts) >= 3) {
        return [
            'category' => member_document_category_code($parts[0] !== '' ? $parts[0] : 'general'),
            'subcategory' => member_document_subcategory_code($parts[1]),
            'subsubcategory' => member_document_subsubcategory_code($parts[2]),
        ];
    }

    return ['category' => '', 'subcategory' => '', 'subsubcategory' => member_document_subsubcategory_code($value)];
}
}

if (!function_exists('member_document_taxonomy_from_input')) {
/**
 * @param array<string, string> $categories
 * @return array{category:string,subcategory:string}
 */
function member_document_taxonomy_from_input(string $moduleCode, string $categoryInput, string $subcategoryRef, array $categories, string $fallbackCategory = 'general', string $fallbackSubcategory = ''): array
{
    $category = member_document_category_from_input($categoryInput !== '' ? $categoryInput : $fallbackCategory, $categories);
    $subcategory = member_document_subcategory_code($fallbackSubcategory);
    $subcategoryRef = trim($subcategoryRef);
    if ($subcategoryRef === '') {
        return [$category, ''];
    }

    $parts = member_document_subcategory_ref_parts($subcategoryRef);
    if ($parts['subcategory'] === '') {
        return [$category, ''];
    }

    $refCategory = $parts['category'] !== ''
        ? member_document_category_from_input($parts['category'], $categories)
        : $category;
    if ($refCategory !== $category) {
        throw new RuntimeException('err_subcategory_category_mismatch');
    }

    $subcategories = member_document_subcategories_by_category($moduleCode);
    $knownSubcategories = (array) ($subcategories[$category] ?? []);
    $subcategoryExists = false;
    foreach ($knownSubcategories as $knownSubcategory) {
        if (member_document_subcategory_code((string) ($knownSubcategory['code'] ?? '')) === $parts['subcategory']) {
            $subcategoryExists = true;
            break;
        }
    }
    if (!$subcategoryExists) {
        throw new RuntimeException('err_subcategory_category_mismatch');
    }

    return [$category, $parts['subcategory']];
}
}

if (!function_exists('member_document_category_label_from_code')) {
function member_document_category_label_from_code(string $code): string
{
    $label = trim(str_replace('-', ' ', member_document_category_code($code)));
    if ($label === '') {
        $messages = function_exists('i18n_domain_locale') ? i18n_domain_locale('members_library', current_locale()) : [];

        return (string) ($messages['category_general'] ?? 'General');
    }

    return mb_convert_case($label, MB_CASE_TITLE, 'UTF-8');
}
}

if (!function_exists('member_document_default_categories')) {
/**
 * @return list<array{code:string,label:string,sort_order:int}>
 */
function member_document_default_categories(string $moduleCode = ''): array
{
    $messages = function_exists('i18n_domain_locale') ? i18n_domain_locale('members_library', current_locale()) : [];
    $defaults = [['code' => 'general', 'label' => (string) ($messages['category_general'] ?? 'General'), 'sort_order' => 1]];
    if (function_exists('member_library_default_categories')) {
        foreach (member_library_default_categories() as $category) {
            $code = member_document_category_code((string) ($category['code'] ?? ''));
            $label = content_proposal_clean_single_line((string) ($category['label'] ?? $code), 160);
            if ($code !== '' && $label !== '') {
                $defaults[] = [
                    'code' => $code,
                    'label' => $label,
                    'sort_order' => (int) ($category['sort_order'] ?? 100),
                ];
            }
        }
    }

    $seen = [];
    $result = [];
    foreach ($defaults as $category) {
        if (isset($seen[$category['code']])) {
            continue;
        }
        $seen[$category['code']] = true;
        $result[] = $category;
    }

    return $result;
}
}

if (!function_exists('member_document_default_subcategories')) {
/**
 * @return list<array{category_code:string,code:string,label:string,sort_order:int}>
 */
function member_document_default_subcategories(string $moduleCode = ''): array
{
    $defaults = [];
    if (function_exists('member_library_default_subcategories')) {
        foreach (member_library_default_subcategories() as $subcategory) {
            $categoryCode = member_document_category_code((string) ($subcategory['category_code'] ?? 'general'));
            $code = member_document_subcategory_code((string) ($subcategory['code'] ?? ''));
            $label = content_proposal_clean_single_line((string) ($subcategory['label'] ?? $code), 160);
            if ($categoryCode !== '' && $code !== '' && $label !== '') {
                $defaults[] = [
                    'category_code' => $categoryCode,
                    'code' => $code,
                    'label' => $label,
                    'sort_order' => (int) ($subcategory['sort_order'] ?? 100),
                ];
            }
        }
    }

    return $defaults;
}
}

if (!function_exists('member_document_ensure_categories_table')) {
function member_document_ensure_categories_table(string $moduleCode): bool
{
    $moduleCode = member_document_module_normalize($moduleCode);
    if ($moduleCode === '') {
        return false;
    }

    try {
        db()->exec('CREATE TABLE IF NOT EXISTS member_module_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            module_code VARCHAR(80) NOT NULL,
            code VARCHAR(120) NOT NULL,
            label VARCHAR(160) NOT NULL,
            sort_order INT NOT NULL DEFAULT 100,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_member_module_category (module_code, code),
            INDEX idx_member_module_category_module (module_code),
            INDEX idx_member_module_category_deleted (module_code, deleted_at)
        )');
        if (!table_has_column('member_module_categories', 'deleted_at')) {
            db()->exec('ALTER TABLE member_module_categories ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER sort_order');
        }
        if (!table_has_index('member_module_categories', 'idx_member_module_category_deleted')) {
            db()->exec('ALTER TABLE member_module_categories ADD INDEX idx_member_module_category_deleted (module_code, deleted_at)');
        }
        $insert = db()->prepare('INSERT IGNORE INTO member_module_categories (module_code, code, label, sort_order) VALUES (?, ?, ?, ?)');
        foreach (member_document_default_categories($moduleCode) as $category) {
            $insert->execute([$moduleCode, (string) $category['code'], (string) $category['label'], (int) $category['sort_order']]);
        }

        return table_exists('member_module_categories');
    } catch (Throwable) {
        return false;
    }
}
}

if (!function_exists('member_document_ensure_subcategories_table')) {
function member_document_ensure_subcategories_table(string $moduleCode): bool
{
    $moduleCode = member_document_module_normalize($moduleCode);
    if ($moduleCode === '') {
        return false;
    }

    try {
        db()->exec('CREATE TABLE IF NOT EXISTS member_module_subcategories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            module_code VARCHAR(80) NOT NULL,
            category_code VARCHAR(120) NOT NULL,
            code VARCHAR(120) NOT NULL,
            label VARCHAR(160) NOT NULL,
            sort_order INT NOT NULL DEFAULT 100,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_member_module_subcategory (module_code, category_code, code),
            INDEX idx_member_module_subcategory_module (module_code, category_code),
            INDEX idx_member_module_subcategory_deleted (module_code, deleted_at)
        )');
        if (!table_has_column('member_module_subcategories', 'deleted_at')) {
            db()->exec('ALTER TABLE member_module_subcategories ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER sort_order');
        }
        if (!table_has_index('member_module_subcategories', 'idx_member_module_subcategory_deleted')) {
            db()->exec('ALTER TABLE member_module_subcategories ADD INDEX idx_member_module_subcategory_deleted (module_code, deleted_at)');
        }
        $insert = db()->prepare('INSERT IGNORE INTO member_module_subcategories (module_code, category_code, code, label, sort_order) VALUES (?, ?, ?, ?, ?)');
        foreach (member_document_default_subcategories($moduleCode) as $subcategory) {
            $insert->execute([
                $moduleCode,
                (string) $subcategory['category_code'],
                (string) $subcategory['code'],
                (string) $subcategory['label'],
                (int) $subcategory['sort_order'],
            ]);
        }

        return table_exists('member_module_subcategories');
    } catch (Throwable) {
        return false;
    }
}
}

if (!function_exists('member_document_ensure_subsubcategories_table')) {
function member_document_ensure_subsubcategories_table(string $moduleCode): bool
{
    $moduleCode = member_document_module_normalize($moduleCode);
    if ($moduleCode === '') {
        return false;
    }

    try {
        member_document_ensure_subcategories_table($moduleCode);
        db()->exec('CREATE TABLE IF NOT EXISTS member_module_subsubcategories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            module_code VARCHAR(80) NOT NULL,
            category_code VARCHAR(120) NOT NULL,
            subcategory_code VARCHAR(120) NOT NULL,
            code VARCHAR(120) NOT NULL,
            label VARCHAR(160) NOT NULL,
            sort_order INT NOT NULL DEFAULT 100,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_member_module_subsubcategory (module_code, category_code, subcategory_code, code),
            INDEX idx_member_module_subsubcategory_parent (module_code, category_code, subcategory_code),
            INDEX idx_member_module_subsubcategory_deleted (module_code, deleted_at)
        )');
        if (!table_has_column('member_module_subsubcategories', 'deleted_at')) {
            db()->exec('ALTER TABLE member_module_subsubcategories ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER sort_order');
        }
        if (!table_has_index('member_module_subsubcategories', 'idx_member_module_subsubcategory_deleted')) {
            db()->exec('ALTER TABLE member_module_subsubcategories ADD INDEX idx_member_module_subsubcategory_deleted (module_code, deleted_at)');
        }

        return table_exists('member_module_subsubcategories');
    } catch (Throwable) {
        return false;
    }
}
}

if (!function_exists('member_document_categories')) {
/**
 * @return array<string, string>
 */
function member_document_categories(string $moduleCode): array
{
    $moduleCode = member_document_module_normalize($moduleCode);
    $categories = [];
    $deletedCategories = [];
    if (member_document_ensure_categories_table($moduleCode)) {
        try {
            $stmt = db()->prepare('SELECT code FROM member_module_categories WHERE module_code = ? AND deleted_at IS NOT NULL');
            $stmt->execute([$moduleCode]);
            foreach ($stmt->fetchAll() ?: [] as $row) {
                $code = member_document_category_code((string) ($row['code'] ?? ''));
                if ($code !== '') {
                    $deletedCategories[$code] = true;
                }
            }
        } catch (Throwable) {
            $deletedCategories = [];
        }
    }

    foreach (member_document_default_categories($moduleCode) as $category) {
        $code = member_document_category_code((string) $category['code']);
        if ($code !== '' && !isset($deletedCategories[$code])) {
            $categories[$code] = (string) $category['label'];
        }
    }

    if (member_document_ensure_categories_table($moduleCode)) {
        try {
            $stmt = db()->prepare('SELECT code, label FROM member_module_categories WHERE module_code = ? AND deleted_at IS NULL ORDER BY sort_order ASC, label ASC');
            $stmt->execute([$moduleCode]);
            foreach ($stmt->fetchAll() ?: [] as $row) {
                $code = member_document_category_code((string) ($row['code'] ?? ''));
                $label = content_proposal_clean_single_line((string) ($row['label'] ?? $code), 160);
                if ($code !== '' && $label !== '') {
                    $categories[$code] = $label;
                }
            }
        } catch (Throwable) {
        }
    }

    try {
        if (table_exists('member_module_documents') && table_has_column('member_module_documents', 'category')) {
            $stmt = db()->prepare('SELECT category FROM member_module_documents WHERE module_code = ? AND category IS NOT NULL AND category <> "" GROUP BY category ORDER BY category ASC');
            $stmt->execute([$moduleCode]);
            foreach ($stmt->fetchAll() ?: [] as $row) {
                $code = member_document_category_code((string) ($row['category'] ?? ''));
                if ($code !== '' && !isset($deletedCategories[$code]) && !isset($categories[$code])) {
                    $categories[$code] = member_document_category_label_from_code($code);
                }
            }
        }
    } catch (Throwable) {
    }

    return $categories;
}
}

if (!function_exists('member_document_category_from_input')) {
/**
 * @param array<string, string> $categories
 */
function member_document_category_from_input(string $value, array $categories): string
{
    $code = member_document_category_code($value);
    if ($code === '') {
        $code = 'general';
    }
    if (!isset($categories[$code])) {
        throw new RuntimeException('err_category');
    }

    return $code;
}
}

if (!function_exists('member_document_upsert_category')) {
function member_document_upsert_category(string $moduleCode, string $label): string
{
    $moduleCode = member_document_module_normalize($moduleCode);
    if (!member_document_ensure_categories_table($moduleCode)) {
        throw new RuntimeException('storage_unavailable');
    }

    $label = content_proposal_clean_single_line($label, 160);
    $code = member_document_category_code($label);
    if ($moduleCode === '' || $label === '' || $code === '') {
        throw new RuntimeException('err_category_required');
    }

    db()->prepare('INSERT INTO member_module_categories (module_code, code, label, deleted_at) VALUES (?, ?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
        ->execute([$moduleCode, $code, $label]);

    return $code;
}
}

if (!function_exists('member_document_upsert_subcategory')) {
/**
 * @param array<string, string> $categories
 * @return array{category:string,subcategory:string}
 */
function member_document_upsert_subcategory(string $moduleCode, array $categories, string $categoryInput, string $label): array
{
    $moduleCode = member_document_module_normalize($moduleCode);
    if (!member_document_ensure_subcategories_table($moduleCode)) {
        throw new RuntimeException('storage_unavailable');
    }

    $category = member_document_category_from_input($categoryInput !== '' ? $categoryInput : 'general', $categories);
    $label = content_proposal_clean_single_line($label, 160);
    $code = member_document_subcategory_code($label);
    if ($moduleCode === '' || $label === '' || $code === '') {
        throw new RuntimeException('err_subcategory_required');
    }

    db()->prepare('INSERT INTO member_module_subcategories (module_code, category_code, code, label, deleted_at) VALUES (?, ?, ?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
        ->execute([$moduleCode, $category, $code, $label]);

    return ['category' => $category, 'subcategory' => $code];
}
}

if (!function_exists('member_document_upsert_subsubcategory')) {
/**
 * @param array<string, string> $categories
 * @return array{category:string,subcategory:string,subsubcategory:string}
 */
function member_document_upsert_subsubcategory(string $moduleCode, array $categories, string $categoryInput, string $subcategoryInput, string $label): array
{
    $moduleCode = member_document_module_normalize($moduleCode);
    if (!member_document_ensure_subsubcategories_table($moduleCode)) {
        throw new RuntimeException('storage_unavailable');
    }

    [$category, $subcategory] = member_document_taxonomy_from_input(
        $moduleCode,
        $categoryInput !== '' ? $categoryInput : 'general',
        member_document_subcategory_ref($categoryInput !== '' ? $categoryInput : 'general', $subcategoryInput),
        $categories
    );
    $label = content_proposal_clean_single_line($label, 160);
    $code = member_document_subsubcategory_code($label);
    if ($moduleCode === '' || $subcategory === '' || $label === '' || $code === '') {
        throw new RuntimeException('err_subsubcategory_required');
    }

    db()->prepare('INSERT INTO member_module_subsubcategories (module_code, category_code, subcategory_code, code, label, deleted_at) VALUES (?, ?, ?, ?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
        ->execute([$moduleCode, $category, $subcategory, $code, $label]);

    return ['category' => $category, 'subcategory' => $subcategory, 'subsubcategory' => $code];
}
}

if (!function_exists('member_document_subcategory_options')) {
/**
 * @return list<array{category_code:string,code:string,label:string}>
 */
function member_document_subcategory_options(string $moduleCode): array
{
    $moduleCode = member_document_module_normalize($moduleCode);
    $options = [];
    if (member_document_ensure_subcategories_table($moduleCode)) {
        try {
            $stmt = db()->prepare('SELECT category_code, code, label FROM member_module_subcategories WHERE module_code = ? AND deleted_at IS NULL ORDER BY category_code ASC, sort_order ASC, label ASC');
            $stmt->execute([$moduleCode]);
            $rows = $stmt->fetchAll() ?: [];
        } catch (Throwable) {
            $rows = [];
        }
    } else {
        $rows = member_document_default_subcategories($moduleCode);
    }

    foreach ($rows as $row) {
        $categoryCode = member_document_category_code((string) ($row['category_code'] ?? 'general'));
        $code = member_document_subcategory_code((string) ($row['code'] ?? ''));
        $label = content_proposal_clean_single_line((string) ($row['label'] ?? $code), 160);
        if ($categoryCode === '' || $code === '' || $label === '') {
            continue;
        }
        $options[] = ['category_code' => $categoryCode, 'code' => $code, 'label' => $label];
    }

    return $options;
}
}

if (!function_exists('member_document_subcategories_by_category')) {
/**
 * @return array<string, list<array{category_code:string,code:string,label:string}>>
 */
function member_document_subcategories_by_category(string $moduleCode): array
{
    $byCategory = [];
    foreach (member_document_subcategory_options($moduleCode) as $subcategory) {
        $byCategory[$subcategory['category_code']][] = $subcategory;
    }

    return $byCategory;
}
}

if (!function_exists('member_document_visible_categories')) {
/**
 * @param array<string, string> $categories
 * @param array<string, int> $countsByCategory
 * @return array<string, string>
 */
function member_document_visible_categories(array $categories, array $countsByCategory): array
{
    $visible = [];
    foreach ($categories as $code => $label) {
        if ((int) ($countsByCategory[(string) $code] ?? 0) <= 0) {
            continue;
        }
        $visible[(string) $code] = (string) $label;
    }

    return $visible;
}
}

if (!function_exists('member_document_visible_subcategories_by_category')) {
/**
 * @param array<string, list<array<string, mixed>>> $subcategoriesByCategory
 * @param array<string, int> $countsBySubcategory
 * @return array<string, list<array<string, mixed>>>
 */
function member_document_visible_subcategories_by_category(array $subcategoriesByCategory, array $countsBySubcategory): array
{
    $visible = [];
    foreach ($subcategoriesByCategory as $categoryCode => $subcategories) {
        foreach ($subcategories as $subcategory) {
            $code = member_document_subcategory_code((string) ($subcategory['code'] ?? ''));
            $count = (int) ($countsBySubcategory[(string) $categoryCode . ':' . $code] ?? 0);
            if ($code === '' || $count <= 0) {
                continue;
            }
            $subcategory['total'] = $count;
            $visible[(string) $categoryCode][] = $subcategory;
        }
    }

    return $visible;
}
}

if (!function_exists('member_document_module_list_state')) {
/**
 * @param array<string, string> $categories
 * @param array<string, mixed> $query
 * @return array{search:string,stats:array<string,mixed>,subcategories_by_category:array<string,list<array{category_code:string,code:string,label:string}>>,visible_categories:array<string,string>,visible_subcategories_by_category:array<string,list<array<string,mixed>>>,category_filter:string,subcategory_filter:string}
 */
function member_document_module_list_state(string $moduleCode, array $categories, array $query, bool $requireSubcategoryStats): array
{
    $search = trim((string) ($query['q'] ?? ''));
    if (mb_strlen($search) > 120) {
        $search = mb_substr($search, 0, 120);
    }

    $stats = member_document_module_stats($moduleCode);
    $countsByCategory = [];
    foreach ((array) ($stats['by_category'] ?? []) as $categoryCode => $categoryTotal) {
        $countsByCategory[(string) $categoryCode] = (int) $categoryTotal;
    }
    $countsBySubcategory = [];
    foreach ((array) ($stats['by_subcategory'] ?? []) as $subcategoryKey => $subcategoryTotal) {
        $countsBySubcategory[(string) $subcategoryKey] = (int) $subcategoryTotal;
    }

    $subcategoriesByCategory = member_document_subcategories_by_category($moduleCode);
    foreach ($countsBySubcategory as $subcategoryKey => $subcategoryTotal) {
        $parts = explode(':', (string) $subcategoryKey, 2);
        if (count($parts) !== 2 || $subcategoryTotal <= 0) {
            continue;
        }
        $parentCode = member_document_category_code($parts[0]);
        $subcategoryCode = member_document_subcategory_code($parts[1]);
        if ($parentCode === '' || $subcategoryCode === '') {
            continue;
        }
        $known = false;
        foreach ($subcategoriesByCategory[$parentCode] ?? [] as $subcategoryOption) {
            if (member_document_subcategory_code((string) $subcategoryOption['code']) === $subcategoryCode) {
                $known = true;
                break;
            }
        }
        if (!$known) {
            $subcategoriesByCategory[$parentCode][] = [
                'category_code' => $parentCode,
                'code' => $subcategoryCode,
                'label' => member_document_category_label_from_code($subcategoryCode),
            ];
        }
    }

    $visibleCategories = member_document_visible_categories($categories, $countsByCategory);
    $visibleSubcategoriesByCategory = member_document_visible_subcategories_by_category($subcategoriesByCategory, $countsBySubcategory);
    $categoryFilter = '';
    $categoryInput = trim((string) ($query['category'] ?? ''));
    if ($categoryInput !== '') {
        $categoryCode = member_document_category_code($categoryInput);
        if (isset($categories[$categoryCode])) {
            $categoryFilter = $categoryCode;
        }
    }

    $subcategoryFilter = '';
    $subcategoryInput = trim((string) ($query['subcategory'] ?? ''));
    if ($subcategoryInput !== '') {
        $subcategoryCode = member_document_subcategory_code($subcategoryInput);
        if ($subcategoryCode !== '') {
            $candidateCategory = $categoryFilter;
            if ($candidateCategory === '') {
                foreach ($visibleSubcategoriesByCategory as $parentCode => $subcategories) {
                    foreach ($subcategories as $subcategoryInfo) {
                        if (member_document_subcategory_code((string) ($subcategoryInfo['code'] ?? '')) === $subcategoryCode) {
                            $candidateCategory = (string) $parentCode;
                            break 2;
                        }
                    }
                }
            }
            if ($candidateCategory !== '' && (!$requireSubcategoryStats || (int) ($countsBySubcategory[$candidateCategory . ':' . $subcategoryCode] ?? 0) > 0)) {
                $categoryFilter = $candidateCategory;
                $subcategoryFilter = $subcategoryCode;
            }
        }
    }

    return [
        'search' => $search,
        'stats' => $stats,
        'subcategories_by_category' => $subcategoriesByCategory,
        'visible_categories' => $visibleCategories,
        'visible_subcategories_by_category' => $visibleSubcategoriesByCategory,
        'category_filter' => $categoryFilter,
        'subcategory_filter' => $subcategoryFilter,
    ];
}
}

if (!function_exists('member_document_favorites_label')) {
/**
 * @param array<string, mixed> $labels
 */
function member_document_favorites_label(array $labels, string $locale = ''): string
{
    $label = trim((string) ($labels['favorites'] ?? ''));
    if ($label !== '') {
        return $label;
    }

    $messages = member_document_labels($locale !== '' ? $locale : (function_exists('current_locale') ? current_locale() : 'fr'));

    return trim((string) $messages['favorites']);
}
}

if (!function_exists('member_document_favorite_document_ids')) {
/**
 * @return list<int>
 */
function member_document_favorite_document_ids(int $memberId, string $moduleCode): array
{
    $moduleCode = member_document_module_normalize($moduleCode);
    if (
        $memberId <= 0
        || $moduleCode === ''
        || !function_exists('ensure_member_favorites_table')
        || !ensure_member_favorites_table()
        || !ensure_member_module_documents_table()
    ) {
        return [];
    }

    try {
        $stmt = db()->prepare('SELECT d.id FROM member_favorites f INNER JOIN member_module_documents d ON d.id = f.target_id WHERE f.member_id = ? AND f.target_type = ? AND d.module_code = ? ORDER BY f.created_at DESC, f.id DESC');
        $stmt->execute([$memberId, 'member_module_document', $moduleCode]);
        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    } catch (Throwable) {
        return [];
    }
}
}

if (!function_exists('member_document_video_extensions')) {
/**
 * @return list<string>
 */
function member_document_video_extensions(): array
{
    return ['mp4', 'webm', 'mov', 'm4v'];
}
}

if (!function_exists('member_document_is_video_extension')) {
function member_document_is_video_extension(string $extension): bool
{
    return in_array(strtolower($extension), member_document_video_extensions(), true);
}
}

if (!function_exists('member_document_upload_max_bytes')) {
function member_document_upload_max_bytes(string $moduleCode, string $extension): int
{
    if (member_document_module_normalize($moduleCode) === 'videos' && member_document_is_video_extension($extension)) {
        return 1024 * 1024 * 1024;
    }

    return 120 * 1024 * 1024;
}
}

if (!function_exists('member_document_store_upload')) {
function member_document_store_upload(array $file, string $moduleCode, int $memberId): array
{
    $allowedExtensions = ['pdf', 'docx', 'txt', 'md', 'html', 'htm', 'ppt', 'pptx', 'xls', 'xlsx', 'csv', 'zip', 'jpg', 'jpeg', 'png', 'webp', 'mp4', 'webm', 'mov', 'm4v'];
    $allowedMimes = [
        'pdf' => ['application/pdf', 'application/x-pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'txt' => ['text/plain', 'application/octet-stream'],
        'md' => ['text/plain', 'text/markdown', 'application/octet-stream'],
        'html' => ['text/html', 'text/plain', 'application/octet-stream'],
        'htm' => ['text/html', 'text/plain', 'application/octet-stream'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
        'xls' => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'csv' => ['text/csv', 'text/plain', 'application/octet-stream'],
        'zip' => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'mp4' => ['video/mp4', 'application/octet-stream'],
        'webm' => ['video/webm', 'application/octet-stream'],
        'mov' => ['video/quicktime', 'application/octet-stream'],
        'm4v' => ['video/x-m4v', 'video/mp4', 'application/octet-stream'],
    ];

    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('err_invalid');
    }

    $targetDir = dirname(__DIR__) . '/storage/private/member_modules/' . $moduleCode;
    $base = slugify(pathinfo($originalName, PATHINFO_FILENAME));
    if ($base === '') {
        $base = 'document';
    }
    $filename = secure_move_uploaded_file(
        $file,
        $targetDir,
        'doc_' . $memberId . '-' . $base,
        $allowedExtensions,
        $allowedMimes,
        member_document_upload_max_bytes($moduleCode, $extension)
    );

    return [
        'public_path' => 'storage/private/member_modules/' . $moduleCode . '/' . $filename,
        'absolute_path' => $targetDir . '/' . $filename,
        'extension' => $extension,
    ];
}
}

if (!function_exists('member_document_delete_file')) {
function member_document_delete_file(string $publicPath): void
{
    $safePath = member_document_safe_path($publicPath);
    if ($safePath === null || (
        !str_starts_with($safePath, 'storage/private/member_modules/')
        && !str_starts_with($safePath, 'storage/uploads/member_modules/')
    )) {
        return;
    }

    $absolute = storage_document_absolute_path($safePath);
    if (is_file($absolute)) {
        @unlink($absolute);
    }
}
}

if (!function_exists('member_document_proposal_action')) {
function member_document_proposal_action(string $summary): string
{
    $action = content_proposal_clean_single_line(
        content_proposal_detail_from_summary($summary, ['Action']),
        32
    );

    return in_array($action, ['update_document', 'delete_document'], true) ? $action : '';
}
}

if (!function_exists('member_document_proposal_document_id')) {
function member_document_proposal_document_id(string $summary): int
{
    return max(0, (int) content_proposal_detail_from_summary($summary, ['Document ID']));
}
}

if (!function_exists('member_document_proposal_detail')) {
function member_document_proposal_detail(string $summary, array $labels): string
{
    return content_proposal_detail_from_summary($summary, $labels);
}
}

if (!function_exists('member_document_create_record')) {
function member_document_create_record(
    int $memberId,
    string $moduleCode,
    string $title,
    string $description,
    string $tags,
    string $publicPath,
    string $category = 'general',
    string $subcategory = ''
): int {
    if (!ensure_member_module_documents_table()) {
        throw new RuntimeException('storage_unavailable');
    }

    $moduleCode = member_document_module_normalize($moduleCode);
    $title = content_proposal_clean_single_line($title, 255);
    $description = content_proposal_clean_multiline($description, 5000);
    $tags = content_proposal_clean_single_line($tags, 255);
    $category = member_document_category_code($category !== '' ? $category : 'general');
    $subcategory = member_document_subcategory_code($subcategory);
    $safePath = member_document_safe_path($publicPath) ?? '';
    if ($moduleCode === '' || $title === '' || $safePath === '') {
        throw new RuntimeException('err_required');
    }

    $absolute = storage_document_absolute_path($safePath);
    if (!is_file($absolute)) {
        throw new RuntimeException('err_invalid');
    }

    $existingStmt = db()->prepare('SELECT id FROM member_module_documents WHERE module_code = ? AND file_path = ? LIMIT 1');
    $existingStmt->execute([$moduleCode, $safePath]);
    $existingId = (int) ($existingStmt->fetchColumn() ?: 0);
    if ($existingId > 0) {
        return $existingId;
    }

    $extension = strtolower((string) pathinfo($safePath, PATHINFO_EXTENSION));
    $extractedText = member_document_extract_text($absolute, $extension);
    db()->prepare('INSERT INTO member_module_documents (module_code, member_id, category, subcategory, tags, title, description, file_path, extracted_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([
            $moduleCode,
            max(0, $memberId),
            $category,
            $subcategory,
            $tags,
            $title,
            $description !== '' ? $description : null,
            $safePath,
            $extractedText !== '' ? $extractedText : null,
        ]);

    return (int) db()->lastInsertId();
}
}

if (!function_exists('member_document_update_record')) {
function member_document_update_record(
    int $documentId,
    string $moduleCode,
    string $title,
    string $description,
    string $tags,
    string $replacementPublicPath = '',
    string $category = 'general',
    string $subcategory = ''
): void {
    if (!ensure_member_module_documents_table()) {
        throw new RuntimeException('storage_unavailable');
    }

    $moduleCode = member_document_module_normalize($moduleCode);
    $title = content_proposal_clean_single_line($title, 255);
    $description = content_proposal_clean_multiline($description, 5000);
    $tags = content_proposal_clean_single_line($tags, 255);
    $category = member_document_category_code($category !== '' ? $category : 'general');
    $subcategory = member_document_subcategory_code($subcategory);
    if ($documentId <= 0 || $moduleCode === '' || $title === '') {
        throw new RuntimeException('err_required');
    }

    $stmt = db()->prepare('SELECT file_path FROM member_module_documents WHERE id = ? AND module_code = ? LIMIT 1');
    $stmt->execute([$documentId, $moduleCode]);
    $currentPath = (string) ($stmt->fetchColumn() ?: '');
    if ($currentPath === '') {
        throw new RuntimeException('err_invalid');
    }

    $replacementSafePath = '';
    if ($replacementPublicPath !== '') {
        $replacementSafePath = member_document_safe_path($replacementPublicPath) ?? '';
        if ($replacementSafePath === '') {
            throw new RuntimeException('err_invalid');
        }
        $absolute = storage_document_absolute_path($replacementSafePath);
        if (!is_file($absolute)) {
            throw new RuntimeException('err_invalid');
        }
    }

    if ($replacementSafePath !== '') {
        $extension = strtolower(pathinfo($replacementSafePath, PATHINFO_EXTENSION));
        $extractedText = member_document_extract_text(storage_document_absolute_path($replacementSafePath), $extension);
        db()->prepare('UPDATE member_module_documents SET category = ?, subcategory = ?, title = ?, description = ?, tags = ?, file_path = ?, extracted_text = ? WHERE id = ? AND module_code = ?')
            ->execute([$category, $subcategory, $title, $description !== '' ? $description : null, $tags, $replacementSafePath, $extractedText !== '' ? $extractedText : null, $documentId, $moduleCode]);
        if ($currentPath !== $replacementSafePath) {
            member_document_delete_file($currentPath);
        }
    } else {
        db()->prepare('UPDATE member_module_documents SET category = ?, subcategory = ?, title = ?, description = ?, tags = ? WHERE id = ? AND module_code = ?')
            ->execute([$category, $subcategory, $title, $description !== '' ? $description : null, $tags, $documentId, $moduleCode]);
    }

    if (table_exists('member_favorites')) {
        $definition = member_document_module_definition($moduleCode) ?? [];
        $route = (string) ($definition['route'] ?? $moduleCode);
        $favoriteUrl = route_url_clean($route, ['q' => $title, 'category' => $category, 'subcategory' => $subcategory]);
        db()->prepare('UPDATE member_favorites SET title = ?, url = ? WHERE target_type = ? AND target_id = ?')
            ->execute([$title, $favoriteUrl, 'member_module_document', $documentId]);
    }
}
}

if (!function_exists('member_document_delete_record')) {
function member_document_delete_record(int $documentId, string $moduleCode): void
{
    if (!ensure_member_module_documents_table()) {
        throw new RuntimeException('storage_unavailable');
    }

    $moduleCode = member_document_module_normalize($moduleCode);
    if ($documentId <= 0 || $moduleCode === '') {
        throw new RuntimeException('err_required');
    }

    $stmt = db()->prepare('SELECT file_path FROM member_module_documents WHERE id = ? AND module_code = ? LIMIT 1');
    $stmt->execute([$documentId, $moduleCode]);
    $path = (string) ($stmt->fetchColumn() ?: '');
    if ($path === '') {
        throw new RuntimeException('err_invalid');
    }

    member_document_delete_file($path);
    db()->prepare('DELETE FROM member_module_documents WHERE id = ? AND module_code = ?')->execute([$documentId, $moduleCode]);
    if (table_exists('member_favorites')) {
        db()->prepare('DELETE FROM member_favorites WHERE target_type = ? AND target_id = ?')->execute(['member_module_document', $documentId]);
    }
}
}

if (!function_exists('member_document_apply_accepted_proposal')) {
function member_document_apply_accepted_proposal(array $proposal, string $moduleCode): ?int
{
    $moduleCode = member_document_module_normalize($moduleCode);
    if (!member_document_module_allows_member_management($moduleCode)) {
        return null;
    }

    $proposalType = (string) ($proposal['proposal_type'] ?? '');
    if ($proposalType === 'category') {
        member_document_upsert_category($moduleCode, (string) ($proposal['title'] ?? ''));

        return null;
    }

    $summary = (string) ($proposal['summary'] ?? '');
    if ($proposalType === 'subcategory') {
        $categories = member_document_categories($moduleCode);
        $category = member_document_proposal_detail($summary, ['Category', 'Thématique', 'Thematique', 'Topic']);
        member_document_upsert_subcategory($moduleCode, $categories, $category !== '' ? $category : 'general', (string) ($proposal['title'] ?? ''));

        return null;
    }

    if ($proposalType === 'subsubcategory') {
        $categories = member_document_categories($moduleCode);
        $category = member_document_proposal_detail($summary, ['Category', 'Thématique', 'Thematique', 'Topic']);
        $subcategory = member_document_proposal_detail($summary, ['Subcategory', 'Sous-thématique', 'Sous-thematique', 'Subtopic']);
        member_document_upsert_subsubcategory($moduleCode, $categories, $category !== '' ? $category : 'general', $subcategory, (string) ($proposal['title'] ?? ''));

        return null;
    }

    if ($proposalType !== 'content') {
        return null;
    }

    $action = member_document_proposal_action($summary);
    if ($action === '') {
        return member_document_create_record(
            max(0, (int) ($proposal['member_id'] ?? 0)),
            $moduleCode,
            (string) ($proposal['title'] ?? ''),
            member_document_proposal_detail($summary, ['Description']),
            member_document_proposal_detail($summary, ['Tags', 'Étiquettes', 'Etiquettes']),
            (string) ($proposal['source_ref'] ?? ''),
            member_document_proposal_detail($summary, ['Category', 'Thématique', 'Thematique', 'Topic']),
            member_document_proposal_detail($summary, ['Subcategory', 'Sous-thématique', 'Sous-thematique', 'Subtopic'])
        );
    }

    $documentId = member_document_proposal_document_id($summary);
    if ($action === 'delete_document') {
        member_document_delete_record($documentId, $moduleCode);

        return $documentId;
    }

    member_document_update_record(
        $documentId,
        $moduleCode,
        (string) ($proposal['title'] ?? ''),
        member_document_proposal_detail($summary, ['Description']),
        member_document_proposal_detail($summary, ['Tags', 'Étiquettes', 'Etiquettes']),
        (string) ($proposal['source_ref'] ?? ''),
        member_document_proposal_detail($summary, ['Category', 'Thématique', 'Thematique', 'Topic']),
        member_document_proposal_detail($summary, ['Subcategory', 'Sous-thématique', 'Sous-thematique', 'Subtopic'])
    );

    return $documentId;
}
}

if (!function_exists('member_document_extract_text')) {
function member_document_extract_text(string $path, string $extension): string
{
    $extension = strtolower($extension);
    if ($extension === 'pdf' && function_exists('article_extract_pdf_text')) {
        return article_extract_pdf_text($path);
    }
    if ($extension === 'docx' && function_exists('article_extract_docx_html')) {
        return trim(strip_tags(article_extract_docx_html($path)));
    }
    if (in_array($extension, ['txt', 'md', 'html', 'htm', 'csv'], true)) {
        $raw = @file_get_contents($path);
        if (!is_string($raw)) {
            return '';
        }
        if (in_array($extension, ['html', 'htm'], true)) {
            $raw = strip_tags($raw);
        }
        return trim((string) preg_replace('/\s+/u', ' ', $raw));
    }

    return '';
}
}

if (!function_exists('member_document_module_stats')) {
function member_document_module_stats(string $moduleCode): array
{
    $countStmt = db()->prepare('SELECT COUNT(*) FROM member_module_documents WHERE module_code = ?');
    $countStmt->execute([$moduleCode]);
    $total = (int) $countStmt->fetchColumn();

    $formatStmt = db()->prepare('SELECT file_path FROM member_module_documents WHERE module_code = ?');
    $formatStmt->execute([$moduleCode]);
    $formats = [];
    foreach ($formatStmt->fetchAll() ?: [] as $row) {
        $extension = strtolower(pathinfo((string) ($row['file_path'] ?? ''), PATHINFO_EXTENSION));
        if ($extension !== '') {
            $formats[$extension] = true;
        }
    }

    $latestStmt = db()->prepare('SELECT uploaded_at FROM member_module_documents WHERE module_code = ? ORDER BY uploaded_at DESC, id DESC LIMIT 1');
    $latestStmt->execute([$moduleCode]);
    $latest = trim((string) ($latestStmt->fetchColumn() ?: ''));

    $byCategory = [];
    $bySubcategory = [];
    try {
        $categoryStmt = db()->prepare('SELECT category, COUNT(*) AS total FROM member_module_documents WHERE module_code = ? GROUP BY category ORDER BY category ASC');
        $categoryStmt->execute([$moduleCode]);
        foreach ($categoryStmt->fetchAll() ?: [] as $row) {
            $code = member_document_category_code((string) ($row['category'] ?? 'general'));
            if ($code !== '') {
                $byCategory[$code] = (int) ($row['total'] ?? 0);
            }
        }
        $subcategoryStmt = db()->prepare('SELECT category, subcategory, COUNT(*) AS total FROM member_module_documents WHERE module_code = ? AND subcategory IS NOT NULL AND subcategory <> "" GROUP BY category, subcategory ORDER BY category ASC, subcategory ASC');
        $subcategoryStmt->execute([$moduleCode]);
        foreach ($subcategoryStmt->fetchAll() ?: [] as $row) {
            $category = member_document_category_code((string) ($row['category'] ?? 'general'));
            $subcategory = member_document_subcategory_code((string) ($row['subcategory'] ?? ''));
            if ($category !== '' && $subcategory !== '') {
                $bySubcategory[$category . ':' . $subcategory] = (int) ($row['total'] ?? 0);
            }
        }
    } catch (Throwable) {
        $byCategory = [];
        $bySubcategory = [];
    }

    return ['total' => $total, 'formats' => count($formats), 'latest' => $latest, 'by_category' => $byCategory, 'by_subcategory' => $bySubcategory];
}
}

if (!function_exists('member_document_fetch_documents')) {
/**
 * @param list<int> $favoriteIds
 */
function member_document_fetch_documents(string $moduleCode, string $search, int $limit = 60, string $category = '', string $subcategory = '', array $favoriteIds = []): array
{
    $where = ['module_code = ?'];
    $params = [$moduleCode];
    $category = trim($category) !== '' ? member_document_category_code($category) : '';
    $subcategory = trim($subcategory) !== '' ? member_document_subcategory_code($subcategory) : '';
    if ($category !== '') {
        $where[] = 'category = ?';
        $params[] = $category;
    }
    if ($subcategory !== '') {
        $where[] = 'subcategory = ?';
        $params[] = $subcategory;
    }
    if ($favoriteIds !== []) {
        $where[] = 'id IN (' . implode(',', array_fill(0, count($favoriteIds), '?')) . ')';
        array_push($params, ...$favoriteIds);
    }
    if ($search !== '') {
        $where[] = '(title LIKE ? OR description LIKE ? OR extracted_text LIKE ? OR tags LIKE ? OR category LIKE ? OR subcategory LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }
    $stmt = db()->prepare('SELECT * FROM member_module_documents WHERE ' . implode(' AND ', $where) . ' ORDER BY uploaded_at DESC, id DESC LIMIT ' . (int) $limit);
    $stmt->execute($params);

    return $stmt->fetchAll() ?: [];
}
}

if (!function_exists('render_member_document_taxonomy_fields')) {
/**
 * @param array<string, string> $categories
 * @param array<string, string> $labels
 */
function render_member_document_taxonomy_fields(string $moduleCode, array $categories, array $labels, string $selectedCategory = 'general', string $selectedSubcategory = ''): string
{
    $selectedCategory = member_document_category_code($selectedCategory !== '' ? $selectedCategory : 'general');
    $selectedSubcategory = member_document_subcategory_code($selectedSubcategory);
    $subcategoriesByCategory = member_document_subcategories_by_category($moduleCode);

    $html = '<label><span>' . e((string) $labels['category_field']) . '</span><select name="category">';
    foreach ($categories as $code => $label) {
        $html .= '<option value="' . e((string) $code) . '"' . ($selectedCategory === (string) $code ? ' selected' : '') . '>' . e((string) $label) . '</option>';
    }
    $html .= '</select></label>'
        . '<label><span>' . e((string) $labels['subcategory_field']) . '</span><select name="subcategory_ref">'
        . '<option value="">' . e((string) $labels['no_subcategory']) . '</option>';

    foreach ($subcategoriesByCategory as $parentCode => $subcategories) {
        $parentLabel = (string) ($categories[(string) $parentCode] ?? member_document_category_label_from_code((string) $parentCode));
        $html .= '<optgroup label="' . e($parentLabel) . '">';
        foreach ($subcategories as $subcategory) {
            $code = member_document_subcategory_code((string) $subcategory['code']);
            if ($code === '') {
                continue;
            }
            $html .= '<option value="' . e(member_document_subcategory_ref((string) $parentCode, $code)) . '"'
                . ($selectedCategory === (string) $parentCode && $selectedSubcategory === $code ? ' selected' : '')
                . '>' . e((string) $subcategory['label']) . '</option>';
        }
        $html .= '</optgroup>';
    }

    return $html . '</select></label>';
}
}

if (!function_exists('render_member_document_module_cards')) {
function render_member_document_module_cards(array $documents, array $labels, string $moduleCode = '', ?array $viewer = null, bool $canManage = false, array $returnQuery = []): string
{
    $html = '';
    $moduleCode = member_document_module_normalize($moduleCode);
    $viewerId = max(0, (int) ($viewer['id'] ?? 0));
    $returnSearch = (string) ($returnQuery['q'] ?? '');
    $returnCategory = (string) ($returnQuery['category'] ?? '');
    $returnSubcategory = (string) ($returnQuery['subcategory'] ?? '');
    $returnFavorites = (string) ($returnQuery['favorites'] ?? '');
    $categories = member_document_categories($moduleCode);
    $subcategoriesByCategory = member_document_subcategories_by_category($moduleCode);
    $subcategoryLabels = [];
    foreach ($subcategoriesByCategory as $parentCode => $subcategories) {
        foreach ($subcategories as $subcategory) {
            $subcategoryLabels[(string) $parentCode . ':' . (string) $subcategory['code']] = (string) $subcategory['label'];
        }
    }
    $allowMemberManagement = member_document_module_allows_member_management($moduleCode);
    foreach ($documents as $document) {
        $safePath = member_document_safe_path((string) ($document['file_path'] ?? ''));
        if ($safePath === null) {
            continue;
        }
        $extension = strtolower(pathinfo($safePath, PATHINFO_EXTENSION));
        $docTitle = trim((string) ($document['title'] ?? ''));
        if ($docTitle === '') {
            $docTitle = (string) $labels['documents'];
        }
        $docDescription = trim((string) ($document['description'] ?? ''));
        $docTags = trim((string) ($document['tags'] ?? ''));
        $docExtract = trim((string) ($document['extracted_text'] ?? ''));
        $documentId = max(0, (int) ($document['id'] ?? 0));
        $documentInlineUrl = $documentId > 0 ? route_url('member_document_preview', ['module' => $moduleCode, 'id' => $documentId]) : '';
        $documentPreviewUrl = $documentInlineUrl !== '' ? $documentInlineUrl . '#view=Fit' : '';
        $documentDownloadUrl = $documentId > 0 ? route_url('member_document_preview', ['module' => $moduleCode, 'id' => $documentId, 'download' => '1']) : '';
        $docCategory = member_document_category_code((string) ($document['category'] ?? 'general'));
        $docSubcategory = member_document_subcategory_code((string) ($document['subcategory'] ?? ''));
        $docCategoryLabel = (string) ($categories[$docCategory] ?? member_document_category_label_from_code($docCategory));
        $docSubcategoryLabel = $docSubcategory !== '' ? (string) ($subcategoryLabels[$docCategory . ':' . $docSubcategory] ?? $docSubcategory) : '';
        $canEditDocument = $allowMemberManagement && $documentId > 0 && ($canManage || ($viewerId > 0 && (int) ($document['member_id'] ?? 0) === $viewerId));
        $isFavorite = $viewerId > 0 && $documentId > 0 && function_exists('favorite_is_saved') && favorite_is_saved($viewerId, 'member_module_document', $documentId);
        $dialogId = 'member-document-edit-dialog-' . $documentId;
        $html .= '<article class="news-card feature-card member-document-card">'
            . '<span class="badge muted">' . e(strtoupper($extension)) . '</span>'
            . '<h2>' . e($docTitle) . '</h2>';
        if ($docDescription !== '') {
            $html .= '<p>' . e($docDescription) . '</p>';
        }
        if ($docTags !== '') {
            $html .= '<p class="help">' . e((string) $labels['tags']) . ': ' . e($docTags) . '</p>';
        }
        $html .= '<p class="help taxonomy-badge-row">'
            . e((string) $labels['category_field']) . ': '
            . '<span class="badge muted taxonomy-pill-category">' . e($docCategoryLabel) . '</span>'
            . ($docSubcategoryLabel !== '' ? '<span class="badge muted taxonomy-pill-subcategory">' . e($docSubcategoryLabel) . '</span>' : '')
            . '</p>';
        if ($docExtract !== '') {
            $html .= '<p class="help">' . e(mb_safe_strimwidth($docExtract, 0, 220, '...')) . '</p>';
        }
        if ($extension === 'pdf' && $documentPreviewUrl !== '') {
            $html .= '<details class="member-document-preview-toggle"><summary>' . e((string) $labels['preview']) . '</summary>'
                . '<iframe src="' . e($documentPreviewUrl) . '" class="member-document-pdf-preview" loading="lazy"></iframe></details>';
        }
        if ($moduleCode === 'videos' && member_document_is_video_extension($extension) && $documentInlineUrl !== '') {
            $html .= '<div class="member-document-video-player">'
                . '<video controls preload="metadata" playsinline src="' . e($documentInlineUrl) . '">'
                . e((string) $labels['open'])
                . '</video></div>';
        }
        $html .= '<p class="actions member-document-card-actions">';
        if ($documentDownloadUrl !== '') {
            $html .= '<a class="button secondary" href="' . e($documentDownloadUrl) . '" target="_blank" rel="noopener">' . e((string) $labels['open']) . '</a>';
        }
        if ($canEditDocument) {
            $html .= '<button class="button secondary" type="button" data-member-document-modal-open="' . e($dialogId) . '" aria-haspopup="dialog" aria-controls="' . e($dialogId) . '">' . e((string) $labels['edit_document']) . '</button>';
        }
        if ($viewerId > 0 && $documentId > 0 && function_exists('favorite_toggle')) {
            $html .= '<form method="post" class="inline-form">'
                . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="action" value="toggle_favorite_document">'
                . '<input type="hidden" name="id" value="' . $documentId . '">'
                . '<input type="hidden" name="return_q" value="' . e($returnSearch) . '">'
                . '<input type="hidden" name="return_category" value="' . e($returnCategory) . '">'
                . '<input type="hidden" name="return_subcategory" value="' . e($returnSubcategory) . '">'
                . '<input type="hidden" name="return_favorites" value="' . e($returnFavorites) . '">'
                . '<button class="button secondary" type="submit">' . ($isFavorite ? '&#9733; ' : '&#9734; ') . e((string) $labels['favorite']) . '</button>'
                . '</form>';
        }
        $html .= '</p></article>';

        if ($canEditDocument) {
            $html .= '<dialog class="member-document-dialog" id="' . e($dialogId) . '" aria-labelledby="' . e($dialogId) . '-title">'
                . '<div class="member-document-dialog-card">'
                . '<div class="member-document-dialog-header module-dialog-header">'
                . '<div><p class="module-dialog-eyebrow">' . e((string) $labels['documents']) . '</p>'
                . '<h2 id="' . e($dialogId) . '-title">' . e((string) $labels['edit_document_title']) . '</h2>'
                . '<p class="help">' . e($docTitle) . '</p></div>'
                . '<button class="member-document-dialog-close module-dialog-close" type="button" data-member-document-modal-close aria-label="' . e((string) $labels['modal_close']) . '">&times;</button>'
                . '</div>'
                . '<form method="post" enctype="multipart/form-data" class="member-document-dialog-form module-dialog-form">'
                . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="action" value="update_document">'
                . '<input type="hidden" name="id" value="' . $documentId . '">'
                . '<input type="hidden" name="return_q" value="' . e($returnSearch) . '">'
                . '<input type="hidden" name="return_category" value="' . e($returnCategory) . '">'
                . '<input type="hidden" name="return_subcategory" value="' . e($returnSubcategory) . '">'
                . '<input type="hidden" name="return_favorites" value="' . e($returnFavorites) . '">'
                . '<label><span>' . e((string) $labels['title_field']) . '</span><input type="text" name="title" value="' . e($docTitle) . '" maxlength="255" required></label>'
                . render_member_document_taxonomy_fields($moduleCode, $categories, $labels, $docCategory, $docSubcategory)
                . '<label><span>' . e((string) $labels['description_field']) . '</span><textarea name="description" rows="5" maxlength="5000">' . e($docDescription) . '</textarea></label>'
                . '<label><span>' . e((string) $labels['tags_field']) . '</span><input type="text" name="tags" value="' . e($docTags) . '" maxlength="255"></label>'
                . '<label><span>' . e((string) $labels['replace_document_file']) . '</span><input type="file" name="document_file"></label>'
                . '<p class="member-document-dialog-actions module-dialog-actions">'
                . '<button class="button" type="submit">' . e((string) $labels['save_document']) . '</button>'
                . '<button class="button secondary" type="button" data-member-document-modal-close>' . e((string) $labels['cancel']) . '</button>'
                . '</p></form>'
                . '<form method="post" class="member-document-delete-form">'
                . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="action" value="delete_document">'
                . '<input type="hidden" name="id" value="' . $documentId . '">'
                . '<input type="hidden" name="return_q" value="' . e($returnSearch) . '">'
                . '<input type="hidden" name="return_category" value="' . e($returnCategory) . '">'
                . '<input type="hidden" name="return_subcategory" value="' . e($returnSubcategory) . '">'
                . '<input type="hidden" name="return_favorites" value="' . e($returnFavorites) . '">'
                . '<p class="help">' . e($canManage ? (string) $labels['delete_document_warning_admin'] : (string) $labels['delete_document_warning']) . '</p>'
                . '<button class="button secondary member-document-danger" type="submit">' . e((string) $labels['delete']) . '</button>'
                . '</form></div></dialog>';
        }
    }

    return $html;
}
}

if (!function_exists('render_member_document_module_stats')) {
/**
 * @param array<string, int|string|null> $stats
 * @param array<string, string> $labels
 * @param list<string> $hiddenStats
 */
function render_member_document_module_stats(array $stats, array $labels, string $latestLabel, array $hiddenStats = []): string
{
    $hiddenStats = array_map('strval', $hiddenStats);
    $cards = [];
    if (!in_array('documents', $hiddenStats, true)) {
        $cards[] = '<article><span>' . e((string) $labels['documents']) . '</span><strong>' . (int) ($stats['total'] ?? 0) . '</strong></article>';
    }
    if (!in_array('formats', $hiddenStats, true)) {
        $cards[] = '<article><span>' . e((string) $labels['formats']) . '</span><strong>' . (int) ($stats['formats'] ?? 0) . '</strong></article>';
    }
    if (!in_array('latest', $hiddenStats, true)) {
        $cards[] = '<article><span>' . e((string) $labels['latest']) . '</span><strong>' . e($latestLabel) . '</strong></article>';
    }

    return '<div class="member-document-hero-stats">' . implode('', $cards) . '</div>';
}
}

if (!function_exists('render_member_document_module_page')) {
function render_member_document_module_page(string $module): void
{
    $moduleCode = member_document_module_normalize($module);
    $definition = member_document_module_definition($moduleCode);
    if ($definition === null) {
        http_response_code(404);
        echo render_layout('<div class="card"><h1>404</h1><p>' . e(i18n_error_text('module_unavailable', 'Module unavailable.')) . '</p></div>', '404');
        return;
    }

    $user = require_login();
    $locale = current_locale();
    $labels = member_document_module_labels($moduleCode, $locale);
    $memberAreaLabel = member_area_eyebrow_label($locale);
    $moduleText = member_document_module_text($moduleCode, $locale);
    $title = (string) $moduleText['title'];
    $intro = (string) $moduleText['intro'];

    set_page_meta([
        'title' => $title,
        'description' => (string) $moduleText['meta_desc'],
        'robots' => 'noindex,follow',
        'schema_type' => 'CollectionPage',
    ]);

    if (!ensure_member_module_documents_table()) {
        echo render_layout('<div class="card"><p>' . e((string) $labels['storage_unavailable']) . '</p></div>', $title);
        return;
    }

    $canManageDocuments = member_document_current_user_is_administrator();
    $canProposeDocument = member_document_module_allows_member_management($moduleCode);
    $canProposeTaxonomy = in_array($moduleCode, ['presentations', 'videos'], true);
    $canProposeSubsubcategory = $moduleCode === 'videos' && $canProposeTaxonomy;
    $proposalContactDefault = trim((string) ($user['email'] ?? ''));
    if ($proposalContactDefault === '') {
        $proposalContactDefault = trim((string) ($user['callsign'] ?? ''));
    }
    $categories = member_document_categories($moduleCode);
    member_document_ensure_subcategories_table($moduleCode);
    if ($canProposeSubsubcategory) {
        member_document_ensure_subsubcategories_table($moduleCode);
    }
    $returnUrl = static function () use ($definition, $moduleCode): string {
        return route_url_clean((string) ($definition['route'] ?? $moduleCode), [
            'q' => (string) ($_POST['return_q'] ?? $_GET['q'] ?? ''),
            'category' => (string) ($_POST['return_category'] ?? $_GET['category'] ?? ''),
            'subcategory' => (string) ($_POST['return_subcategory'] ?? $_GET['subcategory'] ?? ''),
            'favorites' => (string) ($_POST['return_favorites'] ?? $_GET['favorites'] ?? '') === '1' ? '1' : '',
        ]);
    };

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            verify_csrf();
            $action = (string) ($_POST['action'] ?? '');
            if ($action === 'toggle_favorite_document') {
                $documentId = (int) ($_POST['id'] ?? 0);
                if ($documentId > 0 && function_exists('favorite_toggle')) {
                    $docStmt = db()->prepare('SELECT id, title, category, subcategory FROM member_module_documents WHERE id = ? AND module_code = ? LIMIT 1');
                    $docStmt->execute([$documentId, $moduleCode]);
                    $document = $docStmt->fetch() ?: null;
                    if (is_array($document)) {
                        $docTitle = trim((string) ($document['title'] ?? ''));
                        if ($docTitle === '') {
                            $docTitle = (string) $labels['documents'];
                        }
                        $favoriteUrl = route_url_clean((string) ($definition['route'] ?? $moduleCode), [
                            'q' => $docTitle,
                            'category' => (string) ($document['category'] ?? ''),
                            'subcategory' => (string) ($document['subcategory'] ?? ''),
                        ]);
                        $saved = favorite_toggle((int) $user['id'], 'member_module_document', (int) $document['id'], $docTitle, $favoriteUrl);
                        notify_member((int) $user['id'], 'favorite', $saved ? (string) $labels['favorite_added'] : (string) $labels['favorite_removed'], $docTitle, $favoriteUrl);
                        set_flash('success', $saved ? (string) $labels['favorite_added_msg'] : (string) $labels['favorite_removed_msg']);
                    }
                }
                redirect_url($returnUrl());
            }
            if ($action === 'propose_category' && $canProposeTaxonomy) {
                $proposalTitle = content_proposal_clean_single_line((string) ($_POST['proposal_category_name'] ?? $_POST['proposal_category'] ?? ''), 160);
                $proposalReason = content_proposal_clean_multiline((string) ($_POST['proposal_reason'] ?? $_POST['proposal_details'] ?? ''), 1600);
                $proposalContact = content_proposal_clean_single_line((string) ($_POST['proposal_contact'] ?? $proposalContactDefault), 220);
                if ($proposalContact === '') {
                    $proposalContact = $proposalContactDefault;
                }
                if ($proposalTitle === '') {
                    throw new RuntimeException('err_category_required');
                }

                $proposalSummary = content_proposal_details_text([
                    (string) $labels['propose_category_reason'] => $proposalReason,
                ]);
                $proposalStatus = $canManageDocuments ? 'accepted' : 'pending';
                $proposalId = content_proposal_create((int) $user['id'], $moduleCode, 'category', $proposalTitle, $proposalSummary, $proposalContact, '', $proposalStatus);
                if ($canManageDocuments) {
                    $category = member_document_upsert_category($moduleCode, $proposalTitle);
                    set_flash('success', (string) $labels['category_created_direct']);
                    redirect_url(route_url_clean((string) ($definition['route'] ?? $moduleCode), ['category' => $category]));
                }

                content_proposal_notify_site((string) $labels['propose_category_subject'], [
                    'area' => $moduleCode,
                    'proposal_type' => 'category',
                    'title' => $proposalTitle,
                    'summary' => $proposalSummary,
                    'contact' => $proposalContact,
                    'source_ref' => 'content_proposals#' . $proposalId,
                ]);
                set_flash('success', (string) $labels['proposal_recorded']);
                redirect('my_requests');
            }
            if ($action === 'propose_subcategory' && $canProposeTaxonomy) {
                $proposalTitle = content_proposal_clean_single_line((string) ($_POST['proposal_subcategory_name'] ?? $_POST['proposal_subcategory'] ?? ''), 160);
                $proposalCategory = member_document_category_from_input((string) ($_POST['proposal_parent_category'] ?? $_POST['proposal_category'] ?? 'general'), $categories);
                $proposalReason = content_proposal_clean_multiline((string) ($_POST['proposal_reason'] ?? $_POST['proposal_details'] ?? ''), 1600);
                $proposalContact = content_proposal_clean_single_line((string) ($_POST['proposal_contact'] ?? $proposalContactDefault), 220);
                if ($proposalContact === '') {
                    $proposalContact = $proposalContactDefault;
                }
                if ($proposalTitle === '') {
                    throw new RuntimeException('err_subcategory_required');
                }

                $proposalSummary = content_proposal_details_text([
                    (string) $labels['category_field'] => $proposalCategory,
                    (string) $labels['subcategory_field'] => $proposalTitle,
                    (string) $labels['propose_subcategory_reason'] => $proposalReason,
                ]);
                $proposalStatus = $canManageDocuments ? 'accepted' : 'pending';
                $proposalId = content_proposal_create((int) $user['id'], $moduleCode, 'subcategory', $proposalTitle, $proposalSummary, $proposalContact, '', $proposalStatus);
                if ($canManageDocuments) {
                    $saved = member_document_upsert_subcategory($moduleCode, $categories, $proposalCategory, $proposalTitle);
                    set_flash('success', (string) $labels['subcategory_created_direct']);
                    redirect_url(route_url_clean((string) ($definition['route'] ?? $moduleCode), ['category' => $saved['category'], 'subcategory' => $saved['subcategory']]));
                }

                content_proposal_notify_site((string) $labels['propose_subcategory_subject'], [
                    'area' => $moduleCode,
                    'proposal_type' => 'subcategory',
                    'title' => $proposalTitle,
                    'summary' => $proposalSummary,
                    'contact' => $proposalContact,
                    'source_ref' => 'content_proposals#' . $proposalId,
                ]);
                set_flash('success', (string) $labels['proposal_recorded']);
                redirect('my_requests');
            }
            if ($action === 'propose_subsubcategory' && $canProposeSubsubcategory) {
                $proposalTitle = content_proposal_clean_single_line((string) ($_POST['proposal_subsubcategory_name'] ?? $_POST['proposal_subsubcategory'] ?? ''), 160);
                $parentParts = member_document_subcategory_ref_parts((string) ($_POST['proposal_parent_subcategory_ref'] ?? ''));
                [$proposalCategory, $proposalSubcategory] = member_document_taxonomy_from_input(
                    $moduleCode,
                    $parentParts['category'] !== '' ? $parentParts['category'] : 'general',
                    member_document_subcategory_ref($parentParts['category'] !== '' ? $parentParts['category'] : 'general', $parentParts['subcategory']),
                    $categories
                );
                $proposalReason = content_proposal_clean_multiline((string) ($_POST['proposal_reason'] ?? $_POST['proposal_details'] ?? ''), 1600);
                $proposalContact = content_proposal_clean_single_line((string) ($_POST['proposal_contact'] ?? $proposalContactDefault), 220);
                if ($proposalContact === '') {
                    $proposalContact = $proposalContactDefault;
                }
                if ($proposalTitle === '' || $proposalSubcategory === '') {
                    throw new RuntimeException('err_subsubcategory_required');
                }

                $proposalSummary = content_proposal_details_text([
                    (string) $labels['category_field'] => $proposalCategory,
                    (string) $labels['subcategory_field'] => $proposalSubcategory,
                    (string) $labels['subsubcategory_field'] => $proposalTitle,
                    (string) $labels['propose_subsubcategory_reason'] => $proposalReason,
                ]);
                $proposalStatus = $canManageDocuments ? 'accepted' : 'pending';
                $proposalId = content_proposal_create((int) $user['id'], $moduleCode, 'subsubcategory', $proposalTitle, $proposalSummary, $proposalContact, '', $proposalStatus);
                if ($canManageDocuments) {
                    $saved = member_document_upsert_subsubcategory($moduleCode, $categories, $proposalCategory, $proposalSubcategory, $proposalTitle);
                    set_flash('success', (string) $labels['subsubcategory_created_direct']);
                    redirect_url(route_url_clean((string) ($definition['route'] ?? $moduleCode), ['category' => $saved['category'], 'subcategory' => $saved['subcategory']]));
                }

                content_proposal_notify_site((string) $labels['propose_subsubcategory_subject'], [
                    'area' => $moduleCode,
                    'proposal_type' => 'subsubcategory',
                    'title' => $proposalTitle,
                    'summary' => $proposalSummary,
                    'contact' => $proposalContact,
                    'source_ref' => 'content_proposals#' . $proposalId,
                ]);
                set_flash('success', (string) $labels['proposal_recorded']);
                redirect('my_requests');
            }
            if ($action === 'propose_document' && $canProposeDocument) {
                $proposalTitle = content_proposal_clean_single_line((string) ($_POST['proposal_title'] ?? ''), 255);
                $proposalDescription = content_proposal_clean_multiline((string) ($_POST['proposal_description'] ?? ''), 5000);
                $proposalTags = content_proposal_clean_single_line((string) ($_POST['proposal_tags'] ?? ''), 255);
                $proposalSubcategoryRef = trim((string) ($_POST['subcategory_ref'] ?? ''));
                [$proposalCategory, $proposalSubcategory] = member_document_taxonomy_from_input(
                    $moduleCode,
                    (string) ($_POST['category'] ?? 'general'),
                    $proposalSubcategoryRef,
                    $categories
                );
                $proposalContact = content_proposal_clean_single_line((string) ($_POST['proposal_contact'] ?? ($user['email'] ?? '')), 220);
                if ($proposalContact === '') {
                    $proposalContact = content_proposal_clean_single_line((string) ($user['callsign'] ?? ''), 220);
                }
                $proposalFile = $_FILES['proposal_file'] ?? null;
                if ($proposalTitle === '' || !is_array($proposalFile)) {
                    throw new RuntimeException((string) $labels['err_required']);
                }

                $stored = member_document_store_upload($proposalFile, $moduleCode, (int) ($user['id'] ?? 0));
                $proposalSummary = content_proposal_details_text([
                    'Module' => $moduleCode,
                    'Category' => $proposalCategory,
                    'Subcategory' => $proposalSubcategory,
                    'Tags' => $proposalTags,
                    'Document' => (string) ($proposalFile['name'] ?? ''),
                    'Description' => $proposalDescription,
                ]);
                $proposalStatus = $canManageDocuments ? 'accepted' : 'pending';
                $proposalId = content_proposal_create(
                    (int) $user['id'],
                    $moduleCode,
                    'content',
                    $proposalTitle,
                    $proposalSummary,
                    $proposalContact,
                    (string) $stored['public_path'],
                    $proposalStatus
                );

                if ($canManageDocuments) {
                    member_document_create_record(
                        (int) $user['id'],
                        $moduleCode,
                        $proposalTitle,
                        $proposalDescription,
                        $proposalTags,
                        (string) $stored['public_path'],
                        $proposalCategory,
                        $proposalSubcategory
                    );
                    set_flash('success', (string) $labels['content_validated_direct']);
                    redirect_url(route_url((string) ($definition['route'] ?? $moduleCode)));
                }

                content_proposal_notify_site((string) $labels['propose_content_subject'], [
                    'area' => $moduleCode,
                    'proposal_type' => 'content',
                    'title' => $proposalTitle,
                    'summary' => $proposalSummary,
                    'contact' => $proposalContact,
                    'source_ref' => 'content_proposals#' . $proposalId,
                ]);
                set_flash('success', (string) $labels['propose_content_recorded']);
                redirect('my_requests');
            }
            if (($action !== 'update_document' && $action !== 'delete_document') || !member_document_module_allows_member_management($moduleCode)) {
                throw new RuntimeException((string) $labels['err_invalid']);
            }

            $documentId = (int) ($_POST['id'] ?? 0);
            $docStmt = db()->prepare('SELECT * FROM member_module_documents WHERE id = ? AND module_code = ? LIMIT 1');
            $docStmt->execute([$documentId, $moduleCode]);
            $document = $docStmt->fetch() ?: null;
            if (!is_array($document)) {
                throw new RuntimeException((string) $labels['document_missing']);
            }
            if (!$canManageDocuments && (int) ($document['member_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
                throw new RuntimeException((string) $labels['document_forbidden']);
            }

            $titleInput = content_proposal_clean_single_line((string) ($_POST['title'] ?? $document['title'] ?? ''), 255);
            $description = content_proposal_clean_multiline((string) ($_POST['description'] ?? $document['description'] ?? ''), 5000);
            $tags = content_proposal_clean_single_line((string) ($_POST['tags'] ?? $document['tags'] ?? ''), 255);
            $category = member_document_category_from_input((string) ($_POST['category'] ?? $document['category'] ?? 'general'), $categories);
            $subcategory = member_document_subcategory_code((string) ($document['subcategory'] ?? ''));
            if (array_key_exists('subcategory_ref', $_POST)) {
                [$category, $subcategory] = member_document_taxonomy_from_input(
                    $moduleCode,
                    (string) ($_POST['category'] ?? $document['category'] ?? 'general'),
                    trim((string) ($_POST['subcategory_ref'] ?? '')),
                    $categories,
                    (string) ($document['category'] ?? 'general')
                );
            }
            if ($titleInput === '') {
                throw new RuntimeException((string) $labels['err_required']);
            }

            if ($action === 'delete_document') {
                if ($canManageDocuments) {
                    member_document_delete_record($documentId, $moduleCode);
                    set_flash('success', (string) $labels['ok_deleted']);
                    redirect_url($returnUrl());
                }

                $proposalSummary = content_proposal_details_text([
                    'Action' => 'delete_document',
                    'Document ID' => (string) $documentId,
                    'Module' => $moduleCode,
                    'Category' => (string) ($document['category'] ?? 'general'),
                    'Subcategory' => (string) ($document['subcategory'] ?? ''),
                    'Tags' => (string) ($document['tags'] ?? ''),
                    'Description' => mb_safe_substr((string) ($document['description'] ?? ''), 0, 1800),
                ]);
                $proposalId = content_proposal_create((int) $user['id'], $moduleCode, 'content', $titleInput, $proposalSummary, (string) ($user['email'] ?? ''), (string) ($document['file_path'] ?? ''), 'pending');
                content_proposal_notify_site((string) $labels['document_change_subject'], [
                    'area' => $moduleCode,
                    'proposal_type' => 'content',
                    'title' => $titleInput,
                    'summary' => $proposalSummary,
                    'contact' => (string) ($user['email'] ?? ''),
                    'source_ref' => 'content_proposals#' . $proposalId,
                ]);
                set_flash('success', (string) $labels['document_change_recorded']);
                redirect('my_requests');
            }

            $replacementPublicPath = '';
            $file = $_FILES['document_file'] ?? null;
            if (is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $stored = member_document_store_upload($file, $moduleCode, (int) ($user['id'] ?? 0));
                $replacementPublicPath = (string) $stored['public_path'];
            }

            if ($canManageDocuments) {
                member_document_update_record($documentId, $moduleCode, $titleInput, $description, $tags, $replacementPublicPath, $category, $subcategory);
                set_flash('success', (string) $labels['ok_updated']);
                redirect_url($returnUrl());
            }

            $proposalSummary = content_proposal_details_text([
                'Action' => 'update_document',
                'Document ID' => (string) $documentId,
                'Module' => $moduleCode,
                'Category' => $category,
                'Subcategory' => $subcategory,
                'Tags' => $tags,
                'Description' => $description,
            ]);
            $proposalId = content_proposal_create((int) $user['id'], $moduleCode, 'content', $titleInput, $proposalSummary, (string) ($user['email'] ?? ''), $replacementPublicPath, 'pending');
            content_proposal_notify_site((string) $labels['document_change_subject'], [
                'area' => $moduleCode,
                'proposal_type' => 'content',
                'title' => $titleInput,
                'summary' => $proposalSummary,
                'contact' => (string) ($user['email'] ?? ''),
                'source_ref' => 'content_proposals#' . $proposalId,
            ]);
            set_flash('success', (string) $labels['document_change_recorded']);
            redirect('my_requests');
        } catch (Throwable $throwable) {
            $key = $throwable->getMessage();
            set_flash('error', (string) ($labels[$key] ?? $key));
            redirect_url($returnUrl());
        }
    }

    $listState = member_document_module_list_state($moduleCode, $categories, $_GET, true);
    $search = $listState['search'];
    $stats = $listState['stats'];
    $subcategoriesByCategory = $listState['subcategories_by_category'];
    $visibleCategories = $listState['visible_categories'];
    $visibleSubcategoriesByCategory = $listState['visible_subcategories_by_category'];
    $categoryFilter = $listState['category_filter'];
    $subcategoryFilter = $listState['subcategory_filter'];
    $favoriteDocumentIds = member_document_favorite_document_ids((int) ($user['id'] ?? 0), $moduleCode);
    $favoriteDocumentCount = count($favoriteDocumentIds);
    $favoritesOnly = (string) ($_GET['favorites'] ?? '') === '1' && $favoriteDocumentCount > 0;
    $favoritesLabel = member_document_favorites_label($labels, $locale);
    $documents = member_document_fetch_documents($moduleCode, $search, 60, $categoryFilter, $subcategoryFilter, $favoritesOnly ? $favoriteDocumentIds : []);
    $hiddenStats = (array) ($definition['hidden_stats'] ?? []);
    $latestDate = trim((string) ($stats['latest'] ?? ''));
    $latestLabel = $latestDate !== '' ? date('d/m/Y', strtotime($latestDate) ?: time()) : (string) $labels['none'];
    $adminRoute = (string) ($definition['admin_route'] ?? ('admin_' . $moduleCode));
    $routeName = (string) ($definition['route'] ?? $moduleCode);
    $showDocumentProposalForm = $canProposeDocument && (string) ($_GET['propose_document'] ?? $_GET['propose_video'] ?? '') === '1';
    $showCategoryProposalForm = $canProposeTaxonomy && (string) ($_GET['propose_category'] ?? '') === '1';
    $showSubcategoryProposalForm = $canProposeTaxonomy && (string) ($_GET['propose_subcategory'] ?? '') === '1';
    $showSubsubcategoryProposalForm = $canProposeSubsubcategory && (string) ($_GET['propose_subsubcategory'] ?? '') === '1';
    $showProposeDropdown = $canProposeTaxonomy || ($moduleCode === 'videos' && $canProposeDocument);
    $primaryActionHref = '#member-document-list';
    $primaryActionAttributes = '';
    $primaryActionLabel = (string) $labels['view_content'];
    if ($moduleCode === 'videos' && $canProposeDocument) {
        $primaryActionHref = route_url($routeName, ['propose_video' => '1']);
        $primaryActionAttributes = ' data-member-document-modal-open="member-document-proposal-dialog" aria-haspopup="dialog" aria-controls="member-document-proposal-dialog"';
        $primaryActionLabel = (string) $labels['propose_content'];
    } elseif ((bool) ($definition['latest_document_cta'] ?? false) && $documents !== []) {
        $latestSafePath = member_document_safe_path((string) ($documents[0]['file_path'] ?? ''));
        $latestDocumentId = max(0, (int) ($documents[0]['id'] ?? 0));
        if ($latestSafePath !== null && $latestDocumentId > 0) {
            $primaryActionHref = route_url('member_document_preview', ['module' => $moduleCode, 'id' => $latestDocumentId, 'download' => '1']);
            $primaryActionAttributes = ' target="_blank" rel="noopener"';
        }
    }

    ob_start();
    ?>
    <div class="stack member-document-module">
        <section class="page-hero member-document-hero member-module-hero">
            <div class="member-document-hero-copy">
                <p class="eyebrow"><?= e($memberAreaLabel) ?></p>
                <h1 class="member-document-heading"><?= e($title) ?></h1>
                <?php if ($intro !== ''): ?><p class="help"><?= e($intro) ?></p><?php endif; ?>
            </div>
            <div class="member-document-hero-side">
                <?= render_member_document_module_stats($stats, $labels, $latestLabel, $hiddenStats) ?>
                <div class="actions member-document-hero-actions">
                    <?php if ($moduleCode === 'fichiers'): ?>
                        <details class="member-document-propose-menu member-document-manage-menu">
                            <summary class="button" aria-haspopup="menu"><?= e((string) $labels['manage_menu']) ?></summary>
                            <div class="member-document-propose-menu-panel" role="menu">
                                <a class="member-document-propose-menu-item" role="menuitem" href="<?= e($primaryActionHref) ?>"<?= $primaryActionAttributes ?>><?= e((string) $labels['manage_my_files']) ?></a>
                                <?php if ($canManageDocuments): ?>
                                    <a class="member-document-propose-menu-item" role="menuitem" href="<?= e(route_url($adminRoute)) ?>"><?= e((string) $labels['manage_my_shares']) ?></a>
                                <?php endif; ?>
                            </div>
                        </details>
                    <?php elseif ($showProposeDropdown): ?>
                        <details class="member-document-propose-menu">
                            <summary class="button" aria-haspopup="menu"><?= e((string) $labels['propose_menu']) ?></summary>
                            <div class="member-document-propose-menu-panel" role="menu">
                                <?php if ($moduleCode === 'videos'): ?>
                                    <a class="member-document-propose-menu-item" role="menuitem" href="<?= e(route_url($routeName, ['propose_video' => '1'])) ?>" data-member-document-modal-open="member-document-proposal-dialog" aria-haspopup="dialog" aria-controls="member-document-proposal-dialog"><?= e((string) $labels['propose_presentation_item']) ?></a>
                                <?php endif; ?>
                                <?php if ($canProposeTaxonomy): ?>
                                    <a class="member-document-propose-menu-item" role="menuitem" href="<?= e(route_url($routeName, ['propose_category' => '1'])) ?>" data-member-document-modal-open="member-document-category-dialog" aria-haspopup="dialog" aria-controls="member-document-category-dialog"><?= e((string) $labels['propose_category_item']) ?></a>
                                    <a class="member-document-propose-menu-item" role="menuitem" href="<?= e(route_url($routeName, ['propose_subcategory' => '1'])) ?>" data-member-document-modal-open="member-document-subcategory-dialog" aria-haspopup="dialog" aria-controls="member-document-subcategory-dialog"><?= e((string) $labels['propose_subcategory_item']) ?></a>
                                <?php endif; ?>
                                <?php if ($canProposeSubsubcategory): ?>
                                    <a class="member-document-propose-menu-item" role="menuitem" href="<?= e(route_url($routeName, ['propose_subsubcategory' => '1'])) ?>" data-member-document-modal-open="member-document-subsubcategory-dialog" aria-haspopup="dialog" aria-controls="member-document-subsubcategory-dialog"><?= e((string) $labels['propose_subsubcategory_item']) ?></a>
                                <?php endif; ?>
                                <?php if ($moduleCode !== 'videos'): ?>
                                    <a class="member-document-propose-menu-item" role="menuitem" href="<?= e(route_url($routeName, ['propose_document' => '1'])) ?>" data-member-document-modal-open="member-document-proposal-dialog" aria-haspopup="dialog" aria-controls="member-document-proposal-dialog"><?= e((string) $labels['propose_presentation_item']) ?></a>
                                <?php endif; ?>
                            </div>
                        </details>
                    <?php else: ?>
                        <a class="button" href="<?= e($primaryActionHref) ?>"<?= $primaryActionAttributes ?>><?= e($primaryActionLabel) ?></a>
                    <?php endif; ?>
                    <?php if ($canManageDocuments): ?>
                        <a class="button secondary" href="<?= e(route_url($adminRoute)) ?>"><?= e((string) ($moduleCode === 'fichiers' ? $labels['administer'] : $labels['administration'])) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php if ($canProposeTaxonomy): ?>
            <dialog class="member-document-dialog" id="member-document-category-dialog" aria-labelledby="member-document-category-title"<?= $showCategoryProposalForm ? ' open data-member-document-auto-open' : '' ?>>
                <div class="member-document-dialog-card">
                    <div class="member-document-dialog-header module-dialog-header">
                        <div>
                            <p class="module-dialog-eyebrow"><?= e((string) $labels['documents']) ?></p>
                            <h2 id="member-document-category-title"><?= e((string) $labels['propose_category_title']) ?></h2>
                            <p class="help"><?= e($canManageDocuments ? (string) $labels['propose_category_intro_admin'] : (string) $labels['propose_category_intro']) ?></p>
                        </div>
                        <button class="member-document-dialog-close module-dialog-close" type="button" data-member-document-modal-close aria-label="<?= e((string) $labels['modal_close']) ?>">&times;</button>
                    </div>
                    <form method="post" class="member-document-dialog-form module-dialog-form">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="propose_category">
                        <label><span><?= e((string) $labels['propose_category_name']) ?></span><input type="text" name="proposal_category_name" maxlength="160" required></label>
                        <label><span><?= e((string) $labels['propose_category_reason']) ?></span><textarea name="proposal_reason" rows="4" maxlength="1600"></textarea></label>
                        <label><span><?= e((string) $labels['proposal_contact']) ?></span><input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required></label>
                        <p class="member-document-dialog-actions module-dialog-actions">
                            <button class="button" type="submit"><?= e((string) $labels['propose_category']) ?></button>
                            <button class="button secondary" type="button" data-member-document-modal-close><?= e((string) $labels['cancel']) ?></button>
                        </p>
                    </form>
                </div>
            </dialog>

            <dialog class="member-document-dialog" id="member-document-subcategory-dialog" aria-labelledby="member-document-subcategory-title"<?= $showSubcategoryProposalForm ? ' open data-member-document-auto-open' : '' ?>>
                <div class="member-document-dialog-card">
                    <div class="member-document-dialog-header module-dialog-header">
                        <div>
                            <p class="module-dialog-eyebrow"><?= e((string) $labels['documents']) ?></p>
                            <h2 id="member-document-subcategory-title"><?= e((string) $labels['propose_subcategory_title']) ?></h2>
                            <p class="help"><?= e($canManageDocuments ? (string) $labels['propose_subcategory_intro_admin'] : (string) $labels['propose_subcategory_intro']) ?></p>
                        </div>
                        <button class="member-document-dialog-close module-dialog-close" type="button" data-member-document-modal-close aria-label="<?= e((string) $labels['modal_close']) ?>">&times;</button>
                    </div>
                    <form method="post" class="member-document-dialog-form module-dialog-form">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="propose_subcategory">
                        <label>
                            <span><?= e((string) $labels['propose_subcategory_parent']) ?></span>
                            <select name="proposal_parent_category">
                                <?php foreach ($categories as $code => $label): ?>
                                    <option value="<?= e((string) $code) ?>"<?= ($categoryFilter !== '' ? $categoryFilter : 'general') === (string) $code ? ' selected' : '' ?>><?= e((string) $label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label><span><?= e((string) $labels['propose_subcategory_name']) ?></span><input type="text" name="proposal_subcategory_name" maxlength="160" required></label>
                        <label><span><?= e((string) $labels['propose_subcategory_reason']) ?></span><textarea name="proposal_reason" rows="4" maxlength="1600"></textarea></label>
                        <label><span><?= e((string) $labels['proposal_contact']) ?></span><input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required></label>
                        <p class="member-document-dialog-actions module-dialog-actions">
                            <button class="button" type="submit"><?= e((string) $labels['propose_subcategory']) ?></button>
                            <button class="button secondary" type="button" data-member-document-modal-close><?= e((string) $labels['cancel']) ?></button>
                        </p>
                    </form>
                </div>
            </dialog>

            <?php if ($canProposeSubsubcategory): ?>
                <dialog class="member-document-dialog" id="member-document-subsubcategory-dialog" aria-labelledby="member-document-subsubcategory-title"<?= $showSubsubcategoryProposalForm ? ' open data-member-document-auto-open' : '' ?>>
                    <div class="member-document-dialog-card">
                        <div class="member-document-dialog-header module-dialog-header">
                            <div>
                                <p class="module-dialog-eyebrow"><?= e((string) $labels['documents']) ?></p>
                                <h2 id="member-document-subsubcategory-title"><?= e((string) $labels['propose_subsubcategory']) ?></h2>
                                <p class="help"><?= e($canManageDocuments ? (string) $labels['subsubcategory_direct_help'] : (string) $labels['propose_subsubcategory_intro']) ?></p>
                            </div>
                            <button class="member-document-dialog-close module-dialog-close" type="button" data-member-document-modal-close aria-label="<?= e((string) $labels['modal_close']) ?>">&times;</button>
                        </div>
                        <form method="post" class="member-document-dialog-form module-dialog-form">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="propose_subsubcategory">
                            <label>
                                <span><?= e((string) $labels['propose_subsubcategory_parent']) ?></span>
                                <select name="proposal_parent_subcategory_ref" required>
                                    <option value=""><?= e((string) $labels['no_subcategory']) ?></option>
                                    <?php foreach ($subcategoriesByCategory as $parentCode => $subcategories): ?>
                                        <?php $parentLabel = (string) ($categories[(string) $parentCode] ?? member_document_category_label_from_code((string) $parentCode)); ?>
                                        <optgroup label="<?= e($parentLabel) ?>">
                                            <?php foreach ($subcategories as $subcategory): ?>
                                                <?php $subCode = member_document_subcategory_code((string) ($subcategory['code'] ?? '')); ?>
                                                <?php if ($subCode !== ''): ?>
                                                    <option value="<?= e(member_document_subcategory_ref((string) $parentCode, $subCode)) ?>"<?= $categoryFilter === (string) $parentCode && $subcategoryFilter === $subCode ? ' selected' : '' ?>><?= e((string) ($subcategory['label'] ?? $subCode)) ?></option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label><span><?= e((string) $labels['propose_subsubcategory_name']) ?></span><input type="text" name="proposal_subsubcategory_name" maxlength="160" required></label>
                            <label><span><?= e((string) $labels['propose_subsubcategory_reason']) ?></span><textarea name="proposal_reason" rows="4" maxlength="1600"></textarea></label>
                            <label><span><?= e((string) $labels['proposal_contact']) ?></span><input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required></label>
                            <p class="member-document-dialog-actions module-dialog-actions">
                                <button class="button" type="submit"><?= e((string) $labels['propose_subsubcategory']) ?></button>
                                <button class="button secondary" type="button" data-member-document-modal-close><?= e((string) $labels['cancel']) ?></button>
                            </p>
                        </form>
                    </div>
                </dialog>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($canProposeDocument): ?>
            <dialog class="member-document-dialog" id="member-document-proposal-dialog" aria-labelledby="member-document-proposal-title"<?= $showDocumentProposalForm ? ' open data-member-document-auto-open' : '' ?>>
                <div class="member-document-dialog-card">
                    <div class="member-document-dialog-header module-dialog-header">
                        <div>
                            <p class="module-dialog-eyebrow"><?= e((string) $labels['documents']) ?></p>
                            <h2 id="member-document-proposal-title"><?= e((string) $labels['propose_content_title']) ?></h2>
                            <p class="help"><?= e($canManageDocuments ? (string) $labels['propose_content_intro_admin'] : (string) $labels['propose_content_intro']) ?></p>
                        </div>
                        <button class="member-document-dialog-close module-dialog-close" type="button" data-member-document-modal-close aria-label="<?= e((string) $labels['modal_close']) ?>">&times;</button>
                    </div>
                    <form method="post" enctype="multipart/form-data" class="member-document-dialog-form module-dialog-form">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="propose_document">
                        <label><span><?= e((string) $labels['title_field']) ?></span><input type="text" name="proposal_title" maxlength="255" required></label>
                        <?= render_member_document_taxonomy_fields($moduleCode, $categories, $labels, $categoryFilter !== '' ? $categoryFilter : 'general', $subcategoryFilter) ?>
                        <label><span><?= e((string) $labels['description_field']) ?></span><textarea name="proposal_description" rows="5" maxlength="5000"></textarea></label>
                        <label><span><?= e((string) $labels['tags_field']) ?></span><input type="text" name="proposal_tags" maxlength="255"></label>
                        <label><span><?= e((string) $labels['proposal_file_field']) ?></span><input type="file" name="proposal_file" required></label>
                        <label><span><?= e((string) $labels['proposal_contact']) ?></span><input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required></label>
                        <p class="member-document-dialog-actions module-dialog-actions">
                            <button class="button" type="submit"><?= e((string) $labels['propose_content']) ?></button>
                            <button class="button secondary" type="button" data-member-document-modal-close><?= e((string) $labels['cancel']) ?></button>
                        </p>
                    </form>
                </div>
            </dialog>
        <?php endif; ?>

        <section class="card member-document-search-panel">
            <form method="get" class="inline-form member-document-search-form">
                <input type="hidden" name="route" value="<?= e($routeName) ?>">
                <?php if ($categoryFilter !== ''): ?>
                    <input type="hidden" name="category" value="<?= e($categoryFilter) ?>">
                <?php endif; ?>
                <?php if ($subcategoryFilter !== ''): ?>
                    <input type="hidden" name="subcategory" value="<?= e($subcategoryFilter) ?>">
                <?php endif; ?>
                <?php if ($favoritesOnly): ?>
                    <input type="hidden" name="favorites" value="1">
                <?php endif; ?>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $labels['search_ph']) ?>">
                <button class="button" type="submit"><?= e((string) $labels['search']) ?></button>
                <?php if ($search !== '' || $categoryFilter !== '' || $subcategoryFilter !== '' || $favoritesOnly): ?>
                    <a class="button secondary" href="<?= e(route_url($routeName)) ?>"><?= e((string) $labels['reset']) ?></a>
                <?php endif; ?>
            </form>
        </section>

        <section class="member-document-layout module-taxonomy-layout">
            <aside class="card member-document-taxonomy module-taxonomy-index">
                <p class="module-taxonomy-title"><?= e((string) $labels['category_field']) ?></p>
                <nav class="module-taxonomy-list" aria-label="<?= e((string) $labels['category_field']) ?>">
                    <?php if ($favoriteDocumentCount > 0): ?>
                        <a class="module-taxonomy-item<?= $favoritesOnly ? ' is-active' : '' ?>" href="<?= e(route_url_clean((string) ($definition['route'] ?? $moduleCode), ['favorites' => '1', 'q' => $search])) ?>"<?= $favoritesOnly ? ' aria-current="page"' : '' ?>>
                            <span><?= e($favoritesLabel) ?></span>
                            <strong><?= (int) $favoriteDocumentCount ?></strong>
                        </a>
                    <?php endif; ?>
                    <a class="module-taxonomy-item<?= !$favoritesOnly && $categoryFilter === '' && $subcategoryFilter === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean($routeName, ['q' => $search])) ?>"<?= !$favoritesOnly && $categoryFilter === '' && $subcategoryFilter === '' ? ' aria-current="page"' : '' ?>>
                        <span><?= e((string) $labels['all_categories']) ?></span>
                        <strong><?= (int) ($stats['total'] ?? 0) ?></strong>
                    </a>
                    <?php foreach ($visibleCategories as $categoryCode => $categoryLabel): ?>
                        <a class="module-taxonomy-item taxonomy-pill-category<?= !$favoritesOnly && $categoryFilter === $categoryCode && $subcategoryFilter === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean($routeName, ['category' => (string) $categoryCode, 'q' => $search])) ?>"<?= !$favoritesOnly && $categoryFilter === $categoryCode && $subcategoryFilter === '' ? ' aria-current="page"' : '' ?>>
                            <span><?= e((string) $categoryLabel) ?></span>
                            <strong><?= (int) ($stats['by_category'][$categoryCode] ?? 0) ?></strong>
                        </a>
                        <?php if (($visibleSubcategoriesByCategory[(string) $categoryCode] ?? []) !== []): ?>
                            <div class="module-taxonomy-children">
                                <?php foreach ($visibleSubcategoriesByCategory[(string) $categoryCode] as $subcategoryInfo): ?>
                                    <?php $subCode = member_document_subcategory_code((string) ($subcategoryInfo['code'] ?? '')); ?>
                                    <a class="module-taxonomy-item module-taxonomy-subitem taxonomy-pill-subcategory<?= !$favoritesOnly && $categoryFilter === $categoryCode && $subcategoryFilter === $subCode ? ' is-active' : '' ?>" href="<?= e(route_url_clean($routeName, ['category' => (string) $categoryCode, 'subcategory' => $subCode, 'q' => $search])) ?>"<?= !$favoritesOnly && $categoryFilter === $categoryCode && $subcategoryFilter === $subCode ? ' aria-current="page"' : '' ?>>
                                        <span><?= e((string) ($subcategoryInfo['label'] ?? $subCode)) ?></span>
                                        <strong><?= (int) ($subcategoryInfo['total'] ?? 0) ?></strong>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
            </aside>

            <section id="member-document-list" class="member-document-content module-taxonomy-content">
                <?php if ($documents === []): ?>
                    <div class="card">
                        <p><?= e((string) $labels['empty']) ?><?php if ($search !== '' || $categoryFilter !== '' || $subcategoryFilter !== '' || $favoritesOnly): ?><?= e((string) $labels['for_filters']) ?>.<?php endif; ?></p>
                    </div>
                <?php else: ?>
                    <div class="news-grid member-document-grid">
                        <?= render_member_document_module_cards($documents, $labels, $moduleCode, $user, $canManageDocuments, ['q' => $search, 'category' => $categoryFilter, 'subcategory' => $subcategoryFilter, 'favorites' => $favoritesOnly ? '1' : '']) ?>
                    </div>
                <?php endif; ?>
            </section>
        </section>
    </div>
    <?php
    echo render_layout((string) ob_get_clean(), $title);
}
}

if (!function_exists('render_admin_member_document_module_page')) {
function render_admin_member_document_module_page(string $module): void
{
    require_permission('admin.access');
    $user = current_user();
    $moduleCode = member_document_module_normalize($module);
    $definition = member_document_module_definition($moduleCode);
    if ($definition === null) {
        http_response_code(404);
        echo render_layout('<div class="card"><h1>404</h1><p>' . e(i18n_error_text('module_unavailable', 'Module unavailable.')) . '</p></div>', '404');
        return;
    }

    $locale = current_locale();
    $labels = member_document_module_labels($moduleCode, $locale);
    $moduleText = member_document_module_text($moduleCode, $locale);
    $title = (string) $moduleText['title'];
    $adminPageTitle = trim((string) $labels['admin_page_title']);
    if ($adminPageTitle === '') {
        $adminPageTitle = $title;
    }
    $adminEyebrow = trim((string) $labels['admin_eyebrow']);
    if ($adminEyebrow === '') {
        $adminEyebrow = (string) $labels['admin_title_prefix'];
    }
    $adminIntro = trim((string) $labels['admin_intro']);
    if ($adminIntro === '') {
        $adminIntro = (string) $moduleText['intro'];
    }
    $adminLayoutTitle = (string) $labels['admin_title_prefix'] . ' - ' . $adminPageTitle;
    if ((string) $labels['admin_title_prefix'] === $adminPageTitle) {
        $adminLayoutTitle = $adminPageTitle;
    }
    $adminRoute = (string) ($definition['admin_route'] ?? ('admin_' . $moduleCode));

    set_page_meta([
        'title' => $adminLayoutTitle,
        'description' => (string) $moduleText['meta_desc'],
        'robots' => 'noindex,nofollow',
    ]);

    if (!ensure_member_module_documents_table()) {
        echo render_layout('<div class="card"><p>' . e((string) $labels['storage_unavailable']) . '</p></div>', $title);
        return;
    }

    $categories = member_document_categories($moduleCode);
    member_document_ensure_subcategories_table($moduleCode);
    $adminReturnUrl = static function () use ($adminRoute): string {
        return route_url_clean($adminRoute, [
            'category' => (string) ($_POST['return_category'] ?? $_GET['category'] ?? ''),
            'subcategory' => (string) ($_POST['return_subcategory'] ?? $_GET['subcategory'] ?? ''),
            'q' => (string) ($_POST['return_q'] ?? $_GET['q'] ?? ''),
        ]);
    };

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            verify_csrf();
            $action = (string) ($_POST['action'] ?? 'upload');
            if ($action === 'add_category') {
                if (!member_document_ensure_categories_table($moduleCode)) {
                    throw new RuntimeException('storage_unavailable');
                }
                $label = content_proposal_clean_single_line((string) ($_POST['category_label'] ?? ''), 160);
                $code = member_document_category_code((string) ($_POST['category_code'] ?? $label));
                if ($label === '' || $code === '') {
                    throw new RuntimeException('err_required');
                }
                db()->prepare('INSERT INTO member_module_categories (module_code, code, label, deleted_at) VALUES (?, ?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
                    ->execute([$moduleCode, $code, $label]);
                set_flash('success', (string) $labels['ok_added']);
                redirect_url(route_url_clean($adminRoute, ['category' => $code]));
            }
            if ($action === 'update_category') {
                if (!member_document_ensure_categories_table($moduleCode)) {
                    throw new RuntimeException('storage_unavailable');
                }
                $category = member_document_category_from_input((string) ($_POST['category_code'] ?? ''), $categories);
                $label = content_proposal_clean_single_line((string) ($_POST['category_label'] ?? ''), 160);
                if ($label === '') {
                    throw new RuntimeException('err_required');
                }
                db()->prepare('INSERT INTO member_module_categories (module_code, code, label, deleted_at) VALUES (?, ?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
                    ->execute([$moduleCode, $category, $label]);
                set_flash('success', (string) $labels['ok_updated']);
                redirect_url(route_url_clean($adminRoute, ['category' => $category]));
            }
            if ($action === 'delete_category') {
                if (!member_document_ensure_categories_table($moduleCode)) {
                    throw new RuntimeException('storage_unavailable');
                }
                $category = member_document_category_from_input((string) ($_POST['category_code'] ?? ''), $categories);
                if ($category === 'general') {
                    throw new RuntimeException('err_category');
                }
                $subCountStmt = db()->prepare('SELECT COUNT(*) FROM member_module_subcategories WHERE module_code = ? AND category_code = ? AND deleted_at IS NULL');
                $subCountStmt->execute([$moduleCode, $category]);
                if ((int) $subCountStmt->fetchColumn() > 0) {
                    throw new RuntimeException('err_category_has_subcategories');
                }
                db()->prepare('UPDATE member_module_documents SET category = "general", subcategory = "" WHERE module_code = ? AND category = ?')->execute([$moduleCode, $category]);
                db()->prepare('INSERT INTO member_module_categories (module_code, code, label, deleted_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE deleted_at = NOW()')
                    ->execute([$moduleCode, $category, (string) ($categories[$category] ?? member_document_category_label_from_code($category))]);
                set_flash('success', (string) $labels['ok_deleted']);
                redirect($adminRoute);
            }
            if ($action === 'add_subcategory') {
                if (!member_document_ensure_subcategories_table($moduleCode)) {
                    throw new RuntimeException('storage_unavailable');
                }
                $category = member_document_category_from_input((string) ($_POST['subcategory_category'] ?? 'general'), $categories);
                $label = content_proposal_clean_single_line((string) ($_POST['subcategory_label'] ?? ''), 160);
                $code = member_document_subcategory_code((string) ($_POST['subcategory_code'] ?? $label));
                if ($label === '' || $code === '') {
                    throw new RuntimeException('err_required');
                }
                db()->prepare('INSERT INTO member_module_subcategories (module_code, category_code, code, label, deleted_at) VALUES (?, ?, ?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
                    ->execute([$moduleCode, $category, $code, $label]);
                set_flash('success', (string) $labels['ok_added']);
                redirect_url(route_url_clean($adminRoute, ['category' => $category, 'subcategory' => $code]));
            }
            if ($action === 'update_subcategory') {
                if (!member_document_ensure_subcategories_table($moduleCode)) {
                    throw new RuntimeException('storage_unavailable');
                }
                $parts = member_document_subcategory_ref_parts((string) ($_POST['subcategory_ref'] ?? ''));
                $category = member_document_category_from_input($parts['category'] !== '' ? $parts['category'] : (string) ($_POST['category'] ?? 'general'), $categories);
                $subcategory = member_document_subcategory_code($parts['subcategory']);
                $label = content_proposal_clean_single_line((string) ($_POST['subcategory_label'] ?? ''), 160);
                if ($subcategory === '' || $label === '') {
                    throw new RuntimeException('err_required');
                }
                db()->prepare('INSERT INTO member_module_subcategories (module_code, category_code, code, label, deleted_at) VALUES (?, ?, ?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
                    ->execute([$moduleCode, $category, $subcategory, $label]);
                set_flash('success', (string) $labels['ok_updated']);
                redirect_url(route_url_clean($adminRoute, ['category' => $category, 'subcategory' => $subcategory]));
            }
            if ($action === 'delete_subcategory') {
                if (!member_document_ensure_subcategories_table($moduleCode)) {
                    throw new RuntimeException('storage_unavailable');
                }
                $parts = member_document_subcategory_ref_parts((string) ($_POST['subcategory_ref'] ?? ''));
                $category = member_document_category_from_input($parts['category'] !== '' ? $parts['category'] : (string) ($_POST['category'] ?? 'general'), $categories);
                $subcategory = member_document_subcategory_code($parts['subcategory']);
                if ($subcategory === '') {
                    throw new RuntimeException('err_required');
                }
                $countStmt = db()->prepare('SELECT COUNT(*) FROM member_module_documents WHERE module_code = ? AND category = ? AND subcategory = ?');
                $countStmt->execute([$moduleCode, $category, $subcategory]);
                if ((int) $countStmt->fetchColumn() > 0) {
                    throw new RuntimeException('err_subcategory_has_documents');
                }
                db()->prepare('UPDATE member_module_subcategories SET deleted_at = NOW() WHERE module_code = ? AND category_code = ? AND code = ?')->execute([$moduleCode, $category, $subcategory]);
                set_flash('success', (string) $labels['ok_deleted']);
                redirect_url(route_url_clean($adminRoute, ['category' => $category]));
            }
            if ($action === 'delete_document') {
                $id = (int) ($_POST['id'] ?? 0);
                $stmt = db()->prepare('SELECT file_path FROM member_module_documents WHERE id = ? AND module_code = ? LIMIT 1');
                $stmt->execute([$id, $moduleCode]);
                $path = (string) ($stmt->fetchColumn() ?: '');
                member_document_delete_file($path);
                db()->prepare('DELETE FROM member_module_documents WHERE id = ? AND module_code = ?')->execute([$id, $moduleCode]);
                if (table_exists('member_favorites')) {
                    db()->prepare('DELETE FROM member_favorites WHERE target_type = ? AND target_id = ?')->execute(['member_module_document', $id]);
                }
                set_flash('success', (string) $labels['ok_deleted']);
                redirect_url($adminReturnUrl());
            }

            $uploadTitle = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $tags = mb_safe_substr(trim((string) ($_POST['tags'] ?? '')), 0, 255);
            $subcategoryRef = trim((string) ($_POST['subcategory_ref'] ?? ''));
            [$category, $subcategory] = member_document_taxonomy_from_input(
                $moduleCode,
                (string) ($_POST['category'] ?? 'general'),
                $subcategoryRef,
                $categories
            );
            $file = $_FILES['document'] ?? null;
            if ($uploadTitle === '' || !is_array($file)) {
                throw new RuntimeException('err_required');
            }
            $stored = member_document_store_upload($file, $moduleCode, (int) ($user['id'] ?? 0));
            $extractedText = member_document_extract_text((string) $stored['absolute_path'], (string) $stored['extension']);
            db()->prepare('INSERT INTO member_module_documents (module_code, member_id, category, subcategory, tags, title, description, file_path, extracted_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$moduleCode, (int) ($user['id'] ?? 0), $category, $subcategory, $tags, $uploadTitle, $description, (string) $stored['public_path'], $extractedText]);
            set_flash('success', (string) $labels['ok_added']);
            redirect_url(route_url_clean($adminRoute, ['category' => $category, 'subcategory' => $subcategory]));
        } catch (Throwable $throwable) {
            $key = $throwable->getMessage();
            set_flash('error', (string) ($labels[$key] ?? $key));
            redirect($adminRoute);
        }
    }

    $listState = member_document_module_list_state($moduleCode, $categories, $_GET, false);
    $search = $listState['search'];
    $stats = $listState['stats'];
    $subcategoriesByCategory = $listState['subcategories_by_category'];
    $visibleCategories = $listState['visible_categories'];
    $visibleSubcategoriesByCategory = $listState['visible_subcategories_by_category'];
    $categoryFilter = $listState['category_filter'];
    $subcategoryFilter = $listState['subcategory_filter'];
    $documents = member_document_fetch_documents($moduleCode, $search, 100, $categoryFilter, $subcategoryFilter);
    $hiddenStats = (array) ($definition['hidden_stats'] ?? []);
    $latestDate = trim((string) ($stats['latest'] ?? ''));
    $latestLabel = $latestDate !== '' ? date('d/m/Y', strtotime($latestDate) ?: time()) : (string) $labels['none'];

    ob_start();
    ?>
    <div class="stack admin-member-document-module">
        <section class="page-hero admin-member-document-hero">
            <div class="admin-member-document-hero-copy">
                <p class="eyebrow"><?= e($adminEyebrow) ?></p>
                <h1><?= e($adminPageTitle) ?></h1>
                <p class="help"><?= e($adminIntro) ?></p>
            </div>
            <div class="admin-member-document-hero-side">
                <?= render_member_document_module_stats($stats, $labels, $latestLabel, $hiddenStats) ?>
                <p class="actions admin-member-document-hero-actions">
                    <a class="button secondary" href="<?= e(route_url((string) ($definition['route'] ?? $moduleCode))) ?>"><?= e((string) $labels['view_content']) ?></a>
                    <a class="button" href="#admin-member-document-upload"><?= e((string) $labels['upload_title']) ?></a>
                </p>
            </div>
        </section>

        <section class="card" id="admin-member-document-upload">
            <h2><?= e((string) $labels['upload_title']) ?></h2>
            <p class="help"><?= e((string) $labels['upload_help']) ?></p>
            <form method="post" enctype="multipart/form-data" class="stack admin-member-document-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="upload">
                <label><span><?= e((string) $labels['title_field']) ?></span><input type="text" name="title" maxlength="255" required></label>
                <?= render_member_document_taxonomy_fields($moduleCode, $categories, $labels, $categoryFilter !== '' ? $categoryFilter : 'general', $subcategoryFilter) ?>
                <label><span><?= e((string) $labels['description_field']) ?></span><textarea name="description" rows="4"></textarea></label>
                <label><span><?= e((string) $labels['tags_field']) ?></span><input type="text" name="tags" maxlength="255"></label>
                <label><span><?= e((string) $labels['document_field']) ?></span><input type="file" name="document" required></label>
                <p class="actions"><button class="button" type="submit"><?= e((string) $labels['upload']) ?></button></p>
            </form>
        </section>

        <section class="card member-document-search-panel">
            <form method="get" class="inline-form member-document-search-form">
                <input type="hidden" name="route" value="<?= e($adminRoute) ?>">
                <?php if ($categoryFilter !== ''): ?>
                    <input type="hidden" name="category" value="<?= e($categoryFilter) ?>">
                <?php endif; ?>
                <?php if ($subcategoryFilter !== ''): ?>
                    <input type="hidden" name="subcategory" value="<?= e($subcategoryFilter) ?>">
                <?php endif; ?>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $labels['search_ph']) ?>">
                <button class="button" type="submit"><?= e((string) $labels['search']) ?></button>
                <?php if ($search !== '' || $categoryFilter !== '' || $subcategoryFilter !== ''): ?>
                    <a class="button secondary" href="<?= e(route_url($adminRoute)) ?>"><?= e((string) $labels['reset']) ?></a>
                <?php endif; ?>
            </form>
        </section>

        <section class="card admin-member-document-taxonomy">
            <h2><?= e((string) $labels['category_field']) ?></h2>
            <div class="grid-2">
                <form method="post" class="stack">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="add_category">
                    <label><span><?= e((string) $labels['category_field']) ?></span><input type="text" name="category_label" maxlength="160" required></label>
                    <button class="button" type="submit"><?= e((string) $labels['upload']) ?></button>
                </form>
                <form method="post" class="stack">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="add_subcategory">
                    <label><span><?= e((string) $labels['category_field']) ?></span>
                        <select name="subcategory_category">
                            <?php foreach ($categories as $code => $label): ?>
                                <option value="<?= e((string) $code) ?>"<?= $categoryFilter === (string) $code ? ' selected' : '' ?>><?= e((string) $label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><span><?= e((string) $labels['subcategory_field']) ?></span><input type="text" name="subcategory_label" maxlength="160" required></label>
                    <button class="button" type="submit"><?= e((string) $labels['upload']) ?></button>
                </form>
            </div>
            <div class="tags-cloud">
                <?php foreach ($categories as $code => $label): ?>
                    <?php $categoryTotal = (int) (($stats['by_category'][(string) $code] ?? 0)); ?>
                    <?php $subcategoryTotal = count($subcategoriesByCategory[(string) $code] ?? []); ?>
                    <?php $categoryDeleteDisabled = (string) $code === 'general' || $subcategoryTotal > 0; ?>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_category">
                        <input type="hidden" name="category_code" value="<?= e((string) $code) ?>">
                        <span class="pill taxonomy-pill-category"><?= e((string) $code) ?> (<?= $categoryTotal ?>)</span>
                        <input type="text" name="category_label" value="<?= e((string) $label) ?>" maxlength="160" required>
                        <button class="button small" type="submit"><?= e((string) $labels['save_document']) ?></button>
                        <button class="button secondary small" type="submit" name="action" value="delete_category"<?= $categoryDeleteDisabled ? ' disabled' : '' ?>><?= e((string) $labels['delete']) ?></button>
                    </form>
                <?php endforeach; ?>
                <?php foreach ($subcategoriesByCategory as $parentCode => $subcategories): ?>
                    <?php foreach ($subcategories as $subcategoryInfo): ?>
                        <?php $subCode = member_document_subcategory_code((string) $subcategoryInfo['code']); ?>
                        <?php if ($subCode === '') { continue; } ?>
                        <?php $subTotal = (int) (($stats['by_subcategory'][(string) $parentCode . ':' . $subCode] ?? 0)); ?>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="update_subcategory">
                            <input type="hidden" name="subcategory_ref" value="<?= e(member_document_subcategory_ref((string) $parentCode, $subCode)) ?>">
                            <span class="taxonomy-badge-row">
                                <span class="badge muted taxonomy-pill-category"><?= e((string) ($categories[(string) $parentCode] ?? $parentCode)) ?></span>
                                <span class="badge muted taxonomy-pill-subcategory"><?= e($subCode) ?></span>
                                <span class="badge muted"><?= $subTotal ?></span>
                            </span>
                            <input type="text" name="subcategory_label" value="<?= e((string) $subcategoryInfo['label']) ?>" maxlength="160" required>
                            <button class="button small" type="submit"><?= e((string) $labels['save_document']) ?></button>
                            <button class="button secondary small" type="submit" name="action" value="delete_subcategory"<?= $subTotal > 0 ? ' disabled' : '' ?>><?= e((string) $labels['delete']) ?></button>
                        </form>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if ($visibleCategories !== []): ?>
            <nav class="classifieds-category-strip member-document-category-filter" aria-label="<?= e((string) $labels['category_field']) ?>">
                <a class="classifieds-category-pill taxonomy-pill-category<?= $categoryFilter === '' && $subcategoryFilter === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean($adminRoute, ['q' => $search])) ?>"><?= e((string) $labels['all_categories']) ?></a>
                <?php foreach ($visibleCategories as $code => $label): ?>
                    <a class="classifieds-category-pill taxonomy-pill-category<?= $categoryFilter === $code && $subcategoryFilter === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean($adminRoute, ['q' => $search, 'category' => (string) $code])) ?>"><?= e((string) $label) ?></a>
                    <?php foreach (($visibleSubcategoriesByCategory[(string) $code] ?? []) as $subcategoryInfo): ?>
                        <?php $subCode = member_document_subcategory_code((string) ($subcategoryInfo['code'] ?? '')); ?>
                        <a class="classifieds-category-pill taxonomy-pill-subcategory<?= $categoryFilter === $code && $subcategoryFilter === $subCode ? ' is-active' : '' ?>" href="<?= e(route_url_clean($adminRoute, ['q' => $search, 'category' => (string) $code, 'subcategory' => $subCode])) ?>"><?= e((string) ($subcategoryInfo['label'] ?? $subCode)) ?></a>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <section class="admin-member-document-list">
            <h2><?= e((string) $labels['content_list']) ?></h2>
            <?php if ($documents === []): ?>
                <div class="card"><p><?= e((string) $labels['empty']) ?></p></div>
            <?php else: ?>
                <div class="news-grid member-document-grid">
                    <?php foreach ($documents as $document): ?>
                        <?php $safePath = member_document_safe_path((string) ($document['file_path'] ?? '')); ?>
                        <?php if ($safePath === null) { continue; } ?>
                        <?php $extension = strtolower(pathinfo($safePath, PATHINFO_EXTENSION)); ?>
                        <?php $documentId = (int) ($document['id'] ?? 0); ?>
                        <?php $documentDownloadUrl = $documentId > 0 ? route_url('member_document_preview', ['module' => $moduleCode, 'id' => $documentId, 'download' => '1']) : ''; ?>
                        <article class="news-card feature-card member-document-card">
                            <span class="badge muted"><?= e(strtoupper($extension)) ?></span>
                            <h3><?= e((string) ($document['title'] ?? $labels['documents'])) ?></h3>
                            <?php if (trim((string) ($document['description'] ?? '')) !== ''): ?><p><?= e((string) $document['description']) ?></p><?php endif; ?>
                            <?php
                            $docCategory = member_document_category_code((string) ($document['category'] ?? 'general'));
                            $docSubcategory = member_document_subcategory_code((string) ($document['subcategory'] ?? ''));
                            ?>
                            <p class="help taxonomy-badge-row">
                                <?= e((string) $labels['category_field']) ?>:
                                <span class="badge muted taxonomy-pill-category"><?= e((string) ($categories[$docCategory] ?? member_document_category_label_from_code($docCategory))) ?></span>
                                <?php if ($docSubcategory !== ''): ?><span class="badge muted taxonomy-pill-subcategory"><?= e(member_document_category_label_from_code($docSubcategory)) ?></span><?php endif; ?>
                            </p>
                            <?php if (trim((string) ($document['tags'] ?? '')) !== ''): ?><p class="help"><?= e((string) $labels['tags']) ?>: <?= e((string) $document['tags']) ?></p><?php endif; ?>
                            <p class="actions">
                                <?php if ($documentDownloadUrl !== ''): ?><a class="button secondary" href="<?= e($documentDownloadUrl) ?>" target="_blank" rel="noopener"><?= e((string) $labels['open']) ?></a><?php endif; ?>
                                <form method="post" class="inline-form" onsubmit="return confirm('<?= e((string) $labels['confirm_delete']) ?>');">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete_document">
                                    <input type="hidden" name="id" value="<?= $documentId ?>">
                                    <input type="hidden" name="return_q" value="<?= e($search) ?>">
                                    <input type="hidden" name="return_category" value="<?= e($categoryFilter) ?>">
                                    <input type="hidden" name="return_subcategory" value="<?= e($subcategoryFilter) ?>">
                                    <button class="button secondary" type="submit"><?= e((string) $labels['delete']) ?></button>
                                </form>
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <?php
    echo render_layout((string) ob_get_clean(), $adminLayoutTitle);
}
}
