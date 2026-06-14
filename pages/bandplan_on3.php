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

echo render_bandplan_page($title, $t, $rows, 'bandplan-on3-module');
