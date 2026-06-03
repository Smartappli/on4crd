<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$t = i18n_domain_translator('wiki_edit', $locale);

set_page_meta([
    'title' => 'Proposer une page wiki',
    'description' => 'Créer une nouvelle page wiki depuis l’espace membre.',
    'robots' => 'noindex,nofollow',
]);

if (!ensure_wiki_tables()) {
    echo render_layout('<div class="card"><h1>Proposer une page wiki</h1><p>' . e($t('meta_desc')) . '</p></div>', 'Proposer une page wiki');
    return;
}

function wiki_propose_unique_slug(string $title, string $slugInput = ''): string
{
    $base = slugify($slugInput !== '' ? $slugInput : $title);
    if ($base === '' || $base === 'n-a') {
        $base = 'wiki';
    }

    $candidate = $base;
    $suffix = 2;
    while (true) {
        $stmt = db()->prepare('SELECT id FROM wiki_pages WHERE slug = ? LIMIT 1');
        $stmt->execute([$candidate]);
        if (!$stmt->fetchColumn()) {
            return $candidate;
        }
        $candidate = $base . '-' . $suffix;
        $suffix++;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $title = trim((string) ($_POST['title'] ?? ''));
        $slugInput = trim((string) ($_POST['slug'] ?? ''));
        $content = sanitize_rich_html((string) ($_POST['content'] ?? ''));

        if ($title === '' || trim(strip_tags($content)) === '') {
            throw new RuntimeException('Le titre et le contenu sont obligatoires.');
        }
        if (mb_strlen($title) > 190 || mb_strlen($slugInput) > 190 || mb_strlen($content) > 50000) {
            throw new RuntimeException('Un des champs dépasse la longueur autorisée.');
        }

        $slug = wiki_propose_unique_slug($title, $slugInput);
        db()->prepare('INSERT INTO wiki_pages (title, slug, content, author_id) VALUES (?, ?, ?, ?)')
            ->execute([$title, $slug, $content, (int) $user['id']]);

        set_flash('success', 'Page wiki proposée.');
        redirect_url(route_url('wiki_view', ['slug' => $slug]));
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
            <p class="eyebrow">Wiki</p>
            <h1>Proposer une page wiki</h1>
            <p class="help">Rédigez une nouvelle page avec du HTML simple. La page sera créée avec votre compte comme auteur.</p>
        </div>
        <a class="button secondary" href="<?= e(route_url('wiki')) ?>">Wiki</a>
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
            <button class="button" type="submit">Proposer la page</button>
            <a class="button secondary" href="<?= e(route_url('wiki')) ?>">Annuler</a>
        </div>
    </form>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Proposer une page wiki');
