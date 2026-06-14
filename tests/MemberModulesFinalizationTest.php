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
        self::assertStringContainsString('name="proposal_theme"', $albums);
        self::assertMatchesRegularExpression('/<select\s+name="proposal_theme"[^>]*>.*\$albumThemeOptions/s', $albums);
        self::assertStringContainsString('name="proposal_keywords"', $albums);
        self::assertStringContainsString("'Mots cles' => \$keywords", $albums);

        $adminAlbums = $this->source('pages/admin_albums.php');
        self::assertMatchesRegularExpression('/<select\s+name="album_id"\s+required>.*foreach \(\$albums as \$album\)/s', $adminAlbums);
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
        self::assertSame('https://example.org', webotheque_normalize_url('example.org'));
    }

    public function testMembersLibraryModuleKeepsDocumentTopicSelectAndKeywords(): void
    {
        $library = $this->source('pages/members_library.php');

        self::assertStringContainsString('id="members-library-document-dialog"', $library);
        self::assertMatchesRegularExpression('/id="members-library-document-dialog".*<select name="proposal_category">.*name="proposal_tags"/s', $library);
        self::assertStringContainsString("\$proposalTags = content_proposal_clean_single_line", $library);
        self::assertStringContainsString("(string) (\$t['tags'] ?? 'Keywords') => \$proposalTags", $library);

        $codes = array_map(static fn(array $category): string => (string) $category['code'], member_library_default_categories());
        self::assertContains('general', $codes);
        self::assertContains('formation', $codes);
        self::assertContains('technique', $codes);
        self::assertNotContains('medias', $codes);
        self::assertSame(['Formation', 'technique'], library_filter_controlled_tags(['Formation', 'unknown', 'technique']));
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
