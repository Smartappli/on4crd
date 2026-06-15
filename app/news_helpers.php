<?php
declare(strict_types=1);

require_once __DIR__ . '/article_helpers.php';

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

/**
 * @param array<string,mixed> $row
 * @return array<string,mixed>
 */
function localized_news_row(array $row): array
{
    $locale = current_locale();
    if ($locale !== 'fr') {
        $newsId = (int) ($row['id'] ?? 0);
        if ($newsId > 0 && table_exists('news_translations')) {
            try {
                $publicStatuses = news_translation_public_statuses();
                $statusPlaceholders = implode(',', array_fill(0, count($publicStatuses), '?'));
                $stmt = db()->prepare('SELECT title, excerpt, content FROM news_translations WHERE news_post_id = ? AND locale = ? AND status IN (' . $statusPlaceholders . ') ORDER BY CASE status WHEN "reviewed" THEN 0 ELSE 1 END, updated_at DESC LIMIT 1');
                $stmt->execute(array_merge([$newsId, $locale], $publicStatuses));
                $translation = $stmt->fetch();
                if (is_array($translation)) {
                    foreach (['title', 'excerpt', 'content'] as $field) {
                        $value = trim((string) ($translation[$field] ?? ''));
                        if ($value !== '') {
                            $row[$field] = $value;
                        }
                    }
                }
            } catch (Throwable) {
                // Keep the source news item when translations are unavailable.
            }
        }
    }

    $row['title_localized'] = (string) ($row['title'] ?? '');
    $row['excerpt_localized'] = (string) ($row['excerpt'] ?? '');
    $row['content_localized'] = (string) ($row['content'] ?? '');

    return $row;
}

function news_translation_source_hash(string $title, string $excerpt, string $content): string
{
    return article_translation_source_hash($title, $excerpt, $content);
}

/**
 * @return list<string>
 */
function news_translation_public_statuses(): array
{
    return article_translation_public_statuses();
}

/**
 * @return list<string>
 */
function news_translation_target_locales(): array
{
    return article_translation_target_locales();
}

function news_translation_deepl_target(string $locale): ?string
{
    return article_translation_deepl_target($locale);
}

/**
 * @param array<string,mixed> $existing
 * @param array{title:string,excerpt:string,content:string} $source
 */
function news_translation_pending_row_is_source_fallback(array $existing, array $source): bool
{
    return article_translation_pending_row_is_source_fallback($existing, $source);
}

/**
 * @param array{title:string,excerpt:string,content:string} $source
 * @return array{title:string,excerpt:string,content:string,status:string}
 */
function news_translation_auto_fields(array $source, string $locale): array
{
    $sourceTitle = (string) ($source['title'] ?? '');
    $sourceExcerpt = (string) ($source['excerpt'] ?? '');
    $sourceContent = (string) ($source['content'] ?? '');
    $translated = article_translation_deepl_translate([$sourceTitle, $sourceExcerpt, $sourceContent], $locale);
    if (is_array($translated)) {
        return [
            'title' => trim($translated[0]) !== '' ? trim($translated[0]) : $sourceTitle,
            'excerpt' => trim($translated[1]) !== '' ? trim($translated[1]) : $sourceExcerpt,
            'content' => trim($translated[2]) !== '' ? article_sanitize_content($translated[2]) : $sourceContent,
            'status' => 'auto',
        ];
    }

    return [
        'title' => $sourceTitle,
        'excerpt' => $sourceExcerpt,
        'content' => $sourceContent,
        'status' => 'needs_review',
    ];
}

function news_translation_upsert(int $newsId, string $locale, ?string $title = null, ?string $summary = null, ?string $content = null): void
{
    if ($newsId <= 0 || $locale === 'fr' || !table_exists('news_translations')) {
        return;
    }

    $sourceStmt = db()->prepare('SELECT title, excerpt, content FROM news_posts WHERE id = ? LIMIT 1');
    $sourceStmt->execute([$newsId]);
    $source = $sourceStmt->fetch();
    if (!is_array($source)) {
        return;
    }

    $sourceFields = [
        'title' => (string) ($source['title'] ?? ''),
        'excerpt' => (string) ($source['excerpt'] ?? ''),
        'content' => (string) ($source['content'] ?? ''),
    ];
    $sourceHash = news_translation_source_hash($sourceFields['title'], $sourceFields['excerpt'], $sourceFields['content']);

    $existingStmt = db()->prepare('SELECT status, source_hash, title, excerpt, content FROM news_translations WHERE news_post_id = ? AND locale = ? LIMIT 1');
    $existingStmt->execute([$newsId, $locale]);
    $existing = $existingStmt->fetch() ?: null;
    if ($title === null && $summary === null && $content === null && is_array($existing) && (string) ($existing['source_hash'] ?? '') === $sourceHash) {
        $existingStatus = (string) ($existing['status'] ?? '');
        if (in_array($existingStatus, ['reviewed', 'auto'], true)) {
            return;
        }
        if ($existingStatus === 'needs_review' && !news_translation_pending_row_is_source_fallback($existing, $sourceFields)) {
            return;
        }
    }

    if ($title === null && $summary === null && $content === null) {
        $fields = news_translation_auto_fields($sourceFields, $locale);
    } else {
        $fields = [
            'title' => trim((string) ($title ?? '')) !== '' ? trim((string) $title) : $sourceFields['title'],
            'excerpt' => trim((string) ($summary ?? '')) !== '' ? trim((string) $summary) : $sourceFields['excerpt'],
            'content' => trim((string) ($content ?? '')) !== '' ? article_sanitize_content((string) $content) : $sourceFields['content'],
            'status' => 'needs_review',
        ];
    }

    $update = db()->prepare('UPDATE news_translations SET source_hash = ?, title = ?, excerpt = ?, content = ?, status = ?, reviewed_by = NULL, reviewed_at = NULL, updated_at = NOW() WHERE news_post_id = ? AND locale = ?');
    $update->execute([$sourceHash, $fields['title'], $fields['excerpt'], $fields['content'], $fields['status'], $newsId, $locale]);
    if ($update->rowCount() > 0) {
        return;
    }

    db()->prepare('INSERT INTO news_translations (news_post_id, locale, source_hash, title, excerpt, content, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$newsId, $locale, $sourceHash, $fields['title'], $fields['excerpt'], $fields['content'], $fields['status']]);
}

function news_translations_sync_all(int $newsId): int
{
    $count = 0;
    foreach (news_translation_target_locales() as $locale) {
        news_translation_upsert($newsId, $locale);
        $count++;
    }

    return $count;
}
