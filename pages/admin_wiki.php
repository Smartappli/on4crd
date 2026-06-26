<?php
declare(strict_types=1);

require_permission('wiki.moderate');

$locale = current_locale();
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/admin_wiki.php');
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};
$tr = static function (string $key) use ($t): string {
    $value = trim($t($key));

    return $value !== '' && $value !== $key ? $value : $key;
};
$wikiMessages = i18n_domain_locale('wiki', $locale);
$wikiCategories = wiki_categories($wikiMessages);
$wikiSubcategoriesByCategory = wiki_subcategories_by_category();
$wikiSubsubcategoriesByParent = wiki_subsubcategories_by_parent();
$wikiThemeLabel = (string) $wikiMessages['themes'];
$wikiSubcategoryLabel = (string) $wikiMessages['subcategory_field'];
$wikiSubsubcategoryLabel = (string) $wikiMessages['subsubcategory_field'];

$statusLabels = [
    'pending' => $tr('status_pending'),
    'published' => $tr('status_published'),
    'rejected' => $tr('status_rejected'),
];
$proposalStatusLabels = [
    'pending' => $tr('proposal_status_pending'),
    'reviewed' => $tr('proposal_status_reviewed'),
    'accepted' => $tr('proposal_status_accepted'),
    'rejected' => $tr('proposal_status_rejected'),
];
$proposalTypeLabels = [
    'category' => $tr('proposal_type_category'),
    'content' => $tr('proposal_type_content'),
    'domain' => $tr('proposal_type_domain'),
    'subsubcategory' => $tr('proposal_type_subsubcategory'),
    'tag' => $tr('proposal_type_tag'),
];
$statusFilter = trim((string) ($_GET['status'] ?? ''));
if (!isset($statusLabels[$statusFilter])) {
    $statusFilter = '';
}
$pendingProposalUrl = route_url_clean('admin_wiki', ['status' => 'pending']) . '#pending-proposals';

set_page_meta([
    'title' => $t('layout'),
    'description' => $t('meta_desc'),
    'robots' => 'noindex,nofollow',
]);

