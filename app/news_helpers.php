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
