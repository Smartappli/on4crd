<?php
declare(strict_types=1);

$locale = current_locale();
$newsI18n = [
    'fr' => ['title' => 'Actualités', 'unavailable' => 'Le flux actualités sera disponible après publication des premiers contenus.', 'latest_news' => 'Dernières actualités', 'unknown_date' => 'Date non définie', 'latest_fallback_excerpt' => 'Consultez la dernière publication du club.', 'default_section' => 'Actualité', 'published_on' => 'Publié le', 'read_news' => 'Lire l’actualité →', 'no_news_yet' => 'Aucune actualité publiée pour le moment.', 'search_title' => 'Recherche d’actualités', 'search_lead' => 'Trouvez rapidement une publication par mot-clé, période ou catégorie.', 'active_filters' => 'filtre(s) actif(s)', 'keywords' => 'Mots-clés', 'keywords_placeholder' => 'Ex. : contest, réunion, atelier...', 'period' => 'Période', 'category' => 'Catégorie', 'all_categories' => 'Toutes les catégories', 'sort_by' => 'Trier par', 'sort_recent' => 'Plus récentes', 'sort_oldest' => 'Plus anciennes', 'sort_title' => 'Titre (A→Z)', 'apply_filters' => 'Appliquer les filtres', 'reset' => 'Réinitialiser', 'applied_filters' => 'Filtres appliqués :', 'search_filter' => 'Recherche', 'month_filter' => 'Mois', 'category_filter' => 'Catégorie', 'news_overview' => 'Aperçu des actualités', 'no_match' => 'Aucune actualité ne correspond à vos filtres.', 'no_match_help' => 'Essayez de supprimer un filtre ou de lancer une recherche plus large.', 'card_fallback_excerpt' => 'Lire cette actualité pour découvrir les informations complètes.', 'view_article' => 'Voir l’article →', 'page' => 'Page', 'previous' => 'Précédent', 'next' => 'Suivant', 'news_count' => 'actualité(s)'],
    'en' => ['title' => 'News', 'unavailable' => 'The news feed will be available after the first publications.', 'latest_news' => 'Latest news', 'unknown_date' => 'Unknown date', 'latest_fallback_excerpt' => 'Read the club’s latest publication.', 'default_section' => 'News', 'published_on' => 'Published on', 'read_news' => 'Read news →', 'no_news_yet' => 'No news published yet.', 'search_title' => 'News search', 'search_lead' => 'Quickly find a post by keyword, period or category.', 'active_filters' => 'active filter(s)', 'keywords' => 'Keywords', 'keywords_placeholder' => 'E.g.: contest, meeting, workshop...', 'period' => 'Period', 'category' => 'Category', 'all_categories' => 'All categories', 'sort_by' => 'Sort by', 'sort_recent' => 'Most recent', 'sort_oldest' => 'Oldest', 'sort_title' => 'Title (A→Z)', 'apply_filters' => 'Apply filters', 'reset' => 'Reset', 'applied_filters' => 'Applied filters:', 'search_filter' => 'Search', 'month_filter' => 'Month', 'category_filter' => 'Category', 'news_overview' => 'News overview', 'no_match' => 'No news matches your filters.', 'no_match_help' => 'Try removing a filter or using a broader search.', 'card_fallback_excerpt' => 'Open this news item to read full details.', 'view_article' => 'View article →', 'page' => 'Page', 'previous' => 'Previous', 'next' => 'Next', 'news_count' => 'news item(s)'],
    'de' => ['title' => 'Neuigkeiten', 'unavailable' => 'Der Nachrichtenbereich wird nach den ersten Veröffentlichungen verfügbar sein.', 'latest_news' => 'Neueste Nachrichten', 'unknown_date' => 'Unbekanntes Datum', 'latest_fallback_excerpt' => 'Sehen Sie sich die neueste Clubveröffentlichung an.', 'default_section' => 'Nachricht', 'published_on' => 'Veröffentlicht am', 'read_news' => 'Nachricht lesen →', 'no_news_yet' => 'Derzeit keine veröffentlichten Nachrichten.', 'search_title' => 'Nachrichten-Suche', 'search_lead' => 'Finden Sie schnell einen Beitrag nach Stichwort, Zeitraum oder Kategorie.', 'active_filters' => 'aktive(r) Filter', 'keywords' => 'Stichwörter', 'keywords_placeholder' => 'Z. B.: Contest, Treffen, Workshop...', 'period' => 'Zeitraum', 'category' => 'Kategorie', 'all_categories' => 'Alle Kategorien', 'sort_by' => 'Sortieren nach', 'sort_recent' => 'Neueste zuerst', 'sort_oldest' => 'Älteste zuerst', 'sort_title' => 'Titel (A→Z)', 'apply_filters' => 'Filter anwenden', 'reset' => 'Zurücksetzen', 'applied_filters' => 'Aktive Filter:', 'search_filter' => 'Suche', 'month_filter' => 'Monat', 'category_filter' => 'Kategorie', 'news_overview' => 'Nachrichtenübersicht', 'no_match' => 'Keine Nachrichten entsprechen Ihren Filtern.', 'no_match_help' => 'Versuchen Sie, einen Filter zu entfernen oder breiter zu suchen.', 'card_fallback_excerpt' => 'Öffnen Sie diese Nachricht für vollständige Informationen.', 'view_article' => 'Artikel anzeigen →', 'page' => 'Seite', 'previous' => 'Zurück', 'next' => 'Weiter', 'news_count' => 'Nachricht(en)'],
    'es' => ['title' => 'Noticias', 'unavailable' => 'El flujo de noticias estará disponible tras las primeras publicaciones.', 'latest_news' => 'Últimas noticias', 'unknown_date' => 'Fecha no definida', 'latest_fallback_excerpt' => 'Consulte la última publicación del club.', 'default_section' => 'Noticia', 'published_on' => 'Publicado el', 'read_news' => 'Leer noticia →', 'no_news_yet' => 'No hay noticias publicadas por el momento.', 'search_title' => 'Búsqueda de noticias', 'search_lead' => 'Encuentre rápidamente una publicación por palabra clave, período o categoría.', 'active_filters' => 'filtro(s) activo(s)', 'keywords' => 'Palabras clave', 'keywords_placeholder' => 'Ej.: concurso, reunión, taller...', 'period' => 'Período', 'category' => 'Categoría', 'all_categories' => 'Todas las categorías', 'sort_by' => 'Ordenar por', 'sort_recent' => 'Más recientes', 'sort_oldest' => 'Más antiguas', 'sort_title' => 'Título (A→Z)', 'apply_filters' => 'Aplicar filtros', 'reset' => 'Restablecer', 'applied_filters' => 'Filtros aplicados:', 'search_filter' => 'Búsqueda', 'month_filter' => 'Mes', 'category_filter' => 'Categoría', 'news_overview' => 'Resumen de noticias', 'no_match' => 'Ninguna noticia coincide con sus filtros.', 'no_match_help' => 'Intente quitar un filtro o ampliar la búsqueda.', 'card_fallback_excerpt' => 'Abra esta noticia para ver toda la información.', 'view_article' => 'Ver artículo →', 'page' => 'Página', 'previous' => 'Anterior', 'next' => 'Siguiente', 'news_count' => 'noticia(s)'],
    'it' => ['title' => 'Notizie', 'unavailable' => 'Il feed notizie sarà disponibile dopo le prime pubblicazioni.', 'latest_news' => 'Ultime notizie', 'unknown_date' => 'Data non definita', 'latest_fallback_excerpt' => 'Consulta l’ultima pubblicazione del club.', 'default_section' => 'Notizia', 'published_on' => 'Pubblicato il', 'read_news' => 'Leggi notizia →', 'no_news_yet' => 'Nessuna notizia pubblicata al momento.', 'search_title' => 'Ricerca notizie', 'search_lead' => 'Trova rapidamente una pubblicazione per parola chiave, periodo o categoria.', 'active_filters' => 'filtro/i attivo/i', 'keywords' => 'Parole chiave', 'keywords_placeholder' => 'Es.: contest, riunione, workshop...', 'period' => 'Periodo', 'category' => 'Categoria', 'all_categories' => 'Tutte le categorie', 'sort_by' => 'Ordina per', 'sort_recent' => 'Più recenti', 'sort_oldest' => 'Più vecchie', 'sort_title' => 'Titolo (A→Z)', 'apply_filters' => 'Applica filtri', 'reset' => 'Reimposta', 'applied_filters' => 'Filtri applicati:', 'search_filter' => 'Ricerca', 'month_filter' => 'Mese', 'category_filter' => 'Categoria', 'news_overview' => 'Panoramica notizie', 'no_match' => 'Nessuna notizia corrisponde ai filtri.', 'no_match_help' => 'Prova a rimuovere un filtro o ampliare la ricerca.', 'card_fallback_excerpt' => 'Apri questa notizia per i dettagli completi.', 'view_article' => 'Vedi articolo →', 'page' => 'Pagina', 'previous' => 'Precedente', 'next' => 'Successiva', 'news_count' => 'notizia/e'],
    'pt' => ['title' => 'Notícias', 'unavailable' => 'O feed de notícias estará disponível após as primeiras publicações.', 'latest_news' => 'Últimas notícias', 'unknown_date' => 'Data não definida', 'latest_fallback_excerpt' => 'Consulte a publicação mais recente do clube.', 'default_section' => 'Notícia', 'published_on' => 'Publicado em', 'read_news' => 'Ler notícia →', 'no_news_yet' => 'Ainda não há notícias publicadas.', 'search_title' => 'Pesquisa de notícias', 'search_lead' => 'Encontre rapidamente uma publicação por palavra-chave, período ou categoria.', 'active_filters' => 'filtro(s) ativo(s)', 'keywords' => 'Palavras-chave', 'keywords_placeholder' => 'Ex.: concurso, reunião, workshop...', 'period' => 'Período', 'category' => 'Categoria', 'all_categories' => 'Todas as categorias', 'sort_by' => 'Ordenar por', 'sort_recent' => 'Mais recentes', 'sort_oldest' => 'Mais antigas', 'sort_title' => 'Título (A→Z)', 'apply_filters' => 'Aplicar filtros', 'reset' => 'Repor', 'applied_filters' => 'Filtros aplicados:', 'search_filter' => 'Pesquisa', 'month_filter' => 'Mês', 'category_filter' => 'Categoria', 'news_overview' => 'Visão geral das notícias', 'no_match' => 'Nenhuma notícia corresponde aos seus filtros.', 'no_match_help' => 'Tente remover um filtro ou alargar a pesquisa.', 'card_fallback_excerpt' => 'Abra esta notícia para ver os detalhes completos.', 'view_article' => 'Ver artigo →', 'page' => 'Página', 'previous' => 'Anterior', 'next' => 'Seguinte', 'news_count' => 'notícia(s)'],
    'nl' => ['title' => 'Nieuws', 'unavailable' => 'De nieuwsfeed is beschikbaar na de eerste publicaties.', 'latest_news' => 'Laatste nieuws', 'unknown_date' => 'Onbekende datum', 'latest_fallback_excerpt' => 'Bekijk de nieuwste clubpublicatie.', 'default_section' => 'Nieuws', 'published_on' => 'Gepubliceerd op', 'read_news' => 'Nieuws lezen →', 'no_news_yet' => 'Nog geen gepubliceerd nieuws.', 'search_title' => 'Nieuws zoeken', 'search_lead' => 'Vind snel een publicatie op trefwoord, periode of categorie.', 'active_filters' => 'actieve filter(s)', 'keywords' => 'Trefwoorden', 'keywords_placeholder' => 'Bv.: contest, vergadering, workshop...', 'period' => 'Periode', 'category' => 'Categorie', 'all_categories' => 'Alle categorieën', 'sort_by' => 'Sorteren op', 'sort_recent' => 'Meest recent', 'sort_oldest' => 'Oudste', 'sort_title' => 'Titel (A→Z)', 'apply_filters' => 'Filters toepassen', 'reset' => 'Reset', 'applied_filters' => 'Actieve filters:', 'search_filter' => 'Zoeken', 'month_filter' => 'Maand', 'category_filter' => 'Categorie', 'news_overview' => 'Nieuwsoverzicht', 'no_match' => 'Geen nieuws komt overeen met je filters.', 'no_match_help' => 'Probeer een filter te verwijderen of breder te zoeken.', 'card_fallback_excerpt' => 'Open dit nieuwsitem voor alle details.', 'view_article' => 'Artikel bekijken →', 'page' => 'Pagina', 'previous' => 'Vorige', 'next' => 'Volgende', 'news_count' => 'nieuwsbericht(en)'],
];
$newsT = $newsI18n[$locale] ?? $newsI18n['fr'];

