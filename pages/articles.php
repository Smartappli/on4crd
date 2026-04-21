<?php
declare(strict_types=1);

$rows = cache_remember('articles_published_v1', 180, static fn(): array => db()->query('SELECT id, slug, title, excerpt, content, updated_at FROM articles WHERE status = "published" ORDER BY updated_at DESC')->fetchAll());
$themeMap = [
    'antennes' => ['antenne', 'dipôle', 'yagi', 'vertical', 'directive', 'beam'],
    'trafic' => ['dx', 'qso', 'contest', 'trafic', 'propagation'],
    'numerique' => ['numérique', 'digital', 'ft8', 'd-star', 'dmr', 'c4fm'],
    'materiel' => ['émetteur', 'récepteur', 'transceiver', 'matériel', 'station', 'swr', 'ampli'],
    'formation' => ['débutant', 'licence', 'formation', 'guide', 'tutoriel', 'pédagogique'],
];
$themeLabels = [
    'antennes' => 'Antennes',
    'trafic' => 'Trafic & DX',
    'numerique' => 'Modes numériques',
    'materiel' => 'Matériel & station',
    'formation' => 'Formation',
    'autres' => 'Autres thématiques',
];
$themeFilter = trim((string) ($_GET['theme'] ?? ''));
if ($themeFilter !== '' && !array_key_exists($themeFilter, $themeLabels)) {
    $themeFilter = '';
}

$detectTheme = static function (array $article) use ($themeMap): string {
    $haystack = mb_strtolower(trim((string) ($article['title'] ?? '') . ' ' . (string) ($article['excerpt'] ?? '') . ' ' . strip_tags((string) ($article['content'] ?? ''))));
    foreach ($themeMap as $theme => $keywords) {
        foreach ($keywords as $keyword) {
            if (str_contains($haystack, mb_strtolower($keyword))) {
                return $theme;
            }
        }
    }
    return 'autres';
};

$groupedArticles = [];
$themeCounts = array_fill_keys(array_keys($themeLabels), 0);
foreach ($rows as $row) {
    $theme = $detectTheme($row);
    if (!isset($themeCounts[$theme])) {
        $themeCounts[$theme] = 0;
    }
    $themeCounts[$theme]++;
    if ($themeFilter !== '' && $theme !== $themeFilter) {
        continue;
    }
    $groupedArticles[$theme][] = $row;
}

set_page_meta([
    'title' => 'Articles techniques',
    'description' => 'Articles techniques ON4CRD sur le radioamateurisme, le matériel et la pratique.',
    'schema_type' => 'CollectionPage',
]);

ob_start();
?>
<div class="card">
    <div class="row-between">
        <h1>Articles techniques</h1>
        <?php if (has_permission('articles.manage')): ?>
            <a class="button small" href="<?= e(base_url('index.php?route=admin_articles')) ?>">Gérer</a>
        <?php endif; ?>
    </div>
    <p class="help">Les articles sont écrits en français. Les autres langues sont générées automatiquement à partir de cette version source.</p>
    <div class="pill-row">
        <?php foreach ($themeLabels as $themeCode => $themeLabel): ?>
            <a class="pill" href="<?= e(route_url('articles', ['theme' => $themeCode])) ?>"<?= $themeFilter === $themeCode ? ' aria-current="page"' : '' ?>>
                <?= e($themeLabel) ?> · <?= (int) ($themeCounts[$themeCode] ?? 0) ?>
            </a>
        <?php endforeach; ?>
        <?php if ($themeFilter !== ''): ?>
            <a class="pill" href="<?= e(route_url('articles')) ?>">Réinitialiser</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($groupedArticles === []): ?>
    <div class="card">
        <p>Aucun article disponible pour cette thématique.</p>
    </div>
<?php else: ?>
    <?php foreach ($groupedArticles as $themeCode => $themeRows): ?>
        <section class="card">
            <h2><?= e((string) ($themeLabels[$themeCode] ?? 'Thématique')) ?></h2>
            <div class="news-grid">
                <?php foreach ($themeRows as $row): ?>
                    <?php $row = localized_article_row($row); ?>
                    <article class="news-card feature-card">
                        <span class="badge muted"><?= e((string) ($themeLabels[$themeCode] ?? 'Thématique')) ?></span>
                        <h3><a href="<?= e(base_url('index.php?route=article&slug=' . urlencode((string) $row['slug']))) ?>"><?= e((string) $row['title_localized']) ?></a></h3>
                        <p><?= e((string) $row['excerpt_localized']) ?></p>
                        <p><a class="button secondary" href="<?= e(base_url('index.php?route=article&slug=' . urlencode((string) $row['slug']))) ?>">Lire l’article</a></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
<?php
echo render_layout((string) ob_get_clean(), 'Articles');
