<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MemberModuleRegressionContractsTest extends TestCase
{
    private function source(string $relativePath): string
    {
        $source = file_get_contents(dirname(__DIR__) . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        self::assertIsString($source);

        return $source;
    }

    public function testAdminProposalAcceptanceDoesNotReviveDeletedWebothequeDefaultCategories(): void
    {
        $adminHelpers = $this->source('app/admin_helpers.php');

        self::assertStringContainsString('$categories = webotheque_categories($messages);', $adminHelpers);
        self::assertStringNotContainsString(
            '$categories = webotheque_default_categories($messages) + webotheque_categories($messages);',
            $adminHelpers
        );
    }

    public function testFavoritesAreRenderedBeforeRegularTaxonomyItems(): void
    {
        $this->assertAppearsBefore(
            $this->source('pages/members_library.php'),
            '<?php if ($favoriteDocumentCount > 0): ?>',
            '<?php foreach ($visibleCategories as $cat): ?>'
        );
        $this->assertAppearsBefore(
            $this->source('app/member_webotheque.php'),
            '<?php if ($favoriteLinkCount > 0): ?>',
            '<?php foreach ($visibleCategories as $code => $label): ?>'
        );
        $this->assertAppearsBefore(
            $this->source('pages/albums.php'),
            '<?php if ($favoriteAlbumCount > 0): ?>',
            '<?php foreach ($visibleAlbumCategories as $categoryCode => $categoryLabel): ?>'
        );
        $this->assertAppearsBefore(
            $this->source('pages/wiki.php'),
            '<?php if ($favoriteWikiPageCount > 0): ?>',
            '<?php foreach ($visibleWikiThemes as $themeCode => $themeLabel): ?>'
        );
        $this->assertAppearsBefore(
            $this->source('pages/articles.php'),
            '<?php if ($favoriteArticleCount > 0): ?>',
            '<?php foreach ($visibleArticleCategories as $themeCode => $themeLabel): ?>'
        );
        $this->assertAppearsBefore(
            $this->source('app/member_module_documents.php'),
            '<?php if ($favoriteDocumentCount > 0): ?>',
            '<?php foreach ($visibleCategories as $categoryCode => $categoryLabel): ?>'
        );
    }

    public function testAdminDeletionRulesProtectNonEmptyTaxonomyBranches(): void
    {
        $contracts = [
            'app/member_webotheque.php' => [
                'SELECT COUNT(*) FROM member_webotheque_subcategories WHERE category_code = ?',
                "throw new RuntimeException('err_category_has_subcategories');",
                'UPDATE member_webotheque_links SET category = "general", subcategory = "", subsubcategory = "" WHERE category = ?',
                'SELECT COUNT(*) FROM member_webotheque_subsubcategories WHERE category_code = ? AND subcategory_code = ?',
                "throw new RuntimeException('err_subcategory_has_subsubcategories');",
                'SELECT COUNT(*) FROM member_webotheque_links WHERE category = ? AND subcategory = ?',
                "throw new RuntimeException('err_subcategory_has_documents');",
                'SELECT COUNT(*) FROM member_webotheque_links WHERE category = ? AND subcategory = ? AND subsubcategory = ?',
                "throw new RuntimeException('err_subsubcategory_has_documents');",
            ],
            'app/member_module_documents.php' => [
                'SELECT COUNT(*) FROM member_module_subcategories WHERE module_code = ? AND category_code = ? AND deleted_at IS NULL',
                "throw new RuntimeException('err_category_has_subcategories');",
                'UPDATE member_module_documents SET category = "general", subcategory = "" WHERE module_code = ? AND category = ?',
                'SELECT COUNT(*) FROM member_module_documents WHERE module_code = ? AND category = ? AND subcategory = ?',
                "throw new RuntimeException('err_subcategory_has_documents');",
                'UPDATE member_module_subcategories SET deleted_at = NOW() WHERE module_code = ? AND category_code = ? AND code = ?',
            ],
            'pages/admin_albums.php' => [
                'SELECT COUNT(*) FROM album_subcategories WHERE category_code = ?',
                'err_category_has_subcategories',
                'UPDATE albums SET category = "general", subcategory = "" WHERE category = ?',
                'SELECT COUNT(*) FROM albums WHERE category = ? AND subcategory = ?',
                'err_subcategory_has_documents',
                'DELETE FROM album_subcategories WHERE category_code = ? AND code = ?',
            ],
            'pages/admin_wiki.php' => [
                'SELECT COUNT(*) FROM wiki_subcategories WHERE category_code = ?',
                'err_category_has_subcategories',
                'UPDATE wiki_pages SET category = "general", subcategory = "", subsubcategory = "" WHERE category = ?',
                'SELECT COUNT(*) FROM wiki_subsubcategories WHERE category_code = ? AND subcategory_code = ?',
                'err_subcategory_has_subsubcategories',
                'SELECT COUNT(*) FROM wiki_pages WHERE category = ? AND subcategory = ?',
                'err_subcategory_has_documents',
                'DELETE FROM wiki_subcategories WHERE category_code = ? AND code = ?',
                'SELECT COUNT(*) FROM wiki_pages WHERE category = ? AND subcategory = ? AND subsubcategory = ?',
                'err_subsubcategory_has_documents',
                'DELETE FROM wiki_subsubcategories WHERE category_code = ? AND subcategory_code = ? AND code = ?',
            ],
            'pages/admin_articles.php' => [
                'SELECT COUNT(*) FROM article_subcategories WHERE category_code = ?',
                'err_category_has_subcategories',
                'UPDATE articles SET category = "autres", subcategory = "", subsubcategory = "" WHERE category = ?',
                'SELECT COUNT(*) FROM article_subsubcategories WHERE category_code = ? AND subcategory_code = ?',
                'err_subcategory_has_subsubcategories',
                'SELECT COUNT(*) FROM articles WHERE category = ? AND subcategory = ?',
                'err_subcategory_has_documents',
                'DELETE FROM article_subcategories WHERE category_code = ? AND code = ?',
                'SELECT COUNT(*) FROM articles WHERE category = ? AND subcategory = ? AND subsubcategory = ?',
                'err_subsubcategory_has_documents',
                'DELETE FROM article_subsubcategories WHERE category_code = ? AND subcategory_code = ? AND code = ?',
            ],
            'pages/admin_library.php' => [
                'SELECT COUNT(*) FROM member_library_subcategories WHERE category_code = ?',
                "throw new RuntimeException('err_category_has_subcategories');",
                'UPDATE member_library_documents SET category = "general", subcategory = "", subsubcategory = "" WHERE category = ?',
                'SELECT COUNT(*) FROM member_library_documents WHERE category = ? AND subcategory = ?',
                "throw new RuntimeException('err_subcategory_has_documents');",
            ],
        ];

        foreach ($contracts as $path => $expectedSnippets) {
            $source = $this->source($path);
            foreach ($expectedSnippets as $snippet) {
                self::assertStringContainsString($snippet, $source, $path . ' must keep: ' . $snippet);
            }
        }
    }

    public function testAdminArticlesOptionalTaxonomyCodesFallbackToLabels(): void
    {
        $source = $this->source('pages/admin_articles.php');

        self::assertStringContainsString('$codeInput = trim((string) ($_POST[\'category_code\'] ?? \'\'));', $source);
        self::assertStringContainsString('$code = article_category_code($codeInput !== \'\' ? $codeInput : $label);', $source);
        self::assertStringContainsString('$codeInput = trim((string) ($_POST[\'subcategory_code\'] ?? \'\'));', $source);
        self::assertStringContainsString('$code = article_subcategory_code($codeInput !== \'\' ? $codeInput : $label);', $source);
        self::assertStringContainsString('$codeInput = trim((string) ($_POST[\'subsubcategory_code\'] ?? \'\'));', $source);
        self::assertStringContainsString('$code = article_subsubcategory_code($codeInput !== \'\' ? $codeInput : $label);', $source);
        self::assertStringNotContainsString('article_category_code((string) ($_POST[\'category_code\'] ?? $label))', $source);
        self::assertStringNotContainsString('article_subcategory_code((string) ($_POST[\'subcategory_code\'] ?? $label))', $source);
        self::assertStringNotContainsString('article_subsubcategory_code((string) ($_POST[\'subsubcategory_code\'] ?? $label))', $source);
    }

    public function testSchemaKeepsTaxonomyColumnsAndDeletionTombstones(): void
    {
        $schema = $this->source('schema/schema.sql');

        foreach ([
            'CREATE TABLE IF NOT EXISTS wiki_categories',
            'CREATE TABLE IF NOT EXISTS wiki_subcategories',
            'CREATE TABLE IF NOT EXISTS wiki_subsubcategories',
            'CREATE TABLE IF NOT EXISTS member_webotheque_categories',
            'CREATE TABLE IF NOT EXISTS member_webotheque_subcategories',
            'CREATE TABLE IF NOT EXISTS member_webotheque_subsubcategories',
            'CREATE TABLE IF NOT EXISTS member_module_categories',
            'CREATE TABLE IF NOT EXISTS member_module_subcategories',
            'CREATE TABLE IF NOT EXISTS album_categories',
            'CREATE TABLE IF NOT EXISTS album_subcategories',
            'CREATE TABLE IF NOT EXISTS article_categories',
            'CREATE TABLE IF NOT EXISTS article_subcategories',
            'CREATE TABLE IF NOT EXISTS article_subsubcategories',
            'subcategory VARCHAR(120) NOT NULL DEFAULT \'\'',
            'subsubcategory VARCHAR(120) NOT NULL DEFAULT \'\'',
            'idx_wiki_category_deleted',
            'idx_wiki_subsubcategory_parent',
            'idx_webotheque_category_deleted',
            'idx_webotheque_subsubcategory_parent',
            'idx_member_module_category_deleted',
            'idx_member_module_subcategory_deleted',
            'idx_album_category_deleted',
            'idx_article_category_deleted',
            'idx_articles_subcategory',
            'idx_article_subsubcategory_parent',
        ] as $snippet) {
            self::assertStringContainsString($snippet, $schema);
        }
    }

    public function testRouteHelperLoaderKeepsFavoritesHelpersAvailableOnAdaptedModules(): void
    {
        $this->assertRouteHelperIncludes('member_favorites.php', [
            'dashboard',
            'members_library',
            'webotheque',
            'presentations',
            'wiki',
            'wiki_view',
            'articles',
            'article',
            'albums',
            'album',
            'classifieds',
        ]);
        $this->assertRouteHelperIncludes('member_module_documents.php', [
            'my_requests',
            'presentations',
            'videos',
            'pv',
            'fichiers',
            'telechargements',
            'admin_presentations',
            'admin_videos',
            'admin_pv',
            'admin_fichiers',
            'admin_telechargements',
        ]);
    }

    public function testPvAndFilesModulesKeepAdminUploadMemberReadAndAliasContracts(): void
    {
        $renderer = $this->source('app/member_module_documents.php');
        $preview = $this->source('pages/member_document_preview.php');

        foreach ([
            'pv' => ['route' => 'pv', 'admin_route' => 'admin_pv'],
            'fichiers' => ['route' => 'fichiers', 'admin_route' => 'admin_fichiers'],
        ] as $module => $routes) {
            self::assertStringContainsString("render_member_document_module_page('" . $module . "')", $this->source('pages/' . $routes['route'] . '.php'));
            self::assertStringContainsString("render_admin_member_document_module_page('" . $module . "')", $this->source('pages/' . $routes['admin_route'] . '.php'));
        }

        self::assertStringContainsString("redirect('fichiers');", $this->source('pages/telechargements.php'));
        self::assertStringContainsString("redirect('admin_fichiers');", $this->source('pages/admin_telechargements.php'));
        self::assertStringContainsString("return \$module === 'telechargements' ? 'fichiers' : \$module;", $renderer);
        self::assertStringContainsString("'fichiers' => [", $renderer);
        self::assertStringContainsString("'pv' => [", $renderer);
        self::assertStringContainsString("'latest_document_cta' => true", $renderer);
        self::assertStringContainsString('<input type="hidden" name="action" value="upload">', $renderer);
        self::assertStringContainsString('<input type="hidden" name="action" value="delete_document">', $renderer);
        self::assertStringContainsString("route_url('member_document_preview', ['module' => \$moduleCode, 'id' => \$documentId, 'download' => '1'])", $renderer);
        self::assertStringContainsString("'txt' => 'text/plain; charset=utf-8'", $preview);
        self::assertStringContainsString("\$disposition = \$isDownload ? 'attachment' : 'inline';", $preview);
    }

    private function assertAppearsBefore(string $source, string $first, string $second): void
    {
        $firstPosition = strpos($source, $first);
        $secondPosition = strpos($source, $second);
        if ($firstPosition === false || $secondPosition === false) {
            self::fail('Expected snippets were not both present.');
        }

        self::assertLessThan($secondPosition, $firstPosition);
    }

    /**
     * @param list<string> $expectedRoutes
     */
    private function assertRouteHelperIncludes(string $helper, array $expectedRoutes): void
    {
        self::assertTrue(function_exists('app_route_helper_map'));
        $helperRoutes = app_route_helper_map();

        self::assertArrayHasKey($helper, $helperRoutes);
        foreach ($expectedRoutes as $route) {
            self::assertContains($route, $helperRoutes[$helper], sprintf('%s must be loaded for %s.', $helper, $route));
        }
    }
}
