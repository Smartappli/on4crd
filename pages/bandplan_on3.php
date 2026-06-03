<?php
declare(strict_types=1);

$locale = current_locale();

$title = i18n_localized_value([
    'fr' => 'Band plan ON3',
    'en' => 'ON3 band plan',
    'de' => 'ON3-Bandplan',
    'nl' => 'ON3-bandplan',
], $locale, 'fr');

$headers = [
    'fr' => ['band' => 'Bande', 'freq' => 'Frequences (MHz)', 'modes' => 'Modes', 'pwr' => 'Puissance max*', 'notes' => 'Notes'],
    'en' => ['band' => 'Band', 'freq' => 'Frequencies (MHz)', 'modes' => 'Modes', 'pwr' => 'Max power*', 'notes' => 'Notes'],
    'de' => ['band' => 'Band', 'freq' => 'Frequencies (MHz)', 'modes' => 'Modes', 'pwr' => 'Max power*', 'notes' => 'Notes'],
    'nl' => ['band' => 'Band', 'freq' => 'Frequenties (MHz)', 'modes' => 'Modes', 'pwr' => 'Max vermogen*', 'notes' => 'Notities'],
];
$localizedHeaders = is_array($headers[$locale] ?? null) ? $headers[$locale] : $headers['en'];

$rows = [
    ['80 m', '3.500-3.600', 'CW/SSB', [
        'fr' => 'Segment ON3 limite selon autorisation.',
        'en' => 'ON3 limited segment per authorization.',
        'de' => 'ON3 limited segment per authorization.',
        'nl' => 'ON3 beperkt segment volgens vergunning.',
    ], '10 W PEP'],
    ['40 m', '7.000-7.100', 'CW/SSB', [
        'fr' => 'Utilisation partielle selon reglementation.',
        'en' => 'Partial use per regulation.',
        'de' => 'Partial use per regulation.',
        'nl' => 'Gedeeltelijk gebruik volgens regelgeving.',
    ], '10 W PEP'],
    ['15 m', '21.100-21.200', 'SSB/Data', [
        'fr' => 'Ouverture surtout diurne.',
        'en' => 'Mostly daytime openings.',
        'de' => 'Mostly daytime openings.',
        'nl' => 'Openingen vooral overdag.',
    ], '10 W PEP'],
    ['10 m', '28.000-29.700', 'CW/SSB/FM/Data', [
        'fr' => 'Sous-bandes selon plan IARU.',
        'en' => 'Sub-bands per IARU plan.',
        'de' => 'Sub-bands per IARU plan.',
        'nl' => 'Subbanden volgens IARU-plan.',
    ], '10 W PEP'],
    ['2 m', '144.000-146.000', 'FM/SSB/CW', [
        'fr' => '145.500 MHz appel simplex.',
        'en' => '145.500 MHz simplex calling.',
        'de' => '145.500 MHz simplex calling.',
        'nl' => '145.500 MHz simplex-oproep.',
    ], '10 W PEP'],
    ['70 cm', '430.000-440.000', 'FM/Relais/Numeric', [
        'fr' => 'Respecter le plan relais local.',
        'en' => 'Follow local repeater plan.',
        'de' => 'Follow local repeater plan.',
        'nl' => 'Volg het lokale relaisplan.',
    ], '10 W PEP'],
    ['23 cm', '1240-1300', 'FM/SSB/Data', [
        'fr' => 'Portions partagees, filtrage conseille.',
        'en' => 'Shared segments, filtering recommended.',
        'de' => 'Shared segments, filtering recommended.',
        'nl' => 'Gedeelde segmenten, filtering aanbevolen.',
    ], '10 W PEP'],
];

ob_start();
?>
<section class="card bandplan-on3-module">
    <h1><?= e($title) ?></h1>
    <p class="help">IBPT/BIPT:
        <a href="https://www.ibpt.be/file/cc73d96153bbd5448a56f19d925d05b1379c7f21/1891ad4029fa18396c037433ed4c2a063854f1b0/freq-fr.pdf?name=Freq-FR.pdf&type=application%2Fpdf" target="_blank" rel="noopener noreferrer">Freq-FR.pdf</a>
    </p>
    <div class="table-wrap mt-3">
        <table>
            <thead>
            <tr>
                <th><?= e((string) ($localizedHeaders['band'] ?? 'Band')) ?></th>
                <th><?= e((string) ($localizedHeaders['freq'] ?? 'Frequencies (MHz)')) ?></th>
                <th><?= e((string) ($localizedHeaders['modes'] ?? 'Modes')) ?></th>
                <th><?= e((string) ($localizedHeaders['pwr'] ?? 'Max power*')) ?></th>
                <th><?= e((string) ($localizedHeaders['notes'] ?? 'Notes')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e((string) $row[0]) ?></td>
                    <td><?= e((string) $row[1]) ?></td>
                    <td><?= e((string) $row[2]) ?></td>
                    <td><?= e((string) $row[4]) ?></td>
                    <td><?= e(i18n_localized_value($row[3], $locale, 'fr')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
echo render_layout((string) ob_get_clean(), $title);
