<?php
declare(strict_types=1);

require_permission('articles.manage');
$locale = current_locale();
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/admin_articles.php');
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    $value = i18n_localized_value($i18n, $locale, $key);
    return trim($value) !== '' && $value !== $key ? $value : $key;
};
set_page_meta([
    'title' => $t('layout'),
    'description' => $t('meta_desc'),
    'schema_type' => 'WebPage',
]);
articles_sync_scheduled_publications();
$articleMessages = i18n_domain_locale('articles', $locale);
article_ensure_taxonomy_schema($articleMessages);
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
        'excerpt' => '',
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
        return;
    }

    if (!function_exists('article_docx_part_contents')
        || article_docx_part_contents($path, '[Content_Types].xml') === ''
        || article_docx_part_contents($path, 'word/document.xml') === ''
    ) {
        throw new RuntimeException($tm('invalid_doc'));
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

function admin_articles_forget_public_caches(): void
{
    if (!function_exists('cache_forget')) {
        return;
    }

    cache_forget('articles_theme_counts_v2');
    cache_forget('home_latest_article_v2');
}

$knownCategories = article_categories($articleMessages);
$articleSubcategoriesByCategory = article_subcategories_by_category();
$articleSubsubcategoriesByParent = article_subsubcategories_by_parent();

$articleStatusChoices = [
    'draft' => $t('draft'),
    'pending' => $t('pending'),
    'scheduled' => $t('scheduled'),
    'published' => $t('published'),
    'rejected' => $t('rejected'),
];
$articleStatusLabel = static fn(string $status): string => $articleStatusChoices[$status] ?? $status;
$pendingProposalUrl = route_url_clean('admin_articles', ['status' => 'pending']) . '#pending-proposals';
$proposalStatusLabels = [
    'pending' => $t('proposal_status_pending'),
    'reviewed' => $t('proposal_status_reviewed'),
    'accepted' => $t('proposal_status_accepted'),
    'rejected' => $t('proposal_status_rejected'),
];
$proposalTypeLabels = [
    'category' => $t('proposal_type_category'),
    'content' => $t('proposal_type_content'),
    'subcategory' => $t('proposal_type_subcategory'),
    'subsubcategory' => $t('proposal_type_subsubcategory'),
    'tag' => $t('proposal_type_tag'),
];
$articleFieldLimits = [
    'title' => 190,
    'slug' => 190,
    'excerpt' => 2000,
    'content' => 5000000,
    'taxonomy' => 120,
];
$editingDefault = ['id' => 0, 'title' => '', 'slug' => '', 'excerpt' => '', 'content' => '<p></p>', 'status' => 'published', 'category' => 'autres', 'subcategory' => '', 'subsubcategory' => '', 'scheduled_at' => null, 'moderation_note' => null];
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
                throw new RuntimeException($t('err_invalid_proposal'));
            }
            if (!ensure_content_proposals_table()) {
                throw new RuntimeException($t('module_unavailable'));
            }
            db()->prepare('UPDATE content_proposals SET status = ?, moderation_note = ? WHERE id = ? AND area = "articles"')
                ->execute([$proposalStatus, $moderationNote !== '' ? $moderationNote : null, $proposalId]);
            set_flash('success', $t('proposal_status_saved'));
            redirect_url($pendingProposalUrl);
        }

        if ($action === 'bulk_update_articles') {
            $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])), static fn(int $v): bool => $v > 0));
            if ($ids === []) {
                throw new RuntimeException($t('error_field_too_long'));
            }
            $bulkOp = (string) ($_POST['bulk_op'] ?? '');
            $allowedOps = array_merge(array_keys($articleStatusChoices), ['delete']);
            if (!in_array($bulkOp, $allowedOps, true)) {
                throw new RuntimeException($t('err_invalid_article'));
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            if ($bulkOp === 'delete') {
                if (table_exists('article_translations')) {
                    db()->prepare('DELETE FROM article_translations WHERE article_id IN (' . $placeholders . ')')->execute($ids);
                }
                if (table_exists('article_revisions')) {
                    db()->prepare('DELETE FROM article_revisions WHERE article_id IN (' . $placeholders . ')')->execute($ids);
                }
                if (table_exists('member_favorites')) {
                    db()->prepare('DELETE FROM member_favorites WHERE target_type = ? AND target_id IN (' . $placeholders . ')')->execute(array_merge(['article'], $ids));
                }
                db()->prepare('DELETE FROM articles WHERE id IN (' . $placeholders . ')')->execute($ids);
                admin_articles_forget_public_caches();
                set_flash('success', $t('ok_deleted'));
            } else {
                $bulkRowsStmt = db()->prepare('SELECT id, title, slug, author_id FROM articles WHERE id IN (' . $placeholders . ')');
                $bulkRowsStmt->execute($ids);
                $bulkRows = $bulkRowsStmt->fetchAll() ?: [];
                $scheduledAt = null;
                if ($bulkOp === 'scheduled') {
                    $bulkScheduledAtRaw = trim((string) ($_POST['scheduled_at'] ?? ''));
                    $bulkScheduledTs = $bulkScheduledAtRaw !== '' ? strtotime($bulkScheduledAtRaw) : (time() + 3600);
                    if ($bulkScheduledTs === false) {
                        throw new RuntimeException($t('err_invalid_article'));
                    }
                    if ($bulkScheduledTs <= time()) {
                        $bulkOp = 'published';
                    } else {
                        $scheduledAt = date('Y-m-d H:i:s', $bulkScheduledTs);
                    }
                }
                $submittedModerationNote = trim((string) ($_POST['moderation_note'] ?? ''));
                $moderationNote = $bulkOp === 'rejected'
                    ? ($submittedModerationNote !== '' ? $submittedModerationNote : $t('moderation_note_rejected_default'))
                    : null;
                $publishedAtSql = $bulkOp === 'published' ? 'COALESCE(published_at, NOW())' : 'NULL';
                db()->prepare('UPDATE articles SET status = ?, scheduled_at = ?, published_at = ' . $publishedAtSql . ', moderation_note = ?, updated_at = NOW() WHERE id IN (' . $placeholders . ')')
                    ->execute(array_merge([$bulkOp, $scheduledAt, $moderationNote], $ids));
                admin_articles_forget_public_caches();
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
                        notify_member($authorId, 'publication', $t('notification_article_published'), $articleTitle, route_url('article', ['slug' => $articleSlug]));
                    } elseif ($bulkOp === 'scheduled') {
                        notify_member($authorId, 'publication', $t('notification_article_scheduled'), $articleTitle, route_url('my_requests'));
                    } elseif ($bulkOp === 'rejected') {
                        notify_member($authorId, 'moderation', $t('notification_article_rejected'), $moderationNote, route_url('my_requests'));
                    }
                }
                if ($translationSyncFailed) {
                    set_flash('warning', $t('warning_translation_sync_bulk'));
                }
                set_flash('success', $t('ok_saved'));
                $returnId = (int) ($_POST['return_id'] ?? 0);
                if ($returnId > 0 && count($ids) === 1 && in_array($returnId, $ids, true)) {
                    redirect_url(route_url('admin_articles', ['id' => $returnId]));
                }
            }
            redirect_url(route_url_clean('admin_articles', ['q' => (string) ($_GET['q'] ?? ''), 'status' => (string) ($_GET['status'] ?? ''), 'category' => (string) ($_GET['category'] ?? ''), 'subcategory' => (string) ($_GET['subcategory'] ?? ''), 'subsubcategory' => (string) ($_GET['subsubcategory'] ?? ''), 'p' => max(1, (int) ($_GET['p'] ?? 1))]));
        }

        if ($action === 'add_category') {
            if (!article_ensure_categories_table($articleMessages)) {
                throw new RuntimeException($t('module_unavailable'));
            }
            $label = content_proposal_clean_single_line((string) ($_POST['category_label'] ?? ''), 160);
            $codeInput = trim((string) ($_POST['category_code'] ?? ''));
            $code = article_category_code($codeInput !== '' ? $codeInput : $label);
            if ($label === '' || $code === '') {
                throw new RuntimeException($t('err_invalid_category'));
            }
            db()->prepare('INSERT INTO article_categories (code, label, deleted_at) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
                ->execute([$code, $label]);
            set_flash('success', $t('ok_category_updated'));
            redirect_url(route_url_clean('admin_articles', ['category' => $code]));
        }

        if ($action === 'update_category') {
            if (!article_ensure_categories_table($articleMessages)) {
                throw new RuntimeException($t('module_unavailable'));
            }
            $category = article_category_from_input((string) ($_POST['category_code'] ?? $_POST['code'] ?? ''), $knownCategories);
            $label = content_proposal_clean_single_line((string) ($_POST['category_label'] ?? ''), 160);
            if ($label === '') {
                throw new RuntimeException($t('err_invalid_category'));
            }
            db()->prepare('INSERT INTO article_categories (code, label, deleted_at) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
                ->execute([$category, $label]);
            set_flash('success', $t('ok_category_updated'));
            redirect_url(route_url_clean('admin_articles', ['category' => $category]));
        }

        if ($action === 'delete_category') {
            if (!article_ensure_categories_table($articleMessages) || !article_ensure_subcategories_table() || !article_ensure_subsubcategories_table()) {
                throw new RuntimeException($t('module_unavailable'));
            }
            $category = article_category_from_input((string) ($_POST['category_code'] ?? $_POST['code'] ?? ''), $knownCategories);
            if ($category === 'autres') {
                throw new RuntimeException($t('err_delete_category'));
            }
            $subCountStmt = db()->prepare('SELECT COUNT(*) FROM article_subcategories WHERE category_code = ?');
            $subCountStmt->execute([$category]);
            if ((int) ($subCountStmt->fetchColumn() ?: 0) > 0) {
                throw new RuntimeException($t('err_category_has_subcategories'));
            }
            db()->prepare('UPDATE articles SET category = "autres", subcategory = "", subsubcategory = "" WHERE category = ?')->execute([$category]);
            db()->prepare('INSERT INTO article_categories (code, label, deleted_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE deleted_at = NOW()')
                ->execute([$category, (string) ($knownCategories[$category] ?? article_category_label_from_code($category))]);
            set_flash('success', $t('ok_category_deleted'));
            redirect('admin_articles');
        }

        if ($action === 'add_subcategory') {
            if (!article_ensure_subcategories_table()) {
                throw new RuntimeException($t('module_unavailable'));
            }
            $category = article_category_from_input((string) ($_POST['subcategory_category'] ?? 'autres'), $knownCategories);
            $label = content_proposal_clean_single_line((string) ($_POST['subcategory_label'] ?? ''), 160);
            $codeInput = trim((string) ($_POST['subcategory_code'] ?? ''));
            $code = article_subcategory_code($codeInput !== '' ? $codeInput : $label);
            if ($label === '' || $code === '') {
                throw new RuntimeException($t('err_invalid_category'));
            }
            db()->prepare('INSERT INTO article_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                ->execute([$category, $code, $label]);
            set_flash('success', $t('ok_subcategory_updated'));
            redirect_url(route_url_clean('admin_articles', ['category' => $category, 'subcategory' => $code]));
        }

        if ($action === 'update_subcategory') {
            if (!article_ensure_subcategories_table()) {
                throw new RuntimeException($t('module_unavailable'));
            }
            $parts = article_subcategory_ref_parts((string) ($_POST['subcategory_ref'] ?? ''));
            $category = article_category_from_input($parts['category'] !== '' ? $parts['category'] : (string) ($_POST['category'] ?? 'autres'), $knownCategories);
            $subcategory = article_subcategory_code($parts['subcategory']);
            $label = content_proposal_clean_single_line((string) ($_POST['subcategory_label'] ?? ''), 160);
            if ($subcategory === '' || $label === '') {
                throw new RuntimeException($t('err_invalid_category'));
            }
            db()->prepare('INSERT INTO article_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                ->execute([$category, $subcategory, $label]);
            set_flash('success', $t('ok_subcategory_updated'));
            redirect_url(route_url_clean('admin_articles', ['category' => $category, 'subcategory' => $subcategory]));
        }

        if ($action === 'delete_subcategory') {
            if (!article_ensure_subcategories_table()) {
                throw new RuntimeException($t('module_unavailable'));
            }
            $parts = article_subcategory_ref_parts((string) ($_POST['subcategory_ref'] ?? ''));
            $category = article_category_from_input($parts['category'] !== '' ? $parts['category'] : (string) ($_POST['category'] ?? 'autres'), $knownCategories);
            $subcategory = article_subcategory_code($parts['subcategory']);
            if ($subcategory === '') {
                throw new RuntimeException($t('err_invalid_category'));
            }
            if (article_ensure_subsubcategories_table()) {
                $subsubcategoryCountStmt = db()->prepare('SELECT COUNT(*) FROM article_subsubcategories WHERE category_code = ? AND subcategory_code = ?');
                $subsubcategoryCountStmt->execute([$category, $subcategory]);
                if ((int) ($subsubcategoryCountStmt->fetchColumn() ?: 0) > 0) {
                    throw new RuntimeException($t('err_subcategory_has_subsubcategories'));
                }
            }
            $countStmt = db()->prepare('SELECT COUNT(*) FROM articles WHERE category = ? AND subcategory = ?');
            $countStmt->execute([$category, $subcategory]);
            if ((int) ($countStmt->fetchColumn() ?: 0) > 0) {
                throw new RuntimeException($t('err_subcategory_has_documents'));
            }
            db()->prepare('DELETE FROM article_subcategories WHERE category_code = ? AND code = ?')->execute([$category, $subcategory]);
            set_flash('success', $t('ok_subcategory_deleted'));
            redirect_url(route_url_clean('admin_articles', ['category' => $category]));
        }

        if ($action === 'add_subsubcategory') {
            if (!article_ensure_subsubcategories_table()) {
                throw new RuntimeException($t('module_unavailable'));
            }
            $parentParts = article_subcategory_ref_parts((string) ($_POST['subsubcategory_parent_ref'] ?? ''));
            $category = article_category_from_input($parentParts['category'] !== '' ? $parentParts['category'] : (string) ($_POST['subsubcategory_category'] ?? 'autres'), $knownCategories);
            $subcategory = article_subcategory_code($parentParts['subcategory'] !== '' ? $parentParts['subcategory'] : (string) ($_POST['subsubcategory_parent'] ?? ''));
            [$category, $subcategory] = article_taxonomy_from_input($category, article_subcategory_ref($category, $subcategory), $knownCategories);
            $label = content_proposal_clean_single_line((string) ($_POST['subsubcategory_label'] ?? ''), 160);
            $codeInput = trim((string) ($_POST['subsubcategory_code'] ?? ''));
            $code = article_subsubcategory_code($codeInput !== '' ? $codeInput : $label);
            if ($subcategory === '' || $label === '' || $code === '') {
                throw new RuntimeException($t('err_invalid_category'));
            }
            db()->prepare('INSERT INTO article_subsubcategories (category_code, subcategory_code, code, label) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                ->execute([$category, $subcategory, $code, $label]);
            set_flash('success', $t('ok_subsubcategory_updated'));
            redirect_url(route_url_clean('admin_articles', ['category' => $category, 'subcategory' => $subcategory, 'subsubcategory' => $code]));
        }

        if ($action === 'update_subsubcategory') {
            if (!article_ensure_subsubcategories_table()) {
                throw new RuntimeException($t('module_unavailable'));
            }
            $category = article_category_from_input((string) ($_POST['subsubcategory_category'] ?? 'autres'), $knownCategories);
            $subcategory = article_subcategory_code((string) ($_POST['subsubcategory_parent'] ?? ''));
            $code = article_subsubcategory_code((string) ($_POST['subsubcategory_code'] ?? ''));
            $label = content_proposal_clean_single_line((string) ($_POST['subsubcategory_label'] ?? ''), 160);
            if ($subcategory === '' || $code === '' || $label === '') {
                throw new RuntimeException($t('err_invalid_category'));
            }
            [$category, $subcategory] = article_taxonomy_from_input($category, article_subcategory_ref($category, $subcategory), $knownCategories);
            db()->prepare('INSERT INTO article_subsubcategories (category_code, subcategory_code, code, label) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                ->execute([$category, $subcategory, $code, $label]);
            set_flash('success', $t('ok_subsubcategory_updated'));
            redirect_url(route_url_clean('admin_articles', ['category' => $category, 'subcategory' => $subcategory, 'subsubcategory' => $code]));
        }

        if ($action === 'delete_subsubcategory') {
            if (!article_ensure_subsubcategories_table()) {
                throw new RuntimeException($t('module_unavailable'));
            }
            $category = article_category_from_input((string) ($_POST['subsubcategory_category'] ?? 'autres'), $knownCategories);
            $subcategory = article_subcategory_code((string) ($_POST['subsubcategory_parent'] ?? ''));
            $code = article_subsubcategory_code((string) ($_POST['subsubcategory_code'] ?? ''));
            if ($subcategory === '' || $code === '') {
                throw new RuntimeException($t('err_invalid_category'));
            }
            [$category, $subcategory] = article_taxonomy_from_input($category, article_subcategory_ref($category, $subcategory), $knownCategories);
            $countStmt = db()->prepare('SELECT COUNT(*) FROM articles WHERE category = ? AND subcategory = ? AND subsubcategory = ?');
            $countStmt->execute([$category, $subcategory, $code]);
            if ((int) ($countStmt->fetchColumn() ?: 0) > 0) {
                throw new RuntimeException($t('err_subsubcategory_has_documents'));
            }
            db()->prepare('DELETE FROM article_subsubcategories WHERE category_code = ? AND subcategory_code = ? AND code = ?')->execute([$category, $subcategory, $code]);
            set_flash('success', $t('ok_subsubcategory_deleted'));
            redirect_url(route_url_clean('admin_articles', ['category' => $category, 'subcategory' => $subcategory]));
        }

        if ($action === 'save_article' || $action === 'preview_article') {
            $id = (int) ($_POST['id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $slugInput = trim((string) ($_POST['slug'] ?? ''));
            $slugSource = $slugInput !== '' ? $slugInput : $title;
            $slug = article_unique_slug($slugSource, $id);
            $excerpt = article_excerpt_from_input((string) ($_POST['excerpt'] ?? ''));
            $content = article_sanitize_content((string) ($_POST['content'] ?? ''));
            $status = (string) ($_POST['status'] ?? 'draft');
            $moderationNote = trim((string) ($_POST['moderation_note'] ?? ''));
            $categoryChoice = trim((string) ($_POST['category'] ?? 'autres'));
            $customCategory = article_category_code(trim((string) ($_POST['category_custom'] ?? '')));
            $category = $categoryChoice === '__custom__' ? $customCategory : article_category_code($categoryChoice);
            if ($category === '') {
                $category = 'autres';
            }
            if ($categoryChoice === '__custom__' && $category !== 'autres' && !isset($knownCategories[$category])) {
                $customLabel = article_category_label_from_code($category);
                db()->prepare('INSERT INTO article_categories (code, label, deleted_at) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
                    ->execute([$category, $customLabel]);
                $knownCategories[$category] = $customLabel;
            }
            if (!isset($knownCategories[$category])) {
                throw new RuntimeException($t('err_invalid_category'));
            }
            [$category, $subcategory, $subsubcategory] = article_taxonomy_from_input(
                $category,
                trim((string) ($_POST['subcategory_ref'] ?? '')),
                $knownCategories,
                'autres',
                trim((string) ($_POST['subsubcategory_ref'] ?? ''))
            );
            if ($title === '' || !isset($articleStatusChoices[$status])) {
                throw new RuntimeException($t('err_invalid_article'));
            }
            $scheduledAtRaw = trim((string) ($_POST['scheduled_at'] ?? ''));
            $scheduledAtValue = null;
            $publishedAtValue = null;
            $publishNow = false;
            if ($status === 'scheduled') {
                if ($scheduledAtRaw === '') {
                    throw new RuntimeException($t('err_invalid_article'));
                }
                $scheduledTs = strtotime($scheduledAtRaw);
                if ($scheduledTs === false) {
                    throw new RuntimeException($t('err_invalid_article'));
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
                $moderationNoteValue = $moderationNote !== '' ? $moderationNote : $t('moderation_note_rejected_default');
            }
            $imported = import_article_document($_FILES['article_document'] ?? [], $action !== 'preview_article');
            if ($imported['content'] !== '') {
                $content = $imported['content'];
            }
            if (
                mb_strlen($title) > $articleFieldLimits['title']
                || mb_strlen($slugInput) > $articleFieldLimits['slug']
                || mb_strlen($excerpt) > $articleFieldLimits['excerpt']
                || mb_strlen($content) > $articleFieldLimits['content']
                || mb_strlen($category) > $articleFieldLimits['taxonomy']
                || mb_strlen($subcategory) > $articleFieldLimits['taxonomy']
                || mb_strlen($subsubcategory) > $articleFieldLimits['taxonomy']
            ) {
                throw new RuntimeException($t('error_field_too_long'));
            }
            if ($action === 'preview_article') {
                $previewPayload = [
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'content' => $content,
                    'status' => $status,
                    'category' => $category,
                    'subcategory' => $subcategory,
                    'subsubcategory' => $subsubcategory,
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
                    'subcategory' => $subcategory,
                    'subsubcategory' => $subsubcategory,
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
                            $previousStmt = db()->prepare('SELECT title, slug, excerpt, content, status, category, subcategory, subsubcategory, scheduled_at, published_at, author_id FROM articles WHERE id = ? LIMIT 1');
                            $previousStmt->execute([$id]);
                            $previous = $previousStmt->fetch() ?: null;
                            if (!is_array($previous)) {
                                throw new RuntimeException($t('err_invalid_article'));
                            }
                            $authorId = isset($previous['author_id']) ? (int) $previous['author_id'] : 0;
                            $existingPublishedAt = trim((string) ($previous['published_at'] ?? ''));
                            $publishedAtValue = $publishNow ? ($existingPublishedAt !== '' ? $existingPublishedAt : date('Y-m-d H:i:s')) : null;
                            if (table_exists('article_revisions')) {
                                db()->prepare('INSERT INTO article_revisions (article_id, title, slug, excerpt, content, status, category, subcategory, subsubcategory, scheduled_at, published_at, author_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                                    ->execute([
                                        $id,
                                        (string) ($previous['title'] ?? ''),
                                        (string) ($previous['slug'] ?? ''),
                                        (string) ($previous['excerpt'] ?? ''),
                                        (string) ($previous['content'] ?? ''),
                                        (string) ($previous['status'] ?? 'draft'),
                                        (string) ($previous['category'] ?? 'autres'),
                                        (string) ($previous['subcategory'] ?? ''),
                                        (string) ($previous['subsubcategory'] ?? ''),
                                        $previous['scheduled_at'] ?? null,
                                        $previous['published_at'] ?? null,
                                        isset($previous['author_id']) ? (int) $previous['author_id'] : null,
                                    ]);
                            }
                            db()->prepare('UPDATE articles SET title = ?, slug = ?, excerpt = ?, content = ?, status = ?, category = ?, subcategory = ?, subsubcategory = ?, scheduled_at = ?, published_at = ?, moderation_note = ?, updated_at = NOW() WHERE id = ?')
                                ->execute([$title, $slug, $excerpt, $content, $status, $category, $subcategory, $subsubcategory, $scheduledAtValue, $publishedAtValue, $moderationNoteValue, $id]);
                        } else {
                            $publishedAtValue = $publishNow ? date('Y-m-d H:i:s') : null;
                            db()->prepare('INSERT INTO articles (title, slug, excerpt, content, status, category, subcategory, subsubcategory, scheduled_at, published_at, moderation_note, author_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                                ->execute([$title, $slug, $excerpt, $content, $status, $category, $subcategory, $subsubcategory, $scheduledAtValue, $publishedAtValue, $moderationNoteValue, (int) current_user()['id']]);
                            $id = (int) db()->lastInsertId();
                            if (table_exists('article_revisions')) {
                                db()->prepare('INSERT INTO article_revisions (article_id, title, slug, excerpt, content, status, category, subcategory, subsubcategory, scheduled_at, published_at, author_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                                    ->execute([$id, $title, $slug, $excerpt, $content, $status, $category, $subcategory, $subsubcategory, $scheduledAtValue, $publishedAtValue, (int) current_user()['id']]);
                            }
                        }
                        db()->commit();
                        admin_articles_forget_public_caches();
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
                        set_flash('warning', $t('warning_translation_sync_single'));
                    }
                }

                $currentUserId = (int) current_user()['id'];
                if ($authorId > 0 && $authorId !== $currentUserId) {
                    if ($notifyStatus === 'published') {
                        notify_member($authorId, 'publication', $t('notification_article_published'), $title, route_url('article', ['slug' => $slug]));
                    } elseif ($notifyStatus === 'scheduled') {
                        notify_member($authorId, 'publication', $t('notification_article_scheduled'), $title, route_url('my_requests'));
                    } elseif ($notifyStatus === 'rejected') {
                        notify_member($authorId, 'moderation', $t('notification_article_rejected'), $moderationNoteValue ?? $title, route_url('my_requests'));
                    }
                }

                set_flash('success', $t('ok_saved'));
                redirect_url(route_url('admin_articles', ['id' => $id]));
            }
        } elseif ($action === 'delete_article') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new RuntimeException($t('err_invalid_article'));
            }
            if (table_exists('article_translations')) {
                db()->prepare('DELETE FROM article_translations WHERE article_id = ?')->execute([$id]);
            }
            if (table_exists('article_revisions')) {
                db()->prepare('DELETE FROM article_revisions WHERE article_id = ?')->execute([$id]);
            }
            if (table_exists('member_favorites')) {
                db()->prepare('DELETE FROM member_favorites WHERE target_type = ? AND target_id = ?')->execute(['article', $id]);
            }
            db()->prepare('DELETE FROM articles WHERE id = ?')->execute([$id]);
            admin_articles_forget_public_caches();
            set_flash('success', $t('ok_deleted'));
            redirect('admin_articles');
        } elseif ($action === 'restore_revision') {
            $articleId = (int) ($_POST['article_id'] ?? 0);
            $revisionId = (int) ($_POST['revision_id'] ?? 0);
            if ($articleId <= 0 || $revisionId <= 0 || !table_exists('article_revisions')) {
                throw new RuntimeException($t('err_invalid_article'));
            }
            $revStmt = db()->prepare('SELECT * FROM article_revisions WHERE id = ? AND article_id = ? LIMIT 1');
            $revStmt->execute([$revisionId, $articleId]);
            $revision = $revStmt->fetch() ?: null;
            if (!is_array($revision)) {
                throw new RuntimeException($t('err_invalid_article'));
            }
            $restoredStatus = (string) ($revision['status'] ?? 'draft');
            db()->prepare('UPDATE articles SET title = ?, slug = ?, excerpt = ?, content = ?, status = ?, category = ?, subcategory = ?, subsubcategory = ?, scheduled_at = ?, published_at = ?, moderation_note = NULL, updated_at = NOW() WHERE id = ?')
                ->execute([
                    (string) ($revision['title'] ?? ''),
                    (string) ($revision['slug'] ?? ''),
                    (string) ($revision['excerpt'] ?? ''),
                    (string) ($revision['content'] ?? ''),
                    $restoredStatus,
                    (string) ($revision['category'] ?? 'autres'),
                    (string) ($revision['subcategory'] ?? ''),
                    (string) ($revision['subsubcategory'] ?? ''),
                    $revision['scheduled_at'] ?? null,
                    $revision['published_at'] ?? null,
                    $articleId,
                ]);
            if (in_array($restoredStatus, ['published', 'scheduled'], true)) {
                try {
                    article_translations_sync_all($articleId);
                } catch (Throwable) {
                    set_flash('warning', $t('warning_translation_sync_revision'));
                }
            }
            set_flash('success', $t('ok_revision_restored'));
            redirect_url(route_url('admin_articles', ['id' => $articleId]));
        } elseif ($action === 'save_category') {
            $oldCode = article_category_code(trim((string) ($_POST['old_code'] ?? '')));
            $newCode = article_category_code(trim((string) ($_POST['new_code'] ?? '')));
            if ($oldCode === '' || $newCode === '') {
                throw new RuntimeException($t('err_invalid_category'));
            }
            db()->prepare('UPDATE article_categories SET code = ? WHERE code = ?')->execute([$newCode, $oldCode]);
            db()->prepare('UPDATE article_subcategories SET category_code = ? WHERE category_code = ?')->execute([$newCode, $oldCode]);
            if (article_ensure_subsubcategories_table()) {
                db()->prepare('UPDATE article_subsubcategories SET category_code = ? WHERE category_code = ?')->execute([$newCode, $oldCode]);
            }
            db()->prepare('UPDATE articles SET category = ? WHERE category = ?')->execute([$newCode, $oldCode]);
            set_flash('success', $t('ok_category_updated'));
            redirect('admin_articles');
        } elseif ($action === 'retry_scheduled_article') {
            $id = (int) ($_POST['id'] ?? 0);
            $result = editorial_retry_scheduled_article($id);
            if ($result === 'blocked_missing_fields') {
                throw new RuntimeException($t('retry_blocked_missing_fields'));
            }
            if ($result === 'invalid' || $result === 'missing') {
                throw new RuntimeException($t('err_invalid_article'));
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
                throw new RuntimeException($t('err_invalid_article'));
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
$adminCategoryRaw = trim((string) ($_GET['category'] ?? ''));
$adminCategory = $adminCategoryRaw !== '' ? article_category_code($adminCategoryRaw) : '';
$adminSubcategory = article_subcategory_code(trim((string) ($_GET['subcategory'] ?? '')));
$adminSubsubcategory = article_subsubcategory_code(trim((string) ($_GET['subsubcategory'] ?? '')));
$adminSearch = trim((string) ($_GET['q'] ?? ''));
$hasAdvancedArticleFilters = $adminSearch !== '' || $adminCategory !== '' || $adminSubcategory !== '' || $adminSubsubcategory !== '';
$adminWhere = [];
$adminParams = [];
$adminStatusCountWhere = [];
$adminStatusCountParams = [];
if (isset($articleStatusChoices[$adminStatus])) {
    $adminWhere[] = 'status = ?';
    $adminParams[] = $adminStatus;
}
if ($adminCategory !== '') {
    $adminWhere[] = 'category = ?';
    $adminParams[] = $adminCategory;
    $adminStatusCountWhere[] = 'category = ?';
    $adminStatusCountParams[] = $adminCategory;
}
if ($adminSubcategory !== '') {
    $adminWhere[] = 'subcategory = ?';
    $adminParams[] = $adminSubcategory;
    $adminStatusCountWhere[] = 'subcategory = ?';
    $adminStatusCountParams[] = $adminSubcategory;
}
if ($adminSubsubcategory !== '') {
    $adminWhere[] = 'subsubcategory = ?';
    $adminParams[] = $adminSubsubcategory;
    $adminStatusCountWhere[] = 'subsubcategory = ?';
    $adminStatusCountParams[] = $adminSubsubcategory;
}
if ($adminSearch !== '') {
    $adminWhere[] = '(title LIKE ? OR excerpt LIKE ? OR content LIKE ? OR category LIKE ? OR subcategory LIKE ? OR subsubcategory LIKE ?)';
    $adminStatusCountWhere[] = '(title LIKE ? OR excerpt LIKE ? OR content LIKE ? OR category LIKE ? OR subcategory LIKE ? OR subsubcategory LIKE ?)';
    $needle = '%' . $adminSearch . '%';
    $adminParams[] = $needle;
    $adminParams[] = $needle;
    $adminParams[] = $needle;
    $adminParams[] = $needle;
    $adminParams[] = $needle;
    $adminParams[] = $needle;
    $adminStatusCountParams[] = $needle;
    $adminStatusCountParams[] = $needle;
    $adminStatusCountParams[] = $needle;
    $adminStatusCountParams[] = $needle;
    $adminStatusCountParams[] = $needle;
    $adminStatusCountParams[] = $needle;
}
$adminWhereSql = $adminWhere === [] ? '' : ('WHERE ' . implode(' AND ', $adminWhere));
$adminStatusCountWhereSql = $adminStatusCountWhere === [] ? '' : ('WHERE ' . implode(' AND ', $adminStatusCountWhere));
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
$articleStatsStmt = db()->prepare('SELECT status, COUNT(*) AS total FROM articles ' . $adminStatusCountWhereSql . ' GROUP BY status');
$articleStatsStmt->execute($adminStatusCountParams);
$articleStats = $articleStatsStmt->fetchAll() ?: [];
$articleStatMap = array_fill_keys(array_keys($articleStatusChoices), 0);
foreach ($articleStats as $statRow) {
    $articleStatMap[(string) $statRow['status']] = (int) $statRow['total'];
}
$articleCategoryCounts = [];
$articleSubcategoryCounts = [];
$articleSubsubcategoryCounts = [];
$articleTaxonomyRows = db()->query('SELECT category, subcategory, subsubcategory, COUNT(*) AS total FROM articles GROUP BY category, subcategory, subsubcategory ORDER BY category ASC, subcategory ASC, subsubcategory ASC')->fetchAll() ?: [];
foreach ($articleTaxonomyRows as $articleTaxonomyRow) {
    $categoryCode = article_category_code((string) ($articleTaxonomyRow['category'] ?? 'autres'));
    $subcategoryCode = article_subcategory_code((string) ($articleTaxonomyRow['subcategory'] ?? ''));
    $subsubcategoryCode = article_subsubcategory_code((string) ($articleTaxonomyRow['subsubcategory'] ?? ''));
    $total = (int) ($articleTaxonomyRow['total'] ?? 0);
    if ($categoryCode !== '') {
        $articleCategoryCounts[$categoryCode] = ($articleCategoryCounts[$categoryCode] ?? 0) + $total;
    }
    if ($categoryCode !== '' && $subcategoryCode !== '') {
        $articleSubcategoryCounts[$categoryCode . ':' . $subcategoryCode] = ($articleSubcategoryCounts[$categoryCode . ':' . $subcategoryCode] ?? 0) + $total;
    }
    if ($categoryCode !== '' && $subcategoryCode !== '' && $subsubcategoryCode !== '') {
        $articleSubsubcategoryCounts[$categoryCode . ':' . $subcategoryCode . ':' . $subsubcategoryCode] = ($articleSubsubcategoryCounts[$categoryCode . ':' . $subcategoryCode . ':' . $subsubcategoryCode] ?? 0) + $total;
    }
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
<div class="admin-articles-module">
<nav class="admin-article-quick-nav" aria-label="<?= e($t('layout')) ?>">
    <a href="#admin-article-editor"><?= e($t('article_editor')) ?></a>
    <a href="#admin-article-preview"><?= e($t('preview')) ?></a>
    <a href="#admin-article-queue"><?= e($t('editorial_queue')) ?></a>
    <a href="<?= e($pendingProposalUrl) ?>"><?= e($t('pending_proposals_title')) ?></a>
    <a href="#admin-article-list"><?= e($t('existing_articles')) ?></a>
    <a href="#admin-article-taxonomy"><?= e($t('category_edit')) ?></a>
</nav>
<div class="admin-articles-workspace">
    <section class="card admin-article-editor-card" id="admin-article-editor">
        <div class="admin-article-editor-head">
            <div>
                <p class="admin-section-kicker"><?= e($t('article_editor')) ?></p>
                <h1><?= $editingId > 0 ? e($t('edit')) : e($t('create')) ?> <?= e($t('an_article')) ?></h1>
                <p class="help"><?= e($editingId > 0 ? $t('editor_intro_edit') : $t('editor_intro_new')) ?></p>
            </div>
            <div class="admin-article-editor-head-actions">
                <?php if ($editingId > 0): ?>
                    <?php $editingPublicSlug = trim((string) ($editing['slug'] ?? '')); ?>
                    <span class="badge muted admin-article-status admin-article-status-<?= e((string) ($editing['status'] ?? 'draft')) ?>"><?= e($articleStatusLabel((string) ($editing['status'] ?? 'draft'))) ?></span>
                    <?php if ((string) ($editing['status'] ?? '') === 'published' && $editingPublicSlug !== ''): ?>
                        <a class="button small secondary admin-article-public-link" href="<?= e(route_url('article', ['slug' => $editingPublicSlug])) ?>"><?= e($t('preview')) ?></a>
                    <?php endif; ?>
                    <a class="button small secondary" href="<?= e(route_url('admin_articles')) ?>"><?= e($t('new_article')) ?></a>
                <?php endif; ?>
            </div>
        </div>
        <form method="post" enctype="multipart/form-data" class="admin-article-editor-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_article">
            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
            <div class="admin-article-form-section">
                <h2><?= e($t('content_section')) ?></h2>
                <label><?= e($t('title')) ?><input type="text" name="title" value="<?= e((string) $editing['title']) ?>" required></label>
                <label><?= e($t('slug')) ?><input type="text" name="slug" value="<?= e((string) $editing['slug']) ?>" placeholder="<?= e($t('slug_placeholder')) ?>"></label>
                <label><?= e($t('import_document')) ?><input type="file" name="article_document" accept=".pdf,.docx,.txt,.md,.html,.htm,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,text/markdown,text/html"></label>
                <label><?= e($t('excerpt')) ?><textarea name="excerpt" rows="4" data-wysiwyg="off"><?= e((string) $editing['excerpt']) ?></textarea></label>
                <label><?= e($t('content_simple_html')) ?><textarea name="content" rows="16" data-wysiwyg="full"><?= e((string) $editing['content']) ?></textarea></label>
                <p class="help"><?= e($t('editor_tip')) ?></p>
            </div>
            <div class="admin-article-form-section">
                <h2><?= e($t('taxonomy_section')) ?></h2>
                <div class="admin-article-form-grid">
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
                    <label><?= e($t('subcategory_field')) ?>
                        <select name="subcategory_ref">
                            <?php $editingSubcategory = article_subcategory_code((string) ($editing['subcategory'] ?? '')); ?>
                            <option value=""><?= e($t('no_subcategory')) ?></option>
                            <?php foreach ($articleSubcategoriesByCategory as $subcategoryCategoryCode => $subcategories): ?>
                                <?php if ($subcategories === []): ?>
                                    <?php continue; ?>
                                <?php endif; ?>
                                <optgroup label="<?= e((string) ($knownCategories[(string) $subcategoryCategoryCode] ?? article_category_label_from_code((string) $subcategoryCategoryCode))) ?>">
                                    <?php foreach ($subcategories as $subcategoryInfo): ?>
                                        <?php
                                        $subcategoryCode = article_subcategory_code((string) ($subcategoryInfo['code'] ?? ''));
                                        if ($subcategoryCode === '') {
                                            continue;
                                        }
                                        $subcategoryRef = article_subcategory_ref((string) $subcategoryCategoryCode, $subcategoryCode);
                                        $isSelectedSubcategory = $editingCategory === (string) $subcategoryCategoryCode && $editingSubcategory === $subcategoryCode;
                                        ?>
                                        <option value="<?= e($subcategoryRef) ?>" <?= $isSelectedSubcategory ? 'selected' : '' ?>><?= e((string) ($subcategoryInfo['label'] ?? $subcategoryCode)) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><?= e($t('subsubcategory_field')) ?>
                        <select name="subsubcategory_ref">
                            <?php $editingSubsubcategory = article_subsubcategory_code((string) ($editing['subsubcategory'] ?? '')); ?>
                            <option value=""><?= e($t('no_subsubcategory')) ?></option>
                            <?php foreach ($articleSubsubcategoriesByParent as $subsubcategoryParentRef => $subsubcategories): ?>
                                <?php $subsubcategoryParentParts = article_subcategory_ref_parts((string) $subsubcategoryParentRef); ?>
                                <?php $subsubcategoryParentCategory = $subsubcategoryParentParts['category']; ?>
                                <?php $subsubcategoryParentSubcategory = $subsubcategoryParentParts['subcategory']; ?>
                                <?php if ($subsubcategoryParentCategory === '' || $subsubcategoryParentSubcategory === ''): ?>
                                    <?php continue; ?>
                                <?php endif; ?>
                                <?php $subsubcategoryParentLabel = article_category_label_from_code($subsubcategoryParentSubcategory); ?>
                                <?php foreach ($articleSubcategoriesByCategory[$subsubcategoryParentCategory] ?? [] as $subcategoryInfo): ?>
                                    <?php if (article_subcategory_code((string) ($subcategoryInfo['code'] ?? '')) === $subsubcategoryParentSubcategory) { $subsubcategoryParentLabel = (string) ($subcategoryInfo['label'] ?? $subsubcategoryParentLabel); break; } ?>
                                <?php endforeach; ?>
                                <optgroup label="<?= e((string) ($knownCategories[$subsubcategoryParentCategory] ?? article_category_label_from_code($subsubcategoryParentCategory)) . ' / ' . $subsubcategoryParentLabel) ?>">
                                    <?php foreach ($subsubcategories as $subsubcategoryInfo): ?>
                                        <?php
                                        $subsubcategoryCode = article_subsubcategory_code((string) ($subsubcategoryInfo['code'] ?? ''));
                                        if ($subsubcategoryCode === '') {
                                            continue;
                                        }
                                        $subsubcategoryRef = article_subsubcategory_ref($subsubcategoryParentCategory, $subsubcategoryParentSubcategory, $subsubcategoryCode);
                                        $isSelectedSubsubcategory = $editingCategory === $subsubcategoryParentCategory && $editingSubcategory === $subsubcategoryParentSubcategory && $editingSubsubcategory === $subsubcategoryCode;
                                        ?>
                                        <option value="<?= e($subsubcategoryRef) ?>" <?= $isSelectedSubsubcategory ? 'selected' : '' ?>><?= e((string) ($subsubcategoryInfo['label'] ?? $subsubcategoryCode)) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            </div>
            <div class="admin-article-form-section">
                <h2><?= e($t('publication_section')) ?></h2>
                <div class="admin-article-form-grid">
                    <label><?= e($t('status')) ?>
                        <select name="status">
                            <?php foreach ($articleStatusChoices as $statusCode => $statusLabel): ?>
                                <option value="<?= e($statusCode) ?>" <?= (string) $editing['status'] === $statusCode ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><?= e($t('scheduled_at')) ?><input type="datetime-local" name="scheduled_at" value="<?= !empty($editing['scheduled_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $editing['scheduled_at']))) : '' ?>"></label>
                </div>
                <label><?= e($t('moderation_note')) ?><textarea name="moderation_note" rows="3" placeholder="<?= e($t('moderation_note_help')) ?>"><?= e((string) ($editing['moderation_note'] ?? '')) ?></textarea></label>
            </div>
            <button class="button admin-article-save-button" type="submit"><?= e($t('save')) ?></button>
            <button class="button secondary" type="submit" name="action" value="preview_article"><?= e($t('preview')) ?></button>
        </form>
        <?php if ((int) $editing['id'] > 0): ?>
            <div class="admin-article-moderation-panel">
                <div class="admin-article-panel-head">
                    <h2><?= e($t('moderation_actions')) ?></h2>
                    <p class="help"><?= e($t('moderation_actions_help')) ?></p>
                </div>
                <?php if ((string) ($editing['status'] ?? '') !== 'published'): ?>
                    <form method="post" class="admin-article-publish-form">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="bulk_update_articles">
                        <input type="hidden" name="ids[]" value="<?= (int) $editing['id'] ?>">
                        <input type="hidden" name="bulk_op" value="published">
                        <input type="hidden" name="return_id" value="<?= (int) $editing['id'] ?>">
                        <button class="button admin-article-publish-button" type="submit"><?= e($t('publish_now')) ?></button>
                    </form>
                <?php endif; ?>
                <form method="post" class="admin-article-reject-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="bulk_update_articles">
                    <input type="hidden" name="ids[]" value="<?= (int) $editing['id'] ?>">
                    <input type="hidden" name="bulk_op" value="rejected">
                    <input type="hidden" name="return_id" value="<?= (int) $editing['id'] ?>">
                    <label><?= e($t('moderation_note')) ?>
                        <textarea name="moderation_note" rows="3" placeholder="<?= e($t('moderation_note_help')) ?>"><?= e((string) ($editing['moderation_note'] ?? '')) ?></textarea>
                    </label>
                    <button class="button secondary admin-article-reject-button" type="submit"><?= e($t('reject_article')) ?></button>
                </form>
                <form method="post" class="admin-article-delete-form" onsubmit="return confirm('<?= e($t('confirm_delete')) ?>');">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete_article">
                    <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                    <button class="button secondary admin-article-delete-button" type="submit"><?= e($t('delete_article')) ?></button>
                </form>
            </div>
            <section style="margin-top:1rem;">
                <h3><?= e($t('revisions')) ?></h3>
                <?php if ($revisions === []): ?>
                    <p class="help"><?= e($t('no_revisions')) ?></p>
                <?php else: ?>
                    <div class="stack">
                        <?php foreach ($revisions as $revision): ?>
                            <form method="post" class="row-between" style="gap:.6rem;align-items:center;">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="restore_revision">
                                <input type="hidden" name="article_id" value="<?= (int) $editing['id'] ?>">
                                <input type="hidden" name="revision_id" value="<?= (int) $revision['id'] ?>">
                                <span class="help"><?= e($t('revision_saved_at')) ?> <?= e(date('d/m/Y H:i', strtotime((string) $revision['created_at']))) ?> · <?= e($articleStatusLabel((string) $revision['status'])) ?></span>
                                <button class="button small secondary" type="submit" onclick="return confirm('<?= e($t('confirm_restore_revision')) ?>');"><?= e($t('restore_revision')) ?></button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </section>
    <section class="card admin-article-preview-card" id="admin-article-preview">
        <h2><?= e($t('preview')) ?></h2>
        <p class="help"><?= e($t('preview_help')) ?></p>
        <?php if ($previewPayload === null): ?>
            <p class="help"><?= e($t('preview_empty')) ?></p>
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
    <section class="card admin-article-queue-card" id="admin-article-queue">
        <h2><?= e($t('editorial_queue')) ?></h2>
        <p class="help"><?= e($t('editorial_queue_help')) ?></p>
        <?php if ($scheduledQueue === []): ?>
            <p class="help"><?= e($t('editorial_queue_empty')) ?></p>
        <?php else: ?>
            <form method="post" style="margin:0 0 .8rem 0;">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="retry_scheduled_bulk">
                <button class="button secondary small" type="submit"><?= e($t('retry_bulk')) ?></button>
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
                                    <?= e($t('scheduled_at')) ?>:
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
                                    <button class="button small" type="submit"><?= e($t('retry')) ?></button>
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
            <h2 id="pending-proposals-title"><?= e($t('pending_proposals_title')) ?></h2>
            <a class="button secondary small" href="<?= e(route_url('admin_articles')) ?>"><?= e($t('reset_filter')) ?></a>
        </div>
        <?php if ($pendingProposals === []): ?>
            <p class="help"><?= e($t('pending_proposals_empty')) ?></p>
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
                    <h3><?= e((string) ($proposal['title'] ?? $t('proposal_default_title'))) ?></h3>
                    <p class="help"><?= e($t('proposal_author')) ?>: <?= e($memberLabel) ?></p>
                    <?php if (trim((string) ($proposal['summary'] ?? '')) !== ''): ?>
                        <p><?= nl2br(e((string) $proposal['summary'])) ?></p>
                    <?php endif; ?>
                    <?php if (trim((string) ($proposal['contact'] ?? '')) !== ''): ?>
                        <p class="help"><?= e($t('proposal_contact')) ?>: <?= e((string) $proposal['contact']) ?></p>
                    <?php endif; ?>
                    <form method="post" class="stack">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_proposal_status">
                        <input type="hidden" name="proposal_id" value="<?= (int) ($proposal['id'] ?? 0) ?>">
                        <div class="grid-2">
                            <label><?= e($t('proposal_status_label')) ?>
                                <select name="proposal_status">
                                    <?php foreach ($proposalStatusLabels as $statusCode => $statusLabel): ?>
                                        <option value="<?= e($statusCode) ?>" <?= $proposalStatus === $statusCode ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label><?= e($t('proposal_moderation_note')) ?>
                                <textarea name="moderation_note" rows="3"><?= e((string) ($proposal['moderation_note'] ?? '')) ?></textarea>
                            </label>
                        </div>
                        <p><button class="button small" type="submit"><?= e($t('proposal_save_status')) ?></button></p>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    <section class="card admin-article-list-card" id="admin-article-list">
        <div class="admin-article-list-top">
            <div>
                <p class="admin-section-kicker"><?= e($t('status_overview')) ?></p>
                <h2><?= e($t('existing_articles')) ?></h2>
                <p class="help"><?= e(sprintf($t('articles_found'), $totalArticles)) ?></p>
            </div>
            <a class="button secondary" href="<?= e(route_url('admin_articles')) ?>"><?= e($t('new_article')) ?></a>
        </div>
        <div class="admin-article-status-tabs" aria-label="<?= e($t('status')) ?>">
            <?php
            $statusFilterBase = [
                'q' => $adminSearch,
                'category' => $adminCategory,
                'subcategory' => $adminSubcategory,
                'subsubcategory' => $adminSubsubcategory,
            ];
            $allArticleCount = array_sum($articleStatMap);
            ?>
            <a class="admin-article-status-tab<?= $adminStatus === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('admin_articles', array_merge($statusFilterBase, ['status' => '']))) ?>">
                <span><?= e($t('all_statuses')) ?></span>
                <strong><?= (int) $allArticleCount ?></strong>
            </a>
            <?php foreach (['pending', 'draft', 'scheduled', 'published', 'rejected'] as $statusCode): ?>
                <a class="admin-article-status-tab admin-article-status-tab-<?= e($statusCode) ?><?= $adminStatus === $statusCode ? ' is-active' : '' ?>" href="<?= e(route_url_clean('admin_articles', array_merge($statusFilterBase, ['status' => $statusCode]))) ?>">
                    <span><?= e($articleStatusLabel($statusCode)) ?></span>
                    <strong><?= (int) ($articleStatMap[$statusCode] ?? 0) ?></strong>
                </a>
            <?php endforeach; ?>
        </div>
        <details class="admin-article-filter-panel"<?= $hasAdvancedArticleFilters ? ' open' : '' ?>>
            <summary><?= e($t('filter')) ?></summary>
            <form method="get" class="stack admin-article-filter-form">
                <input type="hidden" name="route" value="admin_articles">
                <div class="grid-3">
                    <label><?= e($t('search')) ?><input type="search" name="q" value="<?= e($adminSearch) ?>"></label>
                    <label><?= e($t('status')) ?>
                        <select name="status">
                            <option value=""><?= e($t('all_statuses')) ?></option>
                            <?php foreach ($articleStatusChoices as $statusCode => $statusLabel): ?>
                                <option value="<?= e($statusCode) ?>" <?= $adminStatus === $statusCode ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><?= e($t('category')) ?>
                        <select name="category">
                            <option value=""><?= e($t('all_categories')) ?></option>
                            <?php foreach ($knownCategories as $categoryCode => $categoryLabel): ?>
                                <option value="<?= e($categoryCode) ?>" <?= $adminCategory === $categoryCode ? 'selected' : '' ?>><?= e($categoryLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><?= e($t('subcategory_field')) ?>
                        <select name="subcategory">
                            <option value=""><?= e($t('no_subcategory')) ?></option>
                            <?php foreach ($articleSubcategoriesByCategory as $subcategoryCategoryCode => $subcategories): ?>
                                <?php if ($subcategories === []): ?>
                                    <?php continue; ?>
                                <?php endif; ?>
                                <optgroup label="<?= e((string) ($knownCategories[(string) $subcategoryCategoryCode] ?? article_category_label_from_code((string) $subcategoryCategoryCode))) ?>">
                                    <?php foreach ($subcategories as $subcategoryInfo): ?>
                                        <?php
                                        $subcategoryCode = article_subcategory_code((string) ($subcategoryInfo['code'] ?? ''));
                                        if ($subcategoryCode === '') {
                                            continue;
                                        }
                                        ?>
                                        <option value="<?= e($subcategoryCode) ?>" <?= $adminSubcategory === $subcategoryCode ? 'selected' : '' ?>><?= e((string) ($subcategoryInfo['label'] ?? $subcategoryCode)) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><?= e($t('subsubcategory_field')) ?>
                        <select name="subsubcategory">
                            <option value=""><?= e($t('no_subsubcategory')) ?></option>
                            <?php foreach ($articleSubsubcategoriesByParent as $subsubcategoryParentRef => $subsubcategories): ?>
                                <?php $subsubcategoryParentParts = article_subcategory_ref_parts((string) $subsubcategoryParentRef); ?>
                                <?php $subsubcategoryParentCategory = $subsubcategoryParentParts['category']; ?>
                                <?php $subsubcategoryParentSubcategory = $subsubcategoryParentParts['subcategory']; ?>
                                <?php if ($subsubcategoryParentCategory === '' || $subsubcategoryParentSubcategory === ''): ?>
                                    <?php continue; ?>
                                <?php endif; ?>
                                <?php $subsubcategoryParentLabel = article_category_label_from_code($subsubcategoryParentSubcategory); ?>
                                <?php foreach ($articleSubcategoriesByCategory[$subsubcategoryParentCategory] ?? [] as $subcategoryInfo): ?>
                                    <?php if (article_subcategory_code((string) ($subcategoryInfo['code'] ?? '')) === $subsubcategoryParentSubcategory) { $subsubcategoryParentLabel = (string) ($subcategoryInfo['label'] ?? $subsubcategoryParentLabel); break; } ?>
                                <?php endforeach; ?>
                                <optgroup label="<?= e((string) ($knownCategories[$subsubcategoryParentCategory] ?? article_category_label_from_code($subsubcategoryParentCategory)) . ' / ' . $subsubcategoryParentLabel) ?>">
                                    <?php foreach ($subsubcategories as $subsubcategoryInfo): ?>
                                        <?php
                                        $subsubcategoryCode = article_subsubcategory_code((string) ($subsubcategoryInfo['code'] ?? ''));
                                        if ($subsubcategoryCode === '') {
                                            continue;
                                        }
                                        ?>
                                        <option value="<?= e($subsubcategoryCode) ?>" <?= $adminSubsubcategory === $subsubcategoryCode ? 'selected' : '' ?>><?= e((string) ($subsubcategoryInfo['label'] ?? $subsubcategoryCode)) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <p><button class="button" type="submit"><?= e($t('filter')) ?></button> <a class="button secondary" href="<?= e(route_url('admin_articles')) ?>"><?= e($t('reset_filter')) ?></a></p>
            </form>
        </details>
        <form id="admin-article-bulk-form" method="post" class="admin-article-bulk-bar" data-confirm-message="<?= e($t('confirm_bulk_action')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="bulk_update_articles">
            <div>
                <h3><?= e($t('bulk_actions')) ?></h3>
                <p class="help"><?= e($t('bulk_actions_help')) ?></p>
            </div>
            <label class="admin-article-select-all">
                <input type="checkbox" data-admin-articles-select-page>
                <span><?= e($t('select_article')) ?></span>
                <span class="badge muted" data-admin-articles-selected-count>0</span>
            </label>
            <label><?= e($t('bulk_action')) ?>
                <select name="bulk_op" data-admin-bulk-op>
                    <?php foreach ($articleStatusChoices as $statusCode => $statusLabel): ?>
                        <option value="<?= e($statusCode) ?>"><?= e($statusLabel) ?></option>
                    <?php endforeach; ?>
                    <option value="delete"><?= e($t('delete')) ?></option>
                </select>
            </label>
            <label data-admin-bulk-scheduled-field><?= e($t('scheduled_at')) ?><input type="datetime-local" name="scheduled_at"></label>
            <label data-admin-bulk-note-field><?= e($t('moderation_note')) ?><textarea name="moderation_note" rows="2" placeholder="<?= e($t('moderation_note_help')) ?>"></textarea></label>
            <button class="button small" type="submit" data-admin-bulk-submit><?= e($t('apply_to_selection')) ?></button>
        </form>
        <div class="stack">
            <?php foreach ($articles as $article): ?>
                <?php
                $articleCategoryCode = article_category_code((string) ($article['category'] ?? 'autres'));
                $articleCategoryLabel = (string) ($knownCategories[$articleCategoryCode] ?? article_category_label_from_code($articleCategoryCode));
                $articleSubcategoryCode = article_subcategory_code((string) ($article['subcategory'] ?? ''));
                $articleSubcategoryLabel = '';
                foreach ($articleSubcategoriesByCategory[$articleCategoryCode] ?? [] as $subcategoryInfo) {
                    if (article_subcategory_code((string) ($subcategoryInfo['code'] ?? '')) === $articleSubcategoryCode) {
                        $articleSubcategoryLabel = (string) ($subcategoryInfo['label'] ?? $articleSubcategoryCode);
                        break;
                    }
                }
                $articleSubsubcategoryCode = article_subsubcategory_code((string) ($article['subsubcategory'] ?? ''));
                $articleSubsubcategoryLabel = '';
                foreach ($articleSubsubcategoriesByParent[$articleCategoryCode . ':' . $articleSubcategoryCode] ?? [] as $subsubcategoryInfo) {
                    if (article_subsubcategory_code((string) ($subsubcategoryInfo['code'] ?? '')) === $articleSubsubcategoryCode) {
                        $articleSubsubcategoryLabel = (string) ($subsubcategoryInfo['label'] ?? $articleSubsubcategoryCode);
                        break;
                    }
                }
                $articleExcerpt = article_excerpt_from_input((string) ($article['excerpt'] ?? ''));
                $articleTimelineBadges = [];
                if (!empty($article['published_at'])) {
                    $articleTimelineBadges[] = $t('published_at') . ' ' . date('d/m/Y H:i', strtotime((string) $article['published_at']));
                }
                if (!empty($article['scheduled_at'])) {
                    $articleTimelineBadges[] = $t('scheduled_at') . ' ' . date('d/m/Y H:i', strtotime((string) $article['scheduled_at']));
                }
                ?>
                <article class="article-item admin-article-list-item admin-article-list-item-<?= e((string) $article['status']) ?>">
                    <div class="admin-article-list-header">
                        <div class="admin-article-title-block">
                            <label class="admin-article-select">
                                <input type="checkbox" name="ids[]" value="<?= (int) $article['id'] ?>" form="admin-article-bulk-form">
                                <span><?= e($t('select_article')) ?></span>
                            </label>
                            <div>
                                <h3><?= e((string) $article['title']) ?></h3>
                                <p class="help">#<?= (int) $article['id'] ?> &middot; <?= e($t('updated_at')) ?> <?= e(date('d/m/Y H:i', strtotime((string) ($article['updated_at'] ?? $article['created_at'] ?? 'now')))) ?></p>
                            </div>
                        </div>
                        <div class="admin-article-row-actions">
                            <a class="button small" href="<?= e(route_url('admin_articles', ['id' => (int) $article['id']])) ?>"><?= e($t('edit')) ?></a>
                            <?php if ((string) ($article['status'] ?? '') !== 'published'): ?>
                                <form method="post" class="admin-article-row-publish">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="bulk_update_articles">
                                    <input type="hidden" name="ids[]" value="<?= (int) $article['id'] ?>">
                                    <input type="hidden" name="bulk_op" value="published">
                                    <button class="button small admin-article-publish-button" type="submit"><?= e($t('publish_now')) ?></button>
                                </form>
                            <?php endif; ?>
                            <?php if ((string) ($article['status'] ?? '') !== 'rejected'): ?>
                                <details class="admin-article-row-reject">
                                    <summary class="button small secondary"><?= e($t('reject_article')) ?></summary>
                                    <form method="post" class="admin-article-row-reject-form">
                                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="bulk_update_articles">
                                        <input type="hidden" name="ids[]" value="<?= (int) $article['id'] ?>">
                                        <input type="hidden" name="bulk_op" value="rejected">
                                        <label><?= e($t('moderation_note')) ?>
                                            <textarea name="moderation_note" rows="2" placeholder="<?= e($t('moderation_note_help')) ?>"></textarea>
                                        </label>
                                        <button class="button small secondary admin-article-reject-button" type="submit"><?= e($t('save')) ?></button>
                                    </form>
                                </details>
                            <?php endif; ?>
                            <form method="post" class="admin-article-row-delete" onsubmit="return confirm('<?= e($t('confirm_delete')) ?>');">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete_article">
                                <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
                                <button class="button small secondary admin-article-delete-button" type="submit"><?= e($t('delete')) ?></button>
                            </form>
                        </div>
                    </div>
                    <p class="taxonomy-badge-row"><strong><?= e($t('category_label')) ?></strong> <span class="badge muted taxonomy-pill-category"><?= e($articleCategoryLabel) ?></span><?php if ($articleSubcategoryLabel !== ''): ?><span class="badge muted taxonomy-pill-subcategory"><?= e($articleSubcategoryLabel) ?></span><?php endif; ?><?php if ($articleSubsubcategoryLabel !== ''): ?><span class="badge muted taxonomy-pill-subsubcategory"><?= e($articleSubsubcategoryLabel) ?></span><?php endif; ?> <span class="badge muted admin-article-status admin-article-status-<?= e((string) $article['status']) ?>"><?= e($articleStatusLabel((string) $article['status'])) ?></span></p>
                    <?php if ($articleTimelineBadges !== []): ?>
                        <p class="admin-article-date-row">
                            <?php foreach ($articleTimelineBadges as $articleTimelineBadge): ?>
                                <span class="badge muted"><?= e($articleTimelineBadge) ?></span>
                            <?php endforeach; ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($articleExcerpt !== ''): ?>
                        <p><?= e($articleExcerpt) ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            <?php if ($articles === []): ?><p><?= e($t('no_articles')) ?></p><?php endif; ?>
        </div>
        <?php if ($totalPages > 1): ?>
            <nav class="actions mt-3">
                <?php if ($page > 1): ?><a class="button secondary" href="<?= e(route_url_clean('admin_articles', ['q' => $adminSearch, 'status' => $adminStatus, 'category' => $adminCategory, 'subcategory' => $adminSubcategory, 'subsubcategory' => $adminSubsubcategory, 'p' => $page - 1])) ?>">&larr; <?= e($t('previous')) ?></a><?php endif; ?>
                <span class="badge muted"><?= $page ?> / <?= $totalPages ?></span>
                <?php if ($page < $totalPages): ?><a class="button secondary" href="<?= e(route_url_clean('admin_articles', ['q' => $adminSearch, 'status' => $adminStatus, 'category' => $adminCategory, 'subcategory' => $adminSubcategory, 'subsubcategory' => $adminSubsubcategory, 'p' => $page + 1])) ?>"><?= e($t('next')) ?> &rarr;</a><?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>
    <section class="card admin-article-taxonomy-card" id="admin-article-taxonomy">
        <h2><?= e($t('category_edit')) ?></h2>
        <div class="grid-2">
            <form method="post" class="stack">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="add_category">
                <label><?= e($t('category')) ?>
                    <input type="text" name="category_label" maxlength="160" required>
                </label>
                <input type="hidden" name="category_code" value="">
                <button class="button" type="submit"><?= e($t('add_category')) ?></button>
            </form>
            <form method="post" class="stack">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="add_subcategory">
                <label><?= e($t('category')) ?>
                    <select name="subcategory_category">
                        <?php foreach ($knownCategories as $categoryCode => $categoryLabel): ?>
                            <option value="<?= e((string) $categoryCode) ?>"<?= $adminCategory === (string) $categoryCode ? ' selected' : '' ?>><?= e((string) $categoryLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><?= e($t('subcategory_field')) ?>
                    <input type="text" name="subcategory_label" maxlength="160" required>
                </label>
                <input type="hidden" name="subcategory_code" value="">
                <button class="button" type="submit"><?= e($t('add_subcategory')) ?></button>
            </form>
            <form method="post" class="stack">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="add_subsubcategory">
                <label><?= e($t('subcategory_field')) ?>
                    <select name="subsubcategory_parent_ref" required>
                        <option value=""><?= e($t('no_subcategory')) ?></option>
                        <?php foreach ($articleSubcategoriesByCategory as $parentCode => $subcategories): ?>
                            <optgroup label="<?= e((string) ($knownCategories[(string) $parentCode] ?? article_category_label_from_code((string) $parentCode))) ?>">
                                <?php foreach ($subcategories as $subcategoryInfo): ?>
                                    <?php $subCode = article_subcategory_code((string) ($subcategoryInfo['code'] ?? '')); ?>
                                    <?php if ($subCode === '') { continue; } ?>
                                    <option value="<?= e(article_subcategory_ref((string) $parentCode, $subCode)) ?>"><?= e((string) ($subcategoryInfo['label'] ?? $subCode)) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><?= e($t('subsubcategory_field')) ?>
                    <input type="text" name="subsubcategory_label" maxlength="160" required>
                </label>
                <input type="hidden" name="subsubcategory_code" value="">
                <button class="button" type="submit"><?= e($t('add_subsubcategory')) ?></button>
            </form>
        </div>
        <div class="tags-cloud">
            <?php foreach ($knownCategories as $categoryCode => $categoryLabel): ?>
                <?php
                $categoryTotal = (int) ($articleCategoryCounts[(string) $categoryCode] ?? 0);
                $subcategoryTotal = count($articleSubcategoriesByCategory[(string) $categoryCode] ?? []);
                $categoryDeleteDisabled = (string) $categoryCode === 'autres' || $subcategoryTotal > 0;
                ?>
                <form method="post" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_category">
                    <input type="hidden" name="category_code" value="<?= e((string) $categoryCode) ?>">
                    <span class="pill taxonomy-pill-category"><?= e((string) $categoryCode) ?> (<?= $categoryTotal ?>)</span>
                    <input type="text" name="category_label" value="<?= e((string) $categoryLabel) ?>" maxlength="160" required>
                    <button class="button small" type="submit"><?= e($t('save')) ?></button>
                    <button class="button secondary small" type="submit" name="action" value="delete_category"<?= $categoryDeleteDisabled ? ' disabled' : '' ?>><?= e($t('delete')) ?></button>
                </form>
            <?php endforeach; ?>
            <?php foreach ($articleSubcategoriesByCategory as $parentCode => $subcategories): ?>
                <?php foreach ($subcategories as $subcategoryInfo): ?>
                    <?php
                    $subCode = article_subcategory_code((string) ($subcategoryInfo['code'] ?? ''));
                    if ($subCode === '') {
                        continue;
                    }
                    $subTotal = (int) ($articleSubcategoryCounts[(string) $parentCode . ':' . $subCode] ?? 0);
                    $subsubParentRef = (string) $parentCode . ':' . $subCode;
                    $subsubcategoryTotal = count($articleSubsubcategoriesByParent[$subsubParentRef] ?? []);
                    ?>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_subcategory">
                        <input type="hidden" name="subcategory_ref" value="<?= e(article_subcategory_ref((string) $parentCode, $subCode)) ?>">
                        <span class="taxonomy-badge-row">
                            <span class="badge muted taxonomy-pill-category"><?= e((string) ($knownCategories[(string) $parentCode] ?? article_category_label_from_code((string) $parentCode))) ?></span>
                            <span class="badge muted taxonomy-pill-subcategory"><?= e($subCode) ?></span>
                            <span class="badge muted"><?= $subTotal ?></span>
                        </span>
                        <input type="text" name="subcategory_label" value="<?= e((string) ($subcategoryInfo['label'] ?? $subCode)) ?>" maxlength="160" required>
                        <button class="button small" type="submit"><?= e($t('save')) ?></button>
                        <button class="button secondary small" type="submit" name="action" value="delete_subcategory"<?= ($subTotal > 0 || $subsubcategoryTotal > 0) ? ' disabled' : '' ?>><?= e($t('delete')) ?></button>
                    </form>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php foreach ($articleSubsubcategoriesByParent as $parentRef => $subsubcategories): ?>
                <?php $parentParts = article_subcategory_ref_parts((string) $parentRef); ?>
                <?php $parentCategory = $parentParts['category']; ?>
                <?php $parentSubcategory = $parentParts['subcategory']; ?>
                <?php if ($parentCategory === '' || $parentSubcategory === '') { continue; } ?>
                <?php $parentSubcategoryLabel = article_category_label_from_code($parentSubcategory); ?>
                <?php foreach ($articleSubcategoriesByCategory[$parentCategory] ?? [] as $subcategoryInfo): ?>
                    <?php if (article_subcategory_code((string) ($subcategoryInfo['code'] ?? '')) === $parentSubcategory) { $parentSubcategoryLabel = (string) ($subcategoryInfo['label'] ?? $parentSubcategory); break; } ?>
                <?php endforeach; ?>
                <?php foreach ($subsubcategories as $subsubcategoryInfo): ?>
                    <?php $subsubCode = article_subsubcategory_code((string) ($subsubcategoryInfo['code'] ?? '')); ?>
                    <?php if ($subsubCode === '') { continue; } ?>
                    <?php $subsubTotal = (int) ($articleSubsubcategoryCounts[$parentCategory . ':' . $parentSubcategory . ':' . $subsubCode] ?? 0); ?>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_subsubcategory">
                        <input type="hidden" name="subsubcategory_category" value="<?= e($parentCategory) ?>">
                        <input type="hidden" name="subsubcategory_parent" value="<?= e($parentSubcategory) ?>">
                        <input type="hidden" name="subsubcategory_code" value="<?= e($subsubCode) ?>">
                        <span class="taxonomy-badge-row">
                            <span class="badge muted taxonomy-pill-category"><?= e((string) ($knownCategories[$parentCategory] ?? article_category_label_from_code($parentCategory))) ?></span>
                            <span class="badge muted taxonomy-pill-subcategory"><?= e($parentSubcategoryLabel) ?></span>
                            <span class="badge muted taxonomy-pill-subsubcategory"><?= e($subsubCode) ?></span>
                            <span class="badge muted"><?= $subsubTotal ?></span>
                        </span>
                        <input type="text" name="subsubcategory_label" value="<?= e((string) ($subsubcategoryInfo['label'] ?? $subsubCode)) ?>" maxlength="160" required>
                        <button class="button small" type="submit"><?= e($t('save')) ?></button>
                        <button class="button secondary small" type="submit" name="action" value="delete_subsubcategory"<?= $subsubTotal > 0 ? ' disabled' : '' ?>><?= e($t('delete')) ?></button>
                    </form>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </section>
</div>
</div>
<?php
echo render_layout((string) ob_get_clean(), $t('layout'));
