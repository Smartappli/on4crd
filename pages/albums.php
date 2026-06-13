<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('albums', $locale);
set_page_meta(['title' => (string) $t['public_albums'], 'description' => (string) $t['meta_desc']]);
$user = current_user();
$canManageAlbums = has_permission('albums.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
    $action = (string) ($_POST['action'] ?? '');
    $user = require_login(route_url('albums'));
    verify_csrf();

    if ($action === 'toggle_favorite_album') {
        $albumId = (int) ($_POST['album_id'] ?? 0);
        if ($albumId > 0) {
            $favStmt = db()->prepare('SELECT id, title FROM albums WHERE id = ? AND is_public = 1 LIMIT 1');
            $favStmt->execute([$albumId]);
            $favRow = $favStmt->fetch() ?: null;
            if ($favRow !== null) {
                $favTitle = trim((string) ($favRow['title'] ?? 'Album'));
                $favUrl = route_url('album', ['id' => (int) $favRow['id']]);
                $saved = favorite_toggle((int) $user['id'], 'album', (int) $favRow['id'], $favTitle, $favUrl);
                notify_member((int) $user['id'], 'favorite', $saved ? 'Favorite added' : 'Favorite removed', $favTitle, $favUrl);
                set_flash('success', $saved ? 'Album added to favorites.' : 'Album removed from favorites.');
            }
        }
        redirect_url(route_url_clean('albums', ['q' => (string) ($_GET['q'] ?? ''), 'p' => max(1, (int) ($_GET['p'] ?? 1))]));
    }

    if ($action === 'propose_album') {
        if (!table_exists('albums')) {
            throw new RuntimeException((string) $t['gallery_unavailable']);
        }
        $proposalTitle = (string) ($_POST['proposal_title'] ?? '');
        $proposalDescription = (string) ($_POST['proposal_description'] ?? '');
        $proposalContact = (string) ($_POST['proposal_contact'] ?? '');
        $title = content_proposal_clean_single_line($proposalTitle, 190);
        $description = content_proposal_clean_multiline($proposalDescription, 5000);
        $contact = content_proposal_clean_single_line($proposalContact, 220);
        if ($title === '') {
            throw new RuntimeException('Demande invalide.');
        }
        if (has_permission('albums.manage')) {
            db()->prepare('INSERT INTO albums (title, description, is_public) VALUES (?, ?, 1)')
                ->execute([$title, $description !== '' ? $description : null]);
            $albumId = (int) db()->lastInsertId();
            cache_forget('admin_albums_list_v2');
            cache_forget('admin_albums_photos_total_v2');
            cache_forget('home_public_album_random_photos_v1');
            set_flash('success', 'Album cree et valide directement.');
            redirect_url(route_url('album', ['id' => $albumId]));
        }
        $summary = content_proposal_details_text([
            'Description' => $description,
        ]);
        $proposalId = content_proposal_create((int) $user['id'], 'albums', 'content', $title, $summary, $contact);
        content_proposal_notify_site('Proposition d album ON4CRD', [
            'area' => 'albums',
            'proposal_type' => 'content',
            'title' => content_proposal_clean_single_line($title, 190),
            'summary' => $summary,
            'contact' => $contact,
            'source_ref' => 'content_proposals#' . $proposalId,
        ]);
        set_flash('success', 'Proposition enregistree dans vos contenus.');
        redirect('my_requests');
    }

    throw new RuntimeException('Demande invalide.');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect_url(route_url('albums'));
    }
}

if (!table_exists('albums') || !table_exists('album_photos')) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['public_albums']) . '</h1><p>' . e((string) $t['gallery_unavailable']) . '</p></div>', (string) $t['albums']);
    return;
}

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 100) {
    $search = mb_substr($search, 0, 100);
}
$page = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 12;

