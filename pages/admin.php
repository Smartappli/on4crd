<?php
declare(strict_types=1);

require_permission('admin.access');
$locale = current_locale();
$i18n = [
    'fr' => ['layout' => 'Administration', 'title' => 'Administration centralisée', 'lead' => 'Tous les modules et outils d’administration sont regroupés dans ce tableau de bord unique.', 'search_label' => 'Recherche rapide', 'search_placeholder' => 'Module, outil, description…', 'search_cta' => 'Filtrer', 'search_reset' => 'Réinitialiser', 'empty' => 'Aucun module ne correspond à la recherche.'],
    'en' => ['layout' => 'Administration', 'title' => 'Centralized administration', 'lead' => 'All admin modules and tools are grouped in this single dashboard.', 'search_label' => 'Quick search', 'search_placeholder' => 'Module, tool, description…', 'search_cta' => 'Filter', 'search_reset' => 'Reset', 'empty' => 'No module matches your search.'],
    'de' => ['layout' => 'Verwaltung', 'title' => 'Zentralisierte Verwaltung', 'lead' => 'Alle Verwaltungs-Module und Werkzeuge sind in diesem einzigen Dashboard gebündelt.', 'search_label' => 'Schnellsuche', 'search_placeholder' => 'Modul, Werkzeug, Beschreibung…', 'search_cta' => 'Filtern', 'search_reset' => 'Zurücksetzen', 'empty' => 'Kein Modul entspricht Ihrer Suche.'],
    'nl' => ['layout' => 'Beheer', 'title' => 'Gecentraliseerd beheer', 'lead' => 'Alle beheermodules en tools zijn gegroepeerd in dit ene dashboard.', 'search_label' => 'Snel zoeken', 'search_placeholder' => 'Module, tool, beschrijving…', 'search_cta' => 'Filteren', 'search_reset' => 'Reset', 'empty' => 'Geen module komt overeen met je zoekopdracht.'],
];
$t = $i18n[$locale] ?? $i18n['fr'];


$adminSearch = trim((string) ($_GET['q'] ?? ''));
$adminSearchNeedle = $adminSearch !== '' ? mb_safe_strtolower($adminSearch) : '';

/**
 * @return array<int, array{route:string,title:array{fr:string,en:string,de:string,nl:string},desc:array{fr:string,en:string,de:string,nl:string},module?:string,permission?:string}>
 */
