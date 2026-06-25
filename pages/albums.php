<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('albums', $locale);
set_page_meta(['title' => (string) $t['public_albums'], 'description' => (string) $t['meta_desc']]);
$user = current_user();
$canManageAlbums = has_permission('albums.manage');

if (!function_exists('albums_page_post_checkbox')) {
function albums_page_post_checkbox(string $key, ?int $default = null, string ...$fallbackKeys): ?int
{
    foreach (array_merge([$key], $fallbackKeys) as $candidateKey) {
        if (!array_key_exists($candidateKey, $_POST)) {
            continue;
        }

        $values = is_array($_POST[$candidateKey]) ? $_POST[$candidateKey] : [$_POST[$candidateKey]];
        foreach ($values as $singleValue) {
            if (!is_scalar($singleValue)) {
                continue;
            }
            if (in_array(strtolower(trim((string) $singleValue)), ['1', 'on', 'true', 'yes'], true)) {
                return 1;
            }
        }

        return 0;
    }

    return $default;
}
}

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
                $favTitle = trim((string) ($favRow['title'] ?? $t['albums']));
                $favUrl = route_url('album', ['id' => (int) $favRow['id']]);
                $saved = favorite_toggle((int) $user['id'], 'album', (int) $favRow['id'], $favTitle, $favUrl);
                notify_member((int) $user['id'], 'favorite', $saved ? (string) $t['favorite_added'] : (string) $t['favorite_removed'], $favTitle, $favUrl);
                set_flash('success', $saved ? (string) $t['favorite_added_msg'] : (string) $t['favorite_removed_msg']);
            }
        }
        redirect_url(route_url_clean('albums', [
            'category' => (string) ($_POST['return_category'] ?? $_GET['category'] ?? ''),
            'subcategory' => (string) ($_POST['return_subcategory'] ?? $_GET['subcategory'] ?? ''),
            'favorites' => (string) ($_POST['return_favorites'] ?? $_GET['favorites'] ?? '') === '1' ? '1' : '',
            'q' => (string) ($_POST['return_q'] ?? $_GET['q'] ?? ''),
            'p' => max(1, (int) ($_POST['return_p'] ?? $_GET['p'] ?? 1)),
        ]));
    }

    if ($action === 'update_album' || $action === 'delete_album') {
        if (!album_ensure_source_proposal_column()) {
            throw new RuntimeException((string) $t['gallery_unavailable']);
        }
        $albumId = (int) ($_POST['album_id'] ?? 0);
        $albumStmt = db()->prepare('SELECT * FROM albums WHERE id = ? LIMIT 1');
        $albumStmt->execute([$albumId]);
        $album = $albumStmt->fetch() ?: null;
        if (!is_array($album)) {
            throw new RuntimeException((string) $t['invalid_album']);
        }
        if (!$canManageAlbums && (int) ($album['member_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            throw new RuntimeException((string) $t['album_forbidden']);
        }

        $title = content_proposal_clean_single_line((string) ($_POST['title'] ?? $album['title'] ?? ''), 190);
        $description = content_proposal_clean_multiline((string) ($_POST['description'] ?? $album['description'] ?? ''), 10000);
        $albumCategoriesForPost = album_categories();
        $category = album_category_from_input((string) ($_POST['category'] ?? $album['category'] ?? 'general'), $albumCategoriesForPost);
        $subcategory = album_subcategory_code((string) ($album['subcategory'] ?? ''));
        if (array_key_exists('subcategory_ref', $_POST)) {
            [$category, $subcategory] = album_taxonomy_from_input(
                (string) ($_POST['category'] ?? $album['category'] ?? 'general'),
                trim((string) ($_POST['subcategory_ref'] ?? '')),
                $albumCategoriesForPost,
                (string) ($album['category'] ?? 'general')
            );
        }
        if ($title === '') {
            throw new RuntimeException((string) $t['title_required']);
        }

        $returnUrl = route_url_clean('albums', [
            'category' => (string) ($_POST['return_category'] ?? $_GET['category'] ?? ''),
            'subcategory' => (string) ($_POST['return_subcategory'] ?? $_GET['subcategory'] ?? ''),
            'favorites' => (string) ($_POST['return_favorites'] ?? $_GET['favorites'] ?? '') === '1' ? '1' : '',
            'q' => (string) ($_POST['return_q'] ?? $_GET['q'] ?? ''),
            'p' => max(1, (int) ($_POST['return_p'] ?? $_GET['p'] ?? 1)),
        ]);

        if ($action === 'delete_album') {
            if ($canManageAlbums) {
                album_delete_record($albumId);
                set_flash('success', (string) $t['album_deleted_ok']);
                redirect_url($returnUrl);
            }

            $summary = content_proposal_details_text([
                'Action' => 'delete_album',
                'Album ID' => (string) $albumId,
                (string) $t['category_field'] => (string) ($album['category'] ?? 'general'),
                (string) $t['subcategory_field'] => (string) ($album['subcategory'] ?? ''),
                (string) $t['description_label'] => mb_safe_substr((string) ($album['description'] ?? ''), 0, 5000),
            ]);
            $sourceRef = route_url('album', ['id' => $albumId]);
            $proposalId = content_proposal_create((int) $user['id'], 'albums', 'content', $title, $summary, (string) ($user['email'] ?? ''), $sourceRef, 'pending');
            content_proposal_notify_site((string) $t['album_change_subject'], [
                'area' => 'albums',
                'proposal_type' => 'content',
                'title' => $title,
                'summary' => $summary,
                'contact' => (string) ($user['email'] ?? ''),
                'source_ref' => 'content_proposals#' . $proposalId . ' ' . $sourceRef,
            ]);
            set_flash('success', (string) $t['album_change_recorded']);
            redirect('my_requests');
        }

        if ($canManageAlbums) {
            $isFeatured = array_key_exists('album_is_featured_present', $_POST)
                ? albums_page_post_checkbox('album_is_featured', 0)
                : albums_page_post_checkbox('album_is_featured', null, 'is_featured');
            album_update_record($albumId, $title, $description, null, $category, $subcategory, $isFeatured);
            set_flash('success', (string) $t['album_updated_ok']);
            redirect_url($returnUrl);
        }

        $summary = content_proposal_details_text([
            'Action' => 'update_album',
            'Album ID' => (string) $albumId,
            (string) $t['category_field'] => $category,
            (string) $t['subcategory_field'] => $subcategory,
            (string) $t['description_label'] => $description,
        ]);
        $sourceRef = route_url('album', ['id' => $albumId]);
        $proposalId = content_proposal_create((int) $user['id'], 'albums', 'content', $title, $summary, (string) ($user['email'] ?? ''), $sourceRef, 'pending');
        content_proposal_notify_site((string) $t['album_change_subject'], [
            'area' => 'albums',
            'proposal_type' => 'content',
            'title' => $title,
            'summary' => $summary,
            'contact' => (string) ($user['email'] ?? ''),
            'source_ref' => 'content_proposals#' . $proposalId . ' ' . $sourceRef,
        ]);
        set_flash('success', (string) $t['album_change_recorded']);
        redirect('my_requests');
    }

    if ($action === 'propose_album') {
        if (!table_exists('albums') || !table_exists('album_photos')) {
            throw new RuntimeException((string) $t['gallery_unavailable']);
        }
        if (!album_ensure_photo_sort_order_column() || !album_ensure_source_proposal_column()) {
            throw new RuntimeException((string) $t['gallery_unavailable']);
        }
        $proposalTitle = (string) ($_POST['proposal_title'] ?? '');
        $proposalDescription = (string) ($_POST['proposal_description'] ?? '');
        $proposalTheme = (string) ($_POST['proposal_theme'] ?? 'general');
        $albumCategoriesForPost = album_categories();
        [$proposalCategory, $proposalSubcategory] = album_taxonomy_from_input(
            $proposalTheme,
            trim((string) ($_POST['proposal_subcategory_ref'] ?? '')),
            $albumCategoriesForPost
        );
        $proposalKeywords = (string) ($_POST['proposal_keywords'] ?? '');
        $proposalContact = (string) ($_POST['proposal_contact'] ?? '');
        $title = content_proposal_clean_single_line($proposalTitle, 190);
        $description = content_proposal_clean_multiline($proposalDescription, 5000);
        $theme = $proposalCategory;
        $keywords = content_proposal_clean_single_line($proposalKeywords, 255);
        $contact = content_proposal_clean_single_line($proposalContact, 220);
        if ($title === '') {
            throw new RuntimeException((string) $t['invalid_request']);
        }
        if (has_permission('albums.manage')) {
            $albumMetadata = ($theme !== '' && $theme !== 'general') || $proposalSubcategory !== '' || $keywords !== ''
                ? content_proposal_details_text([
                    (string) $t['category_field'] => $theme,
                    (string) $t['subcategory_field'] => $proposalSubcategory,
                    (string) $t['keywords_label'] => $keywords,
                ])
                : '';
            $albumDescription = trim($description . ($albumMetadata !== '' ? "\n\n" . $albumMetadata : ''));
            db()->prepare('INSERT INTO albums (member_id, category, subcategory, title, description, is_public) VALUES (?, ?, ?, ?, ?, 1)')
                ->execute([(int) $user['id'], $proposalCategory, $proposalSubcategory, $title, $albumDescription !== '' ? $albumDescription : null]);
            $albumId = (int) db()->lastInsertId();
            album_clear_caches();
            set_flash('success', (string) $t['album_created_direct_continue']);
            redirect_url(route_url('album', ['id' => $albumId]) . '#album-upload');
        }
        if (!ensure_content_proposals_table()) {
            throw new RuntimeException((string) $t['gallery_unavailable']);
        }
        $albumId = 0;
        $proposalId = 0;
        $summary = '';
        $sourceRef = '';
        db()->beginTransaction();
        try {
            db()->prepare('INSERT INTO albums (member_id, category, subcategory, title, description, is_public, source_proposal_id) VALUES (?, ?, ?, ?, ?, 0, NULL)')
                ->execute([(int) $user['id'], $proposalCategory, $proposalSubcategory, $title, null]);
            $albumId = (int) db()->lastInsertId();
            $sourceRef = route_url('album', ['id' => $albumId]);
            $summary = content_proposal_details_text([
                'Album ID' => (string) $albumId,
                (string) $t['category_field'] => $theme,
                (string) $t['subcategory_field'] => $proposalSubcategory,
                (string) $t['keywords_label'] => $keywords,
                (string) $t['description_label'] => $description,
            ]);
            $albumDescription = album_proposal_description_from_summary($summary);
            db()->prepare('UPDATE albums SET description = ? WHERE id = ?')->execute([$albumDescription, $albumId]);
            $proposalId = content_proposal_create((int) $user['id'], 'albums', 'content', $title, $summary, $contact, $sourceRef, 'pending');
            db()->prepare('UPDATE albums SET source_proposal_id = ? WHERE id = ?')->execute([$proposalId, $albumId]);
            db()->commit();
        } catch (Throwable $throwable) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            throw $throwable;
        }
        album_clear_caches();
        content_proposal_notify_site((string) $t['propose_album_title'], [
            'area' => 'albums',
            'proposal_type' => 'content',
            'title' => content_proposal_clean_single_line($title, 190),
            'summary' => $summary,
            'contact' => $contact,
            'source_ref' => 'content_proposals#' . $proposalId . ' ' . $sourceRef,
        ]);
        set_flash('success', (string) $t['proposal_album_recorded_continue']);
        redirect_url($sourceRef . '#album-upload');
    }

    if ($action === 'propose_category') {
        $proposalTitle = content_proposal_clean_single_line((string) ($_POST['proposal_category_name'] ?? ''), 160);
        $proposalContact = content_proposal_clean_single_line((string) ($_POST['proposal_contact'] ?? ($user['email'] ?? '')), 220);
        if ($proposalContact === '') {
            $proposalContact = content_proposal_clean_single_line((string) ($user['callsign'] ?? ''), 220);
        }
        if ($proposalTitle === '') {
            throw new RuntimeException((string) $t['invalid_request']);
        }
        $summary = content_proposal_details_text([
            (string) $t['description_label'] => (string) ($_POST['proposal_reason'] ?? ''),
        ]);
        if ($canManageAlbums) {
            if (!album_ensure_categories_table()) {
                throw new RuntimeException((string) $t['gallery_unavailable']);
            }
            $code = album_category_code($proposalTitle);
            db()->prepare('INSERT INTO album_categories (code, label, deleted_at) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
                ->execute([$code, $proposalTitle]);
            album_clear_caches();
            set_flash('success', (string) $t['category_created_direct']);
            redirect_url(route_url_clean('albums', ['category' => $code]));
        }
        $proposalId = content_proposal_create((int) $user['id'], 'albums', 'category', $proposalTitle, $summary, $proposalContact);
        content_proposal_notify_site((string) $t['propose_category_title'], [
            'area' => 'albums',
            'proposal_type' => 'category',
            'title' => $proposalTitle,
            'summary' => $summary,
            'contact' => $proposalContact,
            'source_ref' => 'content_proposals#' . $proposalId,
        ]);
        set_flash('success', (string) $t['proposal_recorded']);
        redirect('my_requests');
    }

    if ($action === 'propose_subcategory') {
        $proposalTitle = content_proposal_clean_single_line((string) ($_POST['proposal_subcategory_name'] ?? ''), 160);
        $albumCategoriesForPost = album_categories();
        $parentCategory = album_category_from_input((string) ($_POST['proposal_parent_category'] ?? 'general'), $albumCategoriesForPost);
        $proposalContact = content_proposal_clean_single_line((string) ($_POST['proposal_contact'] ?? ($user['email'] ?? '')), 220);
        if ($proposalContact === '') {
            $proposalContact = content_proposal_clean_single_line((string) ($user['callsign'] ?? ''), 220);
        }
        if ($proposalTitle === '') {
            throw new RuntimeException((string) $t['invalid_request']);
        }
        $summary = content_proposal_details_text([
            (string) $t['category_field'] => $parentCategory,
            (string) $t['subcategory_field'] => $proposalTitle,
            (string) $t['description_label'] => (string) ($_POST['proposal_reason'] ?? ''),
        ]);
        if ($canManageAlbums) {
            if (!album_ensure_subcategories_table()) {
                throw new RuntimeException((string) $t['gallery_unavailable']);
            }
            $code = album_subcategory_code($proposalTitle);
            db()->prepare('INSERT INTO album_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                ->execute([$parentCategory, $code, $proposalTitle]);
            album_clear_caches();
            set_flash('success', (string) $t['subcategory_created_direct']);
            redirect_url(route_url_clean('albums', ['category' => $parentCategory, 'subcategory' => $code]));
        }
        $proposalId = content_proposal_create((int) $user['id'], 'albums', 'subcategory', $proposalTitle, $summary, $proposalContact);
        content_proposal_notify_site((string) $t['propose_subcategory_title'], [
            'area' => 'albums',
            'proposal_type' => 'subcategory',
            'title' => $proposalTitle,
            'summary' => $summary,
            'contact' => $proposalContact,
            'source_ref' => 'content_proposals#' . $proposalId,
        ]);
        set_flash('success', (string) $t['proposal_recorded']);
        redirect('my_requests');
    }

    throw new RuntimeException((string) $t['invalid_request']);
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect_url(route_url('albums'));
    }
}

if (!table_exists('albums') || !table_exists('album_photos')) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['public_albums']) . '</h1><p>' . e((string) $t['gallery_unavailable']) . '</p></div>', (string) $t['albums']);
    return;
}
if (!album_ensure_photo_sort_order_column() || !album_ensure_schema_columns_and_indexes() || !album_ensure_source_proposal_column()) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['public_albums']) . '</h1><p>' . e((string) $t['gallery_unavailable']) . '</p></div>', (string) $t['albums']);
    return;
}
album_sync_accepted_proposals();
$albumCategories = album_categories();
$albumSubcategoriesByCategory = album_subcategories_by_category();