if (!table_exists('news_posts')) {
    echo render_layout('<div class="card"><h1>' . e((string) $newsT['title']) . '</h1><p>' . e((string) $newsT['unavailable']) . '</p></div>', (string) $newsT['title']);
    return;
}

$posts = [];
$search = trim((string) ($_GET['q'] ?? ''));
$monthFilter = trim((string) ($_GET['ym'] ?? ''));
$categoryFilter = trim((string) ($_GET['category'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'recent');
$page = max(1, (int) ($_GET['p'] ?? 1));
if (!in_array($sort, ['recent', 'oldest', 'title'], true)) {
    $sort = 'recent';
}
if (!preg_match('/^\d{4}-\d{2}$/', $monthFilter)) {
    $monthFilter = '';
}
if (!preg_match('/^[a-z0-9-]{1,100}$/', $categoryFilter)) {
    $categoryFilter = '';
}

$where = ['p.status = "published"'];
$params = [];
if ($search !== '') {
    $where[] = '(p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)';
    $searchLike = '%' . $search . '%';
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
}
if ($monthFilter !== '') {
    $where[] = 'DATE_FORMAT(COALESCE(p.published_at, p.updated_at), "%Y-%m") = ?';
    $params[] = $monthFilter;
}
if ($categoryFilter !== '') {
    $where[] = 's.slug = ?';
    $params[] = $categoryFilter;
}
$orderBy = match ($sort) {
    'oldest' => 'COALESCE(p.published_at, p.updated_at) ASC',
    'title' => 'p.title ASC',
    default => 'COALESCE(p.published_at, p.updated_at) DESC',
};
$perPage = 18;
$cacheBase = 'news_list_' . md5(json_encode([$where, $params, $sort]));
$totalPosts = cache_remember($cacheBase . '_count', 45, static function () use ($where, $params): int {
    try {
        $countSql = 'SELECT COUNT(*) FROM news_posts p LEFT JOIN news_sections s ON s.id = p.section_id WHERE ' . implode(' AND ', $where);
        $countStmt = db()->prepare($countSql);
        $countStmt->execute($params);
        return (int) $countStmt->fetchColumn();
    } catch (Throwable) {
        return 0;
    }
});
$totalPages = max(1, (int) ceil($totalPosts / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;
$activeFiltersCount = 0;
if ($search !== '') {
    $activeFiltersCount++;
}
if ($monthFilter !== '') {
    $activeFiltersCount++;
}
if ($categoryFilter !== '') {
    $activeFiltersCount++;
}
$resultStart = $totalPosts > 0 ? ($offset + 1) : 0;
$resultEnd = min($offset + $perPage, $totalPosts);

$posts = cache_remember($cacheBase . '_page_' . $page, 45, static function () use ($where, $orderBy, $perPage, $offset, $params): array {
    try {
        $sql = 'SELECT p.slug, p.title, p.excerpt, p.published_at, p.updated_at, s.slug AS section_slug, s.name AS section_name, m.callsign AS author_callsign
            FROM news_posts p
            LEFT JOIN news_sections s ON s.id = p.section_id
            LEFT JOIN members m ON m.id = p.author_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ' . $orderBy . '
            LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    } catch (Throwable) {
        return [];
    }
});

$categories = cache_remember('news_categories_v1', 120, static function (): array {
    try {
        return db()->query('SELECT s.slug, s.name, COUNT(p.id) AS total
            FROM news_sections s
            INNER JOIN news_posts p ON p.section_id = s.id AND p.status = "published"
            GROUP BY s.id, s.slug, s.name
            ORDER BY s.sort_order ASC, s.name ASC')->fetchAll() ?: [];
    } catch (Throwable) {
        return [];
    }
});

$archives = cache_remember('news_archives_v1', 120, static function (): array {
    try {
        return db()->query('SELECT DATE_FORMAT(COALESCE(published_at, updated_at), "%Y-%m") AS ym, COUNT(*) AS total
            FROM news_posts
            WHERE status = "published"
            GROUP BY ym
            ORDER BY ym DESC
            LIMIT 18')->fetchAll() ?: [];
    } catch (Throwable) {
        return [];
    }
});

$latestNews = cache_remember('news_latest_v1', 60, static function (): array {
    try {
        $latestNewsStmt = db()->query('SELECT p.slug, p.title, p.excerpt, p.published_at, p.updated_at, s.name AS section_name
            FROM news_posts p
            LEFT JOIN news_sections s ON s.id = p.section_id
            WHERE p.status = "published"
            ORDER BY COALESCE(p.published_at, p.updated_at) DESC
            LIMIT 2');
        return $latestNewsStmt ? ($latestNewsStmt->fetchAll() ?: []) : [];
    } catch (Throwable) {
        return [];
    }
});

ob_start();
?>
<section class="card">
    <h2 class="news-ui-heading"><?= e((string) $newsT['latest_news']) ?></h2>
    <?php if ($latestNews !== []): ?>
        <div class="news-grid latest-news-grid">
            <?php foreach ($latestNews as $latestPost): ?>
                <?php
                $latestDateRaw = (string) ($latestPost['published_at'] ?? $latestPost['updated_at'] ?? '');
                $latestDate = $latestDateRaw !== '' ? date('d/m/Y', strtotime($latestDateRaw)) : (string) $newsT['unknown_date'];
                $latestExcerpt = trim((string) ($latestPost['excerpt'] ?? ''));
                if ($latestExcerpt === '') {
                    $latestExcerpt = (string) $newsT['latest_fallback_excerpt'];
                }
                ?>
                <article class="news-card feature-card">
                    <a class="news-card-link" href="<?= e(route_url('news_view', ['slug' => (string) ($latestPost['slug'] ?? '')])) ?>">
                        <span class="badge muted"><?= e((string) ($latestPost['section_name'] ?? (string) $newsT['default_section'])) ?></span>
                        <h3><?= e((string) ($latestPost['title'] ?? (string) $newsT['default_section'])) ?></h3>
                        <p class="help"><?= e((string) $newsT['published_on']) ?> <?= e($latestDate) ?></p>
                        <p><?= e($latestExcerpt) ?></p>
                        <span class="news-card-cta"><?= e((string) $newsT['read_news']) ?></span>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="news-empty-state">
            <p><?= e((string) $newsT['no_news_yet']) ?></p>
        </div>
    <?php endif; ?>
</section>

<section class="card news-filters mt-4">
    <div class="news-search-header">
        <h1 class="news-ui-heading"><?= e((string) $newsT['search_title']) ?></h1>
        <p class="help"><?= e((string) $newsT['search_lead']) ?></p>
    </div>
    <?php if ($activeFiltersCount > 0): ?>
        <div class="news-meta-row">
            <span class="badge muted"><?= $activeFiltersCount ?> <?= e((string) $newsT['active_filters']) ?></span>
        </div>
    <?php endif; ?>
    <form method="get" class="inline-form news-search-form">
        <input type="hidden" name="route" value="news">
        <label class="news-search-field news-search-field--query">
            <span><?= e((string) $newsT['keywords']) ?></span>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $newsT['keywords_placeholder']) ?>">
        </label>
        <label class="news-search-field">
            <span><?= e((string) $newsT['period']) ?></span>
            <input type="month" name="ym" value="<?= e($monthFilter) ?>">
        </label>
        <label class="news-search-field">
            <span><?= e((string) $newsT['category']) ?></span>
            <select name="category">
                <option value=""><?= e((string) $newsT['all_categories']) ?></option>
                <?php foreach ($categories as $category): ?>
                    <?php $slug = (string) ($category['slug'] ?? ''); ?>
                    <option value="<?= e($slug) ?>" <?= $categoryFilter === $slug ? 'selected' : '' ?>><?= e((string) ($category['name'] ?? (string) $newsT['category'])) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="news-search-field">
            <span><?= e((string) $newsT['sort_by']) ?></span>
            <select name="sort">
                <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>><?= e((string) $newsT['sort_recent']) ?></option>
                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>><?= e((string) $newsT['sort_oldest']) ?></option>
                <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>><?= e((string) $newsT['sort_title']) ?></option>
            </select>
        </label>
        <div class="news-search-actions">
            <button class="button" type="submit"><?= e((string) $newsT['apply_filters']) ?></button>
            <?php if ($search !== '' || $monthFilter !== '' || $categoryFilter !== ''): ?>
                <a class="button secondary" href="<?= e(route_url('news')) ?>"><?= e((string) $newsT['reset']) ?></a>
            <?php endif; ?>
        </div>
    </form>
    <?php if ($activeFiltersCount > 0): ?>
        <div class="news-active-filters">
            <strong><?= e((string) $newsT['applied_filters']) ?></strong>
            <?php if ($search !== ''): ?>
                <span class="pill"><?= e((string) $newsT['search_filter']) ?> : “<?= e($search) ?>”</span>
            <?php endif; ?>
            <?php if ($monthFilter !== ''): ?>
                <span class="pill"><?= e((string) $newsT['month_filter']) ?> : <?= e($monthFilter) ?></span>
            <?php endif; ?>
            <?php if ($categoryFilter !== ''): ?>
                <span class="pill"><?= e((string) $newsT['category_filter']) ?> : <?= e($categoryFilter) ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($categories !== []): ?>
        <div class="news-archives" id="news-categories">
            <?php foreach ($categories as $category): ?>
                <?php $slug = (string) ($category['slug'] ?? ''); ?>
                <a class="pill" href="<?= e(route_url('news', ['category' => $slug])) ?>"<?= $categoryFilter === $slug ? ' aria-current="page"' : '' ?>>
                    <?= e((string) ($category['name'] ?? (string) $newsT['category'])) ?> · <?= (int) ($category['total'] ?? 0) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($archives !== []): ?>
        <div class="news-archives" id="news-archives">
            <?php foreach ($archives as $archive):
                $ym = (string) ($archive['ym'] ?? '');
                if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
                    continue;
                }
                [$year, $month] = explode('-', $ym);
                $label = $month . '/' . $year;
                ?>
                <a class="pill" href="<?= e(route_url('news', ['ym' => $ym])) ?>"<?= $monthFilter === $ym ? ' aria-current="page"' : '' ?>>
                    <?= e($label) ?> · <?= (int) ($archive['total'] ?? 0) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="card mt-4" id="news-list">
        <h2 class="news-ui-heading"><?= e((string) $newsT['news_overview']) ?></h2>
        <?php if ($posts === []): ?>
            <div class="news-empty-state">
                <p><?= e((string) $newsT['no_match']) ?></p>
                <p class="help"><?= e((string) $newsT['no_match_help']) ?></p>
            </div>
        <?php else: ?>
            <div class="news-grid">
                <?php foreach ($posts as $post):
                $publishedAtRaw = (string) ($post['published_at'] ?? $post['updated_at'] ?? '');
                $publishedAt = $publishedAtRaw !== '' ? date('d/m/Y', strtotime($publishedAtRaw)) : (string) $newsT['unknown_date'];
                $excerpt = trim((string) ($post['excerpt'] ?? ''));
                if ($excerpt === '') {
                    $excerpt = (string) $newsT['card_fallback_excerpt'];
                }
                ?>
                <article class="news-card feature-card">
                    <a class="news-card-link" href="<?= e(route_url('news_view', ['slug' => (string) $post['slug']])) ?>">
                        <span class="badge muted"><?= e((string) ($post['section_name'] ?? (string) $newsT['default_section'])) ?></span>
                        <h3><?= e((string) $post['title']) ?></h3>
                        <p class="help">
                            <?= e((string) $newsT['published_on']) ?> <?= e($publishedAt) ?>
                            <?php if (trim((string) ($post['author_callsign'] ?? '')) !== ''): ?>
                                · <?= e((string) $post['author_callsign']) ?>
                            <?php endif; ?>
                        </p>
                        <p><?= e($excerpt) ?></p>
                        <span class="news-card-cta"><?= e((string) $newsT['view_article']) ?></span>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
</section>

<?php if ($totalPosts > $perPage): ?>
    <section class="card mt-4">
        <div class="row-between">
            <p class="help"><?= e((string) $newsT['page']) ?> <?= (int) $page ?> / <?= (int) $totalPages ?> — <?= (int) $totalPosts ?> <?= e((string) $newsT['news_count']) ?></p>
            <p class="actions">
                <?php if ($page > 1): ?>
                    <a class="button secondary" href="<?= e(route_url('news', ['q' => $search, 'ym' => $monthFilter, 'category' => $categoryFilter, 'sort' => $sort, 'p' => $page - 1])) ?>">← <?= e((string) $newsT['previous']) ?></a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a class="button secondary" href="<?= e(route_url('news', ['q' => $search, 'ym' => $monthFilter, 'category' => $categoryFilter, 'sort' => $sort, 'p' => $page + 1])) ?>"><?= e((string) $newsT['next']) ?> →</a>
                <?php endif; ?>
            </p>
        </div>
    </section>
<?php endif; ?>
<?php
echo render_layout((string) ob_get_clean(), (string) $newsT['title']);
