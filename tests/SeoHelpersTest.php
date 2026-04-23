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
    }

    public function testSeoBuildCanonicalQueryFiltersTechnicalParamsAndSortsKeys(): void
    {
        $query = [
            'z' => '3',
            '_csrf' => 'secret',
            'maintenance_bypass' => 'token',
            'a' => '1',
        ];

        self::assertSame('a=1&z=3', seo_build_canonical_query($query));
    }

    public function testSeoRouteShouldNoindexForPrivateRoutes(): void
    {
        self::assertTrue(seo_route_should_noindex('admin_newsletters'));
        self::assertTrue(seo_route_should_noindex('newsletter'));
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
}
