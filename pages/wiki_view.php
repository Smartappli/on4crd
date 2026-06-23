<?php
declare(strict_types=1);

$locale = current_locale();
$wikiMessages = i18n_domain_locale('wiki', $locale);
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/wiki_view.php');
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

set_page_meta([
    'title' => $t('layout'),
    'description' => $t('meta_desc'),
    'robots' => 'index,follow',
]);

if (!ensure_wiki_tables()) {
    echo render_layout('<div class="card"><p>' . e($t('not_found')) . '</p></div>', $t('layout'));
    return;
}

$slug = trim((string) ($_GET['slug'] ?? ''));
$canModerateWiki = has_permission('wiki.moderate');
$visibilitySql = $canModerateWiki ? '' : ' AND ' . wiki_public_page_where_sql('p');
$stmt = db()->prepare(
    'SELECT p.*, m.callsign
     FROM wiki_pages p
     LEFT JOIN members m ON m.id = p.author_id
     WHERE p.slug = ?' . $visibilitySql . '
     LIMIT 1'
);
$stmt->execute([$slug]);
$row = $stmt->fetch();

if (!$row) {
    echo render_layout('<div class="card"><p>' . e($t('not_found')) . '</p></div>', $t('layout'));
    return;
}

$wikiCategories = wiki_categories($wikiMessages);
$wikiStatus = (string) ($row['status'] ?? 'published');
$isPublished = $wikiStatus === 'published';
$isModificationProposal = (string) ($row['proposal_kind'] ?? 'page') === 'modification';
$currentUser = current_user();
$canManageWikiPage = $currentUser !== null
    && !$isModificationProposal
    && ($canModerateWiki || (int) ($row['author_id'] ?? 0) === (int) ($currentUser['id'] ?? 0));
