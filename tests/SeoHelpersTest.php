<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SeoHelpersTest extends TestCase
{
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
}
