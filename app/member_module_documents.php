<?php
declare(strict_types=1);

if (!function_exists('member_document_module_definitions')) {
function member_document_module_definitions(): array
{
    return [
        'presentations' => [
            'route' => 'presentations',
            'admin_route' => 'admin_presentations',
            'domain' => 'presentations',
            'legacy_categories' => ['presentations'],
            'fallback_title' => 'Presentations',
            'fallback_intro' => 'Supports de reunions, exposes et documents presentes aux membres.',
        ],
        'videos' => [
            'route' => 'videos',
            'admin_route' => 'admin_videos',
            'domain' => 'videos',
            'legacy_categories' => ['videos', 'medias'],
            'fallback_title' => 'Videos',
            'fallback_intro' => 'Videos et ressources audiovisuelles partagees avec les membres du club.',
        ],
        'fichiers' => [
            'route' => 'fichiers',
            'admin_route' => 'admin_fichiers',
            'domain' => 'fichiers',
            'legacy_categories' => ['fichiers', 'telechargements'],
            'fallback_title' => 'Fichiers',
            'fallback_intro' => 'Fichiers et ressources a telecharger pour les membres.',
        ],
        'pv' => [
            'route' => 'pv',
            'admin_route' => 'admin_pv',
            'domain' => 'pv',
            'legacy_categories' => ['pv'],
            'fallback_title' => 'Procès verbaux',
            'fallback_intro' => 'Procès verbaux et comptes rendus disponibles pour les membres.',
        ],
    ];
}
}

if (!function_exists('member_document_module_normalize')) {
function member_document_module_normalize(string $module): string
{
    $module = preg_replace('/[^a-z0-9_]/', '', strtolower($module)) ?: '';

    return $module === 'telechargements' ? 'fichiers' : $module;
}
}

if (!function_exists('member_document_module_definition')) {
function member_document_module_definition(string $module): ?array
{
    $module = member_document_module_normalize($module);
    $definitions = member_document_module_definitions();

    return isset($definitions[$module]) && is_array($definitions[$module]) ? $definitions[$module] : null;
}
}

if (!function_exists('member_document_labels')) {
function member_document_labels(string $locale): array
{
    $labels = [
        'fr' => [
            'members_area' => 'Espace membres',
            'documents' => 'Documents',
            'formats' => 'Formats',
            'latest' => 'Dernier ajout',
            'none' => 'Aucun',
            'view_content' => 'Voir les contenus',
            'administration' => 'Administration',
            'search_ph' => 'Rechercher par titre, resume ou contenu indexe',
            'search' => 'Rechercher',
            'reset' => 'Reinitialiser',
            'empty' => 'Aucun contenu trouve.',
            'for_filters' => ' pour ces filtres',
            'tags' => 'Etiquettes',
            'preview' => 'Apercu',
            'open' => 'Ouvrir',
            'storage_unavailable' => 'Ce module est temporairement indisponible.',
            'upload_title' => 'Ajouter un contenu',
            'upload_help' => 'Le contenu sera disponible uniquement dans ce module.',
            'title_field' => 'Titre',
            'description_field' => 'Description',
            'tags_field' => 'Etiquettes',
            'document_field' => 'Fichier',
            'upload' => 'Ajouter',
            'delete' => 'Supprimer',
            'confirm_delete' => 'Supprimer ce contenu ?',
            'ok_added' => 'Contenu ajoute.',
            'ok_deleted' => 'Contenu supprime.',
            'err_required' => 'Titre et fichier requis.',
            'err_invalid' => 'Type de fichier non autorise.',
            'admin_title_prefix' => 'Administration',
            'content_list' => 'Contenus',
        ],
        'en' => [
            'members_area' => 'Members area',
            'documents' => 'Documents',
            'formats' => 'Formats',
            'latest' => 'Latest upload',
            'none' => 'None',
            'view_content' => 'View content',
            'administration' => 'Administration',
            'search_ph' => 'Search by title, summary or indexed content',
            'search' => 'Search',
            'reset' => 'Reset',
            'empty' => 'No content found.',
            'for_filters' => ' for these filters',
            'tags' => 'Tags',
            'preview' => 'Preview',
            'open' => 'Open',
            'storage_unavailable' => 'This module is temporarily unavailable.',
            'upload_title' => 'Add content',
            'upload_help' => 'The content will be available only in this module.',
            'title_field' => 'Title',
            'description_field' => 'Description',
            'tags_field' => 'Tags',
            'document_field' => 'File',
            'upload' => 'Add',
            'delete' => 'Delete',
            'confirm_delete' => 'Delete this content?',
            'ok_added' => 'Content added.',
            'ok_deleted' => 'Content deleted.',
            'err_required' => 'Title and file are required.',
            'err_invalid' => 'File type is not allowed.',
            'admin_title_prefix' => 'Administration',
            'content_list' => 'Content',
        ],
    ];

    return $labels[$locale] ?? $labels['en'];
}
}

