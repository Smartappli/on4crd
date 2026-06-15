<?php
declare(strict_types=1);

require_permission('articles.manage');
$locale = current_locale();
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/admin_articles.php');
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key, string $fallback = '') use ($locale, $i18n): string {
    $value = i18n_localized_value($i18n, $locale, $key);
    return trim($value) !== '' && $value !== $key ? $value : $fallback;
};
set_page_meta([
    'title' => $t('layout'),
    'description' => $t('meta_desc'),
    'schema_type' => 'WebPage',
]);
articles_sync_scheduled_publications();
$previewPayload = null;

/**
 * @return array{excerpt:string,content:string}
 */
function import_article_document(array $file, bool $persist = true): array
{
    $locale = current_locale();
    $tm = i18n_domain_translator('admin_articles_import', $locale);
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['excerpt' => '', 'content' => ''];
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException($tm('upload_failed'));
    }

    $originalName = trim((string) ($file['name'] ?? 'document'));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'docx', 'txt', 'md', 'html', 'htm'];
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException($tm('allowed_formats'));
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 25 * 1024 * 1024) {
        throw new RuntimeException($tm('upload_failed'));
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException($tm('invalid_doc'));
    }

    $allowedMimesByExtension = [
        'pdf' => ['application/pdf', 'application/x-pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'txt' => ['text/plain', 'application/octet-stream'],
        'md' => ['text/plain', 'text/markdown', 'application/octet-stream'],
        'html' => ['text/html', 'text/plain', 'application/octet-stream'],
        'htm' => ['text/html', 'text/plain', 'application/octet-stream'],
    ];
    $mime = detect_uploaded_mime_type($tmpPath);
    if (!in_array($mime, $allowedMimesByExtension[$extension] ?? [], true)) {
        throw new RuntimeException($tm('invalid_doc'));
    }
    if ($extension === 'pdf' || $extension === 'docx') {
        assert_upload_file_is_valid_signature($tmpPath, [$extension]);
    }
    if ($extension === 'docx') {
        article_assert_docx_document($tmpPath);
    }

    $absolutePath = $tmpPath;
    if ($persist) {
        $targetDir = __DIR__ . '/../storage/private/articles';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new RuntimeException($tm('create_dir'));
        }

        $basename = slugify(pathinfo($originalName, PATHINFO_FILENAME));
        if ($basename === '') {
            $basename = 'article';
        }
        $filename = $basename . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $absolutePath = $targetDir . '/' . $filename;
        if (!move_uploaded_file($tmpPath, $absolutePath)) {
            throw new RuntimeException($tm('save_doc'));
        }
    }

    $documentTitle = trim((string) pathinfo($originalName, PATHINFO_FILENAME));
    $sourceLabel = '<p class="help article-source-document">' . e($tm('source_file')) . ': ' . e($originalName) . '</p>';
    if (in_array($extension, ['txt', 'md'], true)) {
        $rawText = (string) file_get_contents($absolutePath);
        $content = article_import_text_to_html($rawText);
    } elseif (in_array($extension, ['html', 'htm'], true)) {
        $rawHtml = (string) file_get_contents($absolutePath);
        $content = article_sanitize_content($rawHtml);
    } elseif ($extension === 'docx') {
        $content = article_extract_docx_html($absolutePath);
        if ($content === '') {
            $content = '<div class="article-document"><p>' . e($tm('docx_extraction_unavailable')) . '</p></div>';
        }
    } elseif ($extension === 'pdf') {
        $rawText = article_extract_pdf_text($absolutePath);
        $content = article_import_text_to_html($rawText);
        if ($content === '') {
            $content = '<div class="article-document"><p>' . e($tm('pdf_extraction_unavailable')) . '</p></div>';
        }
    } else {
        $content = '';
    }
    $content = trim($content);
    if ($content !== '') {
        $content .= "\n" . $sourceLabel;
    }
    $content = article_sanitize_content($content);

    return [
        'excerpt' => $tm('imported_doc') . ' ' . $documentTitle,
        'content' => $content,
    ];
}

function article_assert_docx_document(string $path): void
{
    $locale = current_locale();
    $tm = i18n_domain_translator('admin_articles_import', $locale);
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException($tm('invalid_doc'));
        }
        $hasContentTypes = $zip->locateName('[Content_Types].xml') !== false;
        $hasDocument = $zip->locateName('word/document.xml') !== false;
        $zip->close();
        if (!$hasContentTypes || !$hasDocument) {
            throw new RuntimeException($tm('invalid_doc'));
        }
    }
}

/**
 * @param array<string,mixed> $article
 * @return list<string>
 */
function editorial_blocked_reasons(array $article): array
{
    return editorial_blocked_reasons_from_article($article);
}

