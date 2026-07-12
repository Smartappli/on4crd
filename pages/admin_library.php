<?php
declare(strict_types=1);

require_permission('admin.access');
$user = require_login();
$locale = current_locale();
$t = i18n_domain_locale('admin_library', $locale);
$memberLibraryMessages = i18n_domain_locale('members_library', $locale);
set_page_meta(['title' => (string) $t['title'], 'description' => (string) $t['meta_desc']]);

function library_category_slug(string $value): string
{
    $slug = slugify($value);
    return $slug !== '' ? $slug : 'general';
}

function library_subcategory_slug(string $value): string
{
    return function_exists('member_library_subcategory_slug')
        ? member_library_subcategory_slug($value)
        : (slugify($value) ?: '');
}

function library_subsubcategory_slug(string $value): string
{
    return function_exists('member_library_subsubcategory_slug')
        ? member_library_subsubcategory_slug($value)
        : (slugify($value) ?: '');
}

function library_tag_norm(string $value): string
{
    $tag = trim($value);
    if ($tag === '') {
        return '';
    }
    $tag = mb_strtolower($tag, 'UTF-8');
    $tag = preg_replace('/\s+/u', ' ', $tag) ?? $tag;
    return trim($tag);
}

function library_tag_split(string $value): array
{
    $parts = explode(',', $value);
    $out = [];
    foreach ($parts as $part) {
        $tag = trim($part);
        if ($tag !== '') {
            $out[] = $tag;
        }
    }
    return $out;
}

function library_tag_unique(array $tags): array
{
    $seen = [];
    $out = [];
    foreach ($tags as $tag) {
        $clean = trim((string) $tag);
        if ($clean === '') {
            continue;
        }
        $key = mb_strtolower($clean, 'UTF-8');
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $out[] = $clean;
    }
    return $out;
}

function library_controlled_vocabulary(): array
{
    return library_controlled_vocabulary_list();
}

function library_ingestion_templates(): array
{
    return library_ingestion_templates_map();
}

function ensure_member_library_categories_table(): void
{
    db()->exec('CREATE TABLE IF NOT EXISTS member_library_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(120) NOT NULL UNIQUE,
        label VARCHAR(160) NOT NULL,
        sort_order INT NOT NULL DEFAULT 100,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');
    $categoryInsert = db()->prepare('INSERT IGNORE INTO member_library_categories (code, label, sort_order) VALUES (?, ?, ?)');
    $libraryMessages = function_exists('i18n_domain_locale') ? i18n_domain_locale('members_library', current_locale()) : [];
    $defaultCategories = function_exists('member_library_default_categories')
        ? member_library_default_categories()
        : [['code' => 'general', 'label' => (string) ($libraryMessages['category_general'] ?? 'General'), 'sort_order' => 1]];
    foreach ($defaultCategories as $category) {
        $categoryInsert->execute([
            (string) $category['code'],
            (string) $category['label'],
            (int) $category['sort_order'],
        ]);
    }
    if (function_exists('member_library_ensure_subcategories_table')) {
        member_library_ensure_subcategories_table();
    }
    if (function_exists('member_library_ensure_subsubcategories_table')) {
        member_library_ensure_subsubcategories_table();
    }
}

function library_extract_text(string $path, string $extension): string
{
    return member_library_extract_text($path, $extension);
}

function library_store_upload(array $file, int $memberId): array
{
    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, member_library_document_upload_extensions(), true)) {
        throw new RuntimeException('err_invalid');
    }

    return member_library_store_document_upload($file, $memberId, 'doc');
}

try {
    ensure_member_library_categories_table();
} catch (Throwable) {
}

