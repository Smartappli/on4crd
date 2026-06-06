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

    /**
     * @return array<string, string>
     */
    private function extractAssocStringArray(string $source, string $variableName): array
    {
        $pattern = sprintf("/\\$%s = \\[(.*?)\\];/s", preg_quote($variableName, '/'));
        preg_match($pattern, $source, $match);
        self::assertNotEmpty($match, sprintf('Could not extract $%s', $variableName));

        preg_match_all("/'([^']+)'\\s*=>\\s*'([^']+)'/", $match[1], $matches, PREG_SET_ORDER);
        $values = [];
        foreach ($matches as $routeMatch) {
            $values[(string) $routeMatch[1]] = (string) $routeMatch[2];
        }

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

    public function testPageControllerStaticIncludesReferenceExistingFiles(): void
    {
        $pageFiles = glob(__DIR__ . '/../pages/*.php');
        self::assertIsArray($pageFiles);

        foreach ($pageFiles as $file) {
            $contents = file_get_contents((string) $file);
            self::assertIsString($contents);

            preg_match_all(
                '/\b(?:require|require_once|include|include_once)\s+__DIR__\s*\.\s*\'([^\']+)\'\s*;/',
                $contents,
                $matches
            );

            foreach ($matches[1] as $relativePath) {
                $target = dirname((string) $file) . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
                self::assertFileExists(
                    $target,
                    sprintf('Page file %s references missing static include %s', basename((string) $file), $relativePath)
                );
            }
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

    public function testNewsSectionsAreSeeded(): void
    {
        $schema = file_get_contents(__DIR__ . '/../app/runtime_schema.php');
        self::assertIsString($schema);
        $updates = file_get_contents(__DIR__ . '/../app/runtime_schema_updates.php');
        self::assertIsString($updates);
        $installer = file_get_contents(__DIR__ . '/../install.php');
        self::assertIsString($installer);

        self::assertStringContainsString('function seed_news_sections(): void', $schema);
        self::assertStringContainsString("['on4crd', 'ON4CRD', 10]", $schema);
        self::assertStringContainsString("['autre-club', 'Autre club', 20]", $schema);
        self::assertStringContainsString("['contests', 'Contests', 30]", $schema);
        self::assertStringContainsString('seed_news_sections();', $updates);
        self::assertStringContainsString('seed_news_sections();', $installer);
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

    public function testRouteModuleMappingsCoverManagedRoutesAndSeededModules(): void
    {
        $router = file_get_contents(__DIR__ . '/../index.php');
        self::assertIsString($router);
        $routeModules = $this->extractAssocStringArray($router, 'routeModules');

        foreach ([
            'dashboard_widget_card' => 'dashboard',
            'members_library' => 'members',
            'tools' => 'tools',
            'tools_geocode' => 'tools',
            'relais' => 'education',
            'events_feed' => 'events',
            'admin_members' => 'admin',
            'admin_library' => 'admin',
            'admin_events_feed' => 'admin',
        ] as $route => $module) {
            self::assertSame($module, $routeModules[$route] ?? null, sprintf('Route %s must be gated by module %s.', $route, $module));
        }

        $schema = file_get_contents(__DIR__ . '/../app/runtime_schema.php');
        self::assertIsString($schema);
        preg_match_all("/\\['([a-z0-9_]+)',\\s*'[^']+',\\s*'[^']+',\\s*[01],\\s*[01],\\s*'(?:public|members|admin)'/", $schema, $moduleMatches);
        $seededModules = array_values(array_unique($moduleMatches[1] ?? []));

        foreach (array_unique(array_values($routeModules)) as $module) {
            self::assertContains($module, $seededModules, sprintf('Module %s is referenced by routeModules but not seeded.', $module));
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

    public function testRegistrationCleansSharedEmailAuthOrphans(): void
    {
        $register = file_get_contents(__DIR__ . '/../pages/register.php');
        $helpers = file_get_contents(__DIR__ . '/../app/member_profile_helpers.php');
        self::assertIsString($register);
        self::assertIsString($helpers);

        self::assertStringContainsString('member_cleanup_registration_auth_orphan($authEmail, $callsign);', $register);
        self::assertStringContainsString('member_delete_unlinked_auth_user((int) $userId);', $register);
        self::assertStringContainsString('function member_cleanup_registration_auth_orphan(string $authEmail, string $callsign): void', $helpers);
        self::assertStringContainsString('function member_shared_contact_emails(): array', $helpers);
        self::assertStringContainsString("'crddurnal@gmail.com'", $helpers);
        self::assertStringContainsString('array_merge($emails, member_shared_contact_emails())', $helpers);
        self::assertStringContainsString('str_ends_with($authEmail, \'@local.invalid\')', $helpers);
        self::assertStringContainsString('SELECT id FROM users WHERE username = ? AND email IN (', $helpers);
        self::assertStringContainsString('function member_delete_unlinked_auth_user(int $authUserId): void', $helpers);
    }

    public function testDirectorySearchMatchesCallsignsCaseInsensitively(): void
    {
        $directory = file_get_contents(__DIR__ . '/../pages/directory.php');
        self::assertIsString($directory);

        self::assertStringContainsString('UPPER(callsign) LIKE ?', $directory);
        self::assertStringContainsString("mb_safe_strtoupper(\$search)", $directory);
    }

    public function testCurrentUserRepairsMissingAuthUserLinkByCallsign(): void
    {
        $authHelpers = file_get_contents(__DIR__ . '/../app/auth_helpers.php');
        self::assertIsString($authHelpers);

        self::assertStringContainsString('$authClient->getUsername()', $authHelpers);
        self::assertStringContainsString('UPPER(callsign) = ?', $authHelpers);
        self::assertStringContainsString('$linkedAuthUserId === $authUserId', $authHelpers);
        self::assertStringContainsString('$linkedAuthUserId === 0', $authHelpers);
        self::assertStringContainsString('UPDATE members SET auth_user_id = ? WHERE id = ? AND (auth_user_id IS NULL OR auth_user_id = 0) LIMIT 1', $authHelpers);
    }

    public function testRouteSpecificHelpersAreLoadedLazily(): void
    {
        $functions = file_get_contents(__DIR__ . '/../app/functions.php');
        self::assertIsString($functions);
        foreach (['widgets.php', 'widget_catalog.php', 'widget_renderer.php', 'ham_weather_advice.php', 'member_content.php', 'member_library_helpers.php', 'member_favorites.php', 'member_preferences.php', 'member_recommendations.php', 'qsl_helpers.php', 'knowledge_helpers.php', 'auction_helpers.php', 'admin_helpers.php'] as $lazyHelper) {
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
        self::assertStringContainsString("'widget_catalog.php' => ['dashboard'", $loader);
        self::assertStringContainsString("'widget_renderer.php' => ['home'", $loader);
        self::assertStringContainsString("'ham_weather_advice.php' => ['home']", $loader);
        self::assertStringContainsString("'member_library_helpers.php' => ['members_library'", $loader);
        self::assertStringContainsString("'member_favorites.php' => ['dashboard'", $loader);
        self::assertStringContainsString("'member_preferences.php' => ['dashboard'", $loader);
        self::assertStringContainsString("'member_recommendations.php' => ['settings']", $loader);
        self::assertStringNotContainsString("'member_content.php' => ['dashboard'", $loader);
        self::assertStringContainsString("'article_helpers.php' => ['home', 'dashboard', 'search'", $loader);
        self::assertStringContainsString("'llms.txt', 'ai-index.json'", $loader);
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

    public function testPasswordResetCanBeForcedWithoutFirstLoginDefault(): void
    {
        $router = file_get_contents(__DIR__ . '/../index.php');
        $register = file_get_contents(__DIR__ . '/../pages/register.php');
        $adminMembers = file_get_contents(__DIR__ . '/../pages/admin_members.php');
        $authHelpers = file_get_contents(__DIR__ . '/../app/auth_helpers.php');
        self::assertIsString($router);
        self::assertIsString($register);
        self::assertIsString($adminMembers);
        self::assertIsString($authHelpers);

        self::assertStringContainsString('member_password_change_required($passwordChangeUser)', $router);
        self::assertStringContainsString('forced_notice', $router);
        self::assertMatchesRegularExpression('/password_hash\(\$password, PASSWORD_DEFAULT\),\s*0,/', $register);
        self::assertStringContainsString('redirect(module_enabled(\'dashboard\') ? \'dashboard\' : \'home\');', $register);
        self::assertStringContainsString('password_change_required = ?', $adminMembers);
        self::assertStringContainsString('password_reset_forced_at = ?', $adminMembers);
        self::assertStringContainsString('password_reset_forced_at', $authHelpers);
    }

    public function testConfiguredAdministratorsIncludeOn8cj(): void
    {
        $permissions = file_get_contents(__DIR__ . '/../app/permissions.php');
        self::assertIsString($permissions);

        self::assertStringContainsString("'ON8CJ'", $permissions);
    }

    public function testProposalDialogTriggersKeepNativeFallbacks(): void
    {
        $contracts = [
            'pages/articles.php' => ['data-articles-category-open'],
            'pages/classifieds.php' => ['data-classifieds-category-open'],
            'pages/events.php' => ['data-event-proposal-open'],
            'pages/members_library.php' => [
                'data-members-library-modal-open="members-library-category-dialog"',
                'data-members-library-modal-open="members-library-document-dialog"',
            ],
            'pages/news.php' => [
                'data-news-proposal-open="news-proposal-dialog"',
                'data-news-proposal-open="news-category-proposal-dialog"',
            ],
            'pages/wiki.php' => ['data-wiki-theme-open'],
        ];

        foreach ($contracts as $relativePath => $attributes) {
            $source = file_get_contents(__DIR__ . '/../' . $relativePath);
            self::assertIsString($source);

            foreach ($attributes as $attribute) {
                $matchingLines = array_values(array_filter(
                    explode("\n", $source),
                    static fn(string $line): bool => str_contains($line, $attribute)
                ));

                self::assertNotEmpty(
                    $matchingLines,
                    sprintf('%s must contain proposal trigger %s.', $relativePath, $attribute)
                );

                self::assertStringContainsString(
                    '<a ',
                    $matchingLines[0],
                    sprintf('%s must expose %s as a native link, not a JavaScript-only button.', $relativePath, $attribute)
                );
                self::assertStringContainsString('href=', $matchingLines[0], sprintf('%s native trigger %s must have an href fallback.', $relativePath, $attribute));
                self::assertStringNotContainsString('<button', $matchingLines[0], sprintf('%s native trigger %s must not be a button.', $relativePath, $attribute));
            }
        }

        $legacyFallbacks = [
            'data-articles-category-fallback',
            'data-event-proposal-fallback',
            'data-members-library-fallback',
            'data-news-proposal-fallback',
            'data-wiki-theme-fallback',
            'dataset.articlesCategoryFallback',
            'dataset.eventProposalFallback',
            'dataset.membersLibraryFallback',
            'dataset.newsProposalFallback',
            'dataset.wikiThemeFallback',
        ];

        foreach (array_keys($contracts) as $relativePath) {
            $source = file_get_contents(__DIR__ . '/../' . $relativePath);
            self::assertIsString($source);
            foreach ($legacyFallbacks as $fallback) {
                self::assertStringNotContainsString($fallback, $source, sprintf('%s still contains legacy fallback attribute %s.', $relativePath, $fallback));
            }
        }

        foreach (glob(__DIR__ . '/../assets/js/modules/*.js') ?: [] as $path) {
            $source = file_get_contents((string) $path);
            self::assertIsString($source);
            foreach ($legacyFallbacks as $fallback) {
                self::assertStringNotContainsString($fallback, $source, sprintf('%s still contains legacy fallback logic %s.', basename((string) $path), $fallback));
            }
        }
    }
}
