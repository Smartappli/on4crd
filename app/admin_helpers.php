<?php
declare(strict_types=1);

require_once __DIR__ . '/module_catalog.php';

/**
 * @return array{layout:string,title:string,lead:string,search_label:string,search_placeholder:string,search_cta:string,search_reset:string,empty:string}
 */
function admin_dashboard_translations(string $locale): array
{
    $i18n = i18n_domain_messages('admin');

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
