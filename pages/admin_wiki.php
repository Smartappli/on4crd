<?php
declare(strict_types=1);

require_permission('wiki.moderate');

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

if (!ensure_wiki_tables()) {
    echo render_layout('<div class="card"><h1>' . e($t('layout')) . '</h1><p>' . e($t('meta_desc')) . '</p></div>', $t('layout'));
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        if ($id <= 0 || !in_array($status, ['pending', 'published', 'rejected'], true)) {
            throw new RuntimeException('Page wiki invalide.');
        }
        $pageStmt = db()->prepare('SELECT id FROM wiki_pages WHERE id = ? LIMIT 1');
        $pageStmt->execute([$id]);
        if (!$pageStmt->fetchColumn()) {
            throw new RuntimeException('Page wiki introuvable.');
        }
        db()->prepare('UPDATE wiki_pages SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$status, $id]);
        set_flash('success', 'Statut wiki enregistré.');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect('admin_wiki');
}

$pages = db()->query('SELECT id, title, slug, status, updated_at FROM wiki_pages ORDER BY status ASC, updated_at DESC')->fetchAll();

ob_start();
?>
<div class="card">
    <div class="row-between">
        <h1><?= e($t('title')) ?></h1>
        <?php if (has_permission('wiki.moderate')): ?>
            <a class="button small" href="<?= e(route_url('wiki_edit')) ?>"><?= e($t('new_page')) ?></a>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table>
                <thead><tr><th><?= e($t('th_title')) ?></th><th><?= e($t('th_slug')) ?></th><th>Statut</th><th><?= e($t('th_updated')) ?></th><th><?= e($t('th_action')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($pages as $page): ?>
                <tr>
                    <td><?= e((string) $page['title']) ?></td>
                    <td><code><?= e((string) $page['slug']) ?></code></td>
                    <td><span class="badge muted"><?= e((string) ($page['status'] ?? 'published')) ?></span></td>
                    <td><?= e((string) $page['updated_at']) ?></td>
                    <td>
                        <a href="<?= e(route_url('wiki_edit', ['id' => (int) $page['id']])) ?>"><?= e($t('edit')) ?></a>
                        <form method="post" class="inline-form" style="display:inline-flex;margin-left:.5rem;">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $page['id'] ?>">
                            <select name="status">
                                <?php foreach (['pending' => 'En validation', 'published' => 'Publié', 'rejected' => 'Refusé'] as $statusCode => $statusLabel): ?>
                                    <option value="<?= e($statusCode) ?>" <?= (string) ($page['status'] ?? 'published') === $statusCode ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="button secondary small" type="submit">OK</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($pages === []): ?><tr><td colspan="5"><?= e($t('empty')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
echo render_layout((string) ob_get_clean(), $t('layout'));
