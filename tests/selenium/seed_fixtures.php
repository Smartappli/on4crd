<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/config_loader.php';

$configFile = app_config_file_path();
$isSeleniumConfig = str_replace('\\', '/', $configFile);
if (getenv('ON4CRD_ALLOW_SELENIUM_FIXTURES') !== '1' && !str_ends_with($isSeleniumConfig, '/storage/auth/selenium-config.php')) {
    fwrite(STDERR, "Refusing to seed Selenium fixtures outside the Selenium config.\n");
    exit(2);
}

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/cache.php';
require_once dirname(__DIR__, 2) . '/app/route_helper_loader.php';
app_load_route_helpers('__all');

function selenium_fixture_member_id(): int
{
    if (table_exists('members')) {
        $stmt = db()->prepare('SELECT id FROM members WHERE callsign = ? LIMIT 1');
        $stmt->execute(['SELENIUMADMIN']);
        $memberId = (int) ($stmt->fetchColumn() ?: 0);
        if ($memberId > 0) {
            return $memberId;
        }

        $memberId = (int) (db()->query('SELECT id FROM members ORDER BY id ASC LIMIT 1')?->fetchColumn() ?: 0);
        if ($memberId > 0) {
            return $memberId;
        }
    }

    return 1;
}

function selenium_fixture_ensure_auction_tables(): void
{
    db()->exec(
        'CREATE TABLE IF NOT EXISTS auction_lots (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(190) NOT NULL UNIQUE,
            title VARCHAR(190) NOT NULL,
            summary TEXT DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            image_url VARCHAR(255) DEFAULT NULL,
            starting_price_cents INT NOT NULL DEFAULT 0,
            reserve_price_cents INT DEFAULT NULL,
            min_increment_cents INT NOT NULL DEFAULT 100,
            buy_now_price_cents INT DEFAULT NULL,
            current_price_cents INT NOT NULL DEFAULT 0,
            starts_at DATETIME NOT NULL,
            ends_at DATETIME NOT NULL,
            extended_until DATETIME DEFAULT NULL,
            status ENUM("draft","scheduled","active","closed","cancelled") NOT NULL DEFAULT "draft",
            winner_member_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_auction_lots_status_ends_at (status, ends_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS auction_bids (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lot_id INT NOT NULL,
            member_id INT NOT NULL,
            amount_cents INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_auction_bids_lot_amount (lot_id, amount_cents, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function selenium_fixture_seed_article(int $memberId): void
{
    if (!table_exists('articles')) {
        return;
    }

    db()->prepare(
        'INSERT INTO articles (slug, title, excerpt, content, status, category, subcategory, published_at, author_id)
         VALUES (?, ?, ?, ?, "published", "radio", "selenium", NOW(), ?)
         ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            excerpt = VALUES(excerpt),
            content = VALUES(content),
            status = "published",
            category = "radio",
            subcategory = "selenium",
            published_at = NOW(),
            author_id = VALUES(author_id)'
    )->execute([
        'selenium-fixture-article',
        'Selenium fixture article radio',
        'Article public de regression Selenium.',
        '<p>Article public de regression Selenium pour verifier la navigation detail.</p>',
        $memberId,
    ]);
}

function selenium_fixture_seed_wiki(int $memberId): void
{
    if (!table_exists('wiki_pages')) {
        return;
    }

    db()->prepare(
        'INSERT INTO wiki_pages (slug, title, content, category, subcategory, author_id, status, proposal_kind)
         VALUES (?, ?, ?, "radio", "selenium", ?, "published", "page")
         ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            content = VALUES(content),
            category = "radio",
            subcategory = "selenium",
            author_id = VALUES(author_id),
            status = "published",
            proposal_kind = "page"'
    )->execute([
        'selenium-fixture-wiki',
        'Selenium fixture wiki radio',
        '<p>Page wiki publique de regression Selenium.</p>',
        $memberId,
    ]);
}

function selenium_fixture_seed_album(int $memberId): void
{
    if (!table_exists('albums') || !table_exists('album_photos')) {
        return;
    }

    $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/albums';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Cannot create Selenium album upload directory.');
    }
    $photoName = 'selenium-fixture-album-photo.png';
    $photoPath = $uploadDir . '/' . $photoName;
    if (!is_file($photoPath)) {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAAGklEQVR4nGP8z8Dwn4GBgYGJgYGB4T8ABQsCBAJH7m4AAAAASUVORK5CYII=', true);
        if ($png === false) {
            throw new RuntimeException('Cannot decode Selenium PNG fixture.');
        }
        file_put_contents($photoPath, $png);
    }
    @chmod($photoPath, 0644);

    $stmt = db()->prepare('SELECT id FROM albums WHERE title = ? LIMIT 1');
    $stmt->execute(['Selenium fixture album public']);
    $albumId = (int) ($stmt->fetchColumn() ?: 0);
    if ($albumId <= 0) {
        db()->prepare('INSERT INTO albums (member_id, category, subcategory, title, description, is_public) VALUES (?, "radio", "selenium", ?, ?, 1)')
            ->execute([$memberId, 'Selenium fixture album public', 'Album public de regression Selenium.']);
        $albumId = (int) db()->lastInsertId();
    } else {
        db()->prepare('UPDATE albums SET member_id = ?, category = "radio", subcategory = "selenium", description = ?, is_public = 1 WHERE id = ?')
            ->execute([$memberId, 'Album public de regression Selenium.', $albumId]);
    }

    $publicPath = 'storage/uploads/albums/' . $photoName;
    $photoStmt = db()->prepare('SELECT id FROM album_photos WHERE album_id = ? AND file_path = ? LIMIT 1');
    $photoStmt->execute([$albumId, $publicPath]);
    if ((int) ($photoStmt->fetchColumn() ?: 0) <= 0) {
        db()->prepare('INSERT INTO album_photos (album_id, sort_order, title, caption, file_path) VALUES (?, 1, ?, ?, ?)')
            ->execute([$albumId, 'Selenium fixture photo', 'Photo de regression Selenium.', $publicPath]);
    }
}

function selenium_fixture_seed_auction(): void
{
    selenium_fixture_ensure_auction_tables();
    db()->prepare(
        'INSERT INTO auction_lots (slug, title, summary, description, image_url, starting_price_cents, reserve_price_cents, min_increment_cents, buy_now_price_cents, starts_at, ends_at, status)
         VALUES (?, ?, ?, ?, "", 1000, NULL, 100, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 7 DAY), "active")
         ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            summary = VALUES(summary),
            description = VALUES(description),
            starting_price_cents = 1000,
            starts_at = DATE_SUB(NOW(), INTERVAL 1 DAY),
            ends_at = DATE_ADD(NOW(), INTERVAL 7 DAY),
            status = "active"'
    )->execute([
        'selenium-fixture-lot',
        'Selenium fixture auction lot',
        'Lot de regression Selenium.',
        '<p>Lot de regression Selenium pour verifier la navigation detail.</p>',
    ]);
}

function selenium_fixture_seed_classified(int $memberId): void
{
    if (!ensure_classified_ads_table()) {
        return;
    }

    $stmt = db()->prepare('SELECT id FROM classified_ads WHERE title = ? LIMIT 1');
    $stmt->execute(['Selenium fixture classified radio']);
    $id = (int) ($stmt->fetchColumn() ?: 0);
    if ($id <= 0) {
        db()->prepare('INSERT INTO classified_ads (owner_member_id, category_code, title, description, location, contact, price_cents, status, expires_at) VALUES (?, "gear", ?, ?, ?, ?, 2500, "active", DATE_ADD(NOW(), INTERVAL 30 DAY))')
            ->execute([$memberId, 'Selenium fixture classified radio', 'Annonce de regression Selenium.', 'Durnal', 'selenium@example.test']);
        return;
    }

    db()->prepare('UPDATE classified_ads SET owner_member_id = ?, category_code = "gear", description = ?, location = ?, contact = ?, price_cents = 2500, status = "active", expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY), updated_at = NOW() WHERE id = ?')
        ->execute([$memberId, 'Annonce de regression Selenium.', 'Durnal', 'selenium@example.test', $id]);
}

function selenium_fixture_seed_webotheque(int $memberId): void
{
    if (!ensure_webotheque_table()) {
        return;
    }

    $url = 'https://example.org/selenium-fixture-webotheque';
    $stmt = db()->prepare('SELECT id FROM member_webotheque_links WHERE url = ? LIMIT 1');
    $stmt->execute([$url]);
    $id = (int) ($stmt->fetchColumn() ?: 0);
    if ($id <= 0) {
        db()->prepare('INSERT INTO member_webotheque_links (member_id, category, subcategory, title, url, description, tags) VALUES (?, "radio", "selenium", ?, ?, ?, ?)')
            ->execute([$memberId, 'Selenium fixture webotheque', $url, 'Lien webotheque de regression Selenium.', 'selenium,radio']);
        return;
    }

    db()->prepare('UPDATE member_webotheque_links SET member_id = ?, category = "radio", subcategory = "selenium", title = ?, description = ?, tags = ?, updated_at = NOW() WHERE id = ?')
        ->execute([$memberId, 'Selenium fixture webotheque', 'Lien webotheque de regression Selenium.', 'selenium,radio', $id]);
}

$memberId = selenium_fixture_member_id();
selenium_fixture_seed_article($memberId);
selenium_fixture_seed_wiki($memberId);
selenium_fixture_seed_album($memberId);
selenium_fixture_seed_auction();
selenium_fixture_seed_classified($memberId);
selenium_fixture_seed_webotheque($memberId);

foreach ([
    'auction_public_lots_60_v1',
    'admin_albums_list_v2',
    'admin_albums_photos_total_v2',
] as $cacheKey) {
    cache_forget($cacheKey);
}

echo json_encode(['ok' => true], JSON_THROW_ON_ERROR) . PHP_EOL;
