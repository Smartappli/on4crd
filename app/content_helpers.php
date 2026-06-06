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
                author_id INT DEFAULT NULL,
                status ENUM("pending","published","rejected") NOT NULL DEFAULT "published",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_wiki_status_updated (status, updated_at),
                INDEX idx_wiki_updated (updated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        if (!table_has_column('wiki_pages', 'status')) {
            db()->exec('ALTER TABLE wiki_pages ADD COLUMN status ENUM("pending","published","rejected") NOT NULL DEFAULT "published" AFTER author_id');
        } else {
            db()->exec('ALTER TABLE wiki_pages MODIFY COLUMN status ENUM("pending","published","rejected") NOT NULL DEFAULT "published"');
        }
        if (!table_has_column('wiki_pages', 'created_at')) {
            db()->exec('ALTER TABLE wiki_pages ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status');
        }
        if (!table_has_column('wiki_pages', 'updated_at')) {
            db()->exec('ALTER TABLE wiki_pages ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
        }
        if (!table_has_index('wiki_pages', 'idx_wiki_status_updated')) {
            db()->exec('ALTER TABLE wiki_pages ADD INDEX idx_wiki_status_updated (status, updated_at)');
        }
        if (!table_has_index('wiki_pages', 'idx_wiki_updated')) {
            db()->exec('ALTER TABLE wiki_pages ADD INDEX idx_wiki_updated (updated_at)');
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
