<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RouterContractTest extends TestCase
{
    public function testEachSwitchCaseReferencesAnExistingPageFile(): void
    {
        $router = file_get_contents(__DIR__ . '/../index.php');
        self::assertIsString($router);

        preg_match_all("/case '([^']+)': require __DIR__ \\.'\\/pages\\/([^']+)'; break;/", $router, $matches, PREG_SET_ORDER);
        self::assertNotEmpty($matches);

        foreach ($matches as $match) {
            $file = __DIR__ . '/../pages/' . $match[2];
            self::assertFileExists($file, sprintf('Route %s points to missing file %s', $match[1], $match[2]));
        }
    }

    public function testEveryPhpPageIsReachableThroughRouter(): void
    {
        $router = file_get_contents(__DIR__ . '/../index.php');
        self::assertIsString($router);

        preg_match_all("/case '([^']+)': require __DIR__ \\.'\\/pages\\/([^']+)'; break;/", $router, $matches, PREG_SET_ORDER);
        $routedFiles = [];
        foreach ($matches as $match) {
            $routedFiles[] = $match[2];
        }

        $pageFiles = glob(__DIR__ . '/../pages/*.php');
        self::assertIsArray($pageFiles);

        foreach ($pageFiles as $file) {
            $basename = basename((string) $file);
            self::assertContains($basename, $routedFiles, sprintf('Page file %s is not mapped in index.php', $basename));
        }
    }

    public function testSensitiveRoutesAreNotPublic(): void
    {
        $router = file_get_contents(__DIR__ . '/../index.php');
        self::assertIsString($router);

        preg_match("/\\$publicRoutes = \\[(.*?)\\];/s", $router, $match);
        self::assertNotEmpty($match);

        preg_match_all("/'([^']+)'/", $match[1], $publicMatches);
        $publicRoutes = $publicMatches[1];

        $mustStayPrivate = [
            'admin',
            'admin_permissions',
            'admin_modules',
            'admin_articles',
            'admin_committee',
            'admin_wiki',
            'admin_albums',
            'admin_news',
            'admin_press',
            'admin_editorial',
            'admin_translation_reviews',
            'admin_live_feeds',
            'admin_events',
            'admin_shop',
            'admin_auctions',
            'dashboard',
            'save_dashboard',
            'widget_render',
            'qsl',
            'qsl_preview',
            'qsl_export',
            'auction_bid',
            'shop_checkout',
        ];

        foreach ($mustStayPrivate as $route) {
            self::assertNotContains($route, $publicRoutes, sprintf('Sensitive route %s must not be public', $route));
        }
    }
}
