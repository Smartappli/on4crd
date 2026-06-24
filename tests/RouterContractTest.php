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
            '/case \'([^\']+)\':(?:(?!case \').)*?\$dispatchPage\(\'([^\']+)\'\);(?:(?!case \').)*?break;/s',
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

    public function testLoggedInToolbarShowsMembershipStatusBadge(): void
    {
        $layout = file_get_contents(__DIR__ . '/../app/layout_renderer.php');
        self::assertIsString($layout);

        self::assertStringContainsString('if ($user !== null) {', $layout);
        self::assertStringContainsString('$membershipBadgeHtml = \'<span class="membership-status-badge">\'', $layout);
        self::assertStringContainsString("layoutI18n['membership_good_standing']", $layout);
        self::assertStringContainsString('toolbar-account-stack', $layout);
        self::assertStringContainsString('membership-status-badge', $layout);
        self::assertStringContainsString("'</div>' . \$membershipBadgeHtml . '</div>'", $layout);
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
            'member_library_preview',
            'member_document_preview',
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
            'member_library_preview' => 'members',
            'member_document_preview' => 'members',
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

    public function testPublicRegistrationIsClosedAndAdminCreationCleansSharedEmailAuthOrphans(): void
    {
        $register = file_get_contents(__DIR__ . '/../pages/register.php');
        $adminMembers = file_get_contents(__DIR__ . '/../pages/admin_members.php');
        $helpers = file_get_contents(__DIR__ . '/../app/member_profile_helpers.php');
        self::assertIsString($register);
        self::assertIsString($adminMembers);
        self::assertIsString($helpers);

        self::assertStringNotContainsString('createUserWithUniqueUsername', $register);
        self::assertStringNotContainsString('ON DUPLICATE KEY UPDATE', $register);
        self::assertStringNotContainsString('ensure_configured_administrator_roles', $register);
        self::assertStringContainsString('member_cleanup_registration_auth_orphan($authEmail, $callsign);', $adminMembers);
        self::assertStringContainsString('member_delete_unlinked_auth_user($authUserId);', $adminMembers);
        self::assertStringContainsString('function member_cleanup_registration_auth_orphan(string $authEmail, string $callsign): void', $helpers);
        self::assertStringContainsString('function member_shared_contact_emails(): array', $helpers);
        self::assertStringContainsString("'crddurnal@gmail.com'", $helpers);
        self::assertStringContainsString('array_merge($emails, member_shared_contact_emails())', $helpers);
        self::assertStringContainsString('str_ends_with($authEmail, \'@local.invalid\')', $helpers);
        self::assertStringContainsString('SELECT id FROM users WHERE username = ? AND email IN (', $helpers);
        self::assertStringContainsString('function member_delete_unlinked_auth_user(int $authUserId): void', $helpers);
    }

    public function testAdminMembersCreatesAccountsWithoutPublicRegistrationUpsert(): void
    {
        $register = file_get_contents(__DIR__ . '/../pages/register.php');
        $adminMembers = file_get_contents(__DIR__ . '/../pages/admin_members.php');
        self::assertIsString($register);
        self::assertIsString($adminMembers);

        self::assertStringContainsString('Public registration is closed', $register);
        self::assertStringContainsString("SELECT COUNT(*) FROM members WHERE UPPER(callsign) = ?", $adminMembers);
        self::assertStringContainsString('$authClient->admin()->createUserWithUniqueUsername($authEmail, $password, $callsign);', $adminMembers);
        self::assertStringContainsString('INSERT INTO members (', $adminMembers);
        self::assertStringNotContainsString('ON DUPLICATE KEY UPDATE', $adminMembers);
        self::assertStringNotContainsString('registerWithUniqueUsername($authEmail, $password, $callsign)', $register);
    }

    public function testDirectorySearchMatchesCallsignsCaseInsensitively(): void
    {
        $directory = file_get_contents(__DIR__ . '/../pages/directory.php');
        self::assertIsString($directory);

        self::assertStringContainsString('UPPER(callsign) LIKE ?', $directory);
        self::assertStringContainsString("mb_safe_strtoupper(\$search)", $directory);
    }

    public function testDirectoryDoesNotHideOn4crdCallsign(): void
    {
        $directory = file_get_contents(__DIR__ . '/../pages/directory.php');
        self::assertIsString($directory);

        self::assertStringNotContainsString('UPPER(callsign) <> ?', $directory);
    }

    public function testDirectoryExcludesExplicitlyHiddenMembers(): void
    {
        $directory = file_get_contents(__DIR__ . '/../pages/directory.php');
        $schemaUpdates = file_get_contents(__DIR__ . '/../app/runtime_schema_updates.php');
        self::assertIsString($directory);
        self::assertIsString($schemaUpdates);

        self::assertStringContainsString("'directory_hidden' => 'ALTER TABLE members ADD COLUMN directory_hidden TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active'", $schemaUpdates);
        self::assertStringContainsString('$directoryVisibleWhere = \'is_active = 1 AND directory_hidden = 0\';', $directory);
        self::assertStringContainsString('WHERE \' . $directoryVisibleWhere', $directory);
    }

    public function testConfigLoaderSupportsEnvironmentOverride(): void
    {
        $loader = file_get_contents(__DIR__ . '/../app/config_loader.php');
        $core = file_get_contents(__DIR__ . '/../app/core.php');
        $bootstrap = file_get_contents(__DIR__ . '/../app/bootstrap.php');
        self::assertIsString($loader);
        self::assertIsString($core);
        self::assertIsString($bootstrap);

        self::assertStringContainsString("getenv('ON4CRD_CONFIG_FILE')", $loader);
        self::assertStringContainsString('function app_config_file_path(): string', $loader);
        self::assertStringContainsString('require_once __DIR__ . \'/config_loader.php\';', $core);
        self::assertStringContainsString('$configFile = app_config_file_path();', $core);
        self::assertStringContainsString('require_once __DIR__ . \'/config_loader.php\';', $bootstrap);
        self::assertStringContainsString('$bootstrapConfigFile = app_config_file_path();', $bootstrap);
    }

    public function testCommitteeEditorialHelpersSupportCurrentEditorialSchema(): void
    {
        $committeeHelpers = file_get_contents(__DIR__ . '/../app/committee_helpers.php');
        self::assertIsString($committeeHelpers);

        self::assertStringContainsString("table_has_column('editorial_contents', 'content_key')", $committeeHelpers);
        self::assertStringContainsString("function editorial_content_text_columns(): array", $committeeHelpers);
        self::assertStringContainsString("\$textColumn = \$locale . '_text';", $committeeHelpers);
        self::assertStringContainsString('INSERT INTO editorial_contents (content_key, fr_text, en_text, de_text, nl_text)', $committeeHelpers);
        self::assertStringNotContainsString('SELECT fr, en, de, nl FROM editorial_contents WHERE slot = ?', $committeeHelpers);
    }

    public function testSeedModulesPreservesAdminConfigurableState(): void
    {
        $schema = file_get_contents(__DIR__ . '/../app/runtime_schema.php');
        self::assertIsString($schema);

        self::assertStringContainsString('is_enabled = IF(VALUES(is_core) = 1, VALUES(is_enabled), is_enabled)', $schema);
        self::assertStringContainsString('visibility = IF(VALUES(is_core) = 1, VALUES(visibility), visibility)', $schema);
        self::assertStringNotContainsString('is_enabled = VALUES(is_enabled), visibility = VALUES(visibility)', $schema);
    }

    public function testSeleniumHelperDisablesServiceWorkerDuringAuthenticatedRuns(): void
    {
        $seleniumHelpers = file_get_contents(__DIR__ . '/../tests/selenium/helpers.js');
        self::assertIsString($seleniumHelpers);

        self::assertStringContainsString("async function disableServiceWorkerForSelenium(driver)", $seleniumHelpers);
        self::assertStringContainsString("driver.sendDevToolsCommand('ServiceWorker.disable')", $seleniumHelpers);
        self::assertStringContainsString("Page.addScriptToEvaluateOnNewDocument", $seleniumHelpers);
        self::assertStringContainsString("await driver.wait(async () => !(await isLoginPage(driver))", $seleniumHelpers);
        self::assertStringContainsString("await driver.manage().deleteAllCookies();", $seleniumHelpers);
        self::assertStringContainsString("function readConfiguredSeleniumAppBaseUrl()", $seleniumHelpers);
        self::assertStringContainsString("async function ensureSeleniumTarget(t, driver)", $seleniumHelpers);
        self::assertStringContainsString("SELENIUM_STRICT_TARGET", $seleniumHelpers);
        self::assertStringContainsString("function resetSeleniumLoginThrottle(username)", $seleniumHelpers);
        self::assertStringContainsString('for ($octet = 16; $octet <= 31; $octet++)', $seleniumHelpers);
        self::assertStringContainsString("SELENIUM_THROTTLE_IPS", $seleniumHelpers);
        self::assertStringContainsString("\$buckets[] = \$bucket(['attemptToLogin', \$ip]);", $seleniumHelpers);
    }

    public function testPressReleasesUseCurrentPublishedOnSchemaColumn(): void
    {
        $pressHelpers = file_get_contents(__DIR__ . '/../app/press_helpers.php');
        $schema = file_get_contents(__DIR__ . '/../schema/schema.sql');
        self::assertIsString($pressHelpers);
        self::assertIsString($schema);

        self::assertStringContainsString('published_on DATE DEFAULT NULL', $schema);
        self::assertStringContainsString("table_has_column('press_releases', 'published_on')", $pressHelpers);
        self::assertStringContainsString("'published_on'", $pressHelpers);
        self::assertStringContainsString("'release_date'", $pressHelpers);
        self::assertStringContainsString('` DESC, id DESC', $pressHelpers);
    }

    public function testCurrentUserRepairsMissingAuthUserLinkByCallsign(): void
    {
        $authHelpers = file_get_contents(__DIR__ . '/../app/auth_helpers.php');
        self::assertIsString($authHelpers);

        self::assertStringContainsString('function authenticated_member_row(\\Delight\\Auth\\Auth $authClient, int $sessionMemberId = 0): ?array', $authHelpers);
        self::assertStringContainsString('function authenticated_member_create_from_auth_user(\\Delight\\Auth\\Auth $authClient, string $authUsername): ?array', $authHelpers);
        self::assertStringContainsString("preg_match('/^[A-Z0-9]{3,32}$/', \$authUsername)", $authHelpers);
        self::assertStringContainsString('INSERT INTO members (', $authHelpers);
        self::assertStringContainsString('$authClient->getUsername()', $authHelpers);
        self::assertStringContainsString('UPPER(callsign) = ?', $authHelpers);
        self::assertStringContainsString('$linkedAuthUserId === $authUserId', $authHelpers);
        self::assertStringContainsString('$linkedAuthUserId === 0', $authHelpers);
        self::assertStringContainsString('UPDATE members SET auth_user_id = ? WHERE id = ? AND (auth_user_id IS NULL OR auth_user_id = 0) LIMIT 1', $authHelpers);
    }

    public function testLoginValidatesMemberProfileBeforeSuccessFlash(): void
    {
        $login = file_get_contents(__DIR__ . '/../pages/login.php');
        self::assertIsString($login);

        self::assertStringContainsString('authenticated_member_row($authClient', $login);
        self::assertStringContainsString("throw new RuntimeException((string) (\$t['member_unavailable'] ?? \$t['auth_unavailable']));", $login);
        self::assertStringContainsString("\$_SESSION['member_id'] = (int) (\$memberRow['id'] ?? 0);", $login);
        self::assertStringNotContainsString('session_regenerate_id(true);', $login);
    }

    public function testPublicRegisterDoesNotAutoLoginOrCreateMembers(): void
    {
        $register = file_get_contents(__DIR__ . '/../pages/register.php');
        self::assertIsString($register);

        self::assertStringContainsString("redirect('login');", $register);
        self::assertStringNotContainsString('authenticated_member_row($authClient', $register);
        self::assertStringNotContainsString("\$_SESSION['member_id']", $register);
        self::assertStringNotContainsString('createUserWithUniqueUsername', $register);
        self::assertStringNotContainsString('session_regenerate_id(true);', $register);
        self::assertStringNotContainsString("\$_SESSION['member_id'] = (int) \$authClient->getUserId();", $register);
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
        self::assertStringContainsString("'widget_catalog.php' => ['dashboard', 'save_dashboard', 'widget_render'", $loader);
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
        self::assertStringContainsString("'layout_renderer.php' => ['bandplan_on3', 'bandplan_on2', 'bandplan_harec']", $loader);
        self::assertStringContainsString("'knowledge_helpers.php' => ['chatbot']", $loader);
        self::assertStringContainsString("'member_media.php' => ['directory', 'gdpr', 'profile', 'admin_committee']", $loader);
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
        $adminMembers = file_get_contents(__DIR__ . '/../pages/admin_members.php');
        $authHelpers = file_get_contents(__DIR__ . '/../app/auth_helpers.php');
        self::assertIsString($router);
        self::assertIsString($adminMembers);
        self::assertIsString($authHelpers);

        self::assertStringContainsString('member_password_change_required($passwordChangeUser)', $router);
        self::assertStringContainsString('forced_notice', $router);
        self::assertStringContainsString('password_hash($password, PASSWORD_DEFAULT)', $adminMembers);
        self::assertStringContainsString('password_change_required = ?', $adminMembers);
        self::assertStringContainsString('password_reset_forced_at = ?', $adminMembers);
        self::assertStringContainsString('password_reset_forced_at', $authHelpers);
    }

    public function testConfiguredAdministratorsIncludeExpectedCallsigns(): void
    {
        $permissions = file_get_contents(__DIR__ . '/../app/permissions.php');
        self::assertIsString($permissions);

        foreach (['ON8CJ', 'ON7ZB', 'ON4BEN'] as $callsign) {
            self::assertStringContainsString("'" . $callsign . "'", $permissions);
        }
    }

    public function testAdminDashboardSurfacesPendingContentQueues(): void
    {
        $adminHelpers = file_get_contents(__DIR__ . '/../app/admin_helpers.php');
        $adminPage = file_get_contents(__DIR__ . '/../pages/admin.php');
        self::assertIsString($adminHelpers);
        self::assertIsString($adminPage);

        self::assertStringContainsString('function admin_pending_content_counts_by_route(): array', $adminHelpers);
        self::assertStringContainsString('SELECT area, COUNT(*) AS total FROM content_proposals WHERE status = "pending" GROUP BY area', $adminHelpers);
        self::assertStringContainsString("'pending_count' => \$pendingCount", $adminHelpers);
        self::assertStringContainsString('function admin_pending_content_proposals_for_dashboard(string $locale', $adminHelpers);
        self::assertStringContainsString('function admin_update_content_proposal_status(', $adminHelpers);

        self::assertStringContainsString('$pendingProposals = admin_pending_content_proposals_for_dashboard($locale);', $adminPage);
        self::assertStringContainsString('id="pending-proposals"', $adminPage);
        self::assertStringContainsString('name="action" value="update_content_proposal_status"', $adminPage);
        self::assertStringContainsString('admin-pending-badge', $adminPage);
        self::assertStringContainsString("\$card['pending_count']", $adminPage);
    }

    public function testAdminRoutesAreProtectedDispatchedAndCatalogued(): void
    {
        $router = file_get_contents(__DIR__ . '/../index.php');
        $moduleCatalog = file_get_contents(__DIR__ . '/../app/module_catalog.php');
        self::assertIsString($router);
        self::assertIsString($moduleCatalog);

        $routeModules = $this->extractAssocStringArray($router, 'routeModules');
        $dispatchRoutes = [];
        foreach ($this->extractDispatchRoutes($router) as $match) {
            $dispatchRoutes[(string) $match[1]] = (string) $match[2];
        }

        preg_match_all("/\\['route' => '([^']+)'/", $moduleCatalog, $catalogMatches);
        $catalogRoutes = array_fill_keys($catalogMatches[1], true);
        $technicalAdminRoutes = ['admin' => true, 'admin_events_feed' => true];
        $adminPageFiles = glob(__DIR__ . '/../pages/admin*.php');
        self::assertIsArray($adminPageFiles);

        $adminRoutes = [];
        foreach ($adminPageFiles as $pageFile) {
            $adminRoutes[basename((string) $pageFile, '.php')] = true;
        }

        foreach ($routeModules as $route => $module) {
            if ($route === 'admin' || str_starts_with($route, 'admin_')) {
                $adminRoutes[$route] = true;
            }
        }

        ksort($adminRoutes);
        self::assertNotEmpty($adminRoutes);

        foreach (array_keys($catalogRoutes) as $route) {
            self::assertArrayHasKey($route, $adminRoutes, sprintf('Catalogued admin route %s must have a page file or route module mapping.', $route));
            self::assertArrayHasKey($route, $routeModules, sprintf('Catalogued admin route %s must have a module gate.', $route));
        }

        foreach (array_keys($adminRoutes) as $route) {
            self::assertArrayHasKey($route, $dispatchRoutes, sprintf('Admin route %s must be dispatched.', $route));
            self::assertSame('pages/' . $route . '.php', $dispatchRoutes[$route], sprintf('Admin route %s must dispatch to its matching page file.', $route));
            self::assertArrayHasKey($route, $routeModules, sprintf('Admin route %s must have a module gate.', $route));
            self::assertNotSame('', $routeModules[$route], sprintf('Admin route %s must have a non-empty module gate.', $route));

            $pagePath = __DIR__ . '/../' . $dispatchRoutes[$route];
            self::assertFileExists($pagePath, sprintf('Admin route %s points to a missing page.', $route));
            $page = file_get_contents($pagePath);
            self::assertIsString($page);

            $isRedirectOnly = preg_match('/^\s*<\?php\s+declare\(strict_types=1\);\s+redirect\([\'"][^\'"]+[\'"]\);\s*$/s', $page) === 1;
            $hasProtection = str_contains($page, 'require_permission(')
                || str_contains($page, 'render_admin_member_document_module_page(')
                || str_contains($page, 'render_admin_webotheque_page(')
                || $isRedirectOnly;
            self::assertTrue($hasProtection, sprintf('Admin route %s must enforce a permission or be an explicit redirect.', $route));

            if (!$isRedirectOnly && !isset($technicalAdminRoutes[$route])) {
                self::assertArrayHasKey($route, $catalogRoutes, sprintf('Admin route %s must be present in the admin dashboard catalog.', $route));
            }
        }
    }

    public function testAdminCommitteeUsesSingleFormAndSummaryTable(): void
    {
        $adminCommittee = file_get_contents(__DIR__ . '/../pages/admin_committee.php');
        self::assertIsString($adminCommittee);

        self::assertSame(1, substr_count($adminCommittee, '<form method="post"'));
        self::assertStringContainsString('name="member_id"', $adminCommittee);
        self::assertStringContainsString('id="admin-committee-form"', $adminCommittee);
        self::assertStringContainsString("UPDATE members SET is_committee = ?, committee_role = ?, committee_bio = ?, committee_sort_order = ? WHERE id = ?", $adminCommittee);
        self::assertStringContainsString("SELECT id FROM members WHERE is_active = 1 AND is_committee = 1 ORDER BY committee_sort_order ASC, callsign ASC", $adminCommittee);
        self::assertStringContainsString('UPDATE members SET committee_sort_order = ? WHERE id = ?', $adminCommittee);
        self::assertStringContainsString('$committeeRows = db()->query(', $adminCommittee);
        self::assertStringContainsString('SELECT id, callsign, full_name, avatar_path, photo_path, committee_role, committee_sort_order', $adminCommittee);
        self::assertStringContainsString('<table>', $adminCommittee);
        self::assertStringContainsString('class="admin-committee-avatar"', $adminCommittee);
        self::assertStringContainsString('member_avatar_src($row)', $adminCommittee);
        self::assertStringContainsString('form="admin-committee-form" name="committee_move" value="up:', $adminCommittee);
        self::assertStringContainsString('form="admin-committee-form" name="committee_move" value="down:', $adminCommittee);
        self::assertStringContainsString("route_url('admin_committee', ['member_id' => (int) \$row['id']])", $adminCommittee);
        self::assertStringNotContainsString("SELECT id, callsign, full_name, committee_role, committee_bio, committee_sort_order", $adminCommittee);
        self::assertStringNotContainsString("\$row['committee_bio']", $adminCommittee);
        self::assertStringNotContainsString('name="members[', $adminCommittee);
    }

    public function testPublicCommitteeReadsMembersManagedByAdminCommittee(): void
    {
        $committeeHelpers = file_get_contents(__DIR__ . '/../app/committee_helpers.php');
        self::assertIsString($committeeHelpers);

        self::assertStringContainsString('FROM members', $committeeHelpers);
        self::assertStringContainsString('is_committee = 1', $committeeHelpers);
        self::assertStringContainsString('committee_bio', $committeeHelpers);
        self::assertStringContainsString('committee_sort_order AS sort_order', $committeeHelpers);
        self::assertStringContainsString('committee_members', $committeeHelpers);
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
            'app/member_webotheque.php' => [
                'data-webotheque-modal-open="webotheque-link-dialog"',
                'data-webotheque-modal-open="webotheque-domain-dialog"',
                'data-webotheque-modal-open="webotheque-tag-dialog"',
                'data-webotheque-modal-open="admin-webotheque-link-dialog"',
            ],
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

    public function testWikiPageProposalReplacesDirectNewPageForModerators(): void
    {
        $wiki = file_get_contents(__DIR__ . '/../pages/wiki.php');
        $wikiPropose = file_get_contents(__DIR__ . '/../pages/wiki_propose.php');
        self::assertIsString($wiki);
        self::assertIsString($wikiPropose);

        self::assertStringNotContainsString("route_url('wiki_edit')", $wiki);
        self::assertStringContainsString("route_url('wiki_propose')", $wiki);
        self::assertStringContainsString("\$autoPublish = has_permission('wiki.moderate');", $wikiPropose);
        self::assertStringContainsString("\$status = \$autoPublish ? 'published' : 'pending';", $wikiPropose);
        self::assertStringContainsString("route_url('wiki_view', ['slug' => \$slug])", $wikiPropose);
    }

    public function testWikiCategoryProposalIsDirectForModeratorsAndPendingForMembers(): void
    {
        $wiki = file_get_contents(__DIR__ . '/../pages/wiki.php');
        self::assertIsString($wiki);

        self::assertStringContainsString("\$autoAccept = has_permission('wiki.moderate');", $wiki);
        self::assertStringContainsString("\$proposalStatus = \$autoAccept ? 'accepted' : 'pending';", $wiki);
        self::assertStringContainsString("if (!\$autoAccept) {", $wiki);
        self::assertStringContainsString("redirect('my_requests');", $wiki);
        self::assertStringContainsString('status = "accepted"', $wiki);
    }

    public function testWikiPendingCategoryProposalsAreManageableFromAdminWiki(): void
    {
        $adminWiki = file_get_contents(__DIR__ . '/../pages/admin_wiki.php');
        self::assertIsString($adminWiki);

        self::assertStringContainsString("require_permission('wiki.moderate');", $adminWiki);
        self::assertStringContainsString("\$pendingProposalUrl = route_url_clean('admin_wiki', ['status' => 'pending']) . '#pending-proposals';", $adminWiki);
        self::assertStringContainsString("\$action = (string) (\$_POST['action'] ?? 'update_page_status');", $adminWiki);
        self::assertStringContainsString("if (\$action === 'update_proposal_status')", $adminWiki);
        self::assertStringContainsString('UPDATE content_proposals SET status = ?, moderation_note = ? WHERE id = ? AND area = "wiki"', $adminWiki);
        self::assertStringContainsString('WHERE cp.area = "wiki" AND cp.status = "pending"', $adminWiki);
        self::assertStringContainsString('id="pending-proposals"', $adminWiki);
        self::assertStringContainsString('name="action" value="update_proposal_status"', $adminWiki);
        self::assertStringContainsString('name="proposal_status"', $adminWiki);
    }

    public function testPublicContentProposalAreasIncludeAlbumsAndAuctions(): void
    {
        $helpers = file_get_contents(__DIR__ . '/../app/content_helpers.php');
        $requests = file_get_contents(__DIR__ . '/../pages/my_requests.php');
        self::assertIsString($helpers);
        self::assertIsString($requests);

        self::assertStringContainsString("'albums' => true", $helpers);
        self::assertStringContainsString("'auctions' => true", $helpers);
        self::assertStringContainsString("'webotheque' => true", $helpers);
        self::assertStringContainsString("'albums' => 'albums'", $requests);
        self::assertStringContainsString("'auctions' => 'auctions'", $requests);
        self::assertStringContainsString("'webotheque' => 'webotheque'", $requests);
        self::assertStringContainsString("\$area === 'members_library' && \$proposalType === 'content' && \$proposalStatus === 'accepted'", $requests);
        self::assertStringContainsString("route_url_clean('members_library', ['q' => \$proposalTitle])", $requests);
        self::assertStringContainsString("route_url_clean('albums', ['q' => \$proposalTitle])", $requests);
        self::assertStringContainsString("route_url_clean('webotheque', ['q' => \$proposalTitle])", $requests);
        self::assertStringContainsString("route_url_clean('webotheque', ['category' => \$proposalTitle])", $requests);
    }

    public function testRequestedModulesAutoValidateForAdministratorsAndKeepMemberQueue(): void
    {
        $contracts = [
            'pages/events.php' => ["has_permission('events.manage')", "content_proposal_create((int) \$user['id'], 'events', 'content'", "redirect('my_requests')"],
            'pages/news.php' => ["has_permission('news.moderate')", "INSERT INTO news_posts", "INSERT INTO news_sections", "redirect('my_requests')"],
            'pages/albums.php' => ["has_permission('albums.manage')", "INSERT INTO albums", "content_proposal_create((int) \$user['id'], 'albums', 'content'", "redirect('my_requests')"],
            'pages/members_library.php' => ["has_permission('admin.access')", "INSERT INTO member_library_categories", "\$proposalStatus = \$autoAccept ? 'accepted' : 'pending';", "redirect('my_requests')"],
            'pages/auctions.php' => ["has_permission('auctions.manage')", "INSERT INTO auction_lots", "content_proposal_create((int) \$user['id'], 'auctions', 'content'", "redirect('my_requests')"],
            'pages/classifieds.php' => ["classifieds_can_moderate()", "\$proposalStatus = \$autoAccept ? 'accepted' : 'pending';", "redirect('my_requests')"],
            'pages/classifieds_manage.php' => ["content_proposal_accepted_categories('classifieds', 32)"],
            'app/member_webotheque.php' => ["data-webotheque-modal-open=\"webotheque-link-dialog\"", "data-webotheque-modal-open=\"webotheque-domain-dialog\"", "data-webotheque-modal-open=\"webotheque-tag-dialog\"", "data-webotheque-modal-open=\"admin-webotheque-link-dialog\"", "id=\"webotheque-link-dialog\"", "id=\"webotheque-domain-dialog\"", "id=\"webotheque-tag-dialog\"", "id=\"admin-webotheque-link-dialog\"", "\$proposalStatus = \$autoAccept ? 'accepted' : 'pending';", "content_proposal_create((int) \$user['id'], 'webotheque', 'content'", "content_proposal_create((int) \$user['id'], 'webotheque', 'domain'", "content_proposal_create((int) \$user['id'], 'webotheque', 'tag'", "webotheque_insert_link", "redirect('my_requests')"],
        ];

        foreach ($contracts as $relativePath => $needles) {
            $source = file_get_contents(__DIR__ . '/../' . $relativePath);
            self::assertIsString($source);
            foreach ($needles as $needle) {
                self::assertStringContainsString($needle, $source, sprintf('%s must contain %s.', $relativePath, $needle));
            }
        }
    }

    public function testProfileGeocodeConsentIsCheckedByDefault(): void
    {
        $profile = file_get_contents(__DIR__ . '/../pages/profile.php');
        self::assertIsString($profile);

        self::assertStringContainsString('name="allow_geocode" value="1" checked', $profile);
    }

    public function testSettingsRecommendationsCheckboxCanBeDisabled(): void
    {
        $settings = file_get_contents(__DIR__ . '/../pages/settings.php');
        self::assertIsString($settings);

        self::assertStringContainsString("\$enabled = isset(\$_POST['recommendations_enabled']) && (string) \$_POST['recommendations_enabled'] === '1';", $settings);
        self::assertStringNotContainsString("\$_POST['recommendations_enabled'] ?? '1'", $settings);
    }

    public function testProfileQrzUrlFieldIsEditable(): void
    {
        $profile = file_get_contents(__DIR__ . '/../pages/profile.php');
        self::assertIsString($profile);

        self::assertStringContainsString('name="qrz_url"', $profile);
        self::assertStringContainsString('$submittedQrzUrl = trim((string) ($_POST[\'qrz_url\'] ?? \'\'));', $profile);
        self::assertStringNotContainsString('name="qrz_url" maxlength="255" readonly', $profile);
    }

    public function testProfilePhotoUploadHasLivePreview(): void
    {
        $profile = file_get_contents(__DIR__ . '/../pages/profile.php');
        $profileJs = file_get_contents(__DIR__ . '/../assets/js/modules/profile.js');
        self::assertIsString($profile);
        self::assertIsString($profileJs);

        self::assertStringContainsString('$profilePhotoPreviewSrc = member_avatar_src($member);', $profile);
        self::assertStringContainsString('class="profile-photo-upload-grid"', $profile);
        self::assertStringContainsString('data-profile-photo-input', $profile);
        self::assertStringContainsString('data-profile-photo-preview', $profile);
        self::assertStringContainsString('URL.createObjectURL(file)', $profileJs);
        self::assertStringContainsString('URL.revokeObjectURL(previewObjectUrl)', $profileJs);
    }

    public function testProfileUsesSharedLicenceClassChoicesAndPublicRegisterHasNoRadioProfileForm(): void
    {
        $profile = file_get_contents(__DIR__ . '/../pages/profile.php');
        $register = file_get_contents(__DIR__ . '/../pages/register.php');
        $directory = file_get_contents(__DIR__ . '/../pages/directory.php');
        self::assertIsString($profile);
        self::assertIsString($register);
        self::assertIsString($directory);

        self::assertStringContainsString('$licenceClassOptionsHtml = member_profile_licence_class_options_html($t, (string) ($member[\'licence_class\'] ?? \'\'));', $profile);
        self::assertStringNotContainsString('name="licence_class"', $register);
        self::assertStringContainsString('<select name="licence_class"><?= $licenceClassOptionsHtml ?></select>', $profile);
        self::assertStringContainsString('member_profile_licence_class_display_text($profileT, $licenceValue)', $directory);
        self::assertStringContainsString('$licenceFilterLabel = member_profile_licence_class_display_text($profileT, $licenceFilter);', $directory);
        self::assertStringContainsString('$licenceClass = member_profile_licence_class_display_text($profileT, (string) ($member[\'licence_class\'] ?? \'\'));', $directory);
        self::assertStringNotContainsString('<option value="ON3">ON3</option>', $register);
        self::assertStringNotContainsString('<option value="ON2">ON2</option>', $register);
    }

    public function testDirectoryDisplaysPostalCodeThroughProfileFieldMetadata(): void
    {
        $directory = file_get_contents(__DIR__ . '/../pages/directory.php');
        self::assertIsString($directory);

        self::assertStringContainsString('$profilePreviewFields = member_profile_preview_fields($profileT);', $directory);
        self::assertStringContainsString("member_profile_display_row(\$member, 'postal_code', \$profilePreviewFields['postal_code'])", $directory);
        self::assertStringContainsString("<?= e((string) \$postalCodeRow['label']) ?> <?= e((string) \$postalCodeRow['text']) ?>", $directory);
        self::assertStringNotContainsString('$addDetail((string) $postalCodeRow[\'label\'], (string) $postalCodeRow[\'text\']);', $directory);
    }

    public function testDirectorySeparatesRadioModesAndBands(): void
    {
        $directory = file_get_contents(__DIR__ . '/../pages/directory.php');
        $css = file_get_contents(__DIR__ . '/../assets/css/app.css');
        self::assertIsString($directory);
        self::assertIsString($css);

        self::assertStringContainsString('class="directory-radio-row"', $directory);
        self::assertStringContainsString('class="directory-mode-list"', $directory);
        self::assertStringContainsString('class="directory-band-list"', $directory);
        self::assertStringContainsString('.directory-radio-row', $css);
        self::assertStringContainsString('.directory-mode-list > span', $css);
        self::assertStringContainsString('justify-content: flex-end;', $css);
    }

    public function testGdprReusesProfileFieldsAndVisibilitySettings(): void
    {
        $gdpr = file_get_contents(__DIR__ . '/../pages/gdpr.php');
        self::assertIsString($gdpr);

        self::assertStringContainsString('member_profile_select_columns_sql()', $gdpr);
        self::assertStringContainsString('member_profile_visibility_fields($t)', $gdpr);
        self::assertStringContainsString('member_profile_preview_rows($member, (string) $viewer, $t, true)', $gdpr);
        self::assertStringContainsString('$gt(\'profile_data_title\')', $gdpr);
        self::assertStringContainsString('$gt(\'profile_data_help\')', $gdpr);
        self::assertStringContainsString('gdpr-callsign', $gdpr);
    }

    public function testProfileUsesSharedQslViaChoicesAndPublicRegisterHasNoQslForm(): void
    {
        $profile = file_get_contents(__DIR__ . '/../pages/profile.php');
        $register = file_get_contents(__DIR__ . '/../pages/register.php');
        $directory = file_get_contents(__DIR__ . '/../pages/directory.php');
        self::assertIsString($profile);
        self::assertIsString($register);
        self::assertIsString($directory);

        self::assertStringContainsString('$qslViaOptionsHtml = member_profile_qsl_via_options_html($t, (string) ($member[\'qsl_via\'] ?? \'\'));', $profile);
        self::assertStringContainsString('<select name="qsl_via"><?= $qslViaOptionsHtml ?></select>', $profile);
        self::assertStringNotContainsString('name="qsl_via"', $register);
        self::assertStringContainsString("member_profile_display_row(\$member, 'qsl_via', \$profilePreviewFields['qsl_via'])", $directory);
        self::assertStringContainsString("<?= e((string) \$qslViaRow['label']) ?> <?= e((string) \$qslViaRow['text']) ?>", $directory);
        self::assertStringNotContainsString('<input type="text" name="qsl_via"', $profile);
        self::assertStringNotContainsString('<input type="text" name="qsl_via"', $register);
    }
}
