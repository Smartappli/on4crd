<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['meta_title' => 'Notre relais ONØCRD', 'meta_desc' => 'Informations techniques et accès EchoLink du relais ONØCRD.', 'title' => 'Notre relais', 'callsign' => 'Indicatif', 'channel' => 'Canal', 'mode' => 'Mode', 'input' => "Fréquence d'entrée ( TX )", 'output' => 'Fréquence de sortie ( RX )', 'shift' => 'Shift', 'subtone' => 'Subtone CTCSS', 'polarization' => 'Polarisation', 'locator' => 'Locator', 'location' => 'Localisation', 'power' => 'Puissance', 'altitude' => 'Altitude', 'tx' => 'Entrée ( TX )', 'rx' => 'Sortie ( RX )', 'yes' => 'Oui / Oui', 'p1' => 'Accès Echolink mis en place pour nos amis hors de portée du relais de Durnal.', 'p2' => 'Merci de privilégier le VHF si vous êtes à portée du relais. Balise vocale toutes les 15 min.', 'p3' => 'Le Relais SVXlink installé est l’implémentation d’un lien SVXLink (Echolink) avec un Relais FM existant. Ici: ONØCRD – Sysop ON4DL – Suppléant ON5GB. « svxlink » est un système de service vocal à usage général pour l\'utilisation de la radio « amateur » .', 'p4' => 'Avec la collaboration de ON4LS est né depuis ce 18/03/2021 un accès Echolink sur ONØCRD. Par ceci nous ouvrons une porte à nos amis afin de garder contact avec les OM’s de la zone couverte par le relais.'],
    'en' => ['meta_title' => 'Our ONØCRD repeater', 'meta_desc' => 'Technical information and EchoLink access for ONØCRD repeater.', 'title' => 'Our repeater', 'callsign' => 'Callsign', 'channel' => 'Channel', 'mode' => 'Mode', 'input' => 'Input frequency ( TX )', 'output' => 'Output frequency ( RX )', 'shift' => 'Shift', 'subtone' => 'CTCSS subtone', 'polarization' => 'Polarization', 'locator' => 'Locator', 'location' => 'Location', 'power' => 'Power', 'altitude' => 'Altitude', 'tx' => 'Input ( TX )', 'rx' => 'Output ( RX )', 'yes' => 'Yes / Yes', 'p1' => 'Echolink access is available for friends outside Durnal repeater coverage.', 'p2' => 'Please prefer VHF if you are within repeater coverage. Voice beacon every 15 minutes.', 'p3' => 'The installed SVXlink repeater is an SVXLink (Echolink) link with an existing FM repeater. Here: ONØCRD – Sysop ON4DL – Deputy ON5GB. “svxlink” is a general-purpose voice service system for amateur radio usage.', 'p4' => 'With ON4LS collaboration, EchoLink access on ONØCRD has been available since 18/03/2021. This opens a gateway to keep contact with OMs in the repeater coverage area.'],
    'de' => ['meta_title' => 'Unser ONØCRD-Relais', 'meta_desc' => 'Technische Informationen und EchoLink-Zugang für das ONØCRD-Relais.', 'title' => 'Unser Relais', 'callsign' => 'Rufzeichen', 'channel' => 'Kanal', 'mode' => 'Modus', 'input' => 'Eingangsfrequenz ( TX )', 'output' => 'Ausgangsfrequenz ( RX )', 'shift' => 'Ablage', 'subtone' => 'CTCSS-Subton', 'polarization' => 'Polarisation', 'locator' => 'Locator', 'location' => 'Standort', 'power' => 'Leistung', 'altitude' => 'Höhe', 'tx' => 'Eingang ( TX )', 'rx' => 'Ausgang ( RX )', 'yes' => 'Ja / Ja', 'p1' => 'Echolink-Zugang wurde für Freunde außerhalb der Reichweite des Durnal-Relais eingerichtet.', 'p2' => 'Bitte bevorzugen Sie VHF, wenn Sie in Reichweite des Relais sind. Sprachbake alle 15 Minuten.', 'p3' => 'Das installierte SVXlink-Relais ist eine SVXLink-(Echolink)-Anbindung an ein bestehendes FM-Relais. Hier: ONØCRD – Sysop ON4DL – Stellvertreter ON5GB. „svxlink“ ist ein allgemeines Sprachdienstsystem für die Amateurfunknutzung.', 'p4' => 'In Zusammenarbeit mit ON4LS wurde seit dem 18.03.2021 ein Echolink-Zugang auf ONØCRD eingerichtet. So halten wir den Kontakt mit OMs im Abdeckungsbereich des Relais.'],
    'nl' => ['meta_title' => 'Onze ONØCRD-repeater', 'meta_desc' => 'Technische informatie en EchoLink-toegang voor de ONØCRD-repeater.', 'title' => 'Onze repeater', 'callsign' => 'Roepnaam', 'channel' => 'Kanaal', 'mode' => 'Modus', 'input' => 'Ingangsfrequentie ( TX )', 'output' => 'Uitgangsfrequentie ( RX )', 'shift' => 'Shift', 'subtone' => 'CTCSS-subtoon', 'polarization' => 'Polarisatie', 'locator' => 'Locator', 'location' => 'Locatie', 'power' => 'Vermogen', 'altitude' => 'Hoogte', 'tx' => 'Ingang ( TX )', 'rx' => 'Uitgang ( RX )', 'yes' => 'Ja / Ja', 'p1' => 'Echolink-toegang is voorzien voor vrienden buiten bereik van de Durnal-repeater.', 'p2' => 'Gebruik bij voorkeur VHF als je binnen bereik van de repeater bent. Spraakbaken elke 15 minuten.', 'p3' => 'De geïnstalleerde SVXlink-repeater is een SVXLink(Echolink)-koppeling met een bestaande FM-repeater. Hier: ONØCRD – Sysop ON4DL – Plaatsvervanger ON5GB. “svxlink” is een algemeen spraakservicesysteem voor amateurradiogebruik.', 'p4' => 'Met medewerking van ON4LS is sinds 18/03/2021 Echolink-toegang op ONØCRD beschikbaar. Zo houden we contact met OMs in het dekkingsgebied van de repeater.'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

set_page_meta([
    'title' => $t('meta_title'),
    'description' => $t('meta_desc'),
    'schema_type' => 'WebPage',
]);

ob_start();
?>
<section class="card max-w-5xl mx-auto">
    <h1 class="text-3xl font-bold text-slate-900 text-center"><?= e($t('title')) ?></h1>
    <div class="mt-6 overflow-x-auto">
        <table class="w-full min-w-[720px] border border-slate-200 rounded-xl overflow-hidden text-sm">
            <tbody>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800"><?= e($t('callsign')) ?></th><td class="px-4 py-3">ONØCRD</td><td class="px-4 py-3"><?= e($t('tx')) ?></td><td class="px-4 py-3"><?= e($t('rx')) ?></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800"><?= e($t('channel')) ?></th><td class="px-4 py-3">RV 46</td><td class="px-4 py-3"></td><td class="px-4 py-3"></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800"><?= e($t('mode')) ?></th><td class="px-4 py-3">Mode Répétiteur FM + EchoLink</td><td class="px-4 py-3"></td><td class="px-4 py-3"></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800">Fréquence d'entrée ( TX )</th><td class="px-4 py-3">144,975</td><td class="px-4 py-3">Mhz</td><td class="px-4 py-3"></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800">Fréquence de sortie ( RX )</th><td class="px-4 py-3">145,575</td><td class="px-4 py-3">Mhz</td><td class="px-4 py-3"></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800">Shift</th><td class="px-4 py-3">0,6</td><td class="px-4 py-3">Mhz</td><td class="px-4 py-3"></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800">Subtone CTCSS</th><td class="px-4 py-3">131,8</td><td class="px-4 py-3">Hz</td><td class="px-4 py-3">Oui / Oui</td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800">Polarisation</th><td class="px-4 py-3">Verticale 6,5 Dbi</td><td class="px-4 py-3"></td><td class="px-4 py-3"></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800">Locator</th><td class="px-4 py-3">JO20LI</td><td class="px-4 py-3"></td><td class="px-4 py-3"></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800">Localisation</th><td class="px-4 py-3">Yvoir ( Durnal )</td><td class="px-4 py-3"></td><td class="px-4 py-3"></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800">Puissance</th><td class="px-4 py-3">25 Watts</td><td class="px-4 py-3"></td><td class="px-4 py-3"></td></tr>
            <tr><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800">Altitude</th><td class="px-4 py-3">314m</td><td class="px-4 py-3"></td><td class="px-4 py-3"></td></tr>
            </tbody>
        </table>
    </div>

    <div class="mt-6 space-y-4 text-slate-700">
        <p><?= e($t('p1')) ?></p>
        <p><?= e($t('p2')) ?></p>
        <p><?= e($t('p3')) ?></p>
        <p><?= e($t('p4')) ?></p>
    </div>
</section>
<?php
echo render_layout((string) ob_get_clean(), $t('title'));
