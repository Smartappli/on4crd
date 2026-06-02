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
                status ENUM("draft","active","sold","archived","expired") NOT NULL DEFAULT "draft",
                expires_at DATETIME NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_classified_owner_status (owner_member_id, status),
                INDEX idx_classified_status_created (status, created_at),
                INDEX idx_classified_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $columnStmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );

        $columnStmt->execute(['classified_ads', 'expires_at']);
        if ((int) $columnStmt->fetchColumn() === 0) {
            db()->exec('ALTER TABLE classified_ads ADD COLUMN expires_at DATETIME NULL DEFAULT NULL AFTER status');
        }

        db()->exec('ALTER TABLE classified_ads MODIFY COLUMN status ENUM("draft","active","sold","archived","expired") NOT NULL DEFAULT "draft"');

        return true;
    } catch (Throwable $throwable) {
        log_structured_event('classified_ads_table_ensure_failed', [
            'message' => $throwable->getMessage(),
        ]);

        return false;
    }
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
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_wiki_updated (updated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

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

        return true;
    } catch (Throwable $throwable) {
        log_structured_event('wiki_tables_ensure_failed', [
            'message' => $throwable->getMessage(),
        ]);

        return false;
    }
}
}

if (!function_exists('classifieds_sync_expired')) {
function classifieds_sync_expired(): void
{
    if (!ensure_classified_ads_table()) {
        return;
    }

    db()->exec('UPDATE classified_ads SET status = "expired", updated_at = NOW() WHERE status = "active" AND expires_at IS NOT NULL AND expires_at < NOW()');
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
