<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('bandplan_on3', $locale);
$title = (string) $t['title'];

$rows = [
    ['80 m', '3.500-3.600', 'CW/SSB', '10 W PEP', 'note_80m'],
    ['40 m', '7.000-7.100', 'CW/SSB', '10 W PEP', 'note_40m'],
    ['15 m', '21.100-21.200', 'SSB/Data', '10 W PEP', 'note_15m'],
    ['10 m', '28.000-29.700', 'CW/SSB/FM/Data', '10 W PEP', 'note_10m'],
    ['2 m', '144.000-146.000', 'FM/SSB/CW', '10 W PEP', 'note_2m'],
    ['70 cm', '430.000-440.000', 'FM/Relais/Numeric', '10 W PEP', 'note_70cm'],
    ['23 cm', '1240-1300', 'FM/SSB/Data', '10 W PEP', 'note_23cm'],
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
                <th><?= e((string) $t['header_band']) ?></th>
                <th><?= e((string) $t['header_freq']) ?></th>
                <th><?= e((string) $t['header_modes']) ?></th>
                <th><?= e((string) $t['header_power']) ?></th>
                <th><?= e((string) $t['header_notes']) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e((string) $row[0]) ?></td>
                    <td><?= e((string) $row[1]) ?></td>
                    <td><?= e((string) $row[2]) ?></td>
                    <td><?= e((string) $row[3]) ?></td>
                    <td><?= e((string) ($t[(string) $row[4]] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
echo render_layout((string) ob_get_clean(), $title);