if (!function_exists('member_document_module_text')) {
function member_document_module_text(string $module, string $locale): array
{
    $definition = member_document_module_definition($module);
    if ($definition === null) {
        return ['title' => ucfirst($module), 'intro' => '', 'meta_desc' => ''];
    }

    $domain = (string) ($definition['domain'] ?? $module);
    $messages = i18n_domain_locale($domain, $locale);
    $title = trim((string) ($messages['title'] ?? ''));
    $intro = trim((string) ($messages['intro'] ?? ''));
    $metaDesc = trim((string) ($messages['meta_desc'] ?? ''));

    if ($title === '') {
        $title = (string) ($definition['fallback_title'] ?? ucfirst($module));
    }
    if ($intro === '') {
        $intro = (string) ($definition['fallback_intro'] ?? '');
    }
    if ($metaDesc === '') {
        $metaDesc = $intro;
    }

    return ['title' => $title, 'intro' => $intro, 'meta_desc' => $metaDesc];
}
}

if (!function_exists('ensure_member_module_documents_table')) {
function ensure_member_module_documents_table(): bool
{
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS member_module_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            module_code VARCHAR(80) NOT NULL,
            member_id INT NOT NULL,
            tags VARCHAR(255) NOT NULL DEFAULT "",
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            file_path VARCHAR(255) NOT NULL,
            extracted_text LONGTEXT NULL,
            legacy_library_document_id INT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_module_uploaded (module_code, uploaded_at),
            INDEX idx_member_module (member_id, module_code),
            INDEX idx_module_tags (module_code, tags)
        )');

        if (!table_has_column('member_module_documents', 'legacy_library_document_id')) {
            db()->exec('ALTER TABLE member_module_documents ADD COLUMN legacy_library_document_id INT NULL AFTER extracted_text');
        }

        member_document_migrate_library_categories();

        return table_exists('member_module_documents');
    } catch (Throwable) {
        return false;
    }
}
}

if (!function_exists('member_document_migrate_library_categories')) {
function member_document_migrate_library_categories(): void
{
    if (!table_exists('member_library_documents') || !table_exists('member_module_documents')) {
        return;
    }

    $allLegacyCategories = [];
    foreach (member_document_module_definitions() as $moduleCode => $definition) {
        $legacyCategories = (array) ($definition['legacy_categories'] ?? []);
        if ($legacyCategories === []) {
            continue;
        }
        foreach ($legacyCategories as $legacyCategory) {
            $legacyCategory = trim((string) $legacyCategory);
            if ($legacyCategory !== '') {
                $allLegacyCategories[$legacyCategory] = true;
            }
        }
        $placeholders = implode(',', array_fill(0, count($legacyCategories), '?'));
        $select = db()->prepare('SELECT * FROM member_library_documents WHERE category IN (' . $placeholders . ') ORDER BY id ASC');
        $select->execute(array_values($legacyCategories));
        $rows = $select->fetchAll() ?: [];
        $exists = db()->prepare('SELECT id FROM member_module_documents WHERE legacy_library_document_id = ? AND module_code = ? LIMIT 1');
        $insert = db()->prepare(
            'INSERT INTO member_module_documents (module_code, member_id, tags, title, description, file_path, extracted_text, legacy_library_document_id, uploaded_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($rows as $row) {
            $legacyId = (int) ($row['id'] ?? 0);
            if ($legacyId <= 0) {
                continue;
            }
            $exists->execute([$legacyId, $moduleCode]);
            if ($exists->fetch()) {
                continue;
            }
            $insert->execute([
                $moduleCode,
                (int) ($row['member_id'] ?? 0),
                (string) ($row['tags'] ?? ''),
                (string) ($row['title'] ?? ''),
                (string) ($row['description'] ?? ''),
                (string) ($row['file_path'] ?? ''),
                (string) ($row['extracted_text'] ?? ''),
                $legacyId,
                (string) ($row['uploaded_at'] ?? date('Y-m-d H:i:s')),
            ]);
        }
    }

    if ($allLegacyCategories !== []) {
        $legacyCategories = array_keys($allLegacyCategories);
        $placeholders = implode(',', array_fill(0, count($legacyCategories), '?'));
        db()->prepare('DELETE FROM member_library_documents WHERE category IN (' . $placeholders . ')')->execute($legacyCategories);
        if (table_exists('member_library_categories')) {
            db()->prepare('DELETE FROM member_library_categories WHERE code IN (' . $placeholders . ')')->execute($legacyCategories);
        }
    }
}
}

