<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class I18nNativeLocalesTest extends TestCase
{
    /**
     * @return list<string>
     */
    private function supportedLocales(): array
    {
        return supported_locales();
    }

    public function testSupportedLocaleSetContainsConfiguredLocales(): void
    {
        self::assertSame(['fr', 'en', 'de', 'nl', 'it', 'es', 'pt', 'bg', 'hr', 'cs', 'da', 'et', 'fi', 'el', 'hu', 'ga', 'lv', 'lt', 'mt', 'pl', 'ro', 'sk', 'sl', 'sv', 'ar', 'hi', 'ja', 'zh', 'bn', 'ru', 'id'], $this->supportedLocales());
    }

    public function testOnlyArabicUsesRtlLayoutDirection(): void
    {
        foreach ($this->supportedLocales() as $locale) {
            self::assertSame($locale === 'ar', is_rtl_locale($locale), sprintf('Unexpected text direction for locale %s', $locale));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function loadLocaleFile(string $path): array
    {
        $t = static fn (string $key): string => $key;
        $messages = require $path;

        self::assertIsArray($messages);

        return $messages;
    }

    public function testEveryModularI18nDomainHasAllNativeLocaleFiles(): void
    {
        $directories = glob(__DIR__ . '/../app/i18n/*', GLOB_ONLYDIR);
        self::assertIsArray($directories);
        self::assertNotEmpty($directories);

        foreach ($directories as $directory) {
            $domain = basename((string) $directory);
            foreach ($this->supportedLocales() as $locale) {
                self::assertFileExists(
                    $directory . '/' . $locale . '.php',
                    sprintf('Missing native locale file app/i18n/%s/%s.php', $domain, $locale)
                );
            }
        }
    }

    public function testEveryModularI18nDomainKeepsLocaleKeyParity(): void
    {
        $directories = glob(__DIR__ . '/../app/i18n/*', GLOB_ONLYDIR);
        self::assertIsArray($directories);

        foreach ($directories as $directory) {
            $reference = $this->loadLocaleFile($directory . '/fr.php');
            $referenceKeys = array_keys($reference);
            sort($referenceKeys);

            foreach ($this->supportedLocales() as $locale) {
                $candidate = $this->loadLocaleFile($directory . '/' . $locale . '.php');
                $candidateKeys = array_keys($candidate);
                sort($candidateKeys);

                self::assertSame(
                    $referenceKeys,
                    $candidateKeys,
                    sprintf('Locale key mismatch in app/i18n/%s/%s.php', basename((string) $directory), $locale)
                );
            }
        }
    }

    public function testHomePageUsesDefinedHomeI18nKeys(): void
    {
        $homePage = file_get_contents(__DIR__ . '/../pages/home.php');
        self::assertIsString($homePage);

        preg_match_all('/\$homeI18n\[[\'"]([^\'"]+)[\'"]\]/', $homePage, $matches);
        $usedKeys = array_values(array_unique($matches[1] ?? []));
        sort($usedKeys);

        self::assertNotEmpty($usedKeys);

        foreach ($this->supportedLocales() as $locale) {
            $messages = $this->loadLocaleFile(__DIR__ . '/../app/i18n/home/' . $locale . '.php');
            $missingKeys = array_values(array_diff($usedKeys, array_keys($messages)));

            self::assertSame(
                [],
                $missingKeys,
                sprintf('Missing home i18n keys used by pages/home.php in app/i18n/home/%s.php', $locale)
            );
        }
    }

    public function testHomeMemberModuleTranslationKeysExistForEveryLocale(): void
    {
        $homePage = file_get_contents(__DIR__ . '/../pages/home.php');
        self::assertIsString($homePage);

        preg_match_all('/[\'"](member_module_[a-z0-9_]+)[\'"]/', $homePage, $matches);
        $memberModuleKeys = array_values(array_unique($matches[1] ?? []));
        sort($memberModuleKeys);

        self::assertCount(16, $memberModuleKeys);

        foreach ($this->supportedLocales() as $locale) {
            $messages = $this->loadLocaleFile(__DIR__ . '/../app/i18n/home/' . $locale . '.php');
            $missingKeys = array_values(array_diff($memberModuleKeys, array_keys($messages)));

            self::assertSame(
                [],
                $missingKeys,
                sprintf('Missing member module home i18n keys in app/i18n/home/%s.php', $locale)
            );

            foreach ($memberModuleKeys as $key) {
                self::assertNotSame('', trim((string) ($messages[$key] ?? '')), sprintf('Empty member module translation %s in app/i18n/home/%s.php', $key, $locale));
            }
        }
    }

    public function testHomeDonationLinkTargetsDedicatedDonationRoute(): void
    {
        $homePage = file_get_contents(__DIR__ . '/../pages/home.php');
        self::assertIsString($homePage);

        self::assertMatchesRegularExpression(
            '/route_url\(\'donation\'\)\)\s*\.\s*\'">\'\s*\.\s*e\(\(string\)\s*\$homeI18n\[\'link_donate\'\]\)/',
            $homePage,
            'The "Faire un don" home link must target the dedicated donation page, not membership or sponsoring.'
        );
    }

    public function testEveryVisibleRoutedPageUsesAModularI18nDomain(): void
    {
        $router = file_get_contents(__DIR__ . '/../index.php');
        self::assertIsString($router);
        preg_match_all(
            '/case \'([^\']+)\':\s*\$dispatchPage\(\'([^\']+)\'\);\s*break;/',
            $router,
            $routeMatches,
            PREG_SET_ORDER
        );

        $domainFiles = glob(__DIR__ . '/../app/i18n/*.php') ?: [];
        $domainDirectories = glob(__DIR__ . '/../app/i18n/*', GLOB_ONLYDIR) ?: [];
        $domains = [];
        foreach (array_merge($domainFiles, $domainDirectories) as $path) {
            $domains[basename((string) $path, '.php')] = true;
        }

        $technicalPages = [
            'ad_click',
            'admin_events_feed',
            'ai_index',
            'dashboard_widget_card',
            'events_feed',
            'knowledge_graph',
            'llms',
            'newsletter_unsubscribe',
            'qsl_export',
            'qsl_preview',
            'robots',
            'save_dashboard',
            'sitemap',
            'tools_geocode',
            'widget_render',
        ];
        $missing = [];

        foreach ($routeMatches as $match) {
            $route = (string) $match[1];
            $relativePath = (string) $match[2];
            if (!str_starts_with($relativePath, 'pages/')) {
                continue;
            }

            $page = basename($relativePath, '.php');
            if (in_array($page, $technicalPages, true) || in_array($route, $technicalPages, true)) {
                continue;
            }

            $source = file_get_contents(__DIR__ . '/../' . $relativePath);
            self::assertIsString($source);
            preg_match_all('/i18n_domain_(?:locale|translator|messages)\([\'"]([^\'"]+)[\'"]/', $source, $domainMatches);
            preg_match_all('/t_page\([\'"]([^\'"]+)[\'"]/', $source, $tPageMatches);
            preg_match_all('#/i18n/([a-z0-9_]+)(?:\.php|/)#', $source, $requireMatches);

            $usedDomains = array_unique(array_merge(
                $domainMatches[1] ?? [],
                $tPageMatches[1] ?? [],
                $requireMatches[1] ?? []
            ));
            if (str_contains($source, 'admin_dashboard_translations(')) {
                $usedDomains[] = 'admin';
            }

            $usedDomains = array_values(array_filter(
                $usedDomains,
                static fn(string $domain): bool => isset($domains[$domain])
            ));
            if ($usedDomains === []) {
                $missing[] = $route . ' -> ' . $relativePath;
            }
        }

        self::assertSame([], $missing);
    }

    public function testAdminModuleCardsUseModularI18nDomain(): void
    {
        $moduleCatalog = file_get_contents(__DIR__ . '/../app/module_catalog.php');
        self::assertIsString($moduleCatalog);
        self::assertStringContainsString("i18n_domain_messages('admin_module_cards')", $moduleCatalog);

        $messages = i18n_domain_messages('admin_module_cards');
        self::assertNotEmpty($messages);

        foreach (admin_module_cards_catalog() as $card) {
            $route = (string) ($card['route'] ?? '');
            self::assertNotSame('', $route);
            foreach ($this->supportedLocales() as $locale) {
                self::assertNotSame('', trim((string) ($messages[$locale][$route . '_title'] ?? '')), sprintf('Missing admin module title for %s in %s.', $route, $locale));
                self::assertNotSame('', trim((string) ($messages[$locale][$route . '_desc'] ?? '')), sprintf('Missing admin module description for %s in %s.', $route, $locale));
            }
        }
    }

    public function testFrenchLocaleFilesAreValidUtf8AndReadable(): void
    {
        $files = glob(__DIR__ . '/../app/i18n/*/fr.php');
        self::assertIsArray($files);
        self::assertNotEmpty($files);

        foreach ($files as $file) {
            $contents = file_get_contents((string) $file);
            self::assertIsString($contents);

            self::assertSame(1, preg_match('//u', $contents), sprintf('French locale file is not valid UTF-8: %s', $file));
            self::assertStringNotContainsString("\u{FFFD}", $contents, sprintf('French locale file contains replacement characters: %s', $file));

            $messages = $this->loadLocaleFile((string) $file);
            self::assertNotEmpty($messages, sprintf('French locale file is not readable as a message array: %s', $file));
        }
    }
}
