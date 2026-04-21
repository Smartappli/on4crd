<?php
declare(strict_types=1);

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '' || !table_exists('news_posts')) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>Actualité introuvable</h1><p>Cette actualité n\'existe pas ou n\'est pas publiée.</p></div>', 'Actualité');
    return;
}

$stmt = db()->prepare('SELECT title, excerpt, content, published_at, updated_at FROM news_posts WHERE slug = ? AND status = "published" LIMIT 1');
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!is_array($post)) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>Actualité introuvable</h1><p>Cette actualité n\'existe pas ou n\'est pas publiée.</p></div>', 'Actualité');
    return;
}

$publishedAtRaw = (string) ($post['published_at'] ?? $post['updated_at'] ?? '');
$publishedAt = $publishedAtRaw !== '' ? date('d/m/Y H:i', strtotime($publishedAtRaw)) : 'Date non définie';
$excerpt = trim((string) ($post['excerpt'] ?? ''));
$content = trim((string) ($post['content'] ?? ''));

ob_start();
?>
<article class="card">
    <p><a href="<?= e(route_url('news')) ?>">← Retour aux actualités</a></p>
    <h1><?= e((string) $post['title']) ?></h1>
    <p class="help">Publié le <?= e($publishedAt) ?></p>

    <?php if ($excerpt !== ''): ?>
        <p><strong><?= e($excerpt) ?></strong></p>
    <?php endif; ?>

    <?php if ($content !== ''): ?>
        <section class="inner-card">
            <?= $content ?>
        </section>
    <?php else: ?>
        <p>Le contenu détaillé sera ajouté prochainement.</p>
    <?php endif; ?>
</article>
<?php
echo render_layout((string) ob_get_clean(), (string) $post['title']);
