<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$t = i18n_domain_translator('wiki_edit', $locale);
$tr = static function (string $key, string $fallback) use ($t): string {
    $value = trim($t($key));

    return $value !== '' && $value !== $key ? $value : $fallback;
};

set_page_meta([
    'title' => $tr('propose_title', 'Proposer une page wiki'),
    'description' => $tr('propose_meta_desc', 'Créer une nouvelle page wiki depuis l’espace membre.'),
    'robots' => 'noindex,nofollow',
]);

if (!ensure_wiki_tables()) {
    echo render_layout('<div class="card"><h1>' . e($tr('propose_title', 'Proposer une page wiki')) . '</h1><p>' . e($tr('meta_desc', 'Créer ou modifier une page wiki.')) . '</p></div>', $tr('propose_title', 'Proposer une page wiki'));
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $title = trim((string) ($_POST['title'] ?? ''));
        $slugInput = trim((string) ($_POST['slug'] ?? ''));
        $content = sanitize_rich_html((string) ($_POST['content'] ?? ''));

        if ($title === '' || trim(strip_tags($content)) === '') {
            throw new RuntimeException($tr('error_title_content_required', 'Le titre et le contenu sont obligatoires.'));
        }
        if (mb_strlen($title) > 190 || mb_strlen($slugInput) > 190 || mb_strlen($content) > 50000) {
            throw new RuntimeException($tr('error_field_too_long', 'Un des champs dépasse la longueur autorisée.'));
        }

        $slug = wiki_unique_slug($title, $slugInput);
        db()->prepare('INSERT INTO wiki_pages (title, slug, content, author_id, status) VALUES (?, ?, ?, ?, "pending")')
            ->execute([$title, $slug, $content, (int) $user['id']]);

        set_flash('success', $tr('propose_success', 'Page wiki proposée. Elle sera publiée après validation.'));
        redirect_url(route_url('wiki'));
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('wiki_propose');
    }
}

ob_start();
?>
<div class="wiki-edit-page">
    <section class="wiki-edit-hero">
        <div>
            <p class="eyebrow"><?= e($tr('wiki_label', 'Wiki')) ?></p>
            <h1><?= e($tr('propose_title', 'Proposer une page wiki')) ?></h1>
            <p class="help"><?= e($tr('propose_help', 'Rédigez une nouvelle page avec du HTML simple. Elle sera relue avant publication.')) ?></p>
        </div>
        <a class="button secondary" href="<?= e(route_url('wiki')) ?>"><?= e($tr('wiki_label', 'Wiki')) ?></a>
    </section>

    <form method="post" class="wiki-edit-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <div class="wiki-edit-grid">
            <label><?= e($t('title_label')) ?><input type="text" name="title" maxlength="190" required></label>
            <label><?= e($t('slug_label')) ?><input type="text" name="slug" maxlength="190" placeholder="theme-titre-de-page"></label>
        </div>
        <label><?= e($t('content_label')) ?>
            <textarea name="content" rows="22" maxlength="50000" data-wysiwyg="full" required><p></p></textarea>
        </label>
        <div class="actions">
            <button class="button" type="submit"><?= e($tr('propose_submit', 'Proposer la page')) ?></button>
            <a class="button secondary" href="<?= e(route_url('wiki')) ?>"><?= e($tr('cancel', 'Annuler')) ?></a>
        </div>
    </form>
</div>
<?php
echo render_layout((string) ob_get_clean(), $tr('propose_title', 'Proposer une page wiki'));
