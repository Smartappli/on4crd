<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RouterContractTest extends TestCase
{
    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function extractDispatchRoutes(string $router): array
    {
        preg_match_all(
            "/case '([^']+)': \\$dispatchPage\\('([^']+)'\\); break;/",
            $router,
            $matches,
            PREG_SET_ORDER
        );

        return $matches;
    }

    /**
     * @return list<string>
     */
    private function extractArrayValues(string $router, string $variableName): array
    {
        $pattern = sprintf("/\\$%s = \\[(.*?)\\];/s", preg_quote($variableName, '/'));
        preg_match($pattern, $router, $match);
        self::assertNotEmpty($match, sprintf('Could not extract $%s from index.php', $variableName));

        preg_match_all("/'([^']+)'/", $match[1], $valueMatches);

        /** @var list<string> $values */
        $values = $valueMatches[1];

        return $values;
    }

    public function testEachSwitchCaseReferencesAnExistingPageFile(): void
    {
        $router = file_get_contents(__DIR__ . '/../index.php');
        self::assertIsString($router);

        $matches = $this->extractDispatchRoutes($router);
        self::assertNotEmpty($matches);

        foreach ($matches as $match) {
            $file = __DIR__ . '/../' . ltrim($match[2], '/');
            self::assertFileExists($file, sprintf('Route %s points to missing file %s', $match[1], $match[2]));
        }
    }

    public function testEveryPhpPageIsReachableThroughRouter(): void
    {
        $router = file_get_contents(__DIR__ . '/../index.php');
        self::assertIsString($router);

        $matches = $this->extractDispatchRoutes($router);
        $routedFiles = [];
        foreach ($matches as $match) {
            if (str_starts_with($match[2], 'pages/')) {
                $routedFiles[] = basename($match[2]);
            }
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

        $publicRoutes = $this->extractArrayValues($router, 'publicRoutes');

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

    public function testEveryPublicRouteIsHandledInSwitchOrPreDispatchGuard(): void
    {
        $router = file_get_contents(__DIR__ . '/../index.php');
        self::assertIsString($router);

        $publicRoutes = $this->extractArrayValues($router, 'publicRoutes');
        $dispatchRoutes = array_map(
            static fn(array $match): string => $match[1],
            $this->extractDispatchRoutes($router)
        );

        $preDispatchRoutes = [
            'toggle_theme',
            'set_language',
            'set_accent',
            'set_theme',
            'logout',
        ];

        foreach ($publicRoutes as $route) {
            self::assertTrue(
                in_array($route, $dispatchRoutes, true) || in_array($route, $preDispatchRoutes, true),
                sprintf('Public route %s is not explicitly handled', $route)
            );
        }
    }
}
