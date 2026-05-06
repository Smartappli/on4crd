<?php
$user = require_login();
$i18n = [
    'fr' => ['title' => 'Bibliothèque membres', 'intro' => 'Déposez des PDF et consultez-les en ligne. Ces documents alimentent aussi la base de connaissances du chatbot Raymond.', 'title_ph' => 'Titre', 'desc_ph' => 'Résumé / mots-clés', 'upload' => 'Uploader', 'open' => 'Ouvrir le PDF', 'err_required' => 'Titre et PDF requis.', 'err_invalid' => 'Le fichier doit être un PDF valide.', 'err_size' => 'Le fichier PDF dépasse la limite autorisée (15 Mo).', 'err_upload' => 'Le téléversement du PDF a échoué.', 'ok_added' => 'Document ajouté à la bibliothèque membres.', 'storage_unavailable' => 'La bibliothèque est temporairement indisponible.', 'meta_desc' => 'Bibliothèque privée des membres ON4CRD pour téléverser et consulter des PDF.'],
    'en' => ['title' => 'Members library', 'intro' => 'Upload PDFs and read them online. These files also enrich Raymond\'s knowledge base.', 'title_ph' => 'Title', 'desc_ph' => 'Summary / keywords', 'upload' => 'Upload', 'open' => 'Open PDF', 'err_required' => 'Title and PDF are required.', 'err_invalid' => 'The uploaded file must be a valid PDF.', 'err_size' => 'PDF file is too large (15 MB max).', 'err_upload' => 'PDF upload failed.', 'ok_added' => 'Document added to the members library.', 'storage_unavailable' => 'The library is temporarily unavailable.', 'meta_desc' => 'Private ON4CRD members library to upload and view PDF documents.'],
    'de' => ['title' => 'Mitgliederbibliothek', 'intro' => 'PDFs hochladen und online lesen. Diese Dokumente speisen auch Raymonds Wissensbasis.', 'title_ph' => 'Titel', 'desc_ph' => 'Zusammenfassung / Schlüsselwörter', 'upload' => 'Hochladen', 'open' => 'PDF öffnen', 'err_required' => 'Titel und PDF sind erforderlich.', 'err_invalid' => 'Die Datei muss ein gültiges PDF sein.', 'err_size' => 'PDF-Datei ist zu groß (max. 15 MB).', 'err_upload' => 'PDF-Upload fehlgeschlagen.', 'ok_added' => 'Dokument zur Mitgliederbibliothek hinzugefügt.', 'storage_unavailable' => 'Die Bibliothek ist vorübergehend nicht verfügbar.', 'meta_desc' => 'Private ON4CRD-Mitgliederbibliothek zum Hochladen und Lesen von PDF-Dokumenten.'],
    'nl' => ['title' => 'Ledenbibliotheek', 'intro' => 'Upload PDF\'s en bekijk ze online. Deze documenten verrijken ook Raymonds kennisbank.', 'title_ph' => 'Titel', 'desc_ph' => 'Samenvatting / sleutelwoorden', 'upload' => 'Uploaden', 'open' => 'PDF openen', 'err_required' => 'Titel en PDF zijn verplicht.', 'err_invalid' => 'Het bestand moet een geldige PDF zijn.', 'err_size' => 'PDF-bestand is te groot (max. 15 MB).', 'err_upload' => 'PDF-upload mislukt.', 'ok_added' => 'Document toegevoegd aan de ledenbibliotheek.', 'storage_unavailable' => 'De bibliotheek is tijdelijk niet beschikbaar.', 'meta_desc' => 'Privé ON4CRD-ledenbibliotheek om PDF-documenten te uploaden en online te bekijken.'],
];
$locale = strtolower((string) ($_SESSION['locale'] ?? 'fr'));
$t = $i18n[$locale] ?? $i18n['fr'];
set_page_meta([
    'title' => (string) $t['title'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,follow',
]);

if (!ensure_member_library_table()) {
    echo render_layout('<div class="card"><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['title']);
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $file = $_FILES['pdf'] ?? null;

    if ($title === '' || !is_array($file)) {
        set_flash('error', (string) $t['err_required']);
        redirect('members_library');
    }

    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        set_flash('error', (string) $t['err_upload']);
        redirect('members_library');
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 15 * 1024 * 1024) {
        set_flash('error', (string) $t['err_size']);
        redirect('members_library');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
    $mime = '';
    if ($tmpName !== '') {
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string) ($finfo->file($tmpName) ?: '');
        }
        if ($mime === '') {
            $mime = (string) @mime_content_type($tmpName);
        }
    }
    if ($extension !== 'pdf' || ($mime !== 'application/pdf' && $mime !== 'application/x-pdf')) {
        set_flash('error', (string) $t['err_invalid']);
        redirect('members_library');
    }

    try {
        $saved = secure_move_uploaded_file(
            $file,
            dirname(__DIR__) . '/storage/uploads/library',
            'doc_' . (int) $user['id'],
            ['pdf'],
            ['application/pdf', 'application/x-pdf'],
            15 * 1024 * 1024
        );
    } catch (Throwable) {
        set_flash('error', (string) $t['err_upload']);
        redirect('members_library');
    }
    $publicPath = 'storage/uploads/library/' . $saved;
    $absolutePath = dirname(__DIR__) . '/' . $publicPath;
    $extractedText = '';

    if (is_file($absolutePath) && trim((string) shell_exec('command -v pdftotext')) !== '') {
        $tmpPath = tempnam(sys_get_temp_dir(), 'pdf_');
        if (is_string($tmpPath) && $tmpPath !== '') {
            shell_exec('pdftotext ' . escapeshellarg($absolutePath) . ' ' . escapeshellarg($tmpPath));
            if (is_file($tmpPath)) {
                $extractedText = trim((string) file_get_contents($tmpPath));
                @unlink($tmpPath);
            }
        }
    }

    db()->prepare('INSERT INTO member_library_documents (member_id, title, description, file_path, extracted_text) VALUES (?, ?, ?, ?, ?)')
        ->execute([(int) $user['id'], $title, $description, $publicPath, $extractedText]);

    set_flash('success', (string) $t['ok_added']);
    redirect('members_library');
}

$documents = db()->query('SELECT * FROM member_library_documents ORDER BY uploaded_at DESC LIMIT 100')->fetchAll();
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
<?php echo render_layout((string) ob_get_clean(), (string) $t['title']);
