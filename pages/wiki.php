<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['title' => 'Wiki', 'unavailable' => 'Le wiki sera disponible après initialisation des pages.', 'new_pages' => 'Nouvelles pages', 'updated_pages' => 'Pages modifiées', 'most_read' => 'Les plus lues', 'new_page' => 'Nouvelle page', 'search_placeholder' => 'Rechercher une page (titre ou contenu)', 'search' => 'Rechercher', 'reset' => 'Réinitialiser', 'wiki_pages' => 'Pages du wiki', 'no_page' => 'Aucune page trouvée', 'for_search' => ' pour cette recherche', 'summary_fallback' => 'Consulter cette page pour accéder au contenu complet.', 'updated_at' => 'Mise à jour :', 'open_page' => 'Ouvrir la page'],
    'en' => ['title' => 'Wiki', 'unavailable' => 'The wiki will be available after page initialization.', 'new_pages' => 'New pages', 'updated_pages' => 'Updated pages', 'most_read' => 'Most read', 'new_page' => 'New page', 'search_placeholder' => 'Search for a page (title or content)', 'search' => 'Search', 'reset' => 'Reset', 'wiki_pages' => 'Wiki pages', 'no_page' => 'No page found', 'for_search' => ' for this search', 'summary_fallback' => 'Open this page to access the full content.', 'updated_at' => 'Updated:', 'open_page' => 'Open page'],
    'de' => ['title' => 'Wiki', 'unavailable' => 'Das Wiki ist nach der Initialisierung der Seiten verfügbar.', 'new_pages' => 'Neue Seiten', 'updated_pages' => 'Aktualisierte Seiten', 'most_read' => 'Am meisten gelesen', 'new_page' => 'Neue Seite', 'search_placeholder' => 'Seite suchen (Titel oder Inhalt)', 'search' => 'Suchen', 'reset' => 'Zurücksetzen', 'wiki_pages' => 'Wiki-Seiten', 'no_page' => 'Keine Seite gefunden', 'for_search' => ' für diese Suche', 'summary_fallback' => 'Öffnen Sie diese Seite, um den vollständigen Inhalt zu sehen.', 'updated_at' => 'Aktualisiert:', 'open_page' => 'Seite öffnen'],
    'nl' => ['title' => 'Wiki', 'unavailable' => "De wiki is beschikbaar na initialisatie van de pagina's.", 'new_pages' => "Nieuwe pagina's", 'updated_pages' => "Bijgewerkte pagina's", 'most_read' => 'Meest gelezen', 'new_page' => 'Nieuwe pagina', 'search_placeholder' => 'Zoek een pagina (titel of inhoud)', 'search' => 'Zoeken', 'reset' => 'Reset', 'wiki_pages' => "Wiki-pagina's", 'no_page' => 'Geen pagina gevonden', 'for_search' => ' voor deze zoekopdracht', 'summary_fallback' => 'Open deze pagina om de volledige inhoud te bekijken.', 'updated_at' => 'Bijgewerkt:', 'open_page' => 'Pagina openen'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

if (!table_exists('wiki_pages')) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['title']) . '</h1><p>' . e((string) $t['unavailable']) . '</p></div>', (string) $t['title']);
    return;
}

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 120) {
    $search = mb_substr($search, 0, 120);
}

$rows = [];
try {
    if ($search === '') {
        $stmt = db()->query('SELECT slug, title, content, updated_at FROM wiki_pages ORDER BY updated_at DESC LIMIT 120');
        $rows = $stmt->fetchAll() ?: [];
    } else {
        $stmt = db()->prepare('SELECT slug, title, content, updated_at FROM wiki_pages WHERE title LIKE ? OR content LIKE ? ORDER BY updated_at DESC LIMIT 120');
        $like = '%' . $search . '%';
        $stmt->execute([$like, $like]);
        $rows = $stmt->fetchAll() ?: [];
    }
} catch (Throwable) {
    $rows = [];
}

ob_start();
?>
<div class="stack">
    <section class="card wiki-header">
        <div class="stats-grid">
            <article class="stat-card">
                <strong><?= e((string) $t['new_pages']) ?></strong>
            </article>
            <article class="stat-card">
                <strong><?= e((string) $t['updated_pages']) ?></strong>
            </article>
            <article class="stat-card">
                <strong><?= e((string) $t['most_read']) ?></strong>
            </article>
        </div>
        <?php if (has_permission('wiki.edit')): ?>
            <p><a class="button small" href="<?= e(base_url('index.php?route=wiki_edit')) ?>"><?= e((string) $t['new_page']) ?></a></p>
        <?php endif; ?>
        <form method="get" class="inline-form">
            <input type="hidden" name="route" value="wiki">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_placeholder']) ?>">
            <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
            <?php if ($search !== ''): ?>
                <a class="button secondary" href="<?= e(route_url('wiki')) ?>"><?= e((string) $t['reset']) ?></a>
            <?php endif; ?>
        </form>
    </section>

    <section class="card">
        <h2><?= e((string) $t['wiki_pages']) ?></h2>
        <?php if ($rows === []): ?>
            <p><?= e((string) $t['no_page']) ?><?= $search !== '' ? e((string) $t['for_search']) : '' ?>.</p>
        <?php else: ?>
            <div class="wiki-grid">
                <?php foreach ($rows as $row):
                    $summary = trim(strip_tags((string) ($row['content'] ?? '')));
                    if ($summary === '') {
                        $summary = (string) $t['summary_fallback'];
                    }
                    if (mb_strlen($summary) > 190) {
                        $summary = mb_substr($summary, 0, 187) . '…';
                    }
                    ?>
                    <article class="wiki-card">
                        <h3><a href="<?= e(base_url('index.php?route=wiki_view&slug=' . urlencode((string) $row['slug']))) ?>"><?= e((string) $row['title']) ?></a></h3>
                        <p class="help"><?= e((string) $t['updated_at']) ?> <?= e(date('d/m/Y H:i', strtotime((string) $row['updated_at']))) ?></p>
                        <p><?= e($summary) ?></p>
                        <p><a class="button secondary" href="<?= e(base_url('index.php?route=wiki_view&slug=' . urlencode((string) $row['slug']))) ?>"><?= e((string) $t['open_page']) ?></a></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['title']);
