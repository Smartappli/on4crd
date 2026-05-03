<?php
declare(strict_types=1);

require_permission('admin.access');

$locale = current_locale();
$i18n = [
    'fr' => ['title' => 'Administration du wiki', 'new_page' => 'Nouvelle page', 'th_title' => 'Titre', 'th_slug' => 'Slug', 'th_updated' => 'Mise à jour', 'th_action' => 'Action', 'edit' => 'Modifier', 'empty' => 'Aucune page.', 'layout' => 'Administration wiki', 'meta_desc' => 'Gestion des pages wiki.'],
    'en' => ['title' => 'Wiki administration', 'new_page' => 'New page', 'th_title' => 'Title', 'th_slug' => 'Slug', 'th_updated' => 'Updated', 'th_action' => 'Action', 'edit' => 'Edit', 'empty' => 'No pages.', 'layout' => 'Wiki administration', 'meta_desc' => 'Manage wiki pages.'],
    'de' => ['title' => 'Wiki-Verwaltung', 'new_page' => 'Neue Seite', 'th_title' => 'Titel', 'th_slug' => 'Slug', 'th_updated' => 'Aktualisiert', 'th_action' => 'Aktion', 'edit' => 'Bearbeiten', 'empty' => 'Keine Seiten.', 'layout' => 'Wiki-Verwaltung', 'meta_desc' => 'Wiki-Seiten verwalten.'],
    'nl' => ['title' => 'Wiki-beheer', 'new_page' => 'Nieuwe pagina', 'th_title' => 'Titel', 'th_slug' => 'Slug', 'th_updated' => 'Bijgewerkt', 'th_action' => 'Actie', 'edit' => 'Bewerken', 'empty' => 'Geen pagina\'s.', 'layout' => 'Wiki-beheer', 'meta_desc' => 'Wiki-pagina\'s beheren.'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};


set_page_meta([
    'title' => $t('layout'),
    'description' => $t('meta_desc'),
    'robots' => 'noindex,nofollow',
]);

$pages = db()->query('SELECT id, title, slug, updated_at FROM wiki_pages ORDER BY updated_at DESC')->fetchAll();

ob_start();
?>
<div class="card">
    <div class="row-between">
        <h1><?= e($t('title')) ?></h1>
        <?php if (has_permission('wiki.edit')): ?>
            <a class="button small" href="<?= e(base_url('index.php?route=wiki_edit')) ?>"><?= e($t('new_page')) ?></a>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th><?= e($t('th_title')) ?></th><th><?= e($t('th_slug')) ?></th><th><?= e($t('th_updated')) ?></th><th><?= e($t('th_action')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($pages as $page): ?>
                <tr>
                    <td><?= e((string) $page['title']) ?></td>
                    <td><code><?= e((string) $page['slug']) ?></code></td>
                    <td><?= e((string) $page['updated_at']) ?></td>
                    <td><a href="<?= e(base_url('index.php?route=wiki_edit&id=' . (int) $page['id'])) ?>"><?= e($t('edit')) ?></a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($pages === []): ?><tr><td colspan="4"><?= e($t('empty')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
echo render_layout((string) ob_get_clean(), $t('layout'));
