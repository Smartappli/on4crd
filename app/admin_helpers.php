<?php
declare(strict_types=1);

require_once __DIR__ . '/module_catalog.php';

/**
 * @return array{layout:string,title:string,lead:string,search_label:string,search_placeholder:string,search_cta:string,search_reset:string,empty:string}
 */
function admin_dashboard_translations(string $locale): array
{
    $i18n = [
        'fr' => ['layout' => 'Administration', 'title' => 'Administration centralisée', 'lead' => 'Tous les modules et outils d’administration sont regroupés dans ce tableau de bord unique.', 'search_label' => 'Recherche rapide', 'search_placeholder' => 'Module, outil, description…', 'search_cta' => 'Filtrer', 'search_reset' => 'Réinitialiser', 'empty' => 'Aucun module ne correspond à la recherche.'],
        'en' => ['layout' => 'Administration', 'title' => 'Centralized administration', 'lead' => 'All admin modules and tools are grouped in this single dashboard.', 'search_label' => 'Quick search', 'search_placeholder' => 'Module, tool, description…', 'search_cta' => 'Filter', 'search_reset' => 'Reset', 'empty' => 'No module matches your search.'],
        'de' => ['layout' => 'Verwaltung', 'title' => 'Zentralisierte Verwaltung', 'lead' => 'Alle Verwaltungs-Module und Werkzeuge sind in diesem einzigen Dashboard gebündelt.', 'search_label' => 'Schnellsuche', 'search_placeholder' => 'Modul, Werkzeug, Beschreibung…', 'search_cta' => 'Filtern', 'search_reset' => 'Zurücksetzen', 'empty' => 'Kein Modul entspricht Ihrer Suche.'],
        'nl' => ['layout' => 'Beheer', 'title' => 'Gecentraliseerd beheer', 'lead' => 'Alle beheermodules en tools zijn gegroepeerd in dit ene dashboard.', 'search_label' => 'Snel zoeken', 'search_placeholder' => 'Module, tool, beschrijving…', 'search_cta' => 'Filteren', 'search_reset' => 'Reset', 'empty' => 'Geen module komt overeen met je zoekopdracht.'],
        'es' => ['layout' => 'Administración', 'title' => 'Administración centralizada', 'lead' => 'Todos los módulos y herramientas de administración se agrupan en este panel único.', 'search_label' => 'Búsqueda rápida', 'search_placeholder' => 'Módulo, herramienta, descripción…', 'search_cta' => 'Filtrar', 'search_reset' => 'Restablecer', 'empty' => 'Ningún módulo coincide con su búsqueda.'],
        'it' => ['layout' => 'Amministrazione', 'title' => 'Amministrazione centralizzata', 'lead' => 'Tutti i moduli e gli strumenti di amministrazione sono raccolti in questa dashboard unica.', 'search_label' => 'Ricerca rapida', 'search_placeholder' => 'Modulo, strumento, descrizione…', 'search_cta' => 'Filtra', 'search_reset' => 'Reimposta', 'empty' => 'Nessun modulo corrisponde alla ricerca.'],
        'pt' => ['layout' => 'Administração', 'title' => 'Administração centralizada', 'lead' => 'Todos os módulos e ferramentas de administração estão agrupados neste painel único.', 'search_label' => 'Pesquisa rápida', 'search_placeholder' => 'Módulo, ferramenta, descrição…', 'search_cta' => 'Filtrar', 'search_reset' => 'Repor', 'empty' => 'Nenhum módulo corresponde à sua pesquisa.'],
    ];

    $resolved = [];
    foreach (array_keys($i18n['fr']) as $key) {
        $pool = [];
        foreach ($i18n as $lang => $translations) {
            if (isset($translations[$key]) && is_string($translations[$key])) {
                $pool[$lang] = $translations[$key];
            }
        }
        $resolved[$key] = i18n_localized_value($pool, $locale, 'fr');
    }

    return $resolved;
}

/**
 * @return array<int, array{route:string,title:string,desc:string}>
 */
function admin_dashboard_cards(string $locale, int $userId, string $search = ''): array
{
    $needle = trim($search);
    $needle = $needle !== '' ? mb_safe_strtolower($needle) : '';

    return admin_cards_for_dashboard($locale, $userId, $needle);
}

/**
 * @return array<int, array{route:string,title:string,desc:string}>
 */
function admin_cards_for_dashboard(string $locale, int $userId, string $searchNeedle = ''): array
{
    return cache_remember('admin_cards_' . $locale . '_' . $userId . '_' . md5($searchNeedle), 30, static function () use ($locale, $searchNeedle): array {
        $cards = [];
        foreach (admin_module_cards_catalog() as $card) {
            $module = (string) ($card['module'] ?? '');
            $permission = (string) ($card['permission'] ?? '');
            if ($module !== '' && !module_enabled($module)) {
                continue;
            }
            if ($permission !== '' && !has_permission($permission)) {
                continue;
            }
            $title = i18n_localized_value($card['title'], $locale, 'fr');
            $desc = i18n_localized_value($card['desc'], $locale, 'fr');
            if ($searchNeedle !== '') {
                $haystack = mb_safe_strtolower($title . ' ' . $desc);
                if (!str_contains($haystack, $searchNeedle)) {
                    continue;
                }
            }
            $cards[] = ['route' => (string) $card['route'], 'title' => $title, 'desc' => $desc];
        }
        return $cards;
    });
}
