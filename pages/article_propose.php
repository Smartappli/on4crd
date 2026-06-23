<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$articlesI18n = i18n_domain_locale('articles', $locale);
$label = static function (string $key, string $fallback) use ($articlesI18n): string {
    $value = trim((string) ($articlesI18n[$key] ?? ''));
    return $value !== '' ? $value : $fallback;
};

function article_propose_enforce_submission_limits(int $memberId): void
{
    if ($memberId <= 0) {
        throw new RuntimeException(article_propose_t('error_invalid_user', 'Utilisateur invalide.'));
    }

    $lastStmt = db()->prepare('SELECT created_at FROM articles WHERE author_id = ? ORDER BY created_at DESC, id DESC LIMIT 1');
    $lastStmt->execute([$memberId]);
    $lastCreatedAt = $lastStmt->fetchColumn();
    if (is_string($lastCreatedAt) && $lastCreatedAt !== '') {
        $lastTs = strtotime($lastCreatedAt);
        if ($lastTs !== false && $lastTs > time() - 60) {
            throw new RuntimeException(article_propose_t('error_wait_before_next', 'Veuillez patienter une minute avant de proposer un autre article.'));
        }
    }

    $countStmt = db()->prepare('SELECT COUNT(*) FROM articles WHERE author_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)');
    $countStmt->execute([$memberId]);
    if ((int) ($countStmt->fetchColumn() ?: 0) >= 5) {
        throw new RuntimeException(article_propose_t('error_daily_limit', 'Limite atteinte : maximum 5 propositions d\'article par 24 heures.'));
    }
}

function article_propose_t(string $key, string $fallback): string
{
    static $messages = null;
    if ($messages === null) {
        $messages = i18n_domain_locale('articles', current_locale());
    }

    $value = trim((string) ($messages[$key] ?? ''));

    return $value !== '' ? $value : $fallback;
}

$title = $label('propose_article', 'Proposer un article');
article_ensure_taxonomy_schema($articlesI18n);
$categories = article_categories($articlesI18n);

set_page_meta([
    'title' => $title,
    'description' => $label('propose_article_meta_desc', 'Composer et soumettre un article pour validation.'),
    'robots' => 'noindex,nofollow',
    'schema_type' => 'WebPage',
]);

if (!table_exists('articles')) {
    echo render_layout('<div class="card"><p>' . e($label('module_unavailable', 'Le module articles est temporairement indisponible.')) . '</p></div>', $title);
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $articleTitle = trim((string) ($_POST['title'] ?? ''));
        $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
        $rawContent = trim((string) ($_POST['content'] ?? ''));
        [$category, $subcategory] = article_taxonomy_from_input(
            (string) ($_POST['category'] ?? 'autres'),
            trim((string) ($_POST['subcategory_ref'] ?? '')),
            $categories
        );
        if (mb_strlen($articleTitle) > 190 || mb_strlen($excerpt) > 2000 || mb_strlen($rawContent) > 50000) {
            throw new RuntimeException($label('error_field_too_long', 'Un des champs dépasse la longueur autorisée.'));
        }
        if ($articleTitle === '' || $rawContent === '') {
            throw new RuntimeException($label('error_title_content_required', 'Le titre et le contenu sont obligatoires.'));
        }

        article_propose_enforce_submission_limits((int) $user['id']);

        $content = article_sanitize_content($rawContent);
        if (trim(strip_tags($content)) === '') {
            throw new RuntimeException($label('error_content_empty_after_cleanup', 'Le contenu de l\'article est vide après nettoyage.'));
        }

        $maxSlugAttempts = 5;
        for ($slugAttempt = 0; $slugAttempt < $maxSlugAttempts; $slugAttempt++) {
            $slug = article_unique_slug($articleTitle);
            try {
                db()->beginTransaction();
                db()->prepare('INSERT INTO articles (title, slug, excerpt, content, status, category, subcategory, author_id) VALUES (?, ?, ?, ?, "pending", ?, ?, ?)')
                    ->execute([$articleTitle, $slug, $excerpt !== '' ? $excerpt : null, $content, $category, $subcategory, (int) $user['id']]);
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

        set_flash('success', $label('propose_article_success', 'Article soumis pour validation.'));
        redirect('my_requests');
    } catch (Throwable $throwable) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        $message = $throwable instanceof PDOException
            ? $label('error_article_save_failed', 'Impossible d\'enregistrer l\'article pour le moment.')
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
            <p class="eyebrow"><?= e($label('layout_title', 'Articles')) ?></p>
            <h1><?= e($title) ?></h1>
            <p class="help"><?= e($label('propose_article_help', 'Mettez votre article en page avec des titres, paragraphes, listes et liens. Il sera enregistré dans vos contenus puis validé avant publication.')) ?></p>
        </div>
        <div class="actions">
            <a class="button secondary" href="<?= e(route_url('articles')) ?>"><?= e($label('page_title', 'Articles')) ?></a>
            <a class="button secondary" href="<?= e(route_url('my_requests')) ?>"><?= e($label('my_contents', 'Mes contenus')) ?></a>
        </div>
    </section>

    <section class="card">
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <label><?= e($label('article_title_label', 'Titre de l\'article')) ?><input type="text" name="title" maxlength="190" required></label>
            <?= render_article_taxonomy_fields($categories, $articlesI18n, 'autres') ?>
            <label><?= e($label('excerpt_label', 'Résumé')) ?><textarea name="excerpt" rows="3" maxlength="2000" placeholder="<?= e($label('excerpt_placeholder', 'Court résumé affiché dans la liste des articles.')) ?>"></textarea></label>
            <label><?= e($label('content_label', 'Contenu mis en page')) ?><textarea name="content" rows="16" maxlength="50000" data-wysiwyg="full" required placeholder="<?= e($label('content_placeholder', '<h2>Titre de section</h2>' . "\n" . '<p>Votre texte...</p>' . "\n" . '<ul><li>Point important</li></ul>')) ?>"></textarea></label>
            <p class="help"><?= e($label('html_cleanup_help', 'Le HTML est nettoyé automatiquement. Les scripts, iframes et attributs dangereux sont retirés avant validation.')) ?></p>
            <div class="actions">
                <button class="button" type="submit"><?= e($label('submit_for_review', 'Soumettre pour validation')) ?></button>
                <a class="button secondary" href="<?= e(route_url('articles')) ?>"><?= e($label('propose_category_cancel', 'Annuler')) ?></a>
            </div>
        </form>
    </section>
</div>
<?php

echo render_layout((string) ob_get_clean(), $title);
