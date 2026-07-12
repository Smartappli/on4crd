<?php
declare(strict_types=1);

require_module_enabled('press');
require_permission('admin.access');
$t = i18n_domain_locale('admin_press');

set_page_meta([
    'title' => (string) $t['layout'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'contact') {
            $stmt = db()->prepare('INSERT INTO press_contacts (full_name, role_label, email, phone, notes, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                trim((string) ($_POST['full_name'] ?? '')),
                trim((string) ($_POST['role_label'] ?? '')),
                trim((string) ($_POST['email'] ?? '')),
                trim((string) ($_POST['phone'] ?? '')),
                trim((string) ($_POST['notes'] ?? '')),
                (int) ($_POST['sort_order'] ?? 100),
                1,
            ]);
            set_flash('success', (string) $t['contact_added']);
        } elseif ($action === 'release') {
            $filePath = null;
            if (isset($_FILES['pdf']) && ($_FILES['pdf']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $prefix = slugify((string) ($_POST['title'] ?? 'communique'));
                $savedFile = secure_move_uploaded_file(
                    $_FILES['pdf'],
                    dirname(__DIR__) . '/storage/press',
                    $prefix !== '' ? $prefix : 'communique',
                    ['pdf'],
                    ['application/pdf'],
                    10 * 1024 * 1024
                );
                $filePath = 'storage/press/' . $savedFile;
            }
            $stmt = db()->prepare('INSERT INTO press_releases (title, summary, published_on, file_path, is_published) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                trim((string) ($_POST['title'] ?? '')),
                trim((string) ($_POST['summary'] ?? '')),
                (string) ($_POST['published_on'] ?? date('Y-m-d')),
                $filePath,
                1,
            ]);
            set_flash('success', (string) $t['release_added']);
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect('admin_press');
}

$contacts = press_contacts();
$releases = latest_press_releases(50);
ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1><?= e((string) $t['contacts_title']) ?></h1>
        <form method="post" data-admin-dirty-track>
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="contact">
            <label><?= e((string) $t['full_name']) ?><input type="text" name="full_name" required></label>
            <label><?= e((string) $t['role']) ?><input type="text" name="role_label" required></label>
            <label><?= e((string) $t['email']) ?><input type="email" name="email"></label>
            <label><?= e((string) $t['phone']) ?><input type="text" name="phone"></label>
            <label><?= e((string) $t['notes']) ?><textarea name="notes" rows="3"></textarea></label>
            <label><?= e((string) $t['order']) ?><input type="text" name="sort_order" value="100"></label>
            <button class="button"><?= e((string) $t['add']) ?></button>
        </form>
        <div class="inner-card table-wrap">
            <table>
                <thead><tr><th><?= e((string) $t['th_name']) ?></th><th><?= e((string) $t['th_role']) ?></th><th><?= e((string) $t['th_contact']) ?></th></tr></thead>
                <tbody>
                <?php foreach ($contacts as $contact): ?>
                    <tr><td><?= e((string) $contact['full_name']) ?></td><td><?= e((string) $contact['role_label']) ?></td><td><?= e((string) $contact['email']) ?> <?= e((string) $contact['phone']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="card">
        <h1><?= e((string) $t['releases_title']) ?></h1>
        <form method="post" enctype="multipart/form-data" data-admin-dirty-track>
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="release">
            <label><?= e((string) $t['title']) ?><input type="text" name="title" required></label>
            <label><?= e((string) $t['summary']) ?><textarea name="summary" rows="3"></textarea></label>
            <label><?= e((string) $t['publish_date']) ?><input type="date" name="published_on" value="<?= e(date('Y-m-d')) ?>"></label>
            <label><?= e((string) $t['pdf_doc']) ?><input type="file" name="pdf" accept="application/pdf,.pdf"></label>
            <button class="button"><?= e((string) $t['add']) ?></button>
        </form>
        <div class="inner-card table-wrap">
            <table>
                <thead><tr><th><?= e((string) $t['th_date']) ?></th><th><?= e((string) $t['title']) ?></th><th><?= e((string) $t['th_file']) ?></th></tr></thead>
                <tbody>
                <?php foreach ($releases as $release): ?>
                    <tr><td><?= e((string) $release['published_on']) ?></td><td><?= e((string) $release['title']) ?></td><td><?php if ($release['file_path']): ?><a href="<?= e(base_url((string) safe_storage_public_path_or_null((string) $release['file_path'], ['storage/press/']))) ?>" target="_blank" rel="noopener"><?= e((string) $t['open']) ?></a><?php endif; ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['layout']);
