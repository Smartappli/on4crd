<?php
declare(strict_types=1);

if (!function_exists('ensure_member_library_table')) {
function ensure_member_library_table(): bool
{
    static $ready = null;
    if (is_bool($ready)) {
        return $ready;
    }
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS member_library_documents (id INT AUTO_INCREMENT PRIMARY KEY, member_id INT NOT NULL, category VARCHAR(120) NOT NULL DEFAULT "general", tags VARCHAR(255) NOT NULL DEFAULT "", title VARCHAR(255) NOT NULL, description TEXT NULL, file_path VARCHAR(255) NOT NULL, extracted_text LONGTEXT NULL, uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_uploaded (uploaded_at), INDEX idx_member_uploaded (member_id, uploaded_at), INDEX idx_category (category), INDEX idx_tags (tags))');
        $ready = table_exists('member_library_documents');
        if ($ready) {
            $hasCategory = false;
            try {
                $col = db()->query("SHOW COLUMNS FROM member_library_documents LIKE 'category'");
                $hasCategory = (bool) ($col && $col->fetch());
            } catch (Throwable) {
                $hasCategory = false;
            }
            if (!$hasCategory) {
                db()->exec('ALTER TABLE member_library_documents ADD COLUMN category VARCHAR(120) NOT NULL DEFAULT "general" AFTER member_id');
            }
            $hasTags = false;
            try {
                $tagsCol = db()->query("SHOW COLUMNS FROM member_library_documents LIKE 'tags'");
                $hasTags = (bool) ($tagsCol && $tagsCol->fetch());
            } catch (Throwable) {
                $hasTags = false;
            }
            if (!$hasTags) {
                db()->exec('ALTER TABLE member_library_documents ADD COLUMN tags VARCHAR(255) NOT NULL DEFAULT "" AFTER category');
            }
            try {
                db()->exec('ALTER TABLE member_library_documents ADD INDEX idx_category (category)');
            } catch (Throwable) {
                // Index may already exist.
            }
            try {
                db()->exec('ALTER TABLE member_library_documents ADD INDEX idx_tags (tags)');
            } catch (Throwable) {
                // Index may already exist.
            }
        }
    } catch (Throwable) {
        $ready = false;
    }

    return $ready;
}
}


if (!function_exists('library_controlled_vocabulary_list')) {
function library_controlled_vocabulary_list(): array
{
    return [
        'formation',
        'securite',
        'legal',
        'reglement',
        'technique',
        'antenne',
        'propagation',
        'traffic',
        'numerique',
        'materiel',
        'maintenance',
        'procedure',
        'club',
    ];
}
}

if (!function_exists('member_library_category_slug')) {
function member_library_category_slug(string $value): string
{
    return content_proposal_category_code($value, 120, 'general');
}
}

if (!function_exists('member_library_default_categories')) {
function member_library_default_categories(): array
{
    return [
        ['code' => 'general', 'label' => 'General', 'sort_order' => 1],
    ];
}
}

if (!function_exists('member_library_module_definitions')) {
function member_library_module_definitions(): array
{
    return [
        'presentations' => [
            'category' => 'presentations',
            'title' => ['fr' => 'Présentations', 'en' => 'Presentations'],
            'intro' => [
                'fr' => 'Supports de réunions, exposés et documents présentés aux membres.',
                'en' => 'Meeting decks, talks and documents presented to members.',
            ],
            'meta_desc' => [
                'fr' => 'Présentations réservées aux membres ON4CRD.',
                'en' => 'Presentations reserved for ON4CRD members.',
            ],
        ],
        'videos' => [
            'category' => 'videos',
            'title' => ['fr' => 'Videos', 'en' => 'Videos'],
            'intro' => [
                'fr' => 'Vidéos et ressources audiovisuelles partagées avec les membres du club.',
                'en' => 'Videos and audiovisual resources shared with club members.',
            ],
            'meta_desc' => [
                'fr' => 'Vidéos réservées aux membres ON4CRD.',
                'en' => 'Videos reserved for ON4CRD members.',
            ],
        ],
        'pv' => [
            'category' => 'pv',
            'title' => ['fr' => 'PV', 'en' => 'Minutes'],
            'intro' => [
                'fr' => 'Procès-verbaux et comptes rendus disponibles pour les membres.',
                'en' => 'Minutes and reports available to members.',
            ],
            'meta_desc' => [
                'fr' => 'PV réservés aux membres ON4CRD.',
                'en' => 'Minutes reserved for ON4CRD members.',
            ],
        ],
        'telechargements' => [
            'category' => 'telechargements',
            'title' => ['fr' => 'Téléchargements', 'en' => 'Downloads'],
            'intro' => [
                'fr' => 'Fichiers et ressources à télécharger pour les membres.',
                'en' => 'Files and resources for members to download.',
            ],
            'meta_desc' => [
                'fr' => 'Téléchargements réservés aux membres ON4CRD.',
                'en' => 'Downloads reserved for ON4CRD members.',
            ],
        ],
    ];
}
}