$albumCategoryCounts = [];
$albumSubcategoryCounts = [];
try {
    foreach (db()->query('SELECT category, COUNT(*) AS total FROM albums WHERE is_public = 1 GROUP BY category ORDER BY category ASC')->fetchAll() ?: [] as $categoryRow) {
        $code = album_category_code((string) ($categoryRow['category'] ?? 'general'));
        if ($code !== '') {
            $albumCategoryCounts[$code] = (int) ($categoryRow['total'] ?? 0);
        }
    }
    foreach (db()->query('SELECT category, subcategory, COUNT(*) AS total FROM albums WHERE is_public = 1 AND subcategory IS NOT NULL AND subcategory <> "" GROUP BY category, subcategory ORDER BY category ASC, subcategory ASC')->fetchAll() ?: [] as $subcategoryRow) {
        $categoryCode = album_category_code((string) ($subcategoryRow['category'] ?? 'general'));
        $subcategoryCode = album_subcategory_code((string) ($subcategoryRow['subcategory'] ?? ''));
        if ($categoryCode !== '' && $subcategoryCode !== '') {
            $albumSubcategoryCounts[$categoryCode . ':' . $subcategoryCode] = (int) ($subcategoryRow['total'] ?? 0);
        }
    }
} catch (Throwable) {
    $albumCategoryCounts = [];
    $albumSubcategoryCounts = [];
}
foreach ($albumSubcategoryCounts as $subcategoryKey => $subcategoryTotal) {
    $parts = explode(':', (string) $subcategoryKey, 2);
    if (count($parts) !== 2 || (int) $subcategoryTotal <= 0) {
        continue;
    }
    $parentCode = album_category_code($parts[0]);
    $subcategoryCode = album_subcategory_code($parts[1]);
    if ($parentCode === '' || $subcategoryCode === '') {
        continue;
    }
    $known = false;
    foreach ($albumSubcategoriesByCategory[$parentCode] ?? [] as $subcategoryOption) {
        if (album_subcategory_code((string) $subcategoryOption['code']) === $subcategoryCode) {
            $known = true;
            break;
        }
    }
    if (!$known) {
        $albumSubcategoriesByCategory[$parentCode][] = [
            'category_code' => $parentCode,
            'code' => $subcategoryCode,
            'label' => album_category_label_from_code($subcategoryCode),
        ];
    }
}
$visibleAlbumCategories = album_visible_categories($albumCategories, $albumCategoryCounts);
$visibleAlbumSubcategoriesByCategory = album_visible_subcategories_by_category($albumSubcategoriesByCategory, $albumSubcategoryCounts);

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 100) {
    $search = mb_substr($search, 0, 100);
}
$categoryFilter = '';
$categoryInput = trim((string) ($_GET['category'] ?? ''));
if ($categoryInput !== '') {
    $categoryCode = album_category_code($categoryInput);
    if (isset($albumCategories[$categoryCode])) {
        $categoryFilter = $categoryCode;
    }
}
$subcategoryFilter = '';
$subcategoryInput = trim((string) ($_GET['subcategory'] ?? ''));
if ($subcategoryInput !== '') {
    $subcategoryCode = album_subcategory_code($subcategoryInput);
    if ($subcategoryCode !== '') {
        $candidateCategory = $categoryFilter;
        if ($candidateCategory === '') {
            foreach ($visibleAlbumSubcategoriesByCategory as $parentCode => $subcategories) {
                foreach ($subcategories as $subcategoryInfo) {
                    if (album_subcategory_code((string) ($subcategoryInfo['code'] ?? '')) === $subcategoryCode) {
                        $candidateCategory = (string) $parentCode;
                        break 2;
                    }
                }
            }
        }
        if ($candidateCategory !== '' && (int) ($albumSubcategoryCounts[$candidateCategory . ':' . $subcategoryCode] ?? 0) > 0) {
            $categoryFilter = $candidateCategory;
            $subcategoryFilter = $subcategoryCode;
        }
    }
}
$favoriteAlbumIds = $user !== null ? album_favorite_album_ids((int) ($user['id'] ?? 0)) : [];
$favoriteAlbumCount = count($favoriteAlbumIds);
$favoritesOnly = (string) ($_GET['favorites'] ?? '') === '1' && $favoriteAlbumCount > 0;
$favoritesLabel = (string) $t['favorites'];
$page = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 12;