$params = [];
$where = 'a.is_public = 1';
if ($search !== '') {
    $where .= ' AND (a.title LIKE ? OR a.description LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}

$countStmt = db()->prepare('SELECT COUNT(*) FROM albums a WHERE ' . $where);
$countStmt->execute($params);
$totalAlbums = (int) $countStmt->fetchColumn();
$pagination = pagination_state($totalAlbums, $page, $perPage);
$page = $pagination['page'];
$totalPages = $pagination['total_pages'];
$offset = $pagination['offset'];

$stmt = db()->prepare(
    'SELECT a.*,
        (SELECT COUNT(*) FROM album_photos p WHERE p.album_id = a.id) AS photo_count,
        (SELECT p.file_path FROM album_photos p WHERE p.album_id = a.id ORDER BY p.sort_order ASC, p.id ASC LIMIT 1) AS cover_path
     FROM albums a
     WHERE ' . $where . '
     ORDER BY a.id DESC
     LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset
);
$stmt->execute($params);
$rows = $stmt->fetchAll() ?: [];

$photoTotalStmt = db()->prepare(
    'SELECT COUNT(*)
     FROM album_photos p
     INNER JOIN albums a ON a.id = p.album_id
     WHERE ' . $where
);
$photoTotalStmt->execute($params);
$photoTotal = (int) $photoTotalStmt->fetchColumn();
$latestAlbumDate = trim((string) (db()->query(
    'SELECT MAX(latest_at) FROM (
        SELECT a.created_at AS latest_at FROM albums a WHERE a.is_public = 1
        UNION ALL
        SELECT p.created_at AS latest_at
        FROM album_photos p
        INNER JOIN albums a ON a.id = p.album_id
        WHERE a.is_public = 1
    ) latest_album_content'
)->fetchColumn() ?: ''));
$latestAlbumLabel = module_hero_latest_stat_date_label($latestAlbumDate, $locale);
$proposalContactDefault = '';
if ($user !== null) {
    $proposalContactDefault = trim((string) ($user['email'] ?? ''));
    if ($proposalContactDefault === '') {
        $proposalContactDefault = trim((string) ($user['callsign'] ?? ''));
    }
}
$showAlbumProposalForm = $user !== null && (string) ($_GET['propose_album'] ?? '') === '1';
$albumProposalUrl = $user !== null ? route_url('albums', ['propose_album' => '1']) : route_url('login', ['next' => route_url('albums')]);

