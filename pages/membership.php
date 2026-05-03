<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => [
        'badge' => 'Adhésion ON4CRD', 'title' => 'Rejoindre le club', 'intro' => 'Devenez membre pour participer activement aux projets radio, profiter de l\'entraide technique et intégrer une communauté locale dynamique.',
        'cta_join' => 'Demander l\'adhésion', 'cta_events' => 'Voir les prochaines activités', 'benefits_title' => 'Avantages membres', 'dues_title' => 'Informations de cotisation',
        'dues_help' => 'Les montants sont indicatifs et validés en assemblée annuelle. Le règlement peut se faire par virement ou en espèces au local.',
        'help_committee' => 'Besoin d\'aide ? Contactez le comité via la page', 'committee' => 'Comité',
        'due_adult' => 'Cotisation annuelle adulte', 'due_student' => 'Cotisation annuelle étudiant (-25 ans)', 'due_family' => 'Cotisation familiale (même domicile)',
        'benefit_1' => 'Accès aux ateliers pratiques, démonstrations techniques et sorties terrain.',
        'benefit_2' => 'Accompagnement personnalisé pour les nouveaux radioamateurs (examens, premiers QSO, installation).',
        'benefit_3' => 'Utilisation des infrastructures du club selon le planning : station, antennes et matériel mutualisé.',
        'benefit_4' => 'Tarifs membres sur certaines activités, commandes groupées et opérations boutique.',
    ],
    'en' => [
        'badge' => 'ON4CRD Membership', 'title' => 'Join the club', 'intro' => 'Become a member to actively take part in radio projects, benefit from technical support, and join a dynamic local community.',
        'cta_join' => 'Apply for membership', 'cta_events' => 'See upcoming activities', 'benefits_title' => 'Member benefits', 'dues_title' => 'Membership fees',
        'dues_help' => 'Amounts are indicative and validated at the annual meeting. Payment can be made by bank transfer or cash at the club.',
        'help_committee' => 'Need help? Contact the committee via the', 'committee' => 'Committee',
        'due_adult' => 'Annual adult membership', 'due_student' => 'Annual student membership (-25)', 'due_family' => 'Family membership (same household)',
        'benefit_1' => 'Access to practical workshops, technical demonstrations, and field outings.',
        'benefit_2' => 'Personalized support for new radio amateurs (exams, first QSOs, setup).',
        'benefit_3' => 'Use of club infrastructure according to schedule: station, antennas, and shared equipment.',
        'benefit_4' => 'Member pricing on selected activities, group orders, and shop operations.',
    ],
    'de' => [
        'badge' => 'ON4CRD Mitgliedschaft', 'title' => 'Dem Club beitreten', 'intro' => 'Werden Sie Mitglied, um aktiv an Funkprojekten teilzunehmen, technische Unterstützung zu erhalten und Teil einer dynamischen lokalen Gemeinschaft zu werden.',
        'cta_join' => 'Mitgliedschaft beantragen', 'cta_events' => 'Nächste Aktivitäten ansehen', 'benefits_title' => 'Mitgliedervorteile', 'dues_title' => 'Mitgliedsbeiträge',
        'dues_help' => 'Die Beträge sind Richtwerte und werden in der Jahresversammlung bestätigt. Zahlung per Überweisung oder bar im Clubheim möglich.',
        'help_committee' => 'Brauchen Sie Hilfe? Kontaktieren Sie das Komitee über die Seite', 'committee' => 'Komitee',
        'due_adult' => 'Jährlicher Erwachsenenbeitrag', 'due_student' => 'Jährlicher Studentenbeitrag (-25)', 'due_family' => 'Familienbeitrag (gleicher Haushalt)',
        'benefit_1' => 'Zugang zu Praxis-Workshops, technischen Vorführungen und Außeneinsätzen.',
        'benefit_2' => 'Individuelle Begleitung für neue Funkamateure (Prüfungen, erste QSOs, Einrichtung).',
        'benefit_3' => 'Nutzung der Clubinfrastruktur nach Plan: Station, Antennen und gemeinsam genutztes Material.',
        'benefit_4' => 'Mitgliedspreise für bestimmte Aktivitäten, Sammelbestellungen und Shop-Aktionen.',
    ],
    'nl' => [
        'badge' => 'ON4CRD Lidmaatschap', 'title' => 'Word lid van de club', 'intro' => 'Word lid om actief deel te nemen aan radioprojecten, technische ondersteuning te krijgen en deel uit te maken van een dynamische lokale gemeenschap.',
        'cta_join' => 'Lidmaatschap aanvragen', 'cta_events' => 'Komende activiteiten bekijken', 'benefits_title' => 'Ledenvoordelen', 'dues_title' => 'Lidgeldinformatie',
        'dues_help' => 'Bedragen zijn indicatief en worden bevestigd op de jaarlijkse vergadering. Betaling kan via overschrijving of contant in het lokaal.',
        'help_committee' => 'Hulp nodig? Contacteer het comité via de pagina', 'committee' => 'Comité',
        'due_adult' => 'Jaarlijks lidgeld volwassene', 'due_student' => 'Jaarlijks lidgeld student (-25)', 'due_family' => 'Gezinslidgeld (zelfde adres)',
        'benefit_1' => 'Toegang tot praktische workshops, technische demonstraties en terreinactiviteiten.',
        'benefit_2' => 'Persoonlijke begeleiding voor nieuwe radioamateurs (examens, eerste QSO\'s, installatie).',
        'benefit_3' => 'Gebruik van clubinfrastructuur volgens planning: station, antennes en gedeeld materiaal.',
        'benefit_4' => 'Ledentarieven op bepaalde activiteiten, groepsbestellingen en shopacties.',
    ],
];
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