function editorial_retry_scheduled_article(int $id): string
{
    if ($id <= 0) {
        return 'invalid';
    }
    $stmt = db()->prepare('SELECT id, title, content, status, scheduled_at, published_at FROM articles WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $article = $stmt->fetch() ?: null;
    if (!is_array($article)) {
        return 'missing';
    }
    $reasons = editorial_blocked_reasons($article);
    if (in_array('missing_title', $reasons, true) || in_array('missing_content', $reasons, true)) {
        return 'blocked_missing_fields';
    }
    if (in_array('stuck_in_past_schedule', $reasons, true)) {
        db()->prepare('UPDATE articles SET status = "published", published_at = COALESCE(published_at, NOW()), updated_at = NOW() WHERE id = ?')->execute([$id]);
        return 'published';
    }
    if (in_array('missing_schedule_date', $reasons, true) || in_array('invalid_schedule_date', $reasons, true)) {
        db()->prepare('UPDATE articles SET status = "scheduled", scheduled_at = ?, updated_at = NOW() WHERE id = ?')
            ->execute([date('Y-m-d H:i:s', time() + 3600), $id]);
        return 'rescheduled';
    }
    articles_sync_scheduled_publications();
    return 'retried';
}

$defaultCategories = [
    'antennes' => $t('cat_antennes'),
    'trafic' => $t('cat_trafic'),
    'numerique' => $t('cat_numerique'),
    'materiel' => $t('cat_materiel'),
    'formation' => $t('cat_formation'),
    'autres' => $t('cat_autres'),
];

$existingCategoryRows = db()->query('SELECT DISTINCT category FROM articles WHERE category IS NOT NULL AND category <> "" ORDER BY category ASC')->fetchAll();
$knownCategories = $defaultCategories;
foreach ($existingCategoryRows as $existingCategoryRow) {
    $code = trim((string) ($existingCategoryRow['category'] ?? ''));
    if ($code !== '' && !isset($knownCategories[$code])) {
        $knownCategories[$code] = ucwords(str_replace('-', ' ', $code));
    }
}
foreach (content_proposal_accepted_categories('articles', 120) as $code => $label) {
    if ($code !== '' && !isset($knownCategories[$code])) {
        $knownCategories[$code] = $label;
    }
}

$articleStatusChoices = [
    'draft' => $t('draft', 'Brouillon'),
    'pending' => $t('pending', 'En validation'),
    'scheduled' => $t('scheduled', 'Programme'),
    'published' => $t('published', 'Publie'),
    'rejected' => $t('rejected', 'Refuse'),
];
$articleStatusLabel = static fn(string $status): string => $articleStatusChoices[$status] ?? $status;
$pendingProposalUrl = route_url_clean('admin_articles', ['status' => 'pending']) . '#pending-proposals';
$proposalStatusLabels = [
    'pending' => $t('proposal_status_pending', 'En attente'),
    'reviewed' => $t('proposal_status_reviewed', 'Relue'),
    'accepted' => $t('proposal_status_accepted', 'Acceptée'),
    'rejected' => $t('proposal_status_rejected', 'Refusée'),
];
$proposalTypeLabels = [
    'category' => $t('proposal_type_category', 'Thématique'),
    'content' => $t('proposal_type_content', 'Article'),
    'tag' => $t('proposal_type_tag', 'Mot clé'),
];
$editingDefault = ['id' => 0, 'title' => '', 'slug' => '', 'excerpt' => '', 'content' => '<p></p>', 'status' => 'draft', 'category' => 'autres', 'scheduled_at' => null, 'moderation_note' => null];
$editing = $editingDefault;
$editingId = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'update_proposal_status') {
            $proposalId = (int) ($_POST['proposal_id'] ?? 0);
            $proposalStatus = (string) ($_POST['proposal_status'] ?? 'pending');
            $moderationNote = trim((string) ($_POST['moderation_note'] ?? ''));
            if ($proposalId <= 0 || !isset($proposalStatusLabels[$proposalStatus])) {
                throw new RuntimeException($t('err_invalid_proposal', 'Proposition invalide.'));
            }
            if (!ensure_content_proposals_table()) {
                throw new RuntimeException($t('module_unavailable', 'Stockage indisponible.'));
            }
            db()->prepare('UPDATE content_proposals SET status = ?, moderation_note = ? WHERE id = ? AND area = "articles"')
                ->execute([$proposalStatus, $moderationNote !== '' ? $moderationNote : null, $proposalId]);
            set_flash('success', $t('proposal_status_saved', 'Proposition mise à jour.'));
            redirect_url($pendingProposalUrl);
        }

        if ($action === 'bulk_update_articles') {
            $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])), static fn(int $v): bool => $v > 0));
            if ($ids === []) {
                throw new RuntimeException($t('err_invalid_article', 'Article invalide.'));
            }
            $bulkOp = (string) ($_POST['bulk_op'] ?? '');
            $allowedOps = array_merge(array_keys($articleStatusChoices), ['delete']);
            if (!in_array($bulkOp, $allowedOps, true)) {
                throw new RuntimeException($t('err_invalid_article', 'Article invalide.'));
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            if ($bulkOp === 'delete') {
                if (table_exists('article_translations')) {
                    db()->prepare('DELETE FROM article_translations WHERE article_id IN (' . $placeholders . ')')->execute($ids);
                }
                if (table_exists('article_revisions')) {
                    db()->prepare('DELETE FROM article_revisions WHERE article_id IN (' . $placeholders . ')')->execute($ids);
                }
                db()->prepare('DELETE FROM articles WHERE id IN (' . $placeholders . ')')->execute($ids);
                set_flash('success', $t('ok_deleted', 'Article supprimé.'));
            } else {
                $bulkRowsStmt = db()->prepare('SELECT id, title, slug, author_id FROM articles WHERE id IN (' . $placeholders . ')');
                $bulkRowsStmt->execute($ids);
                $bulkRows = $bulkRowsStmt->fetchAll() ?: [];
                $scheduledAt = $bulkOp === 'scheduled' ? date('Y-m-d H:i:s', time() + 3600) : null;
                $moderationNote = $bulkOp === 'rejected' ? 'Refuse par moderation.' : null;
                $publishedAtSql = $bulkOp === 'published' ? 'COALESCE(published_at, NOW())' : 'NULL';
                db()->prepare('UPDATE articles SET status = ?, scheduled_at = ?, published_at = ' . $publishedAtSql . ', moderation_note = ?, updated_at = NOW() WHERE id IN (' . $placeholders . ')')
                    ->execute(array_merge([$bulkOp, $scheduledAt, $moderationNote], $ids));
                $translationSyncFailed = false;
                $currentUserId = (int) current_user()['id'];
                foreach ($bulkRows as $bulkRow) {
                    $articleId = (int) ($bulkRow['id'] ?? 0);
                    if ($articleId > 0 && in_array($bulkOp, ['published', 'scheduled'], true)) {
                        try {
                            article_translations_sync_all($articleId);
                        } catch (Throwable) {
                            $translationSyncFailed = true;
                        }
                    }
                    $authorId = isset($bulkRow['author_id']) ? (int) $bulkRow['author_id'] : 0;
                    if ($authorId <= 0 || $authorId === $currentUserId) {
                        continue;
                    }
                    $articleTitle = (string) ($bulkRow['title'] ?? '');
                    $articleSlug = (string) ($bulkRow['slug'] ?? '');
                    if ($bulkOp === 'published') {
                        notify_member($authorId, 'publication', 'Article publie', $articleTitle, route_url('article', ['slug' => $articleSlug]));
                    } elseif ($bulkOp === 'scheduled') {
                        notify_member($authorId, 'publication', 'Article planifie', $articleTitle, route_url('my_requests'));
                    } elseif ($bulkOp === 'rejected') {
                        notify_member($authorId, 'moderation', 'Article refuse', $moderationNote, route_url('my_requests'));
                    }
                }
                if ($translationSyncFailed) {
                    set_flash('warning', 'Certains articles sont enregistres, mais leurs traductions automatiques devront etre relancees.');
                }
                set_flash('success', $t('ok_saved'));
            }
            redirect_url(route_url_clean('admin_articles', ['q' => (string) ($_GET['q'] ?? ''), 'status' => (string) ($_GET['status'] ?? ''), 'category' => (string) ($_GET['category'] ?? ''), 'p' => max(1, (int) ($_GET['p'] ?? 1))]));
        }

        if ($action === 'save_article' || $action === 'preview_article') {
            $id = (int) ($_POST['id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $slugInput = trim((string) ($_POST['slug'] ?? ''));
            $slugSource = $slugInput !== '' ? $slugInput : $title;
            $slug = article_unique_slug($slugSource, $id);
            $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
            $content = article_sanitize_content((string) ($_POST['content'] ?? ''));
            $status = (string) ($_POST['status'] ?? 'draft');
            $moderationNote = trim((string) ($_POST['moderation_note'] ?? ''));
            $categoryChoice = trim((string) ($_POST['category'] ?? 'autres'));
            $customCategory = slugify(trim((string) ($_POST['category_custom'] ?? '')));
            $category = $categoryChoice === '__custom__' ? $customCategory : slugify($categoryChoice);
            if ($category === '') {
                $category = 'autres';
            }
            if ($title === '' || !isset($articleStatusChoices[$status])) {
                throw new RuntimeException($t('err_invalid_article', 'Article invalide.'));
            }
            $scheduledAtRaw = trim((string) ($_POST['scheduled_at'] ?? ''));
            $scheduledAtValue = null;
            $publishedAtValue = null;
            $publishNow = false;
            if ($status === 'scheduled') {
                if ($scheduledAtRaw === '') {
                    throw new RuntimeException($t('err_invalid_article', 'Date de planification requise.'));
                }
                $scheduledTs = strtotime($scheduledAtRaw);
                if ($scheduledTs === false) {
                    throw new RuntimeException($t('err_invalid_article', 'Date de planification invalide.'));
                }
                if ($scheduledTs <= time()) {
                    $status = 'published';
                    $publishNow = true;
                } else {
                    $scheduledAtValue = date('Y-m-d H:i:s', $scheduledTs);
                }
            } elseif ($status === 'published') {
                $publishNow = true;
            }
            $moderationNoteValue = null;
            if ($status === 'rejected') {
                $moderationNoteValue = $moderationNote !== '' ? $moderationNote : 'Refuse par moderation.';
            }
            $imported = import_article_document($_FILES['article_document'] ?? [], $action !== 'preview_article');
            if ($imported['content'] !== '') {
                $content = $imported['content'];
                if ($excerpt === '') {
                    $excerpt = $imported['excerpt'];
                }
            }
            if (
                mb_strlen($title) > 190
                || mb_strlen($slugInput) > 190
                || mb_strlen($excerpt) > 2000
                || mb_strlen($content) > 50000
                || mb_strlen($category) > 120
            ) {
                throw new RuntimeException($t('err_invalid_article', 'Un des champs dépasse la longueur autorisée.'));
            }
            if ($action === 'preview_article') {
                $previewPayload = [
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'content' => $content,
                    'status' => $status,
                    'category' => $category,
                    'scheduled_at' => $scheduledAtValue,
                    'moderation_note' => $moderationNoteValue,
                ];
                $editing = array_merge((array) $editing, [
                    'id' => $id,
                    'title' => $title,
                    'slug' => $slug,
                    'excerpt' => $excerpt,
                    'content' => $content,
                    'status' => $status,
                    'category' => $category,
                    'scheduled_at' => $scheduledAtValue,
                    'moderation_note' => $moderationNoteValue,
                ]);
            } else {
                $notifyStatus = $status;
                $authorId = (int) current_user()['id'];
                $maxSlugAttempts = 5;
                for ($slugAttempt = 0; $slugAttempt < $maxSlugAttempts; $slugAttempt++) {
                    $slug = article_unique_slug($slugSource, $id);
                    try {
                        db()->beginTransaction();
                        if ($id > 0) {
                            $previousStmt = db()->prepare('SELECT title, slug, excerpt, content, status, category, scheduled_at, published_at, author_id FROM articles WHERE id = ? LIMIT 1');
                            $previousStmt->execute([$id]);
                            $previous = $previousStmt->fetch() ?: null;
                            if (!is_array($previous)) {
                                throw new RuntimeException($t('err_invalid_article', 'Article invalide.'));
                            }
                            $authorId = isset($previous['author_id']) ? (int) $previous['author_id'] : 0;
                            $existingPublishedAt = trim((string) ($previous['published_at'] ?? ''));
                            $publishedAtValue = $publishNow ? ($existingPublishedAt !== '' ? $existingPublishedAt : date('Y-m-d H:i:s')) : null;
                            if (table_exists('article_revisions')) {
                                db()->prepare('INSERT INTO article_revisions (article_id, title, slug, excerpt, content, status, category, scheduled_at, published_at, author_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                                    ->execute([
                                        $id,
                                        (string) ($previous['title'] ?? ''),
                                        (string) ($previous['slug'] ?? ''),
                                        (string) ($previous['excerpt'] ?? ''),
                                        (string) ($previous['content'] ?? ''),
                                        (string) ($previous['status'] ?? 'draft'),
                                        (string) ($previous['category'] ?? 'autres'),
                                        $previous['scheduled_at'] ?? null,
                                        $previous['published_at'] ?? null,
                                        isset($previous['author_id']) ? (int) $previous['author_id'] : null,
                                    ]);
                            }
                            db()->prepare('UPDATE articles SET title = ?, slug = ?, excerpt = ?, content = ?, status = ?, category = ?, scheduled_at = ?, published_at = ?, moderation_note = ?, updated_at = NOW() WHERE id = ?')
                                ->execute([$title, $slug, $excerpt, $content, $status, $category, $scheduledAtValue, $publishedAtValue, $moderationNoteValue, $id]);
                        } else {
                            $publishedAtValue = $publishNow ? date('Y-m-d H:i:s') : null;
                            db()->prepare('INSERT INTO articles (title, slug, excerpt, content, status, category, scheduled_at, published_at, moderation_note, author_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                                ->execute([$title, $slug, $excerpt, $content, $status, $category, $scheduledAtValue, $publishedAtValue, $moderationNoteValue, (int) current_user()['id']]);
                            $id = (int) db()->lastInsertId();
                            if (table_exists('article_revisions')) {
                                db()->prepare('INSERT INTO article_revisions (article_id, title, slug, excerpt, content, status, category, scheduled_at, published_at, author_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                                    ->execute([$id, $title, $slug, $excerpt, $content, $status, $category, $scheduledAtValue, $publishedAtValue, (int) current_user()['id']]);
                            }
                        }
                        db()->commit();
                        break;
                    } catch (Throwable $saveThrowable) {
                        if (db()->inTransaction()) {
                            db()->rollBack();
                        }
                        if (!article_is_duplicate_slug_error($saveThrowable) || $slugAttempt === $maxSlugAttempts - 1) {
                            throw $saveThrowable;
                        }
                        usleep(20000 * ($slugAttempt + 1));
                    }
                }

                if (in_array($notifyStatus, ['published', 'scheduled'], true)) {
                    try {
                        article_translations_sync_all($id);
                    } catch (Throwable) {
                        set_flash('warning', 'Article enregistre, mais les traductions automatiques devront etre relancees.');
                    }
                }

                $currentUserId = (int) current_user()['id'];
                if ($authorId > 0 && $authorId !== $currentUserId) {
                    if ($notifyStatus === 'published') {
                        notify_member($authorId, 'publication', 'Article publie', $title, route_url('article', ['slug' => $slug]));
                    } elseif ($notifyStatus === 'scheduled') {
                        notify_member($authorId, 'publication', 'Article planifie', $title, route_url('my_requests'));
                    } elseif ($notifyStatus === 'rejected') {
                        notify_member($authorId, 'moderation', 'Article refuse', $moderationNoteValue ?? $title, route_url('my_requests'));
                    }
                }

                set_flash('success', $t('ok_saved'));
                redirect('admin_articles');
            }
        } elseif ($action === 'delete_article') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new RuntimeException($t('err_invalid_article', 'Article invalide.'));
            }
            if (table_exists('article_translations')) {
                db()->prepare('DELETE FROM article_translations WHERE article_id = ?')->execute([$id]);
            }
            if (table_exists('article_revisions')) {
                db()->prepare('DELETE FROM article_revisions WHERE article_id = ?')->execute([$id]);
            }
            db()->prepare('DELETE FROM articles WHERE id = ?')->execute([$id]);
            set_flash('success', $t('ok_deleted', 'Article supprimé.'));
            redirect('admin_articles');
        } elseif ($action === 'restore_revision') {
            $articleId = (int) ($_POST['article_id'] ?? 0);
            $revisionId = (int) ($_POST['revision_id'] ?? 0);
            if ($articleId <= 0 || $revisionId <= 0 || !table_exists('article_revisions')) {
                throw new RuntimeException($t('err_invalid_article', 'Article invalide.'));
            }
            $revStmt = db()->prepare('SELECT * FROM article_revisions WHERE id = ? AND article_id = ? LIMIT 1');
            $revStmt->execute([$revisionId, $articleId]);
            $revision = $revStmt->fetch() ?: null;
            if (!is_array($revision)) {
                throw new RuntimeException($t('err_invalid_article', 'Article invalide.'));
            }
            $restoredStatus = (string) ($revision['status'] ?? 'draft');
            db()->prepare('UPDATE articles SET title = ?, slug = ?, excerpt = ?, content = ?, status = ?, category = ?, scheduled_at = ?, published_at = ?, moderation_note = NULL, updated_at = NOW() WHERE id = ?')
                ->execute([
                    (string) ($revision['title'] ?? ''),
                    (string) ($revision['slug'] ?? ''),
                    (string) ($revision['excerpt'] ?? ''),
                    (string) ($revision['content'] ?? ''),
                    $restoredStatus,
                    (string) ($revision['category'] ?? 'autres'),
                    $revision['scheduled_at'] ?? null,
                    $revision['published_at'] ?? null,
                    $articleId,
                ]);
            if (in_array($restoredStatus, ['published', 'scheduled'], true)) {
                try {
                    article_translations_sync_all($articleId);
                } catch (Throwable) {
                    set_flash('warning', 'Version restauree, mais les traductions automatiques devront etre relancees.');
                }
            }
            set_flash('success', $t('ok_revision_restored', 'Version restaurée.'));
            redirect_url(route_url('admin_articles', ['id' => $articleId]));
        } elseif ($action === 'save_category') {
            $oldCode = slugify(trim((string) ($_POST['old_code'] ?? '')));
            $newCode = slugify(trim((string) ($_POST['new_code'] ?? '')));
            if ($oldCode === '' || $newCode === '') {
                throw new RuntimeException($t('err_invalid_category'));
            }
            db()->prepare('UPDATE articles SET category = ? WHERE category = ?')->execute([$newCode, $oldCode]);
            set_flash('success', $t('ok_category_updated'));
            redirect('admin_articles');
        } elseif ($action === 'delete_category') {
            $code = slugify(trim((string) ($_POST['code'] ?? '')));
            if ($code === '' || $code === 'autres') {
                throw new RuntimeException($t('err_delete_category'));
            }
            db()->prepare('UPDATE articles SET category = "autres" WHERE category = ?')->execute([$code]);
            set_flash('success', $t('ok_category_deleted'));
            redirect('admin_articles');
        } elseif ($action === 'retry_scheduled_article') {
            $id = (int) ($_POST['id'] ?? 0);
            $result = editorial_retry_scheduled_article($id);
            if ($result === 'blocked_missing_fields') {
                throw new RuntimeException($t('retry_blocked_missing_fields'));
            }
            if ($result === 'invalid' || $result === 'missing') {
                throw new RuntimeException($t('err_invalid_article', 'Article invalide.'));
            }
            if ($result === 'published') {
                set_flash('success', $t('retry_applied_published'));
            } elseif ($result === 'rescheduled') {
                set_flash('success', $t('retry_applied_rescheduled'));
            } else {
                set_flash('success', $t('retry_applied'));
            }
            redirect('admin_articles');
        } elseif ($action === 'retry_scheduled_bulk') {
            $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])), static fn(int $v): bool => $v > 0));
            if ($ids === []) {
                throw new RuntimeException($t('err_invalid_article', 'Article invalide.'));
            }
            $counts = ['published' => 0, 'rescheduled' => 0, 'retried' => 0, 'blocked_missing_fields' => 0, 'missing' => 0, 'invalid' => 0];
            foreach ($ids as $id) {
                $result = editorial_retry_scheduled_article($id);
                $counts[$result] = ($counts[$result] ?? 0) + 1;
            }
            $message = sprintf(
                $t('retry_bulk_summary'),
                (int) $counts['published'],
                (int) $counts['rescheduled'],
                (int) $counts['retried'],
                (int) $counts['blocked_missing_fields']
            );
            set_flash('success', $message);
            redirect('admin_articles');
        }
    } catch (Throwable $throwable) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        set_flash('error', $throwable->getMessage());
        redirect('admin_articles');
    }
}

