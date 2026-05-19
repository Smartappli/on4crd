<?php
declare(strict_types=1);

require_login();
$locale = current_locale();
$i18n = [
    'fr' => ['title' => 'Bibliothèque membres', 'intro' => 'Consultez les PDF par catégorie ou via la recherche.', 'open' => 'Ouvrir le PDF', 'empty' => 'Aucun document trouvé.', 'storage_unavailable' => 'La bibliothèque est temporairement indisponible.', 'meta_desc' => 'Consultation de la bibliothèque privée des membres ON4CRD.', 'search_ph' => 'Rechercher (titre, résumé, contenu indexé)', 'search' => 'Rechercher', 'all_categories' => 'Toutes les catégories', 'for_filters' => ' pour ces filtres'],
    'en' => ['title' => 'Members library', 'intro' => 'Browse PDFs by category or search.', 'open' => 'Open PDF', 'empty' => 'No document found.', 'storage_unavailable' => 'The library is temporarily unavailable.', 'meta_desc' => 'Browsing interface for ON4CRD private members library.', 'search_ph' => 'Search (title, summary, indexed content)', 'search' => 'Search', 'all_categories' => 'All categories', 'for_filters' => ' for these filters'],
    'de' => ['title' => 'Mitgliederbibliothek', 'intro' => 'PDFs nach Kategorie oder per Suche durchsuchen.', 'open' => 'PDF öffnen', 'empty' => 'Kein Dokument gefunden.', 'storage_unavailable' => 'Die Bibliothek ist vorübergehend nicht verfügbar.', 'meta_desc' => 'Ansichtsoberfläche für die private ON4CRD-Mitgliederbibliothek.', 'search_ph' => 'Suchen (Titel, Zusammenfassung, indexierter Inhalt)', 'search' => 'Suchen', 'all_categories' => 'Alle Kategorien', 'for_filters' => ' für diese Filter'],
    'es' => ['title' => 'Biblioteca de miembros', 'intro' => 'Consulte los PDF por categoría o mediante búsqueda.', 'open' => 'Abrir PDF', 'empty' => 'No se encontró ningún documento.', 'storage_unavailable' => 'La biblioteca no está disponible temporalmente.', 'meta_desc' => 'Interfaz de consulta de la biblioteca privada de miembros ON4CRD.', 'search_ph' => 'Buscar (título, resumen, contenido indexado)', 'search' => 'Buscar', 'all_categories' => 'Todas las categorías', 'for_filters' => ' para estos filtros'],
    'it' => ['title' => 'Biblioteca membri', 'intro' => 'Consulta i PDF per categoria o tramite ricerca.', 'open' => 'Apri PDF', 'empty' => 'Nessun documento trovato.', 'storage_unavailable' => 'La biblioteca è temporaneamente non disponibile.', 'meta_desc' => 'Interfaccia di consultazione della biblioteca privata membri ON4CRD.', 'search_ph' => 'Cerca (titolo, riepilogo, contenuto indicizzato)', 'search' => 'Cerca', 'all_categories' => 'Tutte le categorie', 'for_filters' => ' per questi filtri'],
    'pt' => ['title' => 'Biblioteca de membros', 'intro' => 'Consulte PDFs por categoria ou pesquisa.', 'open' => 'Abrir PDF', 'empty' => 'Nenhum documento encontrado.', 'storage_unavailable' => 'A biblioteca está temporariamente indisponível.', 'meta_desc' => 'Interface de consulta da biblioteca privada de membros ON4CRD.', 'search_ph' => 'Pesquisar (título, resumo, conteúdo indexado)', 'search' => 'Pesquisar', 'all_categories' => 'Todas as categorias', 'for_filters' => ' para estes filtros'],
    'nl' => ['title' => 'Ledenbibliotheek', 'intro' => 'Bekijk PDF\'s per categorie of via zoeken.', 'open' => 'PDF openen', 'empty' => 'Geen document gevonden.', 'storage_unavailable' => 'De bibliotheek is tijdelijk niet beschikbaar.', 'meta_desc' => 'Raadpleeginterface voor de private ON4CRD-ledenbibliotheek.', 'search_ph' => 'Zoeken (titel, samenvatting, geïndexeerde inhoud)', 'search' => 'Zoeken', 'all_categories' => 'Alle categorieën', 'for_filters' => ' voor deze filters'],
];
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $value = trim(i18n_localized_value($i18n, $locale, 'fr'));
    if ($value === '') {
        $value = trim((string) ($i18n['fr'][$key] ?? ''));
    }
    $t[$key] = $value;
}
set_page_meta(['title' => (string) $t['title'], 'description' => (string) $t['meta_desc'], 'robots' => 'noindex,follow']);