function admin_module_cards(): array
{
    return [
        ['route' => 'admin_modules', 'title' => ['fr' => 'Modules', 'en' => 'Modules', 'de' => 'Module', 'nl' => 'Modules'], 'desc' => ['fr' => 'Activation, désactivation et pilotage global des modules.', 'en' => 'Enable, disable and globally manage modules.', 'de' => 'Module aktivieren, deaktivieren und zentral steuern.', 'nl' => 'Modules activeren, deactiveren en centraal beheren.'], 'permission' => 'modules.manage'],
        ['route' => 'admin_permissions', 'title' => ['fr' => 'Permissions', 'en' => 'Permissions', 'de' => 'Berechtigungen', 'nl' => 'Rechten'], 'desc' => ['fr' => 'Rôles, droits et affectations.', 'en' => 'Roles, rights and assignments.', 'de' => 'Rollen, Rechte und Zuweisungen.', 'nl' => 'Rollen, rechten en toewijzingen.']],
        ['route' => 'admin_news', 'title' => ['fr' => 'Actualités', 'en' => 'News', 'de' => 'Neuigkeiten', 'nl' => 'Nieuws'], 'desc' => ['fr' => 'Sections, rédaction et modération.', 'en' => 'Sections, writing and moderation.', 'de' => 'Bereiche, Redaktion und Moderation.', 'nl' => 'Secties, redactie en moderatie.'], 'module' => 'news'],
        ['route' => 'admin_articles', 'title' => ['fr' => 'Articles', 'en' => 'Articles', 'de' => 'Artikel', 'nl' => 'Artikels'], 'desc' => ['fr' => 'Articles techniques publics.', 'en' => 'Public technical articles.', 'de' => 'Öffentliche technische Artikel.', 'nl' => 'Publieke technische artikels.'], 'module' => 'articles'],
        ['route' => 'admin_committee', 'title' => ['fr' => 'Comité', 'en' => 'Committee', 'de' => 'Komitee', 'nl' => 'Comité'], 'desc' => ['fr' => 'Membres du comité, rôle, ordre et biographie.', 'en' => 'Committee members, role, order and biography.', 'de' => 'Komiteemitglieder, Rolle, Reihenfolge und Biografie.', 'nl' => 'Comitéleden, rol, volgorde en biografie.'], 'module' => 'committee'],
        ['route' => 'admin_press', 'title' => ['fr' => 'Presse', 'en' => 'Press', 'de' => 'Presse', 'nl' => 'Pers'], 'desc' => ['fr' => 'Contacts presse, communiqués datés et documents téléchargeables.', 'en' => 'Press contacts, dated releases and downloadable documents.', 'de' => 'Pressekontakte, datierte Mitteilungen und Downloads.', 'nl' => 'Perscontacten, gedateerde berichten en downloads.'], 'module' => 'press'],
        ['route' => 'admin_events', 'title' => ['fr' => 'Agenda', 'en' => 'Agenda', 'de' => 'Agenda', 'nl' => 'Agenda'], 'desc' => ['fr' => 'Événements du club et contests locaux affichés dans les widgets live.', 'en' => 'Club events and local contests shown in live widgets.', 'de' => 'Clubveranstaltungen und lokale Contests in Live-Widgets.', 'nl' => 'Clubevenementen en lokale contests in live widgets.'], 'module' => 'events'],
        ['route' => 'admin_dinner_reservations', 'title' => ['fr' => 'Dîner annuel', 'en' => 'Annual dinner', 'de' => 'Jahresessen', 'nl' => 'Jaarlijks diner'], 'desc' => ['fr' => 'Réservations, lignes repas/dessert, quantités et total automatique.', 'en' => 'Reservations, meal/dessert lines, quantities and auto total.', 'de' => 'Reservierungen, Menüzeilen, Mengen und automatische Summe.', 'nl' => 'Reservaties, maaltijdregels, aantallen en automatisch totaal.'], 'module' => 'events', 'permission' => 'events.manage'],
        ['route' => 'admin_shop', 'title' => ['fr' => 'Boutique', 'en' => 'Shop', 'de' => 'Shop', 'nl' => 'Winkel'], 'desc' => ['fr' => 'Catalogue produits, catégories et commandes club.', 'en' => 'Product catalog, categories and club orders.', 'de' => 'Produktkatalog, Kategorien und Clubbestellungen.', 'nl' => 'Productcatalogus, categorieën en clubbestellingen.'], 'module' => 'shop', 'permission' => 'shop.manage'],
        ['route' => 'admin_auctions', 'title' => ['fr' => 'Enchères', 'en' => 'Auctions', 'de' => 'Auktionen', 'nl' => 'Veilingen'], 'desc' => ['fr' => 'Lots, planification, offres et clôture.', 'en' => 'Lots, scheduling, bids and closing.', 'de' => 'Lose, Planung, Gebote und Abschluss.', 'nl' => 'Kavels, planning, biedingen en afsluiting.'], 'module' => 'auctions', 'permission' => 'auctions.manage'],
        ['route' => 'admin_editorial', 'title' => ['fr' => 'Éditorial multilingue', 'en' => 'Multilingual editorial', 'de' => 'Mehrsprachige Redaktion', 'nl' => 'Meertalige redactie'], 'desc' => ['fr' => 'Français source, traduction auto EN/DE/NL et relecture manuelle.', 'en' => 'French source, EN/DE/NL auto translation and manual review.', 'de' => 'Französische Quelle, automatische Übersetzung und Review.', 'nl' => 'Franse bron, automatische vertaling en manuele review.']],
        ['route' => 'admin_translation_reviews', 'title' => ['fr' => 'Relecture linguistique', 'en' => 'Translation reviews', 'de' => 'Sprachliche Prüfung', 'nl' => 'Taalreview'], 'desc' => ['fr' => 'Workflow de validation des traductions des actualités et articles.', 'en' => 'Validation workflow for news/article translations.', 'de' => 'Freigabe-Workflow für News-/Artikelübersetzungen.', 'nl' => 'Validatieworkflow voor vertalingen van nieuws/artikels.']],
        ['route' => 'admin_live_feeds', 'title' => ['fr' => 'Flux live', 'en' => 'Live feeds', 'de' => 'Live-Feeds', 'nl' => 'Live feeds'], 'desc' => ['fr' => 'Pilotage fin des flux radioamateur, TTL, URLs et activation.', 'en' => 'Fine control of radio feeds, TTL, URLs and activation.', 'de' => 'Feinsteuerung von Funk-Feeds, TTL, URLs und Aktivierung.', 'nl' => 'Fijn beheer van radiofeeds, TTL, URL’s en activatie.']],
        ['route' => 'admin_newsletters', 'title' => ['fr' => 'Newsletter', 'en' => 'Newsletter', 'de' => 'Newsletter', 'nl' => 'Nieuwsbrief'], 'desc' => ['fr' => 'Abonnés, import CSV et campagnes email.', 'en' => 'Subscribers, CSV import and email campaigns.', 'de' => 'Abonnenten, CSV-Import und E-Mail-Kampagnen.', 'nl' => 'Abonnees, CSV-import en e-mailcampagnes.']],
        ['route' => 'admin_wiki', 'title' => ['fr' => 'Wiki', 'en' => 'Wiki', 'de' => 'Wiki', 'nl' => 'Wiki'], 'desc' => ['fr' => 'Pages collaboratives et révisions.', 'en' => 'Collaborative pages and revisions.', 'de' => 'Kollaborative Seiten und Revisionen.', 'nl' => 'Samenwerkingspagina’s en revisies.'], 'module' => 'wiki'],
        ['route' => 'admin_albums', 'title' => ['fr' => 'Albums', 'en' => 'Albums', 'de' => 'Alben', 'nl' => 'Albums'], 'desc' => ['fr' => 'Galerie publique et synchro sociale.', 'en' => 'Public gallery and social sync.', 'de' => 'Öffentliche Galerie und Social-Sync.', 'nl' => 'Publieke galerij en sociale sync.'], 'module' => 'albums'],
        ['route' => 'admin_ads', 'title' => ['fr' => 'Publicités', 'en' => 'Ads', 'de' => 'Werbung', 'nl' => 'Advertenties'], 'desc' => ['fr' => 'Régie publicitaire, placements et statistiques.', 'en' => 'Ad inventory, placements and statistics.', 'de' => 'Werbeverwaltung, Platzierungen und Statistiken.', 'nl' => 'Advertentiebeheer, plaatsingen en statistieken.'], 'module' => 'advertising'],
    ];
}

