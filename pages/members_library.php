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

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 120) {
    $search = mb_substr($search, 0, 120);
}
$category = trim((string) ($_GET['category'] ?? ''));
$tag = trim((string) ($_GET['tag'] ?? ''));
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
    <section class="page-hero">
        <div>
            <p class="eyebrow"><?= e((string) $t['title']) ?></p>
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
            </div>
            <p class="members-library-hero-action">
                <a class="button" href="mailto:on4crd@gmail.com?subject=<?= e(rawurlencode((string) ($t['propose_document_subject'] ?? 'Proposition de document pour la bibliotheque ON4CRD'))) ?>"><?= e((string) ($t['propose_document'] ?? 'Proposer un document')) ?></a>
            </p>
        </div>
    </section>

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

    <section class="members-library-layout">
        <aside class="members-library-index card">
            <p class="members-library-index-title"><?= e((string) ($t['topics'] ?? 'Thematiques')) ?></p>
            <nav class="members-library-category-list" aria-label="<?= e((string) ($t['topics'] ?? 'Thematiques')) ?>">
                <a class="members-library-category-item<?= $category === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('members_library', ['q' => $search, 'tag' => $tag])) ?>">
                    <span><?= e((string) $t['all_categories']) ?></span>
                    <strong><?= (int) array_sum(array_map(static fn(array $cat): int => (int) ($cat['total'] ?? 0), $categories)) ?></strong>
                </a>
                <?php if ($categories === []): ?>
                    <p class="help"><?= e((string) ($t['empty'] ?? 'Aucun document trouve.')) ?></p>
                <?php endif; ?>
                <?php foreach ($categories as $cat): ?>
                    <?php $catName = trim((string) ($cat['category'] ?? 'general')); if ($catName === '') { $catName = 'general'; } ?>
                    <a class="members-library-category-item<?= $catName === $category ? ' is-active' : '' ?>" href="<?= e(route_url_clean('members_library', ['category' => $catName, 'q' => $search, 'tag' => $tag])) ?>">
                        <span><?= e($catName) ?></span>
                        <strong><?= (int) ($cat['total'] ?? 0) ?></strong>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <div class="members-library-content">
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
                <?php $docTitle = trim((string) ($document['title'] ?? '')); if ($docTitle === '') { $docTitle = (string) $t['document']; } ?>
                <?php $docDescription = trim((string) ($document['description'] ?? '')); ?>
                <?php $docTags = trim((string) ($document['tags'] ?? '')); ?>
                <?php $docExtract = trim((string) ($document['extracted_text'] ?? '')); ?>
                <?php $docId = (int) ($document['id'] ?? 0); ?>
                <?php $relatedDocs = $relatedByDocumentId[$docId] ?? []; ?>
                <?php $isFavorite = favorite_is_saved((int) $user['id'], 'library_document', (int) ($document['id'] ?? 0)); ?>
                <article class="news-card feature-card members-library-document-card">
                    <span class="badge muted"><?= e($docCategory) ?> / <?= e(strtoupper($extension)) ?></span>
                    <h3><?= e($docTitle) ?></h3>
                    <?php if ($docDescription !== ''): ?><p><?= e($docDescription) ?></p><?php endif; ?>
                    <?php if ($docTags !== ''): ?><p class="help"><?= e((string) $t['tags']) ?>: <?= e($docTags) ?></p><?php endif; ?>
                    <?php if ($docExtract !== ''): ?><p class="help"><?= e(mb_safe_strimwidth($docExtract, 0, 220, '...')) ?></p><?php endif; ?>
                    <?php if ($extension === 'pdf'): ?>
                        <details class="admin-library-preview-toggle">
                            <summary><?= e((string) $t['preview']) ?></summary>
                            <iframe src="<?= e(base_url($safePath)) ?>" class="admin-library-pdf-preview" loading="lazy"></iframe>
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
                    <p class="actions">
                        <a class="button secondary" href="<?= e(base_url($safePath)) ?>" target="_blank" rel="noopener"><?= e((string) $t['open']) ?></a>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="toggle_favorite_document">
                            <input type="hidden" name="document_id" value="<?= (int) ($document['id'] ?? 0) ?>">
                            <button class="button secondary" type="submit"><?= $isFavorite ? '&#9733; ' . e((string) $t['favorite']) : '&#9734; ' . e((string) $t['favorite']) ?></button>
                        </form>
                    </p>
                </article>
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

