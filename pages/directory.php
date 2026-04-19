<?php
declare(strict_types=1);

$members = [];
if (table_exists('members')) {
    $members = db()->query('SELECT callsign, full_name FROM members WHERE is_active = 1 ORDER BY callsign ASC LIMIT 200')->fetchAll();
}

ob_start();
?>
<div class="card">
    <h1>Annuaire</h1>
    <?php if ($members === []): ?>
        <p>Aucun membre actif trouvé.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($members as $member): ?>
                <li><strong><?= e((string) $member['callsign']) ?></strong> — <?= e((string) $member['full_name']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php

echo render_layout((string) ob_get_clean(), 'Annuaire');
