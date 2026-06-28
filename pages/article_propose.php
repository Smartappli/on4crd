<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$articlesI18n = i18n_domain_locale('articles', $locale);
$label = static function (string $key) use ($articlesI18n): string {
    $value = trim((string) ($articlesI18n[$key] ?? ''));
    return $value !== '' ? $value : $key;
};

function article_propose_enforce_submission_limits(int $memberId): void
{
    if ($memberId <= 0) {
        throw new RuntimeException(article_propose_t('error_invalid_user'));
    }

    $lastStmt = db()->prepare('SELECT created_at FROM articles WHERE author_id = ? ORDER BY created_at DESC, id DESC LIMIT 1');
    $lastStmt->execute([$memberId]);
    $lastCreatedAt = $lastStmt->fetchColumn();
    if (is_string($lastCreatedAt) && $lastCreatedAt !== '') {
        $lastTs = strtotime($lastCreatedAt);
        if ($lastTs !== false && $lastTs > time() - 60) {
            throw new RuntimeException(article_propose_t('error_wait_before_next'));
        }
    }

    $countStmt = db()->prepare('SELECT COUNT(*) FROM articles WHERE author_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)');
    $countStmt->execute([$memberId]);
    if ((int) ($countStmt->fetchColumn() ?: 0) >= 5) {
        throw new RuntimeException(article_propose_t('error_daily_limit'));
    }
}

function article_propose_t(string $key): string
{
    static $messages = null;
    if ($messages === null) {
        $messages = i18n_domain_locale('articles', current_locale());
    }

    $value = trim((string) ($messages[$key] ?? ''));

    return $value !== '' ? $value : $key;
}

$title = $label('propose_article');
article_ensure_taxonomy_schema($articlesI18n);
$categories = article_categories($articlesI18n);
$articleFieldLimits = [
    'title' => 190,
    'excerpt' => 2000,
    'content' => 5000000,
    'taxonomy' => 120,
];

set_page_meta([
    'title' => $title,
    'description' => $label('propose_article_meta_desc'),
    'robots' => 'noindex,nofollow',
    'schema_type' => 'WebPage',
]);

if (!table_exists('articles')) {
    echo render_layout('<div class="card"><p>' . e($label('module_unavailable')) . '</p></div>', $title);
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $articleTitle = trim((string) ($_POST['title'] ?? ''));
        $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
        $rawContent = trim((string) ($_POST['content'] ?? ''));
        [$category, $subcategory, $subsubcategory] = article_taxonomy_from_input(
            (string) ($_POST['category'] ?? 'autres'),
            trim((string) ($_POST['subcategory_ref'] ?? '')),
            $categories,
            'autres',
            trim((string) ($_POST['subsubcategory_ref'] ?? ''))
        );
        if (
            mb_strlen($articleTitle) > $articleFieldLimits['title']
            || mb_strlen($excerpt) > $articleFieldLimits['excerpt']
            || mb_strlen($rawContent) > $articleFieldLimits['content']
            || mb_strlen($category) > $articleFieldLimits['taxonomy']
            || mb_strlen($subcategory) > $articleFieldLimits['taxonomy']
            || mb_strlen($subsubcategory) > $articleFieldLimits['taxonomy']
        ) {
            throw new RuntimeException($label('error_field_too_long'));
        }
        if ($articleTitle === '' || $rawContent === '') {
            throw new RuntimeException($label('error_title_content_required'));
        }

        article_propose_enforce_submission_limits((int) $user['id']);

        $content = article_sanitize_content($rawContent);
        if (trim(strip_tags($content)) === '') {
            throw new RuntimeException($label('error_content_empty_after_cleanup'));
        }

        $maxSlugAttempts = 5;
        for ($slugAttempt = 0; $slugAttempt < $maxSlugAttempts; $slugAttempt++) {
            $slug = article_unique_slug($articleTitle);
            try {
                db()->beginTransaction();
                db()->prepare('INSERT INTO articles (title, slug, excerpt, content, status, category, subcategory, subsubcategory, author_id) VALUES (?, ?, ?, ?, "pending", ?, ?, ?, ?)')
                    ->execute([$articleTitle, $slug, $excerpt !== '' ? $excerpt : null, $content, $category, $subcategory, $subsubcategory, (int) $user['id']]);
                db()->commit();
                break;
            } catch (Throwable $submitThrowable) {
                if (db()->inTransaction()) {
                    db()->rollBack();
                }
                if (!article_is_duplicate_slug_error($submitThrowable) || $slugAttempt === $maxSlugAttempts - 1) {
                    throw $submitThrowable;
                }
                usleep(20000 * ($slugAttempt + 1));
            }
        }

        set_flash('success', $label('propose_article_success'));
        redirect('my_requests');
    } catch (Throwable $throwable) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        $message = $throwable instanceof PDOException
            ? $label('error_article_save_failed')
            : $throwable->getMessage();
        set_flash('error', $message);
        redirect('article_propose');
    }
}

ob_start();
?>
<div class="stack">
    <section class="page-hero">
        <div>
            <p class="eyebrow"><?= e($label('layout_title')) ?></p>
            <h1><?= e($title) ?></h1>
            <p class="help"><?= e($label('propose_article_help')) ?></p>
        </div>
        <div class="actions">
            <a class="button secondary" href="<?= e(route_url('articles')) ?>"><?= e($label('page_title')) ?></a>
            <a class="button secondary" href="<?= e(route_url('my_requests')) ?>"><?= e($label('my_contents')) ?></a>
        </div>
    </section>

    <section class="card">
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <label><?= e($label('article_title_label')) ?><input type="text" name="title" maxlength="190" required></label>
            <?= render_article_taxonomy_fields($categories, $articlesI18n, 'autres') ?>
            <label><?= e($label('excerpt_label')) ?><textarea name="excerpt" rows="3" maxlength="2000" placeholder="<?= e($label('excerpt_placeholder')) ?>"></textarea></label>
            <label><?= e($label('content_label')) ?><textarea name="content" rows="16" maxlength="<?= (int) $articleFieldLimits['content'] ?>" data-wysiwyg="full" required placeholder="<?= e($label('content_placeholder')) ?>"></textarea></label>
            <p class="help"><?= e($label('html_cleanup_help')) ?></p>
            <div class="actions">
                <button class="button" type="submit"><?= e($label('submit_for_review')) ?></button>
                <a class="button secondary" href="<?= e(route_url('articles')) ?>"><?= e($label('propose_category_cancel')) ?></a>
            </div>
        </form>
    </section>
</div>
<?php

echo render_layout((string) ob_get_clean(), $title);
