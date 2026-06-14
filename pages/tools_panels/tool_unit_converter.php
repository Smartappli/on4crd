<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
$unitConversionPanelId = 'tool-unit-converter';
require __DIR__ . '/tool_unit_conversions.php'; // NOSONAR - this partial must remain repeatable with a different panel id.
