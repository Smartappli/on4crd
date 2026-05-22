<?php
declare(strict_types=1);

require_login();
$locale = current_locale();
$i18n = i18n_domain_messages('members_library');
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $value = trim(i18n_localized_value($i18n, $locale, 'fr'));
    if ($value === '') {
        $value = trim((string) ($i18n['fr'][$key] ?? ''));
    }
    $t[$key] = $value;
}
set_page_meta(['title' => (string) $t['title'], 'description' => (string) $t['meta_desc'], 'robots' => 'noindex,follow']);

if (!ensure_member_library_table()) {
    echo render_layout('<div class="card"><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['title']);
    return;
}

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 120) { $search = mb_substr($search, 0, 120); }
$category = trim((string) ($_GET['category'] ?? ''));

$categories = db()->query('SELECT category, COUNT(*) AS total FROM member_library_documents GROUP BY category ORDER BY category')->fetchAll() ?: [];
$where = [];
$params = [];
if ($category !== '') { $where[] = 'category = ?'; $params[] = $category; }
if ($search !== '') {
    $where[] = '(title LIKE ? OR description LIKE ? OR extracted_text LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}
$sql = 'SELECT * FROM member_library_documents';
if ($where !== []) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY uploaded_at DESC LIMIT 150';
$stmt = db()->prepare($sql); $stmt->execute($params);
$documents = $stmt->fetchAll() ?: [];

ob_start();
?>
<div class="card">
    <h1><?= e((string) $t['title']) ?></h1>
    <p><?= e((string) $t['intro']) ?></p>
    <form method="get" class="inline-form" style="flex-wrap:wrap; margin-bottom:.8rem;">
        <input type="hidden" name="route" value="members_library">
        <select name="category">
            <option value=""><?= e((string) $t['all_categories']) ?></option>
            <?php foreach ($categories as $cat): $catName = trim((string) ($cat['category'] ?? 'general')); if ($catName === '') { $catName = 'general'; } ?>
                <option value="<?= e($catName) ?>" <?= $catName === $category ? 'selected' : '' ?>><?= e($catName) ?> (<?= (int) ($cat['total'] ?? 0) ?>)</option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
        <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
    </form>

    <?php if ($documents === []): ?>
        <p class="help"><?= e((string) $t['empty']) ?><?= ($search !== '' || $category !== '') ? e((string) $t['for_filters']) : '' ?>.</p>
    <?php endif; ?>

    <?php foreach ($documents as $document): ?>
        <?php $safePath = safe_storage_public_path_or_null((string) ($document['file_path'] ?? ''), ['storage/uploads/library/']); ?>
        <?php if ($safePath === null) { continue; } ?>
        <article class="card" style="margin-top:12px;">
            <?php $docCategory = trim((string) ($document['category'] ?? 'general')); if ($docCategory === '') { $docCategory = 'general'; } ?>
            <?php $docTitle = trim((string) ($document['title'] ?? '')); if ($docTitle === '') { $docTitle = 'Document'; } ?>
            <?php $docDescription = trim((string) ($document['description'] ?? '')); if ($docDescription === '') { $docDescription = (string) $t['intro']; } ?>
            <p><span class="badge muted"><?= e($docCategory) ?></span></p>
            <h3><?= e($docTitle) ?></h3>
            <p><?= e($docDescription) ?></p>
            <iframe src="<?= e(base_url($safePath)) ?>" style="width:100%;height:480px;border:1px solid #ccc;" loading="lazy"></iframe>
            <p><a class="button secondary" href="<?= e(base_url($safePath)) ?>" target="_blank" rel="noopener"><?= e((string) $t['open']) ?></a></p>
        </article>
    <?php endforeach; ?>
</div>
<?php echo render_layout((string) ob_get_clean(), (string) $t['title']);
