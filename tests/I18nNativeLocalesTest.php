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
        $messages = require $path; // NOSONAR - locale loader returns array data.

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

    public function testEveryI18nDomainLoaderHasNativeLocaleDirectory(): void
    {
        $domainLoaders = glob(__DIR__ . '/../app/i18n/*.php');
        self::assertIsArray($domainLoaders);
        self::assertNotEmpty($domainLoaders);

        foreach ($domainLoaders as $loaderPath) {
            $domain = basename((string) $loaderPath, '.php');
            $directory = __DIR__ . '/../app/i18n/' . $domain;

            self::assertDirectoryExists($directory, sprintf('Missing native locale directory app/i18n/%s', $domain));
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
        $usedKeys = array_values(array_unique($matches[1]));
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
        $memberModuleKeys = array_values(array_unique($matches[1]));
        sort($memberModuleKeys);

        self::assertNotEmpty($memberModuleKeys);
        self::assertSame(0, count($memberModuleKeys) % 2, 'Member module title and description keys must stay paired.');

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
            'member_document_preview',
            'member_library_preview',
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
            preg_match_all('/render_(?:admin_)?member_document_module_page\([\'"]([^\'"]+)[\'"]\)/', $source, $memberDocumentMatches);

            $usedDomains = array_unique(array_merge(
                $domainMatches[1],
                $tPageMatches[1],
                $requireMatches[1],
                $memberDocumentMatches[1]
            ));
            if (str_contains($source, 'render_webotheque_page(') || str_contains($source, 'render_admin_webotheque_page(')) {
                $usedDomains[] = 'webotheque';
            }
            if (str_contains($source, 'admin_dashboard_translations(')) {
                $usedDomains[] = 'admin';
            }
            if (preg_match('/^\s*<\?php\s+declare\(strict_types=1\);\s+redirect\([\'"][^\'"]+[\'"]\);\s*$/s', $source) === 1) {
                $usedDomains[] = '__redirect_only__';
            }

            $usedDomains = array_values(array_filter(
                $usedDomains,
                static fn(string $domain): bool => $domain === '__redirect_only__' || isset($domains[$domain])
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
            $route = (string) $card['route'];
            self::assertNotSame('', $route);
            foreach ($this->supportedLocales() as $locale) {
                self::assertNotSame('', trim((string) ($messages[$locale][$route . '_title'] ?? '')), sprintf('Missing admin module title for %s in %s.', $route, $locale));
                self::assertNotSame('', trim((string) ($messages[$locale][$route . '_desc'] ?? '')), sprintf('Missing admin module description for %s in %s.', $route, $locale));
            }
        }
    }

    public function testDirectLocaleFileReadsDoNotPoisonDomainLoaderCache(): void
    {
        $directMessages = $this->loadLocaleFile(__DIR__ . '/../app/i18n/admin_module_cards/fr.php');
        self::assertSame('Modules', $directMessages['admin_modules_title'] ?? null);

        $domainMessages = i18n_domain_messages('admin_module_cards');
        self::assertSame('Modules', $domainMessages['fr']['admin_modules_title'] ?? null);
        self::assertSame('Modules', $domainMessages['en']['admin_modules_title'] ?? null);
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
            foreach (['Ã', 'â€™', 'â€œ', 'â€', 'â€“', 'â€”', 'â€¦', 'Â°', 'Âµ', 'Â·', 'Â '] as $fragment) {
                self::assertStringNotContainsString($fragment, $contents, sprintf('French locale file contains mojibake fragment %s: %s', $fragment, $file));
            }

            $messages = $this->loadLocaleFile((string) $file);
            self::assertNotEmpty($messages, sprintf('French locale file is not readable as a message array: %s', $file));
        }
    }

    public function testEveryLocaleFileIsValidUtf8WithoutEncodingArtifacts(): void
    {
        $files = glob(__DIR__ . '/../app/i18n/*/*.php');
        self::assertIsArray($files);
        self::assertNotEmpty($files);

        $mojibakeFragments = [
            "\u{FFFD}",
            "\u{00C3}\u{00A0}",
            "\u{00C3}\u{00A2}",
            "\u{00C3}\u{00A4}",
            "\u{00C3}\u{00A7}",
            "\u{00C3}\u{00A8}",
            "\u{00C3}\u{00A9}",
            "\u{00C3}\u{00AA}",
            "\u{00C3}\u{00AB}",
            "\u{00C3}\u{00AE}",
            "\u{00C3}\u{00AF}",
            "\u{00C3}\u{00B4}",
            "\u{00C3}\u{00B6}",
            "\u{00C3}\u{00B9}",
            "\u{00C3}\u{00BB}",
            "\u{00C3}\u{00BC}",
            "\u{00C2}\u{00A0}",
            "\u{00C2}\u{00B0}",
            "\u{00E2}\u{20AC}\u{2122}",
            "\u{00E2}\u{20AC}\u{0153}",
            "\u{00E2}\u{20AC}\u{009D}",
            "\u{00E2}\u{20AC}\u{201C}",
        ];

        foreach ($files as $file) {
            $contents = file_get_contents((string) $file);
            self::assertIsString($contents);

            self::assertSame(1, preg_match('//u', $contents), sprintf('Locale file is not valid UTF-8: %s', $file));
            self::assertFalse(str_starts_with($contents, "\xEF\xBB\xBF"), sprintf('Locale file contains a UTF-8 BOM: %s', $file));
            foreach ($mojibakeFragments as $fragment) {
                self::assertStringNotContainsString($fragment, $contents, sprintf('Locale file contains encoding artifact %s: %s', bin2hex($fragment), $file));
            }

            $messages = $this->loadLocaleFile((string) $file);
            self::assertNotEmpty($messages, sprintf('Locale file is not readable as a message array: %s', $file));
        }
    }

    public function testFrenchAndEnglishLocaleFilesAreCompleteReferences(): void
    {
        $directories = glob(__DIR__ . '/../app/i18n/*', GLOB_ONLYDIR);
        self::assertIsArray($directories);
        self::assertNotEmpty($directories);

        $assertNotEmptyTranslations = static function (array $messages, string $locale, string $domain, string $prefix = '') use (&$assertNotEmptyTranslations): void {
            foreach ($messages as $key => $value) {
                $fullKey = $prefix === '' ? (string) $key : $prefix . '.' . (string) $key;
                if (is_array($value)) {
                    self::assertNotEmpty($value, sprintf('Empty %s reference translation group for %s in app/i18n/%s', $locale, $fullKey, $domain));
                    $assertNotEmptyTranslations($value, $locale, $domain, $fullKey);
                    continue;
                }

                self::assertNotSame('', trim((string) $value), sprintf('Empty %s reference translation for %s in app/i18n/%s', $locale, $fullKey, $domain));
            }
        };

        foreach ($directories as $directory) {
            $fr = $this->loadLocaleFile($directory . '/fr.php');
            $en = $this->loadLocaleFile($directory . '/en.php');
            $domain = basename((string) $directory);
            $frKeys = array_keys($fr);
            $enKeys = array_keys($en);
            sort($frKeys);
            sort($enKeys);

            self::assertSame($frKeys, $enKeys, sprintf('French/English key mismatch in app/i18n/%s', $domain));

            foreach (['fr' => $fr, 'en' => $en] as $locale => $messages) {
                $assertNotEmptyTranslations($messages, $locale, $domain);
            }
        }
    }

    public function testAdminLocaleFilesDoNotKeepFrenchOrEnglishFallbackStrings(): void
    {
        $issues = [];
        $technicalValues = array_fill_keys([
            '',
            'ID',
            'URL',
            'API',
            'CSV',
            'HTML',
            'JSON',
            'PDF',
            'RSS',
            'GDPR',
            'RGPD',
            'ON4CRD',
            'QSL',
            'UBA',
            'LoTW',
            'eQSL',
            'LoTW + eQSL',
            'QRZ',
            'QRZ.com',
            'AM',
            'FM',
            'SSB',
            'CW',
            'FT8',
            'FT4',
            'DMR',
            'D-STAR',
            'APRS',
            'RTTY',
            'SSTV',
            'WSPR',
            'HAREC',
            'Dashboard',
            'Wiki',
            'Newsletter',
            'Newsletters',
            'Administration',
            'Admin',
            'OpenAI',
            'DeepL',
            'Matomo',
        ], true);

        foreach (glob(__DIR__ . '/../app/i18n/admin*', GLOB_ONLYDIR) ?: [] as $directory) {
            $domain = basename((string) $directory);
            $frPath = $directory . '/fr.php';
            $enPath = $directory . '/en.php';
            if (!is_file($frPath) || !is_file($enPath)) {
                continue;
            }

            $fr = $this->flattenAdminLocaleValues($this->loadLocaleFile($frPath));
            $en = $this->flattenAdminLocaleValues($this->loadLocaleFile($enPath));
            foreach ($this->supportedLocales() as $locale) {
                if ($locale === 'fr' || $locale === 'en') {
                    continue;
                }
                $path = $directory . '/' . $locale . '.php';
                $messages = $this->flattenAdminLocaleValues($this->loadLocaleFile($path));
                foreach ($messages as $key => $value) {
                    if (!$this->isAdminNativeTranslationCandidate($key, $value, $technicalValues)) {
                        continue;
                    }
                    if (
                        isset($en[$key])
                        && $value === $en[$key]
                        && $this->isAdminNativeTranslationCandidate($key, $en[$key], $technicalValues)
                    ) {
                        $issues[] = sprintf('%s/%s:%s still equals en "%s"', $domain, $locale, $key, $value);
                    }
                    if (
                        isset($fr[$key])
                        && $value === $fr[$key]
                        && $this->isAdminNativeTranslationCandidate($key, $fr[$key], $technicalValues)
                    ) {
                        $issues[] = sprintf('%s/%s:%s still equals fr "%s"', $domain, $locale, $key, $value);
                    }
                }
            }
        }

        self::assertSame([], $issues);
    }

    public function testNativeLocaleFilesDoNotKeepFrenchOrEnglishFallbackStrings(): void
    {
        $issues = [];
        $technicalValues = $this->nativeLocaleTechnicalValues();

        foreach (glob(__DIR__ . '/../app/i18n/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $domain = basename((string) $directory);
            $frPath = $directory . '/fr.php';
            $enPath = $directory . '/en.php';
            if (!is_file($frPath) || !is_file($enPath)) {
                continue;
            }

            $fr = $this->flattenAdminLocaleValues($this->loadLocaleFile($frPath));
            $en = $this->flattenAdminLocaleValues($this->loadLocaleFile($enPath));
            foreach ($this->supportedLocales() as $locale) {
                if ($locale === 'fr' || $locale === 'en') {
                    continue;
                }

                $path = $directory . '/' . $locale . '.php';
                $messages = $this->flattenAdminLocaleValues($this->loadLocaleFile($path));
                foreach ($messages as $key => $value) {
                    if (!$this->isNativeTranslationCandidate($key, $value, $technicalValues)) {
                        continue;
                    }
                    if (
                        isset($en[$key])
                        && $value === $en[$key]
                        && $this->isNativeTranslationCandidate($key, $en[$key], $technicalValues)
                    ) {
                        $issues[] = sprintf('%s/%s:%s still equals en "%s"', $domain, $locale, $key, $value);
                    }
                    if (
                        isset($fr[$key])
                        && $value === $fr[$key]
                        && $this->isNativeTranslationCandidate($key, $fr[$key], $technicalValues)
                    ) {
                        $issues[] = sprintf('%s/%s:%s still equals fr "%s"', $domain, $locale, $key, $value);
                    }
                }
            }
        }

        if ($issues !== []) {
            self::fail(
                "Native locale fallback strings found:\n"
                . implode("\n", array_slice($issues, 0, 50))
                . (count($issues) > 50 ? "\n... and " . (count($issues) - 50) . ' more.' : '')
            );
        }

        self::assertSame([], $issues);
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, string>
     */
    private function flattenAdminLocaleValues(array $values, string $prefix = ''): array
    {
        $flat = [];
        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . (string) $key;
            if (is_array($value)) {
                $flat += $this->flattenAdminLocaleValues($value, $path);
                continue;
            }
            if (is_string($value)) {
                $flat[$path] = trim(strip_tags($value));
            }
        }

        return $flat;
    }

    /**
     * @param array<string, true> $technicalValues
     */
    private function isAdminNativeTranslationCandidate(string $key, string $value, array $technicalValues): bool
    {
        $value = trim($value);
        if ($value === '' || isset($technicalValues[$value])) {
            return false;
        }
        if (preg_match('/(?:^|\.|_)(?:slug|code|id|url|uri|path|route|token|csrf|parser|locator|callsign|filename|file_name)(?:$|\.|_)/i', $key) === 1) {
            return false;
        }
        if (preg_match('/^[A-Z0-9 _.:\/#%+\-()]+$/u', $value) === 1) {
            return false;
        }
        if (preg_match('/^(?:[a-z][a-z0-9]*_)+[a-z0-9]+$/', $value) === 1) {
            return false;
        }
        if (preg_match('/\.(?:php|html?|css|js|json|xml|csv|pdf|png|jpe?g|webp|svg)$/i', $value) === 1) {
            return false;
        }
        if (preg_match('/^https?:\/\//i', $value) === 1) {
            return false;
        }
        if ((function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value)) < 4) {
            return false;
        }

        return preg_match('/\p{L}/u', $value) === 1;
    }

    /**
     * @return array<string, true>
     */
    private function nativeLocaleTechnicalValues(): array
    {
        return array_fill_keys([
            '',
            'ADIF',
            'AM',
            'APRS',
            'API',
            'CSV',
            'CW',
            'D-STAR',
            'DMR',
            'EchoLink',
            'FM',
            'FT4',
            'FT8',
            'GDPR',
            'HAMQSL',
            'HamQSL',
            'HAREC',
            'HTML',
            'ID',
            'JSON',
            'LoRa',
            'LoTW',
            'LoTW + eQSL',
            'MHz',
            'ON2',
            'ON3',
            'ON4CRD',
            'PDF',
            'QRZ',
            'QRZ.com',
            'QSL',
            'RGPD',
            'RSS',
            'RTTY',
            'SSB',
            'SSTV',
            'UBA',
            'UTC',
            'URL',
            'Winlink',
            'WSPR',
            'eQSL',
            'kHz',
            'Administration',
            'Admin',
            'Adresse',
            'Adresse.',
            'Agenda',
            'Album',
            'Album public',
            'Albums',
            'Altitude',
            'Amber',
            'Antennes',
            'Assistant',
            'Aurora',
            'Band',
            'Campagnes',
            'Canal',
            'Clics',
            'Club',
            'Comité',
            'Contact',
            'CQ zone',
            'Dashboard',
            'dBµV',
            'DeepL',
            'Dessert',
            'Details',
            'Deutsch',
            'Document',
            'Duty cycle',
            'Email',
            'E-mail',
            'Emerald',
            'Export',
            'File',
            'Filter',
            'Front SVG',
            'Galerie',
            'Durnal, Yvoir, Namur, Belgium',
            'Google Map - Radio Club Durnal',
            'Grid',
            'Grx (dBi)',
            'Gtx (dBi)',
            'Illustration ON4CRD',
            'Impedance (Ω)',
            'Import',
            'Input ERP (W)',
            'ITU zone',
            'Label',
            'Legal',
            'Licence',
            'Liste',
            'Locator',
            'Locator A',
            'Locator B',
            'Logo UBA',
            'Matomo',
            'Modules',
            'Name',
            'Newsletter',
            'Newsletters',
            'ON4CRD Newsletter',
            'OpenAI',
            'Orange',
            'Parser',
            'Password',
            'Pause',
            'Polarisation',
            'Preview',
            'Privacy',
            'Profil',
            'Prosigns',
            'Public',
            'Q-code',
            'QSL via',
            'Radio Club Durnal - Bocq Arena',
            'QTH / Localité',
            'record(s)',
            'Reset',
            'Role',
            'Rue des Ecoles, 5530 Purnode',
            'Rue des Écoles, 5530 Purnode',
            's to ms',
            'Separator',
            'Service',
            'Sponsoring',
            'Sponsoring ON4CRD',
            'Start',
            'Station',
            'Stats',
            'Status',
            'Total:',
            'Type',
            'UBA logo',
            'Upload',
            'Violet',
            'Visual',
            'Vpp to Vrms',
            'Vrms to Vpp',
            'Watts (W)',
            'Website',
            'Week',
            'Wh to Joules',
            'Widgets',
            'Wiki',
            'Wiki administration',
        ], true);
    }

    /**
     * @param array<string, true> $technicalValues
     */
    private function isNativeTranslationCandidate(string $key, string $value, array $technicalValues): bool
    {
        $value = trim($value);
        $key = strtolower($key);
        if ($value === '' || isset($technicalValues[$value])) {
            return false;
        }
        if (preg_match('/(?:^|\.|_)(?:slug|code|filename|file_name|url|uri|path|route|id|uuid|token|csrf|format|unit|dbm|dbw|ohm|mhz|khz|hz)(?:$|\.|_)/i', $key) === 1) {
            return false;
        }
        if (preg_match('/(?:^|\.|_)(?:value|line|venue|club_name|calendar_name|default_title|wiki_title|address_value|location_value|callsign_value)(?:$|\.|_)/i', $key) === 1) {
            return false;
        }
        if (preg_match('/^(?:[a-z][a-z0-9]*_)+[a-z0-9]+$/', $value) === 1) {
            return false;
        }
        if (preg_match('/\.(?:ics|php|html?|css|js|json|xml|csv|pdf|png|jpe?g|webp|gif|svg)$/i', $value) === 1) {
            return false;
        }
        if (preg_match('/^https?:\/\//i', $value) === 1) {
            return false;
        }
        if (preg_match('/^[A-Z0-9 _.\-:\/()+#%&]+$/u', $value) === 1) {
            return false;
        }
        if (preg_match('/^(?:[A-Z]{2,}|[A-Z0-9-]{2,})(?:\s+(?:[A-Z]{2,}|[A-Z0-9-]{2,}))*$/u', $value) === 1) {
            return false;
        }
        if ((function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value)) < 4) {
            return false;
        }

        return preg_match('/\p{L}/u', $value) === 1;
    }

    public function testRequestedFrenchModuleLabelsStayAccented(): void
    {
        $auctions = file_get_contents(__DIR__ . '/../pages/auctions.php');
        self::assertIsString($auctions);
        self::assertStringContainsString('Créer un lot', $auctions);
        self::assertStringNotContainsString('Creer un lot', $auctions);
        self::assertStringNotContainsString('Creer un lot0', $auctions);

        $classifieds = file_get_contents(__DIR__ . '/../pages/classifieds.php');
        self::assertIsString($classifieds);
        self::assertStringContainsString('Proposer une catégorie', $classifieds);
        self::assertStringContainsString('La catégorie sera validée directement.', $classifieds);
        self::assertStringNotContainsString('Creer une categorie', $classifieds);
        self::assertStringNotContainsString('La categorie sera validee directement.', $classifieds);

        $articles = $this->loadLocaleFile(__DIR__ . '/../app/i18n/articles/fr.php');
        self::assertSame('Proposer une thématique', $articles['propose_category']);
        self::assertSame('Proposition de thématique d’article ON4CRD', $articles['propose_category_subject']);
        self::assertStringContainsString('nouvelle thématique d’article', (string) $articles['propose_category_body']);
        self::assertSame('Nom de la thématique', $articles['propose_category_name_label']);

        $articlesSource = file_get_contents(__DIR__ . '/../app/i18n/articles/fr.php');
        self::assertIsString($articlesSource);
        self::assertStringNotContainsString('Proposer une cagégorie', $articlesSource);

        $wiki = file_get_contents(__DIR__ . '/../pages/wiki.php');
        self::assertIsString($wiki);
        self::assertStringContainsString('Proposer une thématique', $wiki);
        self::assertStringContainsString('Indiquez la thématique à ajouter', $wiki);
        self::assertStringNotContainsString('Créer une thématique', $wiki);
        self::assertStringNotContainsString('Créer la thématique', $wiki);
        self::assertStringNotContainsString('Proposer une thematique', $wiki);
        self::assertStringNotContainsString('Indiquez la thematique a ajouter', $wiki);

        $wikiMessages = $this->loadLocaleFile(__DIR__ . '/../app/i18n/wiki/fr.php');
        self::assertSame('Proposer une thématique', $wikiMessages['create_theme']);
        self::assertSame('Proposer la thématique', $wikiMessages['create_theme_submit']);
    }
}