if (!function_exists('member_document_safe_path')) {
function member_document_safe_path(string $path): ?string
{
    return safe_storage_public_path_or_null($path, [
        'storage/uploads/member_modules/',
        'storage/uploads/library/',
    ]);
}
}

if (!function_exists('member_document_store_upload')) {
function member_document_store_upload(array $file, string $moduleCode, int $memberId): array
{
    $allowedExtensions = ['pdf', 'docx', 'txt', 'md', 'html', 'htm', 'ppt', 'pptx', 'xls', 'xlsx', 'csv', 'zip', 'jpg', 'jpeg', 'png', 'webp', 'mp4', 'webm', 'mov', 'm4v'];
    $allowedMimes = [
        'pdf' => ['application/pdf', 'application/x-pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'txt' => ['text/plain', 'application/octet-stream'],
        'md' => ['text/plain', 'text/markdown', 'application/octet-stream'],
        'html' => ['text/html', 'text/plain', 'application/octet-stream'],
        'htm' => ['text/html', 'text/plain', 'application/octet-stream'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
        'xls' => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'csv' => ['text/csv', 'text/plain', 'application/octet-stream'],
        'zip' => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'mp4' => ['video/mp4', 'application/octet-stream'],
        'webm' => ['video/webm', 'application/octet-stream'],
        'mov' => ['video/quicktime', 'application/octet-stream'],
        'm4v' => ['video/x-m4v', 'video/mp4', 'application/octet-stream'],
    ];

    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('err_invalid');
    }

    $targetDir = dirname(__DIR__) . '/storage/uploads/member_modules/' . $moduleCode;
    $base = slugify(pathinfo($originalName, PATHINFO_FILENAME));
    if ($base === '') {
        $base = 'document';
    }
    $filename = secure_move_uploaded_file(
        $file,
        $targetDir,
        'doc_' . $memberId . '-' . $base,
        $allowedExtensions,
        $allowedMimes,
        100 * 1024 * 1024
    );

    return [
        'public_path' => 'storage/uploads/member_modules/' . $moduleCode . '/' . $filename,
        'absolute_path' => $targetDir . '/' . $filename,
        'extension' => $extension,
    ];
}
}

if (!function_exists('member_document_extract_text')) {
function member_document_extract_text(string $path, string $extension): string
{
    $extension = strtolower($extension);
    if ($extension === 'pdf' && function_exists('article_extract_pdf_text')) {
        return article_extract_pdf_text($path);
    }
    if ($extension === 'docx' && function_exists('article_extract_docx_html')) {
        return trim(strip_tags(article_extract_docx_html($path)));
    }
    if (in_array($extension, ['txt', 'md', 'html', 'htm', 'csv'], true)) {
        $raw = @file_get_contents($path);
        if (!is_string($raw)) {
            return '';
        }
        if (in_array($extension, ['html', 'htm'], true)) {
            $raw = strip_tags($raw);
        }
        return trim((string) preg_replace('/\s+/u', ' ', $raw));
    }

    return '';
}
}

if (!function_exists('member_document_module_stats')) {
function member_document_module_stats(string $moduleCode): array
{
    $countStmt = db()->prepare('SELECT COUNT(*) FROM member_module_documents WHERE module_code = ?');
    $countStmt->execute([$moduleCode]);
    $total = (int) $countStmt->fetchColumn();

    $formatStmt = db()->prepare('SELECT file_path FROM member_module_documents WHERE module_code = ?');
    $formatStmt->execute([$moduleCode]);
    $formats = [];
    foreach ($formatStmt->fetchAll() ?: [] as $row) {
        $extension = strtolower(pathinfo((string) ($row['file_path'] ?? ''), PATHINFO_EXTENSION));
        if ($extension !== '') {
            $formats[$extension] = true;
        }
    }

    $latestStmt = db()->prepare('SELECT uploaded_at FROM member_module_documents WHERE module_code = ? ORDER BY uploaded_at DESC, id DESC LIMIT 1');
    $latestStmt->execute([$moduleCode]);
    $latest = trim((string) ($latestStmt->fetchColumn() ?: ''));

    return ['total' => $total, 'formats' => count($formats), 'latest' => $latest];
}
}

if (!function_exists('member_document_fetch_documents')) {
function member_document_fetch_documents(string $moduleCode, string $search, int $limit = 60): array
{
    $where = ['module_code = ?'];
    $params = [$moduleCode];
    if ($search !== '') {
        $where[] = '(title LIKE ? OR description LIKE ? OR extracted_text LIKE ? OR tags LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like);
    }
    $stmt = db()->prepare('SELECT * FROM member_module_documents WHERE ' . implode(' AND ', $where) . ' ORDER BY uploaded_at DESC, id DESC LIMIT ' . (int) $limit);
    $stmt->execute($params);

    return $stmt->fetchAll() ?: [];
}
}

if (!function_exists('render_member_document_module_cards')) {
function render_member_document_module_cards(array $documents, array $labels): string
{
    $html = '';
    foreach ($documents as $document) {
        $safePath = member_document_safe_path((string) ($document['file_path'] ?? ''));
        if ($safePath === null) {
            continue;
        }
        $extension = strtolower(pathinfo($safePath, PATHINFO_EXTENSION));
        $docTitle = trim((string) ($document['title'] ?? ''));
        if ($docTitle === '') {
            $docTitle = (string) $labels['documents'];
        }
        $docDescription = trim((string) ($document['description'] ?? ''));
        $docTags = trim((string) ($document['tags'] ?? ''));
        $docExtract = trim((string) ($document['extracted_text'] ?? ''));
        $html .= '<article class="news-card feature-card member-document-card">'
            . '<span class="badge muted">' . e(strtoupper($extension)) . '</span>'
            . '<h2>' . e($docTitle) . '</h2>';
        if ($docDescription !== '') {
            $html .= '<p>' . e($docDescription) . '</p>';
        }
        if ($docTags !== '') {
            $html .= '<p class="help">' . e((string) $labels['tags']) . ': ' . e($docTags) . '</p>';
        }
        if ($docExtract !== '') {
            $html .= '<p class="help">' . e(mb_safe_strimwidth($docExtract, 0, 220, '...')) . '</p>';
        }
        if ($extension === 'pdf') {
            $html .= '<details class="member-document-preview-toggle"><summary>' . e((string) $labels['preview']) . '</summary>'
                . '<iframe src="' . e(base_url($safePath)) . '" class="member-document-pdf-preview" loading="lazy"></iframe></details>';
        }
        $html .= '<p class="actions"><a class="button secondary" href="' . e(base_url($safePath)) . '" target="_blank" rel="noopener">' . e((string) $labels['open']) . '</a></p>'
            . '</article>';
    }

    return $html;
}
}

if (!function_exists('render_member_document_module_page')) {
function render_member_document_module_page(string $module): void
{
    $moduleCode = member_document_module_normalize($module);
    $definition = member_document_module_definition($moduleCode);
    if ($definition === null) {
        http_response_code(404);
        echo render_layout('<div class="card"><h1>404</h1><p>Module indisponible.</p></div>', '404');
        return;
    }

    require_login();
    $locale = current_locale();
    $labels = member_document_labels($locale);
    $memberAreaLabel = member_area_eyebrow_label($locale);
    $moduleText = member_document_module_text($moduleCode, $locale);
    $title = (string) $moduleText['title'];
    $intro = (string) $moduleText['intro'];

    set_page_meta([
        'title' => $title,
        'description' => (string) $moduleText['meta_desc'],
        'robots' => 'noindex,follow',
        'schema_type' => 'CollectionPage',
    ]);

    if (!ensure_member_module_documents_table()) {
        echo render_layout('<div class="card"><p>' . e((string) $labels['storage_unavailable']) . '</p></div>', $title);
        return;
    }

    $search = trim((string) ($_GET['q'] ?? ''));
    if (mb_strlen($search) > 120) {
        $search = mb_substr($search, 0, 120);
    }

    $stats = member_document_module_stats($moduleCode);
    $documents = member_document_fetch_documents($moduleCode, $search);
    $latestDate = trim((string) ($stats['latest'] ?? ''));
    $latestLabel = $latestDate !== '' ? date('d/m/Y', strtotime($latestDate) ?: time()) : (string) $labels['none'];
    $adminRoute = (string) ($definition['admin_route'] ?? ('admin_' . $moduleCode));

    ob_start();
    ?>
    <div class="stack member-document-module">
        <section class="page-hero member-document-hero member-module-hero">
            <div class="member-document-hero-copy">
                <p class="eyebrow"><?= e($memberAreaLabel) ?></p>
                <h1 class="member-document-heading"><?= e($title) ?></h1>
                <?php if ($intro !== ''): ?><p class="help"><?= e($intro) ?></p><?php endif; ?>
            </div>
            <div class="member-document-hero-side">
                <div class="member-document-hero-stats">
                    <article>
                        <span><?= e((string) $labels['documents']) ?></span>
                        <strong><?= (int) ($stats['total'] ?? 0) ?></strong>
                    </article>
                    <article>
                        <span><?= e((string) $labels['formats']) ?></span>
                        <strong><?= (int) ($stats['formats'] ?? 0) ?></strong>
                    </article>
                    <article>
                        <span><?= e((string) $labels['latest']) ?></span>
                        <strong><?= e($latestLabel) ?></strong>
                    </article>
                </div>
                <p class="actions member-document-hero-actions">
                    <a class="button secondary" href="#member-document-list"><?= e((string) $labels['view_content']) ?></a>
                    <?php if (has_permission('admin.access')): ?>
                        <a class="button" href="<?= e(route_url($adminRoute)) ?>"><?= e((string) $labels['administration']) ?></a>
                    <?php endif; ?>
                </p>
            </div>
        </section>

        <section class="card member-document-search-panel">
            <form method="get" class="inline-form member-document-search-form">
                <input type="hidden" name="route" value="<?= e((string) ($definition['route'] ?? $moduleCode)) ?>">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $labels['search_ph']) ?>">
                <button class="button" type="submit"><?= e((string) $labels['search']) ?></button>
                <?php if ($search !== ''): ?>
                    <a class="button secondary" href="<?= e(route_url((string) ($definition['route'] ?? $moduleCode))) ?>"><?= e((string) $labels['reset']) ?></a>
                <?php endif; ?>
            </form>
        </section>

        <section id="member-document-list" class="member-document-content">
            <?php if ($documents === []): ?>
                <div class="card">
                    <p><?= e((string) $labels['empty']) ?><?php if ($search !== ''): ?><?= e((string) $labels['for_filters']) ?>.<?php endif; ?></p>
                </div>
            <?php else: ?>
                <div class="news-grid member-document-grid">
                    <?= render_member_document_module_cards($documents, $labels) ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <?php
    echo render_layout((string) ob_get_clean(), $title);
}
}

