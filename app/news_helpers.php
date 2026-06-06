<?php
declare(strict_types=1);

function news_status_label(string $status): string
{
    return match ($status) {
        'draft' => 'Brouillon',
        'pending' => 'En attente',
        'published' => 'Publié',
        'archived' => 'Archivé',
        default => ucfirst($status),
    };
}

/**
 * @return int[]
 */
function managed_section_ids_for_member(int $memberId): array
{
    if ($memberId <= 0 || !table_exists('news_section_managers')) {
        return [];
    }
    $stmt = db()->prepare('SELECT section_id FROM news_section_managers WHERE member_id = ?');
    $stmt->execute([$memberId]);
    return array_values(array_unique(array_map('intval', array_column($stmt->fetchAll() ?: [], 'section_id'))));
}

function can_submit_news_in_section(int|array $user, int $sectionId): bool
{
    if ($sectionId <= 0) {
        return false;
    }
    if (is_array($user) && ((int) ($user['is_admin'] ?? 0) === 1)) {
        return true;
    }
    $memberId = is_array($user) ? (int) ($user['id'] ?? 0) : (int) $user;
    if ($memberId <= 0) {
        return false;
    }
    return in_array($sectionId, managed_section_ids_for_member($memberId), true);
}

function news_slug_base(string $value, int $maxLength = 190): string
{
    $maxLength = max(1, $maxLength);
    $base = slugify($value);
    if ($base === '' || $base === 'n-a') {
        $base = 'news';
    }
    if (strlen($base) > $maxLength) {
        $base = substr($base, 0, $maxLength);
    }

    $base = trim($base, '-');
    return $base !== '' ? $base : 'news';
}

function news_slug_candidate(string $base, int $suffix = 0, int $maxLength = 190): string
{
    $maxLength = max(1, $maxLength);
    $base = news_slug_base($base, $maxLength);
    if ($suffix <= 1) {
        return $base;
    }

    $suffixText = '-' . $suffix;
    $prefixLength = max(1, $maxLength - strlen($suffixText));
    $prefix = rtrim(substr($base, 0, $prefixLength), '-');
    if ($prefix === '') {
        $prefix = substr('news', 0, $prefixLength);
    }

    return $prefix . $suffixText;
}

function news_unique_slug(string $value, int $ignoreId = 0, int $maxLength = 190): string
{
    $base = news_slug_base($value, $maxLength);
    $suffix = 1;
    do {
        $candidate = news_slug_candidate($base, $suffix, $maxLength);
        $stmt = db()->prepare('SELECT id FROM news_posts WHERE slug = ? AND id <> ? LIMIT 1');
        $stmt->execute([$candidate, max(0, $ignoreId)]);
        if (!$stmt->fetchColumn()) {
            return $candidate;
        }
        $suffix++;
    } while ($suffix < 10000);

    throw new RuntimeException('Impossible de générer un slug actualité unique.');
}

function news_default_section_id(): int
{
    if (!table_exists('news_sections')) {
        return 0;
    }

    try {
        $stmt = db()->prepare('SELECT id FROM news_sections WHERE slug = ? LIMIT 1');
        $stmt->execute(['on4crd']);
        $sectionId = (int) ($stmt->fetchColumn() ?: 0);
        if ($sectionId > 0) {
            return $sectionId;
        }

        $sectionId = (int) (db()->query('SELECT id FROM news_sections ORDER BY sort_order ASC, id ASC LIMIT 1')->fetchColumn() ?: 0);
        if ($sectionId > 0) {
            return $sectionId;
        }

        db()->prepare('INSERT INTO news_sections (slug, name, sort_order) VALUES (?, ?, ?)')
            ->execute(['on4crd', 'ON4CRD', 10]);

        return (int) db()->lastInsertId();
    } catch (Throwable) {
        return 0;
    }
}

function can_edit_news_post(array $post, int $memberId): bool
{
    if ($memberId <= 0) {
        return false;
    }
    if (has_permission('news.moderate')) {
        return true;
    }

    $authorId = (int) ($post['author_id'] ?? 0);
    $sectionId = (int) ($post['section_id'] ?? 0);
    return $authorId === $memberId || can_submit_news_in_section($memberId, $sectionId);
}

function news_translation_upsert(int $newsId, string $locale, ?string $title = null, ?string $summary = null, ?string $content = null): void
{
    if ($newsId <= 0 || $locale === 'fr' || !table_exists('news_translations')) {
        return;
    }
    $src = db()->prepare('SELECT title, excerpt AS summary, content FROM news_posts WHERE id = ? LIMIT 1');
    $src->execute([$newsId]);
    $row = $src->fetch();
    if (!is_array($row)) {
        return;
    }
    $finalTitle = trim((string) ($title ?? '')) ?: (string) ($row['title'] ?? '');
    $finalSummary = trim((string) ($summary ?? '')) ?: (string) ($row['summary'] ?? '');
    $finalContent = trim((string) ($content ?? '')) ?: (string) ($row['content'] ?? '');
    $status = ($title === null && $summary === null && $content === null) ? 'auto' : 'needs_review';

    $update = db()->prepare('UPDATE news_translations SET title = ?, summary = ?, content = ?, status = ?, updated_at = NOW() WHERE news_id = ? AND locale = ?');
    $update->execute([$finalTitle, $finalSummary, $finalContent, $status, $newsId, $locale]);
    if ($update->rowCount() > 0) {
        return;
    }
    db()->prepare('INSERT INTO news_translations (news_id, locale, title, summary, content, status) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$newsId, $locale, $finalTitle, $finalSummary, $finalContent, $status]);
}
