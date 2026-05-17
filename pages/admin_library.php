<?php
declare(strict_types=1);

require_permission('admin.access');
$user = current_user();
$locale = current_locale();
$i18n = [
    'fr' => ['title' => 'Administration bibliothèque', 'intro' => 'Ajoutez et consultez les documents PDF de la bibliothèque membres.', 'category_ph' => 'Catégorie', 'categories' => 'Gestion des catégories', 'existing_categories' => 'Catégories existantes', 'add_category' => 'Ajouter la catégorie', 'delete' => 'Supprimer', 'title_ph' => 'Titre', 'desc_ph' => 'Résumé / mots-clés', 'upload' => 'Uploader', 'open' => 'Ouvrir le PDF', 'preview' => 'Aperçu intégré', 'prev' => 'Précédent', 'next' => 'Suivant', 'page' => 'Page', 'err_required' => 'Titre et PDF requis.', 'err_invalid' => 'Le fichier doit être un PDF valide.', 'err_size' => 'Le fichier PDF dépasse la limite autorisée (15 Mo).', 'err_upload' => 'Le téléversement du PDF a échoué.', 'ok_added' => 'Document ajouté à la bibliothèque membres.', 'storage_unavailable' => 'La bibliothèque est temporairement indisponible.', 'meta_desc' => 'Administration de la bibliothèque membres ON4CRD.'],
    'en' => ['title' => 'Library administration', 'intro' => 'Add and browse member library PDF documents.', 'category_ph' => 'Category', 'categories' => 'Category management', 'existing_categories' => 'Existing categories', 'add_category' => 'Add category', 'delete' => 'Delete', 'title_ph' => 'Title', 'desc_ph' => 'Summary / keywords', 'upload' => 'Upload', 'open' => 'Open PDF', 'preview' => 'Embedded preview', 'prev' => 'Previous', 'next' => 'Next', 'page' => 'Page', 'err_required' => 'Title and PDF are required.', 'err_invalid' => 'The uploaded file must be a valid PDF.', 'err_size' => 'PDF file is too large (15 MB max).', 'err_upload' => 'PDF upload failed.', 'ok_added' => 'Document added to the members library.', 'storage_unavailable' => 'The library is temporarily unavailable.', 'meta_desc' => 'Administration for ON4CRD members library.'],
    'de' => ['title' => 'Bibliotheksverwaltung', 'intro' => 'PDF-Dokumente der Mitgliederbibliothek hinzufügen und einsehen.', 'category_ph' => 'Kategorie', 'categories' => 'Kategorienverwaltung', 'existing_categories' => 'Vorhandene Kategorien', 'add_category' => 'Kategorie hinzufügen', 'delete' => 'Löschen', 'title_ph' => 'Titel', 'desc_ph' => 'Zusammenfassung / Schlüsselwörter', 'upload' => 'Hochladen', 'open' => 'PDF öffnen', 'preview' => 'Eingebettete Vorschau', 'prev' => 'Zurück', 'next' => 'Weiter', 'page' => 'Seite', 'err_required' => 'Titel und PDF sind erforderlich.', 'err_invalid' => 'Die Datei muss ein gültiges PDF sein.', 'err_size' => 'PDF-Datei ist zu groß (max. 15 MB).', 'err_upload' => 'PDF-Upload fehlgeschlagen.', 'ok_added' => 'Dokument zur Mitgliederbibliothek hinzugefügt.', 'storage_unavailable' => 'Die Bibliothek ist vorübergehend nicht verfügbar.', 'meta_desc' => 'Verwaltung der ON4CRD-Mitgliederbibliothek.'],
    'nl' => ['title' => 'Bibliotheekbeheer', 'intro' => 'Voeg PDF-documenten toe aan de ledenbibliotheek en bekijk ze.', 'category_ph' => 'Categorie', 'categories' => 'Categoriebeheer', 'existing_categories' => 'Bestaande categorieën', 'add_category' => 'Categorie toevoegen', 'delete' => 'Verwijderen', 'title_ph' => 'Titel', 'desc_ph' => 'Samenvatting / sleutelwoorden', 'upload' => 'Uploaden', 'open' => 'PDF openen', 'preview' => 'Ingesloten voorbeeld', 'prev' => 'Vorige', 'next' => 'Volgende', 'page' => 'Pagina', 'err_required' => 'Titel en PDF zijn verplicht.', 'err_invalid' => 'Het bestand moet een geldige PDF zijn.', 'err_size' => 'PDF-bestand is te groot (max. 15 MB).', 'err_upload' => 'PDF-upload mislukt.', 'ok_added' => 'Document toegevoegd aan de ledenbibliotheek.', 'storage_unavailable' => 'De bibliotheek is tijdelijk niet beschikbaar.', 'meta_desc' => 'Beheer van de ON4CRD-ledenbibliotheek.'],
    'pt' => ['title' => 'Administração da biblioteca', 'intro' => 'Adicione e consulte documentos PDF da biblioteca de membros.', 'category_ph' => 'Categoria', 'categories' => 'Gestão de categorias', 'existing_categories' => 'Categorias existentes', 'add_category' => 'Adicionar categoria', 'delete' => 'Eliminar', 'title_ph' => 'Título', 'desc_ph' => 'Resumo / palavras-chave', 'upload' => 'Carregar', 'open' => 'Abrir PDF', 'preview' => 'Pré-visualização incorporada', 'prev' => 'Anterior', 'next' => 'Seguinte', 'page' => 'Página', 'err_required' => 'Título e PDF são obrigatórios.', 'err_invalid' => 'O ficheiro deve ser um PDF válido.', 'err_size' => 'O ficheiro PDF excede o limite permitido (15 MB).', 'err_upload' => 'Falha no carregamento do PDF.', 'ok_added' => 'Documento adicionado à biblioteca de membros.', 'storage_unavailable' => 'A biblioteca está temporariamente indisponível.', 'meta_desc' => 'Administração da biblioteca de membros ON4CRD.'],
    'it' => ['title' => 'Amministrazione biblioteca', 'intro' => 'Aggiungi e consulta documenti PDF della biblioteca membri.', 'category_ph' => 'Categoria', 'categories' => 'Gestione categorie', 'existing_categories' => 'Categorie esistenti', 'add_category' => 'Aggiungi categoria', 'delete' => 'Elimina', 'title_ph' => 'Titolo', 'desc_ph' => 'Riepilogo / parole chiave', 'upload' => 'Carica', 'open' => 'Apri PDF', 'preview' => 'Anteprima integrata', 'prev' => 'Precedente', 'next' => 'Successivo', 'page' => 'Pagina', 'err_required' => 'Titolo e PDF sono obbligatori.', 'err_invalid' => 'Il file deve essere un PDF valido.', 'err_size' => 'Il file PDF supera il limite consentito (15 MB).', 'err_upload' => 'Caricamento PDF non riuscito.', 'ok_added' => 'Documento aggiunto alla biblioteca membri.', 'storage_unavailable' => 'La biblioteca è temporaneamente non disponibile.', 'meta_desc' => 'Amministrazione della biblioteca membri ON4CRD.'],
    'es' => ['title' => 'Administración de biblioteca', 'intro' => 'Añade y consulta documentos PDF de la biblioteca de miembros.', 'category_ph' => 'Categoría', 'categories' => 'Gestión de categorías', 'existing_categories' => 'Categorías existentes', 'add_category' => 'Añadir categoría', 'delete' => 'Eliminar', 'title_ph' => 'Título', 'desc_ph' => 'Resumen / palabras clave', 'upload' => 'Subir', 'open' => 'Abrir PDF', 'preview' => 'Vista previa integrada', 'prev' => 'Anterior', 'next' => 'Siguiente', 'page' => 'Página', 'err_required' => 'El título y el PDF son obligatorios.', 'err_invalid' => 'El archivo debe ser un PDF válido.', 'err_size' => 'El archivo PDF supera el límite permitido (15 MB).', 'err_upload' => 'La subida del PDF ha fallado.', 'ok_added' => 'Documento añadido a la biblioteca de miembros.', 'storage_unavailable' => 'La biblioteca no está disponible temporalmente.', 'meta_desc' => 'Administración de la biblioteca de miembros ON4CRD.'],
];
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $t[$key] = i18n_localized_value($i18n, $locale, (string) $key);
}
set_page_meta(['title' => (string) $t['title'], 'description' => (string) $t['meta_desc']]);

