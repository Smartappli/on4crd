<?php
declare(strict_types=1);

$benefits = [
    'Accès aux ateliers pratiques, démonstrations techniques et sorties terrain.',
    'Accompagnement personnalisé pour les nouveaux radioamateurs (examens, premiers QSO, installation).',
    'Utilisation des infrastructures du club selon le planning : station, antennes et matériel mutualisé.',
    'Tarifs membres sur certaines activités, commandes groupées et opérations boutique.',
];

$dues = [
    ['label' => 'Cotisation annuelle adulte', 'amount' => '35 €'],
    ['label' => 'Cotisation annuelle étudiant (-25 ans)', 'amount' => '20 €'],
    ['label' => 'Cotisation familiale (même domicile)', 'amount' => '50 €'],
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
    . '<span class="inline-flex rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">Adhésion ON4CRD</span>'
    . '<h1 class="mt-4 text-3xl font-extrabold text-slate-900 lg:text-4xl">Rejoindre le club</h1>'
    . '<p class="mt-3 max-w-3xl text-slate-700">Devenez membre pour participer activement aux projets radio, profiter de l\'entraide technique et intégrer une communauté locale dynamique.</p>'
    . '<div class="mt-6 flex flex-wrap gap-3">'
    . '<a class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" href="' . e(route_url('register')) . '">Demander l\'adhésion</a>'
    . '<a class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('events')) . '">Voir les prochaines activités</a>'
    . '</div>'
    . '</section>'
    . '<section class="mt-4 grid gap-4 lg:grid-cols-2">'
    . '<article class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm">'
    . '<h2 class="text-xl font-bold text-slate-900">Avantages membres</h2>'
    . '<ul class="mt-4 grid gap-3">' . $benefitsHtml . '</ul>'
    . '</article>'
    . '<article class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm">'
    . '<h2 class="text-xl font-bold text-slate-900">Informations de cotisation</h2>'
    . '<p class="mt-2 text-sm text-slate-600">Les montants sont indicatifs et validés en assemblée annuelle. Le règlement peut se faire par virement ou en espèces au local.</p>'
    . '<ul class="mt-4 grid gap-2">' . $duesHtml . '</ul>'
    . '<p class="mt-4 text-sm text-slate-600">Besoin d\'aide ? Contactez le comité via la page <a class="font-semibold text-blue-700 hover:text-blue-800" href="' . e(route_url('committee')) . '">Comité</a>.</p>'
    . '</article>'
    . '</section>';

echo render_layout($content, 'Rejoindre le club');
