<?php
declare(strict_types=1);

require_permission('albums.manage');
$locale = current_locale();
$i18n = [
    'fr' => ['invalid_album' => 'Album invalide.', 'uploaded_count' => 'photo(s) ajoutée(s).', 'no_photo_imported' => 'Aucune photo importée.', 'created_thumbs' => 'thumbnail(s) généré(s).', 'manage_title' => 'Gestion des albums', 'photos_editor' => 'Éditer les photos', 'page' => 'Page', 'previous' => 'Précédent', 'next' => 'Suivant', 'create_album' => 'Créer un album', 'add_photo' => 'Ajouter une photo', 'upload' => 'Téléverser', 'rebuild_thumbs' => 'Régénérer les thumbnails', 'title' => 'Titre', 'description' => 'Description', 'public_album' => 'Album public', 'photo_title' => 'Titre photo', 'caption' => 'Légende', 'files_dropzone' => 'Fichiers image (dropzone)', 'albums' => 'Albums', 'created_at' => 'Créé'],
    'en' => ['invalid_album' => 'Invalid album.', 'uploaded_count' => 'photo(s) added.', 'no_photo_imported' => 'No photo imported.', 'created_thumbs' => 'thumbnail(s) generated.', 'manage_title' => 'Album management', 'photos_editor' => 'Edit photos', 'page' => 'Page', 'previous' => 'Previous', 'next' => 'Next', 'create_album' => 'Create album', 'add_photo' => 'Add photo', 'upload' => 'Upload', 'rebuild_thumbs' => 'Rebuild thumbnails', 'title' => 'Title', 'description' => 'Description', 'public_album' => 'Public album', 'photo_title' => 'Photo title', 'caption' => 'Caption', 'files_dropzone' => 'Image files (dropzone)', 'albums' => 'Albums', 'created_at' => 'Created'],
    'de' => ['invalid_album' => 'Ungültiges Album.', 'uploaded_count' => 'Foto(s) hinzugefügt.', 'no_photo_imported' => 'Kein Foto importiert.', 'created_thumbs' => 'Thumbnail(s) erzeugt.', 'manage_title' => 'Albumverwaltung', 'photos_editor' => 'Fotos bearbeiten', 'page' => 'Seite', 'previous' => 'Zurück', 'next' => 'Weiter', 'create_album' => 'Album erstellen', 'add_photo' => 'Foto hinzufügen', 'upload' => 'Hochladen', 'rebuild_thumbs' => 'Thumbnails neu erzeugen', 'title' => 'Titel', 'description' => 'Beschreibung', 'public_album' => 'Öffentliches Album', 'photo_title' => 'Fototitel', 'caption' => 'Bildunterschrift', 'files_dropzone' => 'Bilddateien (Dropzone)', 'albums' => 'Alben', 'created_at' => 'Erstellt'],
    'nl' => ['invalid_album' => 'Ongeldig album.', 'uploaded_count' => 'foto(\'s) toegevoegd.', 'no_photo_imported' => 'Geen foto geïmporteerd.', 'created_thumbs' => 'thumbnail(s) gegenereerd.', 'manage_title' => 'Albumbeheer', 'photos_editor' => 'Foto\'s bewerken', 'page' => 'Pagina', 'previous' => 'Vorige', 'next' => 'Volgende', 'create_album' => 'Album maken', 'add_photo' => 'Foto toevoegen', 'upload' => 'Uploaden', 'rebuild_thumbs' => 'Thumbnails opnieuw opbouwen', 'title' => 'Titel', 'description' => 'Beschrijving', 'public_album' => 'Openbaar album', 'photo_title' => 'Fototitel', 'caption' => 'Bijschrift', 'files_dropzone' => 'Afbeeldingsbestanden (dropzone)', 'albums' => 'Albums', 'created_at' => 'Aangemaakt'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

function delete_album_photo_files(string $publicPath): void
{
    $absolute = dirname(__DIR__) . '/' . ltrim($publicPath, '/');
    if (is_file($absolute)) {
        @unlink($absolute);
    }
    $thumbPublic = album_thumbnail_public_path($publicPath);
    $thumbAbsolute = dirname(__DIR__) . '/' . ltrim($thumbPublic, '/');
    if (is_file($thumbAbsolute)) {
        @unlink($thumbAbsolute);
    }
}

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
            if ($albumId <= 0) {
                throw new RuntimeException((string) $t['invalid_album']);
            }
            $title = trim((string) ($_POST['title'] ?? 'Photo'));
            $caption = trim((string) ($_POST['caption'] ?? ''));
            $files = $_FILES['photos'] ?? $_FILES['photo'] ?? null;
            $insertPhotoStmt = db()->prepare('INSERT INTO album_photos (album_id, title, caption, file_path) VALUES (?, ?, ?, ?)');
            $importedCount = 0;
            if (is_array($files) && is_array($files['name'] ?? null)) {
                $total = count($files['name']);
                for ($i = 0; $i < $total; $i++) {
                    $single = ['name' => $files['name'][$i] ?? '', 'type' => $files['type'][$i] ?? '', 'tmp_name' => $files['tmp_name'][$i] ?? '', 'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE, 'size' => $files['size'][$i] ?? 0];
                    if ((int) $single['error'] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $path = handle_album_upload($single, (string) current_user()['callsign']);
                    $photoTitle = $title !== '' && $total === 1 ? $title : pathinfo((string) $single['name'], PATHINFO_FILENAME);
                    $insertPhotoStmt->execute([$albumId, $photoTitle, $caption, $path]);
                    $importedCount++;
                }
            } else {
                $path = handle_album_upload(is_array($files) ? $files : null, (string) current_user()['callsign']);
                $insertPhotoStmt->execute([$albumId, $title, $caption, $path]);
                $importedCount = 1;
            }
            $album = db()->prepare('SELECT * FROM albums WHERE id = ?');
            $album->execute([$albumId]);
            $albumRow = $album->fetch();
            if ($albumRow && (int) $albumRow['is_public'] === 1 && $importedCount > 0) {
                notify_album_webhooks([
                    'event' => 'album.photo_uploaded',
                    'album_id' => $albumId,
                    'album_title' => (string) $albumRow['title'],
                    'photo_title' => $title,
                    'photo_path' => 'batch',
                    'public_url' => route_url('album', ['id' => $albumId]),
                ]);
            }
            if ($importedCount <= 0) {
                throw new RuntimeException((string) $t['no_photo_imported']);
            }
            set_flash('success', $importedCount . ' ' . (string) $t['uploaded_count']);
            redirect('admin_albums');
        }
        if ($action === 'update_album') {
            $albumId = (int) ($_POST['album_id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $isPublic = isset($_POST['is_public']) ? 1 : 0;
            if ($albumId <= 0 || $title === '') {
                throw new RuntimeException((string) $t['invalid_album']);
            }
            db()->prepare('UPDATE albums SET title = ?, description = ?, is_public = ? WHERE id = ?')->execute([$title, $description, $isPublic, $albumId]);
            set_flash('success', 'Album mis à jour.');
            redirect('admin_albums');
        }
        if ($action === 'update_photo') {
            $photoId = (int) ($_POST['photo_id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $caption = trim((string) ($_POST['caption'] ?? ''));
            if ($photoId <= 0 || $title === '') {
                throw new RuntimeException((string) $t['invalid_album']);
            }
            db()->prepare('UPDATE album_photos SET title = ?, caption = ? WHERE id = ?')->execute([$title, $caption, $photoId]);
            set_flash('success', 'Photo mise à jour.');
            redirect('admin_albums');
        }
        if ($action === 'delete_photo') {
            $photoId = (int) ($_POST['photo_id'] ?? 0);
            if ($photoId <= 0) {
                throw new RuntimeException((string) $t['invalid_album']);
            }
            $photoStmt = db()->prepare('SELECT file_path FROM album_photos WHERE id = ?');
            $photoStmt->execute([$photoId]);
            $photoRow = $photoStmt->fetch();
            db()->prepare('DELETE FROM album_photos WHERE id = ?')->execute([$photoId]);
            if (is_array($photoRow)) {
                delete_album_photo_files((string) ($photoRow['file_path'] ?? ''));
            }
            set_flash('success', 'Photo supprimée.');
            redirect('admin_albums');
        }
        if ($action === 'rebuild_thumbnails') {
            $photoRows = db()->query('SELECT file_path FROM album_photos ORDER BY id DESC')->fetchAll() ?: [];
            $created = 0;
            foreach ($photoRows as $photoRow) {
                $thumb = create_album_thumbnail((string) ($photoRow['file_path'] ?? ''), 640, 640);
                if ($thumb !== null) {
                    $created++;
                }
            }
            set_flash('success', $created . ' ' . (string) $t['created_thumbs']);
            redirect('admin_albums');
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('admin_albums');
    }
}

$albums = cache_remember('admin_albums_list_v1', 30, static fn(): array => db()->query('SELECT a.*, COUNT(p.id) AS photo_count FROM albums a LEFT JOIN album_photos p ON p.album_id = a.id GROUP BY a.id ORDER BY a.created_at DESC')->fetchAll() ?: []);
$photosPage = max(1, (int) ($_GET['photos_page'] ?? 1));
$photosPerPage = 40;
$photosTotal = (int) cache_remember('admin_albums_photos_total_v1', 30, static fn(): int => (int) (db()->query('SELECT COUNT(*) FROM album_photos')?->fetchColumn() ?: 0));
$photosMaxPage = max(1, (int) ceil($photosTotal / $photosPerPage));
if ($photosPage > $photosMaxPage) {
    $photosPage = $photosMaxPage;
}
$photosOffset = ($photosPage - 1) * $photosPerPage;
$photos = db()->query('SELECT p.*, a.title AS album_title FROM album_photos p INNER JOIN albums a ON a.id = p.album_id ORDER BY p.id DESC LIMIT ' . (int) $photosPerPage . ' OFFSET ' . (int) $photosOffset)->fetchAll() ?: [];

ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1><?= e((string) $t['create_album']) ?></h1>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create_album">
            <label><?= e((string) $t['title']) ?>
                <input type="text" name="title" required>
            </label>
            <label><?= e((string) $t['description']) ?>
                <textarea name="description" rows="4"></textarea>
            </label>
            <label><input type="checkbox" name="is_public" checked> <?= e((string) $t['public_album']) ?></label>
            <button class="button">Créer l’album</button>
        </form>
</section>
<section class="card">
        <h2><?= e((string) $t['add_photo']) ?></h2>
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
            <label><?= e((string) $t['photo_title']) ?>
                <input type="text" name="title" required>
            </label>
            <label><?= e((string) $t['caption']) ?>
                <textarea name="caption" rows="3"></textarea>
            </label>
            <label><?= e((string) $t['files_dropzone']) ?>
                <div id="album-dropzone" class="card" style="border:2px dashed #93c5fd;padding:14px;text-align:center;cursor:pointer;">
                    Glissez-déposez vos photos ici ou cliquez pour sélectionner.
                </div>
                <input id="album-photos-input" type="file" name="photos[]" accept="image/*" multiple required style="display:none;">
            </label>
            <button class="button"><?= e((string) $t['upload']) ?></button>
        </form>
    </section>
</div>
<section class="card">
    <h2><?= e((string) $t['albums']) ?></h2>
    <form method="post" class="actions" style="margin-bottom:10px;">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="rebuild_thumbnails">
        <button class="button secondary small" type="submit"><?= e((string) $t['rebuild_thumbs']) ?></button>
    </form>
    <div class="table-wrap">
        <table>
            <thead><tr><th><?= e((string) $t['title']) ?></th><th><?= e((string) $t['public_album']) ?></th><th><?= e((string) $t['photos_editor']) ?></th><th><?= e((string) $t['created_at']) ?></th></tr></thead>
            <tbody>
            <?php foreach ($albums as $album): ?>
                <tr><td><?= e((string) $album['title']) ?></td><td><?= (int) $album['is_public'] === 1 ? 'Oui' : 'Non' ?></td><td><?= (int) $album['photo_count'] ?></td><td><?= e((string) $album['created_at']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="card">
    <h2>Éditer les albums</h2>
    <div class="stack">
        <?php foreach ($albums as $album): ?>
            <article class="article-item">
                <form method="post" class="grid-2">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_album">
                    <input type="hidden" name="album_id" value="<?= (int) $album['id'] ?>">
                    <label>Titre
                        <input type="text" name="title" value="<?= e((string) $album['title']) ?>" required>
                    </label>
                    <label><input type="checkbox" name="is_public" <?= (int) $album['is_public'] === 1 ? 'checked' : '' ?>> Album public</label>
                    <label style="grid-column:1 / -1;">Description
                        <textarea name="description" rows="3"><?= e((string) ($album['description'] ?? '')) ?></textarea>
                    </label>
                    <div><button class="button small" type="submit">Enregistrer</button></div>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<section class="card">
    <h2><?= e((string) $t['photos_editor']) ?></h2>
    <div class="stack">
        <?php foreach ($photos as $photo): ?>
            <article class="article-item">
                <p class="help">Album : <?= e((string) $photo['album_title']) ?></p>
                <form method="post" class="grid-2">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_photo">
                    <input type="hidden" name="photo_id" value="<?= (int) $photo['id'] ?>">
                    <label>Titre
                        <input type="text" name="title" value="<?= e((string) $photo['title']) ?>" required>
                    </label>
                    <label style="grid-column:1 / -1;">Légende
                        <textarea name="caption" rows="2"><?= e((string) ($photo['caption'] ?? '')) ?></textarea>
                    </label>
                    <div class="actions">
                        <button class="button small" type="submit">Mettre à jour</button>
                    </div>
                </form>
                <form method="post" style="margin-top:8px;">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete_photo">
                    <input type="hidden" name="photo_id" value="<?= (int) $photo['id'] ?>">
                    <button class="button small secondary" type="submit">Supprimer</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if ($photosMaxPage > 1): ?>
        <div class="actions mt-3">
            <?php if ($photosPage > 1): ?>
                <a class="button secondary small" href="<?= e(route_url('admin_albums', ['photos_page' => $photosPage - 1])) ?>"><?= e((string) $t['previous']) ?></a>
            <?php endif; ?>
            <span class="pill"><?= e((string) $t['page']) ?> <?= $photosPage ?> / <?= $photosMaxPage ?></span>
            <?php if ($photosPage < $photosMaxPage): ?>
                <a class="button secondary small" href="<?= e(route_url('admin_albums', ['photos_page' => $photosPage + 1])) ?>"><?= e((string) $t['next']) ?></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['manage_title']);
?>
<script nonce="<?= e(csp_nonce()) ?>">
(() => {
    const dropzone = document.querySelector('#album-dropzone');
    const input = document.querySelector('#album-photos-input');
    if (!(dropzone instanceof HTMLElement) || !(input instanceof HTMLInputElement)) {
        return;
    }
    dropzone.addEventListener('click', () => input.click());
    dropzone.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropzone.style.background = '#eff6ff';
    });
    dropzone.addEventListener('dragleave', () => {
        dropzone.style.background = '';
    });
    dropzone.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.style.background = '';
        const files = event.dataTransfer?.files;
        if (!files || files.length === 0) return;
        input.files = files;
        dropzone.textContent = files.length + ' fichier(s) prêt(s) à être importé(s).';
    });
    input.addEventListener('change', () => {
        const count = input.files?.length || 0;
        if (count > 0) {
            dropzone.textContent = count + ' fichier(s) prêt(s) à être importé(s).';
        }
    });
})();
</script>
