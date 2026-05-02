<?php
declare(strict_types=1);

function article_category_logo(string $label): string
{
    $safeLabel = trim($label) !== '' ? $label : 'Catégorie';
    $initial = strtoupper((string) mb_substr($safeLabel, 0, 1, 'UTF-8'));
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96" role="img" aria-label="' . htmlspecialchars($safeLabel, ENT_QUOTES, 'UTF-8') . '"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#1d4ed8"/><stop offset="100%" stop-color="#0f172a"/></linearGradient></defs><rect width="96" height="96" rx="18" fill="url(#g)"/><text x="48" y="56" text-anchor="middle" font-size="38" font-family="Arial, sans-serif" fill="#fff">' . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') . '</text></svg>';

    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

$rows = cache_remember('articles_published_v2', 180, static fn(): array => db()->query('SELECT id, slug, title, excerpt, content, category, updated_at FROM articles WHERE status = "published" ORDER BY updated_at DESC')->fetchAll());
$themeMeta = [
    'antennes' => ['label' => 'Antennes', 'image' => null],
    'trafic' => ['label' => 'Trafic & DX', 'image' => null],
    'numerique' => ['label' => 'Modes numériques', 'image' => null],
    'materiel' => ['label' => 'Matériel & station', 'image' => null],
    'formation' => ['label' => 'Formation', 'image' => null],
    'autres' => ['label' => 'Autres thématiques', 'image' => null],
];

$themeFilter = slugify(trim((string) ($_GET['theme'] ?? '')));
$themeCounts = [];
$groupedArticles = [];

foreach ($rows as $row) {
    $theme = slugify((string) ($row['category'] ?? 'autres'));
    if ($theme === '') {
        $theme = 'autres';
    }

    if (!isset($themeMeta[$theme])) {
        $themeMeta[$theme] = [
            'label' => ucwords(str_replace('-', ' ', $theme)),
            'image' => null,
        ];
    }
    if (!isset($themeCounts[$theme])) {
        $themeCounts[$theme] = 0;
    }
    $themeCounts[$theme]++;

    if ($themeFilter !== '' && $theme !== $themeFilter) {
        continue;
    }
    $groupedArticles[$theme][] = $row;
}

if ($themeFilter !== '' && !isset($themeMeta[$themeFilter])) {
    $themeFilter = '';
}

set_page_meta([
    'title' => 'Articles techniques',
    'description' => 'Articles techniques ON4CRD sur le radioamateurisme, le matériel et la pratique.',
    'schema_type' => 'CollectionPage',
]);

ob_start();
?>
<div class="stack">
    <div class="card">
        <div class="row-between">
            <h1>Articles techniques</h1>
            <?php if (has_permission('articles.manage')): ?>
                <a class="button small" href="<?= e(base_url('index.php?route=admin_articles')) ?>">Gérer</a>
            <?php endif; ?>
        </div>
        <div class="news-grid" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px;">
            <?php foreach ($themeMeta as $themeCode => $theme): ?>
                <?php $logoUrl = (string) ($theme['image'] ?? '');
                if ($logoUrl === '') {
                    $logoUrl = article_category_logo((string) $theme['label']);
                }
                ?>
                <a class="feature-card" href="<?= e(route_url('articles', ['theme' => $themeCode])) ?>"<?= $themeFilter === $themeCode ? ' aria-current="page"' : '' ?> style="display:flex;flex-direction:column;align-items:center;gap:8px;text-align:center;text-decoration:none;">
                    <img src="<?= e($logoUrl) ?>" alt="<?= e((string) $theme['label']) ?>" loading="lazy" width="72" height="72" style="width:72px;height:72px;object-fit:cover;border-radius:16px;">
                    <span><?= e((string) $theme['label']) ?></span>
                    <small><?= (int) ($themeCounts[$themeCode] ?? 0) ?> article(s)</small>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if ($themeFilter !== ''): ?>
            <p><a class="pill" href="<?= e(route_url('articles')) ?>">Réinitialiser le filtre</a></p>
        <?php endif; ?>
    </div>

    <?php if ($groupedArticles === []): ?>
        <div class="card">
            <p>Aucun article disponible pour cette thématique.</p>
        </div>
    <?php else: ?>
        <?php foreach ($groupedArticles as $themeCode => $themeRows): ?>
            <section class="card">
                <h2><?= e((string) ($themeMeta[$themeCode]['label'] ?? 'Thématique')) ?></h2>
                <div class="news-grid">
                    <?php foreach ($themeRows as $row): ?>
                        <?php $row = localized_article_row($row); ?>
                        <article class="news-card feature-card">
                            <span class="badge muted"><?= e((string) ($themeMeta[$themeCode]['label'] ?? 'Thématique')) ?></span>
                            <h3><a href="<?= e(base_url('index.php?route=article&slug=' . urlencode((string) $row['slug']))) ?>"><?= e((string) $row['title_localized']) ?></a></h3>
                            <p><?= e((string) $row['excerpt_localized']) ?></p>
                            <p><a class="button secondary" href="<?= e(base_url('index.php?route=article&slug=' . urlencode((string) $row['slug']))) ?>">Lire l’article</a></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Articles');
