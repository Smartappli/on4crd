<?php
declare(strict_types=1);

require_login();
$locale = current_locale();
$t = i18n_domain_locale('members_library', $locale);
$memberAreaLabel = member_area_eyebrow_label($locale);
/** @var array{id:int} $user */
$user = current_user() ?? ['id' => 0];
$canManageLibrary = has_permission('admin.access');
$isFrench = $locale === 'fr';
$membersLibraryText = static function (string $key, string $fr, string $en) use ($t, $isFrench): string {
    return (string) ($t[$key] ?? ($isFrench ? $fr : $en));
};
$membersLibraryReturnUrl = static function (): string {
    $page = max(1, (int) ($_POST['return_p'] ?? $_GET['p'] ?? 1));

    return route_url_clean('members_library', [
        'category' => (string) ($_POST['return_category'] ?? $_GET['category'] ?? ''),
        'q' => (string) ($_POST['return_q'] ?? $_GET['q'] ?? ''),
        'tag' => (string) ($_POST['return_tag'] ?? $_GET['tag'] ?? ''),
        'p' => $page > 1 ? $page : '',
    ]);
};
set_page_meta(['title' => (string) $t['title'], 'description' => (string) $t['meta_desc'], 'robots' => 'noindex,follow']);

if (!ensure_member_library_table()) {
    echo render_layout('<div class="card"><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['title']);
    return;
}
member_library_sync_accepted_proposals($t);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'toggle_favorite_document') {
            $documentId = (int) ($_POST['document_id'] ?? 0);
            if ($documentId > 0) {
                $docStmt = db()->prepare('SELECT id, title, category, tags FROM member_library_documents WHERE id = ? LIMIT 1');
                $docStmt->execute([$documentId]);
                $docRow = $docStmt->fetch() ?: null;
                if ($docRow !== null) {
                    $docTitle = trim((string) ($docRow['title'] ?? 'Document'));
                    $docCategory = trim((string) ($docRow['category'] ?? ''));
                    $docTags = trim((string) ($docRow['tags'] ?? ''));
                    $favoriteUrl = route_url_clean('members_library', ['q' => $docTitle, 'category' => $docCategory, 'tag' => $docTags]);
                    $saved = favorite_toggle((int) $user['id'], 'library_document', (int) $docRow['id'], $docTitle, $favoriteUrl);
                    notify_member((int) $user['id'], 'favorite', $saved ? (string) $t['favorite_added'] : (string) $t['favorite_removed'], $docTitle, $favoriteUrl);
                    set_flash('success', $saved ? (string) $t['favorite_added_msg'] : (string) $t['favorite_removed_msg']);
                }
            }
            redirect_url(route_url_clean('members_library', ['category' => (string) ($_GET['category'] ?? ''), 'q' => (string) ($_GET['q'] ?? ''), 'tag' => (string) ($_GET['tag'] ?? ''), 'p' => max(1, (int) ($_GET['p'] ?? 1))]));
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
                throw new RuntimeException($membersLibraryText('document_missing', 'Document introuvable.', 'Document not found.'));
            }
            if (!$canManageLibrary && (int) ($document['member_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
                throw new RuntimeException($membersLibraryText('document_forbidden', 'Vous ne pouvez pas modifier ce document.', 'You cannot edit this document.'));
            }

            if ($action === 'delete_document') {
                member_library_delete_document_file((string) ($document['file_path'] ?? ''));
                db()->prepare('DELETE FROM member_library_documents WHERE id = ? LIMIT 1')->execute([$documentId]);
                if (table_exists('member_favorites')) {
                    db()->prepare('DELETE FROM member_favorites WHERE target_type = ? AND target_id = ?')->execute(['library_document', $documentId]);
                }
                set_flash('success', $membersLibraryText('document_deleted', 'Document supprimé.', 'Document deleted.'));
                redirect_url($membersLibraryReturnUrl());
            }

            $title = content_proposal_clean_single_line((string) ($_POST['document_title'] ?? ''), 190);
            if ($title === '') {
                throw new RuntimeException($membersLibraryText('document_title_required', 'Un titre est requis.', 'A title is required.'));
            }
            $documentCategory = member_library_category_slug((string) ($_POST['document_category'] ?? 'general'));
            $documentTags = member_library_clean_tags((string) ($_POST['document_tags'] ?? ''));
            $description = content_proposal_clean_multiline((string) ($_POST['document_description'] ?? ''), 5000);

            $file = $_FILES['document_file'] ?? null;
            $hasReplacementFile = is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            if ($hasReplacementFile) {
                $stored = member_library_store_document_upload($file, (int) ($user['id'] ?? 0), 'doc');
                $extractedText = member_library_extract_text((string) $stored['absolute_path'], (string) $stored['extension']);
                db()->prepare('UPDATE member_library_documents SET category = ?, tags = ?, title = ?, description = ?, file_path = ?, extracted_text = ? WHERE id = ?')
                    ->execute([
                        $documentCategory,
                        $documentTags,
                        $title,
                        $description !== '' ? $description : null,
                        (string) $stored['public_path'],
                        $extractedText !== '' ? $extractedText : null,
                        $documentId,
                    ]);
                member_library_delete_document_file((string) ($document['file_path'] ?? ''));
            } else {
                db()->prepare('UPDATE member_library_documents SET category = ?, tags = ?, title = ?, description = ? WHERE id = ?')
                    ->execute([$documentCategory, $documentTags, $title, $description !== '' ? $description : null, $documentId]);
            }

            if (table_exists('member_favorites')) {
                $favoriteUrl = route_url_clean('members_library', ['q' => $title, 'category' => $documentCategory, 'tag' => $documentTags]);
                db()->prepare('UPDATE member_favorites SET title = ?, url = ? WHERE target_type = ? AND target_id = ?')
                    ->execute([$title, $favoriteUrl, 'library_document', $documentId]);
            }
            set_flash('success', $membersLibraryText('document_updated', 'Document mis à jour.', 'Document updated.'));
            redirect_url($membersLibraryReturnUrl());
        }

        if ($action === 'propose_category') {
            $proposalTitle = (string) ($_POST['proposal_category_name'] ?? $_POST['proposal_category'] ?? '');
            $proposalContact = (string) ($_POST['proposal_contact'] ?? '');
            $proposalSummary = content_proposal_details_text([
                (string) ($t['propose_category_reason'] ?? 'Reason') => (string) ($_POST['proposal_reason'] ?? ''),
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

        if ($action === 'propose_tag') {
            $proposalTitle = (string) ($_POST['proposal_tag'] ?? '');
            $proposalContact = (string) ($_POST['proposal_contact'] ?? '');
            $proposalSummary = content_proposal_details_text([
                (string) ($t['propose_tag_reason'] ?? 'Reason') => (string) ($_POST['proposal_reason'] ?? ''),
            ]);
            $proposalStatus = has_permission('admin.access') ? 'accepted' : 'pending';
            $proposalId = content_proposal_create((int) $user['id'], 'members_library', 'tag', $proposalTitle, $proposalSummary, $proposalContact, '', $proposalStatus);
            if ($proposalStatus === 'accepted') {
                set_flash('success', (string) ($t['tag_created_direct'] ?? $t['proposal_recorded']));
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
            $proposalTitle = (string) ($_POST['proposal_title'] ?? '');
            $proposalCategory = member_library_category_slug((string) ($_POST['proposal_category'] ?? 'general'));
            $proposalTags = content_proposal_clean_single_line((string) ($_POST['proposal_tags'] ?? ''), 255);
            $proposalContact = (string) ($_POST['proposal_contact'] ?? '');
            $storedDocument = member_library_store_proposed_document_upload($_FILES['proposal_file'] ?? null, (int) $user['id']);
            $proposalFilePath = (string) $storedDocument['public_path'];
            $proposalSummary = content_proposal_details_text([
                (string) ($t['propose_document_category'] ?? 'Category') => $proposalCategory,
                (string) ($t['tags'] ?? 'Keywords') => $proposalTags,
                (string) ($t['document'] ?? 'Document') => (string) $storedDocument['original_name'],
                (string) ($t['propose_document_description'] ?? 'Description') => (string) ($_POST['proposal_description'] ?? ''),
            ]);
            $autoAccept = has_permission('admin.access');
            $proposalStatus = $autoAccept ? 'accepted' : 'pending';
            $proposalId = content_proposal_create((int) $user['id'], 'members_library', 'content', $proposalTitle, $proposalSummary, $proposalContact, $proposalFilePath, $proposalStatus);
            if ($autoAccept) {
                member_library_apply_accepted_proposal([
                    'id' => $proposalId,
                    'member_id' => (int) $user['id'],
                    'proposal_type' => 'content',
                    'title' => $proposalTitle,
                    'summary' => $proposalSummary,
                    'source_ref' => $proposalFilePath,
                ], $t);
                set_flash('success', (string) $t['document_validated_direct']);
                redirect_url(route_url('members_library'));
            }
            content_proposal_notify_site((string) $t['propose_document_subject'], [
                'area' => 'members_library',
                'proposal_type' => 'content',
                'title' => content_proposal_clean_single_line($proposalTitle, 190),
                'summary' => $proposalSummary,
                'contact' => content_proposal_clean_single_line($proposalContact, 220),
                'source_ref' => 'content_proposals#' . $proposalId . ' ' . base_url($proposalFilePath),
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
$category = trim((string) ($_GET['category'] ?? ''));
$tag = trim((string) ($_GET['tag'] ?? ''));
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
        'label' => $catCode === 'general' ? 'Général' : $catCode,
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
        $catLabel = $catCode === 'general' ? 'Général' : $catCode;
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
                $catLabel = 'Général';
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
        $categoryLabels[$catCode] = (string) ($catInfo['label'] ?? ($catCode === 'general' ? 'Général' : $catCode));
    }
}
$documentProposalSelectedCategory = $category !== '' ? $category : 'general';
$where = [];
$params = [];
if ($category !== '') {
    $where[] = 'category = ?';
    $params[] = $category;
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

$stmt = db()->prepare('SELECT * FROM member_library_documents' . $whereSql . ' ORDER BY uploaded_at DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);
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
if ($tag !== '') {
    $activeFiltersCount++;
}
$contactEmail = site_contact_email();
$documentProposalUrl = 'mailto:' . rawurlencode($contactEmail) . '?subject=' . rawurlencode((string) $t['propose_document_subject']);
$categoryProposalUrl = 'mailto:' . rawurlencode($contactEmail) . '?subject=' . rawurlencode((string) $t['propose_category_subject']);
$tagProposalUrl = 'mailto:' . rawurlencode($contactEmail) . '?subject=' . rawurlencode((string) $t['propose_tag_subject']);
$pendingLibraryAdminUrl = route_url_clean('admin_library', ['status' => 'pending']) . '#pending-proposals';
$pendingLibraryAdminLabel = $locale === 'fr' ? 'Administrer' : 'Manage';

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
        $relatedStmt = db()->prepare('SELECT id, category, title FROM member_library_documents WHERE category IN (' . $catPlaceholders . ') ORDER BY uploaded_at DESC, id DESC LIMIT 300');
        $relatedStmt->execute($categoryKeys);
        $relatedPool = $relatedStmt->fetchAll() ?: [];

        $poolByCategory = [];
        foreach ($relatedPool as $candidate) {
            $candidateCategory = trim((string) ($candidate['category'] ?? 'general'));
            if ($candidateCategory === '') {
                $candidateCategory = 'general';
            }
            $poolByCategory[$candidateCategory][] = $candidate;
        }

        foreach ($documents as $documentRow) {
            $docId = (int) ($documentRow['id'] ?? 0);
            $docCategory = trim((string) ($documentRow['category'] ?? 'general'));
            if ($docCategory === '') {
                $docCategory = 'general';
            }
            $relatedByDocumentId[$docId] = [];
            foreach (($poolByCategory[$docCategory] ?? []) as $candidate) {
                if ((int) ($candidate['id'] ?? 0) === $docId) {
                    continue;
                }
                $relatedByDocumentId[$docId][] = $candidate;
                if (count($relatedByDocumentId[$docId]) >= 3) {
                    break;
                }
            }
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
                    <span><?= e((string) ($t['categories'] ?? $t['all_categories'])) ?></span>
                    <strong><?= (int) count($categories) ?></strong>
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
                    <summary class="button" aria-haspopup="menu"><?= e((string) ($t['propose_menu'] ?? 'Proposer')) ?></summary>
                    <div class="members-library-propose-menu-panel" role="menu">
                        <a class="members-library-propose-menu-item" role="menuitem" href="<?= e($categoryProposalUrl) ?>" data-members-library-modal-open="members-library-category-dialog" aria-haspopup="dialog" aria-controls="members-library-category-dialog"><?= e((string) ($t['propose_category_item'] ?? 'Une thématique')) ?></a>
                        <a class="members-library-propose-menu-item" role="menuitem" href="<?= e($tagProposalUrl) ?>" data-members-library-modal-open="members-library-tag-dialog" aria-haspopup="dialog" aria-controls="members-library-tag-dialog"><?= e((string) ($t['propose_tag_item'] ?? 'Un mot clé')) ?></a>
                        <a class="members-library-propose-menu-item" role="menuitem" href="<?= e($documentProposalUrl) ?>" data-members-library-modal-open="members-library-document-dialog" aria-haspopup="dialog" aria-controls="members-library-document-dialog"><?= e((string) ($t['propose_document_item'] ?? 'Un document')) ?></a>
                    </div>
                </details>
                <?php if ($canManageLibrary): ?>
                    <a class="button secondary" href="<?= e($pendingLibraryAdminUrl) ?>"><?= e($pendingLibraryAdminLabel) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <dialog class="members-library-dialog" id="members-library-category-dialog" aria-labelledby="members-library-category-title">
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
                <label><span><?= e((string) $t['proposal_contact']) ?></span><input type="text" name="proposal_contact" maxlength="220" value="<?= e((string) ($user['email'] ?? '')) ?>" required></label>
                <div class="members-library-dialog-actions module-dialog-actions">
                    <button class="button" type="submit"><?= e((string) $t['proposal_submit']) ?></button>
                    <button class="button secondary" type="button" data-members-library-modal-close><?= e((string) $t['proposal_cancel']) ?></button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog class="members-library-dialog" id="members-library-tag-dialog" aria-labelledby="members-library-tag-title">
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
                <label><span><?= e((string) $t['proposal_contact']) ?></span><input type="text" name="proposal_contact" maxlength="220" value="<?= e((string) ($user['email'] ?? '')) ?>" required></label>
                <div class="members-library-dialog-actions module-dialog-actions">
                    <button class="button" type="submit"><?= e((string) $t['proposal_submit']) ?></button>
                    <button class="button secondary" type="button" data-members-library-modal-close><?= e((string) $t['proposal_cancel']) ?></button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog class="members-library-dialog" id="members-library-document-dialog" aria-labelledby="members-library-document-title">
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
                <label><span><?= e((string) $t['tags']) ?></span><input type="text" name="proposal_tags" maxlength="255"></label>
                <label><span><?= e((string) $t['document']) ?></span><input type="file" name="proposal_file" accept=".pdf,.docx,.txt,.md,.html,.htm,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,text/markdown,text/html" required></label>
                <label><span><?= e((string) $t['propose_document_description']) ?></span><textarea name="proposal_description" rows="5" maxlength="1600"></textarea></label>
                <label><span><?= e((string) $t['proposal_contact']) ?></span><input type="text" name="proposal_contact" maxlength="220" value="<?= e((string) ($user['email'] ?? '')) ?>" required></label>
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
            <?php if ($tag !== ''): ?>
                <input type="hidden" name="tag" value="<?= e($tag) ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
            <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
            <?php if ($search !== ''): ?>
                <a class="button secondary" href="<?= e(route_url_clean('members_library', ['category' => $category, 'tag' => $tag])) ?>"><?= e((string) $t['reset']) ?></a>
            <?php endif; ?>
        </form>
        <?php if ($search !== '' || $category !== '' || $tag !== ''): ?>
            <p class="help"><?= e((string) $t['documents']) ?> : <?= (int) $totalDocuments ?></p>
        <?php endif; ?>
    </section>

    <section class="members-library-layout module-taxonomy-layout">
        <aside class="members-library-index module-taxonomy-index card">
            <p class="members-library-index-title module-taxonomy-title"><?= e((string) $t['topics']) ?></p>
            <nav class="members-library-category-list module-taxonomy-list" aria-label="<?= e((string) $t['topics']) ?>">
                <a class="members-library-category-item module-taxonomy-item<?= $category === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('members_library', ['q' => $search, 'tag' => $tag])) ?>">
                    <span><?= e((string) $t['all_categories']) ?></span>
                    <strong><?= (int) array_sum(array_map(static fn(array $cat): int => (int) ($cat['total'] ?? 0), $categories)) ?></strong>
                </a>
                <?php if ($categories === []): ?>
                    <p class="help"><?= e((string) ($t['empty'] ?? 'Aucun document trouve.')) ?></p>
                <?php endif; ?>
                <?php foreach ($categories as $cat): ?>
                    <?php $catName = trim((string) ($cat['category'] ?? 'general')); if ($catName === '') { $catName = 'general'; } ?>
                    <a class="members-library-category-item module-taxonomy-item<?= $catName === $category ? ' is-active' : '' ?>" href="<?= e(route_url_clean('members_library', ['category' => $catName, 'q' => $search, 'tag' => $tag])) ?>">
                        <span><?= e((string) ($cat['label'] ?? $catName)) ?></span>
                        <strong><?= (int) ($cat['total'] ?? 0) ?></strong>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <div class="members-library-content module-taxonomy-content">
            <?php if ($documents === []): ?>
                <div class="card">
                    <p><?= e((string) $t['empty']) ?><?php if ($search !== '' || $category !== '' || $tag !== ''): ?><?= e((string) $t['for_filters']) ?>.<?php endif; ?></p>
                </div>
            <?php endif; ?>

            <div class="news-grid members-library-document-grid">
            <?php foreach ($documents as $document): ?>
                <?php $safePath = safe_storage_public_path_or_null((string) ($document['file_path'] ?? ''), ['storage/uploads/library/']); ?>
                <?php if ($safePath === null) { continue; } ?>
                <?php $extension = strtolower(pathinfo($safePath, PATHINFO_EXTENSION)); ?>
                <?php $docCategory = trim((string) ($document['category'] ?? 'general')); if ($docCategory === '') { $docCategory = 'general'; } ?>
                <?php $docCategoryLabel = (string) ($categoryLabels[$docCategory] ?? ($docCategory === 'general' ? 'Général' : $docCategory)); ?>
                <?php $docTitle = trim((string) ($document['title'] ?? '')); if ($docTitle === '') { $docTitle = (string) $t['document']; } ?>
                <?php $docDescription = trim((string) ($document['description'] ?? '')); ?>
                <?php $docTags = trim((string) ($document['tags'] ?? '')); ?>
                <?php $docExtract = trim((string) ($document['extracted_text'] ?? '')); ?>
                <?php $docId = (int) ($document['id'] ?? 0); ?>
                <?php $relatedDocs = $relatedByDocumentId[$docId] ?? []; ?>
                <?php $isFavorite = favorite_is_saved((int) $user['id'], 'library_document', (int) ($document['id'] ?? 0)); ?>
                <?php $canEditDocument = $canManageLibrary || (int) ($document['member_id'] ?? 0) === (int) ($user['id'] ?? 0); ?>
                <?php $editDialogId = 'members-library-edit-dialog-' . $docId; ?>
                <article class="news-card feature-card members-library-document-card">
                    <span class="badge muted"><?= e($docCategoryLabel) ?> / <?= e(strtoupper($extension)) ?></span>
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
                        <summary><?= e((string) ($t['related_docs'] ?? 'Documents lies')) ?></summary>
                        <?php if ($relatedDocs === []): ?>
                            <p class="help"><?= e((string) ($t['no_related_docs'] ?? 'Aucun document lie.')) ?></p>
                        <?php else: ?>
                            <ul class="help" style="padding-left:1rem;margin:.4rem 0;">
                                <?php foreach ($relatedDocs as $related): ?>
                                    <?php $relatedTitle = trim((string) ($related['title'] ?? '')); if ($relatedTitle === '') { $relatedTitle = (string) ($t['document'] ?? 'Document'); } ?>
                                    <li><a href="<?= e(route_url_clean('members_library', ['q' => $relatedTitle, 'category' => (string) ($related['category'] ?? ''), 'tag' => $tag])) ?>"><?= e($relatedTitle) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </details>
                    <div class="actions members-library-document-actions">
                        <?php if ($canEditDocument): ?>
                            <button class="button secondary" type="button" data-members-library-modal-open="<?= e($editDialogId) ?>" aria-haspopup="dialog" aria-controls="<?= e($editDialogId) ?>"><?= e($membersLibraryText('edit_document', 'Modifier', 'Edit')) ?></button>
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
                                    <h2 id="<?= e($editDialogId) ?>-title"><?= e($membersLibraryText('edit_document_title', 'Modifier le document', 'Edit document')) ?></h2>
                                    <p class="help"><?= e($docTitle) ?></p>
                                </div>
                                <button class="members-library-dialog-close module-dialog-close" type="button" data-members-library-modal-close aria-label="<?= e((string) $t['modal_close']) ?>">&times;</button>
                            </div>
                            <form class="members-library-dialog-form module-dialog-form" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="update_document">
                                <input type="hidden" name="document_id" value="<?= $docId ?>">
                                <input type="hidden" name="return_category" value="<?= e($category) ?>">
                                <input type="hidden" name="return_q" value="<?= e($search) ?>">
                                <input type="hidden" name="return_tag" value="<?= e($tag) ?>">
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
                                <label><span><?= e((string) $t['tags']) ?></span><input type="text" name="document_tags" value="<?= e($docTags) ?>" maxlength="255"></label>
                                <label><span><?= e((string) $t['propose_document_description']) ?></span><textarea name="document_description" rows="5" maxlength="5000"><?= e($docDescription) ?></textarea></label>
                                <label><span><?= e($membersLibraryText('replace_document_file', 'Remplacer le fichier', 'Replace file')) ?></span><input type="file" name="document_file" accept=".pdf,.docx,.txt,.md,.html,.htm,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,text/markdown,text/html"></label>
                                <div class="members-library-dialog-actions module-dialog-actions">
                                    <button class="button" type="submit"><?= e($membersLibraryText('save_document', 'Enregistrer', 'Save')) ?></button>
                                    <button class="button secondary" type="button" data-members-library-modal-close><?= e((string) $t['proposal_cancel']) ?></button>
                                </div>
                            </form>
                            <form method="post" class="members-library-delete-form">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete_document">
                                <input type="hidden" name="document_id" value="<?= $docId ?>">
                                <input type="hidden" name="return_category" value="<?= e($category) ?>">
                                <input type="hidden" name="return_q" value="<?= e($search) ?>">
                                <input type="hidden" name="return_tag" value="<?= e($tag) ?>">
                                <input type="hidden" name="return_p" value="<?= $page ?>">
                                <p class="help"><?= e($membersLibraryText('delete_document_warning', 'La suppression du fichier est définitive.', 'Deleting this file is permanent.')) ?></p>
                                <button class="button secondary members-library-danger" type="submit"><?= e($membersLibraryText('delete_document', 'Supprimer le document', 'Delete document')) ?></button>
                            </form>
                        </div>
                    </dialog>
                <?php endif; ?>
            <?php endforeach; ?>
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="card actions">
                    <?php if ($page > 1): ?><a class="button secondary" href="<?= e(route_url_clean('members_library', ['category' => $category, 'q' => $search, 'tag' => $tag, 'p' => $page - 1])) ?>">&larr; <?= e((string) $t['prev']) ?></a><?php endif; ?>
                    <span class="pill"><?= e((string) $t['page']) ?> <?= $page ?> / <?= $totalPages ?></span>
                    <?php if ($page < $totalPages): ?><a class="button secondary" href="<?= e(route_url_clean('members_library', ['category' => $category, 'q' => $search, 'tag' => $tag, 'p' => $page + 1])) ?>"><?= e((string) $t['next']) ?> &rarr;</a><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php echo render_layout((string) ob_get_clean(), (string) $t['title']);