if (!ensure_wiki_tables()) {
    echo render_layout('<div class="card"><h1>' . e($t('layout')) . '</h1><p>' . e($t('meta_desc')) . '</p></div>', $t('layout'));
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnStatus = trim((string) ($_POST['return_status'] ?? ''));
    if (!isset($statusLabels[$returnStatus])) {
        $returnStatus = '';
    }

    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? 'update_page_status');

        if ($action === 'add_category') {
            if (!wiki_ensure_categories_table()) {
                throw new RuntimeException($tr('storage_unavailable'));
            }
            $label = content_proposal_clean_single_line((string) ($_POST['category_label'] ?? ''), 160);
            $code = wiki_category_code((string) ($_POST['category_code'] ?? $label));
            if ($label === '' || $code === '') {
                throw new RuntimeException($tr('invalid_page'));
            }
            db()->prepare('INSERT INTO wiki_categories (code, label, deleted_at) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
                ->execute([$code, $label]);
            set_flash('success', $tr('category_saved'));
            redirect_url(route_url_clean('admin_wiki'));
        }

        if ($action === 'update_category') {
            if (!wiki_ensure_categories_table()) {
                throw new RuntimeException($tr('storage_unavailable'));
            }
            $category = wiki_category_from_input((string) ($_POST['category_code'] ?? ''), $wikiCategories);
            $label = content_proposal_clean_single_line((string) ($_POST['category_label'] ?? ''), 160);
            if ($label === '') {
                throw new RuntimeException($tr('invalid_page'));
            }
            db()->prepare('INSERT INTO wiki_categories (code, label, deleted_at) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
                ->execute([$category, $label]);
            set_flash('success', $tr('category_saved'));
            redirect_url(route_url_clean('admin_wiki'));
        }

        if ($action === 'delete_category') {
            if (!wiki_ensure_categories_table()) {
                throw new RuntimeException($tr('storage_unavailable'));
            }
            $category = wiki_category_from_input((string) ($_POST['category_code'] ?? ''), $wikiCategories);
            if ($category === 'general') {
                throw new RuntimeException($tr('invalid_page'));
            }
            $subCountStmt = db()->prepare('SELECT COUNT(*) FROM wiki_subcategories WHERE category_code = ?');
            $subCountStmt->execute([$category]);
            if ((int) $subCountStmt->fetchColumn() > 0) {
                throw new RuntimeException($tr('err_category_has_subcategories'));
            }
            db()->prepare('UPDATE wiki_pages SET category = "general", subcategory = "", subsubcategory = "" WHERE category = ?')->execute([$category]);
            db()->prepare('INSERT INTO wiki_categories (code, label, deleted_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE deleted_at = NOW()')
                ->execute([$category, (string) ($wikiCategories[$category] ?? wiki_category_label_from_code($category))]);
            set_flash('success', $tr('category_deleted'));
            redirect_url(route_url_clean('admin_wiki'));
        }

        if ($action === 'add_subcategory') {
            if (!wiki_ensure_subcategories_table()) {
                throw new RuntimeException($tr('storage_unavailable'));
            }
            $category = wiki_category_from_input((string) ($_POST['subcategory_category'] ?? 'general'), $wikiCategories);
            $label = content_proposal_clean_single_line((string) ($_POST['subcategory_label'] ?? ''), 160);
            $code = wiki_subcategory_code((string) ($_POST['subcategory_code'] ?? $label));
            if ($label === '' || $code === '') {
                throw new RuntimeException($tr('invalid_page'));
            }
            db()->prepare('INSERT INTO wiki_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                ->execute([$category, $code, $label]);
            set_flash('success', $tr('subcategory_saved'));
            redirect_url(route_url_clean('admin_wiki'));
        }

        if ($action === 'update_subcategory') {
            if (!wiki_ensure_subcategories_table()) {
                throw new RuntimeException($tr('storage_unavailable'));
            }
            $parts = wiki_subcategory_ref_parts((string) ($_POST['subcategory_ref'] ?? ''));
            $category = wiki_category_from_input($parts['category'] !== '' ? $parts['category'] : (string) ($_POST['category'] ?? 'general'), $wikiCategories);
            $subcategory = wiki_subcategory_code($parts['subcategory']);
            $label = content_proposal_clean_single_line((string) ($_POST['subcategory_label'] ?? ''), 160);
            if ($subcategory === '' || $label === '') {
                throw new RuntimeException($tr('invalid_page'));
            }
            db()->prepare('INSERT INTO wiki_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                ->execute([$category, $subcategory, $label]);
            set_flash('success', $tr('subcategory_saved'));
            redirect_url(route_url_clean('admin_wiki'));
        }

        if ($action === 'delete_subcategory') {
            if (!wiki_ensure_subcategories_table()) {
                throw new RuntimeException($tr('storage_unavailable'));
            }
            $parts = wiki_subcategory_ref_parts((string) ($_POST['subcategory_ref'] ?? ''));
            $category = wiki_category_from_input($parts['category'] !== '' ? $parts['category'] : (string) ($_POST['category'] ?? 'general'), $wikiCategories);
            $subcategory = wiki_subcategory_code($parts['subcategory']);
            if ($subcategory === '') {
                throw new RuntimeException($tr('invalid_page'));
            }
            if (wiki_ensure_subsubcategories_table()) {
                $subsubcategoryCountStmt = db()->prepare('SELECT COUNT(*) FROM wiki_subsubcategories WHERE category_code = ? AND subcategory_code = ?');
                $subsubcategoryCountStmt->execute([$category, $subcategory]);
                if ((int) $subsubcategoryCountStmt->fetchColumn() > 0) {
                    throw new RuntimeException($tr('err_subcategory_has_subsubcategories'));
                }
            }
            $countStmt = db()->prepare('SELECT COUNT(*) FROM wiki_pages WHERE category = ? AND subcategory = ?');
            $countStmt->execute([$category, $subcategory]);
            if ((int) $countStmt->fetchColumn() > 0) {
                throw new RuntimeException($tr('err_subcategory_has_documents'));
            }
            db()->prepare('DELETE FROM wiki_subcategories WHERE category_code = ? AND code = ?')->execute([$category, $subcategory]);
            set_flash('success', $tr('subcategory_deleted'));
            redirect_url(route_url_clean('admin_wiki'));
        }

        if ($action === 'add_subsubcategory') {
            if (!wiki_ensure_subsubcategories_table()) {
                throw new RuntimeException($tr('storage_unavailable'));
            }
            $parentParts = wiki_subcategory_ref_parts((string) ($_POST['subsubcategory_parent_ref'] ?? ''));
            $category = wiki_category_from_input($parentParts['category'] !== '' ? $parentParts['category'] : (string) ($_POST['subsubcategory_category'] ?? 'general'), $wikiCategories);
            $subcategory = wiki_subcategory_code($parentParts['subcategory'] !== '' ? $parentParts['subcategory'] : (string) ($_POST['subsubcategory_parent'] ?? ''));
            [$category, $subcategory] = wiki_taxonomy_from_input($category, wiki_subcategory_ref($category, $subcategory), $wikiCategories);
            $label = content_proposal_clean_single_line((string) ($_POST['subsubcategory_label'] ?? ''), 160);
            $code = wiki_subsubcategory_code((string) ($_POST['subsubcategory_code'] ?? $label));
            if ($subcategory === '' || $label === '' || $code === '') {
                throw new RuntimeException($tr('invalid_page'));
            }
            db()->prepare('INSERT INTO wiki_subsubcategories (category_code, subcategory_code, code, label) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                ->execute([$category, $subcategory, $code, $label]);
            set_flash('success', $tr('subsubcategory_saved'));
            redirect_url(route_url_clean('admin_wiki'));
        }

        if ($action === 'update_subsubcategory') {
            if (!wiki_ensure_subsubcategories_table()) {
                throw new RuntimeException($tr('storage_unavailable'));
            }
            $category = wiki_category_from_input((string) ($_POST['subsubcategory_category'] ?? 'general'), $wikiCategories);
            $subcategory = wiki_subcategory_code((string) ($_POST['subsubcategory_parent'] ?? ''));
            $code = wiki_subsubcategory_code((string) ($_POST['subsubcategory_code'] ?? ''));
            $label = content_proposal_clean_single_line((string) ($_POST['subsubcategory_label'] ?? ''), 160);
            if ($subcategory === '' || $code === '' || $label === '') {
                throw new RuntimeException($tr('invalid_page'));
            }
            [$category, $subcategory] = wiki_taxonomy_from_input($category, wiki_subcategory_ref($category, $subcategory), $wikiCategories);
            db()->prepare('INSERT INTO wiki_subsubcategories (category_code, subcategory_code, code, label) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                ->execute([$category, $subcategory, $code, $label]);
            set_flash('success', $tr('subsubcategory_saved'));
            redirect_url(route_url_clean('admin_wiki'));
        }

        if ($action === 'delete_subsubcategory') {
            if (!wiki_ensure_subsubcategories_table()) {
                throw new RuntimeException($tr('storage_unavailable'));
            }
            $category = wiki_category_from_input((string) ($_POST['subsubcategory_category'] ?? 'general'), $wikiCategories);
            $subcategory = wiki_subcategory_code((string) ($_POST['subsubcategory_parent'] ?? ''));
            $code = wiki_subsubcategory_code((string) ($_POST['subsubcategory_code'] ?? ''));
            if ($subcategory === '' || $code === '') {
                throw new RuntimeException($tr('invalid_page'));
            }
            [$category, $subcategory] = wiki_taxonomy_from_input($category, wiki_subcategory_ref($category, $subcategory), $wikiCategories);
            $countStmt = db()->prepare('SELECT COUNT(*) FROM wiki_pages WHERE category = ? AND subcategory = ? AND subsubcategory = ?');
            $countStmt->execute([$category, $subcategory, $code]);
            if ((int) $countStmt->fetchColumn() > 0) {
                throw new RuntimeException($tr('err_subsubcategory_has_documents'));
            }
            db()->prepare('DELETE FROM wiki_subsubcategories WHERE category_code = ? AND subcategory_code = ? AND code = ?')->execute([$category, $subcategory, $code]);
            set_flash('success', $tr('subsubcategory_deleted'));
            redirect_url(route_url_clean('admin_wiki'));
        }

        if ($action === 'update_proposal_status') {
            $proposalId = (int) ($_POST['proposal_id'] ?? 0);
            $proposalStatus = (string) ($_POST['proposal_status'] ?? 'pending');
            $moderationNote = trim((string) ($_POST['moderation_note'] ?? ''));
            if ($proposalId <= 0 || !isset($proposalStatusLabels[$proposalStatus])) {
                throw new RuntimeException($tr('invalid_proposal'));
            }
            if (!ensure_content_proposals_table()) {
                throw new RuntimeException($tr('proposal_storage_unavailable'));
            }

            if ($proposalStatus === 'accepted') {
                $proposalStmt = db()->prepare('SELECT id, summary FROM content_proposals WHERE id = ? AND area = "wiki" LIMIT 1');
                $proposalStmt->execute([$proposalId]);
                $proposal = $proposalStmt->fetch() ?: null;
                if (!is_array($proposal)) {
                    throw new RuntimeException($tr('invalid_proposal'));
                }
                if (wiki_content_proposal_action((string) ($proposal['summary'] ?? '')) === 'delete_page') {
                    wiki_delete_page_record(wiki_content_proposal_page_id((string) ($proposal['summary'] ?? '')));
                }
            }

            db()->prepare('UPDATE content_proposals SET status = ?, moderation_note = ? WHERE id = ? AND area = "wiki"')
                ->execute([$proposalStatus, $moderationNote !== '' ? $moderationNote : null, $proposalId]);
            set_flash('success', $tr('proposal_status_saved'));
            redirect_url($pendingProposalUrl);
        }

        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        if ($id <= 0 || !isset($statusLabels[$status])) {
            throw new RuntimeException($tr('invalid_page'));
        }

        $pageStmt = db()->prepare('SELECT id, title, slug, content, category, subcategory, subsubcategory, author_id, status, proposal_kind, source_page_id, target_slug FROM wiki_pages WHERE id = ? LIMIT 1');
        $pageStmt->execute([$id]);
        $page = $pageStmt->fetch();
        if (!is_array($page)) {
            throw new RuntimeException($tr('page_not_found'));
        }

        if ($status === 'published' && (string) ($page['proposal_kind'] ?? 'page') === 'modification') {
            $sourceId = (int) ($page['source_page_id'] ?? 0);
            $sourceStmt = db()->prepare('SELECT id, title, slug, content, category, subcategory, subsubcategory FROM wiki_pages WHERE id = ? AND proposal_kind = "page" LIMIT 1');
            $sourceStmt->execute([$sourceId]);
            $sourcePage = $sourceStmt->fetch();
            if (!is_array($sourcePage)) {
                throw new RuntimeException($tr('source_page_not_found'));
            }

            $targetSlug = wiki_unique_slug((string) $page['title'], (string) ($page['target_slug'] ?: $sourcePage['slug']), $sourceId);
            $approver = current_user();
            $approverId = (int) ($approver['id'] ?? 0);
            $authorId = (int) ($page['author_id'] ?? 0);
            if ($authorId <= 0) {
                $authorId = $approverId;
            }

            $pdo = db();
            $pdo->beginTransaction();
            try {
                $pdo->prepare('INSERT INTO wiki_revisions (wiki_page_id, member_id, content) VALUES (?, ?, ?)')
                    ->execute([$sourceId, $approverId, (string) ($sourcePage['content'] ?? '')]);
                $pdo->prepare('UPDATE wiki_pages SET title = ?, slug = ?, content = ?, category = ?, subcategory = ?, subsubcategory = ?, author_id = ?, status = "published", proposal_kind = "page", source_page_id = NULL, target_slug = NULL, updated_at = NOW() WHERE id = ?')
                    ->execute([(string) $page['title'], $targetSlug, (string) $page['content'], wiki_category_code((string) ($page['category'] ?? 'general')), wiki_subcategory_code((string) ($page['subcategory'] ?? '')), wiki_subsubcategory_code((string) ($page['subsubcategory'] ?? '')), $authorId, $sourceId]);
                $pdo->prepare('DELETE FROM wiki_pages WHERE id = ?')->execute([$id]);
                $pdo->commit();
            } catch (Throwable $throwable) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $throwable;
            }

            set_flash('success', $tr('modification_applied'));
        } else {
            db()->prepare('UPDATE wiki_pages SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$status, $id]);
            set_flash('success', $tr('status_saved'));
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }

    redirect_url(route_url_clean('admin_wiki', $returnStatus !== '' ? ['status' => $returnStatus] : []));
}

$params = [];
$whereSql = '';
if ($statusFilter !== '') {
    $whereSql = ' WHERE p.status = ?';
    $params[] = $statusFilter;
}
$stmt = db()->prepare(
    'SELECT p.id, p.title, p.slug, p.category, p.subcategory, p.subsubcategory, p.status, p.updated_at, p.proposal_kind, p.source_page_id, p.target_slug,
        s.title AS source_title, s.slug AS source_slug
     FROM wiki_pages p
     LEFT JOIN wiki_pages s ON s.id = p.source_page_id
     ' . $whereSql . '
     ORDER BY p.status ASC, p.updated_at DESC'
);
$stmt->execute($params);
$pages = $stmt->fetchAll() ?: [];
$wikiCategoryCounts = [];
$wikiSubcategoryCounts = [];
$wikiSubsubcategoryCounts = [];
foreach ($pages as $pageRow) {
    $categoryCode = wiki_category_code((string) ($pageRow['category'] ?? 'general'));
    $subcategoryCode = wiki_subcategory_code((string) ($pageRow['subcategory'] ?? ''));
    $subsubcategoryCode = wiki_subsubcategory_code((string) ($pageRow['subsubcategory'] ?? ''));
    if ($categoryCode !== '') {
        $wikiCategoryCounts[$categoryCode] = ($wikiCategoryCounts[$categoryCode] ?? 0) + 1;
    }
    if ($categoryCode !== '' && $subcategoryCode !== '') {
        $wikiSubcategoryCounts[$categoryCode . ':' . $subcategoryCode] = ($wikiSubcategoryCounts[$categoryCode . ':' . $subcategoryCode] ?? 0) + 1;
    }
    if ($categoryCode !== '' && $subcategoryCode !== '' && $subsubcategoryCode !== '') {
        $wikiSubsubcategoryCounts[$categoryCode . ':' . $subcategoryCode . ':' . $subsubcategoryCode] = ($wikiSubsubcategoryCounts[$categoryCode . ':' . $subcategoryCode . ':' . $subsubcategoryCode] ?? 0) + 1;
    }
}

$showPendingProposals = $statusFilter === 'pending';
$pendingProposals = [];
if ($showPendingProposals && ensure_content_proposals_table()) {
    $proposalStmt = db()->prepare(
        'SELECT cp.id, cp.member_id, cp.proposal_type, cp.title, cp.summary, cp.contact, cp.source_ref, cp.status, cp.moderation_note, cp.created_at, cp.updated_at, m.callsign, m.email
         FROM content_proposals cp
         LEFT JOIN members m ON m.id = cp.member_id
         WHERE cp.area = "wiki" AND cp.status = "pending"
         ORDER BY cp.created_at ASC, cp.id ASC'
    );
    $proposalStmt->execute();
    $pendingProposals = $proposalStmt->fetchAll() ?: [];
}

ob_start();
?>
<div class="card">
    <div class="row-between">
        <h1><?= e($t('title')) ?></h1>
        <?php if (has_permission('wiki.moderate')): ?>
            <a class="button small" href="<?= e(route_url('wiki_edit')) ?>"><?= e($t('new_page')) ?></a>
        <?php endif; ?>
    </div>
    <form method="get" class="inline-form" style="margin:.75rem 0 1rem;">
        <input type="hidden" name="route" value="admin_wiki">
        <select name="status" aria-label="<?= e($tr('status_filter')) ?>">
            <option value=""><?= e($tr('all_statuses')) ?></option>
            <?php foreach ($statusLabels as $statusCode => $statusLabel): ?>
                <option value="<?= e($statusCode) ?>" <?= $statusFilter === $statusCode ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button secondary small" type="submit"><?= e($tr('filter')) ?></button>
        <?php if ($statusFilter !== ''): ?>
            <a class="button secondary small" href="<?= e(route_url_clean('admin_wiki')) ?>"><?= e($tr('clear_filter')) ?></a>
        <?php endif; ?>
    </form>
    <section class="admin-wiki-taxonomy">
        <h2><?= e($wikiThemeLabel) ?></h2>
        <div class="grid-2">
            <form method="post" class="stack">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="add_category">
                <label><span><?= e($wikiThemeLabel) ?></span><input type="text" name="category_label" maxlength="160" required></label>
                <button class="button" type="submit"><?= e($tr('add_category')) ?></button>
            </form>
            <form method="post" class="stack">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="add_subcategory">
                <label><span><?= e($wikiThemeLabel) ?></span>
                    <select name="subcategory_category">
                        <?php foreach ($wikiCategories as $code => $label): ?>
                            <option value="<?= e((string) $code) ?>"><?= e((string) $label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span><?= e($wikiSubcategoryLabel) ?></span><input type="text" name="subcategory_label" maxlength="160" required></label>
                <button class="button" type="submit"><?= e($tr('add_subcategory')) ?></button>
            </form>
            <form method="post" class="stack">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="add_subsubcategory">
                <label><span><?= e($wikiSubcategoryLabel) ?></span>
                    <select name="subsubcategory_parent_ref" required>
                        <option value=""><?= e((string) ($wikiMessages['no_subcategory'] ?? '')) ?></option>
                        <?php foreach ($wikiSubcategoriesByCategory as $parentCode => $subcategories): ?>
                            <optgroup label="<?= e((string) ($wikiCategories[(string) $parentCode] ?? wiki_category_label_from_code((string) $parentCode))) ?>">
                                <?php foreach ($subcategories as $subcategoryInfo): ?>
                                    <?php $subCode = wiki_subcategory_code((string) ($subcategoryInfo['code'] ?? '')); ?>
                                    <?php if ($subCode === '') { continue; } ?>
                                    <option value="<?= e(wiki_subcategory_ref((string) $parentCode, $subCode)) ?>"><?= e((string) ($subcategoryInfo['label'] ?? $subCode)) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span><?= e($wikiSubsubcategoryLabel) ?></span><input type="text" name="subsubcategory_label" maxlength="160" required></label>
                <button class="button" type="submit"><?= e($tr('add_subsubcategory')) ?></button>
            </form>
        </div>
        <div class="tags-cloud">
            <?php foreach ($wikiCategories as $code => $label): ?>
                <?php $categoryTotal = (int) ($wikiCategoryCounts[(string) $code] ?? 0); ?>
                <?php $subcategoryTotal = count($wikiSubcategoriesByCategory[(string) $code] ?? []); ?>
                <?php $categoryDeleteDisabled = (string) $code === 'general' || $subcategoryTotal > 0; ?>
                <form method="post" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_category">
                    <input type="hidden" name="category_code" value="<?= e((string) $code) ?>">
                    <span class="pill taxonomy-pill-category"><?= e((string) $code) ?> (<?= $categoryTotal ?>)</span>
                    <input type="text" name="category_label" value="<?= e((string) $label) ?>" maxlength="160" required>
                    <button class="button small" type="submit"><?= e($tr('save')) ?></button>
                    <button class="button secondary small" type="submit" name="action" value="delete_category"<?= $categoryDeleteDisabled ? ' disabled' : '' ?>><?= e($tr('delete')) ?></button>
                </form>
            <?php endforeach; ?>
            <?php foreach ($wikiSubcategoriesByCategory as $parentCode => $subcategories): ?>
                <?php foreach ($subcategories as $subcategoryInfo): ?>
                    <?php $subCode = wiki_subcategory_code((string) ($subcategoryInfo['code'] ?? '')); ?>
                    <?php if ($subCode === '') { continue; } ?>
                    <?php $subTotal = (int) ($wikiSubcategoryCounts[(string) $parentCode . ':' . $subCode] ?? 0); ?>
                    <?php $subsubParentRef = (string) $parentCode . ':' . $subCode; ?>
                    <?php $subsubcategoryTotal = count($wikiSubsubcategoriesByParent[$subsubParentRef] ?? []); ?>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_subcategory">
                        <input type="hidden" name="subcategory_ref" value="<?= e(wiki_subcategory_ref((string) $parentCode, $subCode)) ?>">
                        <span class="taxonomy-badge-row">
                            <span class="badge muted taxonomy-pill-category"><?= e((string) ($wikiCategories[(string) $parentCode] ?? $parentCode)) ?></span>
                            <span class="badge muted taxonomy-pill-subcategory"><?= e($subCode) ?></span>
                            <span class="badge muted"><?= $subTotal ?></span>
                        </span>
                        <input type="text" name="subcategory_label" value="<?= e((string) ($subcategoryInfo['label'] ?? $subCode)) ?>" maxlength="160" required>
                        <button class="button small" type="submit"><?= e($tr('save')) ?></button>
                        <button class="button secondary small" type="submit" name="action" value="delete_subcategory"<?= ($subTotal > 0 || $subsubcategoryTotal > 0) ? ' disabled' : '' ?>><?= e($tr('delete')) ?></button>
                    </form>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php foreach ($wikiSubsubcategoriesByParent as $parentRef => $subsubcategories): ?>
                <?php $parentParts = wiki_subcategory_ref_parts((string) $parentRef); ?>
                <?php $parentCategory = $parentParts['category']; ?>
                <?php $parentSubcategory = $parentParts['subcategory']; ?>
                <?php if ($parentCategory === '' || $parentSubcategory === '') { continue; } ?>
                <?php $parentSubcategoryLabel = wiki_category_label_from_code($parentSubcategory); ?>
                <?php foreach ($wikiSubcategoriesByCategory[$parentCategory] ?? [] as $subcategoryInfo): ?>
                    <?php if (wiki_subcategory_code((string) ($subcategoryInfo['code'] ?? '')) === $parentSubcategory) { $parentSubcategoryLabel = (string) ($subcategoryInfo['label'] ?? $parentSubcategory); break; } ?>
                <?php endforeach; ?>
                <?php foreach ($subsubcategories as $subsubcategoryInfo): ?>
                    <?php $subsubCode = wiki_subsubcategory_code((string) ($subsubcategoryInfo['code'] ?? '')); ?>
                    <?php if ($subsubCode === '') { continue; } ?>
                    <?php $subsubTotal = (int) ($wikiSubsubcategoryCounts[$parentCategory . ':' . $parentSubcategory . ':' . $subsubCode] ?? 0); ?>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_subsubcategory">
                        <input type="hidden" name="subsubcategory_category" value="<?= e($parentCategory) ?>">
                        <input type="hidden" name="subsubcategory_parent" value="<?= e($parentSubcategory) ?>">
                        <input type="hidden" name="subsubcategory_code" value="<?= e($subsubCode) ?>">
                        <span class="taxonomy-badge-row">
                            <span class="badge muted taxonomy-pill-category"><?= e((string) ($wikiCategories[$parentCategory] ?? $parentCategory)) ?></span>
                            <span class="badge muted taxonomy-pill-subcategory"><?= e($parentSubcategoryLabel) ?></span>
                            <span class="badge muted taxonomy-pill-subsubcategory"><?= e($subsubCode) ?></span>
                            <span class="badge muted"><?= $subsubTotal ?></span>
                        </span>
                        <input type="text" name="subsubcategory_label" value="<?= e((string) ($subsubcategoryInfo['label'] ?? $subsubCode)) ?>" maxlength="160" required>
                        <button class="button small" type="submit"><?= e($tr('save')) ?></button>
                        <button class="button secondary small" type="submit" name="action" value="delete_subsubcategory"<?= $subsubTotal > 0 ? ' disabled' : '' ?>><?= e($tr('delete')) ?></button>
                    </form>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </section>
    <div class="table-wrap">
        <table>
            <thead><tr><th><?= e($t('th_title')) ?></th><th><?= e($t('th_slug')) ?></th><th><?= e($wikiThemeLabel) ?></th><th><?= e($tr('th_status')) ?></th><th><?= e($t('th_updated')) ?></th><th><?= e($t('th_action')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($pages as $page):
                $pageStatus = (string) ($page['status'] ?? 'published');
                $proposalKind = (string) ($page['proposal_kind'] ?? 'page');
                $isModificationProposal = $proposalKind === 'modification';
                $sourceLabel = trim((string) ($page['source_title'] ?? ''));
                if ($sourceLabel === '') {
                    $sourceLabel = trim((string) ($page['source_slug'] ?? ''));
                }
                $pageCategory = wiki_category_code((string) ($page['category'] ?? 'general'));
                $pageCategoryLabel = (string) ($wikiCategories[$pageCategory] ?? wiki_category_label_from_code($pageCategory));
                $pageSubcategory = wiki_subcategory_code((string) ($page['subcategory'] ?? ''));
                $pageSubcategoryLabel = $pageSubcategory !== '' ? wiki_category_label_from_code($pageSubcategory) : '';
                foreach ($wikiSubcategoriesByCategory[$pageCategory] ?? [] as $subcategoryInfo) {
                    if (wiki_subcategory_code((string) ($subcategoryInfo['code'] ?? '')) === $pageSubcategory) {
                        $pageSubcategoryLabel = (string) ($subcategoryInfo['label'] ?? $pageSubcategoryLabel);
                        break;
                    }
                }
                $pageSubsubcategory = wiki_subsubcategory_code((string) ($page['subsubcategory'] ?? ''));
                $pageSubsubcategoryLabel = $pageSubsubcategory !== '' ? wiki_category_label_from_code($pageSubsubcategory) : '';
                foreach ($wikiSubsubcategoriesByParent[$pageCategory . ':' . $pageSubcategory] ?? [] as $subsubcategoryInfo) {
                    if (wiki_subsubcategory_code((string) ($subsubcategoryInfo['code'] ?? '')) === $pageSubsubcategory) {
                        $pageSubsubcategoryLabel = (string) ($subsubcategoryInfo['label'] ?? $pageSubsubcategoryLabel);
                        break;
                    }
                }
                ?>
                <tr>
                    <td>
                        <?= e((string) $page['title']) ?>
                        <?php if ($isModificationProposal): ?>
                            <div class="help">
                                <span class="badge muted"><?= e($tr('type_modification')) ?></span>
                                <?php if ($sourceLabel !== ''): ?> <?= e($tr('source_page')) ?>: <?= e($sourceLabel) ?><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><code><?= e((string) $page['slug']) ?></code></td>
                    <td><span class="badge muted taxonomy-pill-category"><?= e($pageCategoryLabel) ?></span><?php if ($pageSubcategory !== ''): ?> <span class="badge muted taxonomy-pill-subcategory"><?= e($pageSubcategoryLabel) ?></span><?php endif; ?><?php if ($pageSubcategory !== '' && $pageSubsubcategory !== ''): ?> <span class="badge muted taxonomy-pill-subsubcategory"><?= e($pageSubsubcategoryLabel) ?></span><?php endif; ?></td>
                    <td><span class="badge muted"><?= e((string) ($statusLabels[$pageStatus] ?? $pageStatus)) ?></span></td>
                    <td><?= e((string) $page['updated_at']) ?></td>
                    <td>
                        <?php if ($isModificationProposal): ?>
                            <a href="<?= e(route_url('wiki_view', ['slug' => (string) $page['slug']])) ?>"><?= e($tr('view')) ?></a>
                        <?php else: ?>
                            <a href="<?= e(route_url('wiki_edit', ['id' => (int) $page['id']])) ?>"><?= e($t('edit')) ?></a>
                        <?php endif; ?>
                        <form method="post" class="inline-form" style="display:inline-flex;margin-left:.5rem;">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $page['id'] ?>">
                            <input type="hidden" name="return_status" value="<?= e($statusFilter) ?>">
                            <select name="status">
                                <?php foreach ($statusLabels as $statusCode => $statusLabel): ?>
                                    <option value="<?= e($statusCode) ?>" <?= $pageStatus === $statusCode ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="button secondary small" type="submit">OK</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($pages === []): ?><tr><td colspan="6"><?= e($t('empty')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php if ($showPendingProposals): ?>
<section class="card" id="pending-proposals" aria-labelledby="pending-proposals-title">
    <div class="row-between">
        <h2 id="pending-proposals-title"><?= e($tr('pending_proposals_title')) ?></h2>
        <a class="button secondary small" href="<?= e(route_url_clean('admin_wiki')) ?>"><?= e($tr('clear_filter')) ?></a>
    </div>
    <?php if ($pendingProposals === []): ?>
        <p class="help"><?= e($tr('pending_proposals_empty')) ?></p>
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
                <h3><?= e((string) ($proposal['title'] ?? $tr('proposal_default_title'))) ?></h3>
                <p class="help"><?= e($tr('proposal_author')) ?>: <?= e($memberLabel) ?></p>
                <?php if (trim((string) ($proposal['summary'] ?? '')) !== ''): ?>
                    <p><?= nl2br(e((string) $proposal['summary'])) ?></p>
                <?php endif; ?>
                <?php if (trim((string) ($proposal['contact'] ?? '')) !== ''): ?>
                    <p class="help"><?= e($tr('proposal_contact')) ?>: <?= e((string) $proposal['contact']) ?></p>
                <?php endif; ?>
                <form method="post" class="stack">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_proposal_status">
                    <input type="hidden" name="proposal_id" value="<?= (int) ($proposal['id'] ?? 0) ?>">
                    <input type="hidden" name="return_status" value="pending">
                    <div class="grid-2">
                        <label><?= e($tr('proposal_status_label')) ?>
                            <select name="proposal_status">
                                <?php foreach ($proposalStatusLabels as $statusCode => $statusLabel): ?>
                                    <option value="<?= e($statusCode) ?>" <?= $proposalStatus === $statusCode ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label><?= e($tr('proposal_moderation_note')) ?>
                            <textarea name="moderation_note" rows="3"><?= e((string) ($proposal['moderation_note'] ?? '')) ?></textarea>
                        </label>
                    </div>
                    <p><button class="button small" type="submit"><?= e($tr('proposal_save_status')) ?></button></p>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<?php
echo render_layout((string) ob_get_clean(), $t('layout'));
