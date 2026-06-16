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
$wikiMessages = i18n_domain_locale('wiki', $locale);
$wikiCategories = wiki_categories($wikiMessages);
$wikiThemeLabel = (string) ($wikiMessages['themes'] ?? 'Themes');

$statusLabels = [
    'pending' => $tr('status_pending', 'En validation'),
    'published' => $tr('status_published', 'Publie'),
    'rejected' => $tr('status_rejected', 'Refuse'),
];
$proposalStatusLabels = [
    'pending' => $tr('proposal_status_pending', 'En attente'),
    'reviewed' => $tr('proposal_status_reviewed', 'Relue'),
    'accepted' => $tr('proposal_status_accepted', 'Acceptee'),
    'rejected' => $tr('proposal_status_rejected', 'Refusee'),
];
$proposalTypeLabels = [
    'category' => $tr('proposal_type_category', 'Thematique'),
    'content' => $tr('proposal_type_content', 'Contenu'),
    'domain' => $tr('proposal_type_domain', 'Domaine'),
    'tag' => $tr('proposal_type_tag', 'Mot cle'),
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

        if ($action === 'update_proposal_status') {
            $proposalId = (int) ($_POST['proposal_id'] ?? 0);
            $proposalStatus = (string) ($_POST['proposal_status'] ?? 'pending');
            $moderationNote = trim((string) ($_POST['moderation_note'] ?? ''));
            if ($proposalId <= 0 || !isset($proposalStatusLabels[$proposalStatus])) {
                throw new RuntimeException($tr('invalid_proposal', 'Proposition wiki invalide.'));
            }
            if (!ensure_content_proposals_table()) {
                throw new RuntimeException($tr('proposal_storage_unavailable', 'Stockage des propositions indisponible.'));
            }

            if ($proposalStatus === 'accepted') {
                $proposalStmt = db()->prepare('SELECT id, summary FROM content_proposals WHERE id = ? AND area = "wiki" LIMIT 1');
                $proposalStmt->execute([$proposalId]);
                $proposal = $proposalStmt->fetch() ?: null;
                if (!is_array($proposal)) {
                    throw new RuntimeException($tr('invalid_proposal', 'Proposition wiki invalide.'));
                }
                if (wiki_content_proposal_action((string) ($proposal['summary'] ?? '')) === 'delete_page') {
                    wiki_delete_page_record(wiki_content_proposal_page_id((string) ($proposal['summary'] ?? '')));
                }
            }

            db()->prepare('UPDATE content_proposals SET status = ?, moderation_note = ? WHERE id = ? AND area = "wiki"')
                ->execute([$proposalStatus, $moderationNote !== '' ? $moderationNote : null, $proposalId]);
            set_flash('success', $tr('proposal_status_saved', 'Proposition wiki mise a jour.'));
            redirect_url($pendingProposalUrl);
        }

        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        if ($id <= 0 || !isset($statusLabels[$status])) {
            throw new RuntimeException($tr('invalid_page', 'Page wiki invalide.'));
        }

        $pageStmt = db()->prepare('SELECT id, title, slug, content, category, author_id, status, proposal_kind, source_page_id, target_slug FROM wiki_pages WHERE id = ? LIMIT 1');
        $pageStmt->execute([$id]);
        $page = $pageStmt->fetch();
        if (!is_array($page)) {
            throw new RuntimeException($tr('page_not_found', 'Page wiki introuvable.'));
        }

        if ($status === 'published' && (string) ($page['proposal_kind'] ?? 'page') === 'modification') {
            $sourceId = (int) ($page['source_page_id'] ?? 0);
            $sourceStmt = db()->prepare('SELECT id, title, slug, content, category FROM wiki_pages WHERE id = ? AND proposal_kind = "page" LIMIT 1');
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
                $pdo->prepare('UPDATE wiki_pages SET title = ?, slug = ?, content = ?, category = ?, author_id = ?, status = "published", proposal_kind = "page", source_page_id = NULL, target_slug = NULL, updated_at = NOW() WHERE id = ?')
                    ->execute([(string) $page['title'], $targetSlug, (string) $page['content'], wiki_category_code((string) ($page['category'] ?? 'general')), $authorId, $sourceId]);
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
    'SELECT p.id, p.title, p.slug, p.category, p.status, p.updated_at, p.proposal_kind, p.source_page_id, p.target_slug,
        s.title AS source_title, s.slug AS source_slug
     FROM wiki_pages p
     LEFT JOIN wiki_pages s ON s.id = p.source_page_id
     ' . $whereSql . '
     ORDER BY p.status ASC, p.updated_at DESC'
);
$stmt->execute($params);
$pages = $stmt->fetchAll() ?: [];

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
            <thead><tr><th><?= e($t('th_title')) ?></th><th><?= e($t('th_slug')) ?></th><th><?= e($wikiThemeLabel) ?></th><th><?= e($tr('th_status', 'Statut')) ?></th><th><?= e($t('th_updated')) ?></th><th><?= e($t('th_action')) ?></th></tr></thead>
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
                    <td><span class="badge muted"><?= e($pageCategoryLabel) ?></span></td>
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
            <?php if ($pages === []): ?><tr><td colspan="6"><?= e($t('empty')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php if ($showPendingProposals): ?>
<section class="card" id="pending-proposals" aria-labelledby="pending-proposals-title">
    <div class="row-between">
        <h2 id="pending-proposals-title"><?= e($tr('pending_proposals_title', 'Contenus wiki en attente de validation')) ?></h2>
        <a class="button secondary small" href="<?= e(route_url_clean('admin_wiki')) ?>"><?= e($tr('clear_filter', 'Tout afficher')) ?></a>
    </div>
    <?php if ($pendingProposals === []): ?>
        <p class="help"><?= e($tr('pending_proposals_empty', 'Aucune proposition wiki en attente de validation.')) ?></p>
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
                <h3><?= e((string) ($proposal['title'] ?? $tr('proposal_default_title', 'Proposition'))) ?></h3>
                <p class="help"><?= e($tr('proposal_author', 'Propose par')) ?>: <?= e($memberLabel) ?></p>
                <?php if (trim((string) ($proposal['summary'] ?? '')) !== ''): ?>
                    <p><?= nl2br(e((string) $proposal['summary'])) ?></p>
                <?php endif; ?>
                <?php if (trim((string) ($proposal['contact'] ?? '')) !== ''): ?>
                    <p class="help"><?= e($tr('proposal_contact', 'Contact')) ?>: <?= e((string) $proposal['contact']) ?></p>
                <?php endif; ?>
                <form method="post" class="stack">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_proposal_status">
                    <input type="hidden" name="proposal_id" value="<?= (int) ($proposal['id'] ?? 0) ?>">
                    <input type="hidden" name="return_status" value="pending">
                    <div class="grid-2">
                        <label><?= e($tr('proposal_status_label', 'Statut')) ?>
                            <select name="proposal_status">
                                <?php foreach ($proposalStatusLabels as $statusCode => $statusLabel): ?>
                                    <option value="<?= e($statusCode) ?>" <?= $proposalStatus === $statusCode ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label><?= e($tr('proposal_moderation_note', 'Note de moderation')) ?>
                            <textarea name="moderation_note" rows="3"><?= e((string) ($proposal['moderation_note'] ?? '')) ?></textarea>
                        </label>
                    </div>
                    <p><button class="button small" type="submit"><?= e($tr('proposal_save_status', 'Enregistrer le statut')) ?></button></p>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<?php
echo render_layout((string) ob_get_clean(), $t('layout'));