ob_start();
?>
<div class="albums-page">
    <section class="albums-hero">
        <div>
            <p class="eyebrow"><?= e((string) $t['gallery']) ?></p>
            <h1 class="albums-hero-title"><?= e((string) $t['public_albums']) ?></h1>
            <p><?= e((string) $t['intro']) ?></p>
        </div>
        <div class="albums-hero-side">
            <div class="albums-hero-stats">
                <article>
                    <span><?= e((string) $t['albums']) ?></span>
                    <strong><?= (int) $totalAlbums ?></strong>
                </article>
                <article>
                    <span><?= e((string) $t['indexed_photos']) ?></span>
                    <strong><?= (int) $photoTotal ?></strong>
                </article>
                <article>
                    <span><?= e(module_hero_latest_stat_text('latest', $locale)) ?></span>
                    <strong><?= e($latestAlbumLabel) ?></strong>
                </article>
            </div>
            <p class="actions albums-hero-actions">
                <a class="button" href="<?= e($albumProposalUrl) ?>"><?= e($canManageAlbums ? 'Créer un album' : 'Proposer un album') ?></a>
            </p>
        </div>
    </section>

    <?php if ($showAlbumProposalForm): ?>
    <section class="card">
        <h2><?= e($canManageAlbums ? 'Créer un album' : 'Proposer un album') ?></h2>
        <p class="help"><?= e($canManageAlbums ? 'L album sera public directement.' : 'Votre proposition sera envoyee en validation et visible dans Mes contenus.') ?></p>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="propose_album">
            <label><span>Titre</span><input type="text" name="proposal_title" maxlength="190" required></label>
            <label><span>Description</span><textarea name="proposal_description" rows="5" maxlength="5000"></textarea></label>
            <label><span>Contact</span><input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required></label>
            <p class="actions">
                <button class="button" type="submit"><?= e($canManageAlbums ? 'Créer' : 'Envoyer la proposition') ?></button>
                <a class="button secondary" href="<?= e(route_url('albums')) ?>">Annuler</a>
            </p>
        </form>
    </section>
    <?php endif; ?>

    <section class="albums-toolbar">
        <form method="get" class="albums-search-form">
            <input type="hidden" name="route" value="albums">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_placeholder']) ?>">
            <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
            <?php if ($search !== ''): ?>
                <a class="button secondary" href="<?= e(route_url('albums')) ?>"><?= e((string) $t['reset']) ?></a>
            <?php endif; ?>
        </form>
    </section>

    <section class="albums-gallery">
        <?php if ($rows === []): ?>
            <article class="albums-empty">
                <h2><?= e((string) $t['none']) ?></h2>
                <p class="help"><?= $search !== '' ? e((string) $t['for_search']) : e((string) $t['intro']) ?></p>
            </article>
        <?php else: ?>
            <div class="albums-grid">
                <?php foreach ($rows as $row):
                    $coverPath = safe_storage_public_path_or_null((string) ($row['cover_path'] ?? ''), ['storage/uploads/albums/']);
                    $coverThumb = $coverPath !== null ? album_thumbnail_public_path($coverPath) : '';
                    $coverThumbAbs = $coverThumb !== '' ? dirname(__DIR__) . '/' . $coverThumb : '';
                    $coverSrc = $coverThumb !== '' && is_file($coverThumbAbs) ? $coverThumb : ($coverPath ?? '');
                    $photoCount = (int) ($row['photo_count'] ?? 0);
                    $description = trim((string) ($row['description'] ?? ''));
                    ?>
                    <article class="album-tile">
                        <a class="album-tile-media" href="<?= e(route_url('album', ['id' => (int) $row['id']])) ?>">
                            <?php if ($coverSrc !== ''): ?>
                                <img src="<?= e(base_url($coverSrc)) ?>" alt="<?= e((string) $t['cover_alt']) ?> <?= e((string) $row['title']) ?>" loading="lazy" decoding="async">
                            <?php else: ?>
                                <span class="album-placeholder-mark" aria-hidden="true"></span>
                            <?php endif; ?>
                        </a>
                        <div class="album-tile-body">
                            <div>
                                <h2><a href="<?= e(route_url('album', ['id' => (int) $row['id']])) ?>"><?= e((string) $row['title']) ?></a></h2>
                                <?php if ($description !== ''): ?>
                                    <p><?= e(mb_safe_strimwidth($description, 0, 150, '...')) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="album-tile-footer">
                                <span class="badge muted"><?= $photoCount ?> <?= e((string) ($photoCount > 1 ? $t['photos'] : $t['photo'])) ?></span>
                                <?php if ($user !== null): ?>
                                    <?php $isFavorite = favorite_is_saved((int) $user['id'], 'album', (int) ($row['id'] ?? 0)); ?>
                                    <form method="post" class="album-favorite-form">
                                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="toggle_favorite_album">
                                        <input type="hidden" name="album_id" value="<?= (int) ($row['id'] ?? 0) ?>">
                                        <button class="button secondary" type="submit"><?= $isFavorite ? '&#9733;' : '&#9734;' ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if ($totalPages > 1): ?>
                <nav class="actions mt-3" aria-label="<?= e((string) $t['pagination']) ?>">
                    <?php if ($page > 1): ?>
                        <a class="button secondary" href="<?= e(route_url_clean('albums', ['q' => $search, 'p' => $page - 1])) ?>"><?= e((string) $t['previous']) ?></a>
                    <?php endif; ?>
                    <span class="pill"><?= e((string) $t['page']) ?> <?= $page ?> / <?= $totalPages ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a class="button secondary" href="<?= e(route_url_clean('albums', ['q' => $search, 'p' => $page + 1])) ?>"><?= e((string) $t['next']) ?></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['albums']);
