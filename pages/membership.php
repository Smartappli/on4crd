<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/membership.php';
$i18n = i18n_expand_supported_locales($i18n);
$t = $i18n[$locale] ?? $i18n['fr'];
$tr = static fn(string $key): string => (string) ($t[$key] ?? ($i18n['fr'][$key] ?? $key));

$benefits = [$tr('benefit_1'), $tr('benefit_2'), $tr('benefit_3'), $tr('benefit_4')];
$dues = [
    ['label' => $tr('due_adult'), 'amount' => '35 €'],
    ['label' => $tr('due_student'), 'amount' => '20 €'],
    ['label' => $tr('due_family'), 'amount' => '50 €'],
];

$benefitsHtml = '';
foreach ($benefits as $benefit) {
    $benefitsHtml .= '<li class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700">' . e($benefit) . '</li>';
}

$duesHtml = '';
foreach ($dues as $due) {
    $duesHtml .= '<li class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3">'
        . '<span class="text-sm text-slate-700">' . e((string) $due['label']) . '</span>'
        . '<strong class="text-base text-slate-900">' . e((string) $due['amount']) . '</strong>'
        . '</li>';
}

$content = '<section class="rounded-3xl border border-slate-200 bg-gradient-to-r from-blue-50 to-white p-8 shadow-sm">'
    . '<span class="inline-flex rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">' . e($tr('badge')) . '</span>'
    . '<h1 class="mt-4 text-3xl font-extrabold text-slate-900 lg:text-4xl">' . e($tr('title')) . '</h1>'
    . '<p class="mt-3 max-w-3xl text-slate-700">' . e($tr('intro')) . '</p>'
    . '<div class="mt-6 flex flex-wrap gap-3">'
    . '<a class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" href="' . e(route_url('register')) . '">' . e($tr('cta_join')) . '</a>'
    . '<a class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('events')) . '">' . e($tr('cta_events')) . '</a>'
    . '</div>'
    . '</section>'
    . '<section class="mt-4 grid gap-4 lg:grid-cols-2">'
    . '<article class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm">'
    . '<h2 class="text-xl font-bold text-slate-900">' . e($tr('benefits_title')) . '</h2>'
    . '<ul class="mt-4 grid gap-3">' . $benefitsHtml . '</ul>'
    . '</article>'
    . '<article class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm">'
    . '<h2 class="text-xl font-bold text-slate-900">' . e($tr('dues_title')) . '</h2>'
    . '<p class="mt-2 text-sm text-slate-600">' . e($tr('dues_help')) . '</p>'
    . '<ul class="mt-4 grid gap-2">' . $duesHtml . '</ul>'
    . '<p class="mt-4 text-sm text-slate-600">' . e($tr('help_committee')) . ' <a class="font-semibold text-blue-700 hover:text-blue-800" href="' . e(route_url('committee')) . '">' . e($tr('committee')) . '</a>.</p>'
    . '</article>'
    . '</section>';

echo render_layout($content, $tr('title'));