$wikiViewText = static function (string $key, string $fr, string $en) use ($locale, $t): string {
    $value = trim($t($key));
    if ($value !== '' && $value !== $key) {
        return $value;
    }

    return $locale === 'fr' ? $fr : $en;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $actingUser = require_login(route_url('wiki_view', ['slug' => (string) $row['slug']]));
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'toggle_favorite') {
            if (function_exists('favorite_toggle')) {
                $saved = favorite_toggle(
                    (int) $actingUser['id'],
                    'wiki_page',
                    (int) $row['id'],
                    (string) ($row['title'] ?? ''),
                    route_url('wiki_view', ['slug' => (string) $row['slug']])
                );
                notify_member((int) $actingUser['id'], 'favorite', $saved ? $wikiViewText('favorite_added', 'Favori ajouté', 'Favorite added') : $wikiViewText('favorite_removed', 'Favori retiré', 'Favorite removed'), (string) ($row['title'] ?? ''), route_url('wiki_view', ['slug' => (string) $row['slug']]));
                set_flash('success', $saved ? $wikiViewText('favorite_added_msg', 'Page ajoutée aux favoris.', 'Page added to favorites.') : $wikiViewText('favorite_removed_msg', 'Page retirée des favoris.', 'Page removed from favorites.'));
            }
            redirect_url(route_url('wiki_view', ['slug' => (string) $row['slug']]));
        }
        if (($action !== 'update_page' && $action !== 'delete_page') || !$canManageWikiPage) {
            throw new RuntimeException($wikiViewText('forbidden', 'Vous ne pouvez pas modifier cette page.', 'You cannot edit this page.'));
        }

        if ($action === 'delete_page') {
            if ($canModerateWiki) {
                wiki_delete_page_record((int) $row['id']);
                set_flash('success', $wikiViewText('delete_success', 'Page wiki supprimee.', 'Wiki page deleted.'));
                redirect_url(route_url('wiki'));
            }

            $summary = content_proposal_details_text([
                'Action' => 'delete_page',
                'Page ID' => (string) (int) $row['id'],
                'Slug' => (string) $row['slug'],
                'Category' => wiki_category_code((string) ($row['category'] ?? 'general')),
                'Subcategory' => wiki_subcategory_code((string) ($row['subcategory'] ?? '')),
            ]);
            $sourceRef = route_url('wiki_view', ['slug' => (string) $row['slug']]);
            $proposalId = content_proposal_create(
                (int) $actingUser['id'],
                'wiki',
                'content',
                (string) $row['title'],
                $summary,
                (string) ($actingUser['email'] ?? ''),
                $sourceRef,
                'pending'
            );
            content_proposal_notify_site($wikiViewText('delete_subject', 'Suppression wiki a valider', 'Wiki deletion pending review'), [
                'area' => 'wiki',
                'proposal_type' => 'content',
                'title' => (string) $row['title'],
                'summary' => $summary,
                'contact' => (string) ($actingUser['email'] ?? ''),
                'source_ref' => 'content_proposals#' . $proposalId . ' ' . $sourceRef,
            ]);
            set_flash('success', $wikiViewText('change_recorded', 'Modification enregistree dans vos contenus en attente de validation.', 'Change saved in your content pending review.'));
            redirect('my_requests');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $slugInput = trim((string) ($_POST['slug'] ?? ''));
        $content = sanitize_rich_html((string) ($_POST['content'] ?? ''));
        $category = wiki_category_from_input((string) ($_POST['category'] ?? 'general'), $wikiCategories);
        $subcategory = wiki_subcategory_code((string) ($row['subcategory'] ?? ''));
        if (array_key_exists('subcategory_ref', $_POST)) {
            [$category, $subcategory] = wiki_taxonomy_from_input(
                (string) ($_POST['category'] ?? 'general'),
                trim((string) ($_POST['subcategory_ref'] ?? '')),
                $wikiCategories,
                (string) ($row['category'] ?? 'general')
            );
        }
        if ($title === '' || trim(strip_tags($content)) === '') {
            throw new RuntimeException($wikiViewText('title_content_required', 'Le titre et le contenu sont obligatoires.', 'Title and content are required.'));
        }
        if (mb_strlen($title) > 190 || mb_strlen($slugInput) > 190 || mb_strlen($category) > 120 || mb_strlen($subcategory) > 120 || mb_strlen($content) > 50000) {
            throw new RuntimeException($wikiViewText('field_too_long', 'Un des champs depasse la longueur autorisee.', 'One field is too long.'));
        }

        $targetSlugInput = $slugInput !== '' ? $slugInput : (string) $row['slug'];
        if ($canModerateWiki) {
            $slug = wiki_unique_slug($title, $targetSlugInput, (int) $row['id']);
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $pdo->prepare('INSERT INTO wiki_revisions (wiki_page_id, member_id, content) VALUES (?, ?, ?)')
                    ->execute([(int) $row['id'], (int) $actingUser['id'], (string) ($row['content'] ?? '')]);
                $pdo->prepare('UPDATE wiki_pages SET title = ?, slug = ?, content = ?, category = ?, subcategory = ?, author_id = ?, status = "published", proposal_kind = "page", source_page_id = NULL, target_slug = NULL, updated_at = NOW() WHERE id = ?')
                    ->execute([$title, $slug, $content, $category, $subcategory, (int) $actingUser['id'], (int) $row['id']]);
                $pdo->commit();
            } catch (Throwable $throwable) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $throwable;
            }

            set_flash('success', $wikiViewText('update_success', 'Page wiki mise a jour.', 'Wiki page updated.'));
            redirect_url(route_url('wiki_view', ['slug' => $slug]));
        }

        $proposalSlug = wiki_unique_slug($title, 'modification-' . (string) $row['slug']);
        $targetSlug = wiki_slug_base($title, $targetSlugInput);
        db()->prepare('INSERT INTO wiki_pages (title, slug, content, category, subcategory, author_id, status, proposal_kind, source_page_id, target_slug) VALUES (?, ?, ?, ?, ?, ?, "pending", "modification", ?, ?)')
            ->execute([$title, $proposalSlug, $content, $category, $subcategory, (int) $actingUser['id'], (int) $row['id'], $targetSlug]);

        set_flash('success', $wikiViewText('change_recorded', 'Modification enregistree dans vos contenus en attente de validation.', 'Change saved in your content pending review.'));
        redirect('my_requests');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect_url(route_url('wiki_view', ['slug' => (string) $row['slug']]));
    }
}

$revisionStmt = db()->prepare(
    'SELECT r.id, r.created_at, m.callsign
     FROM wiki_revisions r
     LEFT JOIN members m ON m.id = r.member_id
     WHERE r.wiki_page_id = ?
     ORDER BY r.created_at DESC, r.id DESC
     LIMIT 10'
);
$revisionStmt->execute([(int) $row['id']]);
$revisions = $revisionStmt->fetchAll() ?: [];

