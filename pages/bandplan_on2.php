<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('bandplan_on2', $locale);
$title = (string) $t['title'];
$rows = [
    ['160 m', '1.810-2.000', 'CW/SSB/Data', '100 W PEP', 'note_160m'],
    ['80 m', '3.500-3.800', 'CW/SSB/Data', '100 W PEP', 'note_80m'],
    ['40 m', '7.000-7.200', 'CW/SSB/Data', '100 W PEP', 'note_40m'],
    ['20 m', '14.000-14.350', 'CW/SSB/Data', '100 W PEP', 'note_20m'],
    ['15 m', '21.000-21.450', 'CW/SSB/Data', '100 W PEP', 'note_15m'],
    ['10 m', '28.000-29.700', 'CW/SSB/FM/Data', '100 W PEP', 'note_10m'],
    ['6 m', '50.000-52.000', 'CW/SSB/FM/Data', '100 W PEP', 'note_6m'],
    ['2 m', '144.000-146.000', 'FM/SSB/CW/Data', '50 W PEP', 'note_2m'],
    ['70 cm', '430.000-440.000', 'FM/SSB/CW/DV', '50 W PEP', 'note_70cm'],
    ['23 cm', '1240-1300', 'FM/SSB/CW/Data', '50 W PEP', 'note_23cm'],
];

echo render_bandplan_page($title, $t, $rows);
