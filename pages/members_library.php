<?php
declare(strict_types=1);

require_login();
$locale = current_locale();
$t = i18n_domain_locale('members_library', $locale);
$memberAreaLabel = member_area_eyebrow_label($locale);
/** @var array{id:int} $user */
$user = current_user() ?? ['id' => 0];
$canManageLibrary = has_permission('admin.access');
$proposalContactDefault = trim((string) ($user['email'] ?? ''));
if ($proposalContactDefault === '') {
    $proposalContactDefault = trim((string) ($user['callsign'] ?? ''));
}
if ($proposalContactDefault === '') {
    $proposalContactDefault = trim((string) ($user['full_name'] ?? ''));
}
$membersLibraryReturnUrl = static function (): string {
    $page = max(1, (int) ($_POST['return_p'] ?? $_GET['p'] ?? 1));

    return route_url_clean('members_library', [
        'category' => (string) ($_POST['return_category'] ?? $_GET['category'] ?? ''),
        'subcategory' => (string) ($_POST['return_subcategory'] ?? $_GET['subcategory'] ?? ''),
        'subsubcategory' => (string) ($_POST['return_subsubcategory'] ?? $_GET['subsubcategory'] ?? ''),
        'q' => (string) ($_POST['return_q'] ?? $_GET['q'] ?? ''),
        'tag' => (string) ($_POST['return_tag'] ?? $_GET['tag'] ?? ''),
        'favorites' => (string) ($_POST['return_favorites'] ?? $_GET['favorites'] ?? '') === '1' ? '1' : '',
        'p' => $page > 1 ? $page : '',
    ]);
};
set_page_meta(['title' => (string) $t['title'], 'description' => (string) $t['meta_desc'], 'robots' => 'noindex,follow']);