if (!function_exists('member_library_module_definition')) {
function member_library_module_definition(string $route): ?array
{
    $route = preg_replace('/[^a-z0-9_]/', '', strtolower($route)) ?: '';
    $definitions = member_library_module_definitions();

    return isset($definitions[$route]) && is_array($definitions[$route]) ? $definitions[$route] : null;
}
}

if (!function_exists('render_member_library_module_page')) {
function render_member_library_module_page(string $route): void
{
    $definition = member_library_module_definition($route);
    if ($definition === null) {
        http_response_code(404);
        echo render_layout('<div class="card"><h1>404</h1><p>Module indisponible.</p></div>', '404');
        return;
    }

    require_login();

    $locale = current_locale();
    $libraryT = i18n_domain_locale('members_library', $locale);
    $moduleT = i18n_domain_locale($route, $locale);
    $text = static fn(string $key, string $fallback): string => (string) ($libraryT[$key] ?? $fallback);
    $title = trim((string) ($moduleT['title'] ?? ''));
    if ($title === '') {
        $title = i18n_localized_value((array) ($definition['title'] ?? []), $locale);
    }
    if ($title === '') {
        $title = ucfirst($route);
    }
    $intro = trim((string) ($moduleT['intro'] ?? ''));
    if ($intro === '') {
        $intro = i18n_localized_value((array) ($definition['intro'] ?? []), $locale);
    }
    $metaDesc = trim((string) ($moduleT['meta_desc'] ?? ''));
    if ($metaDesc === '') {
        $metaDesc = i18n_localized_value((array) ($definition['meta_desc'] ?? []), $locale);
    }
    if ($metaDesc === '') {
        $metaDesc = $intro;
    }

    set_page_meta([
        'title' => $title,
        'description' => $metaDesc,
        'robots' => 'noindex,follow',
        'schema_type' => 'CollectionPage',
    ]);

    if (!ensure_member_library_table()) {
        echo render_layout('<div class="card"><p>' . e($text('storage_unavailable', 'La bibliothèque est temporairement indisponible.')) . '</p></div>', $title);
        return;
    }
    member_library_ensure_categories_table();

    $category = member_library_category_slug((string) ($definition['category'] ?? $route));
    $search = trim((string) ($_GET['q'] ?? ''));
    if (mb_strlen($search) > 120) {
        $search = mb_substr($search, 0, 120);
    }

    $where = ['category = ?'];
    $params = [$category];
    if ($search !== '') {
        $where[] = '(title LIKE ? OR description LIKE ? OR extracted_text LIKE ? OR tags LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like);
    }
    $whereSql = ' WHERE ' . implode(' AND ', $where);

    $countStmt = db()->prepare('SELECT COUNT(*) FROM member_library_documents' . $whereSql);
    $countStmt->execute($params);
    $totalDocuments = (int) $countStmt->fetchColumn();

    $stmt = db()->prepare('SELECT * FROM member_library_documents' . $whereSql . ' ORDER BY uploaded_at DESC, id DESC LIMIT 60');
    $stmt->execute($params);
    $documents = $stmt->fetchAll() ?: [];

    ob_start();
    ?>
    <div class="stack members-library-article-design member-document-module">
        <section class="page-hero">
            <div>
                <p class="eyebrow"><?= e($text('title', 'Bibliothèque membres')) ?></p>
                <h1 class="members-library-heading"><?= e($title) ?></h1>
                <?php if ($intro !== ''): ?><p class="help"><?= e($intro) ?></p><?php endif; ?>
            </div>
            <div class="members-library-hero-side">
                <div class="articles-hero-stats members-library-stats">
                    <article>
                        <span><?= e($text('documents', 'Documents')) ?></span>
                        <strong><?= (int) $totalDocuments ?></strong>
                    </article>
                    <article>
                        <span><?= e($text('category', 'Catégorie')) ?></span>
                        <strong><?= e($title) ?></strong>
                    </article>
                </div>
                <div class="members-library-hero-action">
                    <a class="button secondary" href="<?= e(route_url_clean('members_library', ['category' => $category])) ?>"><?= e($text('title', 'Bibliothèque membres')) ?></a>
                    <?php if (has_permission('admin.access')): ?>
                        <a class="button" href="<?= e(route_url_clean('admin_library', ['category' => $category])) ?>">Administration</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="card members-library-search-panel">
            <form method="get" class="inline-form members-library-search-form">
                <input type="hidden" name="route" value="<?= e($route) ?>">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e($text('search_ph', 'Rechercher par titre, résumé ou contenu indexé')) ?>">
                <button class="button" type="submit"><?= e($text('search', 'Rechercher')) ?></button>
                <?php if ($search !== ''): ?>
                    <a class="button secondary" href="<?= e(route_url($route)) ?>"><?= e($text('reset', 'Réinitialiser')) ?></a>
                <?php endif; ?>
            </form>
        </section>

        <?php if ($documents === []): ?>
            <div class="card">
                <p><?= e($text('empty', 'Aucun document trouvé.')) ?><?php if ($search !== ''): ?><?= e($text('for_filters', ' pour ces filtres')) ?>.<?php endif; ?></p>
            </div>
        <?php else: ?>
            <div class="news-grid members-library-document-grid">
                <?php foreach ($documents as $document): ?>
                    <?php $safePath = safe_storage_public_path_or_null((string) ($document['file_path'] ?? ''), ['storage/uploads/library/']); ?>
                    <?php if ($safePath === null) { continue; } ?>
                    <?php $extension = strtolower(pathinfo($safePath, PATHINFO_EXTENSION)); ?>
                    <?php $docTitle = trim((string) ($document['title'] ?? '')); if ($docTitle === '') { $docTitle = $text('document', 'Document'); } ?>
                    <?php $docDescription = trim((string) ($document['description'] ?? '')); ?>
                    <?php $docTags = trim((string) ($document['tags'] ?? '')); ?>
                    <?php $docExtract = trim((string) ($document['extracted_text'] ?? '')); ?>
                    <article class="news-card feature-card members-library-document-card">
                        <span class="badge muted"><?= e(strtoupper($extension)) ?></span>
                        <h2><?= e($docTitle) ?></h2>
                        <?php if ($docDescription !== ''): ?><p><?= e($docDescription) ?></p><?php endif; ?>
                        <?php if ($docTags !== ''): ?><p class="help"><?= e($text('tags', 'Étiquettes')) ?>: <?= e($docTags) ?></p><?php endif; ?>
                        <?php if ($docExtract !== ''): ?><p class="help"><?= e(mb_safe_strimwidth($docExtract, 0, 220, '...')) ?></p><?php endif; ?>
                        <?php if ($extension === 'pdf'): ?>
                            <details class="admin-library-preview-toggle">
                                <summary><?= e($text('preview', 'Aperçu')) ?></summary>
                                <iframe src="<?= e(base_url($safePath)) ?>" class="admin-library-pdf-preview" loading="lazy"></iframe>
                            </details>
                        <?php endif; ?>
                        <p class="actions">
                            <a class="button secondary" href="<?= e(base_url($safePath)) ?>" target="_blank" rel="noopener"><?= e($text('open', 'Ouvrir le document')) ?></a>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    echo render_layout((string) ob_get_clean(), $title);
}
}

