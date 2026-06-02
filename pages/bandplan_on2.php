<?php
declare(strict_types=1);

$locale = current_locale();

$titleMap = [
    'fr' => 'ON2 band plan',
    'en' => 'ON2 band plan',
    'de' => 'ON2 band plan',
    'nl' => 'ON2 band plan',
    'es' => 'ON2 band plan',
    'it' => 'ON2 band plan',
    'pt' => 'ON2 band plan',
    'ar' => 'ON2 band plan',
    'hi' => 'ON2 band plan',
    'ja' => 'ON2 band plan',
    'zh' => 'ON2 band plan',
    'bn' => 'ON2 band plan',
    'ru' => 'ON2 band plan',
    'id' => 'ON2 band plan',
];
$title = i18n_localized_value($titleMap, $locale, 'en');

$headers = ['band' => 'Band', 'freq' => 'Frequencies (MHz)', 'modes' => 'Modes', 'pwr' => 'Max power*', 'notes' => 'Notes'];
$rows = [
    ['160 m', '1.810-2.000', 'CW/SSB/Data', 'Follow IARU R1 segmentation.', '100 W PEP'],
    ['80 m', '3.500-3.800', 'CW/SSB/Data', 'Phone segment in upper band.', '100 W PEP'],
    ['40 m', '7.000-7.200', 'CW/SSB/Data', 'Frequent contest usage.', '100 W PEP'],
    ['20 m', '14.000-14.350', 'CW/SSB/Data', 'FT8 commonly on 14.074 MHz.', '100 W PEP'],
    ['15 m', '21.000-21.450', 'CW/SSB/Data', 'Variable daytime propagation.', '100 W PEP'],
    ['10 m', '28.000-29.700', 'CW/SSB/FM/Data', 'Includes beacons and FM sub-band.', '100 W PEP'],
    ['6 m', '50.000-52.000', 'CW/SSB/FM/Data', 'Sporadic-E openings possible.', '100 W PEP'],
    ['2 m', '144.000-146.000', 'FM/SSB/CW/Data', 'Simplex calling on 145.500 MHz.', '50 W PEP'],
    ['70 cm', '430.000-440.000', 'FM/SSB/CW/DV', 'Respect local repeater coordination.', '50 W PEP'],
    ['23 cm', '1240-1300', 'FM/SSB/CW/Data', 'Shared band depending on local usage.', '50 W PEP'],
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
