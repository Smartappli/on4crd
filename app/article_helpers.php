<?php
declare(strict_types=1);

/**
 * @param array<string,mixed> $row
 * @return array<string,mixed>
 */
function localized_article_row(array $row): array
{
    $locale = current_locale();
    if ($locale !== 'fr') {
        $articleId = (int) ($row['id'] ?? 0);
        if ($articleId > 0 && table_exists('article_translations')) {
            try {
                $stmt = db()->prepare('SELECT title, excerpt, content FROM article_translations WHERE article_id = ? AND locale = ? ORDER BY CASE status WHEN "reviewed" THEN 0 WHEN "auto" THEN 1 ELSE 2 END, updated_at DESC LIMIT 1');
                $stmt->execute([$articleId, $locale]);
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
                // Keep the source article when translations are unavailable.
            }
        }
    }

    $row['title_localized'] = (string) ($row['title'] ?? '');
    $row['excerpt_localized'] = (string) ($row['excerpt'] ?? '');
    $row['content_localized'] = (string) ($row['content'] ?? '');

    return $row;
}

function article_translation_upsert(int $articleId, string $locale, ?string $title = null, ?string $summary = null, ?string $content = null): void
{
    if ($articleId <= 0 || $locale === 'fr' || !table_exists('article_translations')) {
        return;
    }

    $sourceStmt = db()->prepare('SELECT title, excerpt, content FROM articles WHERE id = ? LIMIT 1');
    $sourceStmt->execute([$articleId]);
    $source = $sourceStmt->fetch();
    if (!is_array($source)) {
        return;
    }

    $finalTitle = trim((string) ($title ?? ''));
    $finalExcerpt = trim((string) ($summary ?? ''));
    $finalContent = trim((string) ($content ?? ''));
    if ($finalTitle === '') {
        $finalTitle = (string) ($source['title'] ?? '');
    }
    if ($finalExcerpt === '') {
        $finalExcerpt = (string) ($source['excerpt'] ?? '');
    }
    if ($finalContent === '') {
        $finalContent = (string) ($source['content'] ?? '');
    }

    $status = ($title === null && $summary === null && $content === null) ? 'auto' : 'needs_review';

    $update = db()->prepare('UPDATE article_translations SET title = ?, excerpt = ?, content = ?, status = ?, updated_at = NOW() WHERE article_id = ? AND locale = ?');
    $update->execute([$finalTitle, $finalExcerpt, $finalContent, $status, $articleId, $locale]);
    if ($update->rowCount() > 0) {
        return;
    }

    db()->prepare('INSERT INTO article_translations (article_id, locale, title, excerpt, content, status) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$articleId, $locale, $finalTitle, $finalExcerpt, $finalContent, $status]);
}
