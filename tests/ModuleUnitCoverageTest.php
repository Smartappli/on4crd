<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/layout_renderer.php';
require_once __DIR__ . '/../app/member_module_documents.php';

final class ModuleUnitCoverageTest extends TestCase
{
    /**
     * @var array<string, list<string>>
     */
    private const MODULE_TEST_FILES = [
        'admin' => ['tests/RouterContractTest.php', 'tests/I18nNativeLocalesTest.php'],
        'advertising' => ['tests/RouterContractTest.php', 'tests/I18nNativeLocalesTest.php'],
        'albums' => ['tests/AlbumHelpersTest.php', 'tests/MemberModulesFinalizationTest.php', 'tests/RouterContractTest.php'],
        'articles' => ['tests/FunctionHelpersExtendedTest.php', 'tests/RouterContractTest.php'],
        'auctions' => ['tests/FunctionHelpersTest.php', 'tests/RouterContractTest.php'],
        'chatbot' => ['tests/ChatbotI18nTest.php', 'tests/ChatbotRagRetrievalTest.php'],
        'classifieds' => ['tests/FunctionHelpersExtendedTest.php', 'tests/RouterContractTest.php'],
        'committee' => ['tests/RouterContractTest.php', 'tests/I18nNativeLocalesTest.php'],
        'dashboard' => ['tests/FunctionHelpersTest.php', 'tests/RouterContractTest.php'],
        'directory' => ['tests/FunctionHelpersExtendedTest.php', 'tests/RouterContractTest.php'],
        'education' => ['tests/RouterContractTest.php', 'tests/I18nNativeLocalesTest.php'],
        'events' => ['tests/RouterContractTest.php', 'tests/I18nNativeLocalesTest.php'],
        'fichiers' => ['tests/MemberModulesFinalizationTest.php', 'tests/RouterContractTest.php'],
        'members' => ['tests/FunctionHelpersExtendedTest.php', 'tests/MemberModulesFinalizationTest.php', 'tests/MemberLibraryHelpersTest.php', 'tests/NotificationContractsTest.php'],
        'news' => ['tests/RouterContractTest.php', 'tests/I18nNativeLocalesTest.php'],
        'presentations' => ['tests/MemberModulesFinalizationTest.php', 'tests/RouterContractTest.php'],
        'press' => ['tests/FunctionHelpersTest.php', 'tests/RouterContractTest.php'],
        'pv' => ['tests/MemberModulesFinalizationTest.php', 'tests/RouterContractTest.php'],
        'qsl' => ['tests/QslHelpersTest.php', 'tests/QslHelpersExtendedTest.php'],
        'tools' => ['tests/ToolsPanelsContractTest.php', 'tests/ToolsI18nTest.php'],
        'videos' => ['tests/MemberModulesFinalizationTest.php', 'tests/RouterContractTest.php'],
        'webotheque' => ['tests/WebothequeHelpersTest.php', 'tests/MemberModulesFinalizationTest.php'],
        'wiki' => ['tests/FunctionHelpersTest.php', 'tests/RouterContractTest.php'],
    ];

    public function testEverySeededModuleHasDeclaredUnitCoverage(): void
    {
        $seededModules = $this->seededModules();
        $coveredModules = array_keys(self::MODULE_TEST_FILES);
        sort($seededModules);
        sort($coveredModules);

        self::assertSame($seededModules, $coveredModules);

        foreach (self::MODULE_TEST_FILES as $module => $testFiles) {
            self::assertNotSame([], $testFiles, sprintf('Module %s has no declared test file.', $module));
            foreach ($testFiles as $testFile) {
                $path = dirname(__DIR__) . '/' . $testFile;
                self::assertFileExists($path, sprintf('Module %s references missing test file %s.', $module, $testFile));

                $source = file_get_contents($path);
                self::assertIsString($source);
                self::assertStringContainsString('extends TestCase', $source, sprintf('%s must be a PHPUnit test file.', $testFile));
                self::assertMatchesRegularExpression('/assert[A-Z]/', $source, sprintf('%s must contain assertions.', $testFile));
            }
        }
    }