$author = trim((string) ($row['callsign'] ?? ''));
$categoryCode = wiki_category_code((string) ($row['category'] ?? 'general'));
$categoryLabel = (string) ($wikiCategories[$categoryCode] ?? wiki_category_label_from_code($categoryCode));
$subcategoryCode = wiki_subcategory_code((string) ($row['subcategory'] ?? ''));
$wikiSubcategoryLabels = [];
foreach (wiki_subcategories_by_category() as $parentCode => $subcategories) {
    foreach ($subcategories as $subcategoryInfo) {
        $wikiSubcategoryLabels[(string) $parentCode . ':' . (string) ($subcategoryInfo['code'] ?? '')] = (string) ($subcategoryInfo['label'] ?? '');
    }
}
$subcategoryLabel = $subcategoryCode !== '' ? (string) ($wikiSubcategoryLabels[$categoryCode . ':' . $subcategoryCode] ?? wiki_category_label_from_code($subcategoryCode)) : '';
$isFavorite = $currentUser !== null && function_exists('favorite_is_saved') && favorite_is_saved((int) ($currentUser['id'] ?? 0), 'wiki_page', (int) $row['id']);
$updatedAt = strtotime((string) ($row['updated_at'] ?? '')) ?: time();
$wikiUrl = route_url_with_locale('wiki_view', $locale, ['slug' => (string) $row['slug']]);
$wikiPlainText = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $row['content']), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
$wikiDescription = mb_safe_strimwidth($wikiPlainText !== '' ? $wikiPlainText : $t('meta_desc'), 0, 220, '...');
set_page_meta([
    'title' => (string) $row['title'],
    'description' => $wikiDescription,
    'ai_summary' => $wikiDescription,
    'robots' => $isPublished ? 'index,follow' : 'noindex,nofollow',
    'canonical' => $wikiUrl,
    'schema_type' => 'TechArticle',
    'modified_time' => date('c', $updatedAt),
    'section' => 'Wiki ON4CRD - ' . $categoryLabel,
    'tags' => ['ON4CRD', 'wiki radioamateur', $categoryLabel, 'Radio Club Durnal'],
    'keywords' => ['ON4CRD', 'wiki radioamateur', $categoryLabel, 'documentation radioamateur', 'Radio Club Durnal'],
    'citation_author' => $author !== '' ? $author : 'Radio Club Durnal ON4CRD',
    'json_ld' => [
        [
            '@context' => 'https://schema.org',
            '@type' => 'TechArticle',
            'headline' => (string) $row['title'],
            'description' => $wikiDescription,
            'abstract' => $wikiDescription,
            'url' => $wikiUrl,
            'dateModified' => date('c', $updatedAt),
            'articleSection' => $categoryLabel,
            'wordCount' => str_word_count($wikiPlainText),
            'inLanguage' => $locale,
            'proficiencyLevel' => 'Beginner',
            'about' => [
                '@type' => 'Thing',
                'name' => 'amateur radio',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Radio Club Durnal ON4CRD',
                'url' => route_url_with_locale('home', $locale),
            ],
            'author' => [
                '@type' => $author !== '' ? 'Person' : 'Organization',
                'name' => $author !== '' ? $author : 'Radio Club Durnal ON4CRD',
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $wikiUrl,
            ],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'ON4CRD',
                    'item' => route_url_with_locale('home', $locale),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $t('layout'),
                    'item' => route_url_with_locale('wiki', $locale),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => (string) $row['title'],
                    'item' => $wikiUrl,
                ],
            ],
        ],
    ],
]);

