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

        $albums = $this->source('pages/albums.php');
        $albumHelpers = $this->source('app/album_helpers.php');
        $schema = $this->source('schema/schema.sql');
        self::assertStringContainsString('name="proposal_theme"', $albums);
        self::assertMatchesRegularExpression('/<select\s+name="proposal_theme"[^>]*>.*\$albumThemeOptions/s', $albums);
        self::assertStringContainsString('name="proposal_keywords"', $albums);
        self::assertStringContainsString("'Mots cles' => \$keywords", $albums);
        self::assertStringContainsString('album_sync_accepted_proposals();', $albums);
        self::assertStringContainsString('album_clear_caches();', $albums);
        self::assertStringContainsString("if (\$action === 'update_album' || \$action === 'delete_album')", $albums);
        self::assertStringContainsString("!\$canManageAlbums && (int) (\$album['member_id'] ?? 0) !== (int) (\$user['id'] ?? 0)", $albums);
        self::assertStringContainsString("'Action' => 'update_album'", $albums);
        self::assertStringContainsString("'Action' => 'delete_album'", $albums);
        self::assertStringContainsString("content_proposal_create((int) \$user['id'], 'albums', 'content', \$title, \$summary", $albums);
        self::assertStringContainsString('data-album-modal-open="<?= e($editDialogId) ?>"', $albums);

        $adminAlbums = $this->source('pages/admin_albums.php');
        self::assertMatchesRegularExpression('/<select\s+name="album_id"\s+required>.*foreach \(\$albums as \$album\)/s', $adminAlbums);
        self::assertStringContainsString('album_sync_accepted_proposals();', $adminAlbums);
        self::assertStringContainsString('album_delete_record($albumId);', $adminAlbums);
        self::assertStringContainsString('function album_apply_accepted_proposal(array $proposal): ?int', $albumHelpers);
        self::assertStringContainsString('function album_sync_accepted_proposals(int $limit = 100): array', $albumHelpers);
        self::assertStringContainsString('function album_proposal_action(string $summary): string', $albumHelpers);
        self::assertStringContainsString('function album_update_record(', $albumHelpers);
        self::assertStringContainsString('function album_delete_record(int $albumId): void', $albumHelpers);
        self::assertStringContainsString('member_id', $albumHelpers);
        self::assertStringContainsString('source_proposal_id', $albumHelpers);
        self::assertStringContainsString('idx_albums_member', $schema);
        self::assertStringContainsString('idx_albums_source_proposal', $schema);
        self::assertSame(
            "Club fieldday\n\nThematique: radio\nMots cles: ft8",
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
        self::assertStringContainsString('name="tags"', $webotheque);
        self::assertStringContainsString('render_webotheque_link_fields($t, $categories, $proposalContact)', $webotheque);
        self::assertStringContainsString('render_webotheque_link_fields($t, $categories)', $webotheque);
        self::assertStringContainsString('function webotheque_apply_accepted_proposal(', $webotheque);
        self::assertStringContainsString('function webotheque_sync_accepted_proposals(', $webotheque);
        self::assertStringContainsString('webotheque_sync_accepted_proposals($categories, $t,', $webotheque);
        self::assertStringContainsString('webotheque_accepted_proposal_sync_failed', $webotheque);
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
        $requestSecurity = $this->source('app/request_security.php');
        $membersLibraryCss = $this->source('assets/css/modules/members_library.css');
        $adminLibraryCss = $this->source('assets/css/modules/admin_library.css');

        self::assertStringContainsString('id="members-library-document-dialog"', $library);
        self::assertStringContainsString('<input type="text" name="proposal_category_name"', $library);
        self::assertStringNotContainsString('<input type="text" name="proposal_category"', $library);
        self::assertMatchesRegularExpression('/id="members-library-document-dialog".*<select name="proposal_category" required>.*name="proposal_tags"/s', $library);
        self::assertStringContainsString("\$documentProposalSelectedCategory = \$category !== '' ? \$category : 'general';", $library);
        self::assertStringContainsString("\$proposalTags = content_proposal_clean_single_line", $library);
        self::assertStringContainsString("(string) (\$t['tags'] ?? 'Keywords') => \$proposalTags", $library);
        self::assertStringContainsString('member_library_sync_accepted_proposals($t);', $library);
        self::assertStringContainsString('member_library_apply_accepted_proposal([', $library);
        self::assertStringContainsString("'member_library_helpers.php' => ['members_library', 'search'", $routeHelperLoader);
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
        self::assertStringNotContainsString("route_url('member_library_preview', ['id' => \$docId, 'download' => '1'])", $library);
        self::assertStringNotContainsString("\$t['open']", $library);
        self::assertStringNotContainsString('href="<?= e(base_url($safePath)) ?>"', $library);
        self::assertStringContainsString('class="members-library-pdf-preview"', $library);
        self::assertStringContainsString("\$documentPreviewUrl = \$documentId > 0 ? route_url('member_library_preview', ['id' => \$documentId]) . '#view=Fit' : '';", $adminLibrary);
        self::assertStringContainsString("\$documentDownloadUrl = \$documentId > 0 ? route_url('member_library_preview', ['id' => \$documentId, 'download' => '1']) : '';", $adminLibrary);
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
        self::assertStringContainsString('function member_library_store_document_upload(?array $file, int $memberId, string $prefix = \'doc\'): array', $memberLibraryHelpers);
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
        self::assertStringContainsString("'qsl', 'qsl_preview', 'qsl_export', 'members_library', 'admin_library', 'member_library_preview'", $routeHelperLoader);
        self::assertStringContainsString("'member_library_preview'", $routeHelperLoader);
        self::assertStringContainsString("'fichiers', 'members_library', 'member_library_preview', 'admin_articles'", $routeHelperLoader);
        self::assertStringContainsString("'wiki', 'wiki_edit', 'wiki_propose', 'wiki_view', 'admin_wiki'", $routeHelperLoader);
        self::assertStringContainsString("\$isDownload = (string) (\$_GET['download'] ?? '') === '1';", $previewPage);
        self::assertStringContainsString("\$disposition = \$isDownload ? 'attachment' : 'inline';", $previewPage);
        self::assertStringContainsString("header('Content-Disposition: ' . \$disposition", $previewPage);
        self::assertStringContainsString("if (in_array(security_header_current_route(), ['member_library_preview'], true))", $requestSecurity);
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
        self::assertSame('storage/uploads/member_modules/videos/demo.pdf', member_document_safe_path('/storage/uploads/member_modules/videos/demo.pdf'));
        self::assertSame('storage/uploads/library/legacy.pdf', member_document_safe_path('storage/uploads/library/legacy.pdf'));
        self::assertNull(member_document_safe_path('storage/uploads/member_modules/../secret.pdf'));

        $fileLabels = member_document_module_labels('fichiers', 'fr');
        self::assertSame('Mes fichiers', $fileLabels['view_content']);
        self::assertSame('Mes partages', $fileLabels['administration']);
        self::assertSame('Mes partages', $fileLabels['admin_title_prefix']);

        $presentationLabels = member_document_module_labels('presentations', 'fr');
        self::assertSame('Voir les contenus', $presentationLabels['view_content']);
        self::assertSame('Administration', $presentationLabels['administration']);

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
        self::assertStringContainsString('name="tags"', $renderer);
        self::assertStringContainsString('function render_member_document_module_stats(', $renderer);
        self::assertSame(2, substr_count($renderer, 'render_member_document_module_stats($stats, $labels, $latestLabel, $hiddenStats)'));
        self::assertStringContainsString('member_document_current_user_is_administrator()', $renderer);
        self::assertStringContainsString('function member_document_module_allows_member_management(', $renderer);
        self::assertStringContainsString('function member_document_apply_accepted_proposal(array $proposal, string $moduleCode): ?int', $renderer);
        self::assertStringContainsString("member_document_module_allows_member_management(\$moduleCode)", $renderer);
        self::assertStringContainsString("content_proposal_create((int) \$user['id'], \$moduleCode, 'content', \$titleInput, \$proposalSummary", $renderer);
        self::assertStringContainsString('data-member-document-modal-open', $renderer);

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
        self::assertStringContainsString("\$t('category_label', 'Topic') . ': ' . \$category", $submit);
        self::assertStringContainsString("\$t('keywords_label', 'Keywords') . ': ' . \$keywords", $submit);
    }
}
