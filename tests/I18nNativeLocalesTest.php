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