if (!function_exists('member_library_ensure_categories_table')) {
function member_library_ensure_categories_table(): bool
{
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS member_library_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(120) NOT NULL UNIQUE,
            label VARCHAR(160) NOT NULL,
            sort_order INT NOT NULL DEFAULT 100,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
        $categoryInsert = db()->prepare('INSERT IGNORE INTO member_library_categories (code, label, sort_order) VALUES (?, ?, ?)');
        foreach (member_library_default_categories() as $category) {
            $categoryInsert->execute([
                (string) $category['code'],
                (string) $category['label'],
                (int) $category['sort_order'],
            ]);
        }
        try {
            db()->exec("UPDATE member_library_documents SET category = 'videos' WHERE category = 'medias'");
        } catch (Throwable) {
        }
        try {
            $legacyCategoryStmt = db()->query("SELECT COUNT(*) FROM member_library_categories WHERE code = 'medias'");
            if ($legacyCategoryStmt !== false && (int) $legacyCategoryStmt->fetchColumn() > 0) {
                $videosCategoryStmt = db()->query("SELECT COUNT(*) FROM member_library_categories WHERE code = 'videos'");
                if ($videosCategoryStmt !== false && (int) $videosCategoryStmt->fetchColumn() > 0) {
                    db()->exec("DELETE FROM member_library_categories WHERE code = 'medias'");
                } else {
                    db()->exec("UPDATE member_library_categories SET code = 'videos', label = 'Videos', sort_order = 20 WHERE code = 'medias'");
                }
            }
        } catch (Throwable) {
        }

        return table_exists('member_library_categories');
    } catch (Throwable) {
        return false;
    }
}
}

