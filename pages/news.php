<?php
declare(strict_types=1);

if (!table_exists('news_posts')) {
    echo render_layout('<div class="card"><h1>Actualités</h1><p>Le flux actualités sera disponible après publication des premiers contenus.</p></div>', 'Actualités');
    return;
}

$posts = [];
try {
    $stmt = db()->query('SELECT slug, title, excerpt, published_at, updated_at FROM news_posts WHERE status = "published" ORDER BY COALESCE(published_at, updated_at) DESC LIMIT 24');
    $posts = $stmt->fetchAll();
} catch (Throwable) {
    $posts = [];
}

ob_start();
?>
<section class="card">
    <h1>Actualités</h1>
    <p class="help">Retrouvez les dernières informations du radio-club sous forme de cards.</p>

    <?php if ($posts === []): ?>
        <p>Aucune actualité publiée pour le moment.</p>
    <?php else: ?>
        <div class="news-grid">
            <?php foreach ($posts as $post):
                $publishedAtRaw = (string) ($post['published_at'] ?? $post['updated_at'] ?? '');
                $publishedAt = $publishedAtRaw !== '' ? date('d/m/Y', strtotime($publishedAtRaw)) : 'Date non définie';
                $excerpt = trim((string) ($post['excerpt'] ?? ''));
                if ($excerpt === '') {
                    $excerpt = 'Lire cette actualité pour découvrir les informations complètes.';
                }
                ?>
                <article class="news-card feature-card">
                    <span class="badge">Actualité</span>
                    <h2><?= e((string) $post['title']) ?></h2>
                    <p class="help">Publié le <?= e($publishedAt) ?></p>
                    <p><?= e($excerpt) ?></p>
                    <p><a class="button secondary" href="<?= e(route_url('news_view', ['slug' => (string) $post['slug']])) ?>">Lire l’actualité</a></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), 'Actualités');
