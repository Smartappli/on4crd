<?php
declare(strict_types=1);

set_page_meta([
    'title' => 'Notre relais ONØCRD',
    'description' => 'Informations techniques et accès EchoLink du relais ONØCRD.',
    'schema_type' => 'WebPage',
]);

ob_start();
?>
<section class="card max-w-5xl mx-auto">
    <h1 class="text-3xl font-bold text-slate-900 text-center">Notre relais</h1>
    <div class="mt-6 overflow-x-auto">
        <table class="w-full min-w-[720px] border border-slate-200 rounded-xl overflow-hidden text-sm">
            <tbody>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800">Indicatif</th><td class="px-4 py-3">ONØCRD</td><td class="px-4 py-3">Entrée ( TX )</td><td class="px-4 py-3">Sortie ( RX )</td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800">Canal</th><td class="px-4 py-3">RV 46</td><td class="px-4 py-3"></td><td class="px-4 py-3"></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800">Mode</th><td class="px-4 py-3">Mode Répétiteur FM + EchoLink</td><td class="px-4 py-3"></td><td class="px-4 py-3"></td></tr>
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
        <p>Accès Echolink mis en place pour nos amis hors de portée du relais de Durnal.</p>
        <p>Merci de privilégier le VHF si vous êtes à portée du relais. Balise vocale toutes les 15 min.</p>
        <p>Le Relais SVXlink installé est l’implémentation d’un lien SVXLink (Echolink) avec un Relais FM existant. Ici: ONØCRD – Sysop ON4DL – Suppléant ON5GB. « svxlink » est un système de service vocal à usage général pour l'utilisation de la radio « amateur » .</p>
        <p>Avec la collaboration de ON4LS est né depuis ce 18/03/2021 un accès Echolink sur ONØCRD. Par ceci nous ouvrons une porte à nos amis afin de garder contact avec les OM’s de la zone couverte par le relais.</p>
    </div>
</section>
<?php
echo render_layout((string) ob_get_clean(), 'Notre relais');
