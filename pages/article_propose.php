<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$articlesI18n = i18n_domain_locale('articles', $locale);
$label = static function (string $key, string $fallback) use ($articlesI18n): string {
    $value = trim((string) ($articlesI18n[$key] ?? ''));
    return $value !== '' ? $value : $fallback;
};

function article_propose_unique_slug(string $title): string
{
    $base = slugify($title);
    if ($base === '' || $base === 'n-a') {
        $base = 'article';
    }

    $candidate = $base;
    $suffix = 2;
    while (true) {
        $stmt = db()->prepare('SELECT id FROM articles WHERE slug = ? LIMIT 1');
        $stmt->execute([$candidate]);
        if (!$stmt->fetchColumn()) {
            return $candidate;
        }
        $candidate = $base . '-' . $suffix;
        $suffix++;
    }
}

function article_propose_enforce_submission_limits(int $memberId): void
{
    if ($memberId <= 0) {
        throw new RuntimeException('Utilisateur invalide.');
    }

    $lastStmt = db()->prepare('SELECT created_at FROM articles WHERE author_id = ? ORDER BY created_at DESC, id DESC LIMIT 1');
    $lastStmt->execute([$memberId]);
    $lastCreatedAt = $lastStmt->fetchColumn();
    if (is_string($lastCreatedAt) && $lastCreatedAt !== '') {
        $lastTs = strtotime($lastCreatedAt);
        if ($lastTs !== false && $lastTs > time() - 60) {
            throw new RuntimeException('Veuillez patienter une minute avant de proposer un autre article.');
        }
    }

    $countStmt = db()->prepare('SELECT COUNT(*) FROM articles WHERE author_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)');
    $countStmt->execute([$memberId]);
    if ((int) ($countStmt->fetchColumn() ?: 0) >= 5) {
        throw new RuntimeException('Limite atteinte: maximum 5 propositions d\'article par 24 heures.');
    }
}

$title = $label('propose_article', 'Proposer un article');
$categories = [
    'antennes' => $label('theme_antennes', 'Antennes'),
    'trafic' => $label('theme_trafic', 'Trafic'),
    'numerique' => $label('theme_numerique', 'Numerique'),
    'materiel' => $label('theme_materiel', 'Materiel'),
    'formation' => $label('theme_formation', 'Formation'),
    'autres' => $label('theme_autres', 'Autres'),
];

set_page_meta([
    'title' => $title,
    'description' => 'Composer et soumettre un article pour validation.',
    'robots' => 'noindex,nofollow',
    'schema_type' => 'WebPage',
]);

if (!table_exists('articles')) {
    echo render_layout('<div class="card"><p>Le module articles est temporairement indisponible.</p></div>', $title);
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $articleTitle = trim((string) ($_POST['title'] ?? ''));
        $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
        $rawContent = trim((string) ($_POST['content'] ?? ''));
        $category = slugify((string) ($_POST['category'] ?? 'autres'));
        if ($category === '' || !isset($categories[$category])) {
            $category = 'autres';
        }
        if (mb_strlen($articleTitle) > 190 || mb_strlen($excerpt) > 2000 || mb_strlen($rawContent) > 50000) {
            throw new RuntimeException('Un des champs depasse la longueur autorisee.');
        }
        if ($articleTitle === '' || $rawContent === '') {
            throw new RuntimeException('Le titre et le contenu sont obligatoires.');
        }

        article_propose_enforce_submission_limits((int) $user['id']);

        $content = article_sanitize_content($rawContent);
        if (trim(strip_tags($content)) === '') {
            throw new RuntimeException('Le contenu de l\'article est vide apres nettoyage.');
        }

        $slug = article_propose_unique_slug($articleTitle);
        db()->beginTransaction();
        db()->prepare('INSERT INTO articles (title, slug, excerpt, content, status, category, author_id) VALUES (?, ?, ?, ?, "pending", ?, ?)')
            ->execute([$articleTitle, $slug, $excerpt !== '' ? $excerpt : null, $content, $category, (int) $user['id']]);
        $articleId = (int) db()->lastInsertId();
        db()->commit();

        try {
            article_translations_sync_all($articleId);
        } catch (Throwable) {
            set_flash('warning', 'Article enregistre, mais les traductions automatiques devront etre relancees.');
        }

        set_flash('success', 'Article soumis pour validation.');
        redirect('my_requests');
    } catch (Throwable $throwable) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        $message = $throwable instanceof PDOException
            ? 'Impossible d\'enregistrer l\'article pour le moment.'
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
            <p class="help">Mettez votre article en page avec des titres, paragraphes, listes et liens. Il sera enregistre dans vos contenus puis valide avant publication.</p>
        </div>
        <div class="actions">
            <a class="button secondary" href="<?= e(route_url('articles')) ?>"><?= e($label('page_title', 'Articles')) ?></a>
            <a class="button secondary" href="<?= e(route_url('my_requests')) ?>">Mes contenus</a>
        </div>
    </section>

    <section class="card">
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <label>Titre de l'article<input type="text" name="title" maxlength="190" required></label>
            <label>Categorie<select name="category">
                <?php foreach ($categories as $code => $categoryLabel): ?>
                    <option value="<?= e($code) ?>"><?= e($categoryLabel) ?></option>
                <?php endforeach; ?>
            </select></label>
            <label>Resume<textarea name="excerpt" rows="3" maxlength="2000" placeholder="Court resume affiche dans la liste des articles."></textarea></label>
            <label>Contenu mis en page<textarea name="content" rows="16" maxlength="50000" data-wysiwyg="full" required placeholder="<h2>Titre de section</h2>&#10;<p>Votre texte...</p>&#10;<ul><li>Point important</li></ul>"></textarea></label>
            <p class="help">Le HTML est nettoye automatiquement. Les scripts, iframes et attributs dangereux sont retires avant validation.</p>
            <div class="actions">
                <button class="button" type="submit">Soumettre pour validation</button>
                <a class="button secondary" href="<?= e(route_url('articles')) ?>">Annuler</a>
            </div>
        </form>
    </section>
</div>
<?php

echo render_layout((string) ob_get_clean(), $title);
