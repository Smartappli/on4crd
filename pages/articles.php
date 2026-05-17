<?php
declare(strict_types=1);

$GLOBALS['articles_i18n'] = [];

function article_category_logo(string $label): string
{
    $safeLabel = trim($label) !== '' ? $label : ((string) ($GLOBALS['articles_i18n']['default_category'] ?? 'Catégorie'));
    $initial = strtoupper((string) mb_substr($safeLabel, 0, 1, 'UTF-8'));
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96" role="img" aria-label="' . htmlspecialchars($safeLabel, ENT_QUOTES, 'UTF-8') . '"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#1d4ed8"/><stop offset="100%" stop-color="#0f172a"/></linearGradient></defs><rect width="96" height="96" rx="18" fill="url(#g)"/><text x="48" y="56" text-anchor="middle" font-size="38" font-family="Arial, sans-serif" fill="#fff">' . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') . '</text></svg>';

    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}


$locale = current_locale();
$i18n = [
    'fr' => [
        'default_category' => 'Catégorie',
        'theme_default' => 'Thématique',
        'page_title' => 'Articles techniques',
        'page_description' => 'Articles techniques ON4CRD sur le radioamateurisme, le matériel et la pratique.',
        'manage' => 'Gérer',
        'article_count' => 'article(s)',
        'reset_filter' => 'Réinitialiser le filtre',
        'search_placeholder' => 'Rechercher un article',
        'search' => 'Rechercher',
        'reset_search' => 'Réinitialiser la recherche',
        'no_article_for_theme' => 'Aucun article disponible pour cette thématique.',
        'read_article' => 'Lire l’article',
        'layout_title' => 'Articles',
        'page' => 'Page',
        'previous' => 'Précédent',
        'next' => 'Suivant',
        'theme_antennes' => 'Antennes',
        'theme_trafic' => 'Trafic & DX',
        'theme_numerique' => 'Modes numériques',
        'theme_materiel' => 'Matériel & station',
        'theme_formation' => 'Formation',
        'theme_autres' => 'Autres thématiques',
    ],
    'en' => [
        'default_category' => 'Category',
        'theme_default' => 'Theme',
        'page_title' => 'Technical articles',
        'page_description' => 'ON4CRD technical articles about amateur radio, equipment and operations.',
        'manage' => 'Manage',
        'article_count' => 'article(s)',
        'reset_filter' => 'Reset filter',
        'search_placeholder' => 'Search for an article',
        'search' => 'Search',
        'reset_search' => 'Reset search',
        'no_article_for_theme' => 'No articles available for this theme.',
        'read_article' => 'Read article',
        'layout_title' => 'Articles',
        'page' => 'Page',
        'previous' => 'Previous',
        'next' => 'Next',
        'theme_antennes' => 'Antennas',
        'theme_trafic' => 'Traffic & DX',
        'theme_numerique' => 'Digital modes',
        'theme_materiel' => 'Equipment & station',
        'theme_formation' => 'Training',
        'theme_autres' => 'Other topics',
    ],
    'de' => [
        'default_category' => 'Kategorie',
        'theme_default' => 'Thema',
        'page_title' => 'Technische Artikel',
        'page_description' => 'Technische ON4CRD-Artikel über Amateurfunk, Ausrüstung und Praxis.',
        'manage' => 'Verwalten',
        'article_count' => 'Artikel',
        'reset_filter' => 'Filter zurücksetzen',
        'search_placeholder' => 'Artikel suchen',
        'search' => 'Suchen',
        'reset_search' => 'Suche zurücksetzen',
        'no_article_for_theme' => 'Keine Artikel für dieses Thema verfügbar.',
        'read_article' => 'Artikel lesen',
        'layout_title' => 'Artikel',
        'page' => 'Seite',
        'previous' => 'Zurück',
        'next' => 'Weiter',
        'theme_antennes' => 'Antennen',
        'theme_trafic' => 'Verkehr & DX',
        'theme_numerique' => 'Digitale Modi',
        'theme_materiel' => 'Ausrüstung & Station',
        'theme_formation' => 'Ausbildung',
        'theme_autres' => 'Weitere Themen',
    ],
    'es' => [
        'default_category' => 'Categoría', 'theme_default' => 'Temática', 'page_title' => 'Artículos técnicos', 'page_description' => 'Artículos técnicos de ON4CRD sobre radioafición, equipos y práctica.', 'manage' => 'Gestionar', 'article_count' => 'artículo(s)', 'reset_filter' => 'Restablecer filtro', 'search_placeholder' => 'Buscar un artículo', 'search' => 'Buscar', 'reset_search' => 'Restablecer búsqueda', 'no_article_for_theme' => 'No hay artículos disponibles para esta temática.', 'read_article' => 'Leer artículo', 'layout_title' => 'Artículos', 'page' => 'Página', 'previous' => 'Anterior', 'next' => 'Siguiente', 'theme_antennes' => 'Antenas', 'theme_trafic' => 'Tráfico y DX', 'theme_numerique' => 'Modos digitales', 'theme_materiel' => 'Equipos y estación', 'theme_formation' => 'Formación', 'theme_autres' => 'Otros temas',
    ],
    'it' => [
        'default_category' => 'Categoria', 'theme_default' => 'Tema', 'page_title' => 'Articoli tecnici', 'page_description' => 'Articoli tecnici ON4CRD su radioamatori, attrezzature e pratica.', 'manage' => 'Gestisci', 'article_count' => 'articolo/i', 'reset_filter' => 'Reimposta filtro', 'search_placeholder' => 'Cerca un articolo', 'search' => 'Cerca', 'reset_search' => 'Reimposta ricerca', 'no_article_for_theme' => 'Nessun articolo disponibile per questo tema.', 'read_article' => 'Leggi articolo', 'layout_title' => 'Articoli', 'page' => 'Pagina', 'previous' => 'Precedente', 'next' => 'Successiva', 'theme_antennes' => 'Antenne', 'theme_trafic' => 'Traffico e DX', 'theme_numerique' => 'Modi digitali', 'theme_materiel' => 'Attrezzature e stazione', 'theme_formation' => 'Formazione', 'theme_autres' => 'Altri temi',
    ],
    'pt' => [
        'default_category' => 'Categoria', 'theme_default' => 'Tema', 'page_title' => 'Artigos técnicos', 'page_description' => 'Artigos técnicos ON4CRD sobre radioamadorismo, equipamento e prática.', 'manage' => 'Gerir', 'article_count' => 'artigo(s)', 'reset_filter' => 'Repor filtro', 'search_placeholder' => 'Pesquisar um artigo', 'search' => 'Pesquisar', 'reset_search' => 'Repor pesquisa', 'no_article_for_theme' => 'Não há artigos disponíveis para este tema.', 'read_article' => 'Ler artigo', 'layout_title' => 'Artigos', 'page' => 'Página', 'previous' => 'Anterior', 'next' => 'Seguinte', 'theme_antennes' => 'Antenas', 'theme_trafic' => 'Tráfego e DX', 'theme_numerique' => 'Modos digitais', 'theme_materiel' => 'Equipamento e estação', 'theme_formation' => 'Formação', 'theme_autres' => 'Outros temas',
    ],
    'nl' => [
        'default_category' => 'Categorie',
        'theme_default' => 'Thema',
        'page_title' => 'Technische artikels',
        'page_description' => 'ON4CRD technische artikels over radioamateurisme, materiaal en praktijk.',
        'manage' => 'Beheren',
        'article_count' => 'artikel(en)',
        'reset_filter' => 'Filter resetten',
        'search_placeholder' => 'Zoek een artikel',
        'search' => 'Zoeken',
        'reset_search' => 'Zoekopdracht resetten',
        'no_article_for_theme' => 'Geen artikelen beschikbaar voor dit thema.',
        'read_article' => 'Artikel lezen',
        'layout_title' => 'Artikels',
        'page' => 'Pagina',
        'previous' => 'Vorige',
        'next' => 'Volgende',
        'theme_antennes' => 'Antennes',
        'theme_trafic' => 'Verkeer & DX',
        'theme_numerique' => 'Digitale modi',
        'theme_materiel' => 'Materiaal & station',
        'theme_formation' => 'Opleiding',
        'theme_autres' => 'Andere thema’s',
    ],
];
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $pool = [];
    foreach ($i18n as $lang => $translations) {
        if (isset($translations[$key]) && is_string($translations[$key])) {
            $pool[$lang] = $translations[$key];
        }
    }

    $value = trim(i18n_localized_value($pool, $locale, 'fr'));
    if ($value === '') {
        $value = trim((string) ($i18n['fr'][$key] ?? ''));
    }
    $t[$key] = $value;
}
$GLOBALS['articles_i18n'] = $t;

