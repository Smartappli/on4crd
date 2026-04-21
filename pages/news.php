<?php
declare(strict_types=1);

if (!table_exists('news_posts')) {
    echo render_layout('<div class="card"><h1>Actualités</h1><p>Le flux actualités sera disponible après publication des premiers contenus.</p></div>', 'Actualités');
    return;
}

$posts = [];
try {
    $stmt = db()->query('SELECT p.slug, p.title, p.excerpt, p.published_at, p.updated_at, s.name AS section_name, m.callsign AS author_callsign
        FROM news_posts p
        LEFT JOIN news_sections s ON s.id = p.section_id
        LEFT JOIN members m ON m.id = p.author_id
        WHERE p.status = "published"
        ORDER BY COALESCE(p.published_at, p.updated_at) DESC
        LIMIT 24');
    $posts = $stmt->fetchAll();
} catch (Throwable) {
    $posts = [];
}

$featuredPost = $posts !== [] ? $posts[0] : null;
$remainingPosts = $posts !== [] ? array_slice($posts, 1) : [];
$latestRaw = (string) (($featuredPost['published_at'] ?? $featuredPost['updated_at'] ?? ''));
$latestDate = $latestRaw !== '' ? date('d/m/Y', strtotime($latestRaw)) : '—';
$sections = [];
foreach ($posts as $post) {
    $section = trim((string) ($post['section_name'] ?? ''));
    if ($section !== '') {
        $sections[$section] = true;
    }
}

ob_start();
?>
<section class="card news-page-header">
    <h1>Actualités</h1>
    <p class="help">Suivez la vie du radio-club : annonces, compte-rendus, résultats et nouvelles techniques.</p>
    <div class="stats-grid">
        <article class="stat-card">
            <span class="help">Articles publiés</span>
            <strong><?= (int) count($posts) ?></strong>
        </article>
        <article class="stat-card">
            <span class="help">Dernière publication</span>
            <strong><?= e($latestDate) ?></strong>
        </article>
        <article class="stat-card">
            <span class="help">Sections actives</span>
            <strong><?= (int) count($sections) ?></strong>
        </article>
    </div>
</section>

<?php if ($posts === []): ?>
    <section class="card">
        <p>Aucune actualité publiée pour le moment.</p>
    </section>
<?php else: ?>
    <?php if (is_array($featuredPost)): ?>
        <?php
        $featuredDateRaw = (string) ($featuredPost['published_at'] ?? $featuredPost['updated_at'] ?? '');
        $featuredDate = $featuredDateRaw !== '' ? date('d/m/Y', strtotime($featuredDateRaw)) : 'Date non définie';
        $featuredExcerpt = trim((string) ($featuredPost['excerpt'] ?? ''));
        if ($featuredExcerpt === '') {
            $featuredExcerpt = 'Découvrez les informations complètes dans l’article.';
        }
        ?>
        <section class="card news-featured">
            <span class="badge">À la une</span>
            <h2><?= e((string) $featuredPost['title']) ?></h2>
            <p class="help">
                <?= e($featuredDate) ?>
                <?php if (trim((string) ($featuredPost['section_name'] ?? '')) !== ''): ?>
                    · <?= e((string) $featuredPost['section_name']) ?>
                <?php endif; ?>
                <?php if (trim((string) ($featuredPost['author_callsign'] ?? '')) !== ''): ?>
                    · <?= e((string) $featuredPost['author_callsign']) ?>
                <?php endif; ?>
            </p>
            <p><?= e($featuredExcerpt) ?></p>
            <p><a class="button" href="<?= e(route_url('news_view', ['slug' => (string) $featuredPost['slug']])) ?>">Lire l’actualité principale</a></p>
        </section>
    <?php endif; ?>

    <?php if ($remainingPosts !== []): ?>
        <section class="card">
            <h2>Dernières publications</h2>
            <div class="news-grid">
                <?php foreach ($remainingPosts as $post):
                $publishedAtRaw = (string) ($post['published_at'] ?? $post['updated_at'] ?? '');
                $publishedAt = $publishedAtRaw !== '' ? date('d/m/Y', strtotime($publishedAtRaw)) : 'Date non définie';
                $excerpt = trim((string) ($post['excerpt'] ?? ''));
                if ($excerpt === '') {
                    $excerpt = 'Lire cette actualité pour découvrir les informations complètes.';
                }
                ?>
                <article class="news-card feature-card">
                    <span class="badge muted"><?= e((string) ($post['section_name'] ?? 'Actualité')) ?></span>
                    <h2><?= e((string) $post['title']) ?></h2>
                    <p class="help">
                        Publié le <?= e($publishedAt) ?>
                        <?php if (trim((string) ($post['author_callsign'] ?? '')) !== ''): ?>
                            · <?= e((string) $post['author_callsign']) ?>
                        <?php endif; ?>
                    </p>
                    <p><?= e($excerpt) ?></p>
                    <p><a class="button secondary" href="<?= e(route_url('news_view', ['slug' => (string) $post['slug']])) ?>">Lire l’actualité</a></p>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>
<?php
echo render_layout((string) ob_get_clean(), 'Actualités');
