<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('bandplan_harec', $locale);
$title = (string) $t['title'];
$rows = [
    ['160 m', '1.810-2.000', 'CW/SSB', '1500 W PEP', 'note_160m'],
    ['80 m', '3.500-3.800', 'CW/SSB/Data', '1500 W PEP', 'note_80m'],
    ['60 m', '5.3515-5.3665', 'SSB/Data', '100 W PEP', 'note_60m'],
    ['40 m', '7.000-7.200', 'CW/SSB/Data', '1500 W PEP', 'note_40m'],
    ['30 m', '10.100-10.150', 'CW/Data', '500 W PEP', 'note_30m'],
    ['20 m', '14.000-14.350', 'CW/SSB/Data', '1500 W PEP', 'note_20m'],
    ['17 m', '18.068-18.168', 'CW/SSB/Data', '1500 W PEP', 'note_17m'],
    ['15 m', '21.000-21.450', 'CW/SSB/Data', '1500 W PEP', 'note_15m'],
    ['12 m', '24.890-24.990', 'CW/SSB/Data', '1500 W PEP', 'note_12m'],
    ['10 m', '28.000-29.700', 'CW/SSB/FM/Data', '1500 W PEP', 'note_10m'],
    ['6 m', '50.000-52.000', 'CW/SSB/FM/Data', '400 W PEP', 'note_6m'],
    ['2 m', '144.000-146.000', 'FM/SSB/CW', '120 W PEP', 'note_2m'],
    ['70 cm', '430.000-440.000', 'FM/Relais/Numeric', '120 W PEP', 'note_70cm'],
    ['23 cm', '1240-1300', 'FM/SSB/CW/Data', '120 W PEP', 'note_23cm'],
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
                <th><?= e((string) $t['header_band']) ?></th>
                <th><?= e((string) $t['header_freq']) ?></th>
                <th><?= e((string) $t['header_modes']) ?></th>
                <th><?= e((string) $t['header_power']) ?></th>
                <th><?= e((string) $t['header_notes']) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= e($r[0]) ?></td>
                    <td><?= e($r[1]) ?></td>
                    <td><?= e($r[2]) ?></td>
                    <td><?= e($r[3]) ?></td>
                    <td><?= e((string) ($t[(string) $r[4]] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
echo render_layout((string) ob_get_clean(), $title);
