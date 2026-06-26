<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$wikiMessages = i18n_domain_locale('wiki', $locale);
$t = i18n_domain_translator('wiki_edit', $locale);
$autoPublish = has_permission('wiki.moderate');
$tr = static function (string $key) use ($t): string {
    $value = trim($t($key));

    return $value !== '' && $value !== $key ? $value : $key;
};

$requestedMode = (string) ($_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['proposal_mode'] ?? '') : ($_GET['mode'] ?? ''));
$isModification = $requestedMode === 'modify';
$pageTitle = $isModification
    ? $tr('propose_modification_title')
    : $tr('propose_title');
$pageDescription = $isModification
    ? $tr('propose_modification_meta_desc')
    : $tr('propose_meta_desc');

set_page_meta([
    'title' => $pageTitle,
    'description' => $pageDescription,
    'robots' => 'noindex,nofollow',
]);

if (!ensure_wiki_tables()) {
    echo render_layout('<div class="card"><h1>' . e($pageTitle) . '</h1><p>' . e($tr('meta_desc')) . '</p></div>', $pageTitle);
    return;
}

$wikiCategories = wiki_categories($wikiMessages);
$sourceId = (int) ($_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['source_id'] ?? 0) : ($_GET['source_id'] ?? 0));
$sourcePages = [];
$sourcePage = null;

$loadSourcePage = static function (int $id): ?array {
    if ($id <= 0) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, title, slug, content, category, subcategory, subsubcategory, author_id FROM wiki_pages WHERE id = ? AND ' . wiki_public_page_where_sql() . ' LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
};

if ($isModification) {
    $sourcePages = db()->query('SELECT id, title, slug, category, subcategory, subsubcategory FROM wiki_pages WHERE ' . wiki_public_page_where_sql() . ' ORDER BY title ASC, id ASC LIMIT 300')->fetchAll() ?: [];
    if ($sourceId > 0) {
        $sourcePage = $loadSourcePage($sourceId);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedMode = (string) ($_POST['proposal_mode'] ?? 'new');
    $postedModification = $postedMode === 'modify';
    $postedSourceId = (int) ($_POST['source_id'] ?? 0);

    try {
        verify_csrf();
        $title = trim((string) ($_POST['title'] ?? ''));
        $slugInput = trim((string) ($_POST['slug'] ?? ''));
        $content = sanitize_rich_html((string) ($_POST['content'] ?? ''));
        [$category, $subcategory, $subsubcategory] = wiki_taxonomy_from_input(
            (string) ($_POST['category'] ?? 'general'),
            trim((string) ($_POST['subcategory_ref'] ?? '')),
            $wikiCategories,
            'general',
            trim((string) ($_POST['subsubcategory_ref'] ?? ''))
        );
        $postedSourcePage = null;

        if ($postedModification) {
            if ($postedSourceId <= 0) {
                throw new RuntimeException($tr('source_page_required'));
            }
            $postedSourcePage = $loadSourcePage($postedSourceId);
            if ($postedSourcePage === null) {
                throw new RuntimeException($tr('source_page_not_found'));
            }
        }

        if ($title === '' || trim(strip_tags($content)) === '') {
            throw new RuntimeException($tr('error_title_content_required'));
        }
        if (mb_strlen($title) > 190 || mb_strlen($slugInput) > 190 || mb_strlen($category) > 120 || mb_strlen($subcategory) > 120 || mb_strlen($subsubcategory) > 120 || mb_strlen($content) > 50000) {
            throw new RuntimeException($tr('error_field_too_long'));
        }

        if ($postedModification && $postedSourcePage !== null) {
            $targetSlugInput = $slugInput !== '' ? $slugInput : (string) ($postedSourcePage['slug'] ?? '');
            if ($autoPublish) {
                $slug = wiki_unique_slug($title, $targetSlugInput, (int) $postedSourcePage['id']);
                $pdo = db();
                $pdo->beginTransaction();
                try {
                    $pdo->prepare('INSERT INTO wiki_revisions (wiki_page_id, member_id, content) VALUES (?, ?, ?)')
                        ->execute([(int) $postedSourcePage['id'], (int) $user['id'], (string) ($postedSourcePage['content'] ?? '')]);
                    $pdo->prepare('UPDATE wiki_pages SET title = ?, slug = ?, content = ?, category = ?, subcategory = ?, subsubcategory = ?, author_id = ?, status = "published", proposal_kind = "page", source_page_id = NULL, target_slug = NULL WHERE id = ?')
                        ->execute([$title, $slug, $content, $category, $subcategory, $subsubcategory, (int) $user['id'], (int) $postedSourcePage['id']]);
                    $pdo->commit();
                } catch (Throwable $throwable) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $throwable;
                }

                set_flash('success', $tr('propose_modification_success_published'));
                redirect_url(route_url('wiki_view', ['slug' => $slug]));
            }

            $proposalSlug = wiki_unique_slug($title, 'modification-' . (string) ($postedSourcePage['slug'] ?? 'wiki'));
            $targetSlug = wiki_slug_base($title, $targetSlugInput);
            db()->prepare('INSERT INTO wiki_pages (title, slug, content, category, subcategory, subsubcategory, author_id, status, proposal_kind, source_page_id, target_slug) VALUES (?, ?, ?, ?, ?, ?, ?, "pending", "modification", ?, ?)')
                ->execute([$title, $proposalSlug, $content, $category, $subcategory, $subsubcategory, (int) $user['id'], (int) $postedSourcePage['id'], $targetSlug]);

            set_flash('success', $tr('propose_modification_success'));
            redirect('my_requests');
        }

        $slug = wiki_unique_slug($title, $slugInput);
        $status = $autoPublish ? 'published' : 'pending';
        db()->prepare('INSERT INTO wiki_pages (title, slug, content, category, subcategory, subsubcategory, author_id, status, proposal_kind) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "page")')
            ->execute([$title, $slug, $content, $category, $subcategory, $subsubcategory, (int) $user['id'], $status]);

        if ($autoPublish) {
            set_flash('success', $tr('propose_success_published'));
            redirect_url(route_url('wiki_view', ['slug' => $slug]));
        }

        set_flash('success', $tr('propose_success'));
        redirect('my_requests');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        $returnParams = [];
        if ($postedModification) {
            $returnParams['mode'] = 'modify';
            if ($postedSourceId > 0) {
                $returnParams['source_id'] = $postedSourceId;
            }
        }
        redirect_url(route_url('wiki_propose', $returnParams));
    }
}

