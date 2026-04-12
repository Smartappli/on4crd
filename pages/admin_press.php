<?php
declare(strict_types=1);

require_permission('admin.access');

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
            set_flash('success', 'Contact presse ajouté.');
        } elseif ($action === 'release') {
            $filePath = null;
            if (isset($_FILES['pdf']) && ($_FILES['pdf']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo((string) $_FILES['pdf']['name'], PATHINFO_EXTENSION));
                $mime = mime_content_type((string) $_FILES['pdf']['tmp_name']) ?: '';
                if ($ext !== 'pdf' || !in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
                    throw new RuntimeException('Seuls les fichiers PDF valides sont autorisés.');
                }
                $name = 'storage/press/' . slugify((string) ($_POST['title'] ?? 'communique')) . '-' . date('YmdHis') . '.pdf';
                $target = dirname(__DIR__) . '/' . $name;
                if (!move_uploaded_file((string) $_FILES['pdf']['tmp_name'], $target)) {
                    throw new RuntimeException('Impossible de déplacer le fichier téléversé.');
                }
                $filePath = $name;
            }
            $stmt = db()->prepare('INSERT INTO press_releases (title, summary, published_on, file_path, is_published) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                trim((string) ($_POST['title'] ?? '')),
                trim((string) ($_POST['summary'] ?? '')),
                (string) ($_POST['published_on'] ?? date('Y-m-d')),
                $filePath,
                1,
            ]);
            set_flash('success', 'Communiqué ajouté.');
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
        <h1>Contacts presse</h1>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="contact">
            <label>Nom complet<input type="text" name="full_name" required></label>
            <label>Rôle<input type="text" name="role_label" required></label>
            <label>Email<input type="email" name="email"></label>
            <label>Téléphone<input type="text" name="phone"></label>
            <label>Notes<textarea name="notes" rows="3"></textarea></label>
            <label>Ordre<input type="text" name="sort_order" value="100"></label>
            <button class="button">Ajouter</button>
        </form>
        <div class="inner-card table-wrap">
            <table>
                <thead><tr><th>Nom</th><th>Rôle</th><th>Contact</th></tr></thead>
                <tbody>
                <?php foreach ($contacts as $contact): ?>
                    <tr><td><?= e((string) $contact['full_name']) ?></td><td><?= e((string) $contact['role_label']) ?></td><td><?= e((string) $contact['email']) ?> <?= e((string) $contact['phone']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="card">
        <h1>Communiqués</h1>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="release">
            <label>Titre<input type="text" name="title" required></label>
            <label>Résumé<textarea name="summary" rows="3"></textarea></label>
            <label>Date de publication<input type="date" name="published_on" value="<?= e(date('Y-m-d')) ?>"></label>
            <label>Document PDF<input type="file" name="pdf" accept="application/pdf,.pdf"></label>
            <button class="button">Ajouter</button>
        </form>
        <div class="inner-card table-wrap">
            <table>
                <thead><tr><th>Date</th><th>Titre</th><th>Fichier</th></tr></thead>
                <tbody>
                <?php foreach ($releases as $release): ?>
                    <tr><td><?= e((string) $release['published_on']) ?></td><td><?= e((string) $release['title']) ?></td><td><?php if ($release['file_path']): ?><a href="<?= e(base_url((string) safe_storage_public_path_or_null((string) $release['file_path'], ['storage/press/']))) ?>" target="_blank" rel="noopener">ouvrir</a><?php endif; ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php

echo render_layout((string) ob_get_clean(), 'Presse');