$themeMeta = [
    'antennes' => ['label' => (string) $t['theme_antennes'], 'image' => null],
    'trafic' => ['label' => (string) $t['theme_trafic'], 'image' => null],
    'numerique' => ['label' => (string) $t['theme_numerique'], 'image' => null],
    'materiel' => ['label' => (string) $t['theme_materiel'], 'image' => null],
    'formation' => ['label' => (string) $t['theme_formation'], 'image' => null],
    'autres' => ['label' => (string) $t['theme_autres'], 'image' => null],
];

$themeFilter = slugify(trim((string) ($_GET['theme'] ?? '')));
$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 120) {
    $search = mb_substr($search, 0, 120);
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$themeCounts = cache_remember('articles_theme_counts_v1', 180, static function (): array {
    $counts = [];
    $rows = db()->query('SELECT category, COUNT(*) AS total FROM articles WHERE status = "published" GROUP BY category')->fetchAll() ?: [];
    foreach ($rows as $row) {
        $theme = slugify((string) ($row['category'] ?? 'autres'));
        if ($theme === '') {
            $theme = 'autres';
        }
        $counts[$theme] = (int) ($row['total'] ?? 0);
    }
    return $counts;
});
foreach (array_keys($themeCounts) as $themeCode) {
    if (!isset($themeMeta[$themeCode])) {
        $themeMeta[$themeCode] = ['label' => ucwords(str_replace('-', ' ', $themeCode)), 'image' => null];
    }
}

$whereParts = ['status = "published"'];
$whereParams = [];
if ($themeFilter !== '') {
    $whereParts[] = 'category = ?';
    $whereParams[] = $themeFilter;
}
if ($search !== '') {
    $whereParts[] = '(title LIKE ? OR excerpt LIKE ? OR content LIKE ?)';
    $like = '%' . $search . '%';
    $whereParams[] = $like;
    $whereParams[] = $like;
    $whereParams[] = $like;
}
$whereSql = 'WHERE ' . implode(' AND ', $whereParts);
$countStmt = db()->prepare('SELECT COUNT(*) FROM articles ' . $whereSql);
$countStmt->execute($whereParams);
$totalArticles = (int) $countStmt->fetchColumn();
$maxPage = max(1, (int) ceil($totalArticles / $perPage));
if ($page > $maxPage) {
    $page = $maxPage;
}
$offset = ($page - 1) * $perPage;
$dataStmt = db()->prepare('SELECT id, slug, title, excerpt, content, category, updated_at FROM articles ' . $whereSql . ' ORDER BY updated_at DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);
$dataStmt->execute($whereParams);
$pagedRows = $dataStmt->fetchAll() ?: [];
$groupedArticles = [];
foreach ($pagedRows as $row) {
    $theme = slugify((string) ($row['category'] ?? 'autres'));
    if ($theme === '') {
        $theme = 'autres';
    }
    $groupedArticles[$theme][] = $row;
}

if ($themeFilter !== '' && !isset($themeMeta[$themeFilter])) {
    $themeFilter = '';
}

set_page_meta([
    'title' => (string) $t['page_title'],
    'description' => (string) $t['page_description'],
    'schema_type' => 'CollectionPage',
]);

ob_start();
?>
<div class="stack">
    <div class="card">
        <div class="row-between">
            <h1><?= e((string) $t['page_title']) ?></h1>
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
                    <small><?= (int) ($themeCounts[$themeCode] ?? 0) ?> <?= e((string) $t['article_count']) ?></small>
                </a>
            <?php endforeach; ?>
        </div>
        <form method="get" class="inline-form" style="margin: 1rem 0 0;">
            <input type="hidden" name="route" value="articles">
            <?php if ($themeFilter !== ''): ?>
                <input type="hidden" name="theme" value="<?= e($themeFilter) ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_placeholder']) ?>">
            <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
            <?php if ($search !== ''): ?>
                <a class="button secondary" href="<?= e(route_url('articles', array_filter(['theme' => $themeFilter], static fn($v): bool => $v !== ''))) ?>"><?= e((string) $t['reset_search']) ?></a>
            <?php endif; ?>
        </form>
        <?php if ($themeFilter !== ''): ?>
            <p><a class="pill" href="<?= e(route_url('articles', array_filter(['q' => $search], static fn($v): bool => $v !== ''))) ?>"><?= e((string) $t['reset_filter']) ?></a></p>
        <?php endif; ?>
    </div>

    <?php if ($groupedArticles === []): ?>
        <div class="card">
            <p><?= e((string) $t['no_article_for_theme']) ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($groupedArticles as $themeCode => $themeRows): ?>
            <section class="card">
                <h2><?= e((string) ($themeMeta[$themeCode]['label'] ?? (string) $t['theme_default'])) ?></h2>
                <div class="news-grid">
                    <?php foreach ($themeRows as $row): ?>
                        <?php $row = localized_article_row($row); ?>
                        <article class="news-card feature-card">
                            <span class="badge muted"><?= e((string) ($themeMeta[$themeCode]['label'] ?? (string) $t['theme_default'])) ?></span>
                            <h3><a href="<?= e(base_url('index.php?route=article&slug=' . urlencode((string) $row['slug']))) ?>"><?= e((string) $row['title_localized']) ?></a></h3>
                            <p><?= e((string) $row['excerpt_localized']) ?></p>
                            <p><a class="button secondary" href="<?= e(base_url('index.php?route=article&slug=' . urlencode((string) $row['slug']))) ?>"><?= e((string) $t['read_article']) ?></a></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if ($maxPage > 1): ?>
        <div class="card actions">
            <?php if ($page > 1): ?>
                <a class="button secondary" href="<?= e(route_url('articles', array_filter(['theme' => $themeFilter, 'q' => $search, 'page' => $page - 1], static fn($v): bool => $v !== ''))) ?>"><?= e((string) $t['previous']) ?></a>
            <?php endif; ?>
            <span class="pill"><?= e((string) $t['page']) ?> <?= $page ?> / <?= $maxPage ?></span>
            <?php if ($page < $maxPage): ?>
                <a class="button secondary" href="<?= e(route_url('articles', array_filter(['theme' => $themeFilter, 'q' => $search, 'page' => $page + 1], static fn($v): bool => $v !== ''))) ?>"><?= e((string) $t['next']) ?></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout_title']);
