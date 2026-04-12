<?php
declare(strict_types=1);

require_permission('albums.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'create_album') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $isPublic = isset($_POST['is_public']) ? 1 : 0;
            db()->prepare('INSERT INTO albums (title, description, is_public) VALUES (?, ?, ?)')->execute([$title, $description, $isPublic]);
            set_flash('success', 'Album créé.');
            redirect('admin_albums');
        }

        if ($action === 'upload_photo') {
            $albumId = (int) ($_POST['album_id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $caption = trim((string) ($_POST['caption'] ?? ''));
            $path = handle_album_upload($_FILES['photo'] ?? null, (string) current_user()['callsign']);
            db()->prepare('INSERT INTO album_photos (album_id, title, caption, file_path) VALUES (?, ?, ?, ?)')->execute([$albumId, $title, $caption, $path]);
            $album = db()->prepare('SELECT * FROM albums WHERE id = ?');
            $album->execute([$albumId]);
            $albumRow = $album->fetch();
            if ($albumRow && (int) $albumRow['is_public'] === 1) {
                notify_album_webhooks([
                    'event' => 'album.photo_uploaded',
                    'album_id' => $albumId,
                    'album_title' => (string) $albumRow['title'],
                    'photo_title' => $title,
                    'photo_path' => $path,
                    'public_url' => base_url($path),
                ]);
            }
            set_flash('success', 'Photo ajoutée.');
            redirect('admin_albums');
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('admin_albums');
    }
}

$albums = db()->query('SELECT a.*, COUNT(p.id) AS photo_count FROM albums a LEFT JOIN album_photos p ON p.album_id = a.id GROUP BY a.id ORDER BY a.created_at DESC')->fetchAll();

ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1>Créer un album</h1>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create_album">
            <label>Titre
                <input type="text" name="title" required>
            </label>
            <label>Description
                <textarea name="description" rows="4"></textarea>
            </label>
            <label><input type="checkbox" name="is_public" checked> Album public</label>
            <button class="button">Créer l’album</button>
        </form>
    </section>
    <section class="card">
        <h2>Ajouter une photo</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="upload_photo">
            <label>Album
                <select name="album_id" required>
                    <?php foreach ($albums as $album): ?>
                        <option value="<?= (int) $album['id'] ?>"><?= e((string) $album['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Titre photo
                <input type="text" name="title" required>
            </label>
            <label>Légende
                <textarea name="caption" rows="3"></textarea>
            </label>
            <label>Fichier image
                <input type="file" name="photo" accept="image/*" required>
            </label>
            <button class="button">Téléverser</button>
        </form>
    </section>
</div>
<section class="card">
    <h2>Albums</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Titre</th><th>Public</th><th>Photos</th><th>Créé</th></tr></thead>
            <tbody>
            <?php foreach ($albums as $album): ?>
                <tr><td><?= e((string) $album['title']) ?></td><td><?= (int) $album['is_public'] === 1 ? 'Oui' : 'Non' ?></td><td><?= (int) $album['photo_count'] ?></td><td><?= e((string) $album['created_at']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php

echo render_layout((string) ob_get_clean(), 'Gestion des albums');
