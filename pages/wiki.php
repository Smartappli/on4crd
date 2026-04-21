<?php
declare(strict_types=1);

if (!table_exists('wiki_pages')) {
    echo render_layout('<div class="card"><h1>Wiki technique</h1><p>Le wiki sera disponible après initialisation des pages.</p></div>', 'Wiki');
    return;
}

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 120) {
    $search = mb_substr($search, 0, 120);
}

$rows = [];
try {
    if ($search === '') {
        $stmt = db()->query('SELECT slug, title, content, updated_at FROM wiki_pages ORDER BY updated_at DESC LIMIT 120');
        $rows = $stmt->fetchAll() ?: [];
    } else {
        $stmt = db()->prepare('SELECT slug, title, content, updated_at FROM wiki_pages WHERE title LIKE ? OR content LIKE ? ORDER BY updated_at DESC LIMIT 120');
        $like = '%' . $search . '%';
        $stmt->execute([$like, $like]);
        $rows = $stmt->fetchAll() ?: [];
    }
} catch (Throwable) {
    $rows = [];
}

$latestUpdate = $rows !== [] ? (string) ($rows[0]['updated_at'] ?? '') : '';
$latestUpdateLabel = $latestUpdate !== '' ? date('d/m/Y H:i', strtotime($latestUpdate)) : '—';

ob_start();
?>
<section class="card wiki-header">
    <div class="row-between">
        <h1>Wiki technique</h1>
        <?php if (has_permission('wiki.edit')): ?>
            <a class="button small" href="<?= e(base_url('index.php?route=wiki_edit')) ?>">Nouvelle page</a>
        <?php endif; ?>
    </div>
    <p class="help">Base de connaissances du radio-club : procédures, fiches techniques, guides terrain et documentation opérationnelle.</p>
    <div class="stats-grid">
        <article class="stat-card">
            <span class="help">Pages indexées</span>
            <strong><?= (int) count($rows) ?></strong>
        </article>
        <article class="stat-card">
            <span class="help">Dernière mise à jour</span>
            <strong><?= e($latestUpdateLabel) ?></strong>
        </article>
    </div>
    <form method="get" class="inline-form">
        <input type="hidden" name="route" value="wiki">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher une page (titre ou contenu)">
        <button class="button" type="submit">Rechercher</button>
        <?php if ($search !== ''): ?>
            <a class="button secondary" href="<?= e(route_url('wiki')) ?>">Réinitialiser</a>
        <?php endif; ?>
    </form>
</section>

<section class="card">
    <h2>Pages du wiki</h2>
    <?php if ($rows === []): ?>
        <p>Aucune page trouvée<?= $search !== '' ? ' pour cette recherche' : '' ?>.</p>
    <?php else: ?>
        <div class="wiki-grid">
            <?php foreach ($rows as $row):
                $summary = trim(strip_tags((string) ($row['content'] ?? '')));
                if ($summary === '') {
                    $summary = 'Consulter cette page pour accéder au contenu complet.';
                }
                if (mb_strlen($summary) > 190) {
                    $summary = mb_substr($summary, 0, 187) . '…';
                }
                ?>
                <article class="wiki-card">
                    <h3><a href="<?= e(base_url('index.php?route=wiki_view&slug=' . urlencode((string) $row['slug']))) ?>"><?= e((string) $row['title']) ?></a></h3>
                    <p class="help">Mise à jour : <?= e(date('d/m/Y H:i', strtotime((string) $row['updated_at']))) ?></p>
                    <p><?= e($summary) ?></p>
                    <p><a class="button secondary" href="<?= e(base_url('index.php?route=wiki_view&slug=' . urlencode((string) $row['slug']))) ?>">Ouvrir la page</a></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), 'Wiki');