$cards = cache_remember('admin_cards_' . $locale . '_' . (int) (current_user()['id'] ?? 0) . '_' . md5($adminSearchNeedle), 30, static function () use ($locale, $adminSearchNeedle): array {
    $cards = [];
    foreach (admin_module_cards() as $card) {
        $module = (string) ($card['module'] ?? '');
        $permission = (string) ($card['permission'] ?? '');
        if ($module !== '' && !module_enabled($module)) {
            continue;
        }
        if ($permission !== '' && !has_permission($permission)) {
            continue;
        }
        $title = (string) ($card['title'][$locale] ?? $card['title']['fr']);
        $desc = (string) ($card['desc'][$locale] ?? $card['desc']['fr']);
        if ($adminSearchNeedle !== '') {
            $haystack = mb_safe_strtolower($title . ' ' . $desc);
            if (!str_contains($haystack, $adminSearchNeedle)) {
                continue;
            }
        }
        $cards[] = [
            'route' => $card['route'],
            'title' => $title,
            'desc' => $desc,
        ];
    }
    return $cards;
});

ob_start();
?>
<div class="stack">
    <section class="card">
        <h1><?= e((string) $t['title']) ?></h1>
        <p class="help"><?= e((string) $t['lead']) ?></p>
        <form method="get" action="<?= e(route_url('admin')) ?>" class="mt-2">
            <label><?= e((string) $t['search_label']) ?>
                <input type="search" name="q" value="<?= e($adminSearch) ?>" placeholder="<?= e((string) $t['search_placeholder']) ?>">
            </label>
            <div class="actions mt-2">
                <button type="submit" class="button"><?= e((string) $t['search_cta']) ?></button>
                <a class="button secondary" href="<?= e(route_url('admin')) ?>"><?= e((string) $t['search_reset']) ?></a>
            </div>
        </form>
    </section>
    <?php if ($cards === []): ?>
        <section class="card empty-state"><p><?= e((string) $t['empty']) ?></p></section>
    <?php else: ?>
    <div class="grid-3">
        <?php foreach ($cards as $card): ?>
            <a class="card admin-link" href="<?= e(route_url((string) $card['route'])) ?>"><h2><?= e((string) $card['title']) ?></h2><p><?= e((string) $card['desc']) ?></p></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['layout']);