if (!function_exists('render_admin_member_document_module_page')) {
function render_admin_member_document_module_page(string $module): void
{
    require_permission('admin.access');
    $user = current_user();
    $moduleCode = member_document_module_normalize($module);
    $definition = member_document_module_definition($moduleCode);
    if ($definition === null) {
        http_response_code(404);
        echo render_layout('<div class="card"><h1>404</h1><p>Module indisponible.</p></div>', '404');
        return;
    }

    $locale = current_locale();
    $labels = member_document_labels($locale);
    $moduleText = member_document_module_text($moduleCode, $locale);
    $title = (string) $moduleText['title'];
    $adminRoute = (string) ($definition['admin_route'] ?? ('admin_' . $moduleCode));

    set_page_meta([
        'title' => (string) $labels['admin_title_prefix'] . ' - ' . $title,
        'description' => (string) $moduleText['meta_desc'],
        'robots' => 'noindex,nofollow',
    ]);

    if (!ensure_member_module_documents_table()) {
        echo render_layout('<div class="card"><p>' . e((string) $labels['storage_unavailable']) . '</p></div>', $title);
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            verify_csrf();
            $action = (string) ($_POST['action'] ?? 'upload');
            if ($action === 'delete_document') {
                $id = (int) ($_POST['id'] ?? 0);
                $stmt = db()->prepare('SELECT file_path FROM member_module_documents WHERE id = ? AND module_code = ? LIMIT 1');
                $stmt->execute([$id, $moduleCode]);
                $path = (string) ($stmt->fetchColumn() ?: '');
                $safePath = member_document_safe_path($path);
                if ($safePath !== null && str_starts_with($safePath, 'storage/uploads/member_modules/')) {
                    $absolute = dirname(__DIR__) . '/' . $safePath;
                    if (is_file($absolute)) {
                        @unlink($absolute);
                    }
                }
                db()->prepare('DELETE FROM member_module_documents WHERE id = ? AND module_code = ?')->execute([$id, $moduleCode]);
                set_flash('success', (string) $labels['ok_deleted']);
                redirect($adminRoute);
            }

            $uploadTitle = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $tags = mb_safe_substr(trim((string) ($_POST['tags'] ?? '')), 0, 255);
            $file = $_FILES['document'] ?? null;
            if ($uploadTitle === '' || !is_array($file)) {
                throw new RuntimeException('err_required');
            }
            $stored = member_document_store_upload($file, $moduleCode, (int) ($user['id'] ?? 0));
            $extractedText = member_document_extract_text((string) $stored['absolute_path'], (string) $stored['extension']);
            db()->prepare('INSERT INTO member_module_documents (module_code, member_id, tags, title, description, file_path, extracted_text) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$moduleCode, (int) ($user['id'] ?? 0), $tags, $uploadTitle, $description, (string) $stored['public_path'], $extractedText]);
            set_flash('success', (string) $labels['ok_added']);
            redirect($adminRoute);
        } catch (Throwable $throwable) {
            $key = $throwable->getMessage();
            set_flash('error', (string) ($labels[$key] ?? $key));
            redirect($adminRoute);
        }
    }

    $search = trim((string) ($_GET['q'] ?? ''));
    if (mb_strlen($search) > 120) {
        $search = mb_substr($search, 0, 120);
    }
    $stats = member_document_module_stats($moduleCode);
    $documents = member_document_fetch_documents($moduleCode, $search, 100);
    $latestDate = trim((string) ($stats['latest'] ?? ''));
    $latestLabel = $latestDate !== '' ? date('d/m/Y', strtotime($latestDate) ?: time()) : (string) $labels['none'];

    ob_start();
    ?>
    <div class="stack admin-member-document-module">
        <section class="page-hero admin-member-document-hero">
            <div class="admin-member-document-hero-copy">
                <p class="eyebrow"><?= e((string) $labels['admin_title_prefix']) ?></p>
                <h1><?= e($title) ?></h1>
                <p class="help"><?= e((string) $moduleText['intro']) ?></p>
            </div>
            <div class="admin-member-document-hero-side">
                <div class="member-document-hero-stats">
                    <article>
                        <span><?= e((string) $labels['documents']) ?></span>
                        <strong><?= (int) ($stats['total'] ?? 0) ?></strong>
                    </article>
                    <article>
                        <span><?= e((string) $labels['formats']) ?></span>
                        <strong><?= (int) ($stats['formats'] ?? 0) ?></strong>
                    </article>
                    <article>
                        <span><?= e((string) $labels['latest']) ?></span>
                        <strong><?= e($latestLabel) ?></strong>
                    </article>
                </div>
                <p class="actions admin-member-document-hero-actions">
                    <a class="button secondary" href="<?= e(route_url((string) ($definition['route'] ?? $moduleCode))) ?>"><?= e((string) $labels['view_content']) ?></a>
                    <a class="button" href="#admin-member-document-upload"><?= e((string) $labels['upload_title']) ?></a>
                </p>
            </div>
        </section>

        <section class="card" id="admin-member-document-upload">
            <h2><?= e((string) $labels['upload_title']) ?></h2>
            <p class="help"><?= e((string) $labels['upload_help']) ?></p>
            <form method="post" enctype="multipart/form-data" class="stack admin-member-document-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="upload">
                <label><span><?= e((string) $labels['title_field']) ?></span><input type="text" name="title" maxlength="255" required></label>
                <label><span><?= e((string) $labels['description_field']) ?></span><textarea name="description" rows="4"></textarea></label>
                <label><span><?= e((string) $labels['tags_field']) ?></span><input type="text" name="tags" maxlength="255"></label>
                <label><span><?= e((string) $labels['document_field']) ?></span><input type="file" name="document" required></label>
                <p class="actions"><button class="button" type="submit"><?= e((string) $labels['upload']) ?></button></p>
            </form>
        </section>

        <section class="card member-document-search-panel">
            <form method="get" class="inline-form member-document-search-form">
                <input type="hidden" name="route" value="<?= e($adminRoute) ?>">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $labels['search_ph']) ?>">
                <button class="button" type="submit"><?= e((string) $labels['search']) ?></button>
                <?php if ($search !== ''): ?>
                    <a class="button secondary" href="<?= e(route_url($adminRoute)) ?>"><?= e((string) $labels['reset']) ?></a>
                <?php endif; ?>
            </form>
        </section>

        <section class="admin-member-document-list">
            <h2><?= e((string) $labels['content_list']) ?></h2>
            <?php if ($documents === []): ?>
                <div class="card"><p><?= e((string) $labels['empty']) ?></p></div>
            <?php else: ?>
                <div class="news-grid member-document-grid">
                    <?php foreach ($documents as $document): ?>
                        <?php $safePath = member_document_safe_path((string) ($document['file_path'] ?? '')); ?>
                        <?php if ($safePath === null) { continue; } ?>
                        <?php $extension = strtolower(pathinfo($safePath, PATHINFO_EXTENSION)); ?>
                        <article class="news-card feature-card member-document-card">
                            <span class="badge muted"><?= e(strtoupper($extension)) ?></span>
                            <h3><?= e((string) ($document['title'] ?? $labels['documents'])) ?></h3>
                            <?php if (trim((string) ($document['description'] ?? '')) !== ''): ?><p><?= e((string) $document['description']) ?></p><?php endif; ?>
                            <?php if (trim((string) ($document['tags'] ?? '')) !== ''): ?><p class="help"><?= e((string) $labels['tags']) ?>: <?= e((string) $document['tags']) ?></p><?php endif; ?>
                            <p class="actions">
                                <a class="button secondary" href="<?= e(base_url($safePath)) ?>" target="_blank" rel="noopener"><?= e((string) $labels['open']) ?></a>
                                <form method="post" class="inline-form" onsubmit="return confirm('<?= e((string) $labels['confirm_delete']) ?>');">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete_document">
                                    <input type="hidden" name="id" value="<?= (int) ($document['id'] ?? 0) ?>">
                                    <button class="button secondary" type="submit"><?= e((string) $labels['delete']) ?></button>
                                </form>
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <?php
    echo render_layout((string) ob_get_clean(), (string) $labels['admin_title_prefix'] . ' - ' . $title);
}
}
