<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MemberModuleTranslationsRegressionTest extends TestCase
{
    /**
     * @param list<string> $requiredKeys
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('taxonomyTranslationDomains')]
    public function testTaxonomyTranslationsExistForEveryLocale(string $domain, array $requiredKeys): void
    {
        $files = glob(dirname(__DIR__) . '/app/i18n/' . $domain . '/*.php');
        self::assertIsArray($files);
        self::assertNotSame([], $files, $domain . ' must expose locale files.');

        foreach ($files as $file) {
            $messages = require $file;
            self::assertIsArray($messages, $file);

            foreach ($requiredKeys as $key) {
                self::assertArrayHasKey($key, $messages, $domain . '/' . basename($file) . ' misses ' . $key);
                self::assertIsString($messages[$key], $domain . '/' . basename($file) . ' has non-string ' . $key);

                $value = trim($messages[$key]);
                self::assertNotSame('', $value, $domain . '/' . basename($file) . ' has empty ' . $key);
                self::assertStringNotContainsString("\u{FFFD}", $value, $domain . '/' . basename($file) . ' has replacement chars in ' . $key);
                self::assertStringNotContainsString('Ã', $value, $domain . '/' . basename($file) . ' has mojibake in ' . $key);
                self::assertStringNotContainsString('Â', $value, $domain . '/' . basename($file) . ' has mojibake in ' . $key);
                self::assertStringNotContainsString('â€™', $value, $domain . '/' . basename($file) . ' has mojibake in ' . $key);
                self::assertStringNotContainsString('â€', $value, $domain . '/' . basename($file) . ' has mojibake in ' . $key);
                self::assertStringNotContainsString('?', $value, $domain . '/' . basename($file) . ' has replacement punctuation in ' . $key);
            }
        }
    }

    /**
     * @return array<string, array{0:string,1:list<string>}>
     */
    public static function taxonomyTranslationDomains(): array
    {
        return [
            'members_library' => [
                'members_library',
                ['topics', 'subcategory', 'subcategories', 'no_subcategory', 'favorite'],
            ],
            'admin_library' => [
                'admin_library',
                ['categories', 'subcategories', 'add_category', 'add_subcategory', 'err_category_has_subcategories', 'err_subcategory_has_documents'],
            ],
            'webotheque' => [
                'webotheque',
                ['topics', 'subcategory_field', 'no_subcategory', 'favorite', 'favorites', 'taxonomy_title', 'add_category', 'add_subcategory', 'err_category_has_subcategories', 'err_subcategory_has_documents'],
            ],
            'albums' => [
                'albums',
                ['category_field', 'subcategory_field', 'no_subcategory', 'favorites'],
            ],
            'admin_albums' => [
                'admin_albums',
                ['add_category', 'add_subcategory', 'err_category_has_subcategories', 'err_subcategory_has_documents'],
            ],
            'wiki' => [
                'wiki',
                ['themes', 'all_themes', 'subcategory_field', 'no_subcategory', 'favorites'],
            ],
            'admin_wiki' => [
                'admin_wiki',
                ['add_category', 'add_subcategory', 'err_category_has_subcategories', 'err_subcategory_has_documents'],
            ],
        ];
    }
}
