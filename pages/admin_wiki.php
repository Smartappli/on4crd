<?php
declare(strict_types=1);

require_permission('wiki.moderate');

$locale = current_locale();
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/admin_wiki.php');
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};
$tr = static function (string $key, string $fallback) use ($t): string {
    $value = trim($t($key));

    return $value !== '' && $value !== $key ? $value : $fallback;
};

$statusLabels = [
    'pending' => $tr('status_pending', 'En validation'),
    'published' => $tr('status_published', 'Publie'),
    'rejected' => $tr('status_rejected', 'Refuse'),
];
$statusFilter = trim((string) ($_GET['status'] ?? ''));
if (!isset($statusLabels[$statusFilter])) {
    $statusFilter = '';
}

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
        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        if ($id <= 0 || !isset($statusLabels[$status])) {
            throw new RuntimeException($tr('invalid_page', 'Page wiki invalide.'));
        }

        $pageStmt = db()->prepare('SELECT id, title, slug, content, author_id, status, proposal_kind, source_page_id, target_slug FROM wiki_pages WHERE id = ? LIMIT 1');
        $pageStmt->execute([$id]);
        $page = $pageStmt->fetch();
        if (!is_array($page)) {
            throw new RuntimeException($tr('page_not_found', 'Page wiki introuvable.'));
        }

        if ($status === 'published' && (string) ($page['proposal_kind'] ?? 'page') === 'modification') {
            $sourceId = (int) ($page['source_page_id'] ?? 0);
            $sourceStmt = db()->prepare('SELECT id, title, slug, content FROM wiki_pages WHERE id = ? AND proposal_kind = "page" LIMIT 1');
            $sourceStmt->execute([$sourceId]);
            $sourcePage = $sourceStmt->fetch();
            if (!is_array($sourcePage)) {
                throw new RuntimeException($tr('source_page_not_found', 'Page source introuvable.'));
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
                $pdo->prepare('UPDATE wiki_pages SET title = ?, slug = ?, content = ?, author_id = ?, status = "published", proposal_kind = "page", source_page_id = NULL, target_slug = NULL, updated_at = NOW() WHERE id = ?')
                    ->execute([(string) $page['title'], $targetSlug, (string) $page['content'], $authorId, $sourceId]);
                $pdo->prepare('DELETE FROM wiki_pages WHERE id = ?')->execute([$id]);
                $pdo->commit();
            } catch (Throwable $throwable) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $throwable;
            }

            set_flash('success', $tr('modification_applied', 'Modification wiki appliquee.'));
        } else {
            db()->prepare('UPDATE wiki_pages SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$status, $id]);
            set_flash('success', $tr('status_saved', 'Statut wiki enregistre.'));
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
    'SELECT p.id, p.title, p.slug, p.status, p.updated_at, p.proposal_kind, p.source_page_id, p.target_slug,
        s.title AS source_title, s.slug AS source_slug
     FROM wiki_pages p
     LEFT JOIN wiki_pages s ON s.id = p.source_page_id
     ' . $whereSql . '
     ORDER BY p.status ASC, p.updated_at DESC'
);
$stmt->execute($params);
$pages = $stmt->fetchAll() ?: [];

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
        <select name="status" aria-label="<?= e($tr('status_filter', 'Filtrer par statut')) ?>">
            <option value=""><?= e($tr('all_statuses', 'Tous les statuts')) ?></option>
            <?php foreach ($statusLabels as $statusCode => $statusLabel): ?>
                <option value="<?= e($statusCode) ?>" <?= $statusFilter === $statusCode ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button secondary small" type="submit"><?= e($tr('filter', 'Filtrer')) ?></button>
        <?php if ($statusFilter !== ''): ?>
            <a class="button secondary small" href="<?= e(route_url_clean('admin_wiki')) ?>"><?= e($tr('clear_filter', 'Tout afficher')) ?></a>
        <?php endif; ?>
    </form>
    <div class="table-wrap">
        <table>
            <thead><tr><th><?= e($t('th_title')) ?></th><th><?= e($t('th_slug')) ?></th><th><?= e($tr('th_status', 'Statut')) ?></th><th><?= e($t('th_updated')) ?></th><th><?= e($t('th_action')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($pages as $page):
                $pageStatus = (string) ($page['status'] ?? 'published');
                $proposalKind = (string) ($page['proposal_kind'] ?? 'page');
                $isModificationProposal = $proposalKind === 'modification';
                $sourceLabel = trim((string) ($page['source_title'] ?? ''));
                if ($sourceLabel === '') {
                    $sourceLabel = trim((string) ($page['source_slug'] ?? ''));
                }
                ?>
                <tr>
                    <td>
                        <?= e((string) $page['title']) ?>
                        <?php if ($isModificationProposal): ?>
                            <div class="help">
                                <span class="badge muted"><?= e($tr('type_modification', 'Modification')) ?></span>
                                <?php if ($sourceLabel !== ''): ?> <?= e($tr('source_page', 'Source')) ?>: <?= e($sourceLabel) ?><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><code><?= e((string) $page['slug']) ?></code></td>
                    <td><span class="badge muted"><?= e((string) ($statusLabels[$pageStatus] ?? $pageStatus)) ?></span></td>
                    <td><?= e((string) $page['updated_at']) ?></td>
                    <td>
                        <?php if ($isModificationProposal): ?>
                            <a href="<?= e(route_url('wiki_view', ['slug' => (string) $page['slug']])) ?>"><?= e($tr('view', 'Voir')) ?></a>
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
            <?php if ($pages === []): ?><tr><td colspan="5"><?= e($t('empty')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
echo render_layout((string) ob_get_clean(), $t('layout'));
