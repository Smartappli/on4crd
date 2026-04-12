<?php
declare(strict_types=1);

function config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $configFile = __DIR__ . '/../config/config.php';
        if (!is_file($configFile)) {
            throw new RuntimeException('Missing config/config.php. Copy config.sample.php first.');
        }
        $config = require $configFile;
    }

    if ($key === null) {
        return $config;
    }

    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function is_https_request(): bool
{
    return (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) === '443')
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
    );
}

function csp_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }

    return $nonce;
}


function mb_safe_substr(string $value, int $start, ?int $length = null): string
{
    if (function_exists('mb_substr')) {
        return $length === null ? mb_substr($value, $start) : mb_substr($value, $start, $length);
    }

    return $length === null ? substr($value, $start) : substr($value, $start, $length);
}

function mb_safe_strtolower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
}

function mb_safe_strtoupper(string $value): string
{
    return function_exists('mb_strtoupper') ? mb_strtoupper($value) : strtoupper($value);
}

function mb_safe_strimwidth(string $value, int $start, int $width, string $trimMarker = ''): string
{
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($value, $start, $width, $trimMarker);
    }

    $slice = substr($value, $start, $width);
    if ($slice === false) {
        return '';
    }

    if (strlen($value) > ($start + $width) && $trimMarker !== '') {
        return rtrim($slice) . $trimMarker;
    }

    return $slice;
}

function matomo_origin(): ?string
{
    $matomoUrl = trim((string) config('tracking.matomo_url', ''));
    if ($matomoUrl === '') {
        return null;
    }

    $parts = parse_url($matomoUrl);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return null;
    }

    $origin = $parts['scheme'] . '://' . $parts['host'];
    if (!empty($parts['port'])) {
        $origin .= ':' . $parts['port'];
    }

    return $origin;
}

function apply_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    $nonce = csp_nonce();
    $scriptSrc = ["'self'", "'nonce-" . $nonce . "'"];
    $imgSrc = ["'self'", 'data:', 'https:'];
    $styleSrc = ["'self'", "'unsafe-inline'"];
    $connectSrc = ["'self'"];

    $matomoOrigin = matomo_origin();
    if ($matomoOrigin !== null) {
        $scriptSrc[] = $matomoOrigin;
        $imgSrc[] = $matomoOrigin;
        $connectSrc[] = $matomoOrigin;
    }

    $csp = [
        "default-src 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'none'",
        "object-src 'none'",
        "manifest-src 'self'",
        "worker-src 'self'",
        "frame-src 'none'",
        "font-src 'self' data:",
        'script-src ' . implode(' ', array_unique($scriptSrc)),
        'style-src ' . implode(' ', array_unique($styleSrc)),
        'img-src ' . implode(' ', array_unique($imgSrc)),
        'connect-src ' . implode(' ', array_unique($connectSrc)),
    ];

    if (is_https_request()) {
        $csp[] = 'upgrade-insecure-requests';
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    header('Content-Security-Policy: ' . implode('; ', $csp));
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Permissions-Policy: camera=(), geolocation=(), microphone=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');

    if (!empty($_SESSION['member_id'])) {
        header('Cache-Control: private, no-store, max-age=0');
        header('Pragma: no-cache');
    }
}

function ensure_storage_htaccess(string $directory, string $rules): void
{
    $file = rtrim($directory, '/') . '/.htaccess';
    if (!is_file($file)) {
        file_put_contents($file, $rules);
    }
}

