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
            '/case \'([^\']+)\':\s*\$dispatchPage\(\'([^\']+)\'\);\s*break;/',
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

    public function testNewsModuleIsSeededAsPublic(): void
    {
        $schema = file_get_contents(__DIR__ . '/../app/runtime_schema.php');
        self::assertIsString($schema);
        $updates = file_get_contents(__DIR__ . '/../app/runtime_schema_updates.php');
        self::assertIsString($updates);

        self::assertMatchesRegularExpression(
            "/\\['news',\\s*'[^']+',\\s*'[^']+',\\s*0,\\s*1,\\s*'public',\\s*30\\]/",
            $schema,
            'The public news route must not be seeded with members-only module visibility.'
        );
        self::assertStringContainsString(
            "UPDATE modules SET is_enabled = 1, visibility = 'public' WHERE code IN ('news'",
            $updates,
            'Runtime schema updates must restore the public news module when production data disabled it.'
        );
        self::assertStringContainsString(
            "UPDATE modules SET is_enabled = 1, visibility = 'members' WHERE code IN ('dashboard', 'members', 'qsl')",
            $updates,
            'Runtime schema updates must restore the member dashboard module when production data disabled it.'
        );
    }

    public function testPublicRoutesAreNotGatedByMembersOnlyModules(): void
    {
        $router = file_get_contents(__DIR__ . '/../index.php');
        self::assertIsString($router);

        self::assertStringNotContainsString("'membership' => 'members'", $router);
        self::assertContains('search', $this->extractArrayValues($router, 'publicRoutes'));

        $publicEducationRoutes = [
            'code_q',
            'code_cw',
            'bandplan_on3',
            'bandplan_on2',
            'bandplan_harec',
        ];
        $publicRoutes = $this->extractArrayValues($router, 'publicRoutes');

        foreach ($publicEducationRoutes as $route) {
            self::assertContains($route, $publicRoutes, sprintf('Route %s must be public.', $route));
            self::assertStringNotContainsString(sprintf("'%s' => 'members'", $route), $router, sprintf('Route %s must not be gated by the members module.', $route));
            self::assertStringContainsString(sprintf("'%s' => 'education'", $route), $router, sprintf('Route %s must use the public education module gate.', $route));

            $page = file_get_contents(__DIR__ . '/../pages/' . $route . '.php');
            self::assertIsString($page);
            self::assertStringNotContainsString('require_login();', $page, sprintf('Public route %s must not require a login in its page controller.', $route));
        }
    }

    public function testDirectDiscoveryRoutesPreserveExtensions(): void
    {
        $router = file_get_contents(__DIR__ . '/../index.php');
        self::assertIsString($router);

        foreach (['sitemap.xml', 'robots.txt', 'llms.txt', 'ai-index.json', 'knowledge-graph.jsonld'] as $route) {
            self::assertStringContainsString("'" . $route . "'", $router);
            self::assertStringContainsString("case '" . $route . "':", $router);
        }
        self::assertStringContainsString('$directDiscoveryRoutes', $router);
        self::assertStringContainsString('$pathBasename', $router);
    }

    public function testAuthDoesNotCallPrivatePdoDatabaseConstructor(): void
    {
        $core = file_get_contents(__DIR__ . '/../app/core.php');
        self::assertIsString($core);

        self::assertStringNotContainsString('new \\Delight\\Db\\PdoDatabase($pdo)', $core);
        self::assertStringContainsString('new \\Delight\\Auth\\Auth($pdo)', $core);
    }

    public function testBootstrapUsesVersionedRuntimeSchemaUpdates(): void
    {
        $bootstrap = file_get_contents(__DIR__ . '/../app/bootstrap.php');
        self::assertIsString($bootstrap);

        self::assertStringContainsString('apply_runtime_schema_updates_if_needed();', $bootstrap);
        self::assertStringNotContainsString('apply_runtime_schema_updates();', $bootstrap);

        $schema = file_get_contents(__DIR__ . '/../app/runtime_schema.php');
        self::assertIsString($schema);
        self::assertStringContainsString('function runtime_schema_version(): string', $schema);
        self::assertStringContainsString('function apply_runtime_schema_updates_if_needed(?string $markerPath = null): bool', $schema);
        self::assertStringContainsString("require_once __DIR__ . '/runtime_schema_updates.php';", $schema);
        self::assertStringNotContainsString('function apply_runtime_schema_updates(): void', $schema);

        $updates = file_get_contents(__DIR__ . '/../app/runtime_schema_updates.php');
        self::assertIsString($updates);
        self::assertStringContainsString('function apply_runtime_schema_updates(): void', $updates);
    }

    public function testRouteSpecificHelpersAreLoadedLazily(): void
    {
        $functions = file_get_contents(__DIR__ . '/../app/functions.php');
        self::assertIsString($functions);
        foreach (['widgets.php', 'qsl_helpers.php', 'knowledge_helpers.php', 'auction_helpers.php', 'admin_helpers.php'] as $lazyHelper) {
            self::assertStringNotContainsString("require_once __DIR__ . '/" . $lazyHelper . "';", $functions);
        }

        $router = file_get_contents(__DIR__ . '/../index.php');
        self::assertIsString($router);
        self::assertStringContainsString('app_load_route_helpers($route);', $router);

        $bootstrap = file_get_contents(__DIR__ . '/../app/bootstrap.php');
        self::assertIsString($bootstrap);
        self::assertStringNotContainsString("require_once __DIR__ . '/newsletter.php';", $bootstrap);

        $loader = file_get_contents(__DIR__ . '/../app/route_helper_loader.php');
        self::assertIsString($loader);
        self::assertStringContainsString("'module_catalog.php' => ['home'", $loader);
        self::assertStringContainsString("'widgets.php' => ['home'", $loader);
        self::assertStringContainsString("'album_helpers.php' => ['home'", $loader);
        self::assertStringContainsString("'qsl_helpers.php' => ['qsl'", $loader);
        self::assertStringContainsString("'knowledge_helpers.php' => ['chatbot']", $loader);
        self::assertStringContainsString("'newsletter.php' => ['newsletter'", $loader);
        self::assertStringNotContainsString("'admin_helpers.php' => ['home'", $loader);

        $moduleCatalog = file_get_contents(__DIR__ . '/../app/module_catalog.php');
        self::assertIsString($moduleCatalog);
        self::assertStringContainsString('function admin_module_cards_catalog(): array', $moduleCatalog);
    }

    public function testModuleAndLoginGuardsPreserveNextRoute(): void
    {
        $router = file_get_contents(__DIR__ . '/../index.php');
        self::assertIsString($router);

        self::assertStringContainsString('require_module_enabled($routeModules[$route], $route);', $router);
        self::assertStringContainsString('require_login(login_next_url_for_route($route, $_GET));', $router);
    }
}