if (!ensure_member_library_table()) {
    echo render_layout('<div class="card"><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['title']);
    return;
}
member_library_sync_accepted_proposals($memberLibraryMessages);

$adminLibraryRoutes = ['admin_library'];
$adminLibraryRoute = (string) ($_GET['route'] ?? $_POST['route'] ?? 'admin_library');
if (!in_array($adminLibraryRoute, $adminLibraryRoutes, true)) {
    $adminLibraryRoute = 'admin_library';
}
$adminLibraryText = static function (string $key) use ($t): string {
    return (string) ($t[$key] ?? $key);
};
$pendingProposalUrl = route_url_clean($adminLibraryRoute, ['status' => 'pending']) . '#pending-proposals';
$proposalStatusLabels = [
    'pending' => $adminLibraryText('proposal_status_pending'),
    'reviewed' => $adminLibraryText('proposal_status_reviewed'),
    'accepted' => $adminLibraryText('proposal_status_accepted'),
    'rejected' => $adminLibraryText('proposal_status_rejected'),
];
$proposalTypeLabels = [
    'category' => $adminLibraryText('proposal_type_category'),
    'subcategory' => $adminLibraryText('proposal_type_subcategory'),
    'subsubcategory' => $adminLibraryText('proposal_type_subsubcategory'),
    'content' => $adminLibraryText('proposal_type_content'),
    'tag' => $adminLibraryText('proposal_type_tag'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? 'upload');
        if ($action === 'update_proposal_status') {
            $proposalId = (int) ($_POST['proposal_id'] ?? 0);
            $proposalStatus = (string) ($_POST['proposal_status'] ?? 'pending');
            $moderationNote = trim((string) ($_POST['moderation_note'] ?? ''));
            if ($proposalId <= 0 || !isset($proposalStatusLabels[$proposalStatus])) {
                throw new RuntimeException('err_required');
            }
            if (!ensure_content_proposals_table()) {
                throw new RuntimeException('storage_unavailable');
            }
            $proposalStmt = db()->prepare('SELECT id, member_id, proposal_type, title, summary, source_ref FROM content_proposals WHERE id = ? AND area = "members_library" LIMIT 1');
            $proposalStmt->execute([$proposalId]);
            $proposal = $proposalStmt->fetch() ?: null;
            if (!is_array($proposal)) {
                throw new RuntimeException('err_required');
            }
            if ($proposalStatus === 'accepted') {
                member_library_apply_accepted_proposal($proposal, $memberLibraryMessages);
            }
            db()->prepare('UPDATE content_proposals SET status = ?, moderation_note = ? WHERE id = ? AND area = "members_library"')
                ->execute([$proposalStatus, $moderationNote !== '' ? $moderationNote : null, $proposalId]);
            set_flash('success', $adminLibraryText('proposal_status_saved'));
            redirect_url($pendingProposalUrl);
        }
        if ($action === 'add_category') {
            $code = library_category_slug((string) ($_POST['category_code'] ?? ''));
            $label = trim((string) ($_POST['category_label'] ?? '')) ?: $code;
            db()->prepare('INSERT INTO member_library_categories (code, label) VALUES (?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')->execute([$code, $label]);
            set_flash('success', (string) $t['ok_category']);
            redirect($adminLibraryRoute);
        }
        if ($action === 'update_category') {
            $code = library_category_slug((string) ($_POST['category_code'] ?? ''));
            $label = trim((string) ($_POST['category_label'] ?? ''));
            if ($code === '' || $label === '') {
                throw new RuntimeException('err_required');
            }
            db()->prepare('INSERT INTO member_library_categories (code, label) VALUES (?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                ->execute([$code, $label]);
            set_flash('success', (string) $t['ok_category']);
            redirect($adminLibraryRoute);
        }
        if ($action === 'delete_category') {
            $code = library_category_slug((string) ($_POST['category_code'] ?? ''));
            if ($code !== 'general') {
                if (function_exists('member_library_ensure_subcategories_table') && member_library_ensure_subcategories_table()) {
                    $subcategoryCountStmt = db()->prepare('SELECT COUNT(*) FROM member_library_subcategories WHERE category_code = ?');
                    $subcategoryCountStmt->execute([$code]);
                    if ((int) ($subcategoryCountStmt->fetchColumn() ?: 0) > 0) {
                        throw new RuntimeException('err_category_has_subcategories');
                    }
                }
                db()->prepare('UPDATE member_library_documents SET category = "general", subcategory = "", subsubcategory = "" WHERE category = ?')->execute([$code]);
                db()->prepare('DELETE FROM member_library_categories WHERE code = ? LIMIT 1')->execute([$code]);
            }
            set_flash('success', (string) $t['ok_category_deleted']);
            redirect($adminLibraryRoute);
        }
        if ($action === 'add_subcategory') {
            $parentCode = library_category_slug((string) ($_POST['subcategory_category'] ?? 'general'));
            $code = library_subcategory_slug((string) ($_POST['subcategory_code'] ?? ''));
            $label = trim((string) ($_POST['subcategory_label'] ?? '')) ?: $code;
            if ($code === '' || $label === '') {
                throw new RuntimeException('err_required');
            }
            db()->prepare('INSERT INTO member_library_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                ->execute([$parentCode, $code, $label]);
            set_flash('success', (string) $t['ok_subcategory']);
            redirect($adminLibraryRoute);
        }
        if ($action === 'update_subcategory') {
            $parentCode = library_category_slug((string) ($_POST['subcategory_category'] ?? 'general'));
            $code = library_subcategory_slug((string) ($_POST['subcategory_code'] ?? ''));
            $label = trim((string) ($_POST['subcategory_label'] ?? ''));
            if ($code === '' || $label === '') {
                throw new RuntimeException('err_required');
            }
            db()->prepare('INSERT INTO member_library_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                ->execute([$parentCode, $code, $label]);
            set_flash('success', (string) $t['ok_subcategory']);
            redirect($adminLibraryRoute);
        }
        if ($action === 'delete_subcategory') {
            $parentCode = library_category_slug((string) ($_POST['subcategory_category'] ?? 'general'));
            $code = library_subcategory_slug((string) ($_POST['subcategory_code'] ?? ''));
            if ($code !== '') {
                if (!function_exists('member_library_ensure_subcategories_table') || !member_library_ensure_subcategories_table()) {
                    throw new RuntimeException('storage_unavailable');
                }
                if (function_exists('member_library_ensure_subsubcategories_table') && member_library_ensure_subsubcategories_table()) {
                    $subsubcategoryCountStmt = db()->prepare('SELECT COUNT(*) FROM member_library_subsubcategories WHERE category_code = ? AND subcategory_code = ?');
                    $subsubcategoryCountStmt->execute([$parentCode, $code]);
                    if ((int) ($subsubcategoryCountStmt->fetchColumn() ?: 0) > 0) {
                        throw new RuntimeException('err_subcategory_has_subsubcategories');
                    }
                }
                $documentCountStmt = db()->prepare('SELECT COUNT(*) FROM member_library_documents WHERE category = ? AND subcategory = ?');
                $documentCountStmt->execute([$parentCode, $code]);
                if ((int) ($documentCountStmt->fetchColumn() ?: 0) > 0) {
                    throw new RuntimeException('err_subcategory_has_documents');
                }
                db()->prepare('DELETE FROM member_library_subcategories WHERE category_code = ? AND code = ? LIMIT 1')->execute([$parentCode, $code]);
            } else {
                throw new RuntimeException('err_required');
            }
            set_flash('success', (string) $t['ok_subcategory_deleted']);
            redirect($adminLibraryRoute);
        }
        if ($action === 'add_subsubcategory') {
            $parentCategory = library_category_slug((string) ($_POST['subsubcategory_category'] ?? 'general'));
            $parentSubcategory = library_subcategory_slug((string) ($_POST['subsubcategory_parent'] ?? ''));
            if (isset($_POST['subsubcategory_parent_ref'])) {
                $parentParts = member_library_subcategory_ref_parts((string) $_POST['subsubcategory_parent_ref']);
                $parentCategory = library_category_slug($parentParts['category'] !== '' ? $parentParts['category'] : 'general');
                $parentSubcategory = library_subcategory_slug($parentParts['subcategory']);
            }
            if ($parentSubcategory !== '') {
                [$parentCategory, $parentSubcategory] = member_library_taxonomy_from_input(
                    $parentCategory,
                    member_library_subcategory_ref($parentCategory, $parentSubcategory),
                    $parentCategory
                );
            }
            $code = library_subsubcategory_slug((string) ($_POST['subsubcategory_code'] ?? ''));
            $label = trim((string) ($_POST['subsubcategory_label'] ?? '')) ?: $code;
            if ($parentSubcategory === '' || $code === '' || $label === '') {
                throw new RuntimeException('err_required');
            }
            if (!function_exists('member_library_ensure_subsubcategories_table') || !member_library_ensure_subsubcategories_table()) {
                throw new RuntimeException('storage_unavailable');
            }
            db()->prepare('INSERT INTO member_library_subsubcategories (category_code, subcategory_code, code, label) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                ->execute([$parentCategory, $parentSubcategory, $code, $label]);
            set_flash('success', (string) $t['ok_subsubcategory']);
            redirect($adminLibraryRoute);
        }
        if ($action === 'update_subsubcategory') {
            $parentCategory = library_category_slug((string) ($_POST['subsubcategory_category'] ?? 'general'));
            $parentSubcategory = library_subcategory_slug((string) ($_POST['subsubcategory_parent'] ?? ''));
            if ($parentSubcategory !== '') {
                [$parentCategory, $parentSubcategory] = member_library_taxonomy_from_input(
                    $parentCategory,
                    member_library_subcategory_ref($parentCategory, $parentSubcategory),
                    $parentCategory
                );
            }
            $code = library_subsubcategory_slug((string) ($_POST['subsubcategory_code'] ?? ''));
            $label = trim((string) ($_POST['subsubcategory_label'] ?? ''));
            if ($parentSubcategory === '' || $code === '' || $label === '') {
                throw new RuntimeException('err_required');
            }
            if (!function_exists('member_library_ensure_subsubcategories_table') || !member_library_ensure_subsubcategories_table()) {
                throw new RuntimeException('storage_unavailable');
            }
            db()->prepare('INSERT INTO member_library_subsubcategories (category_code, subcategory_code, code, label) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                ->execute([$parentCategory, $parentSubcategory, $code, $label]);
            set_flash('success', (string) $t['ok_subsubcategory']);
            redirect($adminLibraryRoute);
        }
        if ($action === 'delete_subsubcategory') {
            $parentCategory = library_category_slug((string) ($_POST['subsubcategory_category'] ?? 'general'));
            $parentSubcategory = library_subcategory_slug((string) ($_POST['subsubcategory_parent'] ?? ''));
            $code = library_subsubcategory_slug((string) ($_POST['subsubcategory_code'] ?? ''));
            if ($parentSubcategory === '' || $code === '') {
                throw new RuntimeException('err_required');
            }
            if (!function_exists('member_library_ensure_subsubcategories_table') || !member_library_ensure_subsubcategories_table()) {
                throw new RuntimeException('storage_unavailable');
            }
            $documentCountStmt = db()->prepare('SELECT COUNT(*) FROM member_library_documents WHERE category = ? AND subcategory = ? AND subsubcategory = ?');
            $documentCountStmt->execute([$parentCategory, $parentSubcategory, $code]);
            if ((int) ($documentCountStmt->fetchColumn() ?: 0) > 0) {
                throw new RuntimeException('err_subsubcategory_has_documents');
            }
            db()->prepare('DELETE FROM member_library_subsubcategories WHERE category_code = ? AND subcategory_code = ? AND code = ? LIMIT 1')
                ->execute([$parentCategory, $parentSubcategory, $code]);
            set_flash('success', (string) $t['ok_subsubcategory_deleted']);
            redirect($adminLibraryRoute);
        }
        if ($action === 'delete_document') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = db()->prepare('SELECT file_path FROM member_library_documents WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $path = (string) ($stmt->fetchColumn() ?: '');
            $safePath = safe_storage_document_path_or_null($path, ['storage/private/library/', 'storage/uploads/library/']);
            if ($safePath !== null) {
                $absolute = storage_document_absolute_path($safePath);
                if (is_file($absolute)) {
                    @unlink($absolute);
                }
            }
            db()->prepare('DELETE FROM member_library_documents WHERE id = ?')->execute([$id]);
            set_flash('success', (string) $t['ok_deleted']);
            redirect($adminLibraryRoute);
        }
        if ($action === 'bulk_delete_documents') {
            $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])), static fn(int $v): bool => $v > 0));
            if ($ids === []) {
                throw new RuntimeException('err_required');
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sel = db()->prepare('SELECT file_path FROM member_library_documents WHERE id IN (' . $placeholders . ')');
            $sel->execute($ids);
            foreach ($sel->fetchAll() ?: [] as $row) {
                $safePath = safe_storage_document_path_or_null((string) ($row['file_path'] ?? ''), ['storage/private/library/', 'storage/uploads/library/']);
                if ($safePath !== null) {
                    $absolute = storage_document_absolute_path($safePath);
                    if (is_file($absolute)) {
                        @unlink($absolute);
                    }
                }
            }
            db()->prepare('DELETE FROM member_library_documents WHERE id IN (' . $placeholders . ')')->execute($ids);
            set_flash('success', (string) $t['ok_deleted']);
            redirect($adminLibraryRoute);
        }
        if ($action === 'merge_tags') {
            $fromTag = trim((string) ($_POST['from_tag'] ?? ''));
            $toTag = trim((string) ($_POST['to_tag'] ?? ''));
            if ($fromTag === '' || $toTag === '') {
                throw new RuntimeException('err_required');
            }

            $fromNorm = library_tag_norm($fromTag);
            $toNorm = library_tag_norm($toTag);
            if ($fromNorm === '' || $toNorm === '') {
                throw new RuntimeException('err_required');
            }

            $rows = db()->query('SELECT id, tags FROM member_library_documents WHERE tags IS NOT NULL AND tags <> ""')->fetchAll() ?: [];
            $updated = 0;
            $updateStmt = db()->prepare('UPDATE member_library_documents SET tags = ? WHERE id = ?');
            foreach ($rows as $row) {
                $docId = (int) ($row['id'] ?? 0);
                if ($docId <= 0) {
                    continue;
                }
                $tags = library_tag_split((string) ($row['tags'] ?? ''));
                if ($tags === []) {
                    continue;
                }
                $changed = false;
                foreach ($tags as $idx => $tag) {
                    if (library_tag_norm($tag) === $fromNorm) {
                        $tags[$idx] = $toTag;
                        $changed = true;
                    }
                }
                if (!$changed) {
                    continue;
                }
                $tags = library_tag_unique($tags);
                $updateStmt->execute([implode(',', $tags), $docId]);
                $updated++;
            }

            set_flash('success', sprintf((string) $t['ok_tags_merged'], $updated));
            redirect($adminLibraryRoute);
        }

        [$category, $subcategory, $subsubcategory] = member_library_taxonomy_from_input(
            (string) ($_POST['category'] ?? 'general'),
            trim((string) ($_POST['subcategory_ref'] ?? '')),
            'general',
            trim((string) ($_POST['subsubcategory_ref'] ?? ''))
        );
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $templateKey = trim((string) ($_POST['template'] ?? ''));
        $templates = library_ingestion_templates();
        $selectedTemplate = $templates[$templateKey] ?? null;
        if (is_array($selectedTemplate) && $category === 'general' && !empty($selectedTemplate['category'])) {
            $category = library_category_slug((string) $selectedTemplate['category']);
        }
        $rawTags = trim((string) ($_POST['tags'] ?? ''));
        $tagsList = library_tag_split($rawTags);
        if (is_array($selectedTemplate) && isset($selectedTemplate['tags']) && is_array($selectedTemplate['tags'])) {
            $tagsList = array_merge($selectedTemplate['tags'], $tagsList);
        }
        $tagsList = library_tag_unique($tagsList);
        $tagsList = library_filter_controlled_tags($tagsList);
        $tags = mb_safe_substr(implode(',', $tagsList), 0, 255);
        $file = $_FILES['document'] ?? null;
        if ($title === '' || !is_array($file)) {
            throw new RuntimeException('err_required');
        }
        $stored = library_store_upload($file, (int) ($user['id'] ?? 0));
        $documentId = member_library_create_document_record(
            (int) ($user['id'] ?? 0),
            $title,
            $category,
            $tags,
            $description,
            (string) $stored['public_path'],
            $subcategory,
            $subsubcategory
        );
        try {
            if (ensure_content_proposals_table()) {
                $proposalSummary = content_proposal_details_text([
                    (string) $memberLibraryMessages['propose_document_category'] => $category,
                    (string) $memberLibraryMessages['propose_document_subcategory'] => $subcategory,
                    (string) $memberLibraryMessages['propose_document_subsubcategory'] => $subsubcategory,
                    (string) $memberLibraryMessages['tags'] => $tags,
                    (string) $memberLibraryMessages['document'] => (string) ($stored['original_name'] ?? ''),
                    (string) $memberLibraryMessages['propose_document_description'] => $description,
                    'Document ID' => (string) $documentId,
                ]);
                content_proposal_create(
                    (int) ($user['id'] ?? 0),
                    'members_library',
                    'content',
                    $title,
                    $proposalSummary,
                    (string) ($user['email'] ?? ''),
                    (string) $stored['public_path'],
                    'accepted'
                );
            }
        } catch (Throwable $throwable) {
            log_structured_event('admin_library_content_record_create_failed', [
                'document_id' => (int) $documentId,
                'message' => $throwable->getMessage(),
            ]);
        }
        notify_member((int) ($user['id'] ?? 0), 'import', (string) $t['notification_import_completed_title'], $title, route_url($adminLibraryRoute));
        set_flash('success', (string) $t['ok_added']);
        redirect($adminLibraryRoute);
    } catch (Throwable $throwable) {
        $key = $throwable->getMessage();
        set_flash('error', (string) ($t[$key] ?? $key));
        redirect($adminLibraryRoute);
    }
}

$categoryOptions = db()->query('SELECT code, label FROM member_library_categories ORDER BY sort_order ASC, label ASC')->fetchAll() ?: [];
$categoryLabels = [];
foreach ($categoryOptions as $catOpt) {
    $catCode = library_category_slug((string) ($catOpt['code'] ?? 'general'));
    $categoryLabels[$catCode] = (string) ($catOpt['label'] ?? $catCode);
}
$subcategoryOptions = function_exists('member_library_subcategory_options') ? member_library_subcategory_options() : [];
$subcategoriesByCategory = [];
$subcategoryLabels = [];
foreach ($subcategoryOptions as $subcatOpt) {
    $parentCode = library_category_slug((string) $subcatOpt['category_code']);
    $subcatCode = library_subcategory_slug((string) $subcatOpt['code']);
    if ($subcatCode === '') {
        continue;
    }
    $subcatLabel = (string) $subcatOpt['label'];
    $subcategoriesByCategory[$parentCode][] = [
        'category_code' => $parentCode,
        'code' => $subcatCode,
        'label' => $subcatLabel,
    ];
    $subcategoryLabels[$parentCode . ':' . $subcatCode] = $subcatLabel;
}
$subsubcategoryOptions = function_exists('member_library_subsubcategory_options') ? member_library_subsubcategory_options() : [];
$subsubcategoriesByParent = [];
$subsubcategoryLabels = [];
foreach ($subsubcategoryOptions as $subsubcatOpt) {
    $parentCode = library_category_slug((string) $subsubcatOpt['category_code']);
    $subcatCode = library_subcategory_slug((string) $subsubcatOpt['subcategory_code']);
    $subsubcatCode = library_subsubcategory_slug((string) $subsubcatOpt['code']);
    if ($subcatCode === '' || $subsubcatCode === '') {
        continue;
    }
    $subsubcatLabel = (string) $subsubcatOpt['label'];
    $subsubcategoriesByParent[$parentCode . ':' . $subcatCode][] = [
        'category_code' => $parentCode,
        'subcategory_code' => $subcatCode,
        'code' => $subsubcatCode,
        'label' => $subsubcatLabel,
    ];
    $subsubcategoryLabels[$parentCode . ':' . $subcatCode . ':' . $subsubcatCode] = $subsubcatLabel;
}
$perPage = 20;
$page = max(1, (int) ($_GET['p'] ?? 1));
$adminCategory = library_category_slug((string) ($_GET['category'] ?? ''));
if ($adminCategory === 'general' && !isset($_GET['category'])) {
    $adminCategory = '';
}
$adminSubcategory = library_subcategory_slug((string) ($_GET['subcategory'] ?? ''));
$adminSubsubcategory = library_subsubcategory_slug((string) ($_GET['subsubcategory'] ?? ''));
$adminSearch = trim((string) ($_GET['q'] ?? ''));
$adminTag = trim((string) ($_GET['tag'] ?? ''));
$where = [];
$params = [];
if ($adminCategory !== '') {
    $where[] = 'category = ?';
    $params[] = $adminCategory;
}
if ($adminSubcategory !== '') {
    $where[] = 'subcategory = ?';
    $params[] = $adminSubcategory;
}
if ($adminSubsubcategory !== '') {
    $where[] = 'subsubcategory = ?';
    $params[] = $adminSubsubcategory;
}
if ($adminSearch !== '') {
    $where[] = '(title LIKE ? OR description LIKE ? OR extracted_text LIKE ?)';
    $needle = '%' . $adminSearch . '%';
    array_push($params, $needle, $needle, $needle);
}
if ($adminTag !== '') {
    $where[] = 'tags LIKE ?';
    $params[] = '%' . $adminTag . '%';
}
$whereSql = $where === [] ? '' : (' WHERE ' . implode(' AND ', $where));
$countStmt = db()->prepare('SELECT COUNT(*) FROM member_library_documents' . $whereSql);
$countStmt->execute($params);
$totalDocuments = (int) ($countStmt->fetchColumn() ?: 0);
$pagination = pagination_state($totalDocuments, $page, $perPage);
$page = $pagination['page'];
$totalPages = $pagination['total_pages'];
$offset = $pagination['offset'];
$stmt = db()->prepare('SELECT id, category, subcategory, subsubcategory, tags, title, description, file_path, extracted_text, uploaded_at FROM member_library_documents' . $whereSql . ' ORDER BY uploaded_at DESC, id DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);
$stmt->execute($params);
$documents = $stmt->fetchAll() ?: [];

$tagRows = db()->query('SELECT tags FROM member_library_documents WHERE tags IS NOT NULL AND tags <> ""')->fetchAll() ?: [];
$tagDuplicates = [];
foreach ($tagRows as $row) {
    $tags = library_tag_split((string) ($row['tags'] ?? ''));
    foreach ($tags as $tag) {
        $norm = library_tag_norm($tag);
        if ($norm === '') {
            continue;
        }
        if (!isset($tagDuplicates[$norm])) {
            $tagDuplicates[$norm] = [];
        }
        $tagDuplicates[$norm][$tag] = ($tagDuplicates[$norm][$tag] ?? 0) + 1;
    }
}
foreach ($tagDuplicates as $norm => $variants) {
    if (count($variants) < 2) {
        unset($tagDuplicates[$norm]);
        continue;
    }
    arsort($variants);
    $tagDuplicates[$norm] = $variants;
}
ksort($tagDuplicates);

$showPendingProposals = (string) ($_GET['status'] ?? '') === 'pending';
$pendingProposals = [];
if ($showPendingProposals && ensure_content_proposals_table()) {
    $pendingStmt = db()->prepare(
        'SELECT cp.id, cp.member_id, cp.proposal_type, cp.title, cp.summary, cp.contact, cp.source_ref, cp.status, cp.moderation_note, cp.created_at, cp.updated_at, m.callsign, m.email
         FROM content_proposals cp
         LEFT JOIN members m ON m.id = cp.member_id
         WHERE cp.area = "members_library" AND cp.status = "pending"
         ORDER BY cp.created_at ASC, cp.id ASC'
    );
    $pendingStmt->execute();
    $pendingProposals = $pendingStmt->fetchAll() ?: [];
}
$proposalSourceUrl = static function (string $sourceRef): string {
    $sourceRef = trim($sourceRef);
    if ($sourceRef === '') {
        return '';
    }
    $safePath = function_exists('member_library_proposal_source_path') ? member_library_proposal_source_path($sourceRef) : '';
    if ($safePath !== '') {
        return '';
    }
    if (preg_match('/https?:\/\/\S+/i', $sourceRef, $match) === 1) {
        return (string) $match[0];
    }
    return '';
};

ob_start();
?>
<div class="card admin-library-shell">
    <header class="admin-library-header">
        <h1><?= e((string) $t['title']) ?></h1>
        <p><?= e((string) $t['intro']) ?></p>
        <div class="admin-library-meta">
            <span class="badge muted"><?= e((string) $t['page']) ?> <?= $page ?> / <?= $totalPages ?></span>
            <span class="badge muted"><?= $totalDocuments ?> <?= e((string) $t['documents']) ?></span>
        </div>
    </header>

    <?php if ($showPendingProposals): ?>
    <section class="admin-library-documents" id="pending-proposals" aria-labelledby="pending-proposals-title">
        <div class="admin-library-meta" style="justify-content:space-between;margin-bottom:.8rem;">
            <h2 id="pending-proposals-title" style="margin:0;"><?= e($adminLibraryText('pending_proposals_title')) ?></h2>
            <a class="button secondary" href="<?= e(route_url($adminLibraryRoute)) ?>"><?= e((string) $t['reset']) ?></a>
        </div>
        <?php if ($pendingProposals === []): ?>
            <article class="card admin-library-empty"><p><?= e($adminLibraryText('pending_proposals_empty')) ?></p></article>
        <?php endif; ?>
        <?php foreach ($pendingProposals as $proposal): ?>
            <?php
            $proposalType = (string) ($proposal['proposal_type'] ?? 'content');
            $proposalStatus = (string) ($proposal['status'] ?? 'pending');
            $sourceUrl = $proposalSourceUrl((string) ($proposal['source_ref'] ?? ''));
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
            <article class="card admin-library-document">
                <p>
                    <span class="badge muted"><?= e((string) ($proposalTypeLabels[$proposalType] ?? $proposalType)) ?></span>
                    <span class="badge muted"><?= e((string) ($proposalStatusLabels[$proposalStatus] ?? $proposalStatus)) ?></span>
                    <span class="badge muted"><?= e(date('d/m/Y H:i', $proposalCreatedTimestamp)) ?></span>
                </p>
                <h3><?= e((string) ($proposal['title'] ?? $adminLibraryText('proposal_default_title'))) ?></h3>
                <p class="help"><?= e($adminLibraryText('proposal_author')) ?>: <?= e($memberLabel) ?></p>
                <?php if (trim((string) ($proposal['summary'] ?? '')) !== ''): ?>
                    <p><?= nl2br(e((string) $proposal['summary'])) ?></p>
                <?php endif; ?>
                <?php if (trim((string) ($proposal['contact'] ?? '')) !== ''): ?>
                    <p class="help"><?= e($adminLibraryText('proposal_contact')) ?>: <?= e((string) $proposal['contact']) ?></p>
                <?php endif; ?>
                <form method="post" class="admin-library-upload-form" data-admin-dirty-track data-confirm-message="<?= e((string) $t['confirm_reject_proposal']) ?>" data-confirm-when-select="proposal_status:rejected">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_proposal_status">
                    <input type="hidden" name="proposal_id" value="<?= (int) ($proposal['id'] ?? 0) ?>">
                    <div class="admin-library-upload-grid">
                        <label class="admin-library-field">
                            <span><?= e($adminLibraryText('proposal_status_label')) ?></span>
                            <select name="proposal_status">
                                <?php foreach ($proposalStatusLabels as $statusCode => $statusLabel): ?>
                                    <option value="<?= e($statusCode) ?>"<?= $proposalStatus === $statusCode ? ' selected' : '' ?>><?= e($statusLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="admin-library-field admin-library-field-wide">
                            <span><?= e($adminLibraryText('proposal_moderation_note')) ?></span>
                            <textarea name="moderation_note" rows="3"><?= e((string) ($proposal['moderation_note'] ?? '')) ?></textarea>
                        </label>
                    </div>
                    <div class="actions">
                        <?php if ($sourceUrl !== ''): ?>
                            <a class="button secondary" href="<?= e($sourceUrl) ?>" target="_blank" rel="noopener"><?= e($adminLibraryText('proposal_open_source')) ?></a>
                        <?php endif; ?>
                        <button class="button" type="submit"><?= e($adminLibraryText('proposal_save_status')) ?></button>
                    </div>
                </form>
            </article>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="admin-library-upload-form" data-admin-dirty-track>
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="upload">
        <div class="admin-library-upload-grid">
            <label class="admin-library-field">
                <span><?= e((string) $t['ingestion_template']) ?></span>
                <select name="template">
                    <option value=""><?= e((string) $t['ingestion_template_none']) ?></option>
                    <option value="training"><?= e((string) $t['ingestion_template_training']) ?></option>
                    <option value="safety"><?= e((string) $t['ingestion_template_safety']) ?></option>
                    <option value="technical"><?= e((string) $t['ingestion_template_technical']) ?></option>
                    <option value="legal"><?= e((string) $t['ingestion_template_legal']) ?></option>
                </select>
            </label>
            <label class="admin-library-field"><span><?= e((string) $t['category_ph']) ?></span><select name="category"><?php foreach ($categoryOptions as $catOpt): ?><option value="<?= e((string) $catOpt['code']) ?>" <?= $adminCategory === (string) $catOpt['code'] ? 'selected' : '' ?>><?= e((string) $catOpt['label']) ?></option><?php endforeach; ?></select></label>
            <label class="admin-library-field">
                <span><?= e((string) $t['subcategory_ph']) ?></span>
                <select name="subcategory_ref">
                    <option value=""><?= e((string) $t['no_subcategory']) ?></option>
                    <?php foreach ($subcategoriesByCategory as $parentCode => $subcatGroup): ?>
                        <optgroup label="<?= e((string) ($categoryLabels[$parentCode] ?? $parentCode)) ?>">
                            <?php foreach ($subcatGroup as $subcatOpt): ?>
                                <option value="<?= e(member_library_subcategory_ref($parentCode, (string) $subcatOpt['code'])) ?>"<?= $adminSubcategory === (string) $subcatOpt['code'] && ($adminCategory === '' || $adminCategory === $parentCode) ? ' selected' : '' ?>><?= e((string) $subcatOpt['label']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="admin-library-field">
                <span><?= e((string) $t['subsubcategory_ph']) ?></span>
                <select name="subsubcategory_ref">
                    <option value=""><?= e((string) $t['no_subsubcategory']) ?></option>
                    <?php foreach ($subsubcategoriesByParent as $parentRef => $subsubcatGroup): ?>
                        <?php [$parentCategoryCode, $parentSubcategoryCode] = array_pad(explode(':', (string) $parentRef, 2), 2, ''); ?>
                        <optgroup label="<?= e((string) ($categoryLabels[$parentCategoryCode] ?? $parentCategoryCode)) ?> / <?= e((string) ($subcategoryLabels[$parentRef] ?? $parentSubcategoryCode)) ?>">
                            <?php foreach ($subsubcatGroup as $subsubcatOpt): ?>
                                <option value="<?= e(member_library_subsubcategory_ref($parentCategoryCode, $parentSubcategoryCode, (string) $subsubcatOpt['code'])) ?>"<?= $adminSubsubcategory === (string) $subsubcatOpt['code'] && ($adminCategory === '' || $adminCategory === $parentCategoryCode) && ($adminSubcategory === '' || $adminSubcategory === $parentSubcategoryCode) ? ' selected' : '' ?>><?= e((string) $subsubcatOpt['label']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="admin-library-field"><span><?= e((string) $t['title_ph']) ?></span><input type="text" name="title" required></label>
            <label class="admin-library-field"><span><?= e((string) $t['tags_ph']) ?></span><input type="text" name="tags" placeholder="<?= e((string) $t['tags_help']) ?>"></label>
            <label class="admin-library-field admin-library-field-wide"><span><?= e((string) $t['desc_ph']) ?></span><textarea name="description"></textarea></label>
            <label class="admin-library-field"><span><?= e((string) $t['file']) ?></span><input type="file" name="document" accept=".pdf,.doc,.docx,.txt,.md,.html,.htm,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,text/html,text/markdown" required></label>
        </div>
        <p class="help"><?= e((string) $t['file_help']) ?></p>
        <button class="button"><?= e((string) $t['upload']) ?></button>
    </form>

    <section class="card admin-library-categories">
        <h2><?= e((string) $t['categories']) ?></h2>
        <form method="post" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="add_category">
            <input type="text" name="category_code" placeholder="<?= e((string) $t['category_code']) ?>">
            <input type="text" name="category_label" placeholder="<?= e((string) $t['category_label']) ?>">
            <button class="button" type="submit"><?= e((string) $t['add_category']) ?></button>
        </form>
        <div class="admin-library-category-list">
            <?php foreach ($categoryOptions as $catOpt): ?>
                <form method="post" class="inline-form admin-library-category-item" data-confirm-message="<?= e((string) $t['confirm_delete']) ?>" data-confirm-when-submit-action="delete_category">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_category">
                    <input type="hidden" name="category_code" value="<?= e((string) $catOpt['code']) ?>">
                    <span class="badge muted taxonomy-pill-category"><?= e((string) $catOpt['code']) ?></span>
                    <input type="text" name="category_label" value="<?= e((string) $catOpt['label']) ?>" maxlength="160" required>
                    <button class="button small" type="submit"><?= e((string) $t['save']) ?></button>
                    <?php if ((string) $catOpt['code'] !== 'general'): ?><button class="button secondary small" type="submit" name="action" value="delete_category"><?= e((string) $t['delete']) ?></button><?php endif; ?>
                </form>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card admin-library-categories">
        <h2><?= e((string) $t['subcategories']) ?></h2>
        <form method="post" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="add_subcategory">
            <select name="subcategory_category" required>
                <?php foreach ($categoryOptions as $catOpt): ?>
                    <option value="<?= e((string) $catOpt['code']) ?>"><?= e((string) $catOpt['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="subcategory_code" placeholder="<?= e((string) $t['subcategory_code']) ?>">
            <input type="text" name="subcategory_label" placeholder="<?= e((string) $t['subcategory_label']) ?>">
            <button class="button" type="submit"><?= e((string) $t['add_subcategory']) ?></button>
        </form>
        <div class="admin-library-category-list">
            <?php foreach ($subcategoriesByCategory as $parentCode => $subcatGroup): ?>
                <?php foreach ($subcatGroup as $subcatOpt): ?>
                    <form method="post" class="inline-form admin-library-category-item" data-confirm-message="<?= e((string) $t['confirm_delete']) ?>" data-confirm-when-submit-action="delete_subcategory">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_subcategory">
                        <input type="hidden" name="subcategory_category" value="<?= e($parentCode) ?>">
                        <input type="hidden" name="subcategory_code" value="<?= e((string) $subcatOpt['code']) ?>">
                        <span class="taxonomy-badge-row">
                            <span class="badge muted taxonomy-pill-category"><?= e((string) ($categoryLabels[$parentCode] ?? $parentCode)) ?></span>
                            <span class="badge muted taxonomy-pill-subcategory"><?= e((string) $subcatOpt['code']) ?></span>
                        </span>
                        <input type="text" name="subcategory_label" value="<?= e((string) $subcatOpt['label']) ?>" maxlength="160" required>
                        <button class="button small" type="submit"><?= e((string) $t['save']) ?></button>
                        <button class="button secondary small" type="submit" name="action" value="delete_subcategory"><?= e((string) $t['delete']) ?></button>
                    </form>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card admin-library-categories">
        <h2><?= e((string) $t['subsubcategories']) ?></h2>
        <form method="post" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="add_subsubcategory">
            <select name="subsubcategory_parent_ref" required>
                <?php foreach ($subcategoriesByCategory as $parentCode => $subcatGroup): ?>
                    <optgroup label="<?= e((string) ($categoryLabels[$parentCode] ?? $parentCode)) ?>">
                        <?php foreach ($subcatGroup as $subcatOpt): ?>
                            <option value="<?= e(member_library_subcategory_ref($parentCode, (string) $subcatOpt['code'])) ?>"><?= e((string) $subcatOpt['label']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <input type="text" name="subsubcategory_code" placeholder="<?= e((string) $t['subsubcategory_code']) ?>">
            <input type="text" name="subsubcategory_label" placeholder="<?= e((string) $t['subsubcategory_label']) ?>">
            <button class="button" type="submit"><?= e((string) $t['add_subsubcategory']) ?></button>
        </form>
        <div class="admin-library-category-list">
            <?php foreach ($subsubcategoriesByParent as $parentRef => $subsubcatGroup): ?>
                <?php [$parentCategoryCode, $parentSubcategoryCode] = array_pad(explode(':', (string) $parentRef, 2), 2, ''); ?>
                <?php foreach ($subsubcatGroup as $subsubcatOpt): ?>
                    <form method="post" class="inline-form admin-library-category-item" data-confirm-message="<?= e((string) $t['confirm_delete']) ?>" data-confirm-when-submit-action="delete_subsubcategory">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_subsubcategory">
                        <input type="hidden" name="subsubcategory_category" value="<?= e($parentCategoryCode) ?>">
                        <input type="hidden" name="subsubcategory_parent" value="<?= e($parentSubcategoryCode) ?>">
                        <input type="hidden" name="subsubcategory_code" value="<?= e((string) $subsubcatOpt['code']) ?>">
                        <span class="taxonomy-badge-row">
                            <span class="badge muted taxonomy-pill-category"><?= e((string) ($categoryLabels[$parentCategoryCode] ?? $parentCategoryCode)) ?></span>
                            <span class="badge muted taxonomy-pill-subcategory"><?= e((string) ($subcategoryLabels[$parentRef] ?? $parentSubcategoryCode)) ?></span>
                            <span class="badge muted taxonomy-pill-subsubcategory"><?= e((string) $subsubcatOpt['code']) ?></span>
                        </span>
                        <input type="text" name="subsubcategory_label" value="<?= e((string) $subsubcatOpt['label']) ?>" maxlength="160" required>
                        <button class="button small" type="submit"><?= e((string) $t['save']) ?></button>
                        <button class="button secondary small" type="submit" name="action" value="delete_subsubcategory"><?= e((string) $t['delete']) ?></button>
                    </form>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card admin-library-categories">
        <h2><?= e((string) $t['tag_cleanup_title']) ?></h2>
        <p class="help"><?= e((string) $t['tag_cleanup_help']) ?></p>
        <form method="post" class="inline-form" style="flex-wrap:wrap;gap:.6rem;">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="merge_tags">
            <input type="text" name="from_tag" placeholder="<?= e((string) $t['tag_from_ph']) ?>" required>
            <input type="text" name="to_tag" placeholder="<?= e((string) $t['tag_to_ph']) ?>" required>
            <button class="button" type="submit"><?= e((string) $t['save']) ?></button>
        </form>
        <?php if ($tagDuplicates === []): ?>
            <p class="help" style="margin-top:.75rem;"><?= e((string) $t['tag_duplicates_empty']) ?></p>
        <?php else: ?>
            <div class="admin-library-category-list" style="margin-top:.75rem;">
                <?php foreach ($tagDuplicates as $normalized => $variants): ?>
                    <article class="card" style="margin:0;">
                        <strong><?= e($normalized) ?></strong>
                        <p class="help" style="margin:.35rem 0 0 0;">
                            <?php $chunks = []; foreach ($variants as $variant => $count) { $chunks[] = $variant . ' (' . $count . ')'; } ?>
                            <?= e(implode(' | ', $chunks)) ?>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-library-documents">
    <form method="get" class="inline-form" style="margin-bottom:.8rem;flex-wrap:wrap;">
        <input type="hidden" name="route" value="<?= e($adminLibraryRoute) ?>">
        <select name="category">
            <option value=""><?= e((string) $t['all_categories']) ?></option>
            <?php foreach ($categoryOptions as $catOpt): ?>
                <option value="<?= e((string) $catOpt['code']) ?>" <?= $adminCategory === (string) $catOpt['code'] ? 'selected' : '' ?>><?= e((string) $catOpt['label']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="subcategory">
            <option value=""><?= e((string) $t['all_subcategories']) ?></option>
            <?php foreach ($subcategoriesByCategory as $parentCode => $subcatGroup): ?>
                <optgroup label="<?= e((string) ($categoryLabels[$parentCode] ?? $parentCode)) ?>">
                    <?php foreach ($subcatGroup as $subcatOpt): ?>
                        <option value="<?= e((string) $subcatOpt['code']) ?>" <?= $adminSubcategory === (string) $subcatOpt['code'] && ($adminCategory === '' || $adminCategory === $parentCode) ? 'selected' : '' ?>><?= e((string) $subcatOpt['label']) ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
        <select name="subsubcategory">
            <option value=""><?= e((string) $t['all_subsubcategories']) ?></option>
            <?php foreach ($subsubcategoriesByParent as $parentRef => $subsubcatGroup): ?>
                <?php [$parentCategoryCode, $parentSubcategoryCode] = array_pad(explode(':', (string) $parentRef, 2), 2, ''); ?>
                <optgroup label="<?= e((string) ($categoryLabels[$parentCategoryCode] ?? $parentCategoryCode)) ?> / <?= e((string) ($subcategoryLabels[$parentRef] ?? $parentSubcategoryCode)) ?>">
                    <?php foreach ($subsubcatGroup as $subsubcatOpt): ?>
                        <option value="<?= e((string) $subsubcatOpt['code']) ?>" <?= $adminSubsubcategory === (string) $subsubcatOpt['code'] && ($adminCategory === '' || $adminCategory === $parentCategoryCode) && ($adminSubcategory === '' || $adminSubcategory === $parentSubcategoryCode) ? 'selected' : '' ?>><?= e((string) $subsubcatOpt['label']) ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
        <input type="search" name="q" value="<?= e($adminSearch) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
        <input type="search" name="tag" value="<?= e($adminTag) ?>" placeholder="<?= e((string) $t['tag_search_ph']) ?>">
        <button class="button" type="submit"><?= e((string) $t['filter']) ?></button>
        <a class="button secondary" href="<?= e(route_url($adminLibraryRoute)) ?>"><?= e((string) $t['reset']) ?></a>
    </form>
    <?php if ($documents === []): ?>
        <article class="card admin-library-empty"><p><?= e((string) $t['empty']) ?></p></article>
    <?php endif; ?>
    <?php if ($documents !== []): ?>
    <form method="post" id="bulk-delete-form" data-confirm-message="<?= e((string) $t['confirm_delete']) ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="bulk_delete_documents">
        <p><button class="button secondary" type="submit"><?= e((string) $t['bulk_delete']) ?></button></p>
    </form>
    <?php endif; ?>
    <?php foreach ($documents as $document): ?>
        <?php $safePath = safe_storage_document_path_or_null((string) ($document['file_path'] ?? ''), ['storage/private/library/', 'storage/uploads/library/']); if ($safePath === null) { continue; } ?>
        <?php $extension = strtolower(pathinfo($safePath, PATHINFO_EXTENSION)); ?>
        <?php $documentId = (int) ($document['id'] ?? 0); ?>
        <?php $documentCategory = library_category_slug((string) ($document['category'] ?? 'general')); ?>
        <?php $documentSubcategory = library_subcategory_slug((string) ($document['subcategory'] ?? '')); ?>
        <?php $documentSubsubcategory = library_subsubcategory_slug((string) ($document['subsubcategory'] ?? '')); ?>
        <?php $documentCategoryLabel = (string) ($categoryLabels[$documentCategory] ?? $documentCategory); ?>
        <?php $documentSubcategoryLabel = $documentSubcategory !== '' ? (string) ($subcategoryLabels[$documentCategory . ':' . $documentSubcategory] ?? $documentSubcategory) : ''; ?>
        <?php $documentSubsubcategoryLabel = $documentSubcategory !== '' && $documentSubsubcategory !== '' ? (string) ($subsubcategoryLabels[$documentCategory . ':' . $documentSubcategory . ':' . $documentSubsubcategory] ?? $documentSubsubcategory) : ''; ?>
        <?php $documentPreviewUrl = $documentId > 0 ? route_url('member_library_preview', ['id' => $documentId]) . '#view=Fit' : ''; ?>
        <?php $documentDownloadUrl = $documentId > 0 ? route_url('member_library_preview', ['id' => $documentId, 'download' => '1']) : ''; ?>
        <article class="card admin-library-document">
            <p><input type="checkbox" form="bulk-delete-form" name="ids[]" value="<?= $documentId ?>"> <span class="help"><?= e((string) $t['select']) ?></span></p>
            <p>
                <span class="badge muted taxonomy-pill-category"><?= e($documentCategoryLabel) ?></span>
                <?php if ($documentSubcategoryLabel !== ''): ?><span class="badge muted taxonomy-pill-subcategory"><?= e($documentSubcategoryLabel) ?></span><?php endif; ?>
                <?php if ($documentSubsubcategoryLabel !== ''): ?><span class="badge muted taxonomy-pill-subsubcategory"><?= e($documentSubsubcategoryLabel) ?></span><?php endif; ?>
                <span class="badge muted"><?= e(strtoupper($extension)) ?></span>
            </p>
            <h3><?= e((string) $document['title']) ?></h3>
            <p><?= e((string) ($document['description'] ?? '')) ?></p>
            <?php if (trim((string) ($document['tags'] ?? '')) !== ''): ?><p class="help"><?= e((string) $t['tags']) ?>: <?= e((string) $document['tags']) ?></p><?php endif; ?>
            <?php if (trim((string) ($document['extracted_text'] ?? '')) !== ''): ?><p class="help"><?= e(mb_safe_strimwidth((string) $document['extracted_text'], 0, 220, '...')) ?></p><?php endif; ?>
            <?php if ($extension === 'pdf' && $documentPreviewUrl !== ''): ?>
                <details class="admin-library-preview-toggle"><summary><?= e((string) $t['preview']) ?></summary><iframe src="<?= e($documentPreviewUrl) ?>" class="admin-library-pdf-preview" loading="lazy" title="<?= e((string) $document['title']) ?>"></iframe></details>
            <?php endif; ?>
            <div class="actions">
                <?php if ($documentDownloadUrl !== ''): ?>
                    <a class="button secondary" href="<?= e($documentDownloadUrl) ?>" target="_blank" rel="noopener"><?= e((string) $t['open']) ?></a>
                <?php endif; ?>
                <form method="post" data-confirm-message="<?= e((string) $t['confirm_delete']) ?>">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete_document">
                    <input type="hidden" name="id" value="<?= $documentId ?>">
                    <button class="button secondary" type="submit"><?= e((string) $t['delete_document']) ?></button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
    </section>
    <?php if ($totalPages > 1): ?>
        <nav class="admin-library-pagination" aria-label="Pagination documents">
            <?php if ($page > 1): ?><a class="button secondary" href="<?= e(route_url_clean($adminLibraryRoute, ['category' => $adminCategory, 'subcategory' => $adminSubcategory, 'subsubcategory' => $adminSubsubcategory, 'q' => $adminSearch, 'tag' => $adminTag, 'p' => $page - 1])) ?>">&larr; <?= e((string) $t['prev']) ?></a><?php endif; ?>
            <span class="badge muted"><?= e((string) $t['page']) ?> <?= $page ?> / <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?><a class="button secondary" href="<?= e(route_url_clean($adminLibraryRoute, ['category' => $adminCategory, 'subcategory' => $adminSubcategory, 'subsubcategory' => $adminSubsubcategory, 'q' => $adminSearch, 'tag' => $adminTag, 'p' => $page + 1])) ?>"><?= e((string) $t['next']) ?> &rarr;</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
<?php echo render_layout((string) ob_get_clean(), (string) $t['title']);