$adminStatus = (string) ($_GET['status'] ?? '');
$adminCategory = slugify(trim((string) ($_GET['category'] ?? '')));
$adminSearch = trim((string) ($_GET['q'] ?? ''));
$adminWhere = [];
$adminParams = [];
if (isset($articleStatusChoices[$adminStatus])) {
    $adminWhere[] = 'status = ?';
    $adminParams[] = $adminStatus;
}
if ($adminCategory !== '') {
    $adminWhere[] = 'category = ?';
    $adminParams[] = $adminCategory;
}
if ($adminSearch !== '') {
    $adminWhere[] = '(title LIKE ? OR excerpt LIKE ? OR content LIKE ?)';
    $needle = '%' . $adminSearch . '%';
    $adminParams[] = $needle;
    $adminParams[] = $needle;
    $adminParams[] = $needle;
}
$adminWhereSql = $adminWhere === [] ? '' : ('WHERE ' . implode(' AND ', $adminWhere));
$page = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 30;
$countStmt = db()->prepare('SELECT COUNT(*) FROM articles ' . $adminWhereSql);
$countStmt->execute($adminParams);
$totalArticles = (int) ($countStmt->fetchColumn() ?: 0);
$pagination = pagination_state($totalArticles, $page, $perPage);
$page = $pagination['page'];
$totalPages = $pagination['total_pages'];
$offset = $pagination['offset'];
$articleStmt = db()->prepare('SELECT * FROM articles ' . $adminWhereSql . ' ORDER BY updated_at DESC, id DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);
$articleStmt->execute($adminParams);
$articles = $articleStmt->fetchAll() ?: [];
$articleStats = db()->query('SELECT status, COUNT(*) AS total FROM articles GROUP BY status')->fetchAll() ?: [];
$articleStatMap = array_fill_keys(array_keys($articleStatusChoices), 0);
foreach ($articleStats as $statRow) {
    $articleStatMap[(string) $statRow['status']] = (int) $statRow['total'];
}
if ($previewPayload === null) {
    $editingId = (int) ($_GET['id'] ?? 0);
    $editing = $editingDefault;
    if ($editingId > 0) {
        $stmt = db()->prepare('SELECT * FROM articles WHERE id = ?');
        $stmt->execute([$editingId]);
        $editing = $stmt->fetch() ?: $editing;
    }
} else {
    $editingId = (int) ($editing['id'] ?? 0);
}
$revisions = [];
if ($editingId > 0 && table_exists('article_revisions')) {
    $revisionStmt = db()->prepare('SELECT id, created_at, status FROM article_revisions WHERE article_id = ? ORDER BY created_at DESC, id DESC LIMIT 20');
    $revisionStmt->execute([$editingId]);
    $revisions = $revisionStmt->fetchAll() ?: [];
}
$scheduledQueue = db()->query('SELECT id, title, slug, status, scheduled_at, updated_at, content FROM articles WHERE status = "scheduled" ORDER BY scheduled_at ASC, updated_at DESC LIMIT 50')->fetchAll() ?: [];
$showPendingProposals = $adminStatus === 'pending';
$pendingProposals = [];
if ($showPendingProposals && ensure_content_proposals_table()) {
    $pendingStmt = db()->prepare(
        'SELECT cp.id, cp.member_id, cp.proposal_type, cp.title, cp.summary, cp.contact, cp.source_ref, cp.status, cp.moderation_note, cp.created_at, cp.updated_at, m.callsign, m.email
         FROM content_proposals cp
         LEFT JOIN members m ON m.id = cp.member_id
         WHERE cp.area = "articles" AND cp.status = "pending"
         ORDER BY cp.created_at ASC, cp.id ASC'
    );
    $pendingStmt->execute();
    $pendingProposals = $pendingStmt->fetchAll() ?: [];
}

ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1><?= $editingId > 0 ? e($t('edit')) : e($t('create')) ?> <?= e($t('an_article')) ?></h1>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_article">
            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
            <label><?= e($t('title')) ?><input type="text" name="title" value="<?= e((string) $editing['title']) ?>" required></label>
            <label><?= e($t('slug')) ?><input type="text" name="slug" value="<?= e((string) $editing['slug']) ?>" placeholder="<?= e($t('slug_placeholder', 'genere-depuis-le-titre')) ?>"></label>
            <label><?= e($t('category')) ?>
                <select name="category" id="article-category">
                    <?php $editingCategory = (string) ($editing['category'] ?? 'autres'); ?>
                    <?php foreach ($knownCategories as $categoryCode => $categoryLabel): ?>
                        <option value="<?= e($categoryCode) ?>" <?= $editingCategory === $categoryCode ? 'selected' : '' ?>><?= e($categoryLabel) ?></option>
                    <?php endforeach; ?>
                    <option value="__custom__"><?= e($t('new_category')) ?></option>
                </select>
            </label>
            <label id="article-category-custom" hidden><?= e($t('new_category_id')) ?>
                <input type="text" name="category_custom" value="" placeholder="<?= e($t('custom_category_ph')) ?>">
            </label>
            <label><?= e($t('import_document')) ?><input type="file" name="article_document" accept=".pdf,.docx,.txt,.md,.html,.htm,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,text/markdown,text/html"></label>
            <label><?= e($t('excerpt')) ?><textarea name="excerpt" rows="4"><?= e((string) $editing['excerpt']) ?></textarea></label>
            <label><?= e($t('content_simple_html')) ?><textarea name="content" rows="16"><?= e((string) $editing['content']) ?></textarea></label>
            <label><?= e($t('status')) ?>
                <select name="status">
                    <?php foreach ($articleStatusChoices as $statusCode => $statusLabel): ?>
                        <option value="<?= e($statusCode) ?>" <?= (string) $editing['status'] === $statusCode ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e($t('scheduled_at', 'Date de publication')) ?><input type="datetime-local" name="scheduled_at" value="<?= !empty($editing['scheduled_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $editing['scheduled_at']))) : '' ?>"></label>
            <label><?= e($t('moderation_note', 'Note de moderation')) ?><textarea name="moderation_note" rows="3" placeholder="<?= e($t('moderation_note_help', 'Visible par le proposant si l article est refuse.')) ?>"><?= e((string) ($editing['moderation_note'] ?? '')) ?></textarea></label>
            <button class="button"><?= e($t('save')) ?></button>
            <button class="button secondary" type="submit" name="action" value="preview_article"><?= e($t('preview', 'Prévisualiser')) ?></button>
        </form>
        <?php if ((int) $editing['id'] > 0): ?>
            <form method="post" style="margin-top:1rem;" onsubmit="return confirm('<?= e($t('confirm_delete', 'Supprimer cet article ?')) ?>');">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete_article">
                <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                <button class="button secondary" type="submit"><?= e($t('delete_article', 'Supprimer l’article')) ?></button>
            </form>
            <section style="margin-top:1rem;">
                <h3><?= e($t('revisions', 'Historique des versions')) ?></h3>
                <?php if ($revisions === []): ?>
                    <p class="help"><?= e($t('no_revisions', 'Aucune révision enregistrée.')) ?></p>
                <?php else: ?>
                    <div class="stack">
                        <?php foreach ($revisions as $revision): ?>
                            <form method="post" class="row-between" style="gap:.6rem;align-items:center;">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="restore_revision">
                                <input type="hidden" name="article_id" value="<?= (int) $editing['id'] ?>">
                                <input type="hidden" name="revision_id" value="<?= (int) $revision['id'] ?>">
                                <span class="help"><?= e($t('revision_saved_at', 'Version du')) ?> <?= e(date('d/m/Y H:i', strtotime((string) $revision['created_at']))) ?> · <?= e($articleStatusLabel((string) $revision['status'])) ?></span>
                                <button class="button small secondary" type="submit" onclick="return confirm('<?= e($t('confirm_restore_revision', 'Restaurer cette version ?')) ?>');"><?= e($t('restore_revision', 'Restaurer')) ?></button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </section>
    <section class="card">
        <h2><?= e($t('preview', 'Prévisualiser')) ?></h2>
        <p class="help"><?= e($t('preview_help', 'Vérifiez le rendu avant publication.')) ?></p>
        <?php if ($previewPayload === null): ?>
            <p class="help"><?= e($t('preview_empty', 'La prévisualisation apparaît après avoir cliqué sur “Prévisualiser”.')) ?></p>
        <?php else: ?>
            <article class="feature-card" style="margin:0;">
                <h3><?= e((string) ($previewPayload['title'] ?? '')) ?></h3>
                <?php if (trim((string) ($previewPayload['excerpt'] ?? '')) !== ''): ?>
                    <p><?= e((string) $previewPayload['excerpt']) ?></p>
                <?php endif; ?>
                <p class="help"><?= e($articleStatusLabel((string) ($previewPayload['status'] ?? 'draft'))) ?><?php if (!empty($previewPayload['scheduled_at'])): ?> · <?= e(date('d/m/Y H:i', strtotime((string) $previewPayload['scheduled_at']))) ?><?php endif; ?></p>
                <div><?= article_sanitize_content((string) ($previewPayload['content'] ?? '')) ?></div>
            </article>
        <?php endif; ?>
    </section>
    <section class="card">
        <h2><?= e($t('editorial_queue', 'Editorial queue')) ?></h2>
        <p class="help"><?= e($t('editorial_queue_help', 'Scheduled board with blocking reasons and one-click retry.')) ?></p>
        <?php if ($scheduledQueue === []): ?>
            <p class="help"><?= e($t('editorial_queue_empty', 'No scheduled articles in queue.')) ?></p>
        <?php else: ?>
            <form method="post" style="margin:0 0 .8rem 0;">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="retry_scheduled_bulk">
                <button class="button secondary small" type="submit"><?= e($t('retry_bulk', 'Retry selected')) ?></button>
            <div class="stack">
                <?php foreach ($scheduledQueue as $queueItem): ?>
                    <?php $blockedReasons = editorial_blocked_reasons((array) $queueItem); ?>
                    <article class="article-item">
                        <div class="row-between" style="gap:.8rem;align-items:flex-start;">
                            <div>
                                <p style="margin:0 0 .25rem 0;">
                                    <label style="display:inline-flex;align-items:center;gap:.35rem;">
                                        <input type="checkbox" name="ids[]" value="<?= (int) $queueItem['id'] ?>">
                                        <span class="help">#<?= (int) $queueItem['id'] ?></span>
                                    </label>
                                </p>
                                <h3 style="margin:.1rem 0;"><?= e((string) (($queueItem['title'] ?? '') !== '' ? $queueItem['title'] : ('#' . (int) $queueItem['id']))) ?></h3>
                                <p class="help" style="margin:0;">
                                    <?= e($t('scheduled_at', 'Date de publication')) ?>:
                                    <?= e((string) (($queueItem['scheduled_at'] ?? '') !== '' ? date('d/m/Y H:i', strtotime((string) $queueItem['scheduled_at'])) : 'n/a')) ?>
                                </p>
                                <?php if ($blockedReasons !== []): ?>
                                    <p class="help" style="margin:.3rem 0 0 0;color:#b54708;">
                                        <?= e($t('blocked_reasons')) ?>: <?= e(implode(', ', $blockedReasons)) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="actions">
                                <a class="button small secondary" href="<?= e(route_url('admin_articles', ['id' => (int) $queueItem['id']])) ?>"><?= e($t('edit')) ?></a>
                                <form method="post">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="retry_scheduled_article">
                                    <input type="hidden" name="id" value="<?= (int) $queueItem['id'] ?>">
                                    <button class="button small" type="submit"><?= e($t('retry', 'Retry')) ?></button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            </form>
        <?php endif; ?>
    </section>
    <?php if ($showPendingProposals): ?>
    <section class="card" id="pending-proposals" aria-labelledby="pending-proposals-title">
        <div class="row-between">
            <h2 id="pending-proposals-title"><?= e($t('pending_proposals_title', 'Contenus en attente de validation')) ?></h2>
            <a class="button secondary small" href="<?= e(route_url('admin_articles')) ?>"><?= e($t('reset_filter', 'Réinitialiser')) ?></a>
        </div>
        <?php if ($pendingProposals === []): ?>
            <p class="help"><?= e($t('pending_proposals_empty', 'Aucune proposition articles en attente de validation.')) ?></p>
        <?php endif; ?>
        <div class="stack">
            <?php foreach ($pendingProposals as $proposal): ?>
                <?php
                $proposalType = (string) ($proposal['proposal_type'] ?? 'content');
                $proposalStatus = (string) ($proposal['status'] ?? 'pending');
                $memberLabel = trim((string) ($proposal['callsign'] ?? ''));
                if ($memberLabel === '') {
                    $memberLabel = trim((string) ($proposal['email'] ?? ''));
                }
                if ($memberLabel === '') {
                    $memberLabel = '#' . (int) ($proposal['member_id'] ?? 0);
                }
                $proposalCreatedTimestamp = strtotime((string) ($proposal['created_at'] ?? 'now'));
                if ($proposalCreatedTimestamp === false) {
                    $proposalCreatedTimestamp = time();
                }
                ?>
                <article class="article-item">
                    <p>
                        <span class="badge muted"><?= e((string) ($proposalTypeLabels[$proposalType] ?? $proposalType)) ?></span>
                        <span class="badge muted"><?= e((string) ($proposalStatusLabels[$proposalStatus] ?? $proposalStatus)) ?></span>
                        <span class="badge muted"><?= e(date('d/m/Y H:i', $proposalCreatedTimestamp)) ?></span>
                    </p>
                    <h3><?= e((string) ($proposal['title'] ?? $t('proposal_default_title', 'Proposition'))) ?></h3>
                    <p class="help"><?= e($t('proposal_author', 'Proposé par')) ?>: <?= e($memberLabel) ?></p>
                    <?php if (trim((string) ($proposal['summary'] ?? '')) !== ''): ?>
                        <p><?= nl2br(e((string) $proposal['summary'])) ?></p>
                    <?php endif; ?>
                    <?php if (trim((string) ($proposal['contact'] ?? '')) !== ''): ?>
                        <p class="help"><?= e($t('proposal_contact', 'Contact')) ?>: <?= e((string) $proposal['contact']) ?></p>
                    <?php endif; ?>
                    <form method="post" class="stack">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_proposal_status">
                        <input type="hidden" name="proposal_id" value="<?= (int) ($proposal['id'] ?? 0) ?>">
                        <div class="grid-2">
                            <label><?= e($t('proposal_status_label', 'Statut')) ?>
                                <select name="proposal_status">
                                    <?php foreach ($proposalStatusLabels as $statusCode => $statusLabel): ?>
                                        <option value="<?= e($statusCode) ?>" <?= $proposalStatus === $statusCode ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label><?= e($t('proposal_moderation_note', 'Note de modération')) ?>
                                <textarea name="moderation_note" rows="3"><?= e((string) ($proposal['moderation_note'] ?? '')) ?></textarea>
                            </label>
                        </div>
                        <p><button class="button small" type="submit"><?= e($t('proposal_save_status', 'Enregistrer le statut')) ?></button></p>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    <section class="card">
        <div class="row-between">
            <h2><?= e($t('existing_articles')) ?></h2>
            <span class="badge"><?php $statusStats = []; foreach (['pending', 'draft', 'scheduled', 'published', 'rejected'] as $statusCode) { $statusStats[] = $articleStatusLabel($statusCode) . ': ' . (int) ($articleStatMap[$statusCode] ?? 0); } echo e(implode(' · ', $statusStats)); ?></span>
        </div>
        <form method="get" class="stack">
            <input type="hidden" name="route" value="admin_articles">
            <div class="grid-3">
                <label><?= e($t('search', 'Recherche')) ?><input type="search" name="q" value="<?= e($adminSearch) ?>"></label>
                <label><?= e($t('status')) ?>
                    <select name="status">
                        <option value=""><?= e($t('all_statuses', 'Tous les statuts')) ?></option>
                        <?php foreach ($articleStatusChoices as $statusCode => $statusLabel): ?>
                            <option value="<?= e($statusCode) ?>" <?= $adminStatus === $statusCode ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><?= e($t('category')) ?>
                    <select name="category">
                        <option value=""><?= e($t('all_categories', 'Toutes les catégories')) ?></option>
                        <?php foreach ($knownCategories as $categoryCode => $categoryLabel): ?>
                            <option value="<?= e($categoryCode) ?>" <?= $adminCategory === $categoryCode ? 'selected' : '' ?>><?= e($categoryLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <p><button class="button" type="submit"><?= e($t('filter', 'Filtrer')) ?></button> <a class="button secondary" href="<?= e(route_url('admin_articles')) ?>"><?= e($t('reset_filter', 'Réinitialiser')) ?></a></p>
        </form>
        <div class="stack">
            <?php foreach ($articles as $article): ?>
                <article class="article-item">
                    <div class="row-between"><h3><?= e((string) $article['title']) ?></h3><a class="button small" href="<?= e(route_url('admin_articles', ['id' => (int) $article['id']])) ?>"><?= e($t('edit')) ?></a></div>
                    <p><strong><?= e($t('category_label')) ?></strong>  <?= e((string) ($knownCategories[(string) ($article['category'] ?? '')] ?? ($article['category'] ?? 'autres'))) ?> · <span class="badge muted"><?= e($articleStatusLabel((string) $article['status'])) ?></span></p>
                    <p><?= e((string) $article['excerpt']) ?></p>
                </article>
            <?php endforeach; ?>
            <?php if ($articles === []): ?><p><?= e($t('no_articles')) ?></p><?php endif; ?>
        </div>
        <?php if ($totalPages > 1): ?>
            <nav class="actions mt-3">
                <?php if ($page > 1): ?><a class="button secondary" href="<?= e(route_url_clean('admin_articles', ['q' => $adminSearch, 'status' => $adminStatus, 'category' => $adminCategory, 'p' => $page - 1])) ?>">&larr; Prev</a><?php endif; ?>
                <span class="badge muted"><?= $page ?> / <?= $totalPages ?></span>
                <?php if ($page < $totalPages): ?><a class="button secondary" href="<?= e(route_url_clean('admin_articles', ['q' => $adminSearch, 'status' => $adminStatus, 'category' => $adminCategory, 'p' => $page + 1])) ?>">Next &rarr;</a><?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>
    <section class="card">
        <h2><?= e($t('category_edit')) ?></h2>
        <div class="stack">
            <?php foreach ($knownCategories as $categoryCode => $categoryLabel): ?>
                <article class="article-item">
                    <form method="post" class="row-between" style="gap:8px;align-items:end;">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="save_category">
                        <input type="hidden" name="old_code" value="<?= e($categoryCode) ?>">
                        <label style="flex:1;"><?= e($t('code')) ?>
                            <input type="text" name="new_code" value="<?= e($categoryCode) ?>" required>
                        </label>
                        <label style="flex:2;"><?= e($t('label')) ?>
                            <input type="text" value="<?= e($categoryLabel) ?>" disabled>
                        </label>
                        <button class="button small" type="submit"><?= e($t('rename_code')) ?></button>
                    </form>
                    <?php if ($categoryCode !== 'autres'): ?>
                        <form method="post" style="margin-top:8px;">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete_category">
                            <input type="hidden" name="code" value="<?= e($categoryCode) ?>">
                            <button class="button small secondary" type="submit"><?= e($t('delete_to_other')) ?></button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            <?php if ($articles === []): ?><p><?= e($t('no_articles')) ?></p><?php endif; ?>
        </div>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), $t('layout'));
