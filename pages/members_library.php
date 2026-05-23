<?php
declare(strict_types=1);

require_login();
$locale = current_locale();
$t = i18n_domain_locale('members_library', $locale);
/** @var array{id:int} $user */
$user = current_user() ?? ['id' => 0];
set_page_meta(['title' => (string) $t['title'], 'description' => (string) $t['meta_desc'], 'robots' => 'noindex,follow']);

if (!ensure_member_library_table()) {
    echo render_layout('<div class="card"><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['title']);
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'toggle_favorite_document') {
    verify_csrf();
    $documentId = (int) ($_POST['document_id'] ?? 0);
    if ($documentId > 0) {
        $docStmt = db()->prepare('SELECT id, title, category FROM member_library_documents WHERE id = ? LIMIT 1');
        $docStmt->execute([$documentId]);
        $docRow = $docStmt->fetch() ?: null;
        if ($docRow !== null) {
            $docTitle = trim((string) ($docRow['title'] ?? 'Document'));
            $docCategory = trim((string) ($docRow['category'] ?? ''));
            $favoriteUrl = route_url_clean('members_library', ['q' => $docTitle, 'category' => $docCategory]);
            $saved = favorite_toggle((int) $user['id'], 'library_document', (int) $docRow['id'], $docTitle, $favoriteUrl);
            notify_member((int) $user['id'], 'favorite', $saved ? 'Favori ajouté' : 'Favori retiré', $docTitle, $favoriteUrl);
            set_flash('success', $saved ? 'Document ajouté aux favoris.' : 'Document retiré des favoris.');
        }
    }
    redirect_url(route_url_clean('members_library', ['category' => (string) ($_GET['category'] ?? ''), 'q' => (string) ($_GET['q'] ?? ''), 'p' => max(1, (int) ($_GET['p'] ?? 1))]));
}

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 120) {
    $search = mb_substr($search, 0, 120);
}
$category = trim((string) ($_GET['category'] ?? ''));
$page = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 12;

$categories = db()->query('SELECT category, COUNT(*) AS total FROM member_library_documents GROUP BY category ORDER BY category')->fetchAll() ?: [];
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

ob_start();
?>
<div class="card members-library-page">
    <div class="row-between">
        <div>
            <h1><?= e((string) $t['title']) ?></h1>
            <p><?= e((string) $t['intro']) ?></p>
        </div>
        <span class="badge muted"><?= $totalDocuments ?> <?= e((string) $t['documents']) ?></span>
    </div>

    <form method="get" class="inline-form" style="flex-wrap:wrap; margin-bottom:.8rem;">
        <input type="hidden" name="route" value="members_library">
        <select name="category">
            <option value=""><?= e((string) $t['all_categories']) ?></option>
            <?php foreach ($categories as $cat): $catName = trim((string) ($cat['category'] ?? 'general')); if ($catName === '') { $catName = 'general'; } ?>
                <option value="<?= e($catName) ?>" <?= $catName === $category ? 'selected' : '' ?>><?= e($catName) ?> (<?= (int) ($cat['total'] ?? 0) ?>)</option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
        <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
        <?php if ($search !== '' || $category !== ''): ?><a class="button secondary" href="<?= e(route_url('members_library')) ?>"><?= e((string) $t['reset']) ?></a><?php endif; ?>
    </form>

    <?php if ($documents === []): ?>
        <p class="help"><?= e((string) $t['empty']) ?><?= ($search !== '' || $category !== '') ? e((string) $t['for_filters']) : '' ?>.</p>
    <?php endif; ?>

    <div class="news-grid">
    <?php foreach ($documents as $document): ?>
        <?php $safePath = safe_storage_public_path_or_null((string) ($document['file_path'] ?? ''), ['storage/uploads/library/']); ?>
        <?php if ($safePath === null) { continue; } ?>
        <?php $extension = strtolower(pathinfo($safePath, PATHINFO_EXTENSION)); ?>
        <?php $docCategory = trim((string) ($document['category'] ?? 'general')); if ($docCategory === '') { $docCategory = 'general'; } ?>
        <?php $docTitle = trim((string) ($document['title'] ?? '')); if ($docTitle === '') { $docTitle = (string) $t['document']; } ?>
        <?php $docDescription = trim((string) ($document['description'] ?? '')); ?>
        <?php $docExtract = trim((string) ($document['extracted_text'] ?? '')); ?>
        <?php $isFavorite = favorite_is_saved((int) $user['id'], 'library_document', (int) ($document['id'] ?? 0)); ?>
        <article class="card feature-card" style="margin-top:12px;">
            <p><span class="badge muted"><?= e($docCategory) ?></span> <span class="badge muted"><?= e(strtoupper($extension)) ?></span></p>
            <h3><?= e($docTitle) ?></h3>
            <?php if ($docDescription !== ''): ?><p><?= e($docDescription) ?></p><?php endif; ?>
            <?php if ($docExtract !== ''): ?><p class="help"><?= e(mb_safe_strimwidth($docExtract, 0, 220, '...')) ?></p><?php endif; ?>
            <?php if ($extension === 'pdf'): ?>
                <details class="admin-library-preview-toggle">
                    <summary><?= e((string) $t['preview']) ?></summary>
                    <iframe src="<?= e(base_url($safePath)) ?>" class="admin-library-pdf-preview" loading="lazy"></iframe>
                </details>
            <?php endif; ?>
            <p class="actions">
                <a class="button secondary" href="<?= e(base_url($safePath)) ?>" target="_blank" rel="noopener"><?= e((string) $t['open']) ?></a>
                <form method="post" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="toggle_favorite_document">
                    <input type="hidden" name="document_id" value="<?= (int) ($document['id'] ?? 0) ?>">
                    <button class="button secondary" type="submit"><?= $isFavorite ? '★ Favori' : '☆ Favori' ?></button>
                </form>
            </p>
        </article>
    <?php endforeach; ?>
    </div>
    <?php if ($totalPages > 1): ?>
        <nav class="admin-library-pagination" aria-label="Pagination documents">
            <?php if ($page > 1): ?><a class="button secondary" href="<?= e(route_url_clean('members_library', ['category' => $category, 'q' => $search, 'p' => $page - 1])) ?>">&larr; <?= e((string) $t['prev']) ?></a><?php endif; ?>
            <span class="badge muted"><?= e((string) $t['page']) ?> <?= $page ?> / <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?><a class="button secondary" href="<?= e(route_url_clean('members_library', ['category' => $category, 'q' => $search, 'p' => $page + 1])) ?>"><?= e((string) $t['next']) ?> &rarr;</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
<?php echo render_layout((string) ob_get_clean(), (string) $t['title']);