if (!ensure_member_library_table()) {
    echo render_layout('<div class="card"><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['title']);
    return;
}
member_library_ensure_categories_table();
member_library_ensure_subcategories_table();
member_library_ensure_subsubcategories_table();
member_library_sync_accepted_proposals($t);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'toggle_favorite_document') {
            $documentId = (int) ($_POST['document_id'] ?? 0);
            if ($documentId > 0) {
                $docStmt = db()->prepare('SELECT id, title, category, subcategory, subsubcategory, tags FROM member_library_documents WHERE id = ? LIMIT 1');
                $docStmt->execute([$documentId]);
                $docRow = $docStmt->fetch() ?: null;
                if ($docRow !== null) {
                    $docTitle = trim((string) ($docRow['title'] ?? $t['document']));
                    $docCategory = trim((string) ($docRow['category'] ?? ''));
                    $docSubcategory = trim((string) ($docRow['subcategory'] ?? ''));
                    $docSubsubcategory = trim((string) ($docRow['subsubcategory'] ?? ''));
                    $docTags = trim((string) ($docRow['tags'] ?? ''));
                    $favoriteUrl = route_url_clean('members_library', ['q' => $docTitle, 'category' => $docCategory, 'subcategory' => $docSubcategory, 'subsubcategory' => $docSubsubcategory, 'tag' => $docTags]);
                    $saved = favorite_toggle((int) $user['id'], 'library_document', (int) $docRow['id'], $docTitle, $favoriteUrl);
                    notify_member((int) $user['id'], 'favorite', $saved ? (string) $t['favorite_added'] : (string) $t['favorite_removed'], $docTitle, $favoriteUrl);
                    set_flash('success', $saved ? (string) $t['favorite_added_msg'] : (string) $t['favorite_removed_msg']);
                }
            }
            redirect_url(route_url_clean('members_library', ['category' => (string) ($_GET['category'] ?? ''), 'subcategory' => (string) ($_GET['subcategory'] ?? ''), 'subsubcategory' => (string) ($_GET['subsubcategory'] ?? ''), 'q' => (string) ($_GET['q'] ?? ''), 'tag' => (string) ($_GET['tag'] ?? ''), 'favorites' => (string) ($_GET['favorites'] ?? '') === '1' ? '1' : '', 'p' => max(1, (int) ($_GET['p'] ?? 1))]));
        }

        if ($action === 'update_document' || $action === 'delete_document') {
            $documentId = (int) ($_POST['document_id'] ?? 0);
            if ($documentId <= 0) {
                throw new RuntimeException((string) $t['invalid']);
            }

            $docStmt = db()->prepare('SELECT * FROM member_library_documents WHERE id = ? LIMIT 1');
            $docStmt->execute([$documentId]);
            $document = $docStmt->fetch() ?: null;
            if (!is_array($document)) {
                throw new RuntimeException((string) $t['document_missing']);
            }
            if (!$canManageLibrary && (int) ($document['member_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
                throw new RuntimeException((string) $t['document_forbidden']);
            }

            $file = $_FILES['document_file'] ?? null;
            $hasReplacementFile = is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            $sourceRef = '';
            $title = content_proposal_clean_single_line((string) ($_POST['document_title'] ?? $document['title'] ?? ''), 190);
            $documentCategory = member_library_category_slug((string) ($_POST['document_category'] ?? $document['category'] ?? 'general'));
            $documentSubcategory = member_library_subcategory_slug((string) ($document['subcategory'] ?? ''));
            $documentSubsubcategory = member_library_subsubcategory_slug((string) ($document['subsubcategory'] ?? ''));
            if (array_key_exists('document_subcategory_ref', $_POST)) {
                [$documentCategory, $documentSubcategory, $documentSubsubcategory] = member_library_taxonomy_from_input(
                    (string) ($_POST['document_category'] ?? $document['category'] ?? 'general'),
                    trim((string) ($_POST['document_subcategory_ref'] ?? '')),
                    (string) ($document['category'] ?? 'general'),
                    trim((string) ($_POST['document_subsubcategory_ref'] ?? ''))
                );
            }
            $documentTags = member_library_clean_tags((string) ($_POST['document_tags'] ?? $document['tags'] ?? ''));
            $description = content_proposal_clean_multiline((string) ($_POST['document_description'] ?? $document['description'] ?? ''), 1800);

            if ($title === '') {
                throw new RuntimeException((string) $t['document_title_required']);
            }

            if ($action === 'delete_document') {
                if ($canManageLibrary) {
                    member_library_delete_document_record($documentId);
                    set_flash('success', (string) $t['document_deleted']);
                    redirect_url($membersLibraryReturnUrl());
                }

                $sourceRef = (string) ($document['file_path'] ?? '');
                $proposalSummary = content_proposal_details_text([
                    'Action' => 'delete_document',
                    'Document ID' => (string) $documentId,
                    (string) $t['propose_document_category'] => (string) ($document['category'] ?? 'general'),
                    (string) $t['propose_document_subcategory'] => (string) ($document['subcategory'] ?? ''),
                    (string) $t['propose_document_subsubcategory'] => (string) ($document['subsubcategory'] ?? ''),
                    (string) $t['tags'] => (string) ($document['tags'] ?? ''),
                    (string) $t['propose_document_description'] => mb_safe_substr((string) ($document['description'] ?? ''), 0, 1800),
                ]);
                $proposalId = content_proposal_create((int) $user['id'], 'members_library', 'content', $title, $proposalSummary, (string) ($user['email'] ?? ''), $sourceRef, 'pending');
                content_proposal_notify_site((string) $t['document_change_subject'], [
                    'area' => 'members_library',
                    'proposal_type' => 'content',
                    'title' => $title,
                    'summary' => $proposalSummary,
                    'contact' => (string) ($user['email'] ?? ''),
                    'source_ref' => 'content_proposals#' . $proposalId,
                ]);
                set_flash('success', (string) $t['document_change_recorded']);
                redirect('my_requests');
            }

            if ($hasReplacementFile) {
                $stored = member_library_store_document_upload($file, (int) ($user['id'] ?? 0), $canManageLibrary ? 'doc' : 'proposal_doc');
                $sourceRef = (string) $stored['public_path'];
            }

            if ($canManageLibrary) {
                member_library_update_document_record($documentId, $title, $documentCategory, $documentTags, $description, $sourceRef, $documentSubcategory, $documentSubsubcategory);
                set_flash('success', (string) $t['document_updated']);
                redirect_url($membersLibraryReturnUrl());
            }

            $proposalSummary = content_proposal_details_text([
                'Action' => 'update_document',
                'Document ID' => (string) $documentId,
                (string) $t['propose_document_category'] => $documentCategory,
                (string) $t['propose_document_subcategory'] => $documentSubcategory,
                (string) $t['propose_document_subsubcategory'] => $documentSubsubcategory,
                (string) $t['tags'] => $documentTags,
                (string) $t['propose_document_description'] => $description,
            ]);
            $proposalId = content_proposal_create((int) $user['id'], 'members_library', 'content', $title, $proposalSummary, (string) ($user['email'] ?? ''), $sourceRef, 'pending');
            content_proposal_notify_site((string) $t['document_change_subject'], [
                'area' => 'members_library',
                'proposal_type' => 'content',
                'title' => $title,
                'summary' => $proposalSummary,
                'contact' => (string) ($user['email'] ?? ''),
                'source_ref' => 'content_proposals#' . $proposalId,
            ]);
            set_flash('success', (string) $t['document_change_recorded']);
            redirect('my_requests');
        }

        if ($action === 'propose_category') {
            $proposalTitle = (string) ($_POST['proposal_category_name'] ?? $_POST['proposal_category'] ?? '');
            $proposalContact = (string) ($_POST['proposal_contact'] ?? $proposalContactDefault);
            if (trim($proposalContact) === '') {
                $proposalContact = $proposalContactDefault;
            }
            $proposalSummary = content_proposal_details_text([
                (string) $t['propose_category_reason'] => (string) ($_POST['proposal_reason'] ?? ''),
            ]);
            $autoAccept = has_permission('admin.access');
            if ($autoAccept) {
                if (!member_library_ensure_categories_table()) {
                    throw new RuntimeException((string) $t['storage_unavailable']);
                }
                $label = content_proposal_clean_single_line($proposalTitle, 160);
                if ($label === '') {
                    throw new RuntimeException((string) $t['invalid']);
                }
                $code = member_library_category_slug($label);
                db()->prepare('INSERT INTO member_library_categories (code, label) VALUES (?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                    ->execute([$code, $label]);
                set_flash('success', (string) $t['category_created_direct']);
                redirect_url(route_url_clean('members_library', ['category' => $code]));
            }
            $proposalId = content_proposal_create((int) $user['id'], 'members_library', 'category', $proposalTitle, $proposalSummary, $proposalContact);
            content_proposal_notify_site((string) $t['propose_category_subject'], [
                'area' => 'members_library',
                'proposal_type' => 'category',
                'title' => content_proposal_clean_single_line($proposalTitle, 190),
                'summary' => $proposalSummary,
                'contact' => content_proposal_clean_single_line($proposalContact, 220),
                'source_ref' => 'content_proposals#' . $proposalId,
            ]);
            set_flash('success', (string) $t['proposal_recorded']);
            redirect('my_requests');
        }

        if ($action === 'propose_subcategory') {
            $proposalTitle = (string) ($_POST['proposal_subcategory_name'] ?? '');
            $parentCategory = member_library_category_slug((string) ($_POST['proposal_parent_category'] ?? 'general'));
            if ($parentCategory === '') {
                $parentCategory = 'general';
            }
            $proposalContact = (string) ($_POST['proposal_contact'] ?? $proposalContactDefault);
            if (trim($proposalContact) === '') {
                $proposalContact = $proposalContactDefault;
            }
            $proposalSummary = content_proposal_details_text([
                (string) $t['propose_document_category'] => $parentCategory,
                (string) $t['propose_document_subcategory'] => $proposalTitle,
                (string) $t['propose_subcategory_reason'] => (string) ($_POST['proposal_reason'] ?? ''),
            ]);
            $autoAccept = has_permission('admin.access');
            if ($autoAccept) {
                if (!member_library_ensure_subcategories_table()) {
                    throw new RuntimeException((string) $t['storage_unavailable']);
                }
                $label = content_proposal_clean_single_line($proposalTitle, 160);
                $code = member_library_subcategory_slug($label);
                if ($label === '' || $code === '') {
                    throw new RuntimeException((string) $t['invalid']);
                }
                db()->prepare('INSERT INTO member_library_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                    ->execute([$parentCategory, $code, $label]);
                set_flash('success', (string) $t['subcategory_created_direct']);
                redirect_url(route_url_clean('members_library', ['category' => $parentCategory, 'subcategory' => $code]));
            }
            $proposalId = content_proposal_create((int) $user['id'], 'members_library', 'subcategory', $proposalTitle, $proposalSummary, $proposalContact);
            content_proposal_notify_site((string) $t['propose_subcategory_subject'], [
                'area' => 'members_library',
                'proposal_type' => 'subcategory',
                'title' => content_proposal_clean_single_line($proposalTitle, 190),
                'summary' => $proposalSummary,
                'contact' => content_proposal_clean_single_line($proposalContact, 220),
                'source_ref' => 'content_proposals#' . $proposalId,
            ]);
            set_flash('success', (string) $t['proposal_recorded']);
            redirect('my_requests');
        }

        if ($action === 'propose_subsubcategory') {
            $proposalTitle = (string) ($_POST['proposal_subsubcategory_name'] ?? '');
            $parentSubcategoryRef = trim((string) ($_POST['proposal_parent_subcategory_ref'] ?? ''));
            $parentSubcategoryParts = member_library_subcategory_ref_parts($parentSubcategoryRef);
            [$parentCategory, $parentSubcategory] = member_library_taxonomy_from_input(
                $parentSubcategoryParts['category'] !== '' ? $parentSubcategoryParts['category'] : (string) ($_POST['proposal_parent_category'] ?? 'general'),
                $parentSubcategoryRef
            );
            if ($parentSubcategory === '') {
                throw new RuntimeException((string) $t['err_subcategory_required']);
            }
            $proposalContact = (string) ($_POST['proposal_contact'] ?? $proposalContactDefault);
            if (trim($proposalContact) === '') {
                $proposalContact = $proposalContactDefault;
            }
            $proposalSummary = content_proposal_details_text([
                (string) $t['propose_document_category'] => $parentCategory,
                (string) $t['propose_document_subcategory'] => $parentSubcategory,
                (string) $t['propose_document_subsubcategory'] => $proposalTitle,
                (string) $t['propose_subsubcategory_reason'] => (string) ($_POST['proposal_reason'] ?? ''),
            ]);
            $autoAccept = has_permission('admin.access');
            if ($autoAccept) {
                if (!member_library_ensure_subsubcategories_table()) {
                    throw new RuntimeException((string) $t['storage_unavailable']);
                }
                $label = content_proposal_clean_single_line($proposalTitle, 160);
                $code = member_library_subsubcategory_slug($label);
                if ($label === '' || $code === '') {
                    throw new RuntimeException((string) $t['invalid']);
                }
                db()->prepare('INSERT INTO member_library_subsubcategories (category_code, subcategory_code, code, label) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                    ->execute([$parentCategory, $parentSubcategory, $code, $label]);
                set_flash('success', (string) $t['subsubcategory_created_direct']);
                redirect_url(route_url_clean('members_library', ['category' => $parentCategory, 'subcategory' => $parentSubcategory, 'subsubcategory' => $code]));
            }
            $proposalId = content_proposal_create((int) $user['id'], 'members_library', 'subsubcategory', $proposalTitle, $proposalSummary, $proposalContact);
            content_proposal_notify_site((string) $t['propose_subsubcategory_subject'], [
                'area' => 'members_library',
                'proposal_type' => 'subsubcategory',
                'title' => content_proposal_clean_single_line($proposalTitle, 190),
                'summary' => $proposalSummary,
                'contact' => content_proposal_clean_single_line($proposalContact, 220),
                'source_ref' => 'content_proposals#' . $proposalId,
            ]);
            set_flash('success', (string) $t['proposal_recorded']);
            redirect('my_requests');
        }

        if ($action === 'propose_tag') {
            $proposalTitle = (string) ($_POST['proposal_tag'] ?? '');
            $proposalContact = (string) ($_POST['proposal_contact'] ?? $proposalContactDefault);
            if (trim($proposalContact) === '') {
                $proposalContact = $proposalContactDefault;
            }
            $proposalSummary = content_proposal_details_text([
                (string) $t['propose_tag_reason'] => (string) ($_POST['proposal_reason'] ?? ''),
            ]);
            $proposalStatus = has_permission('admin.access') ? 'accepted' : 'pending';
            $proposalId = content_proposal_create((int) $user['id'], 'members_library', 'tag', $proposalTitle, $proposalSummary, $proposalContact, '', $proposalStatus);
            if ($proposalStatus === 'accepted') {
                set_flash('success', (string) $t['tag_created_direct']);
                redirect_url(route_url('members_library'));
            }

            content_proposal_notify_site((string) $t['propose_tag_subject'], [
                'area' => 'members_library',
                'proposal_type' => 'tag',
                'title' => content_proposal_clean_single_line($proposalTitle, 190),
                'summary' => $proposalSummary,
                'contact' => content_proposal_clean_single_line($proposalContact, 220),
                'source_ref' => 'content_proposals#' . $proposalId,
            ]);
            set_flash('success', (string) $t['proposal_recorded']);
            redirect('my_requests');
        }

        if ($action === 'propose_document') {
            $proposalTitle = content_proposal_clean_single_line((string) ($_POST['proposal_title'] ?? ''), 190);
            if ($proposalTitle === '') {
                throw new RuntimeException((string) $t['invalid']);
            }
            [$proposalCategory, $proposalSubcategory, $proposalSubsubcategory] = member_library_taxonomy_from_input(
                (string) ($_POST['proposal_category'] ?? 'general'),
                trim((string) ($_POST['proposal_subcategory_ref'] ?? '')),
                'general',
                trim((string) ($_POST['proposal_subsubcategory_ref'] ?? ''))
            );
            $proposalTags = content_proposal_clean_single_line((string) ($_POST['proposal_tags'] ?? ''), 255);
            $proposalContact = (string) ($_POST['proposal_contact'] ?? $proposalContactDefault);
            if (trim($proposalContact) === '') {
                $proposalContact = $proposalContactDefault;
            }
            $storedDocument = member_library_store_proposed_document_upload($_FILES['proposal_file'] ?? null, (int) $user['id']);
            $proposalFilePath = (string) $storedDocument['public_path'];
            $proposalSummary = content_proposal_details_text([
                (string) $t['propose_document_category'] => $proposalCategory,
                (string) $t['propose_document_subcategory'] => $proposalSubcategory,
                (string) $t['propose_document_subsubcategory'] => $proposalSubsubcategory,
                (string) $t['tags'] => $proposalTags,
                (string) $t['document'] => (string) $storedDocument['original_name'],
                (string) $t['propose_document_description'] => (string) ($_POST['proposal_description'] ?? ''),
            ]);
            $autoAccept = has_permission('admin.access');
            $proposalStatus = $autoAccept ? 'accepted' : 'pending';
            $proposalId = content_proposal_create((int) $user['id'], 'members_library', 'content', $proposalTitle, $proposalSummary, $proposalContact, $proposalFilePath, $proposalStatus);
            if ($autoAccept) {
                member_library_create_document_record(
                    (int) $user['id'],
                    $proposalTitle,
                    $proposalCategory,
                    $proposalTags,
                    (string) ($_POST['proposal_description'] ?? ''),
                    $proposalFilePath,
                    $proposalSubcategory,
                    $proposalSubsubcategory
                );
                set_flash('success', (string) $t['document_validated_direct']);
                redirect_url(route_url('members_library'));
            }
            content_proposal_notify_site((string) $t['propose_document_subject'], [
                'area' => 'members_library',
                'proposal_type' => 'content',
                'title' => content_proposal_clean_single_line($proposalTitle, 190),
                'summary' => $proposalSummary,
                'contact' => content_proposal_clean_single_line($proposalContact, 220),
                'source_ref' => 'content_proposals#' . $proposalId,
            ]);
            set_flash('success', (string) $t['proposal_recorded']);
            redirect('my_requests');
        }

        throw new RuntimeException((string) $t['invalid']);
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect_url($membersLibraryReturnUrl());
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 120) {
    $search = mb_substr($search, 0, 120);
}
$category = member_library_category_slug((string) ($_GET['category'] ?? ''));
if ($category === 'general' && !isset($_GET['category'])) {
    $category = '';
}
$subcategory = member_library_subcategory_slug((string) ($_GET['subcategory'] ?? ''));
$subsubcategory = member_library_subsubcategory_slug((string) ($_GET['subsubcategory'] ?? ''));
$tag = trim((string) ($_GET['tag'] ?? ''));
$favoriteDocumentIds = member_library_favorite_document_ids((int) ($user['id'] ?? 0));
$favoriteDocumentCount = count($favoriteDocumentIds);
$favoritesOnly = (string) ($_GET['favorites'] ?? '') === '1' && $favoriteDocumentCount > 0;
$favoritesLabel = member_library_favorites_label($t, $locale);
$generalCategoryLabel = (string) $t['category_general'];
$page = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 12;

$documentCategories = db()->query('SELECT category, COUNT(*) AS total FROM member_library_documents GROUP BY category ORDER BY category')->fetchAll() ?: [];
$categoryMap = [];
foreach ($documentCategories as $catRow) {
    $catCode = trim((string) ($catRow['category'] ?? 'general'));
    if ($catCode === '') {
        $catCode = 'general';
    }
    $categoryMap[$catCode] = [
        'category' => $catCode,
        'label' => $catCode === 'general' ? $generalCategoryLabel : $catCode,
        'total' => (int) ($catRow['total'] ?? 0),
    ];
}
foreach (member_library_default_categories() as $defaultCategory) {
    $catCode = member_library_category_slug((string) ($defaultCategory['code'] ?? 'general'));
    $catLabel = trim((string) ($defaultCategory['label'] ?? $catCode));
    if ($catCode === '') {
        continue;
    }
    if ($catLabel === '') {
        $catLabel = $catCode === 'general' ? $generalCategoryLabel : $catCode;
    }
    if (!isset($categoryMap[$catCode])) {
        $categoryMap[$catCode] = [
            'category' => $catCode,
            'label' => $catLabel,
            'total' => 0,
        ];
    } else {
        $categoryMap[$catCode]['label'] = $catLabel;
    }
}
if (member_library_ensure_categories_table()) {
    try {
        foreach ((db()->query('SELECT code, label FROM member_library_categories ORDER BY sort_order ASC, label ASC')->fetchAll() ?: []) as $catRow) {
            $catCode = member_library_category_slug((string) ($catRow['code'] ?? 'general'));
            $catLabel = trim((string) ($catRow['label'] ?? $catCode));
            if ($catLabel === '') {
                $catLabel = $catCode;
            }
            if ($catCode === 'general' && strcasecmp($catLabel, 'general') === 0) {
                $catLabel = $generalCategoryLabel;
            }
            if (!isset($categoryMap[$catCode])) {
                $categoryMap[$catCode] = ['category' => $catCode, 'label' => $catLabel, 'total' => 0];
            } else {
                $categoryMap[$catCode]['label'] = $catLabel;
            }
        }
    } catch (Throwable) {
        // Keep document-derived categories if the optional category table cannot be read.
    }
}
$categories = array_values($categoryMap);
$categoryLabels = [];
foreach ($categories as $catInfo) {
    $catCode = trim((string) ($catInfo['category'] ?? ''));
    if ($catCode !== '') {
        $categoryLabels[$catCode] = (string) ($catInfo['label'] ?? ($catCode === 'general' ? $generalCategoryLabel : $catCode));
    }
}
$subcategoryCounts = [];
try {
    $subcategoryCountRows = db()->query('SELECT category, subcategory, COUNT(*) AS total FROM member_library_documents WHERE subcategory IS NOT NULL AND subcategory <> "" GROUP BY category, subcategory ORDER BY category ASC, subcategory ASC')->fetchAll() ?: [];
    foreach ($subcategoryCountRows as $subcatCountRow) {
        $parentCode = member_library_category_slug((string) ($subcatCountRow['category'] ?? 'general'));
        $subcatCode = member_library_subcategory_slug((string) ($subcatCountRow['subcategory'] ?? ''));
        if ($subcatCode !== '') {
            $subcategoryCounts[$parentCode . ':' . $subcatCode] = (int) ($subcatCountRow['total'] ?? 0);
        }
    }
} catch (Throwable) {
    $subcategoryCounts = [];
}
$subcategoriesByCategory = [];
$subcategoryLabels = [];
foreach (member_library_subcategory_options() as $subcatOption) {
    $parentCode = member_library_category_slug((string) ($subcatOption['category_code'] ?? 'general'));
    $subcatCode = member_library_subcategory_slug((string) ($subcatOption['code'] ?? ''));
    $subcatLabel = trim((string) ($subcatOption['label'] ?? $subcatCode));
    if ($parentCode === '' || $subcatCode === '' || $subcatLabel === '') {
        continue;
    }
    $subcatInfo = [
        'category_code' => $parentCode,
        'code' => $subcatCode,
        'label' => $subcatLabel,
        'total' => (int) ($subcategoryCounts[$parentCode . ':' . $subcatCode] ?? 0),
    ];
    $subcategoriesByCategory[$parentCode][] = $subcatInfo;
    $subcategoryLabels[$parentCode . ':' . $subcatCode] = $subcatLabel;
}
$subsubcategoryCounts = [];
try {
    $subsubcategoryCountRows = db()->query('SELECT category, subcategory, subsubcategory, COUNT(*) AS total FROM member_library_documents WHERE subsubcategory IS NOT NULL AND subsubcategory <> "" GROUP BY category, subcategory, subsubcategory ORDER BY category ASC, subcategory ASC, subsubcategory ASC')->fetchAll() ?: [];
    foreach ($subsubcategoryCountRows as $subsubcatCountRow) {
        $parentCode = member_library_category_slug((string) ($subsubcatCountRow['category'] ?? 'general'));
        $subcatCode = member_library_subcategory_slug((string) ($subsubcatCountRow['subcategory'] ?? ''));
        $subsubcatCode = member_library_subsubcategory_slug((string) ($subsubcatCountRow['subsubcategory'] ?? ''));
        if ($subcatCode !== '' && $subsubcatCode !== '') {
            $subsubcategoryCounts[$parentCode . ':' . $subcatCode . ':' . $subsubcatCode] = (int) ($subsubcatCountRow['total'] ?? 0);
        }
    }
} catch (Throwable) {
    $subsubcategoryCounts = [];
}
$subsubcategoriesByParent = [];
$subsubcategoryLabels = [];
foreach (member_library_subsubcategory_options() as $subsubcatOption) {
    $parentCode = member_library_category_slug((string) ($subsubcatOption['category_code'] ?? 'general'));
    $subcatCode = member_library_subcategory_slug((string) ($subsubcatOption['subcategory_code'] ?? ''));
    $subsubcatCode = member_library_subsubcategory_slug((string) ($subsubcatOption['code'] ?? ''));
    $subsubcatLabel = trim((string) ($subsubcatOption['label'] ?? $subsubcatCode));
    if ($parentCode === '' || $subcatCode === '' || $subsubcatCode === '' || $subsubcatLabel === '') {
        continue;
    }
    $subsubcatInfo = [
        'category_code' => $parentCode,
        'subcategory_code' => $subcatCode,
        'code' => $subsubcatCode,
        'label' => $subsubcatLabel,
        'total' => (int) ($subsubcategoryCounts[$parentCode . ':' . $subcatCode . ':' . $subsubcatCode] ?? 0),
    ];
    $subsubcategoriesByParent[$parentCode . ':' . $subcatCode][] = $subsubcatInfo;
    $subsubcategoryLabels[$parentCode . ':' . $subcatCode . ':' . $subsubcatCode] = $subsubcatLabel;
}
$visibleCategories = member_library_visible_categories($categories);
$visibleSubcategoriesByCategory = member_library_visible_subcategories_by_category($subcategoriesByCategory);
$visibleSubsubcategoriesByParent = member_library_visible_subsubcategories_by_parent($subsubcategoriesByParent);
$documentProposalSelectedCategory = $category !== '' ? $category : 'general';
$documentProposalSelectedSubcategory = $subcategory;
$documentProposalSelectedSubsubcategory = $subsubcategory;
$where = [];
$params = [];
if ($category !== '') {
    $where[] = 'category = ?';
    $params[] = $category;
}
if ($subcategory !== '') {
    $where[] = 'subcategory = ?';
    $params[] = $subcategory;
}
if ($subsubcategory !== '') {
    $where[] = 'subsubcategory = ?';
    $params[] = $subsubcategory;
}
if ($favoritesOnly) {
    $where[] = 'id IN (' . implode(',', array_fill(0, $favoriteDocumentCount, '?')) . ')';
    array_push($params, ...$favoriteDocumentIds);
}
if ($search !== '') {
    $where[] = '(title LIKE ? OR description LIKE ? OR extracted_text LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
if ($tag !== '') {
    $where[] = 'tags LIKE ?';
    $params[] = '%' . $tag . '%';
}
$whereSql = $where !== [] ? (' WHERE ' . implode(' AND ', $where)) : '';
$countStmt = db()->prepare('SELECT COUNT(*) FROM member_library_documents' . $whereSql);
$countStmt->execute($params);
$totalDocuments = (int) $countStmt->fetchColumn();
$pagination = pagination_state($totalDocuments, $page, $perPage);
$page = $pagination['page'];
$totalPages = $pagination['total_pages'];
$offset = $pagination['offset'];

$stmt = db()->prepare('SELECT * FROM member_library_documents' . $whereSql . ' ORDER BY uploaded_at DESC, id DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);
$stmt->execute($params);
$documents = $stmt->fetchAll() ?: [];
$latestDocumentDate = trim((string) (db()->query('SELECT uploaded_at FROM member_library_documents ORDER BY uploaded_at DESC, id DESC LIMIT 1')->fetchColumn() ?: ''));
$latestDocumentLabel = module_hero_latest_stat_date_label($latestDocumentDate, $locale);
$activeFiltersCount = 0;
if ($search !== '') {
    $activeFiltersCount++;
}
if ($category !== '') {
    $activeFiltersCount++;
}
if ($subcategory !== '') {
    $activeFiltersCount++;
}
if ($subsubcategory !== '') {
    $activeFiltersCount++;
}
if ($favoritesOnly) {
    $activeFiltersCount++;
}
if ($tag !== '') {
    $activeFiltersCount++;
}
$contactEmail = site_contact_email();
$documentProposalUrl = 'mailto:' . rawurlencode($contactEmail) . '?subject=' . rawurlencode((string) $t['propose_document_subject']);
$categoryProposalUrl = 'mailto:' . rawurlencode($contactEmail) . '?subject=' . rawurlencode((string) $t['propose_category_subject']);
$subcategoryProposalUrl = 'mailto:' . rawurlencode($contactEmail) . '?subject=' . rawurlencode((string) $t['propose_subcategory_subject']);
$subsubcategoryProposalUrl = 'mailto:' . rawurlencode($contactEmail) . '?subject=' . rawurlencode((string) $t['propose_subsubcategory_subject']);
$tagProposalUrl = 'mailto:' . rawurlencode($contactEmail) . '?subject=' . rawurlencode((string) $t['propose_tag_subject']);
$showCategoryProposalForm = (string) ($_GET['propose_category'] ?? '') === '1';
$showSubcategoryProposalForm = (string) ($_GET['propose_subcategory'] ?? '') === '1';
$showSubsubcategoryProposalForm = (string) ($_GET['propose_subsubcategory'] ?? '') === '1';
$showTagProposalForm = (string) ($_GET['propose_tag'] ?? '') === '1';
$showDocumentProposalForm = (string) ($_GET['propose_document'] ?? '') === '1';
$pendingLibraryAdminUrl = route_url_clean('admin_library', ['status' => 'pending']) . '#pending-proposals';
$pendingLibraryAdminLabel = (string) $t['administer'];

$relatedByDocumentId = [];
if ($documents !== []) {
    $docIds = [];
    $categoriesInPage = [];
    foreach ($documents as $documentRow) {
        $docId = (int) ($documentRow['id'] ?? 0);
        if ($docId > 0) {
            $docIds[] = $docId;
        }
        $cat = trim((string) ($documentRow['category'] ?? 'general'));
        if ($cat === '') {
            $cat = 'general';
        }
        $categoriesInPage[$cat] = true;
    }

    if ($docIds !== [] && $categoriesInPage !== []) {
        $categoryKeys = array_keys($categoriesInPage);
        $catPlaceholders = implode(',', array_fill(0, count($categoryKeys), '?'));
        $relatedStmt = db()->prepare('SELECT id, category, subcategory, subsubcategory, title FROM member_library_documents WHERE category IN (' . $catPlaceholders . ') ORDER BY uploaded_at DESC, id DESC LIMIT 300');
        $relatedStmt->execute($categoryKeys);
        $relatedPool = $relatedStmt->fetchAll() ?: [];

        $poolByCategory = [];
        $poolBySubcategory = [];
        $poolBySubsubcategory = [];
        foreach ($relatedPool as $candidate) {
            $candidateCategory = trim((string) ($candidate['category'] ?? 'general'));
            if ($candidateCategory === '') {
                $candidateCategory = 'general';
            }
            $poolByCategory[$candidateCategory][] = $candidate;
            $candidateSubcategory = member_library_subcategory_slug((string) ($candidate['subcategory'] ?? ''));
            if ($candidateSubcategory !== '') {
                $poolBySubcategory[$candidateCategory . ':' . $candidateSubcategory][] = $candidate;
                $candidateSubsubcategory = member_library_subsubcategory_slug((string) ($candidate['subsubcategory'] ?? ''));
                if ($candidateSubsubcategory !== '') {
                    $poolBySubsubcategory[$candidateCategory . ':' . $candidateSubcategory . ':' . $candidateSubsubcategory][] = $candidate;
                }
            }
        }

        foreach ($documents as $documentRow) {
            $docId = (int) ($documentRow['id'] ?? 0);
            $docCategory = trim((string) ($documentRow['category'] ?? 'general'));
            if ($docCategory === '') {
                $docCategory = 'general';
            }
            $docSubcategory = member_library_subcategory_slug((string) ($documentRow['subcategory'] ?? ''));
            $docSubsubcategory = member_library_subsubcategory_slug((string) ($documentRow['subsubcategory'] ?? ''));
            $relatedByDocumentId[$docId] = [];
            $candidatePools = [];
            if ($docSubcategory !== '' && $docSubsubcategory !== '') {
                $candidatePools[] = $poolBySubsubcategory[$docCategory . ':' . $docSubcategory . ':' . $docSubsubcategory] ?? [];
            }
            if ($docSubcategory !== '') {
                $candidatePools[] = $poolBySubcategory[$docCategory . ':' . $docSubcategory] ?? [];
            }
            $candidatePools[] = $poolByCategory[$docCategory] ?? [];
            foreach ($candidatePools as $candidatePool) {
                foreach ($candidatePool as $candidate) {
                    if ((int) ($candidate['id'] ?? 0) === $docId) {
                        continue;
                    }
                    $candidateId = (int) ($candidate['id'] ?? 0);
                    if (isset($relatedByDocumentId[$docId][$candidateId])) {
                        continue;
                    }
                    $relatedByDocumentId[$docId][$candidateId] = $candidate;
                    if (count($relatedByDocumentId[$docId]) >= 3) {
                        break 2;
                    }
                }
            }
            $relatedByDocumentId[$docId] = array_values($relatedByDocumentId[$docId]);
        }
    }
}

ob_start();
?>
<div class="stack members-library-article-design">
    <section class="page-hero members-library-hero member-module-hero">
        <div>
            <p class="eyebrow"><?= e($memberAreaLabel) ?></p>
            <h1 class="members-library-heading"><?= e((string) $t['title']) ?></h1>
            <p class="help"><?= e((string) $t['intro']) ?></p>
        </div>
        <div class="members-library-hero-side">
            <div class="articles-hero-stats members-library-stats">
                <article>
                    <span><?= e((string) $t['documents']) ?></span>
                    <strong><?= (int) $totalDocuments ?></strong>
                </article>
                <article>
                    <span><?= e((string) $t['categories']) ?></span>
                    <strong><?= (int) count($visibleCategories) ?></strong>
                </article>
                <article>
                    <span><?= e((string) $t['tags']) ?></span>
                    <strong><?= (int) $activeFiltersCount ?></strong>
                </article>
                <article>
                    <span><?= e(module_hero_latest_stat_text('latest', $locale)) ?></span>
                    <strong><?= e($latestDocumentLabel) ?></strong>
                </article>
            </div>
            <div class="members-library-hero-action">
                <details class="members-library-propose-menu">
                    <summary class="button" aria-haspopup="menu"><?= e((string) $t['propose_menu']) ?></summary>
                    <div class="members-library-propose-menu-panel" role="menu">
                        <a class="members-library-propose-menu-item" role="menuitem" href="<?= e($categoryProposalUrl) ?>" data-members-library-modal-open="members-library-category-dialog" aria-haspopup="dialog" aria-controls="members-library-category-dialog"><?= e((string) $t['propose_category_item']) ?></a>
                        <a class="members-library-propose-menu-item" role="menuitem" href="<?= e($subcategoryProposalUrl) ?>" data-members-library-modal-open="members-library-subcategory-dialog" aria-haspopup="dialog" aria-controls="members-library-subcategory-dialog"><?= e((string) $t['propose_subcategory_item']) ?></a>
                        <a class="members-library-propose-menu-item" role="menuitem" href="<?= e($subsubcategoryProposalUrl) ?>" data-members-library-modal-open="members-library-subsubcategory-dialog" aria-haspopup="dialog" aria-controls="members-library-subsubcategory-dialog"><?= e((string) $t['propose_subsubcategory_item']) ?></a>
                        <a class="members-library-propose-menu-item" role="menuitem" href="<?= e($tagProposalUrl) ?>" data-members-library-modal-open="members-library-tag-dialog" aria-haspopup="dialog" aria-controls="members-library-tag-dialog"><?= e((string) $t['propose_tag_item']) ?></a>
                        <a class="members-library-propose-menu-item" role="menuitem" href="<?= e($documentProposalUrl) ?>" data-members-library-modal-open="members-library-document-dialog" aria-haspopup="dialog" aria-controls="members-library-document-dialog"><?= e((string) $t['propose_document_item']) ?></a>
                    </div>
                </details>
                <?php if ($canManageLibrary): ?>
                    <a class="button secondary" href="<?= e($pendingLibraryAdminUrl) ?>"><?= e($pendingLibraryAdminLabel) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <dialog class="members-library-dialog" id="members-library-category-dialog" aria-labelledby="members-library-category-title"<?= $showCategoryProposalForm ? ' open data-members-library-auto-open' : '' ?>>
        <div class="members-library-dialog-card">
            <div class="members-library-dialog-header module-dialog-header">
                <div>
                    <p class="members-library-dialog-eyebrow module-dialog-eyebrow"><?= e((string) $t['category']) ?></p>
                    <h2 id="members-library-category-title"><?= e((string) $t['propose_category']) ?></h2>
                    <p class="help"><?= e($canManageLibrary ? (string) $t['category_direct_help'] : (string) $t['propose_category_intro']) ?></p>
                </div>
                <button class="members-library-dialog-close module-dialog-close" type="button" data-members-library-modal-close aria-label="<?= e((string) $t['modal_close']) ?>">&times;</button>
            </div>
            <form class="members-library-dialog-form module-dialog-form" method="post" data-members-library-proposal-form data-members-library-recipient="<?= e($contactEmail) ?>" data-members-library-subject="<?= e((string) $t['propose_category_subject']) ?>" data-members-library-intro="<?= e((string) $t['propose_category_body_intro']) ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="propose_category">
                <label><span><?= e((string) $t['propose_category_name']) ?></span><input type="text" name="proposal_category_name" maxlength="160" required></label>
                <label><span><?= e((string) $t['propose_category_reason']) ?></span><textarea name="proposal_reason" rows="5" maxlength="1600"></textarea></label>
                <label><span><?= e((string) $t['proposal_contact']) ?></span><input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required></label>
                <div class="members-library-dialog-actions module-dialog-actions">
                    <button class="button" type="submit"><?= e((string) $t['proposal_submit']) ?></button>
                    <button class="button secondary" type="button" data-members-library-modal-close><?= e((string) $t['proposal_cancel']) ?></button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog class="members-library-dialog" id="members-library-subcategory-dialog" aria-labelledby="members-library-subcategory-title"<?= $showSubcategoryProposalForm ? ' open data-members-library-auto-open' : '' ?>>
        <div class="members-library-dialog-card">
            <div class="members-library-dialog-header module-dialog-header">
                <div>
                    <p class="members-library-dialog-eyebrow module-dialog-eyebrow"><?= e((string) $t['subcategory']) ?></p>
                    <h2 id="members-library-subcategory-title"><?= e((string) $t['propose_subcategory']) ?></h2>
                    <p class="help"><?= e($canManageLibrary ? (string) $t['subcategory_direct_help'] : (string) $t['propose_subcategory_intro']) ?></p>
                </div>
                <button class="members-library-dialog-close module-dialog-close" type="button" data-members-library-modal-close aria-label="<?= e((string) $t['modal_close']) ?>">&times;</button>
            </div>
            <form class="members-library-dialog-form module-dialog-form" method="post" data-members-library-proposal-form data-members-library-recipient="<?= e($contactEmail) ?>" data-members-library-subject="<?= e((string) $t['propose_subcategory_subject']) ?>" data-members-library-intro="<?= e((string) $t['propose_subcategory_body_intro']) ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="propose_subcategory">
                <label>
                    <span><?= e((string) $t['propose_subcategory_parent']) ?></span>
                    <select name="proposal_parent_category" required>
                        <?php foreach ($categories as $proposalCategoryOption): ?>
                            <?php
                            $proposalCategoryCode = trim((string) ($proposalCategoryOption['category'] ?? ''));
                            if ($proposalCategoryCode === '') {
                                continue;
                            }
                            $proposalCategoryLabel = trim((string) ($proposalCategoryOption['label'] ?? $proposalCategoryCode));
                            ?>
                            <option value="<?= e($proposalCategoryCode) ?>"<?= $documentProposalSelectedCategory === $proposalCategoryCode ? ' selected' : '' ?>><?= e($proposalCategoryLabel !== '' ? $proposalCategoryLabel : $proposalCategoryCode) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span><?= e((string) $t['propose_subcategory_name']) ?></span><input type="text" name="proposal_subcategory_name" maxlength="160" required></label>
                <label><span><?= e((string) $t['propose_subcategory_reason']) ?></span><textarea name="proposal_reason" rows="5" maxlength="1600"></textarea></label>
                <label><span><?= e((string) $t['proposal_contact']) ?></span><input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required></label>
                <div class="members-library-dialog-actions module-dialog-actions">
                    <button class="button" type="submit"><?= e((string) $t['proposal_submit']) ?></button>
                    <button class="button secondary" type="button" data-members-library-modal-close><?= e((string) $t['proposal_cancel']) ?></button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog class="members-library-dialog" id="members-library-subsubcategory-dialog" aria-labelledby="members-library-subsubcategory-title"<?= $showSubsubcategoryProposalForm ? ' open data-members-library-auto-open' : '' ?>>
        <div class="members-library-dialog-card">
            <div class="members-library-dialog-header module-dialog-header">
                <div>
                    <p class="members-library-dialog-eyebrow module-dialog-eyebrow"><?= e((string) $t['subsubcategory']) ?></p>
                    <h2 id="members-library-subsubcategory-title"><?= e((string) $t['propose_subsubcategory']) ?></h2>
                    <p class="help"><?= e($canManageLibrary ? (string) $t['subsubcategory_direct_help'] : (string) $t['propose_subsubcategory_intro']) ?></p>
                </div>
                <button class="members-library-dialog-close module-dialog-close" type="button" data-members-library-modal-close aria-label="<?= e((string) $t['modal_close']) ?>">&times;</button>
            </div>
            <form class="members-library-dialog-form module-dialog-form" method="post" data-members-library-proposal-form data-members-library-recipient="<?= e($contactEmail) ?>" data-members-library-subject="<?= e((string) $t['propose_subsubcategory_subject']) ?>" data-members-library-intro="<?= e((string) $t['propose_subsubcategory_body_intro']) ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="propose_subsubcategory">
                <label>
                    <span><?= e((string) $t['propose_subsubcategory_parent']) ?></span>
                    <select name="proposal_parent_subcategory_ref" required>
                        <?php foreach ($subcategoriesByCategory as $parentCode => $subcatGroup): ?>
                            <optgroup label="<?= e((string) ($categoryLabels[$parentCode] ?? $parentCode)) ?>">
                                <?php foreach ($subcatGroup as $subcatOption): ?>
                                    <option value="<?= e(member_library_subcategory_ref($parentCode, (string) $subcatOption['code'])) ?>"<?= $documentProposalSelectedCategory === $parentCode && $documentProposalSelectedSubcategory === (string) $subcatOption['code'] ? ' selected' : '' ?>><?= e((string) $subcatOption['label']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span><?= e((string) $t['propose_subsubcategory_name']) ?></span><input type="text" name="proposal_subsubcategory_name" maxlength="160" required></label>
                <label><span><?= e((string) $t['propose_subsubcategory_reason']) ?></span><textarea name="proposal_reason" rows="5" maxlength="1600"></textarea></label>
                <label><span><?= e((string) $t['proposal_contact']) ?></span><input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required></label>
                <div class="members-library-dialog-actions module-dialog-actions">
                    <button class="button" type="submit"><?= e((string) $t['proposal_submit']) ?></button>
                    <button class="button secondary" type="button" data-members-library-modal-close><?= e((string) $t['proposal_cancel']) ?></button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog class="members-library-dialog" id="members-library-tag-dialog" aria-labelledby="members-library-tag-title"<?= $showTagProposalForm ? ' open data-members-library-auto-open' : '' ?>>
        <div class="members-library-dialog-card">
            <div class="members-library-dialog-header module-dialog-header">
                <div>
                    <p class="members-library-dialog-eyebrow module-dialog-eyebrow"><?= e((string) $t['keyword']) ?></p>
                    <h2 id="members-library-tag-title"><?= e((string) $t['propose_tag']) ?></h2>
                    <p class="help"><?= e($canManageLibrary ? (string) $t['tag_direct_help'] : (string) $t['propose_tag_intro']) ?></p>
                </div>
                <button class="members-library-dialog-close module-dialog-close" type="button" data-members-library-modal-close aria-label="<?= e((string) $t['modal_close']) ?>">&times;</button>
            </div>
            <form class="members-library-dialog-form module-dialog-form" method="post" data-members-library-proposal-form data-members-library-recipient="<?= e($contactEmail) ?>" data-members-library-subject="<?= e((string) $t['propose_tag_subject']) ?>" data-members-library-intro="<?= e((string) $t['propose_tag_body_intro']) ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="propose_tag">
                <label><span><?= e((string) $t['propose_tag_name']) ?></span><input type="text" name="proposal_tag" maxlength="80" required></label>
                <label><span><?= e((string) $t['propose_tag_reason']) ?></span><textarea name="proposal_reason" rows="5" maxlength="1600"></textarea></label>
                <label><span><?= e((string) $t['proposal_contact']) ?></span><input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required></label>
                <div class="members-library-dialog-actions module-dialog-actions">
                    <button class="button" type="submit"><?= e((string) $t['proposal_submit']) ?></button>
                    <button class="button secondary" type="button" data-members-library-modal-close><?= e((string) $t['proposal_cancel']) ?></button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog class="members-library-dialog" id="members-library-document-dialog" aria-labelledby="members-library-document-title"<?= $showDocumentProposalForm ? ' open data-members-library-auto-open' : '' ?>>
        <div class="members-library-dialog-card">
            <div class="members-library-dialog-header module-dialog-header">
                <div>
                    <p class="members-library-dialog-eyebrow module-dialog-eyebrow"><?= e((string) $t['document']) ?></p>
                    <h2 id="members-library-document-title"><?= e((string) $t['propose_document']) ?></h2>
                    <p class="help"><?= e($canManageLibrary ? (string) $t['document_direct_help'] : (string) $t['propose_document_intro']) ?></p>
                </div>
                <button class="members-library-dialog-close module-dialog-close" type="button" data-members-library-modal-close aria-label="<?= e((string) $t['modal_close']) ?>">&times;</button>
            </div>
            <form class="members-library-dialog-form module-dialog-form" method="post" enctype="multipart/form-data" data-members-library-proposal-form data-members-library-recipient="<?= e($contactEmail) ?>" data-members-library-subject="<?= e((string) $t['propose_document_subject']) ?>" data-members-library-intro="<?= e((string) $t['propose_document_body_intro']) ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="propose_document">
                <label><span><?= e((string) $t['propose_document_title']) ?></span><input type="text" name="proposal_title" maxlength="190" required></label>
                <label>
                    <span><?= e((string) $t['propose_document_category']) ?></span>
                    <select name="proposal_category" required>
                        <?php foreach ($categories as $proposalCategoryOption): ?>
                            <?php
                            $proposalCategoryCode = trim((string) ($proposalCategoryOption['category'] ?? ''));
                            if ($proposalCategoryCode === '') {
                                continue;
                            }
                            $proposalCategoryLabel = trim((string) ($proposalCategoryOption['label'] ?? $proposalCategoryCode));
                            ?>
                            <option value="<?= e($proposalCategoryCode) ?>"<?= $documentProposalSelectedCategory === $proposalCategoryCode ? ' selected' : '' ?>><?= e($proposalCategoryLabel !== '' ? $proposalCategoryLabel : $proposalCategoryCode) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span><?= e((string) $t['propose_document_subcategory']) ?></span>
                    <select name="proposal_subcategory_ref">
                        <option value=""><?= e((string) $t['no_subcategory']) ?></option>
                        <?php foreach ($subcategoriesByCategory as $parentCode => $subcatGroup): ?>
                            <optgroup label="<?= e((string) ($categoryLabels[$parentCode] ?? $parentCode)) ?>">
                                <?php foreach ($subcatGroup as $subcatOption): ?>
                                    <option value="<?= e(member_library_subcategory_ref($parentCode, (string) $subcatOption['code'])) ?>"<?= $documentProposalSelectedSubcategory === (string) $subcatOption['code'] && $documentProposalSelectedCategory === $parentCode ? ' selected' : '' ?>><?= e((string) $subcatOption['label']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span><?= e((string) $t['propose_document_subsubcategory']) ?></span>
                    <select name="proposal_subsubcategory_ref">
                        <option value=""><?= e((string) $t['no_subsubcategory']) ?></option>
                        <?php foreach ($subsubcategoriesByParent as $parentRef => $subsubcatGroup): ?>
                            <?php [$parentCategoryCode, $parentSubcategoryCode] = array_pad(explode(':', (string) $parentRef, 2), 2, ''); ?>
                            <optgroup label="<?= e((string) ($categoryLabels[$parentCategoryCode] ?? $parentCategoryCode)) ?> / <?= e((string) ($subcategoryLabels[$parentRef] ?? $parentSubcategoryCode)) ?>">
                                <?php foreach ($subsubcatGroup as $subsubcatOption): ?>
                                    <option value="<?= e(member_library_subsubcategory_ref($parentCategoryCode, $parentSubcategoryCode, (string) $subsubcatOption['code'])) ?>"<?= $documentProposalSelectedCategory === $parentCategoryCode && $documentProposalSelectedSubcategory === $parentSubcategoryCode && $documentProposalSelectedSubsubcategory === (string) $subsubcatOption['code'] ? ' selected' : '' ?>><?= e((string) $subsubcatOption['label']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span><?= e((string) $t['tags']) ?></span><input type="text" name="proposal_tags" maxlength="255"></label>
                <label><span><?= e((string) $t['document']) ?></span><input type="file" name="proposal_file" accept=".pdf,.doc,.docx,.txt,.md,.html,.htm,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,text/markdown,text/html" required></label>
                <label><span><?= e((string) $t['propose_document_description']) ?></span><textarea name="proposal_description" rows="5" maxlength="1600"></textarea></label>
                <label><span><?= e((string) $t['proposal_contact']) ?></span><input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required></label>
                <div class="members-library-dialog-actions module-dialog-actions">
                    <button class="button" type="submit"><?= e((string) $t['proposal_submit']) ?></button>
                    <button class="button secondary" type="button" data-members-library-modal-close><?= e((string) $t['proposal_cancel']) ?></button>
                </div>
            </form>
        </div>
    </dialog>

    <section class="card members-library-search-panel">
        <form method="get" class="inline-form members-library-search-form">
            <input type="hidden" name="route" value="members_library">
            <?php if ($category !== ''): ?>
                <input type="hidden" name="category" value="<?= e($category) ?>">
            <?php endif; ?>
            <?php if ($subcategory !== ''): ?>
                <input type="hidden" name="subcategory" value="<?= e($subcategory) ?>">
            <?php endif; ?>
            <?php if ($subsubcategory !== ''): ?>
                <input type="hidden" name="subsubcategory" value="<?= e($subsubcategory) ?>">
            <?php endif; ?>
            <?php if ($tag !== ''): ?>
                <input type="hidden" name="tag" value="<?= e($tag) ?>">
            <?php endif; ?>
            <?php if ($favoritesOnly): ?>
                <input type="hidden" name="favorites" value="1">
            <?php endif; ?>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
            <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
            <?php if ($search !== ''): ?>
                <a class="button secondary" href="<?= e(route_url_clean('members_library', ['category' => $category, 'subcategory' => $subcategory, 'subsubcategory' => $subsubcategory, 'tag' => $tag, 'favorites' => $favoritesOnly ? '1' : ''])) ?>"><?= e((string) $t['reset']) ?></a>
            <?php endif; ?>
        </form>
        <?php if ($search !== '' || $category !== '' || $subcategory !== '' || $subsubcategory !== '' || $tag !== '' || $favoritesOnly): ?>
            <p class="help"><?= e((string) $t['documents']) ?> : <?= (int) $totalDocuments ?></p>
        <?php endif; ?>
    </section>

    <section class="members-library-layout module-taxonomy-layout">
        <aside class="members-library-index module-taxonomy-index card">
            <p class="members-library-index-title module-taxonomy-title"><?= e((string) $t['topics']) ?></p>
            <nav class="members-library-category-list module-taxonomy-list" aria-label="<?= e((string) $t['topics']) ?>">
                <?php if ($favoriteDocumentCount > 0): ?>
                    <a class="members-library-category-item module-taxonomy-item<?= $favoritesOnly ? ' is-active' : '' ?>" href="<?= e(route_url_clean('members_library', ['favorites' => '1', 'q' => $search, 'tag' => $tag])) ?>">
                        <span><?= e($favoritesLabel) ?></span>
                        <strong><?= (int) $favoriteDocumentCount ?></strong>
                    </a>
                <?php endif; ?>
                <a class="members-library-category-item module-taxonomy-item<?= !$favoritesOnly && $category === '' && $subcategory === '' && $subsubcategory === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('members_library', ['q' => $search, 'tag' => $tag])) ?>">
                    <span><?= e((string) $t['all_categories']) ?></span>
                    <strong><?= (int) array_sum(array_map(static fn(array $cat): int => (int) ($cat['total'] ?? 0), $visibleCategories)) ?></strong>
                </a>
                <?php if ($visibleCategories === []): ?>
                    <p class="help"><?= e((string) $t['empty']) ?></p>
                <?php endif; ?>
                <?php foreach ($visibleCategories as $cat): ?>
                    <?php $catName = trim((string) ($cat['category'] ?? 'general')); if ($catName === '') { $catName = 'general'; } ?>
                    <a class="members-library-category-item module-taxonomy-item<?= !$favoritesOnly && $catName === $category && $subcategory === '' && $subsubcategory === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('members_library', ['category' => $catName, 'q' => $search, 'tag' => $tag])) ?>">
                        <span><?= e((string) ($cat['label'] ?? $catName)) ?></span>
                        <strong><?= (int) ($cat['total'] ?? 0) ?></strong>
                    </a>
                    <?php if (($visibleSubcategoriesByCategory[$catName] ?? []) !== []): ?>
                        <div class="members-library-subcategory-list module-taxonomy-children">
                            <?php foreach ($visibleSubcategoriesByCategory[$catName] as $subcatInfo): ?>
                                <?php $subcatCode = (string) ($subcatInfo['code'] ?? ''); if ($subcatCode === '') { continue; } ?>
                                <a class="members-library-subcategory-item module-taxonomy-item<?= !$favoritesOnly && $catName === $category && $subcatCode === $subcategory && $subsubcategory === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('members_library', ['category' => $catName, 'subcategory' => $subcatCode, 'q' => $search, 'tag' => $tag])) ?>">
                                    <span><?= e((string) ($subcatInfo['label'] ?? $subcatCode)) ?></span>
                                    <strong><?= (int) ($subcatInfo['total'] ?? 0) ?></strong>
                                </a>
                                <?php $subsubParentRef = $catName . ':' . $subcatCode; ?>
                                <?php if (($visibleSubsubcategoriesByParent[$subsubParentRef] ?? []) !== []): ?>
                                    <div class="members-library-subsubcategory-list module-taxonomy-children">
                                        <?php foreach ($visibleSubsubcategoriesByParent[$subsubParentRef] as $subsubcatInfo): ?>
                                            <?php $subsubcatCode = (string) ($subsubcatInfo['code'] ?? ''); if ($subsubcatCode === '') { continue; } ?>
                                            <a class="members-library-subsubcategory-item module-taxonomy-item<?= !$favoritesOnly && $catName === $category && $subcatCode === $subcategory && $subsubcatCode === $subsubcategory ? ' is-active' : '' ?>" href="<?= e(route_url_clean('members_library', ['category' => $catName, 'subcategory' => $subcatCode, 'subsubcategory' => $subsubcatCode, 'q' => $search, 'tag' => $tag])) ?>">
                                                <span><?= e((string) ($subsubcatInfo['label'] ?? $subsubcatCode)) ?></span>
                                                <strong><?= (int) ($subsubcatInfo['total'] ?? 0) ?></strong>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </aside>

        <div class="members-library-content module-taxonomy-content">
            <?php if ($documents === []): ?>
                <div class="card">
                    <p><?= e((string) $t['empty']) ?><?php if ($search !== '' || $category !== '' || $subcategory !== '' || $subsubcategory !== '' || $tag !== '' || $favoritesOnly): ?><?= e((string) $t['for_filters']) ?>.<?php endif; ?></p>
                </div>
            <?php endif; ?>

            <div class="news-grid members-library-document-grid">
            <?php foreach ($documents as $document): ?>
                <?php $safePath = safe_storage_document_path_or_null((string) ($document['file_path'] ?? ''), ['storage/private/library/', 'storage/uploads/library/']); ?>
                <?php if ($safePath === null) { continue; } ?>
                <?php $extension = strtolower(pathinfo($safePath, PATHINFO_EXTENSION)); ?>
                <?php $docCategory = trim((string) ($document['category'] ?? 'general')); if ($docCategory === '') { $docCategory = 'general'; } ?>
                <?php $docCategoryLabel = (string) ($categoryLabels[$docCategory] ?? ($docCategory === 'general' ? $generalCategoryLabel : $docCategory)); ?>
                <?php $docSubcategory = member_library_subcategory_slug((string) ($document['subcategory'] ?? '')); ?>
                <?php $docSubcategoryLabel = $docSubcategory !== '' ? (string) ($subcategoryLabels[$docCategory . ':' . $docSubcategory] ?? $docSubcategory) : ''; ?>
                <?php $docSubsubcategory = member_library_subsubcategory_slug((string) ($document['subsubcategory'] ?? '')); ?>
                <?php $docSubsubcategoryLabel = $docSubcategory !== '' && $docSubsubcategory !== '' ? (string) ($subsubcategoryLabels[$docCategory . ':' . $docSubcategory . ':' . $docSubsubcategory] ?? $docSubsubcategory) : ''; ?>
                <?php $docTitle = trim((string) ($document['title'] ?? '')); if ($docTitle === '') { $docTitle = (string) $t['document']; } ?>
                <?php $docDescription = trim((string) ($document['description'] ?? '')); ?>
                <?php $docTags = trim((string) ($document['tags'] ?? '')); ?>
                <?php $docExtract = trim((string) ($document['extracted_text'] ?? '')); ?>
                <?php $docId = (int) ($document['id'] ?? 0); ?>
                <?php $docDownloadUrl = $docId > 0 ? route_url('member_library_preview', ['id' => $docId, 'download' => '1']) : ''; ?>
                <?php $relatedDocs = $relatedByDocumentId[$docId] ?? []; ?>
                <?php $isFavorite = favorite_is_saved((int) $user['id'], 'library_document', (int) ($document['id'] ?? 0)); ?>
                <?php $canEditDocument = $canManageLibrary || (int) ($document['member_id'] ?? 0) === (int) ($user['id'] ?? 0); ?>
                <?php $editDialogId = 'members-library-edit-dialog-' . $docId; ?>
                <article class="news-card feature-card members-library-document-card">
                    <span class="badge muted"><?= e($docCategoryLabel) ?><?php if ($docSubcategoryLabel !== ''): ?> / <?= e($docSubcategoryLabel) ?><?php endif; ?><?php if ($docSubsubcategoryLabel !== ''): ?> / <?= e($docSubsubcategoryLabel) ?><?php endif; ?> / <?= e(strtoupper($extension)) ?></span>
                    <h3><?= e($docTitle) ?></h3>
                    <?php if ($docDescription !== ''): ?><p><?= e($docDescription) ?></p><?php endif; ?>
                    <?php if ($docTags !== ''): ?><p class="help"><?= e((string) $t['tags']) ?>: <?= e($docTags) ?></p><?php endif; ?>
                    <?php if ($docExtract !== ''): ?><p class="help"><?= e(mb_safe_strimwidth($docExtract, 0, 220, '...')) ?></p><?php endif; ?>
                    <?php if ($extension === 'pdf'): ?>
                        <details class="members-library-preview-toggle">
                            <summary><?= e((string) $t['preview']) ?></summary>
                            <iframe src="<?= e(route_url('member_library_preview', ['id' => $docId]) . '#view=Fit') ?>" class="members-library-pdf-preview" loading="lazy" title="<?= e($docTitle) ?>"></iframe>
                        </details>
                    <?php endif; ?>
                    <details class="admin-library-related-toggle">
                        <summary><?= e((string) $t['related_docs']) ?></summary>
                        <?php if ($relatedDocs === []): ?>
                            <p class="help"><?= e((string) $t['no_related_docs']) ?></p>
                        <?php else: ?>
                            <ul class="help" style="padding-left:1rem;margin:.4rem 0;">
                                <?php foreach ($relatedDocs as $related): ?>
                                    <?php $relatedTitle = trim((string) ($related['title'] ?? '')); if ($relatedTitle === '') { $relatedTitle = (string) $t['document']; } ?>
                                    <li><a href="<?= e(route_url_clean('members_library', ['q' => $relatedTitle, 'category' => (string) ($related['category'] ?? ''), 'subcategory' => (string) ($related['subcategory'] ?? ''), 'subsubcategory' => (string) ($related['subsubcategory'] ?? ''), 'tag' => $tag])) ?>"><?= e($relatedTitle) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </details>
                    <div class="actions members-library-document-actions">
                        <?php if ($docDownloadUrl !== ''): ?>
                            <a class="button secondary" href="<?= e($docDownloadUrl) ?>" target="_blank" rel="noopener"><?= e((string) $t['open']) ?></a>
                        <?php endif; ?>
                        <?php if ($canEditDocument): ?>
                            <button class="button secondary" type="button" data-members-library-modal-open="<?= e($editDialogId) ?>" aria-haspopup="dialog" aria-controls="<?= e($editDialogId) ?>"><?= e((string) $t['edit_document']) ?></button>
                        <?php endif; ?>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="toggle_favorite_document">
                            <input type="hidden" name="document_id" value="<?= (int) ($document['id'] ?? 0) ?>">
                            <button class="button secondary" type="submit"><?= $isFavorite ? '&#9733; ' . e((string) $t['favorite']) : '&#9734; ' . e((string) $t['favorite']) ?></button>
                        </form>
                    </div>
                </article>
                <?php if ($canEditDocument): ?>
                    <dialog class="members-library-dialog" id="<?= e($editDialogId) ?>" aria-labelledby="<?= e($editDialogId) ?>-title">
                        <div class="members-library-dialog-card">
                            <div class="members-library-dialog-header module-dialog-header">
                                <div>
                                    <p class="members-library-dialog-eyebrow module-dialog-eyebrow"><?= e((string) $t['document']) ?></p>
                                    <h2 id="<?= e($editDialogId) ?>-title"><?= e((string) $t['edit_document_title']) ?></h2>
                                    <p class="help"><?= e($docTitle) ?></p>
                                </div>
                                <button class="members-library-dialog-close module-dialog-close" type="button" data-members-library-modal-close aria-label="<?= e((string) $t['modal_close']) ?>">&times;</button>
                            </div>
                            <form class="members-library-dialog-form module-dialog-form" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="update_document">
                                <input type="hidden" name="document_id" value="<?= $docId ?>">
                                <input type="hidden" name="return_category" value="<?= e($category) ?>">
                                <input type="hidden" name="return_subcategory" value="<?= e($subcategory) ?>">
                                <input type="hidden" name="return_subsubcategory" value="<?= e($subsubcategory) ?>">
                                <input type="hidden" name="return_q" value="<?= e($search) ?>">
                                <input type="hidden" name="return_tag" value="<?= e($tag) ?>">
                                <input type="hidden" name="return_favorites" value="<?= $favoritesOnly ? '1' : '' ?>">
                                <input type="hidden" name="return_p" value="<?= $page ?>">
                                <label><span><?= e((string) $t['propose_document_title']) ?></span><input type="text" name="document_title" value="<?= e($docTitle) ?>" maxlength="190" required></label>
                                <label>
                                    <span><?= e((string) $t['propose_document_category']) ?></span>
                                    <select name="document_category" required>
                                        <?php foreach ($categories as $documentCategoryOption): ?>
                                            <?php
                                            $documentCategoryCode = trim((string) ($documentCategoryOption['category'] ?? ''));
                                            if ($documentCategoryCode === '') {
                                                continue;
                                            }
                                            $documentCategoryOptionLabel = trim((string) ($documentCategoryOption['label'] ?? $documentCategoryCode));
                                            ?>
                                            <option value="<?= e($documentCategoryCode) ?>"<?= $docCategory === $documentCategoryCode ? ' selected' : '' ?>><?= e($documentCategoryOptionLabel !== '' ? $documentCategoryOptionLabel : $documentCategoryCode) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>
                                    <span><?= e((string) $t['propose_document_subcategory']) ?></span>
                                    <select name="document_subcategory_ref">
                                        <option value=""><?= e((string) $t['no_subcategory']) ?></option>
                                        <?php foreach ($subcategoriesByCategory as $parentCode => $subcatGroup): ?>
                                            <optgroup label="<?= e((string) ($categoryLabels[$parentCode] ?? $parentCode)) ?>">
                                                <?php foreach ($subcatGroup as $subcatOption): ?>
                                                    <option value="<?= e(member_library_subcategory_ref($parentCode, (string) $subcatOption['code'])) ?>"<?= $docCategory === $parentCode && $docSubcategory === (string) $subcatOption['code'] ? ' selected' : '' ?>><?= e((string) $subcatOption['label']) ?></option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>
                                    <span><?= e((string) $t['propose_document_subsubcategory']) ?></span>
                                    <select name="document_subsubcategory_ref">
                                        <option value=""><?= e((string) $t['no_subsubcategory']) ?></option>
                                        <?php foreach ($subsubcategoriesByParent as $parentRef => $subsubcatGroup): ?>
                                            <?php [$parentCategoryCode, $parentSubcategoryCode] = array_pad(explode(':', (string) $parentRef, 2), 2, ''); ?>
                                            <optgroup label="<?= e((string) ($categoryLabels[$parentCategoryCode] ?? $parentCategoryCode)) ?> / <?= e((string) ($subcategoryLabels[$parentRef] ?? $parentSubcategoryCode)) ?>">
                                                <?php foreach ($subsubcatGroup as $subsubcatOption): ?>
                                                    <option value="<?= e(member_library_subsubcategory_ref($parentCategoryCode, $parentSubcategoryCode, (string) $subsubcatOption['code'])) ?>"<?= $docCategory === $parentCategoryCode && $docSubcategory === $parentSubcategoryCode && $docSubsubcategory === (string) $subsubcatOption['code'] ? ' selected' : '' ?>><?= e((string) $subsubcatOption['label']) ?></option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label><span><?= e((string) $t['tags']) ?></span><input type="text" name="document_tags" value="<?= e($docTags) ?>" maxlength="255"></label>
                                <label><span><?= e((string) $t['propose_document_description']) ?></span><textarea name="document_description" rows="5" maxlength="1800"><?= e($docDescription) ?></textarea></label>
                                <label><span><?= e((string) $t['replace_document_file']) ?></span><input type="file" name="document_file" accept=".pdf,.doc,.docx,.txt,.md,.html,.htm,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,text/markdown,text/html"></label>
                                <div class="members-library-dialog-actions module-dialog-actions">
                                    <button class="button" type="submit"><?= e((string) $t['save_document']) ?></button>
                                    <button class="button secondary" type="button" data-members-library-modal-close><?= e((string) $t['proposal_cancel']) ?></button>
                                </div>
                            </form>
                            <form method="post" class="members-library-delete-form">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete_document">
                                <input type="hidden" name="document_id" value="<?= $docId ?>">
                                <input type="hidden" name="return_category" value="<?= e($category) ?>">
                                <input type="hidden" name="return_subcategory" value="<?= e($subcategory) ?>">
                                <input type="hidden" name="return_subsubcategory" value="<?= e($subsubcategory) ?>">
                                <input type="hidden" name="return_q" value="<?= e($search) ?>">
                                <input type="hidden" name="return_tag" value="<?= e($tag) ?>">
                                <input type="hidden" name="return_favorites" value="<?= $favoritesOnly ? '1' : '' ?>">
                                <input type="hidden" name="return_p" value="<?= $page ?>">
                                <p class="help"><?= e((string) $t['delete_document_warning']) ?></p>
                                <button class="button secondary members-library-danger" type="submit"><?= e((string) $t['delete_document']) ?></button>
                            </form>
                        </div>
                    </dialog>
                <?php endif; ?>
            <?php endforeach; ?>
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="card actions">
                    <?php if ($page > 1): ?><a class="button secondary" href="<?= e(route_url_clean('members_library', ['category' => $category, 'subcategory' => $subcategory, 'subsubcategory' => $subsubcategory, 'q' => $search, 'tag' => $tag, 'favorites' => $favoritesOnly ? '1' : '', 'p' => $page - 1])) ?>">&larr; <?= e((string) $t['prev']) ?></a><?php endif; ?>
                    <span class="pill"><?= e((string) $t['page']) ?> <?= $page ?> / <?= $totalPages ?></span>
                    <?php if ($page < $totalPages): ?><a class="button secondary" href="<?= e(route_url_clean('members_library', ['category' => $category, 'subcategory' => $subcategory, 'subsubcategory' => $subsubcategory, 'q' => $search, 'tag' => $tag, 'favorites' => $favoritesOnly ? '1' : '', 'p' => $page + 1])) ?>"><?= e((string) $t['next']) ?> &rarr;</a><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php echo render_layout((string) ob_get_clean(), (string) $t['title']);

