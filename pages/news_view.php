<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['not_found' => 'Actualité introuvable', 'not_found_msg' => "Cette actualité n'existe pas ou n'est pas publiée.", 'back' => '← Retour aux actualités', 'published_on' => 'Publié le', 'date_unknown' => 'Date non définie', 'content_soon' => 'Le contenu détaillé sera ajouté prochainement.'],
    'en' => ['not_found' => 'News not found', 'not_found_msg' => 'This news item does not exist or is not published.', 'back' => '← Back to news', 'published_on' => 'Published on', 'date_unknown' => 'Date not set', 'content_soon' => 'Detailed content will be added soon.'],
    'de' => ['not_found' => 'Nachricht nicht gefunden', 'not_found_msg' => 'Diese Nachricht existiert nicht oder ist nicht veröffentlicht.', 'back' => '← Zurück zu den Nachrichten', 'published_on' => 'Veröffentlicht am', 'date_unknown' => 'Datum nicht festgelegt', 'content_soon' => 'Detaillierter Inhalt wird in Kürze hinzugefügt.'],
    'nl' => ['not_found' => 'Nieuws niet gevonden', 'not_found_msg' => 'Dit nieuwsbericht bestaat niet of is niet gepubliceerd.', 'back' => '← Terug naar nieuws', 'published_on' => 'Gepubliceerd op', 'date_unknown' => 'Datum niet ingesteld', 'content_soon' => 'Gedetailleerde inhoud wordt binnenkort toegevoegd.'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '' || !table_exists('news_posts')) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e((string) $t['not_found']) . '</h1><p>' . e((string) $t['not_found_msg']) . '</p></div>', (string) $t['not_found']);
    return;
}

$stmt = db()->prepare('SELECT title, excerpt, content, published_at, updated_at FROM news_posts WHERE slug = ? AND status = "published" LIMIT 1');
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!is_array($post)) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e((string) $t['not_found']) . '</h1><p>' . e((string) $t['not_found_msg']) . '</p></div>', (string) $t['not_found']);
    return;
}

$publishedAtRaw = (string) ($post['published_at'] ?? $post['updated_at'] ?? '');
$publishedAt = $publishedAtRaw !== '' ? date('d/m/Y H:i', strtotime($publishedAtRaw)) : (string) $t['date_unknown'];
$excerpt = trim((string) ($post['excerpt'] ?? ''));
$content = trim((string) ($post['content'] ?? ''));

ob_start();
?>
<article class="card">
    <p><a href="<?= e(route_url('news')) ?>"><?= e((string) $t['back']) ?></a></p>
    <h1><?= e((string) $post['title']) ?></h1>
    <p class="help"><?= e((string) $t['published_on']) ?> <?= e($publishedAt) ?></p>

    <?php if ($excerpt !== ''): ?>
        <p><strong><?= e($excerpt) ?></strong></p>
    <?php endif; ?>

    <?php if ($content !== ''): ?>
        <section class="inner-card">
            <?= $content ?>
        </section>
    <?php else: ?>
        <p><?= e((string) $t['content_soon']) ?></p>
    <?php endif; ?>
</article>
<?php
echo render_layout((string) ob_get_clean(), (string) $post['title']);