if (!ensure_member_library_table()) {
    echo render_layout('<div class="card"><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['title']);
    return;
}

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 120) { $search = mb_substr($search, 0, 120); }
$category = trim((string) ($_GET['category'] ?? ''));

$categories = db()->query('SELECT category, COUNT(*) AS total FROM member_library_documents GROUP BY category ORDER BY category')->fetchAll() ?: [];
$where = [];
$params = [];
if ($category !== '') { $where[] = 'category = ?'; $params[] = $category; }
if ($search !== '') {
    $where[] = '(title LIKE ? OR description LIKE ? OR extracted_text LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}
$sql = 'SELECT * FROM member_library_documents';
if ($where !== []) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY uploaded_at DESC LIMIT 150';
$stmt = db()->prepare($sql); $stmt->execute($params);
$documents = $stmt->fetchAll() ?: [];

ob_start();
?>
<div class="card">
    <h1><?= e((string) $t['title']) ?></h1>
    <p><?= e((string) $t['intro']) ?></p>
    <form method="get" class="inline-form" style="flex-wrap:wrap; margin-bottom:.8rem;">
        <input type="hidden" name="route" value="members_library">
        <select name="category">
            <option value=""><?= e((string) $t['all_categories']) ?></option>
            <?php foreach ($categories as $cat): $catName = trim((string) ($cat['category'] ?? 'general')); if ($catName === '') { $catName = 'general'; } ?>
                <option value="<?= e($catName) ?>" <?= $catName === $category ? 'selected' : '' ?>><?= e($catName) ?> (<?= (int) ($cat['total'] ?? 0) ?>)</option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
        <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
    </form>

    <?php if ($documents === []): ?>
        <p class="help"><?= e((string) $t['empty']) ?><?= ($search !== '' || $category !== '') ? e((string) $t['for_filters']) : '' ?>.</p>
    <?php endif; ?>

    <?php foreach ($documents as $document): ?>
        <?php $safePath = safe_storage_public_path_or_null((string) ($document['file_path'] ?? ''), ['storage/uploads/library/']); ?>
        <?php if ($safePath === null) { continue; } ?>
        <article class="card" style="margin-top:12px;">
            <?php $docCategory = trim((string) ($document['category'] ?? 'general')); if ($docCategory === '') { $docCategory = 'general'; } ?>
            <?php $docTitle = trim((string) ($document['title'] ?? '')); if ($docTitle === '') { $docTitle = 'Document'; } ?>
            <?php $docDescription = trim((string) ($document['description'] ?? '')); if ($docDescription === '') { $docDescription = (string) $t['intro']; } ?>
            <p><span class="badge muted"><?= e($docCategory) ?></span></p>
            <h3><?= e($docTitle) ?></h3>
            <p><?= e($docDescription) ?></p>
            <iframe src="<?= e(base_url($safePath)) ?>" style="width:100%;height:480px;border:1px solid #ccc;" loading="lazy"></iframe>
            <p><a class="button secondary" href="<?= e(base_url($safePath)) ?>" target="_blank" rel="noopener"><?= e((string) $t['open']) ?></a></p>
        </article>
    <?php endforeach; ?>
</div>
<?php echo render_layout((string) ob_get_clean(), (string) $t['title']);