$params = [];
$where = 'a.is_public = 1';
if ($categoryFilter !== '') {
    $where .= ' AND a.category = ?';
    $params[] = $categoryFilter;
}
if ($subcategoryFilter !== '') {
    $where .= ' AND a.subcategory = ?';
    $params[] = $subcategoryFilter;
}
if ($favoritesOnly) {
    $where .= ' AND a.id IN (' . implode(',', array_fill(0, $favoriteAlbumCount, '?')) . ')';
    array_push($params, ...$favoriteAlbumIds);
}
if ($search !== '') {
    $where .= ' AND (a.title LIKE ? OR a.description LIKE ? OR a.category LIKE ? OR a.subcategory LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$countStmt = db()->prepare('SELECT COUNT(*) FROM albums a WHERE ' . $where);
$countStmt->execute($params);
$totalAlbums = (int) $countStmt->fetchColumn();
$regularWhere = $where . ' AND a.is_featured = 0';
$regularCountStmt = db()->prepare('SELECT COUNT(*) FROM albums a WHERE ' . $regularWhere);
$regularCountStmt->execute($params);
$regularAlbumsTotal = (int) $regularCountStmt->fetchColumn();
$pagination = pagination_state($regularAlbumsTotal, $page, $perPage);
$page = $pagination['page'];
$totalPages = $pagination['total_pages'];
$offset = $pagination['offset'];

$albumListSelect = 'SELECT a.*,
        (SELECT COUNT(*) FROM album_photos p WHERE p.album_id = a.id) AS photo_count,
        (SELECT p.file_path FROM album_photos p WHERE p.album_id = a.id ORDER BY p.sort_order ASC, p.id ASC LIMIT 1) AS cover_path
     FROM albums a
     WHERE ' . $where;

$featuredStmt = db()->prepare(
    $albumListSelect . '
       AND a.is_featured = 1
     ORDER BY a.created_at DESC, a.id DESC'
);
$featuredStmt->execute($params);
$featuredRows = $featuredStmt->fetchAll() ?: [];

$stmt = db()->prepare(
    'SELECT a.*,
        (SELECT COUNT(*) FROM album_photos p WHERE p.album_id = a.id) AS photo_count,
        (SELECT p.file_path FROM album_photos p WHERE p.album_id = a.id ORDER BY p.sort_order ASC, p.id ASC LIMIT 1) AS cover_path
     FROM albums a
     WHERE ' . $regularWhere . '
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
$showAlbumCategoryProposalForm = $user !== null && (string) ($_GET['propose_category'] ?? '') === '1';
$showAlbumSubcategoryProposalForm = $user !== null && (string) ($_GET['propose_subcategory'] ?? '') === '1';
$albumProposalUrl = $user !== null ? route_url('albums', ['propose_album' => '1']) : route_url('login', ['next' => route_url('albums', ['propose_album' => '1'])]);
$albumCategoryProposalUrl = $user !== null ? route_url('albums', ['propose_category' => '1']) : route_url('login', ['next' => route_url('albums', ['propose_category' => '1'])]);
$albumSubcategoryProposalUrl = $user !== null ? route_url('albums', ['propose_subcategory' => '1']) : route_url('login', ['next' => route_url('albums', ['propose_subcategory' => '1'])]);
$featuredAlbumsTitle = (string) $t['featured_albums'];
$featuredAlbumBadge = (string) $t['featured_album_badge'];
$otherAlbumsTitle = (string) $t['other_albums'];
$albumSections = album_listing_sections($featuredRows, $rows, $featuredAlbumsTitle, $otherAlbumsTitle);

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
            <div class="actions albums-hero-actions">
                <details class="albums-propose-menu">
                    <summary class="button" aria-haspopup="menu"><?= e((string) $t['propose_menu']) ?></summary>
                    <div class="albums-propose-menu-panel" role="menu">
                        <a class="albums-propose-menu-item" role="menuitem" href="<?= e($albumProposalUrl) ?>"><?= e((string) $t['propose_album_item']) ?></a>
                        <a class="albums-propose-menu-item" role="menuitem" href="<?= e($albumCategoryProposalUrl) ?>"><?= e((string) $t['propose_category_item']) ?></a>
                        <a class="albums-propose-menu-item" role="menuitem" href="<?= e($albumSubcategoryProposalUrl) ?>"><?= e((string) $t['propose_subcategory_item']) ?></a>
                    </div>
                </details>
                <?php if ($canManageAlbums): ?>
                    <a class="button secondary" href="<?= e(route_url('admin_albums')) ?>"><?= e((string) $t['administer']) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if ($showAlbumProposalForm): ?>
    <section class="card">
        <h2><?= e((string) $t['propose_album_title']) ?></h2>
        <p class="help"><?= e($canManageAlbums ? (string) $t['proposal_direct_help'] : (string) $t['proposal_pending_help']) ?></p>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="propose_album">
            <label><span><?= e((string) $t['title_label']) ?></span><input type="text" name="proposal_title" maxlength="190" required></label>
            <label><span><?= e((string) $t['category_field']) ?></span>
                <select name="proposal_theme">
                    <?php foreach ($albumCategories as $albumThemeCode => $albumThemeLabel): ?>
                        <option value="<?= e((string) $albumThemeCode) ?>"><?= e((string) $albumThemeLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span><?= e((string) $t['subcategory_field']) ?></span>
                <select name="proposal_subcategory_ref">
                    <option value=""><?= e((string) $t['no_subcategory']) ?></option>
                    <?php foreach ($albumSubcategoriesByCategory as $parentCode => $subcategories): ?>
                        <optgroup label="<?= e((string) ($albumCategories[(string) $parentCode] ?? album_category_label_from_code((string) $parentCode))) ?>">
                            <?php foreach ($subcategories as $subcategoryInfo): ?>
                                <?php $subCode = album_subcategory_code((string) $subcategoryInfo['code']); ?>
                                <?php if ($subCode === '') { continue; } ?>
                                <option value="<?= e(album_subcategory_ref((string) $parentCode, $subCode)) ?>"><?= e((string) $subcategoryInfo['label']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span><?= e((string) $t['keywords_label']) ?></span><input type="text" name="proposal_keywords" maxlength="255"></label>
            <label><span><?= e((string) $t['description_label']) ?></span><textarea name="proposal_description" rows="5" maxlength="5000"></textarea></label>
            <label><span><?= e((string) $t['contact_label']) ?></span><input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required></label>
            <p class="actions">
                <button class="button" type="submit"><?= e($canManageAlbums ? (string) $t['create_album_submit'] : (string) $t['proposal_submit']) ?></button>
                <a class="button secondary" href="<?= e(route_url('albums')) ?>"><?= e((string) $t['cancel']) ?></a>
            </p>
        </form>
    </section>
    <?php endif; ?>

    <?php if ($showAlbumCategoryProposalForm): ?>
    <section class="card" id="album-category-proposal">
        <h2><?= e((string) $t['propose_category_title']) ?></h2>
        <p class="help"><?= e($canManageAlbums ? (string) $t['category_direct_help'] : (string) $t['category_pending_help']) ?></p>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="propose_category">
            <label><span><?= e((string) $t['proposal_category_name_label']) ?></span><input type="text" name="proposal_category_name" maxlength="160" required></label>
            <label><span><?= e((string) $t['description_label']) ?></span><textarea name="proposal_reason" rows="5" maxlength="1600"></textarea></label>
            <label><span><?= e((string) $t['contact_label']) ?></span><input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required></label>
            <p class="actions">
                <button class="button" type="submit"><?= e($canManageAlbums ? (string) $t['create_category_submit'] : (string) $t['proposal_submit']) ?></button>
                <a class="button secondary" href="<?= e(route_url('albums')) ?>"><?= e((string) $t['cancel']) ?></a>
            </p>
        </form>
    </section>
    <?php endif; ?>

    <?php if ($showAlbumSubcategoryProposalForm): ?>
    <section class="card" id="album-subcategory-proposal">
        <h2><?= e((string) $t['propose_subcategory_title']) ?></h2>
        <p class="help"><?= e($canManageAlbums ? (string) $t['subcategory_direct_help'] : (string) $t['subcategory_pending_help']) ?></p>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="propose_subcategory">
            <label><span><?= e((string) $t['parent_category_label']) ?></span>
                <select name="proposal_parent_category" required>
                    <?php foreach ($albumCategories as $albumThemeCode => $albumThemeLabel): ?>
                        <option value="<?= e((string) $albumThemeCode) ?>"><?= e((string) $albumThemeLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span><?= e((string) $t['proposal_subcategory_name_label']) ?></span><input type="text" name="proposal_subcategory_name" maxlength="160" required></label>
            <label><span><?= e((string) $t['description_label']) ?></span><textarea name="proposal_reason" rows="5" maxlength="1600"></textarea></label>
            <label><span><?= e((string) $t['contact_label']) ?></span><input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required></label>
            <p class="actions">
                <button class="button" type="submit"><?= e($canManageAlbums ? (string) $t['create_subcategory_submit'] : (string) $t['proposal_submit']) ?></button>
                <a class="button secondary" href="<?= e(route_url('albums')) ?>"><?= e((string) $t['cancel']) ?></a>
            </p>
        </form>
    </section>
    <?php endif; ?>

    <section class="albums-toolbar">
        <form method="get" class="albums-search-form">
            <input type="hidden" name="route" value="albums">
            <?php if ($categoryFilter !== ''): ?>
                <input type="hidden" name="category" value="<?= e($categoryFilter) ?>">
            <?php endif; ?>
            <?php if ($subcategoryFilter !== ''): ?>
                <input type="hidden" name="subcategory" value="<?= e($subcategoryFilter) ?>">
            <?php endif; ?>
            <?php if ($favoritesOnly): ?>
                <input type="hidden" name="favorites" value="1">
            <?php endif; ?>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_placeholder']) ?>">
            <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
            <?php if ($search !== '' || $categoryFilter !== '' || $subcategoryFilter !== '' || $favoritesOnly): ?>
                <a class="button secondary" href="<?= e(route_url('albums')) ?>"><?= e((string) $t['reset']) ?></a>
            <?php endif; ?>
        </form>
    </section>

    <section class="albums-layout module-taxonomy-layout">
        <aside class="card albums-taxonomy module-taxonomy-index">
            <p class="module-taxonomy-title"><?= e((string) $t['category_field']) ?></p>
            <nav class="albums-category-list module-taxonomy-list" aria-label="<?= e((string) $t['category_field']) ?>">
                <?php if ($favoriteAlbumCount > 0): ?>
                    <a class="albums-category-item module-taxonomy-item<?= $favoritesOnly ? ' is-active' : '' ?>" href="<?= e(route_url_clean('albums', ['favorites' => '1', 'q' => $search])) ?>"<?= $favoritesOnly ? ' aria-current="page"' : '' ?>>
                        <span><?= e($favoritesLabel) ?></span>
                        <strong><?= (int) $favoriteAlbumCount ?></strong>
                    </a>
                <?php endif; ?>
                <a class="albums-category-item module-taxonomy-item<?= !$favoritesOnly && $categoryFilter === '' && $subcategoryFilter === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('albums', ['q' => $search])) ?>"<?= !$favoritesOnly && $categoryFilter === '' && $subcategoryFilter === '' ? ' aria-current="page"' : '' ?>>
                    <span><?= e((string) $t['all_categories']) ?></span>
                    <strong><?= (int) array_sum($albumCategoryCounts) ?></strong>
                </a>
                <?php foreach ($visibleAlbumCategories as $categoryCode => $categoryLabel): ?>
                    <a class="albums-category-item module-taxonomy-item<?= !$favoritesOnly && $categoryFilter === $categoryCode && $subcategoryFilter === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('albums', ['category' => (string) $categoryCode, 'q' => $search])) ?>"<?= !$favoritesOnly && $categoryFilter === $categoryCode && $subcategoryFilter === '' ? ' aria-current="page"' : '' ?>>
                        <span><?= e((string) $categoryLabel) ?></span>
                        <strong><?= (int) ($albumCategoryCounts[$categoryCode] ?? 0) ?></strong>
                    </a>
                    <?php if (($visibleAlbumSubcategoriesByCategory[(string) $categoryCode] ?? []) !== []): ?>
                        <div class="albums-subcategory-list module-taxonomy-children">
                            <?php foreach ($visibleAlbumSubcategoriesByCategory[(string) $categoryCode] as $subcategoryInfo): ?>
                                <?php $subCode = album_subcategory_code((string) ($subcategoryInfo['code'] ?? '')); ?>
                                <a class="albums-subcategory-item module-taxonomy-item module-taxonomy-subitem<?= !$favoritesOnly && $categoryFilter === $categoryCode && $subcategoryFilter === $subCode ? ' is-active' : '' ?>" href="<?= e(route_url_clean('albums', ['category' => (string) $categoryCode, 'subcategory' => $subCode, 'q' => $search])) ?>"<?= !$favoritesOnly && $categoryFilter === $categoryCode && $subcategoryFilter === $subCode ? ' aria-current="page"' : '' ?>>
                                    <span><?= e((string) ($subcategoryInfo['label'] ?? $subCode)) ?></span>
                                    <strong><?= (int) ($subcategoryInfo['total'] ?? 0) ?></strong>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </aside>

    <section class="albums-gallery module-taxonomy-content">
        <?php if ($albumSections === []): ?>
            <article class="albums-empty">
                <h2><?= e((string) $t['none']) ?></h2>
                <p class="help"><?= ($search !== '' || $categoryFilter !== '' || $subcategoryFilter !== '' || $favoritesOnly) ? e((string) $t['for_search']) : e((string) $t['intro']) ?></p>
            </article>
        <?php else: ?>
            <?php foreach ($albumSections as $albumSection): ?>
                <?php
                $albumSectionRows = $albumSection['rows'];
                $albumSectionTitle = trim((string) $albumSection['title']);
                $albumSectionFeatured = (bool) $albumSection['featured'];
                ?>
                <section class="albums-results-section<?= $albumSectionFeatured ? ' albums-featured-section' : '' ?>">
                    <?php if ($albumSectionTitle !== ''): ?>
                        <div class="albums-section-heading">
                            <h2><?= e($albumSectionTitle) ?></h2>
                        </div>
                    <?php endif; ?>
                    <div class="albums-grid<?= $albumSectionFeatured ? ' albums-featured-grid' : '' ?>">
                <?php foreach ($albumSectionRows as $row):
                    $albumId = (int) ($row['id'] ?? 0);
                    $albumTitle = trim((string) ($row['title'] ?? ''));
                    if ($albumTitle === '') {
                        $albumTitle = (string) $t['albums'];
                    }
                    $coverSrc = '';
                    $coverWebpSrc = '';
                    $photoCount = (int) ($row['photo_count'] ?? 0);
                    $description = trim((string) ($row['description'] ?? ''));
                    $descriptionText = '';
                    if ($description !== '') {
                        $descriptionText = album_description_display_text($description);
                        $descriptionText = html_entity_decode(strip_tags($descriptionText), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $descriptionText = trim((string) preg_replace('/\s+/u', ' ', $descriptionText));
                    }
                    $albumCategory = 'general';
                    $albumSubcategory = '';
                    $albumCategoryLabel = (string) ($albumCategories['general'] ?? $t['category_field']);
                    $albumSubcategoryLabel = '';
                    $albumFeatured = (int) ($row['is_featured'] ?? 0) === 1;
                    $canEditAlbum = false;
                    try {
                        $coverPath = album_photo_public_path_or_null((string) ($row['cover_path'] ?? ''));
                        $coverThumb = $coverPath !== null ? album_existing_thumbnail_fallback_public_path($coverPath) : '';
                        if ($coverThumb !== '') {
                            $coverSrc = $coverThumb;
                            $coverWebpSrc = album_existing_thumbnail_webp_public_path((string) $coverPath);
                        } else {
                            $coverSrc = $coverPath ?? '';
                            $coverWebpSrc = $coverPath !== null ? album_existing_display_webp_public_path($coverPath) : '';
                        }
                        $albumCategory = album_category_code((string) ($row['category'] ?? 'general'));
                        $albumSubcategory = album_subcategory_code((string) ($row['subcategory'] ?? ''));
                        $albumCategoryLabel = (string) ($albumCategories[$albumCategory] ?? album_category_label_from_code($albumCategory));
                        $albumSubcategoryLabel = $albumSubcategory !== '' ? album_category_label_from_code($albumSubcategory) : '';
                        $canEditAlbum = $user !== null && $albumId > 0 && ($canManageAlbums || (int) ($row['member_id'] ?? 0) === (int) ($user['id'] ?? 0));
                    } catch (Throwable $throwable) {
                        log_structured_event('album_tile_render_prepare_failed', [
                            'album_id' => $albumId,
                            'message' => $throwable->getMessage(),
                        ]);
                    }
                    $editDialogId = 'album-edit-dialog-' . $albumId;
                    ?>
                    <article class="album-tile<?= $albumSectionFeatured ? ' album-tile-featured' : '' ?>">
                        <div class="album-tile-media-stack">
                            <a class="album-tile-media" href="<?= e(route_url('album', ['id' => $albumId])) ?>">
                                <?php if ($coverSrc !== ''): ?>
                                    <?= album_picture_html($coverSrc, (string) $t['cover_alt'] . ' ' . $albumTitle, ['loading' => 'lazy', 'decoding' => 'async'], $coverWebpSrc) ?>
                                <?php else: ?>
                                    <span class="album-placeholder-mark" aria-hidden="true"></span>
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="album-tile-body">
                            <div>
                                <h2><a href="<?= e(route_url('album', ['id' => $albumId])) ?>"><?= e($albumTitle) ?></a></h2>
                                <div class="album-tile-badges">
                                    <?php if ($albumSectionFeatured): ?>
                                        <span class="badge album-featured-badge"><?= e($featuredAlbumBadge) ?></span>
                                    <?php endif; ?>
                                    <span class="badge muted album-photo-count-badge"><?= $photoCount ?> <?= e((string) ($photoCount > 1 ? $t['photos'] : $t['photo'])) ?></span>
                                    <?php if ($user !== null): ?>
                                        <?php $isFavorite = favorite_is_saved((int) $user['id'], 'album', (int) ($row['id'] ?? 0)); ?>
                                        <?php $favoriteLabel = $isFavorite ? (string) $t['favorite_remove'] : (string) $t['favorite_add']; ?>
                                        <form method="post" class="album-favorite-form">
                                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="toggle_favorite_album">
                                            <input type="hidden" name="album_id" value="<?= (int) ($row['id'] ?? 0) ?>">
                                            <input type="hidden" name="return_q" value="<?= e($search) ?>">
                                            <input type="hidden" name="return_category" value="<?= e($categoryFilter) ?>">
                                            <input type="hidden" name="return_subcategory" value="<?= e($subcategoryFilter) ?>">
                                            <input type="hidden" name="return_favorites" value="<?= $favoritesOnly ? '1' : '' ?>">
                                            <input type="hidden" name="return_p" value="<?= $page ?>">
                                            <button class="button secondary badge muted album-favorite-badge" type="submit" aria-label="<?= e($favoriteLabel) ?>" title="<?= e($favoriteLabel) ?>"><span><?= e((string) $t['favorites']) ?></span><span aria-hidden="true"><?= $isFavorite ? '&#9733;' : '&#9734;' ?></span></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <?php if ($descriptionText !== ''): ?>
                                    <p><?= e(mb_safe_strimwidth($descriptionText, 0, 150, '...')) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($canEditAlbum): ?>
                            <div class="album-tile-actions">
                                <button class="button secondary album-tile-edit-button" type="button" data-album-modal-open="<?= e($editDialogId) ?>" aria-haspopup="dialog" aria-controls="<?= e($editDialogId) ?>"><?= e((string) $t['edit_album']) ?></button>
                            </div>
                        <?php endif; ?>
                    </article>
                    <?php if ($canEditAlbum): ?>
                        <dialog class="album-dialog" id="<?= e($editDialogId) ?>" aria-labelledby="<?= e($editDialogId) ?>-title">
                            <div class="album-dialog-card">
                                <div class="album-dialog-header module-dialog-header">
                                    <div>
                                        <p class="module-dialog-eyebrow"><?= e((string) $t['albums']) ?></p>
                                        <h2 id="<?= e($editDialogId) ?>-title"><?= e((string) $t['edit_album_title']) ?></h2>
                                        <p class="help"><?= e($albumTitle) ?></p>
                                    </div>
                                    <button class="album-dialog-close module-dialog-close" type="button" data-album-modal-close aria-label="<?= e((string) $t['close']) ?>">&times;</button>
                                </div>
                                <form method="post" class="album-dialog-form module-dialog-form">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="update_album">
                                    <input type="hidden" name="album_id" value="<?= $albumId ?>">
                                    <input type="hidden" name="return_q" value="<?= e($search) ?>">
                                    <input type="hidden" name="return_category" value="<?= e($categoryFilter) ?>">
                                    <input type="hidden" name="return_subcategory" value="<?= e($subcategoryFilter) ?>">
                                    <input type="hidden" name="return_favorites" value="<?= $favoritesOnly ? '1' : '' ?>">
                                    <input type="hidden" name="return_p" value="<?= $page ?>">
                                    <label><span><?= e((string) $t['title_label']) ?></span><input type="text" name="title" value="<?= e($albumTitle) ?>" maxlength="190" required></label>
                                    <?= render_album_taxonomy_fields($albumCategories, $t, $albumCategory, $albumSubcategory) ?>
                                    <label><span><?= e((string) $t['description_label']) ?></span><textarea name="description" rows="5" maxlength="10000"><?= e($description) ?></textarea></label>
                                    <?php if ($canManageAlbums): ?>
                                        <input type="hidden" name="album_is_featured_present" value="1">
                                        <label><input type="checkbox" name="album_is_featured" value="1" autocomplete="off" <?= $albumFeatured ? 'checked' : '' ?>> <?= e($featuredAlbumsTitle) ?></label>
                                    <?php endif; ?>
                                    <p class="album-dialog-actions module-dialog-actions">
                                        <button class="button" type="submit"><?= e((string) $t['save_album']) ?></button>
                                        <button class="button secondary" type="button" data-album-modal-close><?= e((string) $t['cancel']) ?></button>
                                    </p>
                                </form>
                                <form method="post" class="album-delete-form">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete_album">
                                    <input type="hidden" name="album_id" value="<?= $albumId ?>">
                                    <input type="hidden" name="return_q" value="<?= e($search) ?>">
                                    <input type="hidden" name="return_category" value="<?= e($categoryFilter) ?>">
                                    <input type="hidden" name="return_subcategory" value="<?= e($subcategoryFilter) ?>">
                                    <input type="hidden" name="return_favorites" value="<?= $favoritesOnly ? '1' : '' ?>">
                                    <input type="hidden" name="return_p" value="<?= $page ?>">
                                    <p class="help"><?= e($canManageAlbums
                                        ? (string) $t['delete_album_warning_admin']
                                        : (string) $t['delete_album_warning']) ?></p>
                                    <button class="button secondary album-danger" type="submit"><?= e((string) $t['delete_album']) ?></button>
                                </form>
                            </div>
                        </dialog>
                    <?php endif; ?>
                <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
            <?php if ($totalPages > 1): ?>
                <nav class="actions mt-3" aria-label="<?= e((string) $t['pagination']) ?>">
                    <?php if ($page > 1): ?>
                        <a class="button secondary" href="<?= e(route_url_clean('albums', ['category' => $categoryFilter, 'subcategory' => $subcategoryFilter, 'favorites' => $favoritesOnly ? '1' : '', 'q' => $search, 'p' => 1])) ?>"><?= e((string) $t['first_page']) ?></a>
                        <a class="button secondary" href="<?= e(route_url_clean('albums', ['category' => $categoryFilter, 'subcategory' => $subcategoryFilter, 'favorites' => $favoritesOnly ? '1' : '', 'q' => $search, 'p' => $page - 1])) ?>"><?= e((string) $t['previous']) ?></a>
                    <?php endif; ?>
                    <span class="pill"><?= e((string) $t['page']) ?> <?= $page ?> / <?= $totalPages ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a class="button secondary" href="<?= e(route_url_clean('albums', ['category' => $categoryFilter, 'subcategory' => $subcategoryFilter, 'favorites' => $favoritesOnly ? '1' : '', 'q' => $search, 'p' => $page + 1])) ?>"><?= e((string) $t['next']) ?></a>
                        <a class="button secondary" href="<?= e(route_url_clean('albums', ['category' => $categoryFilter, 'subcategory' => $subcategoryFilter, 'favorites' => $favoritesOnly ? '1' : '', 'q' => $search, 'p' => $totalPages])) ?>"><?= e((string) $t['last_page']) ?></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['albums']);
