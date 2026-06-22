<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/layout_renderer.php';

final class SurfaceCoverageContractTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private function dispatchRoutes(): array
    {
        $router = $this->source('index.php');
        preg_match_all(
            '/case \'([^\']+)\':(?:(?!case \').)*?\$dispatchPage\(\'([^\']+)\'\);/s',
            $router,
            $matches,
            PREG_SET_ORDER
        );

        $routes = [];
        foreach ($matches as $match) {
            $routes[(string) $match[1]] = (string) $match[2];
        }

        self::assertNotSame([], $routes);

        return $routes;
    }

    /**
     * @return list<string>
     */
    private function arrayValuesFromRouter(string $variableName): array
    {
        $router = $this->source('index.php');
        $pattern = sprintf('/\$%s = \[(.*?)\];/s', preg_quote($variableName, '/'));
        preg_match($pattern, $router, $match);
        self::assertNotEmpty($match, sprintf('Could not extract $%s from index.php.', $variableName));

        preg_match_all("/'([^']+)'/", (string) $match[1], $values);

        return array_values(array_unique($values[1] ?? []));
    }

    /**
     * @return array<string, string>
     */
    private function routeModules(): array
    {
        $router = $this->source('index.php');
        preg_match('/\$routeModules = \[(.*?)\];/s', $router, $match);
        self::assertNotEmpty($match);

        preg_match_all("/'([^']+)'\\s*=>\\s*'([^']+)'/", (string) $match[1], $matches, PREG_SET_ORDER);
        $routeModules = [];
        foreach ($matches as $routeMatch) {
            $routeModules[(string) $routeMatch[1]] = (string) $routeMatch[2];
        }

        return $routeModules;
    }

    public function testEveryRoutedPageIsClassifiedAsPublicMemberOrAdmin(): void
    {
        $dispatchRoutes = array_keys($this->dispatchRoutes());
        $publicRoutes = $this->arrayValuesFromRouter('publicRoutes');
        $routeModules = $this->routeModules();

        $publicPages = array_values(array_intersect($dispatchRoutes, $publicRoutes));
        $adminPages = array_values(array_filter(
            $dispatchRoutes,
            static fn(string $route): bool => $route === 'admin' || str_starts_with($route, 'admin_')
        ));
        $memberPages = array_values(array_diff($dispatchRoutes, $publicPages, $adminPages));

        self::assertSame([], array_intersect($publicPages, $memberPages), 'Public and member routes must be disjoint.');
        self::assertSame([], array_intersect($publicPages, $adminPages), 'Public and admin routes must be disjoint.');
        self::assertSame([], array_intersect($memberPages, $adminPages), 'Member and admin routes must be disjoint.');

        $classifiedRoutes = array_values(array_unique(array_merge($publicPages, $memberPages, $adminPages)));
        sort($dispatchRoutes);
        sort($classifiedRoutes);
        self::assertSame($dispatchRoutes, $classifiedRoutes, 'Every dispatched page must belong to exactly one role surface.');

        foreach (array_merge($memberPages, $adminPages) as $route) {
            self::assertArrayHasKey($route, $routeModules, sprintf('Protected route %s must have a module gate.', $route));
            self::assertNotContains($route, $publicRoutes, sprintf('Protected route %s must not be public.', $route));
        }
    }

    public function testEveryRoutedPageHasI18nDomainAndResolvableAssets(): void
    {
        $applicationSurfaceExceptions = [
            'admin_events_feed',
            'admin_fichiers',
            'admin_classifieds',
            'admin_presentations',
            'admin_pv',
            'admin_telechargements',
            'admin_videos',
            'admin_webotheque',
            'ai-index.json',
            'article_propose',
            'dashboard_widget_card',
            'events_feed',
            'idea_submit',
            'install.php',
            'knowledge-graph.jsonld',
            'llms.txt',
            'member_document_preview',
            'member_library_preview',
            'qsl_export',
            'robots.txt',
            'sitemap.xml',
            'widget_render',
            'wiki_propose',
        ];

        foreach ($this->dispatchRoutes() as $route => $relativePage) {
            $pagePath = dirname(__DIR__) . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePage);
            self::assertFileExists($pagePath, sprintf('Route %s points to a missing page.', $route));
            if (in_array($route, $applicationSurfaceExceptions, true)) {
                continue;
            }

            $domain = pathinfo($relativePage, PATHINFO_FILENAME);
            $i18nFile = dirname(__DIR__) . '/app/i18n/' . $domain . '.php';
            $i18nDirectory = dirname(__DIR__) . '/app/i18n/' . $domain;
            self::assertTrue(
                is_file($i18nFile) || is_dir($i18nDirectory),
                sprintf('Route %s must have an i18n domain for %s.', $route, $domain)
            );

            $cssAssets = module_css_assets_for_route($route);
            self::assertNotSame([], $cssAssets, sprintf('Route %s must load at least the shared CSS module.', $route));
            foreach (array_merge($cssAssets, module_js_assets_for_route($route)) as $asset) {
                self::assertFileExists(
                    dirname(__DIR__) . '/' . $asset,
                    sprintf('Route %s references missing asset %s.', $route, $asset)
                );
            }
        }
    }

    public function testAdminPagesKeepPermissionGuardsAndSeleniumCoverage(): void
    {
        $protectedRoutes = $this->source('tests/selenium/protected-routes.test.js');
        $authenticatedRoutes = $this->source('tests/selenium/authenticated-routes.test.js');
        $adminContract = $this->source('tests/selenium/admin-module-contract.test.js');

        foreach ($this->dispatchRoutes() as $route => $relativePage) {
            if ($route !== 'admin' && !str_starts_with($route, 'admin_')) {
                continue;
            }

            $page = $this->source($relativePage);
            $guardSource = $page;
            if (str_contains($page, 'render_admin_webotheque_page()')) {
                $guardSource .= "\n" . $this->source('app/member_webotheque.php');
            }
            if (str_contains($page, 'render_admin_member_document_module_page(')) {
                $guardSource .= "\n" . $this->source('app/member_module_documents.php');
            }
            self::assertMatchesRegularExpression(
                '/require_permission\\(|has_permission\\(|admin\\.access|modules\\.manage|redirect\\(\'admin_[a-z_]+\'\\)/',
                $guardSource,
                sprintf('Admin route %s must keep an explicit permission guard.', $route)
            );
            self::assertStringContainsString("'" . $route . "'", $protectedRoutes, sprintf('%s must be covered by protected-route Selenium tests.', $route));
            self::assertStringContainsString("'" . $route . "'", $authenticatedRoutes, sprintf('%s must be covered by authenticated Selenium tests.', $route));

            if ($route === 'admin_events_feed') {
                self::assertStringContainsString("routeUrl('admin_events_feed')", $adminContract);
                continue;
            }

            self::assertStringContainsString("'" . $route . "'", $adminContract, sprintf('%s must be covered by the deep admin Selenium contract.', $route));
        }
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(dirname(__DIR__) . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        self::assertIsString($source, sprintf('Could not read %s.', $relativePath));

        return $source;
    }
}
