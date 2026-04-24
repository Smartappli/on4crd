<?php
declare(strict_types=1);

require_permission('admin.access');

$cards = [];
if (has_permission('modules.manage')) {
    $cards[] = ['route' => 'admin_modules', 'title' => 'Modules', 'desc' => 'Activation, désactivation et pilotage global des modules.'];
}
$cards[] = ['route' => 'admin_permissions', 'title' => 'Permissions', 'desc' => 'Rôles, droits et affectations.'];
if (module_enabled('news')) {
    $cards[] = ['route' => 'admin_news', 'title' => 'Actualités', 'desc' => 'Sections, rédaction et modération.'];
}
if (module_enabled('articles')) {
    $cards[] = ['route' => 'admin_articles', 'title' => 'Articles', 'desc' => 'Articles techniques publics.'];
}
if (module_enabled('committee')) {
    $cards[] = ['route' => 'admin_committee', 'title' => 'Comité', 'desc' => 'Membres du comité, rôle, ordre et biographie.'];
}
if (module_enabled('press')) {
    $cards[] = ['route' => 'admin_press', 'title' => 'Presse', 'desc' => 'Contacts presse, communiqués datés et documents téléchargeables.'];
}
if (module_enabled('events')) {
    $cards[] = ['route' => 'admin_events', 'title' => 'Agenda', 'desc' => 'Événements du club et contests locaux affichés dans les widgets live.'];
    if (has_permission('events.manage')) {
        $cards[] = ['route' => 'admin_dinner_reservations', 'title' => 'Dîner annuel', 'desc' => 'Réservations, lignes repas/dessert, quantités et total automatique.'];
    }
}
if (module_enabled('shop') && has_permission('shop.manage')) {
    $cards[] = ['route' => 'admin_shop', 'title' => 'Boutique', 'desc' => 'Catalogue produits, catégories et commandes club.'];
}
if (module_enabled('auctions') && has_permission('auctions.manage')) {
    $cards[] = ['route' => 'admin_auctions', 'title' => 'Enchères', 'desc' => 'Lots, planification, offres et clôture.'];
}
$cards[] = ['route' => 'admin_editorial', 'title' => 'Éditorial multilingue', 'desc' => 'Français source, traduction auto EN/DE/NL et relecture manuelle.'];
$cards[] = ['route' => 'admin_translation_reviews', 'title' => 'Relecture linguistique', 'desc' => 'Workflow de validation des traductions des actualités et articles.'];
$cards[] = ['route' => 'admin_live_feeds', 'title' => 'Flux live', 'desc' => 'Pilotage fin des flux radioamateur, TTL, URLs et activation.'];
$cards[] = ['route' => 'admin_newsletters', 'title' => 'Newsletter', 'desc' => 'Abonnés, import CSV et campagnes email.'];
if (module_enabled('wiki')) {
    $cards[] = ['route' => 'admin_wiki', 'title' => 'Wiki', 'desc' => 'Pages collaboratives et révisions.'];
}
if (module_enabled('albums')) {
    $cards[] = ['route' => 'admin_albums', 'title' => 'Albums', 'desc' => 'Galerie publique et synchro sociale.'];
}
if (module_enabled('advertising')) {
    $cards[] = ['route' => 'admin_ads', 'title' => 'Publicités', 'desc' => 'Régie publicitaire, placements et statistiques.'];
}

ob_start();
?>
<div class="grid-3">
    <?php foreach ($cards as $card): ?>
        <a class="card admin-link" href="<?= e(route_url($card['route'])) ?>"><h2><?= e($card['title']) ?></h2><p><?= e($card['desc']) ?></p></a>
    <?php endforeach; ?>
</div>
<?php

echo render_layout((string) ob_get_clean(), 'Administration');
