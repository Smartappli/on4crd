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
        ['route' => 'admin_modules', 'permission' => 'modules.manage', 'group' => 'settings'],
        ['route' => 'admin_members', 'group' => 'members'],
        ['route' => 'admin_permissions', 'group' => 'members'],
        ['route' => 'admin_news', 'module' => 'news', 'group' => 'content'],
        ['route' => 'admin_articles', 'module' => 'articles', 'group' => 'content'],
        ['route' => 'admin_committee', 'module' => 'committee', 'group' => 'content'],
        ['route' => 'admin_press', 'module' => 'press', 'group' => 'content'],
        ['route' => 'admin_events', 'module' => 'events', 'group' => 'content'],
        ['route' => 'admin_dinner_reservations', 'module' => 'events', 'permission' => 'events.manage', 'group' => 'communication'],
        ['route' => 'admin_auctions', 'module' => 'auctions', 'permission' => 'auctions.manage', 'group' => 'communication'],
        ['route' => 'admin_editorial', 'group' => 'settings'],
        ['route' => 'admin_translation_reviews', 'group' => 'settings'],
        ['route' => 'admin_dashboard', 'module' => 'dashboard', 'permission' => 'admin.access', 'group' => 'settings'],
        ['route' => 'admin_live_feeds', 'group' => 'communication'],
        ['route' => 'admin_newsletters', 'group' => 'communication'],
        ['route' => 'admin_wiki', 'module' => 'wiki', 'group' => 'content'],
        ['route' => 'admin_albums', 'module' => 'albums', 'group' => 'media'],
        ['route' => 'admin_library', 'permission' => 'admin.access', 'group' => 'media'],
        ['route' => 'admin_webotheque', 'module' => 'webotheque', 'permission' => 'admin.access', 'group' => 'media'],
        ['route' => 'admin_presentations', 'module' => 'presentations', 'permission' => 'admin.access', 'group' => 'media'],
        ['route' => 'admin_videos', 'module' => 'videos', 'permission' => 'admin.access', 'group' => 'media'],
        ['route' => 'admin_pv', 'module' => 'pv', 'permission' => 'admin.access', 'group' => 'media'],
        ['route' => 'admin_fichiers', 'module' => 'fichiers', 'permission' => 'admin.access', 'group' => 'media'],
        ['route' => 'admin_ads', 'module' => 'advertising', 'group' => 'communication'],
        ['route' => 'admin_privacy', 'permission' => 'privacy.manage', 'group' => 'members'],
        ['route' => 'admin_classifieds', 'module' => 'classifieds', 'permission' => 'classifieds.moderate', 'group' => 'communication'],
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