if (!function_exists('library_ingestion_templates_map')) {
function library_ingestion_templates_map(): array
{
    return [
        'training' => ['category' => 'formation', 'tags' => ['formation', 'procedure', 'club']],
        'safety' => ['category' => 'general', 'tags' => ['securite', 'procedure', 'reglement']],
        'technical' => ['category' => 'general', 'tags' => ['technique', 'antenne', 'propagation', 'materiel']],
        'legal' => ['category' => 'general', 'tags' => ['legal', 'reglement', 'club']],
    ];
}
}

if (!function_exists('library_filter_controlled_tags')) {
function library_filter_controlled_tags(array $tags): array
{
    $allowed = array_fill_keys(library_controlled_vocabulary_list(), true);
    $out = [];
    foreach ($tags as $tag) {
        $raw = trim((string) $tag);
        if ($raw === '') {
            continue;
        }
        $norm = mb_strtolower($raw, 'UTF-8');
        $norm = preg_replace('/\s+/u', ' ', $norm) ?? $norm;
        $norm = trim($norm);
        if ($norm === '' || !isset($allowed[$norm])) {
            continue;
        }
        $out[] = $raw;
    }
    return $out;
}
}

if (!function_exists('editorial_blocked_reasons_from_article')) {
/**
 * @param array<string,mixed> $article
 * @return list<string>
 */
function editorial_blocked_reasons_from_article(array $article): array
{
    $reasons = [];
    $title = trim((string) ($article['title'] ?? ''));
    $content = trim(strip_tags((string) ($article['content'] ?? '')));
    $status = (string) ($article['status'] ?? 'draft');
    $scheduledAt = trim((string) ($article['scheduled_at'] ?? ''));

    if ($title === '') {
        $reasons[] = 'missing_title';
    }
    if ($content === '') {
        $reasons[] = 'missing_content';
    }
    if ($status === 'scheduled') {
        if ($scheduledAt === '') {
            $reasons[] = 'missing_schedule_date';
        } else {
            $ts = strtotime($scheduledAt);
            if ($ts === false) {
                $reasons[] = 'invalid_schedule_date';
            } elseif ($ts <= time()) {
                $reasons[] = 'stuck_in_past_schedule';
            }
        }
    }
    return $reasons;
}
}