    public function testEverySeededModuleHasAtLeastOneRouteGate(): void
    {
        $routeModules = $this->routeModules();
        $mappedModules = array_values(array_unique(array_values($routeModules)));

        foreach ($this->seededModules() as $module) {
            self::assertContains($module, $mappedModules, sprintf('Seeded module %s is not used by any route gate.', $module));
        }
    }

    public function testRequestedFinalizedModulesHaveDedicatedRegressionTests(): void
    {
        $finalizationTest = $this->source('tests/MemberModulesFinalizationTest.php');

        foreach ([
            'testAlbumsModuleKeepsUploadSelectAndProposalMetadataControls',
            'testWebothequeModuleKeepsCategorySelectsAndKeywordInputs',
            'testMembersLibraryModuleKeepsDocumentTopicSelectAndKeywords',
            'testSharedDocumentModulesAreDeclaredDispatchedAndTagged',
            'testIdeaModuleUsesTopicSelectKeywordsAndSubmitsThem',
        ] as $testMethod) {
            self::assertStringContainsString('function ' . $testMethod . '(', $finalizationTest);
        }
    }

    public function testRequestedModulesHaveSeleniumCoverageAcrossPublicMemberAndAdminSurfaces(): void
    {
        $seleniumSources = [
            'public_routes' => $this->source('tests/selenium/public-routes.test.js'),
            'protected_routes' => $this->source('tests/selenium/protected-routes.test.js'),
            'authenticated_routes' => $this->source('tests/selenium/authenticated-routes.test.js'),
            'member_workflows' => $this->source('tests/selenium/member-workflows.test.js'),
            'member_documents' => $this->source('tests/selenium/member-document-modules.test.js'),
            'member_pending' => $this->source('tests/selenium/member-pending-validation.test.js'),
            'admin_crud' => $this->source('tests/selenium/admin-module-crud-workflows.test.js'),
        ];

        self::assertStringContainsString("['albums', {}]", $seleniumSources['public_routes']);

        foreach ([
            'members_library',
            'webotheque',
            'presentations',
            'videos',
            'pv',
            'fichiers',
            'telechargements',
        ] as $route) {
            self::assertStringContainsString("'" . $route . "'", $seleniumSources['protected_routes'], $route . ' must be protected when public.');
            self::assertStringContainsString("'" . $route . "'", $seleniumSources['authenticated_routes'], $route . ' must be reachable when authenticated.');
        }

        foreach ([
            'admin_albums',
            'admin_library',
            'admin_webotheque',
            'admin_presentations',
            'admin_videos',
            'admin_pv',
            'admin_fichiers',
            'admin_telechargements',
        ] as $route) {
            self::assertStringContainsString("'" . $route . "'", $seleniumSources['protected_routes'], $route . ' must be admin-protected.');
            self::assertStringContainsString("'" . $route . "'", $seleniumSources['authenticated_routes'], $route . ' must be reachable by admin.');
        }

        $workflowContracts = [
            'albums member CRUD' => ['member_workflows', 'Selenium membre: creer modifier supprimer un album'],
            'albums member approval' => ['member_pending', 'Selenium membre: proposer un album, le valider et le retrouver dans les albums'],
            'albums taxonomy approval' => ['member_pending', 'Selenium membre: proposer une thematique albums'],
            'albums admin CRUD' => ['admin_crud', 'Selenium admin: modifier et supprimer albums et webotheque'],
            'webotheque member CRUD' => ['member_workflows', 'Selenium membre: creer, modifier et supprimer un lien webotheque'],
            'webotheque member approval' => ['member_pending', 'Selenium membre: proposer un lien webotheque, le valider'],
            'webotheque taxonomy approval' => ['member_pending', 'Selenium membre: proposer une thematique webotheque'],
            'webotheque admin CRUD' => ['admin_crud', 'Selenium admin: modifier et supprimer albums et webotheque'],
            'library member CRUD' => ['member_workflows', 'Le document members_library supprime cote membre'],
            'library member approval' => ['member_pending', 'Selenium membre: proposer un document bibliotheque, le valider'],
            'library taxonomy approval' => ['member_pending', 'Selenium membre: proposer une categorie members_library'],
            'library admin CRUD' => ['admin_crud', 'Selenium admin: modifier et supprimer presentations, videos et member_library'],
            'presentations member CRUD' => ['member_documents', "for (const moduleCode of ['presentations', 'videos'])"],
            'presentations taxonomy CRUD' => ['member_documents', 'Selenium modules documents: taxonomy, upload, favoris, edition et suppression presentations'],
            'presentations member approval' => ['member_pending', 'Selenium membre: proposer une presentation, la valider'],
            'videos member CRUD' => ['member_documents', "for (const moduleCode of ['presentations', 'videos'])"],
            'videos member approval' => ['member_pending', 'Selenium membre: proposer une video, la valider'],
            'document admin CRUD' => ['admin_crud', 'Selenium admin: modifier et supprimer presentations, videos et member_library'],
            'pv admin to member lifecycle' => ['member_documents', "for (const moduleCode of ['pv', 'fichiers'])"],
            'fichiers admin to member lifecycle' => ['member_documents', 'L alias telechargements doit afficher les fichiers.'],
            'taxonomy admin CRUD' => ['admin_crud', 'Selenium admin: ajouter modifier supprimer thematiques et sous-thematiques modules'],
        ];

        foreach ($workflowContracts as $label => [$sourceKey, $snippet]) {
            self::assertStringContainsString($snippet, $seleniumSources[$sourceKey], $label . ' Selenium coverage is missing.');
        }
    }

