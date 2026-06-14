<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SeoHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION['_page_meta'] = [];
        $_GET = [];
        $_SERVER = [];
    }

    public function testSeoBuildCanonicalQueryFiltersTechnicalParamsAndSortsKeys(): void
    {
        $query = [
            'z' => '3',
            '_csrf' => 'secret',
            'maintenance_bypass' => 'token',
            'utm_source' => 'newsletter',
            'fbclid' => 'tracking',
            'a' => '1',
        ];

        self::assertSame('a=1&z=3', seo_build_canonical_query($query));
    }

    public function testSeoRouteShouldNoindexForPrivateRoutes(): void
    {
        self::assertTrue(seo_route_should_noindex('admin_newsletters'));
        self::assertTrue(seo_route_should_noindex('newsletter'));
        self::assertTrue(seo_route_should_noindex('ai-index.json'));
        self::assertTrue(seo_route_should_noindex('knowledge-graph.jsonld'));
        self::assertFalse(seo_route_should_noindex('shop'));
    }

    public function testSeoApplyDefaultsProvidesRouteSpecificTitleAndDescription(): void
    {
        seo_apply_defaults('shop');

        $meta = (array) ($_SESSION['_page_meta'] ?? []);
        self::assertSame('Boutique du club', $meta['title'] ?? null);
        self::assertSame(
            'Catalogue des produits du club ON4CRD : textile, accessoires et documentation.',
            $meta['description'] ?? null
        );
        self::assertSame('index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1', $meta['robots'] ?? null);
    }

    public function testSeoApplyDefaultsKeepsNoindexOnSensitiveRoutes(): void
    {
        seo_apply_defaults('admin');

        $meta = (array) ($_SESSION['_page_meta'] ?? []);
        self::assertSame('noindex,nofollow', $meta['robots'] ?? null);
        self::assertSame('website', $meta['og_type'] ?? null);
    }

    public function testLocalizedSeoDefaultsPreservesContentIdentifiersAndDropsTracking(): void
    {
        $_GET = [
            'route' => 'article',
            'slug' => 'antenne-vhf',
            'utm_source' => 'newsletter',
            'fbclid' => 'tracking',
        ];

        $meta = localized_seo_defaults('article', 'fr', [], 'ON4CRD');

        self::assertStringContainsString('route=article', (string) $meta['canonical']);
        self::assertStringContainsString('slug=antenne-vhf', (string) $meta['canonical']);
        self::assertStringNotContainsString('utm_source', (string) $meta['canonical']);
        self::assertStringNotContainsString('fbclid', (string) $meta['canonical']);
        self::assertStringContainsString('slug=antenne-vhf', (string) $meta['alternates']['en']);
    }

    public function testSeoCanonicalUrlUsesDirectDiscoveryEndpoints(): void
    {
        $_SERVER['HTTP_HOST'] = 'on4crd.test';

        self::assertSame('http://on4crd.test/ai-index.json', seo_build_canonical_url('ai-index.json'));
        self::assertSame('http://on4crd.test/knowledge-graph.jsonld', seo_build_canonical_url('knowledge-graph.jsonld'));
    }

    public function testLocalizedSeoDefaultsAddsGeoMetadataForAnswerEngines(): void
    {
        $meta = localized_seo_defaults('tools', 'fr', [], 'ON4CRD');

        self::assertSame('public_webpage', $meta['content_type'] ?? null);
        self::assertNotEmpty($meta['ai_summary'] ?? '');
        self::assertIsArray($meta['json_ld'] ?? null);
        self::assertSame('ON4CRD', $meta['json_ld']['isPartOf']['name'] ?? null);
        self::assertSame('Radio Club Durnal ON4CRD', $meta['json_ld']['publisher']['name'] ?? null);
        self::assertSame(club_place_schema(), $meta['json_ld']['about']['location'] ?? null);
        self::assertStringEndsWith('#webpage', (string) ($meta['json_ld']['@id'] ?? ''));
    }

    public function testGeoDiscoveryFilesExposeAnswerEngineSignals(): void
    {
        $robots = file_get_contents(__DIR__ . '/../pages/robots.php');
        self::assertIsString($robots);
        self::assertStringContainsString('GPTBot', $robots);
        self::assertStringContainsString("route_url('ai-index.json')", $robots);
        self::assertStringContainsString("route_url('knowledge-graph.jsonld')", $robots);

        $sitemap = file_get_contents(__DIR__ . '/../pages/sitemap.php');
        self::assertIsString($sitemap);
        self::assertStringContainsString('xmlns:xhtml="http://www.w3.org/1999/xhtml"', $sitemap);
        self::assertStringContainsString('<xhtml:link rel="alternate"', $sitemap);

        $aiIndex = file_get_contents(__DIR__ . '/../pages/ai_index.php');
        self::assertIsString($aiIndex);
        self::assertStringContainsString("'schema_version' => '1.1'", $aiIndex);
        self::assertStringContainsString("'answer_engine_policy' =>", $aiIndex);
        self::assertStringContainsString("'knowledge_graph' =>", $aiIndex);

        $knowledgeGraph = file_get_contents(__DIR__ . '/../pages/knowledge_graph.php');
        self::assertIsString($knowledgeGraph);
        self::assertStringContainsString("'@type' => 'Dataset'", $knowledgeGraph);
        self::assertStringContainsString("'@type' => 'DataCatalog'", $knowledgeGraph);
        self::assertStringContainsString("club_place_schema(\$homeUrl . '#place')", $knowledgeGraph);
    }
}
