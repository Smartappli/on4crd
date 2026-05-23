<?php
declare(strict_types=1);

require_login();
$locale = current_locale();

$titleMap = [
    'fr' => 'HAREC band plan',
    'en' => 'HAREC band plan',
    'de' => 'HAREC band plan',
    'nl' => 'HAREC band plan',
    'es' => 'HAREC band plan',
    'it' => 'HAREC band plan',
    'pt' => 'HAREC band plan',
    'ar' => 'HAREC band plan',
    'hi' => 'HAREC band plan',
    'ja' => 'HAREC band plan',
    'zh' => 'HAREC band plan',
    'bn' => 'HAREC band plan',
    'ru' => 'HAREC band plan',
    'id' => 'HAREC band plan',
];
$title = i18n_localized_value($titleMap, $locale, 'en');

$headers = ['band' => 'Band', 'freq' => 'Frequencies (MHz)', 'modes' => 'Modes', 'pwr' => 'Max power*', 'notes' => 'Notes'];
$rows = [
    ['160 m', '1.810-2.000', 'CW/SSB', 'Lower segment mainly CW.', '1500 W PEP'],
    ['80 m', '3.500-3.800', 'CW/SSB/Data', 'Local/regional NVIS usage.', '1500 W PEP'],
    ['60 m', '5.3515-5.3665', 'SSB/Data', 'Channels/segments per IBPT publication.', '100 W PEP'],
    ['40 m', '7.000-7.200', 'CW/SSB/Data', 'High international activity.', '1500 W PEP'],
    ['30 m', '10.100-10.150', 'CW/Data', 'Generally no phone operation.', '500 W PEP'],
    ['20 m', '14.000-14.350', 'CW/SSB/Data', '14.074 MHz commonly used for FT8.', '1500 W PEP'],
    ['17 m', '18.068-18.168', 'CW/SSB/Data', 'WARC band.', '1500 W PEP'],
    ['15 m', '21.000-21.450', 'CW/SSB/Data', 'Propagation often favorable by day.', '1500 W PEP'],
    ['12 m', '24.890-24.990', 'CW/SSB/Data', 'WARC band.', '1500 W PEP'],
    ['10 m', '28.000-29.700', 'CW/SSB/FM/Data', 'Includes beacons and local FM.', '1500 W PEP'],
    ['6 m', '50.000-52.000', 'CW/SSB/FM/Data', 'Sporadic-E openings.', '400 W PEP'],
    ['2 m', '144.000-146.000', 'FM/SSB/CW', '145.500 simplex calling.', '120 W PEP'],
    ['70 cm', '430.000-440.000', 'FM/Relais/Numeric', 'Analog and digital repeaters.', '120 W PEP'],
    ['23 cm', '1240-1300', 'FM/SSB/CW/Data', 'Shared usage depending on areas.', '120 W PEP'],
];

ob_start();
?>
<section class="card">
    <h1><?= e($title) ?></h1>
    <p class="help">IBPT/BIPT: <a href="https://www.ibpt.be/file/cc73d96153bbd5448a56f19d925d05b1379c7f21/1891ad4029fa18396c037433ed4c2a063854f1b0/freq-fr.pdf?name=Freq-FR.pdf&type=application%2Fpdf" target="_blank" rel="noopener noreferrer">Freq-FR.pdf</a></p>
    <div class="table-wrap mt-3">
        <table>
            <thead>
            <tr>
                <th><?= e($headers['band']) ?></th>
                <th><?= e($headers['freq']) ?></th>
                <th><?= e($headers['modes']) ?></th>
                <th><?= e($headers['pwr']) ?></th>
                <th><?= e($headers['notes']) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= e($r[0]) ?></td>
                    <td><?= e($r[1]) ?></td>
                    <td><?= e($r[2]) ?></td>
                    <td><?= e($r[4]) ?></td>
                    <td><?= e($r[3]) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
echo render_layout((string) ob_get_clean(), $title);
