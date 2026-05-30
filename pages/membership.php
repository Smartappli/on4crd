<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/membership.php';
$i18n = i18n_expand_supported_locales($i18n);
$t = $i18n[$locale] ?? $i18n['fr'];
$tr = static fn(string $key): string => (string) ($t[$key] ?? ($i18n['fr'][$key] ?? $key));

$contactItems = [$tr('contact_item_1'), $tr('contact_item_2')];
$requiredItems = [$tr('required_item_1'), $tr('required_item_2'), $tr('required_item_3'), $tr('required_item_4'), $tr('required_item_5'), $tr('required_item_6')];
$reviewItems = [$tr('review_item_1'), $tr('review_item_2'), $tr('review_item_3')];
$membershipImagePath = '/assets/membership/membership.jpg';
$membershipImageAbsolutePath = __DIR__ . '/../assets/membership/membership.jpg';
$membershipImageUrl = is_file($membershipImageAbsolutePath) ? base_url($membershipImagePath) : '';

$renderList = static function (array $items): string {
    $html = '';
    foreach ($items as $item) {
        $html .= '<li class="rounded-xl border border-slate-200 bg-white p-4 text-sm leading-relaxed text-slate-700">' . e($item) . '</li>';
    }
    return $html;
};

$content = '<section class="rounded-3xl border border-slate-200 bg-gradient-to-r from-blue-50 to-white p-8 shadow-sm">'
    . '<span class="inline-flex rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">' . e($tr('badge')) . '</span>'
    . '<h1 class="mt-4 text-3xl font-extrabold text-slate-900 lg:text-4xl">' . e($tr('title')) . '</h1>'
    . '<p class="mt-3 max-w-3xl text-slate-700">' . e($tr('intro')) . '</p>'
    . ($membershipImageUrl !== ''
        ? '<figure class="mt-6 mx-auto w-full max-w-xs overflow-hidden rounded-2xl border border-slate-200 bg-white">'
            . '<img class="aspect-square h-auto w-full object-contain" src="' . e($membershipImageUrl) . '" alt="' . e($tr('image_alt')) . '" loading="lazy">'
        . '</figure>'
        : '')
    . '</section>'
    . '<section class="mt-4 grid gap-4 lg:grid-cols-2">'
    . '<article class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm">'
    . '<h2 class="text-xl font-bold text-slate-900">' . e($tr('how_title')) . '</h2>'
    . '<p class="mt-2 text-sm text-slate-700">' . e($tr('how_text')) . '</p>'
    . '<h3 class="mt-4 text-sm font-semibold uppercase tracking-wide text-slate-600">' . e($tr('postal_title')) . '</h3>'
    . '<ul class="mt-3 grid gap-3">' . $renderList($contactItems) . '</ul>'
    . '</article>'
    . '<article class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm">'
    . '<h2 class="text-xl font-bold text-slate-900">' . e($tr('required_title')) . '</h2>'
    . '<ul class="mt-4 grid gap-3">' . $renderList($requiredItems) . '</ul>'
    . '</article>'
    . '</section>'
    . '<section class="mt-4 grid gap-4">'
    . '<article class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm">'
    . '<h2 class="text-xl font-bold text-slate-900">' . e($tr('review_title')) . '</h2>'
    . '<p class="mt-2 text-sm text-slate-700">' . e($tr('review_intro')) . '</p>'
    . '<ul class="mt-4 grid gap-3">' . $renderList($reviewItems) . '</ul>'
    . '<p class="mt-4 text-sm font-semibold text-slate-900">' . e($tr('signature')) . '</p>'
    . '</article>'
    . '</section>';

echo render_layout($content, $tr('title'));
