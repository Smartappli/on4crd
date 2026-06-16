<?php
declare(strict_types=1);

if (!function_exists('ensure_classified_ads_table')) {
function ensure_classified_ads_table(): bool
{
    try {
        db()->exec(
            'CREATE TABLE IF NOT EXISTS classified_ads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                owner_member_id INT NOT NULL,
                category_code VARCHAR(32) NOT NULL DEFAULT "gear",
                title VARCHAR(190) NOT NULL,
                description TEXT DEFAULT NULL,
                location VARCHAR(120) DEFAULT NULL,
                contact VARCHAR(190) DEFAULT NULL,
                price_cents INT NOT NULL DEFAULT 0,
                status ENUM("draft","pending","active","sold","archived","expired") NOT NULL DEFAULT "draft",
                expires_at DATETIME NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_classified_owner_status (owner_member_id, status),
                INDEX idx_classified_status_created (status, created_at),
                INDEX idx_classified_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        if (!table_has_column('classified_ads', 'owner_member_id')) {
            db()->exec('ALTER TABLE classified_ads ADD COLUMN owner_member_id INT NOT NULL DEFAULT 0 AFTER id');
        }
        if (!table_has_column('classified_ads', 'category_code')) {
            db()->exec('ALTER TABLE classified_ads ADD COLUMN category_code VARCHAR(32) NOT NULL DEFAULT "gear" AFTER owner_member_id');
        }
        if (!table_has_column('classified_ads', 'title')) {
            db()->exec('ALTER TABLE classified_ads ADD COLUMN title VARCHAR(190) NOT NULL DEFAULT "Classified ad" AFTER category_code');
        }
        if (!table_has_column('classified_ads', 'description')) {
            db()->exec('ALTER TABLE classified_ads ADD COLUMN description TEXT DEFAULT NULL AFTER title');
        }
        if (!table_has_column('classified_ads', 'location')) {
            db()->exec('ALTER TABLE classified_ads ADD COLUMN location VARCHAR(120) DEFAULT NULL AFTER description');
        }
        if (!table_has_column('classified_ads', 'contact')) {
            db()->exec('ALTER TABLE classified_ads ADD COLUMN contact VARCHAR(190) DEFAULT NULL AFTER location');
        }
        if (!table_has_column('classified_ads', 'price_cents')) {
            db()->exec('ALTER TABLE classified_ads ADD COLUMN price_cents INT NOT NULL DEFAULT 0 AFTER contact');
        }
        if (!table_has_column('classified_ads', 'status')) {
            db()->exec('ALTER TABLE classified_ads ADD COLUMN status ENUM("draft","pending","active","sold","archived","expired") NOT NULL DEFAULT "draft" AFTER price_cents');
        }
        if (!table_has_column('classified_ads', 'expires_at')) {
            db()->exec('ALTER TABLE classified_ads ADD COLUMN expires_at DATETIME NULL DEFAULT NULL AFTER status');
        }
        if (!table_has_column('classified_ads', 'created_at')) {
            db()->exec('ALTER TABLE classified_ads ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER expires_at');
        }
        if (!table_has_column('classified_ads', 'updated_at')) {
            db()->exec('ALTER TABLE classified_ads ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
        }

        db()->exec('UPDATE classified_ads SET category_code = "gear" WHERE category_code IS NULL OR category_code = ""');
        db()->exec('UPDATE classified_ads SET title = "Classified ad" WHERE title IS NULL OR title = ""');
        db()->exec('UPDATE classified_ads SET price_cents = 0 WHERE price_cents IS NULL OR price_cents < 0');

        if (classifieds_column_requires_modify('status', 'enum("draft","pending","active","sold","archived","expired")', false, 'draft')) {
            db()->exec('ALTER TABLE classified_ads MODIFY COLUMN status ENUM("draft","pending","active","sold","archived","expired") NOT NULL DEFAULT "draft"');
        }
        if (classifieds_column_requires_modify('category_code', 'varchar(32)', false, 'gear')) {
            db()->exec('ALTER TABLE classified_ads MODIFY COLUMN category_code VARCHAR(32) NOT NULL DEFAULT "gear"');
        }
        if (classifieds_column_requires_modify('title', 'varchar(190)', false, null)) {
            db()->exec('ALTER TABLE classified_ads MODIFY COLUMN title VARCHAR(190) NOT NULL');
        }
        if (classifieds_column_requires_modify('location', 'varchar(120)', true, null)) {
            db()->exec('ALTER TABLE classified_ads MODIFY COLUMN location VARCHAR(120) DEFAULT NULL');
        }
        if (classifieds_column_requires_modify('contact', 'varchar(190)', true, null)) {
            db()->exec('ALTER TABLE classified_ads MODIFY COLUMN contact VARCHAR(190) DEFAULT NULL');
        }
        if (classifieds_column_requires_modify('price_cents', 'int', false, '0')) {
            db()->exec('ALTER TABLE classified_ads MODIFY COLUMN price_cents INT NOT NULL DEFAULT 0');
        }

        if (!table_has_index('classified_ads', 'idx_classified_owner_status')) {
            db()->exec('ALTER TABLE classified_ads ADD INDEX idx_classified_owner_status (owner_member_id, status)');
        }
        if (!table_has_index('classified_ads', 'idx_classified_status_created')) {
            db()->exec('ALTER TABLE classified_ads ADD INDEX idx_classified_status_created (status, created_at)');
        }
        if (!table_has_index('classified_ads', 'idx_classified_expires')) {
            db()->exec('ALTER TABLE classified_ads ADD INDEX idx_classified_expires (expires_at)');
        }

        return true;
    } catch (Throwable $throwable) {
        log_structured_event('classified_ads_table_ensure_failed', [
            'message' => $throwable->getMessage(),
        ]);

        return false;
    }
}
}

if (!function_exists('classifieds_column_metadata')) {
/**
 * @return array{type:string,nullable:bool,default:?string}|null
 */
function classifieds_column_metadata(string $column): ?array
{
    try {
        $stmt = db()->prepare(
            'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
             FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
             LIMIT 1'
        );
        $stmt->execute(['classified_ads', $column]);
        $row = $stmt->fetch();
    } catch (Throwable) {
        return null;
    }

    if (!is_array($row)) {
        return null;
    }

    return [
        'type' => strtolower(str_replace("'", '"', (string) ($row['COLUMN_TYPE'] ?? ''))),
        'nullable' => strtoupper((string) ($row['IS_NULLABLE'] ?? 'YES')) === 'YES',
        'default' => array_key_exists('COLUMN_DEFAULT', $row) && $row['COLUMN_DEFAULT'] !== null ? (string) $row['COLUMN_DEFAULT'] : null,
    ];
}
}

if (!function_exists('classifieds_column_requires_modify')) {
function classifieds_column_requires_modify(string $column, string $expectedType, bool $expectedNullable, ?string $expectedDefault): bool
{
    $metadata = classifieds_column_metadata($column);
    if ($metadata === null) {
        return false;
    }

    $actualType = $metadata['type'];
    $expectedType = strtolower(str_replace("'", '"', $expectedType));
    $typeMatches = $expectedType === 'int'
        ? str_starts_with($actualType, 'int')
        : $actualType === $expectedType;

    return !$typeMatches
        || $metadata['nullable'] !== $expectedNullable
        || $metadata['default'] !== $expectedDefault;
}
}

if (!function_exists('ensure_wiki_tables')) {
function ensure_wiki_tables(): bool
{
    try {
        db()->exec(
            'CREATE TABLE IF NOT EXISTS wiki_pages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(190) NOT NULL UNIQUE,
                title VARCHAR(190) NOT NULL,
                content LONGTEXT NOT NULL,
                category VARCHAR(120) NOT NULL DEFAULT "general",
                author_id INT DEFAULT NULL,
                status ENUM("pending","published","rejected") NOT NULL DEFAULT "published",
                proposal_kind VARCHAR(32) NOT NULL DEFAULT "page",
                source_page_id INT DEFAULT NULL,
                target_slug VARCHAR(190) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_wiki_category (category),
                INDEX idx_wiki_status_updated (status, updated_at),
                INDEX idx_wiki_updated (updated_at),
                INDEX idx_wiki_proposal_kind (proposal_kind, status),
                INDEX idx_wiki_source_page (source_page_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        if (!table_has_column('wiki_pages', 'status')) {
            db()->exec('ALTER TABLE wiki_pages ADD COLUMN status ENUM("pending","published","rejected") NOT NULL DEFAULT "published" AFTER author_id');
        } else {
            db()->exec('ALTER TABLE wiki_pages MODIFY COLUMN status ENUM("pending","published","rejected") NOT NULL DEFAULT "published"');
        }
        $addedCategory = false;
        if (!table_has_column('wiki_pages', 'category')) {
            db()->exec('ALTER TABLE wiki_pages ADD COLUMN category VARCHAR(120) NOT NULL DEFAULT "general" AFTER content');
            $addedCategory = true;
        }
        if (!table_has_column('wiki_pages', 'created_at')) {
            db()->exec('ALTER TABLE wiki_pages ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status');
        }
        if (!table_has_column('wiki_pages', 'updated_at')) {
            db()->exec('ALTER TABLE wiki_pages ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
        }
        if (!table_has_column('wiki_pages', 'proposal_kind')) {
            db()->exec('ALTER TABLE wiki_pages ADD COLUMN proposal_kind VARCHAR(32) NOT NULL DEFAULT "page" AFTER status');
        }
        if (!table_has_column('wiki_pages', 'source_page_id')) {
            db()->exec('ALTER TABLE wiki_pages ADD COLUMN source_page_id INT DEFAULT NULL AFTER proposal_kind');
        }
        if (!table_has_column('wiki_pages', 'target_slug')) {
            db()->exec('ALTER TABLE wiki_pages ADD COLUMN target_slug VARCHAR(190) DEFAULT NULL AFTER source_page_id');
        }
        db()->exec('UPDATE wiki_pages SET category = "general" WHERE category IS NULL OR category = ""');
        if ($addedCategory) {
            db()->exec('UPDATE wiki_pages SET category = LEFT(SUBSTRING_INDEX(slug, "-", 1), 120) WHERE slug IS NOT NULL AND slug <> "" AND slug <> "n-a"');
        }
        db()->exec('UPDATE wiki_pages SET proposal_kind = "page" WHERE proposal_kind IS NULL OR proposal_kind = ""');
        if (!table_has_index('wiki_pages', 'idx_wiki_category')) {
            db()->exec('ALTER TABLE wiki_pages ADD INDEX idx_wiki_category (category)');
        }
        if (!table_has_index('wiki_pages', 'idx_wiki_status_updated')) {
            db()->exec('ALTER TABLE wiki_pages ADD INDEX idx_wiki_status_updated (status, updated_at)');
        }
        if (!table_has_index('wiki_pages', 'idx_wiki_updated')) {
            db()->exec('ALTER TABLE wiki_pages ADD INDEX idx_wiki_updated (updated_at)');
        }
        if (!table_has_index('wiki_pages', 'idx_wiki_proposal_kind')) {
            db()->exec('ALTER TABLE wiki_pages ADD INDEX idx_wiki_proposal_kind (proposal_kind, status)');
        }
        if (!table_has_index('wiki_pages', 'idx_wiki_source_page')) {
            db()->exec('ALTER TABLE wiki_pages ADD INDEX idx_wiki_source_page (source_page_id)');
        }

        db()->exec(
            'CREATE TABLE IF NOT EXISTS wiki_revisions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                wiki_page_id INT NOT NULL,
                member_id INT DEFAULT NULL,
                content LONGTEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_wiki_revision_page (wiki_page_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        if (!table_has_column('wiki_revisions', 'created_at')) {
            db()->exec('ALTER TABLE wiki_revisions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        }
        if (!table_has_index('wiki_revisions', 'idx_wiki_revision_page')) {
            db()->exec('ALTER TABLE wiki_revisions ADD INDEX idx_wiki_revision_page (wiki_page_id, created_at)');
        }

        return true;
    } catch (Throwable $throwable) {
        log_structured_event('wiki_tables_ensure_failed', [
            'message' => $throwable->getMessage(),
        ]);

        return false;
    }
}
}

if (!function_exists('wiki_public_page_where_sql')) {
function wiki_public_page_where_sql(string $alias = ''): string
{
    $prefix = '';
    if ($alias !== '' && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $alias) === 1) {
        $prefix = $alias . '.';
    }

    $where = $prefix . 'status = "published"';
    if (table_has_column('wiki_pages', 'proposal_kind')) {
        $where .= ' AND ' . $prefix . 'proposal_kind <> "modification"';
    }

    return $where;
}
}

if (!function_exists('wiki_slug_base')) {
function wiki_slug_base(string $title, string $slugInput = '', int $maxLength = 190): string
{
    $maxLength = max(1, $maxLength);
    $base = slugify($slugInput !== '' ? $slugInput : $title);
    if ($base === '' || $base === 'n-a') {
        $base = 'wiki';
    }

    if (strlen($base) > $maxLength) {
        $base = substr($base, 0, $maxLength);
    }

    $base = trim($base, '-');
    return $base !== '' ? $base : 'wiki';
}
}

if (!function_exists('wiki_slug_candidate')) {
function wiki_slug_candidate(string $base, int $suffix = 0, int $maxLength = 190): string
{
    $maxLength = max(1, $maxLength);
    $base = wiki_slug_base($base, '', $maxLength);
    if ($suffix <= 1) {
        return $base;
    }

    $suffixText = '-' . $suffix;
    $prefixLength = max(1, $maxLength - strlen($suffixText));
    $prefix = rtrim(substr($base, 0, $prefixLength), '-');
    if ($prefix === '') {
        $prefix = substr('wiki', 0, $prefixLength);
    }

    return $prefix . $suffixText;
}
}

if (!function_exists('wiki_unique_slug')) {
function wiki_unique_slug(string $title, string $slugInput = '', int $ignoreId = 0, int $maxLength = 190): string
{
    $base = wiki_slug_base($title, $slugInput, $maxLength);
    $suffix = 1;

    do {
        $candidate = wiki_slug_candidate($base, $suffix, $maxLength);
        $stmt = db()->prepare('SELECT id FROM wiki_pages WHERE slug = ? AND id <> ? LIMIT 1');
        $stmt->execute([$candidate, max(0, $ignoreId)]);
        if (!$stmt->fetchColumn()) {
            return $candidate;
        }
        $suffix++;
    } while ($suffix < 10000);

    throw new RuntimeException('Impossible de générer un slug wiki unique.');
}
}

if (!function_exists('wiki_category_code')) {
function wiki_category_code(string $value): string
{
    return content_proposal_category_code($value, 120, 'general');
}
}

if (!function_exists('wiki_category_label_from_code')) {
function wiki_category_label_from_code(string $code): string
{
    $label = trim(str_replace('-', ' ', wiki_category_code($code)));
    if ($label === '') {
        return 'General';
    }

    return ucwords($label);
}
}

if (!function_exists('wiki_categories')) {
/**
 * @param array<string, string> $messages
 * @return array<string, string>
 */
function wiki_categories(array $messages = []): array
{
    $categories = [
        'general' => (string) ($messages['category_general'] ?? 'General'),
    ];

    if (function_exists('member_library_default_categories')) {
        foreach (member_library_default_categories() as $category) {
            $code = wiki_category_code((string) ($category['code'] ?? ''));
            $label = content_proposal_clean_single_line((string) ($category['label'] ?? ''), 190);
            if ($code !== '' && $label !== '' && !isset($categories[$code])) {
                $categories[$code] = $label;
            }
        }
    }

    try {
        if (
            function_exists('member_library_ensure_categories_table')
            && member_library_ensure_categories_table()
            && table_exists('member_library_categories')
        ) {
            $rows = db()->query('SELECT code, label FROM member_library_categories ORDER BY sort_order ASC, label ASC')->fetchAll() ?: [];
            foreach ($rows as $row) {
                $code = wiki_category_code((string) ($row['code'] ?? ''));
                $label = content_proposal_clean_single_line((string) ($row['label'] ?? ''), 190);
                if ($code !== '' && $label !== '' && !isset($categories[$code])) {
                    $categories[$code] = $label;
                }
            }
        }
    } catch (Throwable) {
        // Keep wiki-owned categories if the member library category table cannot be read.
    }

    try {
        if (table_exists('wiki_pages') && table_has_column('wiki_pages', 'category')) {
            $rows = db()->query('SELECT category FROM wiki_pages WHERE category IS NOT NULL AND category <> "" GROUP BY category ORDER BY category ASC')->fetchAll() ?: [];
            foreach ($rows as $row) {
                $code = wiki_category_code((string) ($row['category'] ?? ''));
                if ($code !== '' && !isset($categories[$code])) {
                    $categories[$code] = wiki_category_label_from_code($code);
                }
            }
        }
    } catch (Throwable) {
        // Keep the default category if optional category metadata cannot be read.
    }

    foreach (content_proposal_accepted_categories('wiki', 120) as $code => $label) {
        $code = wiki_category_code((string) $code);
        $label = content_proposal_clean_single_line((string) $label, 190);
        if ($code !== '' && $label !== '') {
            $categories[$code] = $label;
        }
    }

    foreach (content_proposal_accepted_categories('members_library', 120) as $code => $label) {
        $code = wiki_category_code((string) $code);
        $label = content_proposal_clean_single_line((string) $label, 190);
        if ($code !== '' && $label !== '' && !isset($categories[$code])) {
            $categories[$code] = $label;
        }
    }

    return $categories;
}
}

if (!function_exists('wiki_category_from_input')) {
/**
 * @param array<string, string> $categories
 */
function wiki_category_from_input(string $value, array $categories): string
{
    $code = wiki_category_code($value);
    if ($code === '') {
        $code = 'general';
    }
    if (!isset($categories[$code])) {
        throw new RuntimeException('Invalid wiki theme.');
    }

    return $code;
}
}

if (!function_exists('classifieds_active_where_sql')) {
function classifieds_active_where_sql(string $alias = ''): string
{
    $prefix = '';
    if ($alias !== '' && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $alias) === 1) {
        $prefix = $alias . '.';
    }

    return $prefix . 'status = "active" AND (' . $prefix . 'expires_at IS NULL OR ' . $prefix . 'expires_at >= NOW())';
}
}

if (!function_exists('classifieds_expires_at_for_status')) {
function classifieds_expires_at_for_status(string $status, ?int $now = null): ?string
{
    if ($status !== 'active') {
        return null;
    }

    return date('Y-m-d H:i:s', ($now ?? time()) + (30 * 86400));
}
}

if (!function_exists('classifieds_can_moderate')) {
function classifieds_can_moderate(): bool
{
    return has_permission('classifieds.moderate');
}
}

if (!function_exists('classifieds_validate_payload')) {
function classifieds_validate_payload(
    string $category,
    string $title,
    string $description,
    string $location,
    string $contact,
    array $categories,
    string $message
): void {
    if ($title === '' || $description === '' || $contact === '' || !isset($categories[$category])) {
        throw new RuntimeException($message);
    }
    if (
        mb_strlen($title) > 190
        || mb_strlen($description) > 10000
        || mb_strlen($location) > 120
        || mb_strlen($contact) > 190
    ) {
        throw new RuntimeException($message);
    }
}
}

if (!function_exists('classifieds_member_ad_exists')) {
function classifieds_member_ad_exists(int $adId, int $memberId): bool
{
    if ($adId <= 0 || $memberId <= 0) {
        return false;
    }

    $stmt = db()->prepare('SELECT id FROM classified_ads WHERE id = ? AND owner_member_id = ? LIMIT 1');
    $stmt->execute([$adId, $memberId]);

    return (bool) $stmt->fetchColumn();
}
}

if (!function_exists('classifieds_member_publication_status')) {
function classifieds_member_publication_status(string $requestedStatus): string
{
    if (!in_array($requestedStatus, ['draft', 'pending', 'active', 'sold', 'archived'], true)) {
        return 'draft';
    }
    if ($requestedStatus === 'active' && !classifieds_can_moderate()) {
        return 'pending';
    }

    return $requestedStatus;
}
}

if (!function_exists('classifieds_enforce_submission_limits')) {
function classifieds_enforce_submission_limits(int $memberId, array $messages = []): void
{
    $messages = array_replace([
        'invalid_user' => 'Invalid user.',
        'rate_limited' => 'Please wait one minute before submitting another ad.',
        'daily_limit' => 'Limit reached: maximum 5 ads per 24 hours.',
    ], $messages);

    if ($memberId <= 0) {
        throw new RuntimeException((string) $messages['invalid_user']);
    }

    $lastStmt = db()->prepare('SELECT created_at FROM classified_ads WHERE owner_member_id = ? ORDER BY created_at DESC, id DESC LIMIT 1');
    $lastStmt->execute([$memberId]);
    $lastCreatedAt = $lastStmt->fetchColumn();
    if (is_string($lastCreatedAt) && $lastCreatedAt !== '') {
        $lastTs = strtotime($lastCreatedAt);
        if ($lastTs !== false && $lastTs > time() - 60) {
            throw new RuntimeException((string) $messages['rate_limited']);
        }
    }

    $countStmt = db()->prepare('SELECT COUNT(*) FROM classified_ads WHERE owner_member_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)');
    $countStmt->execute([$memberId]);
    if ((int) ($countStmt->fetchColumn() ?: 0) >= 5) {
        throw new RuntimeException((string) $messages['daily_limit']);
    }
}
}

if (!function_exists('classifieds_sync_expired')) {
function classifieds_sync_expired(): void
{
    if (!table_exists('classified_ads')) {
        return;
    }

    try {
        db()->exec('UPDATE classified_ads SET status = "expired", updated_at = NOW() WHERE status = "active" AND expires_at IS NOT NULL AND expires_at < NOW()');
    } catch (Throwable $throwable) {
        log_structured_event('classified_ads_expire_sync_failed', [
            'message' => $throwable->getMessage(),
        ]);
    }
}
}

if (!function_exists('ensure_content_proposals_table')) {
/**
 * @return array{type:string,nullable:bool,default:?string}|null
 */
function content_proposals_column_metadata(string $column): ?array
{
    try {
        $stmt = db()->prepare(
            'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
             FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
             LIMIT 1'
        );
        $stmt->execute(['content_proposals', $column]);
        $row = $stmt->fetch();
    } catch (Throwable) {
        return null;
    }

    if (!is_array($row)) {
        return null;
    }

    return [
        'type' => strtolower(str_replace("'", '"', (string) ($row['COLUMN_TYPE'] ?? ''))),
        'nullable' => strtoupper((string) ($row['IS_NULLABLE'] ?? 'YES')) === 'YES',
        'default' => array_key_exists('COLUMN_DEFAULT', $row) && $row['COLUMN_DEFAULT'] !== null ? (string) $row['COLUMN_DEFAULT'] : null,
    ];
}

function ensure_content_proposals_table(): bool
{
    try {
        db()->exec(
            'CREATE TABLE IF NOT EXISTS content_proposals (
                id INT AUTO_INCREMENT PRIMARY KEY,
                member_id INT NOT NULL,
                area VARCHAR(64) NOT NULL,
                proposal_type ENUM("category","content","domain","tag") NOT NULL DEFAULT "content",
                title VARCHAR(190) NOT NULL,
                summary TEXT DEFAULT NULL,
                contact VARCHAR(220) DEFAULT NULL,
                source_ref VARCHAR(500) DEFAULT NULL,
                status ENUM("pending","reviewed","accepted","rejected") NOT NULL DEFAULT "pending",
                moderation_note TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_content_proposals_member_created (member_id, created_at),
                INDEX idx_content_proposals_status_area (status, area),
                INDEX idx_content_proposals_type (proposal_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $columns = [
            'member_id' => 'ALTER TABLE content_proposals ADD COLUMN member_id INT NOT NULL DEFAULT 0 AFTER id',
            'area' => 'ALTER TABLE content_proposals ADD COLUMN area VARCHAR(64) NOT NULL DEFAULT "articles" AFTER member_id',
            'proposal_type' => 'ALTER TABLE content_proposals ADD COLUMN proposal_type ENUM("category","content","domain","tag") NOT NULL DEFAULT "content" AFTER area',
            'title' => 'ALTER TABLE content_proposals ADD COLUMN title VARCHAR(190) NOT NULL DEFAULT "Proposal" AFTER proposal_type',
            'summary' => 'ALTER TABLE content_proposals ADD COLUMN summary TEXT DEFAULT NULL AFTER title',
            'contact' => 'ALTER TABLE content_proposals ADD COLUMN contact VARCHAR(220) DEFAULT NULL AFTER summary',
            'source_ref' => 'ALTER TABLE content_proposals ADD COLUMN source_ref VARCHAR(500) DEFAULT NULL AFTER contact',
            'status' => 'ALTER TABLE content_proposals ADD COLUMN status ENUM("pending","reviewed","accepted","rejected") NOT NULL DEFAULT "pending" AFTER source_ref',
            'moderation_note' => 'ALTER TABLE content_proposals ADD COLUMN moderation_note TEXT DEFAULT NULL AFTER status',
            'created_at' => 'ALTER TABLE content_proposals ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER moderation_note',
            'updated_at' => 'ALTER TABLE content_proposals ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
        ];

        foreach ($columns as $column => $statement) {
            if (!table_has_column('content_proposals', $column)) {
                db()->exec($statement);
            }
        }
        $proposalTypeMetadata = content_proposals_column_metadata('proposal_type');
        if ($proposalTypeMetadata !== null && (!str_contains($proposalTypeMetadata['type'], '"domain"') || !str_contains($proposalTypeMetadata['type'], '"tag"'))) {
            try {
                db()->exec('ALTER TABLE content_proposals MODIFY COLUMN proposal_type ENUM("category","content","domain","tag") NOT NULL DEFAULT "content"');
            } catch (Throwable) {
                // Some database engines used in tests do not support MySQL ENUM modification.
            }
        }

        if (!table_has_index('content_proposals', 'idx_content_proposals_member_created')) {
            db()->exec('ALTER TABLE content_proposals ADD INDEX idx_content_proposals_member_created (member_id, created_at)');
        }
        if (!table_has_index('content_proposals', 'idx_content_proposals_status_area')) {
            db()->exec('ALTER TABLE content_proposals ADD INDEX idx_content_proposals_status_area (status, area)');
        }
        if (!table_has_index('content_proposals', 'idx_content_proposals_type')) {
            db()->exec('ALTER TABLE content_proposals ADD INDEX idx_content_proposals_type (proposal_type)');
        }

        return true;
    } catch (Throwable $throwable) {
        log_structured_event('content_proposals_table_ensure_failed', [
            'message' => $throwable->getMessage(),
        ]);

        return false;
    }
}
}

if (!function_exists('content_proposal_allowed_areas')) {
/**
 * @return array<string, true>
 */
function content_proposal_allowed_areas(): array
{
    return [
        'articles' => true,
        'albums' => true,
        'auctions' => true,
        'classifieds' => true,
        'events' => true,
        'members_library' => true,
        'news' => true,
        'webotheque' => true,
        'wiki' => true,
    ];
}
}

if (!function_exists('content_proposal_clean_single_line')) {
function content_proposal_clean_single_line(string $value, int $maxLength): string
{
    $value = str_replace(["\r", "\n"], ' ', strip_tags($value));
    $value = trim((string) preg_replace('/\s+/u', ' ', $value));
    if ($value !== '' && mb_strlen($value) > $maxLength) {
        throw new RuntimeException('Invalid content proposal.');
    }

    return $value;
}
}

if (!function_exists('content_proposal_clean_multiline')) {
function content_proposal_clean_multiline(string $value, int $maxLength): string
{
    $value = str_replace(["\r\n", "\r"], "\n", strip_tags($value));
    $value = trim((string) preg_replace('/[ \t]+/u', ' ', $value));
    if ($value !== '' && mb_strlen($value) > $maxLength) {
        throw new RuntimeException('Invalid content proposal.');
    }

    return $value;
}
}

if (!function_exists('content_proposal_details_text')) {
/**
 * @param array<string, mixed> $details
 */
function content_proposal_details_text(array $details): string
{
    $lines = [];
    foreach ($details as $label => $value) {
        $label = content_proposal_clean_single_line((string) $label, 120);
        $value = content_proposal_clean_multiline((string) $value, 1800);
        if ($label === '' || $value === '') {
            continue;
        }
        $lines[] = $label . ': ' . $value;
    }

    return content_proposal_clean_multiline(implode("\n", $lines), 5000);
}
}

if (!function_exists('content_proposal_summary_rows')) {
/**
 * @return list<array{label:string,value:string}>
 */
function content_proposal_summary_rows(string $summary): array
{
    $rows = [];
    $currentIndex = null;
    foreach (preg_split('/\R/u', $summary) ?: [] as $line) {
        $line = trim((string) $line);
        if ($line === '') {
            continue;
        }
        if (preg_match('/^\s*([^:]{1,120}):\s*(.*)\s*$/u', $line, $matches) === 1) {
            $rows[] = [
                'label' => trim((string) $matches[1]),
                'value' => trim((string) $matches[2]),
            ];
            $currentIndex = count($rows) - 1;
            continue;
        }
        if ($currentIndex !== null) {
            $rows[$currentIndex]['value'] = trim($rows[$currentIndex]['value'] . "\n" . $line);
        }
    }

    return $rows;
}
}

if (!function_exists('content_proposal_label_key')) {
function content_proposal_label_key(string $label): string
{
    $label = mb_strtolower(trim($label), 'UTF-8');
    if ($label === '') {
        return '';
    }
    $label = strtr($label, [
        'à' => 'a',
        'á' => 'a',
        'â' => 'a',
        'ã' => 'a',
        'ä' => 'a',
        'å' => 'a',
        'æ' => 'ae',
        'ç' => 'c',
        'è' => 'e',
        'é' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'ì' => 'i',
        'í' => 'i',
        'î' => 'i',
        'ï' => 'i',
        'ñ' => 'n',
        'ò' => 'o',
        'ó' => 'o',
        'ô' => 'o',
        'õ' => 'o',
        'ö' => 'o',
        'œ' => 'oe',
        'ù' => 'u',
        'ú' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'ý' => 'y',
        'ÿ' => 'y',
    ]);
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
    if (is_string($ascii) && $ascii !== '') {
        $label = $ascii;
    }
    $label = preg_replace('/[^a-z0-9]+/i', ' ', $label) ?? $label;

    return trim((string) preg_replace('/\s+/u', ' ', $label));
}
}

if (!function_exists('content_proposal_detail_from_summary')) {
/**
 * @param list<string> $labels
 */
function content_proposal_detail_from_summary(string $summary, array $labels): string
{
    $wanted = [];
    foreach ($labels as $label) {
        $normalized = content_proposal_label_key($label);
        if ($normalized !== '') {
            $wanted[$normalized] = true;
        }
    }
    if ($wanted === []) {
        return '';
    }

    foreach (content_proposal_summary_rows($summary) as $row) {
        $label = content_proposal_label_key($row['label']);
        if (isset($wanted[$label])) {
            return trim($row['value']);
        }
    }

    return '';
}
}

if (!function_exists('content_proposal_category_code')) {
function content_proposal_category_code(string $title, int $maxLength = 120, string $fallback = 'category'): string
{
    $maxLength = max(1, $maxLength);
    $code = slugify($title);
    if ($code === '' || $code === 'n-a') {
        $code = slugify($fallback);
    }
    if ($code === '' || $code === 'n-a') {
        $code = 'category';
    }
    if (strlen($code) > $maxLength) {
        $code = rtrim(substr($code, 0, $maxLength), '-');
    }

    return $code !== '' ? $code : 'category';
}
}

if (!function_exists('content_proposal_accepted_categories')) {
/**
 * @return array<string, string>
 */
function content_proposal_accepted_categories(string $area, int $maxCodeLength = 120): array
{
    return content_proposal_accepted_terms($area, 'category', $maxCodeLength, $area);
}
}

if (!function_exists('content_proposal_accepted_terms')) {
/**
 * @return array<string, string>
 */
function content_proposal_accepted_terms(string $area, string $proposalType, int $maxCodeLength = 120, string $fallback = 'term'): array
{
    if (!ensure_content_proposals_table()) {
        return [];
    }

    $proposalType = content_proposal_clean_single_line($proposalType, 24);
    if (!in_array($proposalType, ['category', 'domain', 'tag'], true)) {
        return [];
    }

    try {
        $stmt = db()->prepare(
            'SELECT title
             FROM content_proposals
             WHERE area = ?
               AND proposal_type = ?
               AND status = "accepted"
             ORDER BY updated_at ASC, id ASC'
        );
        $stmt->execute([$area, $proposalType]);
    } catch (Throwable) {
        return [];
    }

    $terms = [];
    foreach (($stmt->fetchAll() ?: []) as $row) {
        $title = content_proposal_clean_single_line((string) ($row['title'] ?? ''), 190);
        if ($title === '') {
            continue;
        }
        $code = content_proposal_category_code($title, $maxCodeLength, $fallback);
        if (!isset($terms[$code])) {
            $terms[$code] = $title;
        }
    }

    return $terms;
}
}

if (!function_exists('content_proposal_payload')) {
/**
 * @return array{member_id:int,area:string,proposal_type:string,title:string,summary:string,contact:string,source_ref:string,status:string}
 */
function content_proposal_payload(
    int $memberId,
    string $area,
    string $proposalType,
    string $title,
    string $summary = '',
    string $contact = '',
    string $sourceRef = '',
    string $status = 'pending'
): array {
    $area = content_proposal_clean_single_line($area, 64);
    $proposalType = content_proposal_clean_single_line($proposalType, 24);
    $title = content_proposal_clean_single_line($title, 190);
    $summary = content_proposal_clean_multiline($summary, 5000);
    $contact = content_proposal_clean_single_line($contact, 220);
    $sourceRef = content_proposal_clean_single_line($sourceRef, 500);
    $status = content_proposal_clean_single_line($status, 32);

    if (
        $memberId <= 0
        || !isset(content_proposal_allowed_areas()[$area])
        || !in_array($proposalType, ['category', 'content', 'domain', 'tag'], true)
        || !in_array($status, ['pending', 'reviewed', 'accepted', 'rejected'], true)
        || $title === ''
    ) {
        throw new RuntimeException('Invalid content proposal.');
    }

    return [
        'member_id' => $memberId,
        'area' => $area,
        'proposal_type' => $proposalType,
        'title' => $title,
        'summary' => $summary,
        'contact' => $contact,
        'source_ref' => $sourceRef,
        'status' => $status,
    ];
}
}

if (!function_exists('content_proposal_create')) {
function content_proposal_create(
    int $memberId,
    string $area,
    string $proposalType,
    string $title,
    string $summary = '',
    string $contact = '',
    string $sourceRef = '',
    string $status = 'pending'
): int {
    if (!ensure_content_proposals_table()) {
        throw new RuntimeException('Content proposal storage is unavailable.');
    }

    $payload = content_proposal_payload($memberId, $area, $proposalType, $title, $summary, $contact, $sourceRef, $status);
    db()->prepare(
        'INSERT INTO content_proposals (member_id, area, proposal_type, title, summary, contact, source_ref, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $payload['member_id'],
        $payload['area'],
        $payload['proposal_type'],
        $payload['title'],
        $payload['summary'] !== '' ? $payload['summary'] : null,
        $payload['contact'] !== '' ? $payload['contact'] : null,
        $payload['source_ref'] !== '' ? $payload['source_ref'] : null,
        $payload['status'],
    ]);

    return (int) db()->lastInsertId();
}
}

if (!function_exists('content_proposal_notify_site')) {
/**
 * @param array{area:string,proposal_type:string,title:string,summary:string,contact:string,source_ref:string} $proposal
 */
function content_proposal_notify_site(string $subject, array $proposal): bool
{
    $subject = content_proposal_clean_single_line($subject, 190);
    $contact = content_proposal_clean_single_line((string) ($proposal['contact'] ?? ''), 220);
    $body = [
        'Nouvelle proposition ON4CRD',
        '',
        'Espace: ' . content_proposal_clean_single_line((string) ($proposal['area'] ?? ''), 64),
        'Type: ' . content_proposal_clean_single_line((string) ($proposal['proposal_type'] ?? ''), 24),
        'Titre: ' . content_proposal_clean_single_line((string) ($proposal['title'] ?? ''), 190),
    ];

    $summary = content_proposal_clean_multiline((string) ($proposal['summary'] ?? ''), 5000);
    if ($summary !== '') {
        $body[] = '';
        $body[] = $summary;
    }
    $sourceRef = content_proposal_clean_single_line((string) ($proposal['source_ref'] ?? ''), 500);
    if ($sourceRef !== '') {
        $body[] = '';
        $body[] = 'Source: ' . $sourceRef;
    }
    if ($contact !== '') {
        $body[] = '';
        $body[] = 'Contact: ' . $contact;
    }

    $headers = 'From: ON4CRD Website <no-reply@on4crd.be>' . "\r\n"
        . 'Content-Type: text/plain; charset=UTF-8';
    if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
        $headers .= "\r\n" . 'Reply-To: ' . $contact;
    }

    return @mail(site_contact_email(), $subject !== '' ? $subject : 'Nouvelle proposition ON4CRD', implode("\n", $body) . "\n", $headers);
}
}

if (!function_exists('articles_sync_scheduled_publications')) {
function articles_sync_scheduled_publications(): void
{
    if (!table_exists('articles')) {
        return;
    }

    db()->exec('UPDATE articles SET status = "published", published_at = COALESCE(published_at, NOW()), updated_at = NOW() WHERE status = "scheduled" AND scheduled_at IS NOT NULL AND scheduled_at <= NOW()');
}
}

function quote_locale_columns(): array
{
    return array_map(static fn(string $locale): string => 'quote_' . $locale, supported_locales());
}

function native_quote_fallback_for_locale(string $locale): array
{
    $quotes = [
        'fr' => ['quote' => 'Chaque contact radio est une passerelle ouverte vers une autre voix.', 'author' => 'ON4CRD'],
        'en' => ['quote' => 'Every radio contact opens a path to another voice.', 'author' => 'ON4CRD'],
        'de' => ['quote' => 'Jeder Funkkontakt öffnet einen Weg zu einer anderen Stimme.', 'author' => 'ON4CRD'],
        'nl' => ['quote' => 'Elk radiocontact opent een pad naar een andere stem.', 'author' => 'ON4CRD'],
        'it' => ['quote' => 'Ogni contatto radio apre un ponte verso un’altra voce.', 'author' => 'ON4CRD'],
        'es' => ['quote' => 'Cada contacto de radio abre un puente hacia otra voz.', 'author' => 'ON4CRD'],
        'pt' => ['quote' => 'Cada contacto de rádio abre uma ponte para outra voz.', 'author' => 'ON4CRD'],
        'ar' => ['quote' => 'كل اتصال لاسلكي يفتح جسراً نحو صوت آخر.', 'author' => 'ON4CRD'],
        'hi' => ['quote' => 'हर रेडियो संपर्क किसी दूसरी आवाज़ तक एक पुल खोलता है।', 'author' => 'ON4CRD'],
        'ja' => ['quote' => 'ひとつの無線交信が、別の声へ続く橋を開く。', 'author' => 'ON4CRD'],
        'zh' => ['quote' => '每一次无线电通联，都是通向另一种声音的桥梁。', 'author' => 'ON4CRD'],
        'bn' => ['quote' => 'প্রতিটি রেডিও যোগাযোগ আরেকটি কণ্ঠের দিকে একটি সেতু খুলে দেয়।', 'author' => 'ON4CRD'],
        'ru' => ['quote' => 'Каждая радиосвязь открывает мост к другому голосу.', 'author' => 'ON4CRD'],
        'id' => ['quote' => 'Setiap kontak radio membuka jembatan menuju suara lain.', 'author' => 'ON4CRD'],
    ];

    return $quotes[$locale] ?? $quotes['fr'];
}

function seed_quotes_from_sql_file(string $filePath): void
{
    if (!is_file($filePath)) {
        return;
    }

    $sql = (string) file_get_contents($filePath);
    if (trim($sql) === '') {
        return;
    }

    if (
        preg_match('/INSERT INTO\s+(?:public\.)?(?:`?radioamateur_citations`?)/i', $sql) === 1
    ) {
        seed_quotes_from_radioamateur_dump($sql);
        return;
    }

    $statements = preg_split('/;\s*(?:\R|$)/', $sql) ?: [];
    foreach ($statements as $statement) {
        $trimmed = trim($statement);
        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#') || str_starts_with($trimmed, '/*')) {
            continue;
        }
        try {
            db()->exec($trimmed);
        } catch (Throwable $throwable) {
            log_structured_event('quotes_seed_statement_skipped', [
                'message' => $throwable->getMessage(),
            ]);
        }
    }
}

function seed_quotes_from_radioamateur_dump(string $sql): void
{
    if (!preg_match_all('/INSERT INTO\s+(?:public\.)?(?:`?radioamateur_citations`?)\s*\([^)]*\)\s*VALUES\s*(.+?);/is', $sql, $matches)) {
        return;
    }

    $insertStmt = db()->prepare('INSERT INTO quotes (quote_fr, quote_en, quote_de, quote_nl, author, is_active) VALUES (?, ?, ?, ?, ?, 1)');
    if ($insertStmt === false) {
        return;
    }

    foreach (($matches[1] ?? []) as $valuesBlock) {
        if (!preg_match_all("/\\(\\s*'((?:''|\\\\'|[^'])*)'\\s*,\\s*'((?:''|\\\\'|[^'])*)'\\s*,\\s*'((?:''|\\\\'|[^'])*)'\\s*,\\s*'((?:''|\\\\'|[^'])*)'\\s*,/u", (string) $valuesBlock, $rows, PREG_SET_ORDER)) {
            continue;
        }

        foreach ($rows as $rowMatch) {
            $quoteFr = trim(str_replace(["''", "\\'"], ["'", "'"], (string) ($rowMatch[1] ?? '')));
            $quoteEn = trim(str_replace(["''", "\\'"], ["'", "'"], (string) ($rowMatch[2] ?? '')));
            $quoteDe = trim(str_replace(["''", "\\'"], ["'", "'"], (string) ($rowMatch[3] ?? '')));
            $quoteNl = trim(str_replace(["''", "\\'"], ["'", "'"], (string) ($rowMatch[4] ?? '')));
            if ($quoteFr === '') {
                continue;
            }

            try {
                $insertStmt->execute([$quoteFr, $quoteEn, $quoteDe, $quoteNl, null]);
            } catch (Throwable) {
                continue;
            }
        }
    }
}

function random_quote_for_layout(): ?array
{
    try {
        if (!table_exists('quotes')) {
            return null;
        }

        $whereActive = table_has_column('quotes', 'is_active') ? ' WHERE is_active = 1' : '';
        $countStmt = db()->query('SELECT COUNT(*) FROM quotes' . $whereActive);
        $activeCount = $countStmt !== false ? (int) $countStmt->fetchColumn() : 0;
        if ($activeCount <= 0) {
            return null;
        }

        $daySeed = date('Y-m-d');
        $offset = (int) (sprintf('%u', crc32($daySeed)) % $activeCount);
        $quoteColumns = array_merge(quote_locale_columns(), ['author']);
        foreach ($quoteColumns as $quoteColumn) {
            if (!table_has_column('quotes', $quoteColumn)) {
                if ($quoteColumn === 'author') {
                    return null;
                }
                continue;
            }
        }

        $selectColumns = array_filter($quoteColumns, static fn(string $quoteColumn): bool => table_has_column('quotes', $quoteColumn));
        $stmt = db()->query('SELECT ' . implode(', ', $selectColumns) . ' FROM quotes' . $whereActive . ' LIMIT 1 OFFSET ' . $offset);
        if ($stmt === false) {
            return null;
        }
    } catch (Throwable) {
        return null;
    }
    $row = $stmt->fetch();
    if (!is_array($row)) {
        return null;
    }

    $locale = current_locale();
    $localizedQuotes = [];
    foreach (supported_locales() as $supportedLocale) {
        $localizedQuotes[$supportedLocale] = trim((string) ($row['quote_' . $supportedLocale] ?? ''));
    }
    $quote = $localizedQuotes[$locale] ?? '';
    if ($quote === '') {
        $nativeFallback = native_quote_fallback_for_locale($locale);
        $quote = (string) $nativeFallback['quote'];
    }
    $author = trim((string) ($row['author'] ?? ''));
    if ($author === '' && isset($nativeFallback)) {
        $author = (string) $nativeFallback['author'];
    }
    if ($quote === '') {
        return null;
    }

    return [
        'quote' => $quote,
        'author' => $author,
    ];
}

function qrz_profile_url_for_callsign(string $callsign): ?string
{
    $callsign = strtoupper(trim($callsign));
    if ($callsign === '' || preg_match('/^[A-Z0-9\/-]{2,32}$/', $callsign) !== 1) {
        return null;
    }

    $url = 'https://www.qrz.com/db/' . rawurlencode($callsign);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: ON4CRD Profile Validator\r\nAccept: text/html\r\n",
            'ignore_errors' => true,
            'timeout' => 4,
        ],
    ]);

    $body = @file_get_contents($url, false, $context, 0, 262144);
    if (!is_string($body) || $body === '') {
        return null;
    }

    $statusLine = (string) ($http_response_header[0] ?? '');
    if (!preg_match('/\s(2\d\d|3\d\d)\s/', $statusLine)) {
        return null;
    }

    $normalizedBody = strtolower($body);
    if (
        str_contains($normalizedBody, 'not found') ||
        str_contains($normalizedBody, 'no such callsign') ||
        str_contains($normalizedBody, 'callsign not found')
    ) {
        return null;
    }

    return $url;
}
