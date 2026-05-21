<?php
declare(strict_types=1);

require_permission('admin.access');

$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/admin_wiki.php';
$i18n = i18n_expand_supported_locales($i18n);
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
            <a class="button small" href="<?= e(route_url('wiki_edit')) ?>"><?= e($t('new_page')) ?></a>
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
                    <td><a href="<?= e(route_url('wiki_edit', ['id' => (int) $page['id']])) ?>"><?= e($t('edit')) ?></a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($pages === []): ?><tr><td colspan="4"><?= e($t('empty')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
echo render_layout((string) ob_get_clean(), $t('layout'));