$initialTitle = $sourcePage !== null ? (string) ($sourcePage['title'] ?? '') : '';
$initialSlug = $sourcePage !== null ? (string) ($sourcePage['slug'] ?? '') : '';
$initialContent = $sourcePage !== null ? (string) ($sourcePage['content'] ?? '<p></p>') : '<p></p>';
$initialCategory = wiki_category_code($sourcePage !== null ? (string) ($sourcePage['category'] ?? 'general') : 'general');
$initialSubcategory = wiki_subcategory_code($sourcePage !== null ? (string) ($sourcePage['subcategory'] ?? '') : '');
$initialSubsubcategory = wiki_subsubcategory_code($sourcePage !== null ? (string) ($sourcePage['subsubcategory'] ?? '') : '');
$helpText = $isModification
    ? ($autoPublish
        ? $tr('propose_modification_help_auto_publish')
        : $tr('propose_modification_help'))
    : ($autoPublish
        ? $tr('propose_help_auto_publish')
        : $tr('propose_help'));

ob_start();
?>
<div class="wiki-edit-page">
    <section class="wiki-edit-hero">
        <div>
            <p class="eyebrow"><?= e($tr('wiki_label')) ?></p>
            <h1><?= e($pageTitle) ?></h1>
            <p class="help"><?= e($helpText) ?></p>
        </div>
        <a class="button secondary" href="<?= e(route_url('wiki')) ?>"><?= e($tr('wiki_label')) ?></a>
    </section>

    <?php if ($isModification): ?>
        <form method="get" class="wiki-edit-form wiki-source-picker">
            <input type="hidden" name="route" value="wiki_propose">
            <input type="hidden" name="mode" value="modify">
            <label><?= e($tr('source_page_label')) ?>
                <select name="source_id" required>
                    <option value=""><?= e($tr('source_page_placeholder')) ?></option>
                    <?php foreach ($sourcePages as $candidate): ?>
                        <option value="<?= (int) $candidate['id'] ?>" <?= (int) $candidate['id'] === $sourceId ? 'selected' : '' ?>>
                            <?= e((string) $candidate['title']) ?> (/<?= e((string) $candidate['slug']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="actions">
                <button class="button" type="submit"><?= e($tr('source_page_load')) ?></button>
            </div>
        </form>
    <?php endif; ?>

    <?php if (!$isModification || $sourcePage !== null): ?>
        <form method="post" class="wiki-edit-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="proposal_mode" value="<?= $isModification ? 'modify' : 'new' ?>">
            <?php if ($isModification): ?>
                <input type="hidden" name="source_id" value="<?= (int) $sourceId ?>">
            <?php endif; ?>
            <div class="wiki-edit-grid">
                <label><?= e($t('title_label')) ?><input type="text" name="title" value="<?= e($initialTitle) ?>" maxlength="190" required></label>
                <label><?= e($t('slug_label')) ?><input type="text" name="slug" value="<?= e($initialSlug) ?>" maxlength="190" placeholder="theme-titre-de-page"></label>
                <?= render_wiki_taxonomy_fields($wikiCategories, $wikiMessages, $initialCategory, $initialSubcategory, $initialSubsubcategory) ?>
            </div>
            <label><?= e($t('content_label')) ?>
                <textarea name="content" rows="22" maxlength="50000" data-wysiwyg="full" required><?= e($initialContent) ?></textarea>
            </label>
            <div class="actions">
                <button class="button" type="submit"><?= e($isModification
                    ? ($autoPublish ? $tr('propose_modification_submit_publish') : $tr('propose_modification_submit'))
                    : ($autoPublish ? $tr('propose_submit_publish') : $tr('propose_submit'))) ?></button>
                <a class="button secondary" href="<?= e(route_url('wiki')) ?>"><?= e($tr('cancel')) ?></a>
            </div>
        </form>
    <?php elseif ($isModification): ?>
        <div class="wiki-edit-form">
            <p class="help"><?= e($tr('source_page_select_help')) ?></p>
        </div>
    <?php endif; ?>
</div>
<?php
echo render_layout((string) ob_get_clean(), $pageTitle);
