<?php
declare(strict_types=1);

require_login();
$locale = current_locale();
$i18n = [
    'fr' => ['title' => 'Bibliothèque membres', 'intro' => 'Consultez les PDF partagés dans la bibliothèque des membres.', 'open' => 'Ouvrir le PDF', 'empty' => 'Aucun document disponible pour le moment.', 'storage_unavailable' => 'La bibliothèque est temporairement indisponible.', 'meta_desc' => 'Consultation de la bibliothèque privée des membres ON4CRD.'],
    'en' => ['title' => 'Members library', 'intro' => 'Browse PDFs shared in the members library.', 'open' => 'Open PDF', 'empty' => 'No document available yet.', 'storage_unavailable' => 'The library is temporarily unavailable.', 'meta_desc' => 'Browsing interface for ON4CRD private members library.'],
    'de' => ['title' => 'Mitgliederbibliothek', 'intro' => 'Durchsuchen Sie die geteilten PDFs der Mitgliederbibliothek.', 'open' => 'PDF öffnen', 'empty' => 'Noch kein Dokument verfügbar.', 'storage_unavailable' => 'Die Bibliothek ist vorübergehend nicht verfügbar.', 'meta_desc' => 'Ansichtsoberfläche für die private ON4CRD-Mitgliederbibliothek.'],
    'nl' => ['title' => 'Ledenbibliotheek', 'intro' => "Bekijk gedeelde PDF's in de ledenbibliotheek.", 'open' => 'PDF openen', 'empty' => 'Nog geen documenten beschikbaar.', 'storage_unavailable' => 'De bibliotheek is tijdelijk niet beschikbaar.', 'meta_desc' => 'Raadpleeginterface voor de private ON4CRD-ledenbibliotheek.'],
];
$t = $i18n[$locale] ?? $i18n['fr'];
set_page_meta(['title' => (string) $t['title'], 'description' => (string) $t['meta_desc'], 'robots' => 'noindex,follow']);

if (!ensure_member_library_table()) {
    echo render_layout('<div class="card"><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['title']);
    return;
}

$documents = db()->query('SELECT * FROM member_library_documents ORDER BY uploaded_at DESC LIMIT 120')->fetchAll();
ob_start();
?>
<div class="card">
    <h1><?= e((string) $t['title']) ?></h1>
    <p><?= e((string) $t['intro']) ?></p>

    <?php if ($documents === []): ?>
        <p class="help"><?= e((string) $t['empty']) ?></p>
    <?php endif; ?>

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