ob_start();
?>
<div class="wiki-view-page">
    <section class="wiki-view-hero">
        <div>
            <p class="eyebrow">/<?= e((string) $row['slug']) ?></p>
            <h1><?= e((string) $row['title']) ?></h1>
            <p class="help">
                <?= e(date('d/m/Y H:i', $updatedAt)) ?>
                &middot; <a class="wiki-category-link" href="<?= e(route_url_clean('wiki', ['theme' => $categoryCode])) ?>"><?= e($categoryLabel) ?></a>
                <?php if ($subcategoryCode !== ''): ?> &middot; <a class="wiki-category-link" href="<?= e(route_url_clean('wiki', ['theme' => $categoryCode, 'subtheme' => $subcategoryCode])) ?>"><?= e($subcategoryLabel) ?></a><?php endif; ?>
                <?php if ($author !== ''): ?> · <?= e($author) ?><?php endif; ?>
                <?php if (!$isPublished): ?> · <span class="badge muted"><?= e($wikiStatus) ?></span><?php endif; ?>
            </p>
        </div>
        <div class="wiki-view-actions">
            <a class="button secondary" href="<?= e(route_url('wiki')) ?>"><?= e($t('layout')) ?></a>
            <?php if ($currentUser !== null): ?>
                <form method="post" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="toggle_favorite">
                    <button class="button secondary" type="submit"><?= $isFavorite ? '&#9733;' : '&#9734;' ?></button>
                </form>
            <?php endif; ?>
            <?php if ($canManageWikiPage): ?>
                <button class="button" type="button" data-wiki-page-modal-open="wiki-page-edit-dialog" aria-haspopup="dialog" aria-controls="wiki-page-edit-dialog"><?= e($wikiViewText('edit_page', 'Modifier / Supprimer', 'Edit / Delete')) ?></button>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($canManageWikiPage): ?>
        <dialog class="wiki-page-dialog" id="wiki-page-edit-dialog" aria-labelledby="wiki-page-edit-dialog-title">
            <div class="wiki-page-dialog-card">
                <div class="wiki-page-dialog-header module-dialog-header">
                    <div>
                        <p class="module-dialog-eyebrow"><?= e($t('layout')) ?></p>
                        <h2 id="wiki-page-edit-dialog-title"><?= e($wikiViewText('edit_page_title', 'Modifier la page wiki', 'Edit wiki page')) ?></h2>
                        <p class="help">/<?= e((string) $row['slug']) ?></p>
                    </div>
                    <button class="wiki-page-dialog-close module-dialog-close" type="button" data-wiki-page-modal-close aria-label="<?= e($wikiViewText('close', 'Fermer', 'Close')) ?>">&times;</button>
                </div>
                <form method="post" class="wiki-page-form module-dialog-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_page">
                    <div class="wiki-edit-grid">
                        <label><span><?= e($wikiViewText('title_label', 'Titre', 'Title')) ?></span><input type="text" name="title" value="<?= e((string) $row['title']) ?>" maxlength="190" required></label>
                        <label><span><?= e($wikiViewText('slug_label', 'Slug', 'Slug')) ?></span><input type="text" name="slug" value="<?= e((string) $row['slug']) ?>" maxlength="190"></label>
                        <?= render_wiki_taxonomy_fields($wikiCategories, $wikiMessages, $categoryCode, $subcategoryCode) ?>
                    </div>
                    <label><span><?= e($wikiViewText('content_label', 'Contenu', 'Content')) ?></span><textarea name="content" rows="18" maxlength="50000" data-wysiwyg="full" required><?= e((string) $row['content']) ?></textarea></label>
                    <p class="wiki-page-dialog-actions module-dialog-actions">
                        <button class="button" type="submit"><?= e($wikiViewText('save_page', 'Enregistrer', 'Save')) ?></button>
                        <button class="button secondary" type="button" data-wiki-page-modal-close><?= e($wikiViewText('cancel', 'Annuler', 'Cancel')) ?></button>
                    </p>
                </form>
                <form method="post" class="wiki-page-delete-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete_page">
                    <p class="help"><?= e($canModerateWiki
                        ? $wikiViewText('delete_page_warning_admin', 'La suppression de cette page est definitive.', 'Deleting this page is permanent.')
                        : $wikiViewText('delete_page_warning', 'La suppression de cette page sera appliquee apres validation.', 'Deleting this page will be applied after review.')) ?></p>
                    <button class="button secondary wiki-page-danger" type="submit"><?= e($wikiViewText('delete_page', 'Supprimer la page', 'Delete page')) ?></button>
                </form>
            </div>
        </dialog>
    <?php endif; ?>

    <div class="wiki-view-layout">
        <article class="wiki-article">
            <?= sanitize_rich_html((string) $row['content']) ?>
        </article>

        <aside class="wiki-history-panel">
            <h2><?= e($t('history')) ?></h2>
            <?php if ($revisions === []): ?>
                <p class="help"><?= e($t('no_revisions')) ?></p>
            <?php else: ?>
                <ol>
                    <?php foreach ($revisions as $revision):
                        $revisionAuthor = trim((string) ($revision['callsign'] ?? ''));
                        $revisionDate = strtotime((string) ($revision['created_at'] ?? '')) ?: time();
                        ?>
                        <li>
                            <strong><?= e(date('d/m/Y H:i', $revisionDate)) ?></strong>
                            <?php if ($revisionAuthor !== ''): ?><span><?= e($revisionAuthor) ?></span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </aside>
    </div>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $row['title']);