    public function testDocumentModulePermissionAndAssetContractsMatchMemberSurface(): void
    {
        $definitions = member_document_module_definitions();

        foreach ([
            'presentations' => ['route' => 'presentations', 'admin_route' => 'admin_presentations', 'member_management' => true],
            'videos' => ['route' => 'videos', 'admin_route' => 'admin_videos', 'member_management' => true],
            'fichiers' => ['route' => 'fichiers', 'admin_route' => 'admin_fichiers', 'member_management' => false],
            'pv' => ['route' => 'pv', 'admin_route' => 'admin_pv', 'member_management' => false],
        ] as $module => $contract) {
            self::assertArrayHasKey($module, $definitions);
            self::assertSame($contract['route'], $definitions[$module]['route'] ?? null);
            self::assertSame($contract['admin_route'], $definitions[$module]['admin_route'] ?? null);
            self::assertSame($contract['member_management'], member_document_module_allows_member_management($module));
        }

        self::assertSame('fichiers', member_document_module_normalize('telechargements'));
        self::assertFalse(member_document_module_allows_member_management('telechargements'));

        foreach (['pv', 'fichiers', 'telechargements'] as $route) {
            self::assertSame(
                ['assets/css/modules/shared.css', 'assets/css/modules/member_documents.css'],
                module_css_assets_for_route($route)
            );
            self::assertSame([], module_js_assets_for_route($route));
        }

        foreach (['admin_pv', 'admin_fichiers', 'admin_telechargements'] as $route) {
            self::assertSame(
                ['assets/css/modules/shared.css', 'assets/css/modules/admin.css', 'assets/css/modules/admin_member_documents.css'],
                module_css_assets_for_route($route)
            );
            self::assertContains('assets/js/modules/wysiwyg.js', module_js_assets_for_route($route));
        }
    }

