<?php
declare(strict_types=1);

return [
    'locators' => [
        ['id' => 'tool-grid', 'title_key' => 'grid_title'],
        ['id' => 'tool-distance', 'title_key' => 'distance'],
    ],
    'conversion' => [
        ['id' => 'tool-freq-wave', 'title_pattern' => 'conv_dot', 'left_key' => 'conv', 'right_key' => 'freq_wave'],
        ['id' => 'tool-power', 'title_pattern' => 'conv_dot', 'left_key' => 'conv', 'right_key' => 'power'],
        ['id' => 'tool-unit-conversions', 'title' => 'Conversion d’unités'],
        ['id' => 'tool-filter', 'title_key' => 'filter_calc'],
        ['id' => 'tool-balun', 'title_key' => 'balun_calc'],
        ['id' => 'tool-swr', 'title_key' => 'swr_calc'],
        ['id' => 'tool-fspl', 'title_key' => 'fspl_calc'],
        ['id' => 'tool-runtime', 'title_key' => 'runtime_calc'],
        ['id' => 'tool-coax', 'title_key' => 'coax_calc'],
        ['id' => 'tool-bandwidth', 'title_key' => 'bandwidth_calc'],
        ['id' => 'tool-duty', 'title_key' => 'duty_cycle_calc'],
        ['id' => 'tool-divider', 'title_key' => 'divider_calc'],
        ['id' => 'tool-resistor-combo', 'title_key' => 'resistor_combo_calc'],
        ['id' => 'tool-mismatch', 'title_key' => 'mismatch_loss_calc'],
        ['id' => 'tool-xl', 'title_key' => 'xl_calc'],
        ['id' => 'tool-xc', 'title_key' => 'xc_calc'],
    ],

    'antenna' => [
        ['id' => 'tool-quarter-wave', 'title_key' => 'quarter_wave_calc'],
        ['id' => 'tool-erp', 'title_key' => 'erp_calc'],
        ['id' => 'tool-dipole', 'title_key' => 'dipole_calc'],
    ],
    'power' => [
        ['id' => 'tool-solar', 'title_key' => 'solar_calc'],
        ['id' => 'tool-battery-current', 'title_key' => 'battery_current_calc'],
    ],
    'advanced_propagation' => [
        ['id' => 'tool-muf', 'title_key' => 'muftool_calc'],
        ['id' => 'tool-skip', 'title_key' => 'skip_calc'],
    ],
    'rf_measures' => [
        ['id' => 'tool-eirp', 'title_key' => 'eirp_calc'],
    ],
    'radio_math' => [
        ['id' => 'tool-db-sum', 'title_key' => 'db_sum_calc'],
        ['id' => 'tool-dbuv', 'title_key' => 'dbuv_calc'],
        ['id' => 'tool-gain-conv', 'title_key' => 'gain_conv_calc'],
        ['id' => 'tool-dbw', 'title' => 'dBm ↔ dBW'],
        ['id' => 'tool-ohm-law', 'title_key' => 'ohm_law_calc'],
        ['id' => 'tool-link-budget', 'title_key' => 'link_budget_calc'],
    ],
];
