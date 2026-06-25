<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MemberModulesFinalizationTest extends TestCase
{
    private function source(string $relativePath): string
    {
        $source = file_get_contents(dirname(__DIR__) . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        self::assertIsString($source);

        return $source;
    }

    public function testAlbumsModuleKeepsUploadSelectAndProposalMetadataControls(): void
    {
        self::assertSame('storage/uploads/albums/thumbs/photo.jpg', album_thumbnail_public_path('storage/uploads/albums/photo.webp'));
        self::assertSame('storage/uploads/albums/thumbs/photo.webp', album_thumbnail_webp_public_path('storage/uploads/albums/photo.jpg'));
        self::assertSame('storage/uploads/albums/display/photo.webp', album_display_webp_public_path('storage/uploads/albums/photo.png'));

        $albums = $this->source('pages/albums.php');
        $album = $this->source('pages/album.php');
        $albumHelpers = $this->source('app/album_helpers.php');
        $albumSchema = $this->source('app/album_schema.php');
        $albumJs = $this->source('assets/js/modules/album.js');
        $albumsCss = $this->source('assets/css/modules/albums.css');
        $adminAlbumsJs = $this->source('assets/js/modules/admin_albums.js');
        $runtimeUpdates = $this->source('app/runtime_schema_updates.php');
        $schema = $this->source('schema/schema.sql');
        self::assertStringContainsString('name="proposal_theme"', $albums);
        self::assertStringContainsString('name="proposal_subcategory_ref"', $albums);
        self::assertStringContainsString('foreach ($albumCategories as $albumThemeCode => $albumThemeLabel)', $albums);
        self::assertStringContainsString('name="proposal_keywords"', $albums);
        self::assertStringContainsString("(string) \$t['keywords_label'] => \$keywords", $albums);
        self::assertStringContainsString("(string) \$t['subcategory_field'] => \$proposalSubcategory", $albums);
        self::assertStringContainsString('$favoriteAlbumIds = $user !== null ? album_favorite_album_ids', $albums);
        self::assertStringContainsString('$visibleAlbumCategories = album_visible_categories($albumCategories, $albumCategoryCounts);', $albums);
        self::assertStringContainsString('$visibleAlbumSubcategoriesByCategory = album_visible_subcategories_by_category', $albums);
        self::assertStringContainsString('album_ensure_photo_sort_order_column()', $albums);
        self::assertStringContainsString('album_ensure_schema_columns_and_indexes()', $albums);
        self::assertStringContainsString("route_url('admin_albums')", $albums);
        self::assertStringContainsString('$regularWhere = $where . \' AND a.is_featured = 0\';', $albums);
        self::assertStringContainsString('AND a.is_featured = 1', $albums);
        self::assertStringContainsString('$featuredAlbumsTitle = (string) $t[\'featured_albums\'];', $albums);
        self::assertStringContainsString('$albumSections = album_listing_sections($featuredRows, $rows, $featuredAlbumsTitle, $otherAlbumsTitle);', $albums);
        self::assertStringContainsString('albums-featured-section', $albums);
        self::assertStringContainsString('album-featured-badge', $albums);
        self::assertStringContainsString("\$t['first_page']", $albums);
        self::assertStringContainsString("'p' => 1", $albums);
        self::assertStringContainsString("\$t['last_page']", $albums);
        self::assertStringContainsString("'p' => \$totalPages", $albums);
        self::assertStringContainsString("log_structured_event('album_tile_render_prepare_failed'", $albums);
        self::assertStringContainsString("\$albumTitle = trim((string) (\$row['title'] ?? ''));", $albums);
        self::assertStringContainsString('$descriptionText = html_entity_decode(strip_tags($descriptionText), ENT_QUOTES | ENT_HTML5, \'UTF-8\');', $albums);
        self::assertStringContainsString('mb_safe_strimwidth($descriptionText, 0, 150, \'...\')', $albums);
        self::assertStringContainsString('album_sync_accepted_proposals();', $albums);
        self::assertStringContainsString('album_clear_caches();', $albums);
        self::assertStringContainsString('function albums_page_post_checkbox(string $key, ?int $default = null, string ...$fallbackKeys): ?int', $albums);
        self::assertStringContainsString("array_key_exists('album_is_featured_present', \$_POST)", $albums);
        self::assertStringContainsString('album_update_record($albumId, $title, $description, null, $category, $subcategory, $isFeatured);', $albums);
        self::assertStringContainsString('name="album_is_featured_present" value="1"', $albums);
        self::assertStringContainsString('name="album_is_featured" value="1" autocomplete="off" <?= $albumFeatured ? \'checked\' : \'\' ?>', $albums);
        self::assertStringContainsString('album_ensure_photo_sort_order_column()', $album);
        self::assertStringContainsString("log_structured_event('album_detail_photos_prepare_failed'", $album);
        self::assertStringContainsString("log_structured_event('album_detail_photo_render_skipped'", $album);
        self::assertStringContainsString("\$albumTitle = trim((string) (\$album['title'] ?? ''));", $album);
        self::assertStringContainsString('$albumDescriptionText = html_entity_decode(strip_tags($albumDescriptionText), ENT_QUOTES | ENT_HTML5, \'UTF-8\');', $album);
        self::assertStringContainsString('data-album-viewer-open', $album);
        self::assertStringContainsString('data-photo-display-src', $album);
        self::assertStringContainsString('album_picture_html($imageSrc', $album);
        self::assertStringContainsString('id="album-photo-viewer"', $album);
        self::assertStringContainsString('data-album-description="<?= e($albumDescriptionText) ?>"', $album);
        self::assertStringContainsString('album_picture_html($coverSrc', $albums);
        self::assertStringContainsString('data-album-viewer-image', $albumJs);
        self::assertStringContainsString("link.getAttribute('data-photo-display-src')", $albumJs);
        self::assertStringContainsString('dialog.showModal();', $albumJs);
        self::assertStringContainsString('.album-photo-viewer-copy', $albumsCss);
        self::assertStringContainsString("if (\$action === 'update_album' || \$action === 'delete_album')", $albums);
        self::assertStringContainsString("!\$canManageAlbums && (int) (\$album['member_id'] ?? 0) !== (int) (\$user['id'] ?? 0)", $albums);
        self::assertStringContainsString("'Action' => 'update_album'", $albums);
        self::assertStringContainsString("'Action' => 'delete_album'", $albums);
        self::assertStringContainsString("content_proposal_create((int) \$user['id'], 'albums', 'content', \$title, \$summary", $albums);
        self::assertStringContainsString('data-album-modal-open="<?= e($editDialogId) ?>"', $albums);

        $adminAlbums = $this->source('pages/admin_albums.php');
        self::assertMatchesRegularExpression('/<select\s+name="album_id"\s+required>.*foreach \(\$albums as \$album\)/s', $adminAlbums);
        self::assertStringContainsString('album_sync_accepted_proposals();', $adminAlbums);
        self::assertStringContainsString('album_ensure_schema_columns_and_indexes()', $adminAlbums);
        self::assertStringContainsString('function albums_admin_post_checkbox(string $key, ?int $recordId = null, string ...$fallbackKeys): int', $adminAlbums);
        self::assertStringContainsString('function albums_admin_post_form_checkbox(string $key, string $presenceKey, ?int $recordId = null, string ...$fallbackKeys): int', $adminAlbums);
        self::assertStringContainsString("\$isFeatured = albums_admin_post_form_checkbox('album_is_featured', 'album_is_featured_present', null, 'is_featured');", $adminAlbums);
        self::assertStringNotContainsString('type="hidden" name="is_featured" value="0"', $adminAlbums);
        self::assertStringNotContainsString('type="hidden" name="album_is_featured" value="0"', $adminAlbums);
        self::assertStringContainsString('type="hidden" name="album_is_featured_present" value="1"', $adminAlbums);
        self::assertStringContainsString('name="album_is_featured[]" value="1"', $adminAlbums);
        self::assertStringContainsString('$albumEditFormId = \'admin-album-edit-form-\' . $albumId;', $adminAlbums);
        self::assertStringContainsString('method="post" action="<?= e(route_url(\'admin_albums\')) ?>"', $adminAlbums);
        self::assertStringContainsString('type="submit" data-admin-album-save', $adminAlbums);
        self::assertStringContainsString("document.querySelectorAll('[data-admin-album-save]')", $adminAlbumsJs);
        self::assertStringContainsString('if (button.form === form) {', $adminAlbumsJs);
        self::assertStringContainsString('form.requestSubmit();', $adminAlbumsJs);
        self::assertStringNotContainsString('name="album_is_featured[<?= (int) $album[\'id\'] ?>]"', $adminAlbums);
        self::assertStringNotContainsString("\$isFeatured = isset(\$_POST['is_featured']) ? 1 : 0;", $adminAlbums);
        self::assertStringContainsString('is_featured, publish_requested', $adminAlbums);
        self::assertStringContainsString('album_update_record($albumId, $title, $description, $isPublic, $category, $subcategory, $isFeatured);', $adminAlbums);
        self::assertStringNotContainsString("cache_remember('admin_albums_list_v2'", $adminAlbums);
        self::assertStringContainsString('album_delete_record($albumId);', $adminAlbums);
        self::assertStringContainsString('id="album-wizard"', $adminAlbums);
        self::assertStringContainsString('name="album_wizard"', $adminAlbums);
        self::assertStringContainsString("album_admin_wizard_url(['album_wizard' => \$albumId, 'step' => 2])", $adminAlbums);
        self::assertStringContainsString("album_admin_wizard_url(['album_wizard' => \$albumId, 'step' => 3])", $adminAlbums);
        self::assertStringContainsString('function albums_admin_photo_render_data(array $photo, array $messages, string $logEvent): array', $adminAlbums);
        self::assertStringContainsString('function albums_admin_log_photo_render_failure(Throwable $throwable, mixed $photo, string $event): void', $adminAlbums);
        self::assertStringContainsString("album_admin_wizard_photo_prepare_failed", $adminAlbums);
        self::assertStringContainsString("album_admin_wizard_photo_render_failed", $adminAlbums);
        self::assertStringContainsString("album_admin_photo_prepare_failed", $adminAlbums);
        self::assertStringContainsString("album_admin_photo_render_failed", $adminAlbums);
        self::assertStringContainsString('albums_admin_js_string((string) $t[\'confirm_delete_photo\'])', $adminAlbums);
        self::assertStringContainsString("if (\$action === 'finalize_album_creation')", $adminAlbums);
        self::assertStringContainsString('album_social_publish_if_public($albumId)', $adminAlbums);
        self::assertStringContainsString('return_wizard_album_id', $adminAlbums);
        self::assertStringContainsString('publish_requested', $adminAlbums);
        self::assertStringContainsString("if (\$action === 'add_subcategory')", $adminAlbums);
        self::assertStringContainsString("if (\$action === 'delete_subcategory')", $adminAlbums);
        self::assertStringContainsString('UPDATE albums SET category = "general", subcategory = "" WHERE category = ?', $adminAlbums);
        self::assertStringContainsString('render_album_taxonomy_fields($albumCategories, $t)', $adminAlbums);
        self::assertStringContainsString('return album_ensure_photo_sort_order_column();', $adminAlbums);
        self::assertStringContainsString('function album_social_publish_if_public(int $albumId): array', $albumHelpers);
        self::assertStringContainsString('function album_admin_wizard_url(array $query = []): string', $albumHelpers);
        self::assertStringContainsString("\$query['focus'] = 'album-wizard';", $albumHelpers);
        self::assertStringContainsString('function album_ensure_photo_sort_order_column(): bool', $albumHelpers);
        self::assertStringContainsString("function_exists('mb_convert_case')", $albumHelpers);
        self::assertStringContainsString('return ucwords(strtolower($label));', $albumHelpers);
        self::assertStringContainsString("table_has_column('album_photos', 'created_at')", $albumHelpers);
        self::assertStringContainsString("table_has_column('albums', (string) \$column)", $albumSchema);
        self::assertStringContainsString("'is_featured' => 'ALTER TABLE albums ADD COLUMN is_featured", $albumSchema);
        self::assertStringContainsString('idx_albums_featured_public', $albumSchema);
        self::assertStringContainsString('ALTER TABLE album_photos ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER file_path', $runtimeUpdates);
        self::assertStringContainsString('ALTER TABLE albums ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER social_publish_error', $albumSchema);
        self::assertStringContainsString("params.get('focus') === 'album-wizard'", $adminAlbumsJs);
        self::assertStringContainsString("wizard.scrollIntoView({ block: 'start' });", $adminAlbumsJs);
        self::assertStringContainsString('secure_move_uploaded_file(', $albumHelpers);
        self::assertStringContainsString('create_album_webp_derivatives($publicPath)', $albumHelpers);
        self::assertStringContainsString('function album_picture_html(', $albumHelpers);
        self::assertMatchesRegularExpression('/secure_move_uploaded_file\([^;]+8 \* 1024 \* 1024,\s+true\s+\)/s', $albumHelpers);
        self::assertStringContainsString("getenv('FACEBOOK_ALBUM_ID')", $albumHelpers);
        self::assertStringContainsString("\$facebookPageId . '/photos'", $albumHelpers);
        self::assertStringContainsString("\$facebookPageId . '/feed'", $albumHelpers);
        self::assertStringNotContainsString("\$facebookPageId . '/albums'", $albumHelpers);
        self::assertStringContainsString('function album_apply_accepted_proposal(array $proposal): ?int', $albumHelpers);
        self::assertStringContainsString('function album_sync_accepted_proposals(int $limit = 100): array', $albumHelpers);
        self::assertStringContainsString('function album_proposal_action(string $summary): string', $albumHelpers);
        self::assertStringContainsString('function album_update_record(', $albumHelpers);
        self::assertStringContainsString('SELECT id, is_public, is_featured FROM albums WHERE id = ? LIMIT 1', $albumHelpers);
        self::assertStringContainsString('is_public = ?, is_featured = ?', $albumHelpers);
        self::assertStringContainsString("log_structured_event('album_featured_update_mismatch'", $albumHelpers);
        self::assertStringContainsString('function album_delete_record(int $albumId): void', $albumHelpers);
        self::assertStringContainsString('function album_subcategory_ref(', $albumHelpers);
        self::assertStringContainsString('function album_visible_categories(', $albumHelpers);
        self::assertStringContainsString('function album_listing_sections(', $albumHelpers);
        self::assertStringContainsString('function album_favorite_album_ids(', $albumHelpers);
        self::assertStringContainsString('idx_album_category_deleted', $albumHelpers);
        self::assertStringContainsString('deleted_at IS NULL', $albumHelpers);
        self::assertStringContainsString('member_id', $albumHelpers);
        self::assertStringContainsString('subcategory', $albumHelpers);
        self::assertStringContainsString('source_proposal_id', $albumHelpers);
        self::assertStringContainsString("require_once __DIR__ . '/album_schema.php';", $runtimeUpdates);
        self::assertStringContainsString('idx_albums_member', $schema);
        self::assertStringContainsString('idx_albums_source_proposal', $schema);
        self::assertStringContainsString('is_featured TINYINT(1) NOT NULL DEFAULT 0', $schema);
        self::assertStringContainsString('idx_albums_featured_public', $schema);
        self::assertStringContainsString('sort_order INT NOT NULL DEFAULT 0', $schema);
        self::assertStringContainsString('publish_requested TINYINT(1) NOT NULL DEFAULT 0', $schema);
        self::assertStringContainsString('facebook_album_id VARCHAR(80) DEFAULT NULL', $schema);
        self::assertStringContainsString('facebook_post_id VARCHAR(80) DEFAULT NULL', $schema);
        self::assertStringContainsString('instagram_media_id VARCHAR(80) DEFAULT NULL', $schema);
        self::assertStringContainsString('social_publish_error TEXT DEFAULT NULL', $schema);
        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS member_module_documents', $schema);
        self::assertStringContainsString('idx_member_module_category_deleted', $schema);
        self::assertSame(
            "Club fieldday\n\nThématique: radio\nMots-clés: ft8",
            album_proposal_description_from_summary("Thematique: radio\nMots cles: ft8\nDescription: Club fieldday")
        );
    }

    public function testWebothequeModuleKeepsCategorySelectsAndKeywordInputs(): void
    {
        $webotheque = $this->source('app/member_webotheque.php');

        self::assertStringContainsString('id="webotheque-link-dialog"', $webotheque);
        self::assertStringContainsString('id="admin-webotheque-link-dialog"', $webotheque);
        self::assertStringContainsString('function render_webotheque_link_fields(', $webotheque);
        self::assertStringContainsString('<select name="category">', $webotheque);
        self::assertStringContainsString('name="subcategory_ref"', $webotheque);
        self::assertStringContainsString('function webotheque_subcategory_ref(', $webotheque);
        self::assertStringContainsString('function webotheque_ensure_categories_table(', $webotheque);
        self::assertStringContainsString('function webotheque_favorite_link_ids(', $webotheque);
        self::assertStringContainsString('$visibleSubcategoriesByCategory = webotheque_visible_subcategories_by_category', $webotheque);
        self::assertStringContainsString("if (\$action === 'add_category')", $webotheque);
        self::assertStringContainsString("if (\$action === 'delete_category')", $webotheque);
        self::assertStringContainsString('UPDATE member_webotheque_links SET category = "general", subcategory = "" WHERE category = ?', $webotheque);
        self::assertStringContainsString('name="tags"', $webotheque);
        self::assertStringContainsString('render_webotheque_link_fields($t, $categories, $proposalContact)', $webotheque);
        self::assertStringContainsString('render_webotheque_link_fields($t, $categories)', $webotheque);
        self::assertStringContainsString('function webotheque_apply_accepted_proposal(', $webotheque);
        self::assertStringContainsString('function webotheque_sync_accepted_proposals(', $webotheque);
        self::assertStringContainsString('webotheque_sync_accepted_proposals($categories, $t,', $webotheque);
        self::assertStringContainsString('webotheque_accepted_proposal_sync_failed', $webotheque);
        self::assertStringContainsString('member_webotheque_categories', $this->source('schema/schema.sql'));
        self::assertGreaterThanOrEqual(2, substr_count($webotheque, 'webotheque_apply_accepted_proposal('));
        self::assertSame('https://example.org', webotheque_normalize_url('example.org'));
    }

    public function testMembersLibraryModuleKeepsDocumentTopicSelectAndKeywords(): void
    {
        $library = $this->source('pages/members_library.php');
        $adminLibrary = $this->source('pages/admin_library.php');
        $adminHelpers = $this->source('app/admin_helpers.php');
        $memberLibraryHelpers = $this->source('app/member_library_helpers.php');
        $contentHelpers = $this->source('app/content_helpers.php');
        $routeHelperLoader = $this->source('app/route_helper_loader.php');
        $searchPage = $this->source('pages/search.php');
        $previewPage = $this->source('pages/member_library_preview.php');
        $myRequests = $this->source('pages/my_requests.php');
        $requestSecurity = $this->source('app/request_security.php');
        $membersLibraryCss = $this->source('assets/css/modules/members_library.css');
        $adminLibraryCss = $this->source('assets/css/modules/admin_library.css');

        self::assertStringContainsString('id="members-library-document-dialog"', $library);
        self::assertStringContainsString('<input type="text" name="proposal_category_name"', $library);
        self::assertStringNotContainsString('<input type="text" name="proposal_category"', $library);
        self::assertMatchesRegularExpression('/id="members-library-document-dialog".*<select name="proposal_category" required>.*name="proposal_tags"/s', $library);
        self::assertStringContainsString("\$documentProposalSelectedCategory = \$category !== '' ? \$category : 'general';", $library);
        self::assertStringContainsString('$visibleCategories = member_library_visible_categories($categories);', $library);
        self::assertStringContainsString('$visibleSubcategoriesByCategory = member_library_visible_subcategories_by_category($subcategoriesByCategory);', $library);
        self::assertStringContainsString('$favoriteDocumentIds = member_library_favorite_document_ids((int) ($user[\'id\'] ?? 0));', $library);
        self::assertStringContainsString('$proposalContactDefault = trim((string) ($user[\'email\'] ?? \'\'));', $library);
        self::assertStringContainsString('$proposalContactDefault = trim((string) ($user[\'callsign\'] ?? \'\'));', $library);
        self::assertStringContainsString('value="<?= e($proposalContactDefault) ?>" required', $library);
        self::assertStringContainsString('$favoritesOnly = (string) ($_GET[\'favorites\'] ?? \'\') === \'1\' && $favoriteDocumentCount > 0;', $library);
        self::assertStringContainsString('$favoritesLabel = member_library_favorites_label($t, $locale);', $library);
        self::assertStringContainsString("['favorites' => '1', 'q' => \$search, 'tag' => \$tag]", $library);
        self::assertStringContainsString("id IN (' . implode(',', array_fill(0, \$favoriteDocumentCount, '?')) . ')", $library);
        self::assertStringContainsString('foreach ($visibleCategories as $cat)', $library);
        self::assertStringContainsString('foreach ($visibleSubcategoriesByCategory[$catName] as $subcatInfo)', $library);
        self::assertStringContainsString("\$proposalTags = content_proposal_clean_single_line", $library);
        self::assertStringContainsString("(string) \$t['tags'] => \$proposalTags", $library);
        self::assertStringContainsString('accept=".pdf,.doc,.docx,.txt,.md,.html,.htm,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,text/markdown,text/html"', $library);
        self::assertStringContainsString('member_library_sync_accepted_proposals($t);', $library);
        self::assertStringContainsString('member_library_create_document_record(', $library);
        self::assertStringContainsString("'member_library_helpers.php' => ['members_library', 'my_requests', 'search'", $routeHelperLoader);
        self::assertStringContainsString("'member_module_documents.php' => ['my_requests', 'presentations'", $routeHelperLoader);
        self::assertStringContainsString('member_library_sync_accepted_proposals(i18n_domain_locale(\'members_library\', $locale));', $myRequests);
        self::assertStringContainsString('storage/(?:private|uploads)/(?:library|member_modules)', $myRequests);
        self::assertStringContainsString('SELECT id, title, description, category, subcategory, tags, file_path, uploaded_at FROM member_library_documents WHERE member_id = ?', $myRequests);
        self::assertStringContainsString('SELECT id, module_code, title, description, category, subcategory, tags, file_path, uploaded_at FROM member_module_documents WHERE member_id = ?', $myRequests);
        self::assertStringContainsString('$directDocumentSourceRefs[$sourceKey] = true;', $myRequests);
        self::assertStringContainsString("function_exists('ensure_member_library_table') && ensure_member_library_table()", $searchPage);
        self::assertStringContainsString('SELECT title, description, extracted_text, category, subcategory, tags FROM member_library_documents', $searchPage);
        self::assertStringContainsString("if (\$action === 'update_document' || \$action === 'delete_document')", $library);
        self::assertStringContainsString("!\$canManageLibrary && (int) (\$document['member_id'] ?? 0) !== (int) (\$user['id'] ?? 0)", $library);
        self::assertStringContainsString("'Action' => 'update_document'", $library);
        self::assertStringContainsString("'Action' => 'delete_document'", $library);
        self::assertStringContainsString("content_proposal_create((int) \$user['id'], 'members_library', 'content', \$title, \$proposalSummary", $library);
        self::assertStringContainsString("member_library_update_document_record(\$documentId, \$title, \$documentCategory, \$documentTags, \$description, \$sourceRef, \$documentSubcategory);", $library);
        self::assertStringContainsString('redirect(\'my_requests\');', $library);
        self::assertStringContainsString('data-members-library-modal-open="<?= e($editDialogId) ?>"', $library);
        self::assertStringContainsString('name="document_file"', $library);
        self::assertStringContainsString('member_library_delete_document_record($documentId);', $library);
        self::assertStringContainsString("route_url('member_library_preview', ['id' => \$docId]) . '#view=Fit'", $library);
        self::assertStringContainsString("\$docDownloadUrl = \$docId > 0 ? route_url('member_library_preview', ['id' => \$docId, 'download' => '1']) : '';", $library);
        self::assertStringContainsString("<?= e((string) \$t['open']) ?>", $library);
        self::assertStringNotContainsString('href="<?= e(base_url($safePath)) ?>"', $library);
        self::assertStringContainsString('class="members-library-pdf-preview"', $library);
        self::assertStringContainsString("\$documentPreviewUrl = \$documentId > 0 ? route_url('member_library_preview', ['id' => \$documentId]) . '#view=Fit' : '';", $adminLibrary);
        self::assertStringContainsString("\$documentDownloadUrl = \$documentId > 0 ? route_url('member_library_preview', ['id' => \$documentId, 'download' => '1']) : '';", $adminLibrary);
        self::assertStringContainsString('accept=".pdf,.doc,.docx,.txt,.md,.html,.htm,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,text/html,text/markdown"', $adminLibrary);
        self::assertStringNotContainsString('iframe src="<?= e(base_url($safePath)) ?>" class="admin-library-pdf-preview"', $adminLibrary);
        self::assertStringNotContainsString('href="<?= e(base_url($safePath)) ?>" target="_blank" rel="noopener"><?= e((string) $t[\'open\']) ?></a>', $adminLibrary);
        self::assertStringContainsString('grid-column: 1 / -1;', $membersLibraryCss);
        self::assertStringContainsString('height: min(140vh, 1320px);', $membersLibraryCss);
        self::assertStringContainsString('min-height: min(1120px, 140vh);', $membersLibraryCss);
        self::assertStringContainsString('.members-library-delete-form', $membersLibraryCss);
        self::assertStringContainsString('height: min(140vh, 1320px);', $adminLibraryCss);
        self::assertStringContainsString('member_library_apply_accepted_proposal($proposal, $memberLibraryMessages);', $adminLibrary);
        self::assertStringContainsString('member_library_sync_accepted_proposals($memberLibraryMessages);', $adminLibrary);
        self::assertStringContainsString("throw new RuntimeException('err_category_has_subcategories');", $adminLibrary);
        self::assertStringContainsString("throw new RuntimeException('err_subcategory_has_documents');", $adminLibrary);
        self::assertStringContainsString('function admin_apply_accepted_content_proposal(array $proposal, string $locale): void', $adminHelpers);
        self::assertStringContainsString("require_once __DIR__ . '/article_import_helpers.php';", $adminHelpers);
        self::assertStringContainsString("i18n_domain_locale('members_library', \$locale)", $adminHelpers);
        self::assertStringContainsString('member_library_apply_accepted_proposal(', $adminHelpers);
        self::assertStringContainsString("require_once __DIR__ . '/album_helpers.php';", $adminHelpers);
        self::assertStringContainsString('album_apply_accepted_proposal($proposal);', $adminHelpers);
        self::assertStringContainsString("require_once __DIR__ . '/member_webotheque.php';", $adminHelpers);
        self::assertStringContainsString('webotheque_apply_accepted_proposal($proposal, $categories, $messages);', $adminHelpers);
        self::assertStringContainsString('function member_library_apply_accepted_proposal(', $memberLibraryHelpers);
        self::assertStringContainsString('function member_library_create_document_record(', $memberLibraryHelpers);
        self::assertStringContainsString('function member_library_store_document_upload(?array $file, int $memberId, string $prefix = \'doc\'): array', $memberLibraryHelpers);
        self::assertSame(120 * 1024 * 1024, member_library_upload_max_bytes());
        self::assertStringContainsString('ALTER TABLE member_library_documents ADD COLUMN extracted_text LONGTEXT NULL AFTER file_path', $memberLibraryHelpers);
        self::assertStringContainsString('function member_library_delete_document_file(string $publicPath): void', $memberLibraryHelpers);
        self::assertStringContainsString('function member_library_update_document_record(', $memberLibraryHelpers);
        self::assertStringContainsString('function member_library_delete_document_record(int $documentId): void', $memberLibraryHelpers);
        self::assertStringContainsString('member_library_document_proposal_action($summary)', $memberLibraryHelpers);
        self::assertStringContainsString("if (member_library_document_proposal_action((string) (\$proposal['summary'] ?? '')) !== '')", $memberLibraryHelpers);
        self::assertStringContainsString('function member_library_sync_accepted_proposals(array $messages = [], int $limit = 100): array', $memberLibraryHelpers);
        self::assertStringContainsString('member_library_accepted_proposal_sync_failed', $memberLibraryHelpers);

        $codes = array_map(static fn(array $category): string => (string) $category['code'], member_library_default_categories());
        self::assertContains('general', $codes);
        self::assertContains('formation', $codes);
        self::assertContains('technique', $codes);
        self::assertNotContains('medias', $codes);
        self::assertSame(['Formation', 'technique'], library_filter_controlled_tags(['Formation', 'unknown', 'technique']));
        self::assertSame('storage/uploads/library/manual.pdf', member_library_proposal_source_path('content_proposals#12 https://on4crd.test/storage/uploads/library/manual.pdf?download=1'));
        self::assertSame('formation', member_library_proposal_category_from_summary("Category: formation\nTags: technique"));
        self::assertSame('technique,antenne', member_library_proposal_tags_from_summary('Tags: technique,unknown,antenne'));
        self::assertStringContainsString('member_library_default_categories()', $contentHelpers);
        self::assertStringContainsString("content_proposal_accepted_categories('members_library'", $contentHelpers);
        self::assertStringContainsString('function wiki_subcategory_ref(', $contentHelpers);
        self::assertStringContainsString('function wiki_visible_categories(', $contentHelpers);
        self::assertStringContainsString('function wiki_favorite_page_ids(', $contentHelpers);
        self::assertStringContainsString('idx_wiki_category_deleted', $contentHelpers);
        self::assertStringContainsString('deleted_at IS NULL', $contentHelpers);
        self::assertStringContainsString('wiki_ensure_subcategories_table();', $contentHelpers);
        self::assertStringContainsString('subcategory VARCHAR(120) NOT NULL DEFAULT ""', $contentHelpers);
        self::assertStringContainsString("'qsl', 'qsl_preview', 'qsl_export', 'members_library', 'admin_library', 'member_library_preview'", $routeHelperLoader);
        self::assertStringContainsString("'member_library_preview'", $routeHelperLoader);
        self::assertStringContainsString("'fichiers', 'members_library', 'member_library_preview', 'member_document_preview', 'admin_articles'", $routeHelperLoader);
        self::assertStringContainsString("'wiki', 'wiki_edit', 'wiki_propose', 'wiki_view', 'admin_wiki'", $routeHelperLoader);
        self::assertStringContainsString("\$isDownload = (string) (\$_GET['download'] ?? '') === '1';", $previewPage);
        self::assertStringContainsString("\$disposition = \$isDownload ? 'attachment' : 'inline';", $previewPage);
        self::assertStringContainsString("'doc' => 'application/msword'", $previewPage);
        self::assertStringContainsString("header('Content-Disposition: ' . \$disposition", $previewPage);
        self::assertStringContainsString("if (in_array(security_header_current_route(), ['member_library_preview', 'member_document_preview'], true))", $requestSecurity);
        self::assertStringContainsString("\$frameAncestors = \"'self'\";", $requestSecurity);
        self::assertStringContainsString("\$xFrameOptions = 'SAMEORIGIN';", $requestSecurity);
    }

    public function testSharedDocumentModulesAreDeclaredDispatchedAndTagged(): void
    {
        $expectedModules = [
            'presentations' => ['route' => 'presentations', 'admin_route' => 'admin_presentations'],
            'videos' => ['route' => 'videos', 'admin_route' => 'admin_videos'],
            'fichiers' => ['route' => 'fichiers', 'admin_route' => 'admin_fichiers'],
            'pv' => ['route' => 'pv', 'admin_route' => 'admin_pv'],
        ];
        $definitions = member_document_module_definitions();

        foreach ($expectedModules as $module => $expected) {
            self::assertArrayHasKey($module, $definitions);
            self::assertSame($expected['route'], $definitions[$module]['route'] ?? null);
            self::assertSame($expected['admin_route'], $definitions[$module]['admin_route'] ?? null);
            self::assertNotSame([], $definitions[$module]['legacy_categories'] ?? []);
            self::assertStringContainsString("render_member_document_module_page('" . $module . "')", $this->source('pages/' . $expected['route'] . '.php'));
            self::assertStringContainsString("render_admin_member_document_module_page('" . $module . "')", $this->source('pages/' . $expected['admin_route'] . '.php'));
        }

        self::assertSame('fichiers', member_document_module_normalize('telechargements'));
        self::assertNull(member_document_module_definition('unknown'));
        self::assertSame('storage/private/member_modules/videos/demo.pdf', member_document_safe_path('/storage/private/member_modules/videos/demo.pdf'));
        self::assertSame('storage/uploads/member_modules/videos/demo.pdf', member_document_safe_path('/storage/uploads/member_modules/videos/demo.pdf'));
        self::assertSame('storage/uploads/library/legacy.pdf', member_document_safe_path('storage/uploads/library/legacy.pdf'));
        self::assertNull(member_document_safe_path('storage/uploads/member_modules/../secret.pdf'));

        $fileLabels = member_document_module_labels('fichiers', 'fr');
        self::assertSame('Mes fichiers', $fileLabels['view_content']);
        self::assertSame('Mes partages', $fileLabels['administration']);
        self::assertSame('Mes partages', $fileLabels['admin_title_prefix']);

        $presentationLabels = member_document_module_labels('presentations', 'fr');
        self::assertSame('Voir les contenus', $presentationLabels['view_content']);
        self::assertSame('Proposer', $presentationLabels['propose_menu']);
        self::assertSame('Proposer une présentation', $presentationLabels['propose_content']);
        self::assertSame('Une présentation', $presentationLabels['propose_presentation_item']);
        self::assertSame('Administrer', $presentationLabels['administration']);

        $pvDefinition = member_document_module_definition('pv');
        self::assertSame(['formats'], $pvDefinition['hidden_stats'] ?? null);
        self::assertTrue((bool) ($pvDefinition['latest_document_cta'] ?? false));
        $pvLabels = member_document_module_labels('pv', 'fr');
        self::assertSame('Consulter le dernier PV', $pvLabels['view_content']);
        $pvStats = render_member_document_module_stats(['total' => 2, 'formats' => 1, 'latest' => '2026-06-14'], $pvLabels, '14/06/2026', ['formats']);
        self::assertStringNotContainsString('Formats', $pvStats);
        self::assertStringContainsString('Dernier ajout', $pvStats);

        $renderer = $this->source('app/member_module_documents.php');
        self::assertStringContainsString('idx_module_tags', $renderer);
        self::assertStringContainsString('idx_module_subcategory', $renderer);
        self::assertStringContainsString('function member_document_subcategory_ref(', $renderer);
        self::assertStringContainsString('function member_document_visible_categories(', $renderer);
        self::assertStringContainsString('function member_document_favorite_document_ids(', $renderer);
        self::assertStringContainsString("\$category = trim(\$category) !== '' ? member_document_category_code(\$category) : '';", $renderer);
        self::assertStringContainsString("\$subcategory = trim(\$subcategory) !== '' ? member_document_subcategory_code(\$subcategory) : '';", $renderer);
        self::assertStringContainsString('idx_member_module_category_deleted', $renderer);
        self::assertStringContainsString('UPDATE member_module_documents SET category = "general", subcategory = "" WHERE module_code = ? AND category = ?', $renderer);
        self::assertStringContainsString("if (\$action === 'toggle_favorite_document')", $renderer);
        self::assertStringContainsString("if (\$action === 'add_subcategory')", $renderer);
        self::assertStringContainsString("if (\$action === 'delete_subcategory')", $renderer);
        self::assertStringContainsString('render_member_document_taxonomy_fields($moduleCode, $categories, $labels', $renderer);
        self::assertStringContainsString('name="tags"', $renderer);
        self::assertStringContainsString('function render_member_document_module_stats(', $renderer);
        self::assertSame(2, substr_count($renderer, 'render_member_document_module_stats($stats, $labels, $latestLabel, $hiddenStats)'));
        self::assertStringContainsString('member_document_current_user_is_administrator()', $renderer);
        self::assertStringContainsString('function member_document_module_allows_member_management(', $renderer);
        self::assertStringContainsString('function member_document_apply_accepted_proposal(array $proposal, string $moduleCode): ?int', $renderer);
        self::assertStringContainsString('function member_document_upsert_category(', $renderer);
        self::assertStringContainsString('function member_document_upsert_subcategory(', $renderer);
        self::assertStringContainsString("member_document_module_allows_member_management(\$moduleCode)", $renderer);
        self::assertStringContainsString("\$canProposeTaxonomy = \$moduleCode === 'presentations';", $renderer);
        self::assertStringContainsString("if (\$action === 'propose_category' && \$canProposeTaxonomy)", $renderer);
        self::assertStringContainsString("if (\$action === 'propose_subcategory' && \$canProposeTaxonomy)", $renderer);
        self::assertStringContainsString("content_proposal_create((int) \$user['id'], \$moduleCode, 'category'", $renderer);
        self::assertStringContainsString("content_proposal_create((int) \$user['id'], \$moduleCode, 'subcategory'", $renderer);
        self::assertStringContainsString("content_proposal_create((int) \$user['id'], \$moduleCode, 'content', \$titleInput, \$proposalSummary", $renderer);
        self::assertStringContainsString("if (\$proposalType === 'category')", $renderer);
        self::assertStringContainsString("if (\$proposalType === 'subcategory')", $renderer);
        self::assertStringContainsString('member-document-propose-menu', $renderer);
        self::assertStringContainsString('member-document-category-dialog', $renderer);
        self::assertStringContainsString('member-document-subcategory-dialog', $renderer);
        self::assertStringContainsString('data-member-document-modal-open', $renderer);
        self::assertStringContainsString('function member_document_upload_max_bytes(', $renderer);
        self::assertStringContainsString('1024 * 1024 * 1024', $renderer);
        self::assertSame(120 * 1024 * 1024, member_document_upload_max_bytes('presentations', 'pdf'));
        self::assertSame(120 * 1024 * 1024, member_document_upload_max_bytes('videos', 'pdf'));
        self::assertSame(1024 * 1024 * 1024, member_document_upload_max_bytes('videos', 'mp4'));
        self::assertStringContainsString('member-document-video-player', $renderer);

        $adminHelpers = $this->source('app/admin_helpers.php');
        $contentHelpers = $this->source('app/content_helpers.php');
        self::assertStringContainsString("'presentations' => ['route' => 'admin_presentations'", $adminHelpers);
        self::assertStringContainsString("'videos' => ['route' => 'admin_videos'", $adminHelpers);
        self::assertStringContainsString("member_document_apply_accepted_proposal(\$proposal, \$area);", $adminHelpers);
        self::assertStringContainsString("'presentations' => true", $contentHelpers);
        self::assertStringContainsString("'videos' => true", $contentHelpers);
    }

    public function testIdeaModuleUsesTopicSelectKeywordsAndSubmitsThem(): void
    {
        $layout = $this->source('app/layout_renderer.php');
        self::assertStringContainsString('id="idea-dialog"', $layout);
        self::assertStringContainsString('name="idea_category"', $layout);
        self::assertStringContainsString('name="idea_keywords"', $layout);
        self::assertMatchesRegularExpression('/<select id="idea-category" name="idea_category">.*\$ideaCategoryOptionHtml/s', $layout);

        $submit = $this->source('pages/idea_submit.php');
        self::assertStringContainsString("\$category = \$cleanLine((string) (\$_POST['idea_category'] ?? 'general'), 80);", $submit);
        self::assertStringContainsString("\$keywords = \$cleanLine((string) (\$_POST['idea_keywords'] ?? ''), 255);", $submit);
        self::assertStringContainsString("\$ideaCategoryTranslationKeys = [", $submit);
        self::assertStringContainsString("\$categoryLabel = \$t(\$ideaCategoryTranslationKeys[\$category]);", $submit);
        self::assertStringContainsString("\$t('category_label') . ': ' . \$categoryLabel", $submit);
        self::assertStringContainsString("\$t('keywords_label') . ': ' . \$keywords", $submit);
    }
}
