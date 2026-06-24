<?php
declare(strict_types=1);

if (!function_exists('webotheque_i18n')) {
function webotheque_i18n(string $locale): array
{
    $messages = i18n_domain_locale('webotheque', $locale);
    $fallback = i18n_domain_locale('webotheque', 'en');

    return array_replace($fallback, $messages);
}
}

if (!function_exists('ensure_webotheque_table')) {
function ensure_webotheque_table(): bool
{
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS member_webotheque_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT NOT NULL,
            category VARCHAR(120) NOT NULL DEFAULT \'general\',
            subcategory VARCHAR(120) NOT NULL DEFAULT \'\',
            title VARCHAR(255) NOT NULL,
            url VARCHAR(500) NOT NULL,
            description TEXT NULL,
            tags VARCHAR(255) NOT NULL DEFAULT \'\',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL,
            INDEX idx_created (created_at),
            INDEX idx_category (category),
            INDEX idx_subcategory (subcategory),
            INDEX idx_category_subcategory (category, subcategory),
            INDEX idx_tags (tags),
            INDEX idx_member_created (member_id, created_at)
        )');

        if (!table_has_column('member_webotheque_links', 'category')) {
            db()->exec('ALTER TABLE member_webotheque_links ADD COLUMN category VARCHAR(120) NOT NULL DEFAULT "general" AFTER member_id');
        }
        if (!table_has_column('member_webotheque_links', 'subcategory')) {
            db()->exec('ALTER TABLE member_webotheque_links ADD COLUMN subcategory VARCHAR(120) NOT NULL DEFAULT "" AFTER category');
        }
        if (!table_has_index('member_webotheque_links', 'idx_category')) {
            db()->exec('ALTER TABLE member_webotheque_links ADD INDEX idx_category (category)');
        }
        if (!table_has_index('member_webotheque_links', 'idx_subcategory')) {
            db()->exec('ALTER TABLE member_webotheque_links ADD INDEX idx_subcategory (subcategory)');
        }
        if (!table_has_index('member_webotheque_links', 'idx_category_subcategory')) {
            db()->exec('ALTER TABLE member_webotheque_links ADD INDEX idx_category_subcategory (category, subcategory)');
        }
        db()->exec('UPDATE member_webotheque_links SET category = "general" WHERE category IS NULL OR category = ""');
        db()->exec('UPDATE member_webotheque_links SET subcategory = "" WHERE subcategory IS NULL');
        webotheque_ensure_categories_table();
        webotheque_ensure_subcategories_table();

        return table_exists('member_webotheque_links');
    } catch (Throwable) {
        return false;
    }
}
}

if (!function_exists('webotheque_normalize_url')) {
function webotheque_normalize_url(string $url): string
{
    $url = trim($url);
    $rawScheme = parse_url($url, PHP_URL_SCHEME);
    if (is_string($rawScheme) && $rawScheme !== '' && !in_array(strtolower($rawScheme), ['http', 'https'], true)) {
        return '';
    }
    if ($url !== '' && !preg_match('~^https?://~i', $url)) {
        $url = 'https://' . $url;
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return '';
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        return '';
    }

    return $url;
}
}

if (!function_exists('webotheque_domain_from_url')) {
function webotheque_domain_from_url(string $url): string
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if (str_starts_with($host, 'www.')) {
        $host = substr($host, 4);
    }

    return $host;
}
}

if (!function_exists('webotheque_category_code')) {
function webotheque_category_code(string $value): string
{
    return content_proposal_category_code($value, 120, 'webotheque');
}
}

if (!function_exists('webotheque_category_label_from_code')) {
function webotheque_category_label_from_code(string $code): string
{
    $label = trim(str_replace('-', ' ', $code));
    if ($label === '') {
        $messages = function_exists('i18n_domain_locale') ? i18n_domain_locale('webotheque', current_locale()) : [];

        return (string) ($messages['category_general'] ?? 'General');
    }

    return mb_convert_case($label, MB_CASE_TITLE, 'UTF-8');
}
}

if (!function_exists('webotheque_ensure_categories_table')) {
function webotheque_ensure_categories_table(array $t = []): bool
{
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS member_webotheque_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(120) NOT NULL,
            label VARCHAR(160) NOT NULL,
            sort_order INT NOT NULL DEFAULT 100,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_webotheque_category (code),
            INDEX idx_webotheque_category_deleted (deleted_at)
        )');
        if (!table_has_column('member_webotheque_categories', 'deleted_at')) {
            db()->exec('ALTER TABLE member_webotheque_categories ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER sort_order');
        }
        if (!table_has_index('member_webotheque_categories', 'idx_webotheque_category_deleted')) {
            db()->exec('ALTER TABLE member_webotheque_categories ADD INDEX idx_webotheque_category_deleted (deleted_at)');
        }

        $count = (int) (db()->query('SELECT COUNT(*) FROM member_webotheque_categories')->fetchColumn() ?: 0);
        if ($count === 0) {
            $insert = db()->prepare('INSERT IGNORE INTO member_webotheque_categories (code, label, sort_order) VALUES (?, ?, ?)');
            $sort = 10;
            foreach (webotheque_default_categories($t) as $code => $label) {
                $categoryCode = webotheque_category_code((string) $code);
                $categoryLabel = content_proposal_clean_single_line((string) $label, 160);
                if ($categoryCode === '' || $categoryLabel === '') {
                    continue;
                }
                $insert->execute([$categoryCode, $categoryLabel, $sort]);
                $sort += 10;
            }
        }

        return table_exists('member_webotheque_categories');
    } catch (Throwable) {
        return false;
    }
}
}

if (!function_exists('webotheque_subcategory_code')) {
function webotheque_subcategory_code(string $value): string
{
    return content_taxonomy_code($value, 120, '', true);
}
}

if (!function_exists('webotheque_subcategory_ref')) {
function webotheque_subcategory_ref(string $categoryCode, string $subcategoryCode): string
{
    $categoryCode = webotheque_category_code($categoryCode !== '' ? $categoryCode : 'general');
    $subcategoryCode = webotheque_subcategory_code($subcategoryCode);

    return $subcategoryCode !== '' ? ($categoryCode . ':' . $subcategoryCode) : '';
}
}

if (!function_exists('webotheque_subcategory_ref_parts')) {
/**
 * @return array{category:string,subcategory:string}
 */
function webotheque_subcategory_ref_parts(string $value): array
{
    $value = trim($value);
    if ($value === '') {
        return ['category' => '', 'subcategory' => ''];
    }

    $parts = explode(':', $value, 2);
    if (count($parts) === 2) {
        return [
            'category' => webotheque_category_code($parts[0] !== '' ? $parts[0] : 'general'),
            'subcategory' => webotheque_subcategory_code($parts[1]),
        ];
    }

    return [
        'category' => '',
        'subcategory' => webotheque_subcategory_code($value),
    ];
}
}

if (!function_exists('webotheque_ensure_subcategories_table')) {
function webotheque_ensure_subcategories_table(): bool
{
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS member_webotheque_subcategories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_code VARCHAR(120) NOT NULL,
            code VARCHAR(120) NOT NULL,
            label VARCHAR(160) NOT NULL,
            sort_order INT NOT NULL DEFAULT 100,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_webotheque_subcategory (category_code, code),
            INDEX idx_webotheque_subcategory_category (category_code)
        )');

        return table_exists('member_webotheque_subcategories');
    } catch (Throwable) {
        return false;
    }
}
}

if (!function_exists('webotheque_subcategory_options')) {
/**
 * @return list<array{category_code:string,code:string,label:string}>
 */
function webotheque_subcategory_options(): array
{
    if (!webotheque_ensure_subcategories_table()) {
        return [];
    }

    try {
        $rows = db()->query('SELECT category_code, code, label FROM member_webotheque_subcategories ORDER BY category_code ASC, sort_order ASC, label ASC')->fetchAll() ?: [];
    } catch (Throwable) {
        $rows = [];
    }

    $options = [];
    foreach ($rows as $row) {
        $categoryCode = webotheque_category_code((string) ($row['category_code'] ?? 'general'));
        $code = webotheque_subcategory_code((string) ($row['code'] ?? ''));
        $label = content_proposal_clean_single_line((string) ($row['label'] ?? $code), 160);
        if ($categoryCode === '' || $code === '' || $label === '') {
            continue;
        }
        $options[] = [
            'category_code' => $categoryCode,
            'code' => $code,
            'label' => $label,
        ];
    }

    return $options;
}
}

if (!function_exists('webotheque_subcategories_by_category')) {
/**
 * @return array<string, list<array{category_code:string,code:string,label:string,total?:int}>>
 */
function webotheque_subcategories_by_category(): array
{
    $byCategory = [];
    foreach (webotheque_subcategory_options() as $subcategory) {
        $byCategory[$subcategory['category_code']][] = $subcategory;
    }

    return $byCategory;
}
}