    public function testMembersLibraryAndNotificationsKeepDatabaseLifecycleCoverage(): void
    {
        $memberLibraryTest = $this->source('tests/MemberLibraryHelpersTest.php');
        $notificationTest = $this->source('tests/NotificationContractsTest.php');

        foreach ([
            'testAcceptedProposalAppliesFullDocumentLifecycleAgainstDatabase',
            'testExtractTextNormalizesPlainHtmlAndMissingFiles',
            'testProposalSourcePathAcceptsLibraryReferencesAndRejectsUnsafePaths',
            'testDefaultTaxonomyAndIngestionTemplatesUseControlledVocabulary',
        ] as $testMethod) {
            self::assertStringContainsString('function ' . $testMethod . '(', $memberLibraryTest);
        }

        self::assertStringContainsString(
            'function testMemberNotificationHelpersCoverCreateReadAndMarkLifecycleAgainstDatabase(',
            $notificationTest
        );
    }

    public function testSharedModuleCssIsLoadedBeforeRouteSpecificCss(): void
    {
        foreach (['albums', 'members_library', 'news', 'webotheque', 'wiki'] as $route) {
            $assets = module_css_assets_for_route($route);

            self::assertGreaterThanOrEqual(2, count($assets), sprintf('Route %s must have shared and route CSS assets.', $route));
            self::assertSame('assets/css/modules/shared.css', $assets[0], sprintf('Route %s must load shared module CSS first.', $route));
        }
    }

    public function testSharedDialogJsIsLoadedBeforeDialogModules(): void
    {
        $routes = [
            'albums' => 'albums',
            'articles' => 'articles',
            'article_propose' => 'articles',
            'members_library' => 'members_library',
            'news' => 'news',
            'webotheque' => 'webotheque',
            'admin_webotheque' => 'webotheque',
            'wiki' => 'wiki',
            'wiki_view' => 'wiki',
            'presentations' => 'member_documents',
            'videos' => 'member_documents',
        ];

        foreach ($routes as $route => $module) {
            $assets = module_js_assets_for_route($route);
            $sharedIndex = array_search('assets/js/modules/module_dialogs.js', $assets, true);
            $moduleIndex = array_search('assets/js/modules/' . $module . '.js', $assets, true);

            self::assertIsInt($sharedIndex, sprintf('Route %s must load the shared dialog helper.', $route));
            self::assertIsInt($moduleIndex, sprintf('Route %s must load its dialog module.', $route));
            self::assertLessThan($moduleIndex, $sharedIndex, sprintf('Route %s must load the shared dialog helper first.', $route));
        }
    }

    public function testBandplanPagesUseSharedRenderer(): void
    {
        self::assertStringContainsString('function render_bandplan_page(', $this->source('app/layout_renderer.php'));

        foreach (['bandplan_harec', 'bandplan_on2'] as $route) {
            self::assertStringContainsString(
                'echo render_bandplan_page($title, $t, $rows);',
                $this->source('pages/' . $route . '.php')
            );
        }

        self::assertStringContainsString(
            "echo render_bandplan_page(\$title, \$t, \$rows, 'bandplan-on3-module');",
            $this->source('pages/bandplan_on3.php')
        );
    }

    /**
     * @return list<string>
     */
    private function seededModules(): array
    {
        $schema = $this->source('app/runtime_schema.php');
        preg_match_all(
            "/\\['([a-z0-9_]+)',\\s*'[^']+',\\s*'[^']+',\\s*[01],\\s*[01],\\s*'(?:public|members|admin)'/",
            $schema,
            $matches
        );

        $modules = array_values(array_unique($matches[1]));
        self::assertNotSame([], $modules);

        return $modules;
    }

    /**
     * @return array<string, string>
     */
    private function routeModules(): array
    {
        $router = $this->source('index.php');
        preg_match('/\\$routeModules = \\[(.*?)\\];/s', $router, $match);
        self::assertNotEmpty($match);

        preg_match_all("/'([^']+)'\\s*=>\\s*'([^']+)'/", $match[1], $matches, PREG_SET_ORDER);
        $routeModules = [];
        foreach ($matches as $routeMatch) {
            $routeModules[(string) $routeMatch[1]] = (string) $routeMatch[2];
        }

        return $routeModules;
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(dirname(__DIR__) . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        self::assertIsString($source);

        return $source;
    }
}
