<?php
declare(strict_types=1);

require_permission('admin.access');
$user = current_user();
$locale = current_locale();
$i18n = [
    'fr' => ['title' => 'Administration bibliothèque', 'intro' => 'Ajoutez et consultez les documents PDF de la bibliothèque membres.', 'title_ph' => 'Titre', 'desc_ph' => 'Résumé / mots-clés', 'upload' => 'Uploader', 'open' => 'Ouvrir le PDF', 'err_required' => 'Titre et PDF requis.', 'err_invalid' => 'Le fichier doit être un PDF valide.', 'err_size' => 'Le fichier PDF dépasse la limite autorisée (15 Mo).', 'err_upload' => 'Le téléversement du PDF a échoué.', 'ok_added' => 'Document ajouté à la bibliothèque membres.', 'storage_unavailable' => 'La bibliothèque est temporairement indisponible.', 'meta_desc' => 'Administration de la bibliothèque membres ON4CRD.'],
    'en' => ['title' => 'Library administration', 'intro' => 'Add and browse member library PDF documents.', 'title_ph' => 'Title', 'desc_ph' => 'Summary / keywords', 'upload' => 'Upload', 'open' => 'Open PDF', 'err_required' => 'Title and PDF are required.', 'err_invalid' => 'The uploaded file must be a valid PDF.', 'err_size' => 'PDF file is too large (15 MB max).', 'err_upload' => 'PDF upload failed.', 'ok_added' => 'Document added to the members library.', 'storage_unavailable' => 'The library is temporarily unavailable.', 'meta_desc' => 'Administration for ON4CRD members library.'],
    'de' => ['title' => 'Bibliotheksverwaltung', 'intro' => 'PDF-Dokumente der Mitgliederbibliothek hinzufügen und einsehen.', 'title_ph' => 'Titel', 'desc_ph' => 'Zusammenfassung / Schlüsselwörter', 'upload' => 'Hochladen', 'open' => 'PDF öffnen', 'err_required' => 'Titel und PDF sind erforderlich.', 'err_invalid' => 'Die Datei muss ein gültiges PDF sein.', 'err_size' => 'PDF-Datei ist zu groß (max. 15 MB).', 'err_upload' => 'PDF-Upload fehlgeschlagen.', 'ok_added' => 'Dokument zur Mitgliederbibliothek hinzugefügt.', 'storage_unavailable' => 'Die Bibliothek ist vorübergehend nicht verfügbar.', 'meta_desc' => 'Verwaltung der ON4CRD-Mitgliederbibliothek.'],
    'nl' => ['title' => 'Bibliotheekbeheer', 'intro' => 'Voeg PDF-documenten toe aan de ledenbibliotheek en bekijk ze.', 'title_ph' => 'Titel', 'desc_ph' => 'Samenvatting / sleutelwoorden', 'upload' => 'Uploaden', 'open' => 'PDF openen', 'err_required' => 'Titel en PDF zijn verplicht.', 'err_invalid' => 'Het bestand moet een geldige PDF zijn.', 'err_size' => 'PDF-bestand is te groot (max. 15 MB).', 'err_upload' => 'PDF-upload mislukt.', 'ok_added' => 'Document toegevoegd aan de ledenbibliotheek.', 'storage_unavailable' => 'De bibliotheek is tijdelijk niet beschikbaar.', 'meta_desc' => 'Beheer van de ON4CRD-ledenbibliotheek.'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

set_page_meta(['title' => (string) $t['title'], 'description' => (string) $t['meta_desc']]);

if (!ensure_member_library_table()) {
    echo render_layout('<div class="card"><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['title']);
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $file = $_FILES['pdf'] ?? null;

    if ($title === '' || !is_array($file)) { set_flash('error', (string) $t['err_required']); redirect('admin_library'); }
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) { set_flash('error', (string) $t['err_upload']); redirect('admin_library'); }
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 15 * 1024 * 1024) { set_flash('error', (string) $t['err_size']); redirect('admin_library'); }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
    $mime = '';
    if ($tmpName !== '') {
        if (class_exists('finfo')) { $finfo = new finfo(FILEINFO_MIME_TYPE); $mime = (string) ($finfo->file($tmpName) ?: ''); }
        if ($mime === '') { $mime = (string) @mime_content_type($tmpName); }
    }
    if ($extension !== 'pdf' || ($mime !== 'application/pdf' && $mime !== 'application/x-pdf')) { set_flash('error', (string) $t['err_invalid']); redirect('admin_library'); }

    try {
        $saved = secure_move_uploaded_file($file, dirname(__DIR__) . '/storage/uploads/library', 'doc_' . (int) ($user['id'] ?? 0), ['pdf'], ['application/pdf', 'application/x-pdf'], 15 * 1024 * 1024);
    } catch (Throwable) {
        set_flash('error', (string) $t['err_upload']);
        redirect('admin_library');
    }

    $publicPath = 'storage/uploads/library/' . $saved;
    db()->prepare('INSERT INTO member_library_documents (member_id, title, description, file_path, extracted_text) VALUES (?, ?, ?, ?, ?)')
        ->execute([(int) ($user['id'] ?? 0), $title, $description, $publicPath, '']);

    set_flash('success', (string) $t['ok_added']);
    redirect('admin_library');
}

$documents = db()->query('SELECT * FROM member_library_documents ORDER BY uploaded_at DESC LIMIT 120')->fetchAll();
ob_start();
?>
<div class="card">
    <h1><?= e((string) $t['title']) ?></h1>
    <p><?= e((string) $t['intro']) ?></p>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="text" name="title" placeholder="<?= e((string) $t['title_ph']) ?>" required>
        <textarea name="description" placeholder="<?= e((string) $t['desc_ph']) ?>"></textarea>
        <input type="file" name="pdf" accept="application/pdf" required>
        <button class="button"><?= e((string) $t['upload']) ?></button>
    </form>

    <?php foreach ($documents as $document): ?>
        <?php $safePath = safe_storage_public_path_or_null((string) ($document['file_path'] ?? ''), ['storage/uploads/library/']); ?>
        <?php if ($safePath === null) { continue; } ?>
        <article class="card" style="margin-top:12px;">
            <h3><?= e((string) $document['title']) ?></h3>
            <p><?= e((string) ($document['description'] ?? '')) ?></p>
            <iframe src="<?= e(base_url($safePath)) ?>" style="width:100%;height:480px;border:1px solid #ccc;" loading="lazy"></iframe>
            <p><a class="button secondary" href="<?= e(base_url($safePath)) ?>" target="_blank" rel="noopener"><?= e((string) $t['open']) ?></a></p>
        </article>
    <?php endforeach; ?>
</div>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['title']);
