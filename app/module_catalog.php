<?php
declare(strict_types=1);

/**
 * @return array<int, array{
 *   route:string,
 *   title:array<string,string>,
 *   desc:array<string,string>,
 *   module?:string,
 *   permission?:string
 * }>
 */
function admin_module_cards_catalog(): array
{
    $definitions = [
        ['route' => 'admin_modules', 'permission' => 'modules.manage'],
        ['route' => 'admin_members'],
        ['route' => 'admin_permissions'],
        ['route' => 'admin_news', 'module' => 'news'],
        ['route' => 'admin_articles', 'module' => 'articles'],
        ['route' => 'admin_committee', 'module' => 'committee'],
        ['route' => 'admin_press', 'module' => 'press'],
        ['route' => 'admin_events', 'module' => 'events'],
        ['route' => 'admin_dinner_reservations', 'module' => 'events', 'permission' => 'events.manage'],
        ['route' => 'admin_auctions', 'module' => 'auctions', 'permission' => 'auctions.manage'],
        ['route' => 'admin_editorial'],
        ['route' => 'admin_translation_reviews'],
        ['route' => 'admin_dashboard', 'module' => 'dashboard', 'permission' => 'admin.access'],
        ['route' => 'admin_live_feeds'],
        ['route' => 'admin_newsletters'],
        ['route' => 'admin_wiki', 'module' => 'wiki'],
        ['route' => 'admin_albums', 'module' => 'albums'],
        ['route' => 'admin_library', 'permission' => 'admin.access'],
        ['route' => 'admin_webotheque', 'module' => 'webotheque', 'permission' => 'admin.access'],
        ['route' => 'admin_presentations', 'module' => 'presentations', 'permission' => 'admin.access'],
        ['route' => 'admin_videos', 'module' => 'videos', 'permission' => 'admin.access'],
        ['route' => 'admin_pv', 'module' => 'pv', 'permission' => 'admin.access'],
        ['route' => 'admin_fichiers', 'module' => 'fichiers', 'permission' => 'admin.access'],
        ['route' => 'admin_ads', 'module' => 'advertising'],
        ['route' => 'admin_privacy', 'permission' => 'privacy.manage'],
        ['route' => 'admin_classifieds', 'module' => 'classifieds', 'permission' => 'classifieds.moderate'],
    ];

    $messages = i18n_domain_messages('admin_module_cards');
    $catalog = [];
    foreach ($definitions as $definition) {
        $route = (string) $definition['route'];
        $title = [];
        $desc = [];
        foreach (supported_locales() as $localeCode) {
            $title[$localeCode] = (string) ($messages[$localeCode][$route . '_title'] ?? $messages['fr'][$route . '_title'] ?? $route);
            $desc[$localeCode] = (string) ($messages[$localeCode][$route . '_desc'] ?? $messages['fr'][$route . '_desc'] ?? '');
        }
        $definition['title'] = $title;
        $definition['desc'] = $desc;
        $catalog[] = $definition;
    }

    return $catalog;
}