try { db()->exec('CREATE TABLE IF NOT EXISTS member_library_categories (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(120) NOT NULL UNIQUE, label VARCHAR(160) NOT NULL, sort_order INT NOT NULL DEFAULT 100, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)'); } catch (Throwable) {}

if (!ensure_member_library_table()) {
    echo render_layout('<div class="card"><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['title']);
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'upload');
    if ($action === 'add_category') {
        $code = trim((string) ($_POST['category_code'] ?? ''));
        if ($code !== '') { db()->prepare('INSERT IGNORE INTO member_library_categories (code, label) VALUES (?, ?)')->execute([$code, $code]); }
        redirect('admin_library');
    }
    if ($action === 'delete_category') {
        $code = trim((string) ($_POST['category_code'] ?? ''));
        if ($code !== '' && $code !== 'general') {
            db()->prepare('UPDATE member_library_documents SET category = "general" WHERE category = ?')->execute([$code]);
            db()->prepare('DELETE FROM member_library_categories WHERE code = ? LIMIT 1')->execute([$code]);
        }
        redirect('admin_library');
    }

    $category = trim((string) ($_POST['category'] ?? '')) ?: 'general';
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
    if ($tmpName !== '') { if (class_exists('finfo')) { $finfo = new finfo(FILEINFO_MIME_TYPE); $mime = (string) ($finfo->file($tmpName) ?: ''); } if ($mime === '') { $mime = (string) @mime_content_type($tmpName); } }
    $pdfSignature = '';
    if ($tmpName !== '' && is_readable($tmpName)) {
        $handle = fopen($tmpName, 'rb');
        if (is_resource($handle)) {
            $pdfSignature = (string) fread($handle, 5);
            fclose($handle);
        }
    }
    if ($extension !== 'pdf' || ($mime !== 'application/pdf' && $mime !== 'application/x-pdf') || $pdfSignature !== '%PDF-') { set_flash('error', (string) $t['err_invalid']); redirect('admin_library'); }

    try { $saved = secure_move_uploaded_file($file, dirname(__DIR__) . '/storage/uploads/library', 'doc_' . (int) ($user['id'] ?? 0), ['pdf'], ['application/pdf', 'application/x-pdf'], 15 * 1024 * 1024); }
    catch (Throwable) { set_flash('error', (string) $t['err_upload']); redirect('admin_library'); }

    $publicPath = 'storage/uploads/library/' . $saved;
    db()->prepare('INSERT INTO member_library_documents (member_id, category, title, description, file_path, extracted_text) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([(int) ($user['id'] ?? 0), $category, $title, $description, $publicPath, '']);
    set_flash('success', (string) $t['ok_added']);
    redirect('admin_library');
}

$categoryOptions = db()->query('SELECT code, label FROM member_library_categories ORDER BY sort_order ASC, label ASC')->fetchAll() ?: [];
if ($categoryOptions === []) {
    db()->prepare('INSERT IGNORE INTO member_library_categories (code, label, sort_order) VALUES (?, ?, ?)')->execute(['general', 'general', 1]);
    $categoryOptions = db()->query('SELECT code, label FROM member_library_categories ORDER BY sort_order ASC, label ASC')->fetchAll() ?: [];
}
$perPage = 20;
$page = max(1, (int) ($_GET['p'] ?? 1));
$offset = ($page - 1) * $perPage;
$totalDocuments = (int) (db()->query('SELECT COUNT(*) FROM member_library_documents')->fetchColumn() ?: 0);
$totalPages = max(1, (int) ceil($totalDocuments / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}
$stmt = db()->prepare('SELECT category, title, description, file_path FROM member_library_documents ORDER BY uploaded_at DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$documents = $stmt->fetchAll() ?: [];
$prevPage = $page > 1 ? $page - 1 : null;
$nextPage = $page < $totalPages ? $page + 1 : null;
ob_start();
?>
<div class="card admin-library-shell">
    <header class="admin-library-header">
        <h1><?= e((string) $t['title']) ?></h1>
        <p><?= e((string) $t['intro']) ?></p>
        <div class="admin-library-meta">
            <span class="badge muted"><?= e((string) $t['page']) ?> <?= e((string) $page) ?> / <?= e((string) $totalPages) ?></span>
            <span class="badge muted"><?= e((string) $totalDocuments) ?> docs</span>
        </div>
    </header>
    <form method="post" enctype="multipart/form-data" class="admin-library-upload-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="upload">
        <div class="admin-library-upload-grid">
            <label class="admin-library-field"><span><?= e((string) $t['category_ph']) ?></span><select name="category"><?php foreach ($categoryOptions as $catOpt): ?><option value="<?= e((string) $catOpt['code']) ?>"><?= e((string) $catOpt['label']) ?></option><?php endforeach; ?></select></label>
            <label class="admin-library-field"><span><?= e((string) $t['title_ph']) ?></span><input type="text" name="title" placeholder="<?= e((string) $t['title_ph']) ?>" required></label>
            <label class="admin-library-field admin-library-field-wide"><span><?= e((string) $t['desc_ph']) ?></span><textarea name="description" placeholder="<?= e((string) $t['desc_ph']) ?>"></textarea></label>
            <label class="admin-library-field"><span>PDF</span><input type="file" name="pdf" accept="application/pdf" required></label>
        </div>
        <button class="button"><?= e((string) $t['upload']) ?></button>
    </form>

    <section class="card admin-library-categories">
        <h2><?= e((string) $t['categories']) ?></h2>
        <form method="post" class="inline-form"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="add_category"><input type="text" name="category_code" placeholder="<?= e((string) $t['category_ph']) ?>"><button class="button" type="submit"><?= e((string) $t['add_category']) ?></button></form>
        <p class="help"><?= e((string) $t['existing_categories']) ?></p>
        <div class="admin-library-category-list">
            <?php foreach ($categoryOptions as $catOpt): ?>
                <form method="post" class="inline-form admin-library-category-item"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_category"><input type="hidden" name="category_code" value="<?= e((string) $catOpt['code']) ?>"><span class="badge muted"><?= e((string) $catOpt['label']) ?></span><?php if ((string) $catOpt['code'] !== 'general'): ?><button class="button secondary" type="submit"><?= e((string) $t['delete']) ?></button><?php endif; ?></form>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-library-documents">
    <?php if ($documents === []): ?>
        <article class="card admin-library-empty">
            <p><?= e((string) $t['intro']) ?></p>
        </article>
    <?php endif; ?>
    <?php foreach ($documents as $document): $safePath = safe_storage_public_path_or_null((string) ($document['file_path'] ?? ''), ['storage/uploads/library/']); if ($safePath === null) { continue; } ?>
        <article class="card admin-library-document">
            <p><span class="badge muted"><?= e((string) ($document['category'] ?? 'general')) ?></span></p>
            <h3><?= e((string) $document['title']) ?></h3>
            <p><?= e((string) ($document['description'] ?? '')) ?></p>
            <details class="admin-library-preview-toggle">
                <summary><?= e((string) $t['preview']) ?></summary>
                <iframe src="<?= e(base_url($safePath)) ?>" class="admin-library-pdf-preview" loading="lazy"></iframe>
            </details>
            <p><a class="button secondary" href="<?= e(base_url($safePath)) ?>" target="_blank" rel="noopener"><?= e((string) $t['open']) ?></a></p>
        </article>
    <?php endforeach; ?>
    </section>
    <?php if ($totalPages > 1): ?>
        <nav class="admin-library-pagination" aria-label="Pagination documents">
            <?php if ($prevPage !== null): ?><a class="button secondary" href="<?= e(route_url('admin_library', ['p' => $prevPage])) ?>">&larr; <?= e((string) $t['prev']) ?></a><?php endif; ?>
            <span class="badge muted"><?= e((string) $t['page']) ?> <?= e((string) $page) ?> / <?= e((string) $totalPages) ?></span>
            <?php if ($nextPage !== null): ?><a class="button secondary" href="<?= e(route_url('admin_library', ['p' => $nextPage])) ?>"><?= e((string) $t['next']) ?> &rarr;</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
<?php echo render_layout((string) ob_get_clean(), (string) $t['title']);