function client_ip_address(): string
{
    return trim((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')) ?: '0.0.0.0';
}

function login_throttle_file(): string
{
    return cache_dir_path() . '/login-' . hash('sha256', client_ip_address()) . '.json';
}

function login_throttle_state(): array
{
    $file = login_throttle_file();
    if (!is_file($file)) {
        return ['attempts' => 0, 'first_attempt_at' => 0, 'locked_until' => 0];
    }

    $decoded = json_decode((string) file_get_contents($file), true);
    return is_array($decoded)
        ? array_merge(['attempts' => 0, 'first_attempt_at' => 0, 'locked_until' => 0], $decoded)
        : ['attempts' => 0, 'first_attempt_at' => 0, 'locked_until' => 0];
}

function write_login_throttle_state(array $state): void
{
    file_put_contents(login_throttle_file(), json_encode($state, JSON_THROW_ON_ERROR));
}

function enforce_login_throttle(): void
{
    $state = login_throttle_state();
    if ((int) ($state['locked_until'] ?? 0) > time()) {
        throw new RuntimeException('Trop de tentatives de connexion. Réessayez plus tard.');
    }
}

function record_login_failure(): void
{
    $state = login_throttle_state();
    $now = time();
    $window = 900;

    if (($now - (int) ($state['first_attempt_at'] ?? 0)) > $window) {
        $state = ['attempts' => 0, 'first_attempt_at' => $now, 'locked_until' => 0];
    }

    $state['first_attempt_at'] = (int) ($state['first_attempt_at'] ?: $now);
    $state['attempts'] = (int) ($state['attempts'] ?? 0) + 1;
    if ($state['attempts'] >= 5) {
        $state['locked_until'] = $now + 900;
    }

    write_login_throttle_state($state);
}

function clear_login_failures(): void
{
    $file = login_throttle_file();
    if (is_file($file)) {
        unlink($file);
    }
}

function normalize_http_url(string $url, bool $allowRelative = false): ?string
{
    $trimmed = trim($url);
    if ($trimmed === '') {
        return null;
    }

    if ($allowRelative && preg_match('~^(?:/|\./|\../|\?|#)~', $trimmed) === 1) {
        return $trimmed;
    }

    if (filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
        throw new RuntimeException('URL invalide.');
    }

    $scheme = strtolower((string) parse_url($trimmed, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        throw new RuntimeException('Seules les URL HTTP et HTTPS sont autorisées.');
    }

    return $trimmed;
}

function is_private_or_reserved_ip(string $ip): bool
{
    if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
        return false;
    }

    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
}

function host_resolves_to_private_network(string $host): bool
{
    $normalizedHost = strtolower(rtrim(trim($host), '.'));
    if ($normalizedHost === '') {
        return true;
    }

    if (in_array($normalizedHost, ['localhost'], true) || str_ends_with($normalizedHost, '.local') || str_ends_with($normalizedHost, '.internal')) {
        return true;
    }

    if (filter_var($normalizedHost, FILTER_VALIDATE_IP) !== false) {
        return is_private_or_reserved_ip($normalizedHost);
    }

    if (function_exists('gethostbynamel')) {
        $ips = @gethostbynamel($normalizedHost);
        if (is_array($ips) && $ips !== []) {
            foreach ($ips as $ip) {
                if (is_private_or_reserved_ip($ip)) {
                    return true;
                }
            }
        }
    }

    return false;
}

function validate_outbound_url(string $url): ?string
{
    return normalize_http_url($url);
}

function validate_public_profile_url(string $url): ?string
{
    return normalize_http_url($url);
}

function validate_remote_feed_url(string $url): ?string
{
    $normalized = normalize_http_url($url);
    if ($normalized === null) {
        return null;
    }

    $host = (string) parse_url($normalized, PHP_URL_HOST);
    if ($host === '' || host_resolves_to_private_network($host)) {
        throw new RuntimeException("L'URL distante pointe vers un réseau privé ou réservé.");
    }

    return $normalized;
}

function place_shop_order(int $memberId, string $paymentMethod, string $notes = ''): string
{
    if (!table_exists('shop_orders') || !table_exists('shop_order_items')) {
        throw new RuntimeException("Le module boutique n'est pas initialisé.");
    }

    $cart = shop_cart_state();
    if (($cart['items'] ?? []) === []) {
        throw new RuntimeException('Le panier est vide.');
    }

    $allowedPayments = ['on_site', 'bank_transfer'];
    $payment = in_array($paymentMethod, $allowedPayments, true) ? $paymentMethod : 'on_site';
    $cleanNotes = trim($notes);
    if (function_exists('mb_substr')) {
        $cleanNotes = mb_substr($cleanNotes, 0, 1000);
    } else {
        $cleanNotes = substr($cleanNotes, 0, 1000);
    }

    $orderReference = 'CMD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $pdo = db();

    $insertOrder = $pdo->prepare(
        'INSERT INTO shop_orders (reference_code, member_id, status, total_cents, payment_method, notes) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insertItem = $pdo->prepare(
        'INSERT INTO shop_order_items (order_id, product_id, title_snapshot, qty, unit_price_cents, line_total_cents) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $fetchProduct = $pdo->prepare('SELECT id, title, price_cents, stock_qty, status FROM shop_products WHERE id = ? LIMIT 1');
    $updateStock = $pdo->prepare('UPDATE shop_products SET stock_qty = stock_qty - ? WHERE id = ? AND stock_qty >= ?');

    $pdo->beginTransaction();
    try {
        $insertOrder->execute([
            $orderReference,
            $memberId,
            'pending',
            (int) ($cart['total_cents'] ?? 0),
            $payment,
            $cleanNotes,
        ]);
        $orderId = (int) $pdo->lastInsertId();

        foreach ((array) ($cart['items'] ?? []) as $item) {
            $product = $item['product'] ?? null;
            $qty = max(1, (int) ($item['quantity'] ?? 0));
            $productId = (int) ($product['id'] ?? 0);
            if ($productId <= 0) {
                throw new RuntimeException('Produit invalide dans le panier.');
            }

            $fetchProduct->execute([$productId]);
            $dbProduct = $fetchProduct->fetch();
            if (!$dbProduct || (string) $dbProduct['status'] !== 'published') {
                throw new RuntimeException('Produit indisponible.');
            }

            if ($dbProduct['stock_qty'] !== null) {
                $updateStock->execute([$qty, (int) $dbProduct['id'], $qty]);
                if ($updateStock->rowCount() === 0) {
                    throw new RuntimeException('Stock insuffisant pour ' . (string) $dbProduct['title'] . '.');
                }
            }

            $insertItem->execute([
                $orderId,
                (int) $dbProduct['id'],
                (string) $dbProduct['title'],
                $qty,
                (int) $dbProduct['price_cents'],
                $qty * (int) $dbProduct['price_cents'],
            ]);
        }

        $pdo->commit();
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $throwable;
    }

    shop_cart_clear();
    return $orderReference;
}

function shop_recent_orders(?int $memberId = null, int $limit = 50): array
{
    if (!table_exists('shop_orders')) {
        return [];
    }

    $sql = 'SELECT o.*, m.callsign
            FROM shop_orders o
            LEFT JOIN members m ON m.id = o.member_id';
    $params = [];
    if ($memberId !== null && $memberId > 0) {
        $sql .= ' WHERE o.member_id = ?';
        $params[] = $memberId;
    }
    $sql .= ' ORDER BY o.created_at DESC, o.id DESC LIMIT ' . max(1, $limit);
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function shop_order_items(int $orderId): array
{
    if (!table_exists('shop_order_items')) {
        return [];
    }
    $stmt = db()->prepare('SELECT * FROM shop_order_items WHERE order_id = ? ORDER BY id ASC');
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

function auction_public_lots(int $limit = 24): array
{
    if (!table_exists('auction_lots')) {
        return [];
    }

    auction_sync_expired_lots();
    $stmt = db()->prepare(
        'SELECT l.*, m.callsign AS winner_callsign
         FROM auction_lots l
         LEFT JOIN members m ON m.id = l.winner_member_id
         WHERE l.status IN ("scheduled","active","closed")
         ORDER BY
            CASE l.status WHEN "active" THEN 1 WHEN "scheduled" THEN 2 ELSE 3 END,
            l.ends_at ASC,
            l.id DESC
         LIMIT ' . max(1, $limit)
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function auction_lot_by_slug(string $slug): ?array
{
    if (!table_exists('auction_lots')) {
        return null;
    }
    auction_sync_expired_lots();
    $stmt = db()->prepare('SELECT l.*, m.callsign AS winner_callsign FROM auction_lots l LEFT JOIN members m ON m.id = l.winner_member_id WHERE l.slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function auction_lot_by_id(int $lotId): ?array
{
    if (!table_exists('auction_lots')) {
        return null;
    }
    auction_sync_expired_lots();
    $stmt = db()->prepare('SELECT l.*, m.callsign AS winner_callsign FROM auction_lots l LEFT JOIN members m ON m.id = l.winner_member_id WHERE l.id = ? LIMIT 1');
    $stmt->execute([$lotId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function auction_bids_for_lot(int $lotId, int $limit = 20): array
{
    if (!table_exists('auction_bids')) {
        return [];
    }
    $stmt = db()->prepare(
        'SELECT b.*, m.callsign, m.full_name
         FROM auction_bids b
         INNER JOIN members m ON m.id = b.member_id
         WHERE b.lot_id = ?
         ORDER BY b.amount_cents DESC, b.created_at ASC
         LIMIT ' . max(1, $limit)
    );
    $stmt->execute([$lotId]);
    return $stmt->fetchAll();
}

function auction_highest_bid(int $lotId): ?array
{
    if (!table_exists('auction_bids')) {
        return null;
    }
    $stmt = db()->prepare(
        'SELECT b.*, m.callsign
         FROM auction_bids b
         INNER JOIN members m ON m.id = b.member_id
         WHERE b.lot_id = ?
         ORDER BY b.amount_cents DESC, b.created_at ASC
         LIMIT 1'
    );
    $stmt->execute([$lotId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function auction_runtime_status(array $lot): string
{
    $status = (string) ($lot['status'] ?? 'draft');
    if (in_array($status, ['cancelled', 'draft'], true)) {
        return $status;
    }

    $now = new DateTimeImmutable('now');
    $startsAt = new DateTimeImmutable((string) $lot['starts_at']);
    $endsAt = new DateTimeImmutable((string) $lot['ends_at']);
    if ($status !== 'closed' && $now >= $endsAt) {
        return 'closed';
    }
    if ($now < $startsAt) {
        return 'scheduled';
    }

    return $status === 'closed' ? 'closed' : 'active';
}

function auction_minimum_bid_cents(array $lot): int
{
    $current = max((int) ($lot['current_price_cents'] ?? 0), (int) ($lot['starting_price_cents'] ?? 0));
    $hasBids = ((int) ($lot['current_price_cents'] ?? 0)) > 0;
    if (!$hasBids) {
        return max(0, (int) ($lot['starting_price_cents'] ?? 0));
    }

    return $current + max(1, (int) ($lot['min_increment_cents'] ?? 100));
}

function auction_sync_expired_lots(): void
{
    if (!table_exists('auction_lots')) {
        return;
    }

    $rows = db()->query('SELECT id FROM auction_lots WHERE status IN ("scheduled","active") AND ends_at <= NOW()')->fetchAll();
    if ($rows === []) {
        return;
    }

    $update = db()->prepare('UPDATE auction_lots SET status = "closed", winner_member_id = ?, current_price_cents = ? WHERE id = ?');
    foreach ($rows as $row) {
        $lotId = (int) $row['id'];
        $highestBid = auction_highest_bid($lotId);
        $winnerId = $highestBid ? (int) $highestBid['member_id'] : null;
        $currentPrice = $highestBid ? (int) $highestBid['amount_cents'] : 0;
        $update->execute([$winnerId, $currentPrice, $lotId]);
    }
}

function place_auction_bid(int $lotId, int $memberId, int $amountCents): void
{
    $lot = auction_lot_by_id($lotId);
    if ($lot === null) {
        throw new RuntimeException('Lot introuvable.');
    }

    $status = auction_runtime_status($lot);
    if ($status !== 'active') {
        throw new RuntimeException('Cette enchère n’est pas active.');
    }

    $minimum = auction_minimum_bid_cents($lot);
    if ($amountCents < $minimum) {
        throw new RuntimeException('Le montant minimum pour enchérir est ' . format_price_eur($minimum) . '.');
    }

    $pdo = db();
    $insertBid = $pdo->prepare('INSERT INTO auction_bids (lot_id, member_id, amount_cents) VALUES (?, ?, ?)');
    $updateLot = $pdo->prepare('UPDATE auction_lots SET current_price_cents = ?, status = "active", winner_member_id = NULL, extended_until = ?, ends_at = ? WHERE id = ?');

    $now = new DateTimeImmutable('now');
    $endsAt = new DateTimeImmutable((string) $lot['ends_at']);
    $extension = null;
    if ($endsAt->getTimestamp() - $now->getTimestamp() <= 300) {
        $extension = $endsAt->modify('+5 minutes')->format('Y-m-d H:i:s');
    }

    $pdo->beginTransaction();
    try {
        $insertBid->execute([$lotId, $memberId, $amountCents]);
        $newEnd = $extension ?? (string) $lot['ends_at'];
        $updateLot->execute([$amountCents, $extension, $newEnd, $lotId]);

        if (!empty($lot['buy_now_price_cents']) && $amountCents >= (int) $lot['buy_now_price_cents']) {
            $pdo->prepare('UPDATE auction_lots SET status = "closed", winner_member_id = ?, current_price_cents = ? WHERE id = ?')
                ->execute([$memberId, $amountCents, $lotId]);
        }

        $pdo->commit();
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $throwable;
    }
}
