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
                'UPDATE member_webotheque_links SET category = "general", subcategory = "" WHERE category = ?',
                'SELECT COUNT(*) FROM member_webotheque_links WHERE category = ? AND subcategory = ?',
                "throw new RuntimeException('err_subcategory_has_documents');",
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
                'UPDATE wiki_pages SET category = "general", subcategory = "" WHERE category = ?',
                'SELECT COUNT(*) FROM wiki_pages WHERE category = ? AND subcategory = ?',
                'err_subcategory_has_documents',
                'DELETE FROM wiki_subcategories WHERE category_code = ? AND code = ?',
            ],
            'pages/admin_library.php' => [
                'SELECT COUNT(*) FROM member_library_subcategories WHERE category_code = ?',
                "throw new RuntimeException('err_category_has_subcategories');",
                'UPDATE member_library_documents SET category = "general", subcategory = "" WHERE category = ?',
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

    public function testSchemaKeepsTaxonomyColumnsAndDeletionTombstones(): void
    {
        $schema = $this->source('schema/schema.sql');

        foreach ([
            'CREATE TABLE IF NOT EXISTS wiki_categories',
            'CREATE TABLE IF NOT EXISTS wiki_subcategories',
            'CREATE TABLE IF NOT EXISTS member_webotheque_categories',
            'CREATE TABLE IF NOT EXISTS member_webotheque_subcategories',
            'CREATE TABLE IF NOT EXISTS member_module_categories',
            'CREATE TABLE IF NOT EXISTS member_module_subcategories',
            'CREATE TABLE IF NOT EXISTS album_categories',
            'CREATE TABLE IF NOT EXISTS album_subcategories',
            'subcategory VARCHAR(120) NOT NULL DEFAULT \'\'',
            'idx_wiki_category_deleted',
            'idx_webotheque_category_deleted',
            'idx_member_module_category_deleted',
            'idx_member_module_subcategory_deleted',
            'idx_album_category_deleted',
        ] as $snippet) {
            self::assertStringContainsString($snippet, $schema);
        }
    }

    public function testRouteHelperLoaderKeepsFavoritesHelpersAvailableOnAdaptedModules(): void
    {
        $loader = $this->source('app/route_helper_loader.php');

        self::assertStringContainsString(
            "'member_favorites.php' => ['dashboard', 'members_library', 'webotheque', 'presentations', 'wiki', 'wiki_view', 'articles', 'article', 'albums', 'album', 'classifieds']",
            $loader
        );
        self::assertStringContainsString(
            "'member_module_documents.php' => ['presentations', 'videos', 'pv', 'fichiers', 'telechargements', 'admin_presentations', 'admin_videos', 'admin_pv', 'admin_fichiers', 'admin_telechargements']",
            $loader
        );
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
}
