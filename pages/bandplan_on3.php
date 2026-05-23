<?php
declare(strict_types=1);

require_login();

$locale = current_locale();
$static = require __DIR__ . '/../app/i18n/static_pages_legacy.php';
$domain = $static['bandplan_on3'] ?? [];
$t = is_array($domain[$locale] ?? null) ? $domain[$locale] : ($domain['fr'] ?? []);

$title = (string) ($t['title'] ?? 'ON3 Band Plan');
$ibptLabel = (string) ($t['ibpt_label'] ?? 'IBPT/BIPT');
$headers = is_array($t['headers'] ?? null) ? $t['headers'] : [];
$rows = is_array($t['rows'] ?? null) ? $t['rows'] : [];

ob_start();
?>
<section class="card">
    <h1><?= e($title) ?></h1>
    <p class="help"><?= e($ibptLabel) ?>:
        <a href="https://www.ibpt.be/file/cc73d96153bbd5448a56f19d925d05b1379c7f21/1891ad4029fa18396c037433ed4c2a063854f1b0/freq-fr.pdf?name=Freq-FR.pdf&type=application%2Fpdf" target="_blank" rel="noopener noreferrer">Freq-FR.pdf</a>
    </p>
    <div class="table-wrap mt-3">
        <table>
            <thead>
            <tr>
                <th><?= e((string) ($headers['band'] ?? 'Band')) ?></th>
                <th><?= e((string) ($headers['freq'] ?? 'Frequencies (MHz)')) ?></th>
                <th><?= e((string) ($headers['modes'] ?? 'Modes')) ?></th>
                <th><?= e((string) ($headers['pwr'] ?? 'Max power*')) ?></th>
                <th><?= e((string) ($headers['notes'] ?? 'Notes')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php if (!is_array($row) || count($row) < 5) { continue; } ?>
                <tr>
                    <td><?= e((string) $row[0]) ?></td>
                    <td><?= e((string) $row[1]) ?></td>
                    <td><?= e((string) $row[2]) ?></td>
                    <td><?= e((string) $row[4]) ?></td>
                    <td><?= e((string) $row[3]) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
echo render_layout((string) ob_get_clean(), $title);