if (!function_exists('webotheque_visible_categories')) {
/**
 * @param array<string, string> $categories
 * @param array<string, int> $countsByCategory
 * @return array<string, string>
 */
function webotheque_visible_categories(array $categories, array $countsByCategory): array
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

if (!function_exists('webotheque_visible_subcategories_by_category')) {
/**
 * @param array<string, list<array<string, mixed>>> $subcategoriesByCategory
 * @param array<string, int> $countsBySubcategory
 * @return array<string, list<array<string, mixed>>>
 */
function webotheque_visible_subcategories_by_category(array $subcategoriesByCategory, array $countsBySubcategory): array
{
    $visible = [];
    foreach ($subcategoriesByCategory as $categoryCode => $subcategories) {
        foreach ($subcategories as $subcategory) {
            $code = webotheque_subcategory_code((string) ($subcategory['code'] ?? ''));
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

if (!function_exists('webotheque_list_state')) {
/**
 * @param array<string, string> $categories
 * @param array<string, mixed> $query
 * @return array{search:string,stats:array<string,mixed>,subcategories_by_category:array<string,list<array{category_code:string,code:string,label:string,total?:int}>>,visible_categories:array<string,string>,visible_subcategories_by_category:array<string,list<array<string,mixed>>>,category_filter:string,subcategory_filter:string}
 */
function webotheque_list_state(array $categories, array $query, bool $requireSubcategoryStats): array
{
    $search = trim((string) ($query['q'] ?? ''));
    if (mb_strlen($search) > 120) {
        $search = mb_substr($search, 0, 120);
    }

    $categoryFilter = '';
    $categoryInput = trim((string) ($query['category'] ?? ''));
    if ($categoryInput !== '') {
        $categoryCode = webotheque_category_code($categoryInput);
        if (isset($categories[$categoryCode])) {
            $categoryFilter = $categoryCode;
        }
    }

    $stats = webotheque_stats();
    $countsByCategory = [];
    foreach ((array) ($stats['by_category'] ?? []) as $categoryCode => $categoryTotal) {
        $countsByCategory[(string) $categoryCode] = (int) $categoryTotal;
    }
    $countsBySubcategory = [];
    foreach ((array) ($stats['by_subcategory'] ?? []) as $subcategoryKey => $subcategoryTotal) {
        $countsBySubcategory[(string) $subcategoryKey] = (int) $subcategoryTotal;
    }

    $subcategoriesByCategory = webotheque_subcategories_by_category();
    foreach ($countsBySubcategory as $subcategoryKey => $subcategoryTotal) {
        $parts = explode(':', (string) $subcategoryKey, 2);
        if (count($parts) !== 2 || $subcategoryTotal <= 0) {
            continue;
        }
        $parentCode = webotheque_category_code($parts[0]);
        $subcategoryCode = webotheque_subcategory_code($parts[1]);
        if ($parentCode === '' || $subcategoryCode === '') {
            continue;
        }
        $alreadyKnown = false;
        foreach ($subcategoriesByCategory[$parentCode] ?? [] as $subcategoryOption) {
            if (webotheque_subcategory_code((string) $subcategoryOption['code']) === $subcategoryCode) {
                $alreadyKnown = true;
                break;
            }
        }
        if (!$alreadyKnown) {
            $subcategoriesByCategory[$parentCode][] = [
                'category_code' => $parentCode,
                'code' => $subcategoryCode,
                'label' => webotheque_category_label_from_code($subcategoryCode),
            ];
        }
    }

    $visibleCategories = webotheque_visible_categories($categories, $countsByCategory);
    $visibleSubcategoriesByCategory = webotheque_visible_subcategories_by_category($subcategoriesByCategory, $countsBySubcategory);
    $subcategoryFilter = '';
    $subcategoryInput = trim((string) ($query['subcategory'] ?? ''));
    if ($subcategoryInput !== '') {
        $subcategoryCode = webotheque_subcategory_code($subcategoryInput);
        if ($subcategoryCode !== '') {
            $candidateCategory = $categoryFilter;
            if ($candidateCategory === '') {
                foreach ($visibleSubcategoriesByCategory as $parentCode => $subcategories) {
                    foreach ($subcategories as $subcategoryInfo) {
                        if (webotheque_subcategory_code((string) ($subcategoryInfo['code'] ?? '')) === $subcategoryCode) {
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

if (!function_exists('webotheque_favorites_label')) {
/**
 * @param array<string, mixed> $messages
 */
function webotheque_favorites_label(array $messages, string $locale = ''): string
{
    $label = trim((string) ($messages['favorites'] ?? ''));
    if ($label !== '') {
        return $label;
    }

    $messages = webotheque_i18n($locale !== '' ? $locale : (function_exists('current_locale') ? current_locale() : 'fr'));

    return trim((string) $messages['favorites']);
}
}

if (!function_exists('webotheque_default_categories')) {
/**
 * @return array<string, string>
 */
function webotheque_default_categories(array $t): array
{
    return [
        'general' => (string) $t['category_general'],
        'radioamateur' => (string) $t['category_radioamateur'],
        'antennes' => (string) $t['category_antennes'],
        'propagation' => (string) $t['category_propagation'],
        'modes-numeriques' => (string) $t['category_modes_numeriques'],
        'logiciels' => (string) $t['category_logiciels'],
        'materiel' => (string) $t['category_materiel'],
        'reglementation' => (string) $t['category_reglementation'],
        'formation' => (string) $t['category_formation'],
    ];
}
}

if (!function_exists('webotheque_categories')) {
/**
 * @return array<string, string>
 */
function webotheque_categories(array $t): array
{
    $categories = [];
    $deletedCategories = [];
    $categoryTableAvailable = webotheque_ensure_categories_table($t);

    if ($categoryTableAvailable) {
        try {
            $rows = db()->query('SELECT code, label FROM member_webotheque_categories WHERE deleted_at IS NULL ORDER BY sort_order ASC, label ASC')->fetchAll() ?: [];
            foreach ($rows as $row) {
                $code = webotheque_category_code((string) ($row['code'] ?? ''));
                $label = content_proposal_clean_single_line((string) ($row['label'] ?? ''), 160);
                if ($code !== '' && $label !== '') {
                    $categories[$code] = $label;
                }
            }
            $deletedRows = db()->query('SELECT code FROM member_webotheque_categories WHERE deleted_at IS NOT NULL')->fetchAll() ?: [];
            foreach ($deletedRows as $row) {
                $code = webotheque_category_code((string) ($row['code'] ?? ''));
                if ($code !== '') {
                    $deletedCategories[$code] = true;
                }
            }
        } catch (Throwable) {
            $categories = [];
            $deletedCategories = [];
        }
    }

    if (!$categoryTableAvailable) {
        $categories = webotheque_default_categories($t);
    }

    try {
        if (table_has_column('member_webotheque_links', 'category')) {
            $rows = db()->query('SELECT category FROM member_webotheque_links WHERE category IS NOT NULL AND category <> "" GROUP BY category ORDER BY category ASC')->fetchAll() ?: [];
            foreach ($rows as $row) {
                $code = webotheque_category_code((string) ($row['category'] ?? ''));
                if ($code !== '' && !isset($deletedCategories[$code]) && !isset($categories[$code])) {
                    $categories[$code] = webotheque_category_label_from_code($code);
                }
            }
        }
    } catch (Throwable) {
        // Keep the base category if optional category metadata cannot be read.
    }

    foreach (content_proposal_accepted_categories('webotheque', 120) + content_proposal_accepted_terms('webotheque', 'domain', 120, 'webotheque') as $code => $label) {
        $code = webotheque_category_code((string) $code);
        $label = content_proposal_clean_single_line((string) $label, 190);
        if ($code !== '' && $label !== '' && !isset($deletedCategories[$code])) {
            $categories[$code] = $label;
        }
    }

    return $categories;
}
}

if (!function_exists('webotheque_category_from_input')) {
/**
 * @param array<string, string> $categories
 */
function webotheque_category_from_input(string $value, array $categories): string
{
    $value = trim($value);
    if ($value === '') {
        return 'general';
    }

    $code = webotheque_category_code($value);
    if (!isset($categories[$code])) {
        throw new RuntimeException('err_category');
    }

    return $code;
}
}

if (!function_exists('webotheque_taxonomy_from_input')) {
/**
 * @param array<string, string> $categories
 * @return array{category:string,subcategory:string}
 */
function webotheque_taxonomy_from_input(string $categoryInput, string $subcategoryRef, array $categories, string $fallbackCategory = 'general'): array
{
    $category = webotheque_category_from_input($categoryInput !== '' ? $categoryInput : $fallbackCategory, $categories);
    $subcategoryRef = trim($subcategoryRef);
    if ($subcategoryRef === '') {
        return [$category, ''];
    }

    $parts = webotheque_subcategory_ref_parts($subcategoryRef);
    if ($parts['subcategory'] === '') {
        return [$category, ''];
    }

    $refCategory = $parts['category'] !== '' ? webotheque_category_from_input($parts['category'], $categories) : $category;
    if ($refCategory !== $category) {
        throw new RuntimeException('err_subcategory_category_mismatch');
    }

    foreach ((array) (webotheque_subcategories_by_category()[$category] ?? []) as $knownSubcategory) {
        if (webotheque_subcategory_code((string) ($knownSubcategory['code'] ?? '')) === $parts['subcategory']) {
            return [$category, $parts['subcategory']];
        }
    }

    throw new RuntimeException('err_subcategory_category_mismatch');
}
}

if (!function_exists('webotheque_upsert_subcategory')) {
/**
 * @param array<string, string> $categories
 * @return array{category:string,subcategory:string}
 */
function webotheque_upsert_subcategory(array $categories, string $categoryInput, string $label): array
{
    if (!webotheque_ensure_subcategories_table()) {
        throw new RuntimeException('storage_unavailable');
    }

    $category = webotheque_category_from_input($categoryInput !== '' ? $categoryInput : 'general', $categories);
    $label = content_proposal_clean_single_line($label, 160);
    $code = webotheque_subcategory_code($label);
    if ($label === '' || $code === '') {
        throw new RuntimeException('err_subcategory_required');
    }

    db()->prepare('INSERT INTO member_webotheque_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
        ->execute([$category, $code, $label]);

    return ['category' => $category, 'subcategory' => $code];
}
}

if (!function_exists('webotheque_link_form_values')) {
/**
 * @param array<string, mixed> $input
 * @param array<string, string> $categories
 * @return array{title:string,url:string,description:string,tags:string,category:string,subcategory:string}
 */
function webotheque_link_form_values(array $input, array $categories): array
{
    $title = content_proposal_clean_single_line((string) ($input['title'] ?? ''), 190);
    $url = webotheque_normalize_url((string) ($input['url'] ?? ''));
    $description = content_proposal_clean_multiline((string) ($input['description'] ?? ''), 5000);
    $tags = content_proposal_clean_single_line((string) ($input['tags'] ?? ''), 255);
    [$category, $subcategory] = webotheque_taxonomy_from_input(
        (string) ($input['category'] ?? ''),
        trim((string) ($input['subcategory_ref'] ?? '')),
        $categories
    );

    return [
        'title' => $title,
        'url' => $url,
        'description' => $description,
        'tags' => $tags,
        'category' => $category,
        'subcategory' => $subcategory,
    ];
}
}

if (!function_exists('webotheque_member_contact')) {
function webotheque_member_contact(array $user): string
{
    $email = trim((string) ($user['email'] ?? ''));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }

    return '';
}
}

if (!function_exists('webotheque_insert_link')) {
function webotheque_insert_link(
    int $memberId,
    string $category,
    string $title,
    string $url,
    string $description = '',
    string $tags = '',
    string $subcategory = ''
): int {
    $title = content_proposal_clean_single_line($title, 190);
    $url = webotheque_normalize_url($url);
    $description = content_proposal_clean_multiline($description, 5000);
    $tags = content_proposal_clean_single_line($tags, 255);
    $category = webotheque_category_code($category);
    $subcategory = webotheque_subcategory_code($subcategory);
    if ($memberId <= 0 || $title === '' || $url === '' || $category === '') {
        throw new RuntimeException($url === '' ? 'err_url' : 'err_required');
    }

    db()->prepare('INSERT INTO member_webotheque_links (member_id, category, subcategory, title, url, description, tags) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$memberId, $category, $subcategory, $title, $url, $description !== '' ? $description : null, $tags]);

    return (int) db()->lastInsertId();
}
}

if (!function_exists('webotheque_proposal_summary_labels')) {
/**
 * @return list<string>
 */
function webotheque_proposal_summary_labels(string $key, array $legacyLabels = []): array
{
    static $labelsByKey = [];
    $cacheKey = $key . '|' . implode('|', $legacyLabels);
    if (isset($labelsByKey[$cacheKey])) {
        return $labelsByKey[$cacheKey];
    }

    $labels = $legacyLabels;
    foreach (supported_locales() as $locale) {
        $messages = webotheque_i18n($locale);
        $label = trim((string) ($messages[$key] ?? ''));
        if ($label !== '') {
            $labels[] = $label;
        }
    }

    $labelsByKey[$cacheKey] = array_values(array_unique($labels));

    return $labelsByKey[$cacheKey];
}
}

if (!function_exists('webotheque_link_proposal_action')) {
function webotheque_link_proposal_action(string $summary): string
{
    $action = content_proposal_clean_single_line(
        content_proposal_detail_from_summary($summary, webotheque_proposal_summary_labels('proposal_action', ['Action'])),
        32
    );

    return in_array($action, ['update_link', 'delete_link'], true) ? $action : '';
}
}

if (!function_exists('webotheque_link_proposal_link_id')) {
function webotheque_link_proposal_link_id(string $summary): int
{
    return max(0, (int) content_proposal_detail_from_summary($summary, webotheque_proposal_summary_labels('proposal_link_id', ['Link ID', 'Lien ID', 'ID du lien'])));
}
}

if (!function_exists('webotheque_update_link_record')) {
function webotheque_update_link_record(
    int $linkId,
    string $category,
    string $title,
    string $url,
    string $description = '',
    string $tags = '',
    string $subcategory = ''
): void {
    if (!ensure_webotheque_table()) {
        throw new RuntimeException('storage_unavailable');
    }

    $linkId = max(0, $linkId);
    $title = content_proposal_clean_single_line($title, 190);
    $url = webotheque_normalize_url($url);
    $description = content_proposal_clean_multiline($description, 5000);
    $tags = content_proposal_clean_single_line($tags, 255);
    $category = webotheque_category_code($category);
    $subcategory = webotheque_subcategory_code($subcategory);
    if ($linkId <= 0 || $title === '' || $url === '' || $category === '') {
        throw new RuntimeException($url === '' ? 'err_url' : 'err_required');
    }

    $stmt = db()->prepare('SELECT id FROM member_webotheque_links WHERE id = ? LIMIT 1');
    $stmt->execute([$linkId]);
    if ((int) ($stmt->fetchColumn() ?: 0) <= 0) {
        throw new RuntimeException('err_required');
    }

    db()->prepare('UPDATE member_webotheque_links SET category = ?, subcategory = ?, title = ?, url = ?, description = ?, tags = ?, updated_at = NOW() WHERE id = ?')
        ->execute([$category, $subcategory, $title, $url, $description !== '' ? $description : null, $tags, $linkId]);

    if (table_exists('member_favorites')) {
        db()->prepare('UPDATE member_favorites SET title = ?, url = ? WHERE target_type = ? AND target_id = ?')
            ->execute([$title, route_url_clean('webotheque', ['category' => $category, 'subcategory' => $subcategory, 'q' => $title]), 'webotheque_link', $linkId]);
    }
}
}

if (!function_exists('webotheque_delete_link_record')) {
function webotheque_delete_link_record(int $linkId): void
{
    if (!ensure_webotheque_table()) {
        throw new RuntimeException('storage_unavailable');
    }

    $linkId = max(0, $linkId);
    if ($linkId <= 0) {
        throw new RuntimeException('err_required');
    }

    $stmt = db()->prepare('SELECT id FROM member_webotheque_links WHERE id = ? LIMIT 1');
    $stmt->execute([$linkId]);
    if ((int) ($stmt->fetchColumn() ?: 0) <= 0) {
        throw new RuntimeException('err_required');
    }

    db()->prepare('DELETE FROM member_webotheque_links WHERE id = ? LIMIT 1')->execute([$linkId]);
    if (table_exists('member_favorites')) {
        db()->prepare('DELETE FROM member_favorites WHERE target_type = ? AND target_id = ?')->execute(['webotheque_link', $linkId]);
    }
}
}

if (!function_exists('webotheque_link_summary')) {
/**
 * @param array<string, string> $t
 */
function webotheque_link_summary(array $t, string $categoryLabel, string $description, string $tags, string $subcategory = ''): string
{
    return content_proposal_details_text([
        (string) $t['domain_field'] => $categoryLabel,
        (string) $t['subcategory_field'] => $subcategory,
        (string) $t['description_field'] => $description,
        (string) $t['tags_field'] => $tags,
    ]);
}
}

if (!function_exists('webotheque_proposal_source_url')) {
function webotheque_proposal_source_url(string $sourceRef): string
{
    $sourceRef = trim($sourceRef);
    if ($sourceRef === '') {
        return '';
    }
    if (preg_match('/https?:\/\/\S+/i', $sourceRef, $match) === 1) {
        return webotheque_normalize_url((string) $match[0]);
    }

    return webotheque_normalize_url($sourceRef);
}
}

if (!function_exists('webotheque_proposal_detail_from_summary')) {
/**
 * @param list<string> $labels
 */
function webotheque_proposal_detail_from_summary(string $summary, array $labels): string
{
    return content_proposal_detail_from_summary($summary, $labels);
}
}

if (!function_exists('webotheque_proposal_category_from_summary')) {
/**
 * @param array<string, string> $categories
 */
function webotheque_proposal_category_from_summary(string $summary, array $categories): string
{
    foreach (preg_split('/\R/u', $summary) ?: [] as $line) {
        if (preg_match('/^\s*([^:]{1,120}):\s*(.+)\s*$/u', (string) $line, $matches) !== 1) {
            continue;
        }
        $code = webotheque_category_code((string) $matches[2]);
        if (isset($categories[$code])) {
            return $code;
        }
    }

    return 'general';
}
}

if (!function_exists('webotheque_proposal_subcategory_from_summary')) {
function webotheque_proposal_subcategory_from_summary(string $summary, array $t = []): string
{
    $labels = isset($t['subcategory_field']) ? [(string) $t['subcategory_field']] : [];
    $labels = array_merge($labels, [
        'Subtopic',
        'Subcategory',
        'Sous-thématique',
        'Sous-thematique',
        'Sous thématique',
        'Sous thematique',
        'Sous-thème',
        'Sous-theme',
    ]);

    return webotheque_subcategory_code(webotheque_proposal_detail_from_summary($summary, array_values(array_unique($labels))));
}
}

if (!function_exists('webotheque_tags_from_text')) {
/**
 * @return array<string, string>
 */
function webotheque_tags_from_text(string $value): array
{
    $tags = [];
    foreach (preg_split('/[,;#]+/u', $value) ?: [] as $part) {
        $tag = content_proposal_clean_single_line((string) $part, 80);
        if ($tag === '') {
            continue;
        }
        $tags[mb_strtolower($tag, 'UTF-8')] = $tag;
    }

    return $tags;
}
}

if (!function_exists('webotheque_accepted_tags')) {
/**
 * @return array<string, string>
 */
function webotheque_accepted_tags(): array
{
    $tags = [];
    foreach (content_proposal_accepted_terms('webotheque', 'tag', 80, 'tag') as $label) {
        foreach (webotheque_tags_from_text((string) $label) as $key => $tag) {
            $tags[$key] = $tag;
        }
    }

    return $tags;
}
}

if (!function_exists('webotheque_apply_accepted_proposal')) {
function webotheque_apply_accepted_proposal(
    array $proposal,
    array $categories,
    array $t = [],
    int $fallbackMemberId = 0,
    string $categoryOverride = ''
): ?int {
    $proposalType = (string) ($proposal['proposal_type'] ?? '');
    $summary = (string) ($proposal['summary'] ?? '');

    if ($proposalType === 'subcategory') {
        $category = $categoryOverride !== ''
            ? webotheque_category_from_input($categoryOverride, $categories)
            : webotheque_proposal_category_from_summary($summary, $categories);
        webotheque_upsert_subcategory($categories, $category, (string) ($proposal['title'] ?? ''));

        return null;
    }

    if ($proposalType !== 'content') {
        return null;
    }

    $linkAction = webotheque_link_proposal_action($summary);
    if ($linkAction !== '') {
        $linkId = webotheque_link_proposal_link_id($summary);
        if ($linkAction === 'delete_link') {
            webotheque_delete_link_record($linkId);

            return $linkId;
        }

        $sourceUrl = webotheque_proposal_source_url((string) ($proposal['source_ref'] ?? ''));
        if ($sourceUrl === '') {
            throw new RuntimeException('err_url');
        }
        $category = $categoryOverride !== ''
            ? webotheque_category_from_input($categoryOverride, $categories)
            : webotheque_proposal_category_from_summary($summary, $categories);
        $descriptionLabels = isset($t['description_field']) ? [(string) $t['description_field'], 'Description'] : ['Description'];
        $tagLabels = isset($t['tags_field']) ? [(string) $t['tags_field'], 'Tags', 'Étiquettes', 'Etiquettes'] : ['Tags', 'Étiquettes', 'Etiquettes'];
        $description = webotheque_proposal_detail_from_summary($summary, array_values(array_unique($descriptionLabels)));
        $tags = webotheque_proposal_detail_from_summary($summary, array_values(array_unique($tagLabels)));
        $subcategory = webotheque_proposal_subcategory_from_summary($summary, $t);

        webotheque_update_link_record(
            $linkId,
            $category,
            (string) ($proposal['title'] ?? ''),
            $sourceUrl,
            $description,
            $tags,
            $subcategory
        );

        return $linkId;
    }

    $sourceUrl = webotheque_proposal_source_url((string) ($proposal['source_ref'] ?? ''));
    if ($sourceUrl === '') {
        throw new RuntimeException('err_url');
    }
    $category = $categoryOverride !== ''
        ? webotheque_category_from_input($categoryOverride, $categories)
        : webotheque_proposal_category_from_summary((string) ($proposal['summary'] ?? ''), $categories);

    $descriptionLabels = isset($t['description_field']) ? [(string) $t['description_field'], 'Description'] : ['Description'];
    $description = webotheque_proposal_detail_from_summary($summary, array_values(array_unique($descriptionLabels)));
    if ($description === '') {
        $description = $summary;
    }
    $tagLabels = isset($t['tags_field']) ? [(string) $t['tags_field'], 'Tags', 'Étiquettes', 'Etiquettes'] : ['Tags', 'Étiquettes', 'Etiquettes'];
    $tags = webotheque_proposal_detail_from_summary($summary, array_values(array_unique($tagLabels)));
    $subcategory = webotheque_proposal_subcategory_from_summary($summary, $t);

    $existingStmt = db()->prepare('SELECT id FROM member_webotheque_links WHERE url = ? LIMIT 1');
    $existingStmt->execute([$sourceUrl]);
    $existingId = (int) ($existingStmt->fetchColumn() ?: 0);
    if ($existingId > 0) {
        return $existingId;
    }

    return webotheque_insert_link(
        max(0, (int) ($proposal['member_id'] ?? $fallbackMemberId)),
        $category,
        (string) ($proposal['title'] ?? ''),
        $sourceUrl,
        $description,
        $tags,
        $subcategory
    );
}
}

if (!function_exists('webotheque_sync_accepted_proposals')) {
/**
 * @param array<string, string> $categories
 * @param array<string, string> $t
 * @return array{checked:int,applied:int,skipped:int,failed:int}
 */
function webotheque_sync_accepted_proposals(array $categories, array $t = [], int $fallbackMemberId = 0, int $limit = 100): array
{
    static $alreadyRan = false;

    $result = ['checked' => 0, 'applied' => 0, 'skipped' => 0, 'failed' => 0];
    if ($alreadyRan) {
        return $result;
    }
    $alreadyRan = true;

    if (!ensure_content_proposals_table() || !ensure_webotheque_table()) {
        return $result;
    }

    $limit = max(1, min(500, $limit));
    try {
        $stmt = db()->prepare(
            'SELECT id, member_id, proposal_type, title, summary, source_ref
             FROM content_proposals
             WHERE area = "webotheque"
               AND status = "accepted"
               AND proposal_type IN ("content", "subcategory")
             ORDER BY updated_at ASC, id ASC
             LIMIT ' . $limit
        );
        $stmt->execute();
        $proposals = $stmt->fetchAll() ?: [];
    } catch (Throwable $throwable) {
        log_structured_event('webotheque_accepted_proposals_sync_load_failed', [
            'message' => $throwable->getMessage(),
        ]);

        return $result;
    }

    foreach ($proposals as $proposal) {
        $result['checked']++;
        try {
            $proposalType = (string) ($proposal['proposal_type'] ?? 'content');
            if ($proposalType === 'subcategory') {
                $summary = (string) ($proposal['summary'] ?? '');
                $category = webotheque_proposal_category_from_summary($summary, $categories);
                $subcategory = webotheque_subcategory_code((string) ($proposal['title'] ?? ''));
                if ($subcategory === '') {
                    throw new RuntimeException('err_subcategory_required');
                }
                if (!webotheque_ensure_subcategories_table()) {
                    throw new RuntimeException('storage_unavailable');
                }

                $existingStmt = db()->prepare('SELECT id FROM member_webotheque_subcategories WHERE category_code = ? AND code = ? LIMIT 1');
                $existingStmt->execute([$category, $subcategory]);
                if ((int) ($existingStmt->fetchColumn() ?: 0) > 0) {
                    $result['skipped']++;
                    continue;
                }

                webotheque_apply_accepted_proposal($proposal, $categories, $t, $fallbackMemberId);
                $result['applied']++;
                continue;
            }

            if (webotheque_link_proposal_action((string) ($proposal['summary'] ?? '')) !== '') {
                $result['skipped']++;
                continue;
            }

            $sourceUrl = webotheque_proposal_source_url((string) ($proposal['source_ref'] ?? ''));
            if ($sourceUrl === '') {
                throw new RuntimeException('err_url');
            }

            $existingStmt = db()->prepare('SELECT id FROM member_webotheque_links WHERE url = ? LIMIT 1');
            $existingStmt->execute([$sourceUrl]);
            if ((int) ($existingStmt->fetchColumn() ?: 0) > 0) {
                $result['skipped']++;
                continue;
            }

            webotheque_apply_accepted_proposal($proposal, $categories, $t, $fallbackMemberId);
            $result['applied']++;
        } catch (Throwable $throwable) {
            $result['failed']++;
            log_structured_event('webotheque_accepted_proposal_sync_failed', [
                'proposal_id' => (int) ($proposal['id'] ?? 0),
                'message' => $throwable->getMessage(),
            ]);
        }
    }

    return $result;
}
}

if (!function_exists('webotheque_stats')) {
function webotheque_stats(): array
{
    $rows = db()->query('SELECT url, tags, category, subcategory, created_at FROM member_webotheque_links')->fetchAll() ?: [];
    $domains = [];
    $tags = webotheque_accepted_tags();
    $byCategory = [];
    $bySubcategory = [];
    $latest = '';
    $latestTimestamp = 0;
    foreach ($rows as $row) {
        $domain = webotheque_domain_from_url((string) ($row['url'] ?? ''));
        if ($domain !== '') {
            $domains[$domain] = true;
        }
        $category = webotheque_category_code((string) ($row['category'] ?? ''));
        if ($category !== '') {
            $byCategory[$category] = ($byCategory[$category] ?? 0) + 1;
        }
        $subcategory = webotheque_subcategory_code((string) ($row['subcategory'] ?? ''));
        if ($category !== '' && $subcategory !== '') {
            $bySubcategory[$category . ':' . $subcategory] = ($bySubcategory[$category . ':' . $subcategory] ?? 0) + 1;
        }
        foreach (webotheque_tags_from_text((string) ($row['tags'] ?? '')) as $key => $tag) {
            $tags[$key] = $tag;
        }
        $createdAt = trim((string) ($row['created_at'] ?? ''));
        $createdTimestamp = $createdAt !== '' ? strtotime($createdAt) : false;
        if ($createdTimestamp !== false && $createdTimestamp > $latestTimestamp) {
            $latestTimestamp = $createdTimestamp;
            $latest = $createdAt;
        }
    }

    return ['total' => count($rows), 'tags' => count($tags), 'domains' => count($domains), 'latest' => $latest, 'by_category' => $byCategory, 'by_subcategory' => $bySubcategory];
}
}

if (!function_exists('webotheque_fetch_links')) {
/**
 * @param list<int> $favoriteIds
 */
function webotheque_fetch_links(string $search, string $category = '', int $limit = 80, string $subcategory = '', array $favoriteIds = []): array
{
    $where = [];
    $params = [];
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
        $where[] = '(title LIKE ? OR url LIKE ? OR description LIKE ? OR tags LIKE ? OR category LIKE ? OR subcategory LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }
    $whereSql = $where === [] ? '' : (' WHERE ' . implode(' AND ', $where));
    $stmt = db()->prepare('SELECT * FROM member_webotheque_links' . $whereSql . ' ORDER BY created_at DESC, id DESC LIMIT ' . (int) $limit);
    $stmt->execute($params);

    return $stmt->fetchAll() ?: [];
}
}

if (!function_exists('webotheque_favorite_link_ids')) {
/**
 * @return list<int>
 */
function webotheque_favorite_link_ids(int $memberId): array
{
    if (
        $memberId <= 0
        || !function_exists('ensure_member_favorites_table')
        || !ensure_member_favorites_table()
        || !ensure_webotheque_table()
    ) {
        return [];
    }

    try {
        $stmt = db()->prepare('SELECT l.id FROM member_favorites f INNER JOIN member_webotheque_links l ON l.id = f.target_id WHERE f.member_id = ? AND f.target_type = ? ORDER BY f.created_at DESC, f.id DESC');
        $stmt->execute([$memberId, 'webotheque_link']);
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

if (!function_exists('render_webotheque_cards')) {
function render_webotheque_cards(array $links, array $t, array $categories = [], ?array $viewer = null, bool $canManage = false, array $returnQuery = []): string
{
    $html = '';
    $text = static fn(string $key): string => (string) $t[$key];
    $viewerId = max(0, (int) ($viewer['id'] ?? 0));
    $returnCategory = (string) ($returnQuery['category'] ?? '');
    $returnSubcategory = (string) ($returnQuery['subcategory'] ?? '');
    $returnFavorites = (string) ($returnQuery['favorites'] ?? '');
    $returnSearch = (string) ($returnQuery['q'] ?? '');
    $subcategoriesByCategory = webotheque_subcategories_by_category();
    $subcategoryLabels = [];
    foreach ($subcategoriesByCategory as $parentCode => $subcategories) {
        foreach ($subcategories as $subcategory) {
            $subcategoryLabels[$parentCode . ':' . (string) $subcategory['code']] = (string) $subcategory['label'];
        }
    }

    foreach ($links as $link) {
        $title = trim((string) ($link['title'] ?? ''));
        $url = trim((string) ($link['url'] ?? ''));
        if ($title === '' || $url === '') {
            continue;
        }
        $description = trim((string) ($link['description'] ?? ''));
        $tags = trim((string) ($link['tags'] ?? ''));
        $linkId = max(0, (int) ($link['id'] ?? 0));
        $category = webotheque_category_code((string) ($link['category'] ?? 'general'));
        $categoryLabel = (string) ($categories[$category] ?? webotheque_category_label_from_code($category));
        $subcategory = webotheque_subcategory_code((string) ($link['subcategory'] ?? ''));
        $subcategoryLabel = $subcategory !== '' ? (string) ($subcategoryLabels[$category . ':' . $subcategory] ?? $subcategory) : '';
        $domain = webotheque_domain_from_url($url);
        $canEditLink = $linkId > 0 && ($canManage || ($viewerId > 0 && (int) ($link['member_id'] ?? 0) === $viewerId));
        $isFavorite = $viewerId > 0 && $linkId > 0 && function_exists('favorite_is_saved') && favorite_is_saved($viewerId, 'webotheque_link', $linkId);
        $dialogId = 'webotheque-edit-dialog-' . $linkId;

        $html .= '<article class="news-card feature-card webotheque-card">'
            . '<span class="badge muted">' . e($domain !== '' ? $domain : (string) $t['link']) . '</span>'
            . '<h2>' . e($title) . '</h2>';
        if ($description !== '') {
            $html .= '<p>' . e($description) . '</p>';
        }
        $html .= '<p class="help">' . e((string) $t['domain_field']) . ': ' . e($categoryLabel) . ($subcategoryLabel !== '' ? ' / ' . e($subcategoryLabel) : '') . '</p>';
        if ($tags !== '') {
            $html .= '<p class="help">' . e((string) $t['tags']) . ': ' . e($tags) . '</p>';
        }
        $html .= '<div class="actions webotheque-link-actions">'
            . '<a class="button secondary" href="' . e($url) . '" target="_blank" rel="noopener noreferrer">' . e((string) $t['open']) . '</a>';
        if ($canEditLink) {
            $html .= '<button class="button secondary" type="button" data-webotheque-modal-open="' . e($dialogId) . '" aria-haspopup="dialog" aria-controls="' . e($dialogId) . '">' . e($text('edit_link')) . '</button>';
        }
        if ($viewerId > 0 && $linkId > 0 && function_exists('favorite_toggle')) {
            $html .= '<form method="post" class="inline-form">'
                . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="action" value="toggle_favorite_link">'
                . '<input type="hidden" name="id" value="' . $linkId . '">'
                . '<input type="hidden" name="return_category" value="' . e($returnCategory) . '">'
                . '<input type="hidden" name="return_subcategory" value="' . e($returnSubcategory) . '">'
                . '<input type="hidden" name="return_favorites" value="' . e($returnFavorites) . '">'
                . '<input type="hidden" name="return_q" value="' . e($returnSearch) . '">'
                . '<button class="button secondary" type="submit">' . ($isFavorite ? '&#9733; ' : '&#9734; ') . e($text('favorite')) . '</button>'
                . '</form>';
        }
        $html .= '</div></article>';

        if ($canEditLink) {
            $html .= '<dialog class="webotheque-proposal-dialog" id="' . e($dialogId) . '" aria-labelledby="' . e($dialogId) . '-title">'
                . '<div class="webotheque-proposal-dialog-card">'
                . '<div class="webotheque-proposal-dialog-header module-dialog-header">'
                . '<div><p class="eyebrow">' . e((string) $t['link']) . '</p>'
                . '<h2 id="' . e($dialogId) . '-title">' . e($text('edit_link_title')) . '</h2>'
                . '<p class="help">' . e($title) . '</p></div>'
                . '<button class="webotheque-proposal-dialog-close module-dialog-close" type="button" data-webotheque-modal-close aria-label="' . e($text('cancel')) . '">&times;</button>'
                . '</div>'
                . '<form method="post" class="webotheque-proposal-form module-dialog-form">'
                . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="action" value="update_link">'
                . '<input type="hidden" name="id" value="' . $linkId . '">'
                . '<input type="hidden" name="return_category" value="' . e($returnCategory) . '">'
                . '<input type="hidden" name="return_subcategory" value="' . e($returnSubcategory) . '">'
                . '<input type="hidden" name="return_favorites" value="' . e($returnFavorites) . '">'
                . '<input type="hidden" name="return_q" value="' . e($returnSearch) . '">'
                . render_webotheque_link_fields($t, $categories, null, [
                    'title' => $title,
                    'url' => $url,
                    'category' => $category,
                    'subcategory' => $subcategory,
                    'description' => $description,
                    'tags' => $tags,
                ])
                . '<p class="webotheque-proposal-dialog-actions module-dialog-actions">'
                . '<button class="button" type="submit">' . e($text('save')) . '</button>'
                . '<button class="button secondary" type="button" data-webotheque-modal-close>' . e($text('cancel')) . '</button>'
                . '</p></form>'
                . '<form method="post" class="webotheque-delete-form">'
                . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="action" value="delete_link">'
                . '<input type="hidden" name="id" value="' . $linkId . '">'
                . '<input type="hidden" name="return_category" value="' . e($returnCategory) . '">'
                . '<input type="hidden" name="return_subcategory" value="' . e($returnSubcategory) . '">'
                . '<input type="hidden" name="return_favorites" value="' . e($returnFavorites) . '">'
                . '<input type="hidden" name="return_q" value="' . e($returnSearch) . '">'
                . '<p class="help">' . e($text('delete_link_warning')) . '</p>'
                . '<button class="button secondary webotheque-danger" type="submit">' . e($text('delete_link')) . '</button>'
                . '</form></div></dialog>';
        }
    }

    return $html;
}
}

if (!function_exists('render_webotheque_link_fields')) {
/**
 * @param array<string, string> $t
 * @param array<string, string> $categories
 */
function render_webotheque_link_fields(array $t, array $categories, ?string $proposalContact = null, array $values = []): string
{
    $title = (string) ($values['title'] ?? '');
    $url = (string) ($values['url'] ?? '');
    $selectedCategory = webotheque_category_code((string) ($values['category'] ?? 'general'));
    $selectedSubcategory = webotheque_subcategory_code((string) ($values['subcategory'] ?? ''));
    $description = (string) ($values['description'] ?? '');
    $tags = (string) ($values['tags'] ?? '');
    $subcategoriesByCategory = webotheque_subcategories_by_category();

    $html = '<label><span>' . e((string) $t['title_field']) . '</span><input type="text" name="title" value="' . e($title) . '" maxlength="190" required></label>'
        . '<label><span>' . e((string) $t['url_field']) . '</span><input type="url" name="url" value="' . e($url) . '" maxlength="500" placeholder="https://example.org" required></label>'
        . '<label><span>' . e((string) $t['domain_field']) . '</span><select name="category">';

    foreach ($categories as $code => $label) {
        $code = (string) $code;
        $html .= '<option value="' . e($code) . '"' . ($selectedCategory === $code ? ' selected' : '') . '>' . e((string) $label) . '</option>';
    }

    $html .= '</select></label>'
        . '<label><span>' . e((string) $t['subcategory_field']) . '</span><select name="subcategory_ref">'
        . '<option value="">' . e((string) $t['no_subcategory']) . '</option>';

    foreach ($subcategoriesByCategory as $parentCode => $subcategories) {
        $html .= '<optgroup label="' . e((string) ($categories[$parentCode] ?? $parentCode)) . '">';
        foreach ($subcategories as $subcategory) {
            $code = webotheque_subcategory_code((string) $subcategory['code']);
            if ($code === '') {
                continue;
            }
            $html .= '<option value="' . e(webotheque_subcategory_ref((string) $parentCode, $code)) . '"'
                . ($selectedCategory === (string) $parentCode && $selectedSubcategory === $code ? ' selected' : '')
                . '>' . e((string) $subcategory['label']) . '</option>';
        }
        $html .= '</optgroup>';
    }

    $html .= '</select></label>'
        . '<label><span>' . e((string) $t['description_field']) . '</span><textarea name="description" rows="4">' . e($description) . '</textarea></label>'
        . '<label><span>' . e((string) $t['tags_field']) . '</span><input type="text" name="tags" value="' . e($tags) . '" maxlength="255"></label>';

    if ($proposalContact !== null) {
        $html .= '<label><span>' . e((string) $t['contact_field']) . '</span><input type="email" name="proposal_contact" maxlength="220" value="' . e($proposalContact) . '"></label>';
    }

    return $html;
}
}

if (!function_exists('render_webotheque_page')) {
function render_webotheque_page(): void
{
    $user = require_login();
    $locale = current_locale();
    $t = webotheque_i18n($locale);
    $memberAreaLabel = member_area_eyebrow_label($locale);

    set_page_meta([
        'title' => (string) $t['title'],
        'description' => (string) $t['meta_desc'],
        'robots' => 'noindex,follow',
        'schema_type' => 'CollectionPage',
    ]);

    if (!ensure_webotheque_table()) {
        echo render_layout('<div class="card"><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['title']);
        return;
    }

    $categories = webotheque_categories($t);
    webotheque_sync_accepted_proposals($categories, $t, (int) ($user['id'] ?? 0));
    $proposalContact = webotheque_member_contact($user);
    $canAutoValidate = has_permission('admin.access');
    $webothequeReturnUrl = static function (): string {
        return route_url_clean('webotheque', [
            'category' => (string) ($_POST['return_category'] ?? $_GET['category'] ?? ''),
            'subcategory' => (string) ($_POST['return_subcategory'] ?? $_GET['subcategory'] ?? ''),
            'favorites' => (string) ($_POST['return_favorites'] ?? $_GET['favorites'] ?? '') === '1' ? '1' : '',
            'q' => (string) ($_POST['return_q'] ?? $_GET['q'] ?? ''),
        ]);
    };

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            verify_csrf();
            $action = (string) ($_POST['action'] ?? '');

            if ($action === 'toggle_favorite_link') {
                $linkId = (int) ($_POST['id'] ?? 0);
                if ($linkId > 0 && function_exists('favorite_toggle')) {
                    $linkStmt = db()->prepare('SELECT id, title, category, subcategory FROM member_webotheque_links WHERE id = ? LIMIT 1');
                    $linkStmt->execute([$linkId]);
                        $link = $linkStmt->fetch() ?: null;
                    if (is_array($link)) {
                        $linkTitle = trim((string) ($link['title'] ?? ''));
                        if ($linkTitle === '') {
                            $linkTitle = (string) $t['link'];
                        }
                        $favoriteUrl = route_url_clean('webotheque', [
                            'q' => $linkTitle,
                            'category' => (string) ($link['category'] ?? ''),
                            'subcategory' => (string) ($link['subcategory'] ?? ''),
                        ]);
                        $saved = favorite_toggle((int) $user['id'], 'webotheque_link', (int) $link['id'], $linkTitle, $favoriteUrl);
                        notify_member((int) $user['id'], 'favorite', $saved ? (string) $t['favorite_added'] : (string) $t['favorite_removed'], $linkTitle, $favoriteUrl);
                        set_flash('success', $saved ? (string) $t['favorite_added_msg'] : (string) $t['favorite_removed_msg']);
                    }
                }
                redirect_url($webothequeReturnUrl());
            }

            if ($action === 'update_link' || $action === 'delete_link') {
                $linkId = (int) ($_POST['id'] ?? 0);
                if ($linkId <= 0) {
                    throw new RuntimeException('err_required');
                }

                $linkStmt = db()->prepare('SELECT * FROM member_webotheque_links WHERE id = ? LIMIT 1');
                $linkStmt->execute([$linkId]);
                $link = $linkStmt->fetch() ?: null;
                if (!is_array($link)) {
                    throw new RuntimeException('link_missing');
                }
                if (!$canAutoValidate && (int) ($link['member_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
                    throw new RuntimeException('link_forbidden');
                }

                $title = content_proposal_clean_single_line((string) ($_POST['title'] ?? $link['title'] ?? ''), 190);
                $url = webotheque_normalize_url((string) ($_POST['url'] ?? $link['url'] ?? ''));
                $description = content_proposal_clean_multiline((string) ($_POST['description'] ?? $link['description'] ?? ''), 5000);
                $tags = content_proposal_clean_single_line((string) ($_POST['tags'] ?? $link['tags'] ?? ''), 255);
                $category = webotheque_category_from_input((string) ($_POST['category'] ?? $link['category'] ?? 'general'), $categories);
                $subcategory = webotheque_subcategory_code((string) ($link['subcategory'] ?? ''));
                if (array_key_exists('subcategory_ref', $_POST)) {
                    [$category, $subcategory] = webotheque_taxonomy_from_input(
                        (string) ($_POST['category'] ?? $link['category'] ?? 'general'),
                        trim((string) ($_POST['subcategory_ref'] ?? '')),
                        $categories,
                        (string) ($link['category'] ?? 'general')
                    );
                }
                if ($title === '' || $url === '') {
                    throw new RuntimeException($url === '' ? 'err_url' : 'err_required');
                }

                if ($action === 'delete_link') {
                    if ($canAutoValidate) {
                        webotheque_delete_link_record($linkId);
                        set_flash('success', (string) $t['ok_deleted']);
                        redirect_url($webothequeReturnUrl());
                    }

                    $proposalSummary = content_proposal_details_text([
                        (string) $t['proposal_action'] => 'delete_link',
                        (string) $t['proposal_link_id'] => (string) $linkId,
                        (string) $t['domain_field'] => (string) ($categories[webotheque_category_code((string) ($link['category'] ?? 'general'))] ?? ($link['category'] ?? 'general')),
                        (string) $t['subcategory_field'] => (string) ($link['subcategory'] ?? ''),
                        (string) $t['description_field'] => mb_safe_substr((string) ($link['description'] ?? ''), 0, 1800),
                        (string) $t['tags_field'] => (string) ($link['tags'] ?? ''),
                    ]);
                    $proposalId = content_proposal_create((int) $user['id'], 'webotheque', 'content', $title, $proposalSummary, $proposalContact, (string) ($link['url'] ?? ''), 'pending');
                    content_proposal_notify_site((string) $t['propose_link_subject'], [
                        'area' => 'webotheque',
                        'proposal_type' => 'content',
                        'title' => $title,
                        'summary' => $proposalSummary,
                        'contact' => $proposalContact,
                        'source_ref' => 'content_proposals#' . $proposalId . ' ' . (string) ($link['url'] ?? ''),
                    ]);
                    set_flash('success', (string) $t['link_change_recorded']);
                    redirect('my_requests');
                }

                if ($canAutoValidate) {
                    webotheque_update_link_record($linkId, $category, $title, $url, $description, $tags, $subcategory);
                    set_flash('success', (string) $t['ok_updated']);
                    redirect_url($webothequeReturnUrl());
                }

                $proposalSummary = content_proposal_details_text([
                    (string) $t['proposal_action'] => 'update_link',
                    (string) $t['proposal_link_id'] => (string) $linkId,
                    (string) $t['domain_field'] => (string) ($categories[$category] ?? $category),
                    (string) $t['subcategory_field'] => $subcategory,
                    (string) $t['description_field'] => $description,
                    (string) $t['tags_field'] => $tags,
                ]);
                $proposalId = content_proposal_create((int) $user['id'], 'webotheque', 'content', $title, $proposalSummary, $proposalContact, $url, 'pending');
                content_proposal_notify_site((string) $t['propose_link_subject'], [
                    'area' => 'webotheque',
                    'proposal_type' => 'content',
                    'title' => $title,
                    'summary' => $proposalSummary,
                    'contact' => $proposalContact,
                    'source_ref' => 'content_proposals#' . $proposalId . ' ' . $url,
                ]);
                set_flash('success', (string) $t['link_change_recorded']);
                redirect('my_requests');
            }

            if ($action === 'propose_domain' || $action === 'propose_category') {
                $proposalCategory = content_proposal_clean_single_line((string) ($_POST['proposal_domain'] ?? $_POST['proposal_category'] ?? ''), 120);
                $proposalDetails = content_proposal_clean_multiline((string) ($_POST['proposal_details'] ?? ''), 1200);
                $proposalContact = content_proposal_clean_single_line((string) ($_POST['proposal_contact'] ?? $proposalContact), 220);
                if ($proposalCategory === '') {
                    throw new RuntimeException('err_category_required');
                }

                $summary = content_proposal_details_text([
                    (string) $t['proposal_details_field'] => $proposalDetails,
                ]);
                $autoAccept = has_permission('admin.access');
                $proposalStatus = $autoAccept ? 'accepted' : 'pending';
                $proposalId = content_proposal_create((int) $user['id'], 'webotheque', 'domain', $proposalCategory, $summary, $proposalContact, '', $proposalStatus);
                if ($autoAccept) {
                    set_flash('success', (string) $t['ok_category_added']);
                    redirect_url(route_url_clean('webotheque', ['category' => content_proposal_category_code($proposalCategory, 120, 'webotheque')]));
                }

                content_proposal_notify_site((string) $t['propose_category_subject'], [
                    'area' => 'webotheque',
                    'proposal_type' => 'domain',
                    'title' => $proposalCategory,
                    'summary' => $summary,
                    'contact' => $proposalContact,
                    'source_ref' => 'content_proposals#' . $proposalId,
                ]);
                set_flash('success', (string) $t['proposal_recorded']);
                redirect('my_requests');
            }

            if ($action === 'propose_subcategory') {
                $proposalSubcategory = content_proposal_clean_single_line((string) ($_POST['proposal_subcategory'] ?? $_POST['proposal_subcategory_name'] ?? ''), 160);
                $proposalCategory = webotheque_category_from_input((string) ($_POST['proposal_parent_category'] ?? $_POST['proposal_category'] ?? 'general'), $categories);
                $proposalDetails = content_proposal_clean_multiline((string) ($_POST['proposal_details'] ?? ''), 1200);
                $proposalContact = content_proposal_clean_single_line((string) ($_POST['proposal_contact'] ?? $proposalContact), 220);
                if ($proposalSubcategory === '') {
                    throw new RuntimeException('err_subcategory_required');
                }

                $summary = content_proposal_details_text([
                    (string) $t['domain_field'] => (string) ($categories[$proposalCategory] ?? $proposalCategory),
                    (string) $t['subcategory_field'] => $proposalSubcategory,
                    (string) $t['proposal_details_field'] => $proposalDetails,
                ]);
                $autoAccept = has_permission('admin.access');
                $proposalStatus = $autoAccept ? 'accepted' : 'pending';
                $proposalId = content_proposal_create((int) $user['id'], 'webotheque', 'subcategory', $proposalSubcategory, $summary, $proposalContact, '', $proposalStatus);
                if ($autoAccept) {
                    $saved = webotheque_upsert_subcategory($categories, $proposalCategory, $proposalSubcategory);
                    set_flash('success', (string) $t['ok_subcategory_added']);
                    redirect_url(route_url_clean('webotheque', ['category' => $saved['category'], 'subcategory' => $saved['subcategory']]));
                }

                content_proposal_notify_site((string) $t['propose_subcategory_subject'], [
                    'area' => 'webotheque',
                    'proposal_type' => 'subcategory',
                    'title' => $proposalSubcategory,
                    'summary' => $summary,
                    'contact' => $proposalContact,
                    'source_ref' => 'content_proposals#' . $proposalId,
                ]);
                set_flash('success', (string) $t['proposal_recorded']);
                redirect('my_requests');
            }

            if ($action === 'propose_tag') {
                $proposalTag = content_proposal_clean_single_line((string) ($_POST['proposal_tag'] ?? ''), 80);
                $proposalDetails = content_proposal_clean_multiline((string) ($_POST['proposal_details'] ?? ''), 1200);
                $proposalContact = content_proposal_clean_single_line((string) ($_POST['proposal_contact'] ?? $proposalContact), 220);
                if ($proposalTag === '') {
                    throw new RuntimeException('err_tag_required');
                }

                $summary = content_proposal_details_text([
                    (string) $t['proposal_details_field'] => $proposalDetails,
                ]);
                $autoAccept = has_permission('admin.access');
                $proposalStatus = $autoAccept ? 'accepted' : 'pending';
                $proposalId = content_proposal_create((int) $user['id'], 'webotheque', 'tag', $proposalTag, $summary, $proposalContact, '', $proposalStatus);
                if ($autoAccept) {
                    set_flash('success', (string) $t['ok_tag_added']);
                    redirect_url(route_url('webotheque'));
                }

                content_proposal_notify_site((string) $t['propose_tag_subject'], [
                    'area' => 'webotheque',
                    'proposal_type' => 'tag',
                    'title' => $proposalTag,
                    'summary' => $summary,
                    'contact' => $proposalContact,
                    'source_ref' => 'content_proposals#' . $proposalId,
                ]);
                set_flash('success', (string) $t['proposal_recorded']);
                redirect('my_requests');
            }

            if ($action === 'propose_link') {
                ['title' => $title, 'url' => $url, 'description' => $description, 'tags' => $tags, 'category' => $category, 'subcategory' => $subcategory] = webotheque_link_form_values($_POST, $categories);
                $proposalContact = content_proposal_clean_single_line((string) ($_POST['proposal_contact'] ?? $proposalContact), 220);
                if ($title === '' || $url === '') {
                    throw new RuntimeException($url === '' ? 'err_url' : 'err_required');
                }

                $summary = webotheque_link_summary($t, (string) ($categories[$category] ?? $category), $description, $tags, $subcategory);
                $autoAccept = has_permission('admin.access');
                $proposalStatus = $autoAccept ? 'accepted' : 'pending';
                $proposalId = content_proposal_create((int) $user['id'], 'webotheque', 'content', $title, $summary, $proposalContact, $url, $proposalStatus);
                if ($autoAccept) {
                    webotheque_insert_link((int) $user['id'], $category, $title, $url, $description, $tags, $subcategory);
                    set_flash('success', (string) $t['ok_added']);
                    redirect_url(route_url_clean('webotheque', ['category' => $category, 'subcategory' => $subcategory]));
                }

                content_proposal_notify_site((string) $t['propose_link_subject'], [
                    'area' => 'webotheque',
                    'proposal_type' => 'content',
                    'title' => $title,
                    'summary' => $summary,
                    'contact' => $proposalContact,
                    'source_ref' => 'content_proposals#' . $proposalId . ' ' . $url,
                ]);
                set_flash('success', (string) $t['proposal_recorded']);
                redirect('my_requests');
            }

            throw new RuntimeException('invalid');
        } catch (Throwable $throwable) {
            $key = $throwable->getMessage();
            set_flash('error', (string) ($t[$key] ?? $key));
            redirect_url($webothequeReturnUrl());
        }
    }

    $listState = webotheque_list_state($categories, $_GET, true);
    $search = $listState['search'];
    $stats = $listState['stats'];
    $subcategoriesByCategory = $listState['subcategories_by_category'];
    $visibleCategories = $listState['visible_categories'];
    $visibleSubcategoriesByCategory = $listState['visible_subcategories_by_category'];
    $categoryFilter = $listState['category_filter'];
    $subcategoryFilter = $listState['subcategory_filter'];
    $favoriteLinkIds = webotheque_favorite_link_ids((int) ($user['id'] ?? 0));
    $favoriteLinkCount = count($favoriteLinkIds);
    $favoritesOnly = (string) ($_GET['favorites'] ?? '') === '1' && $favoriteLinkCount > 0;
    $favoritesLabel = webotheque_favorites_label($t, $locale);
    $showLinkProposalForm = (string) ($_GET['propose_link'] ?? '') === '1';
    $showCategoryProposalForm = (string) ($_GET['propose_domain'] ?? $_GET['propose_category'] ?? '') === '1';
    $showSubcategoryProposalForm = (string) ($_GET['propose_subcategory'] ?? '') === '1';
    $showTagProposalForm = (string) ($_GET['propose_tag'] ?? '') === '1';
    $links = webotheque_fetch_links($search, $categoryFilter, 80, $subcategoryFilter, $favoritesOnly ? $favoriteLinkIds : []);
    $pendingWebothequeAdminUrl = route_url_clean('admin_webotheque', ['status' => 'pending']) . '#pending-proposals';
    $pendingWebothequeAdminLabel = (string) $t['administer'];

    ob_start();
    ?>
    <div class="stack webotheque-page">
        <section class="page-hero webotheque-hero">
            <div class="webotheque-hero-copy">
                <p class="eyebrow"><?= e($memberAreaLabel) ?></p>
                <h1><?= e((string) $t['title']) ?></h1>
                <p class="help"><?= e((string) $t['intro']) ?></p>
            </div>
            <div class="webotheque-hero-side">
                <div class="webotheque-hero-stats">
                    <article><span><?= e((string) $t['links']) ?></span><strong><?= (int) ($stats['total'] ?? 0) ?></strong></article>
                    <article><span><?= e((string) $t['tags']) ?></span><strong><?= (int) ($stats['tags'] ?? 0) ?></strong></article>
                    <article><span><?= e((string) $t['domains']) ?></span><strong><?= (int) ($stats['domains'] ?? 0) ?></strong></article>
                    <article><span><?= e((string) $t['latest']) ?></span><strong><?= e(module_hero_latest_stat_date_label((string) ($stats['latest'] ?? ''), $locale)) ?></strong></article>
                </div>
                <div class="actions webotheque-hero-actions">
                    <details class="webotheque-propose-menu">
                        <summary class="button" aria-haspopup="menu"><?= e((string) $t['propose_menu']) ?></summary>
                        <div class="webotheque-propose-menu-panel" role="menu">
                            <a class="webotheque-propose-menu-item" role="menuitem" href="<?= e(route_url('webotheque', ['propose_domain' => '1'])) ?>" data-webotheque-modal-open="webotheque-domain-dialog" aria-haspopup="dialog" aria-controls="webotheque-domain-dialog"><?= e((string) $t['propose_category_item']) ?></a>
                            <a class="webotheque-propose-menu-item" role="menuitem" href="<?= e(route_url('webotheque', ['propose_subcategory' => '1'])) ?>" data-webotheque-modal-open="webotheque-subcategory-dialog" aria-haspopup="dialog" aria-controls="webotheque-subcategory-dialog"><?= e((string) $t['propose_subcategory_item']) ?></a>
                            <a class="webotheque-propose-menu-item" role="menuitem" href="<?= e(route_url('webotheque', ['propose_tag' => '1'])) ?>" data-webotheque-modal-open="webotheque-tag-dialog" aria-haspopup="dialog" aria-controls="webotheque-tag-dialog"><?= e((string) $t['propose_tag_item']) ?></a>
                            <a class="webotheque-propose-menu-item" role="menuitem" href="<?= e(route_url('webotheque', ['propose_link' => '1'])) ?>" data-webotheque-modal-open="webotheque-link-dialog" aria-haspopup="dialog" aria-controls="webotheque-link-dialog"><?= e((string) $t['propose_link_item']) ?></a>
                        </div>
                    </details>
                    <?php if ($canAutoValidate): ?>
                        <a class="button secondary" href="<?= e($pendingWebothequeAdminUrl) ?>"><?= e($pendingWebothequeAdminLabel) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <dialog class="webotheque-proposal-dialog" id="webotheque-link-dialog" aria-labelledby="webotheque-link-title"<?= $showLinkProposalForm ? ' open data-webotheque-auto-open' : '' ?>>
            <div class="webotheque-proposal-dialog-card">
                <div class="webotheque-proposal-dialog-header module-dialog-header">
                    <div>
                        <p class="eyebrow"><?= e((string) $t['title']) ?></p>
                        <h2 id="webotheque-link-title"><?= e((string) $t['propose_link']) ?></h2>
                        <p class="help"><?= e($canAutoValidate ? (string) $t['proposal_auto_accept_help'] : (string) $t['propose_link_help']) ?></p>
                    </div>
                    <button class="webotheque-proposal-dialog-close module-dialog-close" type="button" data-webotheque-modal-close aria-label="<?= e((string) $t['cancel']) ?>">&times;</button>
                </div>
                <form method="post" class="webotheque-proposal-form module-dialog-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="propose_link">
                    <?= render_webotheque_link_fields($t, $categories, $proposalContact) ?>
                    <p class="webotheque-proposal-dialog-actions module-dialog-actions">
                        <button class="button" type="submit"><?= e((string) $t['submit_proposal']) ?></button>
                        <button class="button secondary" type="button" data-webotheque-modal-close><?= e((string) $t['cancel']) ?></button>
                    </p>
                </form>
            </div>
        </dialog>

        <dialog class="webotheque-proposal-dialog" id="webotheque-domain-dialog" aria-labelledby="webotheque-domain-title"<?= $showCategoryProposalForm ? ' open data-webotheque-auto-open' : '' ?>>
            <div class="webotheque-proposal-dialog-card">
                <div class="webotheque-proposal-dialog-header module-dialog-header">
                    <div>
                        <p class="eyebrow"><?= e((string) $t['title']) ?></p>
                        <h2 id="webotheque-domain-title"><?= e((string) $t['propose_category']) ?></h2>
                        <p class="help"><?= e($canAutoValidate ? (string) $t['proposal_auto_accept_help'] : (string) $t['propose_category_help']) ?></p>
                    </div>
                    <button class="webotheque-proposal-dialog-close module-dialog-close" type="button" data-webotheque-modal-close aria-label="<?= e((string) $t['cancel']) ?>">&times;</button>
                </div>
                <form method="post" class="webotheque-proposal-form module-dialog-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="propose_domain">
                    <label><span><?= e((string) $t['category_name_field']) ?></span><input type="text" name="proposal_domain" maxlength="120" required></label>
                    <label><span><?= e((string) $t['proposal_details_field']) ?></span><textarea name="proposal_details" rows="4" maxlength="1200"></textarea></label>
                    <label><span><?= e((string) $t['contact_field']) ?></span><input type="email" name="proposal_contact" maxlength="220" value="<?= e($proposalContact) ?>"></label>
                    <p class="webotheque-proposal-dialog-actions module-dialog-actions">
                        <button class="button" type="submit"><?= e((string) $t['submit_proposal']) ?></button>
                        <button class="button secondary" type="button" data-webotheque-modal-close><?= e((string) $t['cancel']) ?></button>
                    </p>
                </form>
            </div>
        </dialog>

        <dialog class="webotheque-proposal-dialog" id="webotheque-subcategory-dialog" aria-labelledby="webotheque-subcategory-title"<?= $showSubcategoryProposalForm ? ' open data-webotheque-auto-open' : '' ?>>
            <div class="webotheque-proposal-dialog-card">
                <div class="webotheque-proposal-dialog-header module-dialog-header">
                    <div>
                        <p class="eyebrow"><?= e((string) $t['title']) ?></p>
                        <h2 id="webotheque-subcategory-title"><?= e((string) $t['propose_subcategory']) ?></h2>
                        <p class="help"><?= e($canAutoValidate ? (string) $t['proposal_auto_accept_help'] : (string) $t['propose_subcategory_help']) ?></p>
                    </div>
                    <button class="webotheque-proposal-dialog-close module-dialog-close" type="button" data-webotheque-modal-close aria-label="<?= e((string) $t['cancel']) ?>">&times;</button>
                </div>
                <form method="post" class="webotheque-proposal-form module-dialog-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="propose_subcategory">
                    <label>
                        <span><?= e((string) $t['subcategory_parent_field']) ?></span>
                        <select name="proposal_parent_category">
                            <?php foreach ($categories as $code => $label): ?>
                                <option value="<?= e((string) $code) ?>"<?= $categoryFilter === (string) $code ? ' selected' : '' ?>><?= e((string) $label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><span><?= e((string) $t['subcategory_name_field']) ?></span><input type="text" name="proposal_subcategory" maxlength="160" required></label>
                    <label><span><?= e((string) $t['proposal_details_field']) ?></span><textarea name="proposal_details" rows="4" maxlength="1200"></textarea></label>
                    <label><span><?= e((string) $t['contact_field']) ?></span><input type="email" name="proposal_contact" maxlength="220" value="<?= e($proposalContact) ?>"></label>
                    <p class="webotheque-proposal-dialog-actions module-dialog-actions">
                        <button class="button" type="submit"><?= e((string) $t['submit_proposal']) ?></button>
                        <button class="button secondary" type="button" data-webotheque-modal-close><?= e((string) $t['cancel']) ?></button>
                    </p>
                </form>
            </div>
        </dialog>

        <dialog class="webotheque-proposal-dialog" id="webotheque-tag-dialog" aria-labelledby="webotheque-tag-title"<?= $showTagProposalForm ? ' open data-webotheque-auto-open' : '' ?>>
            <div class="webotheque-proposal-dialog-card">
                <div class="webotheque-proposal-dialog-header module-dialog-header">
                    <div>
                        <p class="eyebrow"><?= e((string) $t['title']) ?></p>
                        <h2 id="webotheque-tag-title"><?= e((string) $t['propose_tag']) ?></h2>
                        <p class="help"><?= e($canAutoValidate ? (string) $t['proposal_auto_accept_help'] : (string) $t['propose_tag_help']) ?></p>
                    </div>
                    <button class="webotheque-proposal-dialog-close module-dialog-close" type="button" data-webotheque-modal-close aria-label="<?= e((string) $t['cancel']) ?>">&times;</button>
                </div>
                <form method="post" class="webotheque-proposal-form module-dialog-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="propose_tag">
                    <label><span><?= e((string) $t['tag_name_field']) ?></span><input type="text" name="proposal_tag" maxlength="80" required></label>
                    <label><span><?= e((string) $t['proposal_details_field']) ?></span><textarea name="proposal_details" rows="4" maxlength="1200"></textarea></label>
                    <label><span><?= e((string) $t['contact_field']) ?></span><input type="email" name="proposal_contact" maxlength="220" value="<?= e($proposalContact) ?>"></label>
                    <p class="webotheque-proposal-dialog-actions module-dialog-actions">
                        <button class="button" type="submit"><?= e((string) $t['submit_proposal']) ?></button>
                        <button class="button secondary" type="button" data-webotheque-modal-close><?= e((string) $t['cancel']) ?></button>
                    </p>
                </form>
            </div>
        </dialog>

        <section class="card webotheque-search-panel">
            <form method="get" class="inline-form webotheque-search-form">
                <input type="hidden" name="route" value="webotheque">
                <?php if ($categoryFilter !== ''): ?>
                    <input type="hidden" name="category" value="<?= e($categoryFilter) ?>">
                <?php endif; ?>
                <?php if ($subcategoryFilter !== ''): ?>
                    <input type="hidden" name="subcategory" value="<?= e($subcategoryFilter) ?>">
                <?php endif; ?>
                <?php if ($favoritesOnly): ?>
                    <input type="hidden" name="favorites" value="1">
                <?php endif; ?>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
                <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
                <?php if ($search !== '' || $categoryFilter !== '' || $subcategoryFilter !== '' || $favoritesOnly): ?>
                    <a class="button secondary" href="<?= e(route_url('webotheque')) ?>"><?= e((string) $t['reset']) ?></a>
                <?php endif; ?>
            </form>
        </section>

        <section class="webotheque-layout module-taxonomy-layout">
            <aside class="card webotheque-domains-column module-taxonomy-index">
                <p class="webotheque-domains-title module-taxonomy-title"><?= e((string) $t['topics']) ?></p>
                <nav class="webotheque-domains-list module-taxonomy-list" aria-label="<?= e((string) $t['topics']) ?>">
                    <?php if ($favoriteLinkCount > 0): ?>
                        <a class="webotheque-domain-item module-taxonomy-item<?= $favoritesOnly ? ' is-active' : '' ?>" href="<?= e(route_url_clean('webotheque', ['favorites' => '1', 'q' => $search])) ?>"<?= $favoritesOnly ? ' aria-current="page"' : '' ?>>
                            <span><?= e($favoritesLabel) ?></span>
                            <strong><?= (int) $favoriteLinkCount ?></strong>
                        </a>
                    <?php endif; ?>
                    <a class="webotheque-domain-item module-taxonomy-item<?= !$favoritesOnly && $categoryFilter === '' && $subcategoryFilter === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('webotheque', ['q' => $search])) ?>"<?= !$favoritesOnly && $categoryFilter === '' && $subcategoryFilter === '' ? ' aria-current="page"' : '' ?>>
                        <span><?= e((string) $t['all_categories']) ?></span>
                        <strong><?= (int) array_sum(array_map('intval', (array) ($stats['by_category'] ?? []))) ?></strong>
                    </a>
                    <?php foreach ($visibleCategories as $code => $label): ?>
                        <a class="webotheque-domain-item module-taxonomy-item<?= !$favoritesOnly && $categoryFilter === $code && $subcategoryFilter === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('webotheque', ['q' => $search, 'category' => (string) $code])) ?>"<?= !$favoritesOnly && $categoryFilter === $code && $subcategoryFilter === '' ? ' aria-current="page"' : '' ?>>
                            <span><?= e((string) $label) ?></span>
                            <strong><?= (int) ($stats['by_category'][$code] ?? 0) ?></strong>
                        </a>
                        <?php if (($visibleSubcategoriesByCategory[(string) $code] ?? []) !== []): ?>
                            <div class="module-taxonomy-children">
                                <?php foreach ($visibleSubcategoriesByCategory[(string) $code] as $subcategoryInfo): ?>
                                    <?php $subCode = webotheque_subcategory_code((string) ($subcategoryInfo['code'] ?? '')); ?>
                                    <a class="webotheque-domain-item module-taxonomy-item module-taxonomy-subitem<?= !$favoritesOnly && $categoryFilter === $code && $subcategoryFilter === $subCode ? ' is-active' : '' ?>" href="<?= e(route_url_clean('webotheque', ['q' => $search, 'category' => (string) $code, 'subcategory' => $subCode])) ?>"<?= !$favoritesOnly && $categoryFilter === $code && $subcategoryFilter === $subCode ? ' aria-current="page"' : '' ?>>
                                        <span><?= e((string) ($subcategoryInfo['label'] ?? $subCode)) ?></span>
                                        <strong><?= (int) ($subcategoryInfo['total'] ?? 0) ?></strong>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
            </aside>

            <section id="webotheque-list" class="webotheque-content module-taxonomy-content">
                <?php if ($links === []): ?>
                    <div class="card"><p><?= e((string) $t['empty']) ?><?php if ($search !== '' || $categoryFilter !== '' || $subcategoryFilter !== '' || $favoritesOnly): ?><?= e((string) $t['for_filters']) ?>.<?php endif; ?></p></div>
                <?php else: ?>
                    <div class="news-grid webotheque-grid"><?= render_webotheque_cards($links, $t, $categories, $user, $canAutoValidate, ['q' => $search, 'category' => $categoryFilter, 'subcategory' => $subcategoryFilter, 'favorites' => $favoritesOnly ? '1' : '']) ?></div>
                <?php endif; ?>
            </section>
        </section>
    </div>
    <?php
    echo render_layout((string) ob_get_clean(), (string) $t['title']);
}
}

if (!function_exists('render_admin_webotheque_page')) {
function render_admin_webotheque_page(): void
{
    require_permission('admin.access');
    $user = current_user();
    $locale = current_locale();
    $t = webotheque_i18n($locale);

    set_page_meta([
        'title' => (string) $t['admin_title'],
        'description' => (string) $t['meta_desc'],
        'robots' => 'noindex,nofollow',
    ]);

    if (!ensure_webotheque_table()) {
        echo render_layout('<div class="card"><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['admin_title']);
        return;
    }

    $categories = webotheque_default_categories($t) + webotheque_categories($t);
    webotheque_sync_accepted_proposals($categories, $t, (int) ($user['id'] ?? 0));
    $adminText = static fn(string $key): string => (string) $t[$key];
    $pendingProposalUrl = route_url_clean('admin_webotheque', ['status' => 'pending']) . '#pending-proposals';
    $webothequeAdminReturnUrl = static function (): string {
        return route_url_clean('admin_webotheque', [
            'category' => (string) ($_POST['return_category'] ?? $_GET['category'] ?? ''),
            'subcategory' => (string) ($_POST['return_subcategory'] ?? $_GET['subcategory'] ?? ''),
            'q' => (string) ($_POST['return_q'] ?? $_GET['q'] ?? ''),
        ]);
    };
    $proposalStatusLabels = [
        'pending' => $adminText('proposal_status_pending'),
        'reviewed' => $adminText('proposal_status_reviewed'),
        'accepted' => $adminText('proposal_status_accepted'),
        'rejected' => $adminText('proposal_status_rejected'),
    ];
    $proposalTypeLabels = [
        'domain' => $adminText('proposal_type_domain'),
        'category' => $adminText('proposal_type_category'),
        'subcategory' => $adminText('proposal_type_subcategory'),
        'content' => $adminText('proposal_type_content'),
        'tag' => $adminText('proposal_type_tag'),
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            verify_csrf();
            $action = (string) ($_POST['action'] ?? 'add_link');
            if ($action === 'add_category') {
                if (!webotheque_ensure_categories_table($t)) {
                    throw new RuntimeException('storage_unavailable');
                }
                $label = content_proposal_clean_single_line((string) ($_POST['category_label'] ?? ''), 160);
                $code = webotheque_category_code((string) ($_POST['category_code'] ?? $label));
                if ($label === '' || $code === '') {
                    throw new RuntimeException('err_category_required');
                }
                db()->prepare('INSERT INTO member_webotheque_categories (code, label, deleted_at) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
                    ->execute([$code, $label]);
                set_flash('success', $adminText('category_saved'));
                redirect_url(route_url_clean('admin_webotheque', ['category' => $code]));
            }
            if ($action === 'update_category') {
                if (!webotheque_ensure_categories_table($t)) {
                    throw new RuntimeException('storage_unavailable');
                }
                $category = webotheque_category_from_input((string) ($_POST['category'] ?? ''), $categories);
                $label = content_proposal_clean_single_line((string) ($_POST['category_label'] ?? ''), 160);
                if ($label === '') {
                    throw new RuntimeException('err_category_required');
                }
                db()->prepare('INSERT INTO member_webotheque_categories (code, label, deleted_at) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
                    ->execute([$category, $label]);
                set_flash('success', $adminText('category_saved'));
                redirect_url(route_url_clean('admin_webotheque', ['category' => $category]));
            }
            if ($action === 'delete_category') {
                if (!webotheque_ensure_categories_table($t) || !webotheque_ensure_subcategories_table()) {
                    throw new RuntimeException('storage_unavailable');
                }
                $category = webotheque_category_from_input((string) ($_POST['category'] ?? ''), $categories);
                if ($category === 'general' || count($categories) <= 1) {
                    throw new RuntimeException('err_category_required');
                }
                $subcategoryCountStmt = db()->prepare('SELECT COUNT(*) FROM member_webotheque_subcategories WHERE category_code = ?');
                $subcategoryCountStmt->execute([$category]);
                if ((int) $subcategoryCountStmt->fetchColumn() > 0) {
                    throw new RuntimeException('err_category_has_subcategories');
                }
                db()->prepare('UPDATE member_webotheque_links SET category = "general", subcategory = "" WHERE category = ?')->execute([$category]);
                db()->prepare('INSERT INTO member_webotheque_categories (code, label, deleted_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE deleted_at = NOW()')
                    ->execute([$category, (string) ($categories[$category] ?? webotheque_category_label_from_code($category))]);
                set_flash('success', $adminText('category_deleted'));
                redirect_url(route_url_clean('admin_webotheque'));
            }
            if ($action === 'add_subcategory') {
                if (!webotheque_ensure_subcategories_table()) {
                    throw new RuntimeException('storage_unavailable');
                }
                $category = webotheque_category_from_input((string) ($_POST['subcategory_category'] ?? 'general'), $categories);
                $label = content_proposal_clean_single_line((string) ($_POST['subcategory_label'] ?? ''), 160);
                $code = webotheque_subcategory_code((string) ($_POST['subcategory_code'] ?? $label));
                if ($label === '' || $code === '') {
                    throw new RuntimeException('err_required');
                }
                db()->prepare('INSERT INTO member_webotheque_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                    ->execute([$category, $code, $label]);
                set_flash('success', $adminText('subcategory_saved'));
                redirect_url(route_url_clean('admin_webotheque', ['category' => $category, 'subcategory' => $code]));
            }
            if ($action === 'update_subcategory') {
                if (!webotheque_ensure_subcategories_table()) {
                    throw new RuntimeException('storage_unavailable');
                }
                $parts = webotheque_subcategory_ref_parts((string) ($_POST['subcategory_ref'] ?? ''));
                $category = webotheque_category_from_input($parts['category'] !== '' ? $parts['category'] : (string) ($_POST['category'] ?? 'general'), $categories);
                $subcategory = webotheque_subcategory_code($parts['subcategory']);
                $label = content_proposal_clean_single_line((string) ($_POST['subcategory_label'] ?? ''), 160);
                if ($subcategory === '' || $label === '') {
                    throw new RuntimeException('err_required');
                }
                db()->prepare('INSERT INTO member_webotheque_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                    ->execute([$category, $subcategory, $label]);
                set_flash('success', $adminText('subcategory_saved'));
                redirect_url(route_url_clean('admin_webotheque', ['category' => $category, 'subcategory' => $subcategory]));
            }
            if ($action === 'delete_subcategory') {
                if (!webotheque_ensure_subcategories_table()) {
                    throw new RuntimeException('storage_unavailable');
                }
                $parts = webotheque_subcategory_ref_parts((string) ($_POST['subcategory_ref'] ?? ''));
                $category = webotheque_category_from_input($parts['category'] !== '' ? $parts['category'] : (string) ($_POST['category'] ?? 'general'), $categories);
                $subcategory = webotheque_subcategory_code($parts['subcategory']);
                if ($subcategory === '') {
                    throw new RuntimeException('err_required');
                }
                $countStmt = db()->prepare('SELECT COUNT(*) FROM member_webotheque_links WHERE category = ? AND subcategory = ?');
                $countStmt->execute([$category, $subcategory]);
                if ((int) $countStmt->fetchColumn() > 0) {
                    throw new RuntimeException('err_subcategory_has_documents');
                }
                db()->prepare('DELETE FROM member_webotheque_subcategories WHERE category_code = ? AND code = ?')->execute([$category, $subcategory]);
                set_flash('success', $adminText('subcategory_deleted'));
                redirect_url(route_url_clean('admin_webotheque', ['category' => $category]));
            }
            if ($action === 'update_link' || $action === 'delete_link') {
                $linkId = (int) ($_POST['id'] ?? 0);
                if ($linkId <= 0) {
                    throw new RuntimeException('err_required');
                }

                if ($action === 'delete_link') {
                    webotheque_delete_link_record($linkId);
                    set_flash('success', (string) $t['ok_deleted']);
                    redirect_url($webothequeAdminReturnUrl());
                }

                ['title' => $title, 'url' => $url, 'description' => $description, 'tags' => $tags, 'category' => $category, 'subcategory' => $subcategory] = webotheque_link_form_values($_POST, $categories);
                if ($title === '' || $url === '') {
                    throw new RuntimeException($url === '' ? 'err_url' : 'err_required');
                }

                webotheque_update_link_record($linkId, $category, $title, $url, $description, $tags, $subcategory);
                set_flash('success', (string) $t['ok_updated']);
                redirect_url($webothequeAdminReturnUrl());
            }
            if ($action === 'update_proposal_status') {
                $proposalId = (int) ($_POST['proposal_id'] ?? 0);
                $proposalStatus = (string) ($_POST['proposal_status'] ?? 'pending');
                $moderationNote = trim((string) ($_POST['moderation_note'] ?? ''));
                if ($proposalId <= 0 || !isset($proposalStatusLabels[$proposalStatus])) {
                    throw new RuntimeException('err_required');
                }
                if (!ensure_content_proposals_table()) {
                    throw new RuntimeException('storage_unavailable');
                }

                $proposalStmt = db()->prepare('SELECT id, member_id, proposal_type, title, summary, source_ref FROM content_proposals WHERE id = ? AND area = "webotheque" LIMIT 1');
                $proposalStmt->execute([$proposalId]);
                $proposal = $proposalStmt->fetch() ?: null;
                if (!is_array($proposal)) {
                    throw new RuntimeException('err_required');
                }

                if ($proposalStatus === 'accepted') {
                    webotheque_apply_accepted_proposal(
                        $proposal,
                        $categories,
                        $t,
                        (int) ($user['id'] ?? 0),
                        (string) ($_POST['proposal_category'] ?? '')
                    );
                }

                db()->prepare('UPDATE content_proposals SET status = ?, moderation_note = ? WHERE id = ? AND area = "webotheque"')
                    ->execute([$proposalStatus, $moderationNote !== '' ? $moderationNote : null, $proposalId]);
                set_flash('success', $adminText('proposal_status_saved'));
                redirect_url($pendingProposalUrl);
            }
            if ($action === 'delete_link') {
                $id = (int) ($_POST['id'] ?? 0);
                webotheque_delete_link_record($id);
                set_flash('success', (string) $t['ok_deleted']);
                redirect('admin_webotheque');
            }

            ['title' => $title, 'url' => $url, 'description' => $description, 'tags' => $tags, 'category' => $category, 'subcategory' => $subcategory] = webotheque_link_form_values($_POST, $categories);
            if ($title === '' || $url === '') {
                throw new RuntimeException($url === '' ? 'err_url' : 'err_required');
            }
            $summary = webotheque_link_summary($t, (string) ($categories[$category] ?? $category), $description, $tags, $subcategory);
            content_proposal_create((int) ($user['id'] ?? 0), 'webotheque', 'content', $title, $summary, webotheque_member_contact($user ?? []), $url, 'accepted');
            webotheque_insert_link((int) ($user['id'] ?? 0), $category, $title, $url, $description, $tags, $subcategory);
            set_flash('success', (string) $t['ok_added']);
            redirect_url(route_url_clean('admin_webotheque', ['category' => $category, 'subcategory' => $subcategory]));
        } catch (Throwable $throwable) {
            $key = $throwable->getMessage();
            set_flash('error', (string) ($t[$key] ?? $key));
            redirect('admin_webotheque');
        }
    }

    $listState = webotheque_list_state($categories, $_GET, false);
    $search = $listState['search'];
    $stats = $listState['stats'];
    $subcategoriesByCategory = $listState['subcategories_by_category'];
    $visibleCategories = $listState['visible_categories'];
    $visibleSubcategoriesByCategory = $listState['visible_subcategories_by_category'];
    $categoryFilter = $listState['category_filter'];
    $subcategoryFilter = $listState['subcategory_filter'];
    $showAdminLinkProposalForm = (string) ($_GET['propose_link'] ?? '') === '1';
    $links = webotheque_fetch_links($search, $categoryFilter, 120, $subcategoryFilter);
    $showPendingProposals = (string) ($_GET['status'] ?? '') === 'pending';
    $pendingProposals = [];
    if ($showPendingProposals && ensure_content_proposals_table()) {
        $pendingStmt = db()->prepare(
            'SELECT cp.id, cp.member_id, cp.proposal_type, cp.title, cp.summary, cp.contact, cp.source_ref, cp.status, cp.moderation_note, cp.created_at, cp.updated_at, m.callsign, m.email
             FROM content_proposals cp
             LEFT JOIN members m ON m.id = cp.member_id
             WHERE cp.area = "webotheque" AND cp.status = "pending"
             ORDER BY cp.created_at ASC, cp.id ASC'
        );
        $pendingStmt->execute();
        $pendingProposals = $pendingStmt->fetchAll() ?: [];
    }

    ob_start();
    ?>
    <div class="stack admin-webotheque-page">
        <section class="page-hero admin-webotheque-hero">
            <div class="admin-webotheque-hero-copy">
                <p class="eyebrow"><?= e((string) $t['administration']) ?></p>
                <h1><?= e((string) $t['title']) ?></h1>
                <p class="help"><?= e((string) $t['intro']) ?></p>
            </div>
            <div class="admin-webotheque-hero-side">
                <div class="webotheque-hero-stats">
                    <article><span><?= e((string) $t['links']) ?></span><strong><?= (int) ($stats['total'] ?? 0) ?></strong></article>
                    <article><span><?= e((string) $t['tags']) ?></span><strong><?= (int) ($stats['tags'] ?? 0) ?></strong></article>
                    <article><span><?= e((string) $t['domains']) ?></span><strong><?= (int) ($stats['domains'] ?? 0) ?></strong></article>
                </div>
                <p class="actions admin-webotheque-hero-actions">
                    <a class="button secondary" href="<?= e(route_url('webotheque')) ?>"><?= e((string) $t['view_links']) ?></a>
                    <a class="button" href="<?= e(route_url('admin_webotheque', ['propose_link' => '1'])) ?>" data-webotheque-modal-open="admin-webotheque-link-dialog" aria-haspopup="dialog" aria-controls="admin-webotheque-link-dialog"><?= e((string) $t['propose_link']) ?></a>
                </p>
            </div>
        </section>

        <?php if ($showPendingProposals): ?>
        <section class="admin-webotheque-list" id="pending-proposals" aria-labelledby="pending-proposals-title">
            <div class="row-between">
                <h2 id="pending-proposals-title"><?= e($adminText('pending_proposals_title')) ?></h2>
                <a class="button secondary" href="<?= e(route_url('admin_webotheque')) ?>"><?= e((string) $t['reset']) ?></a>
            </div>
            <?php if ($pendingProposals === []): ?>
                <div class="card"><p><?= e($adminText('pending_proposals_empty')) ?></p></div>
            <?php endif; ?>
            <?php foreach ($pendingProposals as $proposal): ?>
                <?php
                $proposalType = (string) ($proposal['proposal_type'] ?? 'content');
                $proposalStatus = (string) ($proposal['status'] ?? 'pending');
                $sourceUrl = webotheque_proposal_source_url((string) ($proposal['source_ref'] ?? ''));
                $memberLabel = trim((string) ($proposal['callsign'] ?? ''));
                if ($memberLabel === '') {
                    $memberLabel = trim((string) ($proposal['email'] ?? ''));
                }
                if ($memberLabel === '') {
                    $memberLabel = '#' . (int) ($proposal['member_id'] ?? 0);
                }
                $proposalCreatedTimestamp = strtotime((string) ($proposal['created_at'] ?? 'now'));
                if ($proposalCreatedTimestamp === false) {
                    $proposalCreatedTimestamp = time();
                }
                $proposalCategory = webotheque_proposal_category_from_summary((string) ($proposal['summary'] ?? ''), $categories);
                ?>
                <article class="news-card feature-card webotheque-card">
                    <p>
                        <span class="badge muted"><?= e((string) ($proposalTypeLabels[$proposalType] ?? $proposalType)) ?></span>
                        <span class="badge muted"><?= e((string) ($proposalStatusLabels[$proposalStatus] ?? $proposalStatus)) ?></span>
                        <span class="badge muted"><?= e(date('d/m/Y H:i', $proposalCreatedTimestamp)) ?></span>
                    </p>
                    <h3><?= e((string) ($proposal['title'] ?? $adminText('proposal_default_title'))) ?></h3>
                    <p class="help"><?= e($adminText('proposal_author')) ?>: <?= e($memberLabel) ?></p>
                    <?php if (trim((string) ($proposal['summary'] ?? '')) !== ''): ?>
                        <p><?= nl2br(e((string) $proposal['summary'])) ?></p>
                    <?php endif; ?>
                    <?php if (trim((string) ($proposal['contact'] ?? '')) !== ''): ?>
                        <p class="help"><?= e($adminText('proposal_contact')) ?>: <?= e((string) $proposal['contact']) ?></p>
                    <?php endif; ?>
                    <form method="post" class="webotheque-proposal-form module-dialog-form">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_proposal_status">
                        <input type="hidden" name="proposal_id" value="<?= (int) ($proposal['id'] ?? 0) ?>">
                        <label>
                            <span><?= e($adminText('proposal_status_label')) ?></span>
                            <select name="proposal_status">
                                <?php foreach ($proposalStatusLabels as $statusCode => $statusLabel): ?>
                                    <option value="<?= e($statusCode) ?>"<?= $proposalStatus === $statusCode ? ' selected' : '' ?>><?= e($statusLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <?php if ($proposalType === 'content'): ?>
                            <label>
                                <span><?= e((string) $t['domain_field']) ?></span>
                                <select name="proposal_category">
                                    <?php foreach ($categories as $code => $label): ?>
                                        <option value="<?= e((string) $code) ?>"<?= $proposalCategory === $code ? ' selected' : '' ?>><?= e((string) $label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        <?php endif; ?>
                        <label>
                            <span><?= e($adminText('proposal_moderation_note')) ?></span>
                            <textarea name="moderation_note" rows="3"><?= e((string) ($proposal['moderation_note'] ?? '')) ?></textarea>
                        </label>
                        <p class="actions">
                            <?php if ($sourceUrl !== ''): ?>
                                <a class="button secondary" href="<?= e($sourceUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e($adminText('proposal_open_source')) ?></a>
                            <?php endif; ?>
                            <button class="button" type="submit"><?= e($adminText('proposal_save_status')) ?></button>
                        </p>
                    </form>
                </article>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>

        <dialog class="webotheque-proposal-dialog" id="admin-webotheque-link-dialog" aria-labelledby="admin-webotheque-link-title"<?= $showAdminLinkProposalForm ? ' open data-webotheque-auto-open' : '' ?>>
            <div class="webotheque-proposal-dialog-card">
                <div class="webotheque-proposal-dialog-header module-dialog-header">
                    <div>
                        <p class="eyebrow"><?= e((string) $t['administration']) ?></p>
                        <h2 id="admin-webotheque-link-title"><?= e((string) $t['propose_link']) ?></h2>
                        <p class="help"><?= e((string) $t['proposal_auto_accept_help']) ?></p>
                    </div>
                    <button class="webotheque-proposal-dialog-close module-dialog-close" type="button" data-webotheque-modal-close aria-label="<?= e((string) $t['cancel']) ?>">&times;</button>
                </div>
                <form method="post" class="webotheque-proposal-form module-dialog-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="add_link">
                    <?= render_webotheque_link_fields($t, $categories) ?>
                    <p class="webotheque-proposal-dialog-actions module-dialog-actions">
                        <button class="button" type="submit"><?= e((string) $t['save']) ?></button>
                        <button class="button secondary" type="button" data-webotheque-modal-close><?= e((string) $t['cancel']) ?></button>
                    </p>
                </form>
            </div>
        </dialog>

        <section class="card webotheque-search-panel">
            <form method="get" class="inline-form webotheque-search-form">
                <input type="hidden" name="route" value="admin_webotheque">
                <?php if ($categoryFilter !== ''): ?>
                    <input type="hidden" name="category" value="<?= e($categoryFilter) ?>">
                <?php endif; ?>
                <?php if ($subcategoryFilter !== ''): ?>
                    <input type="hidden" name="subcategory" value="<?= e($subcategoryFilter) ?>">
                <?php endif; ?>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
                <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
                <?php if ($search !== '' || $categoryFilter !== '' || $subcategoryFilter !== ''): ?>
                    <a class="button secondary" href="<?= e(route_url('admin_webotheque')) ?>"><?= e((string) $t['reset']) ?></a>
                <?php endif; ?>
            </form>
        </section>

        <?php if (count($categories) > 1): ?>
            <nav class="classifieds-category-strip webotheque-category-filter" aria-label="<?= e((string) $t['domain_field']) ?>">
                <a class="classifieds-category-pill<?= $categoryFilter === '' && $subcategoryFilter === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('admin_webotheque', ['q' => $search])) ?>"><?= e((string) $t['all_categories']) ?></a>
                <?php foreach ($visibleCategories as $code => $label): ?>
                    <a class="classifieds-category-pill<?= $categoryFilter === $code && $subcategoryFilter === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('admin_webotheque', ['q' => $search, 'category' => (string) $code])) ?>"><?= e((string) $label) ?></a>
                    <?php foreach (($visibleSubcategoriesByCategory[(string) $code] ?? []) as $subcategoryInfo): ?>
                        <?php $subCode = webotheque_subcategory_code((string) $subcategoryInfo['code']); ?>
                        <a class="classifieds-category-pill<?= $categoryFilter === $code && $subcategoryFilter === $subCode ? ' is-active' : '' ?>" href="<?= e(route_url_clean('admin_webotheque', ['q' => $search, 'category' => (string) $code, 'subcategory' => $subCode])) ?>"><?= e((string) ($subcategoryInfo['label'] ?? $subCode)) ?></a>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <section class="card admin-webotheque-taxonomy">
            <h2><?= e($adminText('taxonomy_title')) ?></h2>
            <form method="post" class="inline-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="add_category">
                <label><span><?= e((string) $t['domain_field']) ?></span><input type="text" name="category_label" maxlength="160" required></label>
                <input type="hidden" name="category_code" value="">
                <button class="button" type="submit"><?= e($adminText('add_category')) ?></button>
            </form>
            <?php if ($categories !== []): ?>
                <div class="tags-cloud">
                    <?php foreach ($categories as $code => $label): ?>
                        <?php
                        $categoryTotal = (int) (($stats['by_category'][(string) $code] ?? 0));
                        $categorySubcategoryTotal = count($subcategoriesByCategory[(string) $code] ?? []);
                        $categoryDeleteDisabled = (string) $code === 'general' || $categorySubcategoryTotal > 0 || count($categories) <= 1;
                        ?>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="update_category">
                            <input type="hidden" name="category" value="<?= e((string) $code) ?>">
                            <span class="pill"><?= e((string) $code) ?> (<?= $categoryTotal ?>)</span>
                            <input type="text" name="category_label" value="<?= e((string) $label) ?>" maxlength="160" required>
                            <button class="button small" type="submit"><?= e((string) $t['save']) ?></button>
                            <button class="button secondary small" type="submit" name="action" value="delete_category"<?= $categoryDeleteDisabled ? ' disabled' : '' ?>><?= e((string) $t['delete']) ?></button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="post" class="inline-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="add_subcategory">
                <label><span><?= e((string) $t['domain_field']) ?></span>
                    <select name="subcategory_category">
                        <?php foreach ($categories as $code => $label): ?>
                            <option value="<?= e((string) $code) ?>"<?= $categoryFilter === (string) $code ? ' selected' : '' ?>><?= e((string) $label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span><?= e((string) $t['subcategory_field']) ?></span><input type="text" name="subcategory_label" maxlength="160" required></label>
                <input type="hidden" name="subcategory_code" value="">
                <button class="button" type="submit"><?= e($adminText('add_subcategory')) ?></button>
            </form>
            <?php if ($subcategoriesByCategory === []): ?>
                <p class="help"><?= e($adminText('no_subcategories')) ?></p>
            <?php else: ?>
                <div class="tags-cloud">
                    <?php foreach ($subcategoriesByCategory as $parentCode => $subcategories): ?>
                        <?php foreach ($subcategories as $subcategoryInfo): ?>
                            <?php
                            $subCode = webotheque_subcategory_code((string) $subcategoryInfo['code']);
                            if ($subCode === '') {
                                continue;
                            }
                            $subTotal = (int) (($stats['by_subcategory'][(string) $parentCode . ':' . $subCode] ?? 0));
                            ?>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="update_subcategory">
                                <input type="hidden" name="subcategory_ref" value="<?= e(webotheque_subcategory_ref((string) $parentCode, $subCode)) ?>">
                                <span class="pill"><?= e((string) ($categories[(string) $parentCode] ?? $parentCode)) ?> / <?= e($subCode) ?> (<?= $subTotal ?>)</span>
                                <input type="text" name="subcategory_label" value="<?= e((string) $subcategoryInfo['label']) ?>" maxlength="160" required>
                                <button class="button small" type="submit"><?= e((string) $t['save']) ?></button>
                                <button class="button secondary small" type="submit" name="action" value="delete_subcategory"<?= $subTotal > 0 ? ' disabled' : '' ?>><?= e((string) $t['delete']) ?></button>
                            </form>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="admin-webotheque-list">
            <h2><?= e((string) $t['content_list']) ?></h2>
            <?php if ($links === []): ?>
                <div class="card"><p><?= e((string) $t['empty']) ?></p></div>
            <?php else: ?>
                <div class="news-grid webotheque-grid"><?= render_webotheque_cards($links, $t, $categories, $user, true, ['q' => $search, 'category' => $categoryFilter, 'subcategory' => $subcategoryFilter]) ?></div>
            <?php endif; ?>
        </section>
    </div>
    <?php
    echo render_layout((string) ob_get_clean(), (string) $t['admin_title']);
}
}
