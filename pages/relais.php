<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_translator('relais', $locale);

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
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800"><?= e($t('callsign')) ?></th><td class="px-4 py-3"><?= e($t('callsign_value')) ?></td><td class="px-4 py-3"><?= e($t('tx')) ?></td><td class="px-4 py-3"><?= e($t('rx')) ?></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800"><?= e($t('channel')) ?></th><td class="px-4 py-3"><?= e($t('channel_value')) ?></td><td class="px-4 py-3"></td><td class="px-4 py-3"></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800"><?= e($t('mode')) ?></th><td class="px-4 py-3"><?= e($t('mode_value')) ?></td><td class="px-4 py-3"></td><td class="px-4 py-3"></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800"><?= e($t('input')) ?></th><td class="px-4 py-3"><?= e($t('input_value')) ?></td><td class="px-4 py-3"><?= e($t('unit_mhz')) ?></td><td class="px-4 py-3"></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800"><?= e($t('output')) ?></th><td class="px-4 py-3"><?= e($t('output_value')) ?></td><td class="px-4 py-3"><?= e($t('unit_mhz')) ?></td><td class="px-4 py-3"></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800"><?= e($t('shift')) ?></th><td class="px-4 py-3"><?= e($t('shift_value')) ?></td><td class="px-4 py-3"><?= e($t('unit_mhz')) ?></td><td class="px-4 py-3"></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800"><?= e($t('subtone')) ?></th><td class="px-4 py-3"><?= e($t('subtone_value')) ?></td><td class="px-4 py-3"><?= e($t('unit_hz')) ?></td><td class="px-4 py-3"><?= e($t('subtone_status_value')) ?></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800"><?= e($t('polarization')) ?></th><td class="px-4 py-3"><?= e($t('polarization_value')) ?></td><td class="px-4 py-3"></td><td class="px-4 py-3"></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800"><?= e($t('locator')) ?></th><td class="px-4 py-3"><?= e($t('locator_value')) ?></td><td class="px-4 py-3"></td><td class="px-4 py-3"></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800"><?= e($t('location')) ?></th><td class="px-4 py-3"><?= e($t('location_value')) ?></td><td class="px-4 py-3"></td><td class="px-4 py-3"></td></tr>
            <tr class="border-b border-slate-200"><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800"><?= e($t('power')) ?></th><td class="px-4 py-3"><?= e($t('power_value')) ?></td><td class="px-4 py-3"></td><td class="px-4 py-3"></td></tr>
            <tr><th scope="row" class="bg-slate-50 px-4 py-3 text-left font-semibold text-slate-800"><?= e($t('altitude')) ?></th><td class="px-4 py-3"><?= e($t('altitude_value')) ?></td><td class="px-4 py-3"></td><td class="px-4 py-3"></td></tr>
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
