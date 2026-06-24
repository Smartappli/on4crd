<?php
declare(strict_types=1);

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

function auction_slug_base(string $value, int $maxLength = 190): string
{
    $base = slugify($value);
    if ($base === '' || $base === 'n-a') {
        $base = 'lot';
    }

    return substr($base, 0, $maxLength);
}

function auction_slug_candidate(string $base, int $suffix, int $maxLength = 190): string
{
    if ($suffix <= 1) {
        return substr($base, 0, $maxLength);
    }

    $suffixText = '-' . $suffix;
    $prefixLength = max(1, $maxLength - strlen($suffixText));
    $prefix = rtrim(substr($base, 0, $prefixLength), '-');
    if ($prefix === '') {
        $prefix = substr('lot', 0, $prefixLength);
    }

    return $prefix . $suffixText;
}

function auction_unique_slug(string $value, int $ignoreId = 0, int $maxLength = 190): string
{
    $base = auction_slug_base($value, $maxLength);
    $suffix = 1;

    do {
        $candidate = auction_slug_candidate($base, $suffix, $maxLength);
        $stmt = db()->prepare('SELECT id FROM auction_lots WHERE slug = ? AND id <> ? LIMIT 1');
        $stmt->execute([$candidate, max(0, $ignoreId)]);
        if (!$stmt->fetchColumn()) {
            return $candidate;
        }
        $suffix++;
    } while ($suffix < 10000);

    throw new RuntimeException('Unable to generate a unique auction slug.');
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
    try {
        $startsAt = new DateTimeImmutable((string) $lot['starts_at']);
        $endsAt = new DateTimeImmutable((string) $lot['ends_at']);
    } catch (Throwable) {
        return 'draft';
    }

    if ($endsAt <= $startsAt) {
        return 'draft';
    }
    if ($status !== 'closed' && $now >= $endsAt) {
        return 'closed';
    }
    if ($now < $startsAt) {
        return 'scheduled';
    }

    return $status === 'closed' ? 'closed' : 'active';
}

function auction_status_label(string $status, ?string $locale = null): string
{
    $messages = i18n_domain_locale('auctions', $locale ?? current_locale());
    $key = 'status_' . $status;

    return (string) ($messages[$key] ?? ucfirst($status));
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


function auction_reserve_met(array $lot, int $highestBidCents): bool
{
    $reserve = (int) ($lot['reserve_price_cents'] ?? 0);
    if ($reserve <= 0) {
        return true;
    }

    return $highestBidCents >= $reserve;
}

function auction_sync_expired_lots(): void
{
    if (!table_exists('auction_lots')) {
        return;
    }

    $rows = db()->query('SELECT id, reserve_price_cents FROM auction_lots WHERE status IN ("scheduled","active") AND ends_at <= NOW()')->fetchAll();
    if ($rows === []) {
        return;
    }

    $update = db()->prepare('UPDATE auction_lots SET status = "closed", winner_member_id = ?, current_price_cents = ? WHERE id = ?');
    foreach ($rows as $row) {
        $lotId = (int) $row['id'];
        $highestBid = auction_highest_bid($lotId);
        $currentPrice = $highestBid ? (int) $highestBid['amount_cents'] : 0;
        $reserveMet = auction_reserve_met($row, $currentPrice);
        $winnerId = ($highestBid && $reserveMet) ? (int) $highestBid['member_id'] : null;
        $update->execute([$winnerId, $currentPrice, $lotId]);
    }
}

function place_auction_bid(int $lotId, int $memberId, int $amountCents): void
{
    $locale = current_locale();
    $messages = i18n_domain_locale('auction_bid', $locale);
    $tr = static fn(string $key): string => (string) $messages[$key];

    $pdo = db();
    $insertBid = $pdo->prepare('INSERT INTO auction_bids (lot_id, member_id, amount_cents) VALUES (?, ?, ?)');
    $lockLot = $pdo->prepare('SELECT * FROM auction_lots WHERE id = ? LIMIT 1 FOR UPDATE');
    $updateLot = $pdo->prepare(
        'UPDATE auction_lots SET current_price_cents = ?, status = "active", winner_member_id = NULL, extended_until = ?, ends_at = ? WHERE id = ? AND current_price_cents <= ?'
    );

    $pdo->beginTransaction();
    try {
        $lockLot->execute([$lotId]);
        $lot = $lockLot->fetch();
        if (!$lot) {
            throw new RuntimeException($tr('lot_not_found'));
        }

        $status = auction_runtime_status($lot);
        if ($status !== 'active') {
            throw new RuntimeException($tr('auction_not_active'));
        }

        $minimum = auction_minimum_bid_cents($lot);
        if ($amountCents < $minimum) {
            throw new RuntimeException(strtr($tr('min_bid_required'), ['{amount}' => format_price_eur($minimum)]));
        }

        $now = new DateTimeImmutable('now');
        $endsAt = new DateTimeImmutable((string) $lot['ends_at']);
        $extension = null;
        if ($endsAt->getTimestamp() - $now->getTimestamp() <= 300) {
            $extension = $endsAt->modify('+5 minutes')->format('Y-m-d H:i:s');
        }

        $insertBid->execute([$lotId, $memberId, $amountCents]);
        $newEnd = $extension ?? (string) $lot['ends_at'];
        $updateLot->execute([$amountCents, $extension, $newEnd, $lotId, (int) $lot['current_price_cents']]);
        if ($updateLot->rowCount() === 0) {
            throw new RuntimeException($tr('concurrency_conflict'));
        }

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
