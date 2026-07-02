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

    public function testPublicAndMemberPagesKeepSeleniumCoverage(): void
    {
        $publicRouteCoverage = $this->source('tests/selenium/public-routes.test.js');
        $publicDetailCoverage = $this->source('tests/selenium/public-detail-not-found.test.js');
        $authenticatedPublicCsrf = $this->source('tests/selenium/authenticated-public-csrf.test.js');
        $protectedRoutes = $this->source('tests/selenium/protected-routes.test.js');
        $authenticatedRoutes = $this->source('tests/selenium/authenticated-routes.test.js');

        $publicRoutes = $this->arrayValuesFromRouter('publicRoutes');
        $publicNonPageRoutes = [
            'ad_click',
            'ai-index.json',
            'article',
            'album',
            'auction_view',
            'event_view',
            'events_feed',
            'footer_contact',
            'idea_submit',
            'knowledge-graph.jsonld',
            'llms.txt',
            'news_view',
            'robots.txt',
            'sitemap.xml',
            'tools_geocode',
            'wiki_view',
        ];
        $publicDetailRoutes = ['ad_click', 'article', 'album', 'auction_view', 'event_view', 'news_view', 'wiki_view'];
        $authenticatedPublicPostRoutes = [
            'article',
            'auction_view',
            'album',
            'articles',
            'auctions',
            'chatbot',
            'classifieds',
            'donation',
            'events',
            'gdpr',
            'home',
            'membership',
            'news',
            'newsletter_public',
            'newsletter_unsubscribe',
            'tools',
            'wiki',
            'wiki_view',
        ];
        $memberEndpointRoutes = [
            'auction_bid',
            'dashboard_widget_card',
            'member_document_preview',
            'member_library_preview',
            'qsl_export',
            'qsl_preview',
            'save_dashboard',
            'widget_render',
            'wiki_edit',
        ];

        foreach ($this->dispatchRoutes() as $route => $_relativePage) {
            if (in_array($route, $publicRoutes, true)) {
                if (!in_array($route, $publicNonPageRoutes, true)) {
                    self::assertStringContainsString(
                        "['" . $route . "'",
                        $publicRouteCoverage,
                        sprintf('Public page route %s must be covered by public-routes Selenium tests.', $route)
                    );
                }

                if (in_array($route, $publicDetailRoutes, true)) {
                    self::assertStringContainsString("'" . $route . "'", $publicDetailCoverage, sprintf('Public detail route %s must be covered by not-found Selenium tests.', $route));
                }

                if (in_array($route, $authenticatedPublicPostRoutes, true)) {
                    self::assertStringContainsString("'" . $route . "'", $authenticatedPublicCsrf, sprintf('Public route %s must be covered when authenticated member-only actions are visible.', $route));
                }

                continue;
            }

            if ($route === 'admin' || str_starts_with($route, 'admin_')) {
                continue;
            }

            self::assertStringContainsString("'" . $route . "'", $protectedRoutes, sprintf('Member route %s must be covered by protected-route Selenium tests.', $route));
            if (!in_array($route, $memberEndpointRoutes, true)) {
                self::assertStringContainsString("'" . $route . "'", $authenticatedRoutes, sprintf('Member page route %s must be covered by authenticated Selenium tests.', $route));
            }
        }
    }

    public function testPostHandlingRoutesKeepCsrfAndSeleniumCoverage(): void
    {
        $publicCsrfCoverage = $this->source('tests/selenium/csrf-forms.test.js');
        $authenticatedCsrfCoverage = $this->source('tests/selenium/authenticated-csrf-contract.test.js');
        $authenticatedPublicCsrf = $this->source('tests/selenium/authenticated-public-csrf.test.js');
        $protectedPostEndpointCoverage = [
            'auction_bid' => [
                $this->source('tests/selenium/admin-auctions-workflow.test.js'),
                'form[action*="route=auction_bid"]',
            ],
        ];
        $publicRoutes = $this->arrayValuesFromRouter('publicRoutes');
        $publicPostEndpointRoutes = ['footer_contact', 'idea_submit'];

        foreach ($this->dispatchRoutes() as $route => $relativePage) {
            $controller = $this->routeControllerSource($relativePage);
            if (!str_contains($controller, 'REQUEST_METHOD') || !str_contains($controller, 'POST')) {
                continue;
            }

            self::assertStringContainsString('verify_csrf(', $controller, sprintf('POST route %s must verify CSRF tokens.', $route));

            if (in_array($route, $publicRoutes, true)) {
                $isPublicPostEndpoint = in_array($route, $publicPostEndpointRoutes, true);
                $hasPublicFormCoverage = str_contains($publicCsrfCoverage, "'" . $route . "'")
                    || str_contains($authenticatedPublicCsrf, "'" . $route . "'")
                    || $isPublicPostEndpoint;

                self::assertTrue(
                    $hasPublicFormCoverage,
                    sprintf('Public POST route %s must have public or authenticated-public Selenium CSRF coverage.', $route)
                );
                continue;
            }

            if (isset($protectedPostEndpointCoverage[$route])) {
                [$coverageSource, $coverageSnippet] = $protectedPostEndpointCoverage[$route];
                self::assertStringContainsString(
                    $coverageSnippet,
                    $coverageSource,
                    sprintf('Protected POST endpoint %s must be covered by its workflow Selenium test.', $route)
                );
                continue;
            }

            self::assertStringContainsString("'" . $route . "'", $authenticatedCsrfCoverage, sprintf('Protected POST route %s must be covered by authenticated CSRF Selenium tests.', $route));
        }
    }

    private function routeControllerSource(string $relativePage): string
    {
        $source = $this->source($relativePage);

        if (str_contains($source, 'render_member_webotheque_page(') || str_contains($source, 'render_admin_webotheque_page()')) {
            $source .= "\n" . $this->source('app/member_webotheque.php');
        }

        if (str_contains($source, 'render_member_document_module_page(') || str_contains($source, 'render_admin_member_document_module_page(')) {
            $source .= "\n" . $this->source('app/member_module_documents.php');
        }

        return $source;
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(dirname(__DIR__) . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        self::assertIsString($source, sprintf('Could not read %s.', $relativePath));

        return $source;
    }
}
