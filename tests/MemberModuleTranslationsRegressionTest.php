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
     * @param list<string> $keys
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('adminFallbackTranslationDomains')]
    public function testAdminModuleTranslationsDoNotFallBackToEnglishForNativeLocales(string $domain, array $keys): void
    {
        $base = dirname(__DIR__) . '/app/i18n/' . $domain;
        $english = require $base . '/en.php';
        self::assertIsArray($english);

        $files = glob($base . '/*.php');
        self::assertIsArray($files);

        foreach ($files as $file) {
            $locale = pathinfo($file, PATHINFO_FILENAME);
            if ($locale === 'en') {
                continue;
            }
            $messages = require $file;
            self::assertIsArray($messages, $file);

            foreach ($keys as $key) {
                self::assertArrayHasKey($key, $messages, $domain . '/' . basename($file) . ' misses ' . $key);
                self::assertNotSame(
                    $english[$key] ?? null,
                    $messages[$key],
                    $domain . '/' . basename($file) . ' still uses the English fallback for ' . $key
                );
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
                ['topics', 'subcategory_field', 'subsubcategory_field', 'no_subcategory', 'no_subsubcategory', 'favorite', 'favorites', 'taxonomy_title', 'add_category', 'add_subcategory', 'add_subsubcategory', 'err_category_has_subcategories', 'err_subcategory_has_subsubcategories', 'err_subcategory_has_documents', 'err_subsubcategory_has_documents'],
            ],
            'albums' => [
                'albums',
                ['category_field', 'subcategory_field', 'no_subcategory', 'favorites'],
            ],
            'admin_albums' => [
                'admin_albums',
                ['add_category', 'add_subcategory', 'err_category_has_subcategories', 'err_subcategory_has_documents', 'wizard_title', 'wizard_step_details', 'wizard_step_upload', 'wizard_step_review', 'wizard_finalize', 'batch_max_files', 'batch_max_size'],
            ],
            'wiki' => [
                'wiki',
                ['themes', 'all_themes', 'subcategory_field', 'subsubcategory_field', 'no_subcategory', 'no_subsubcategory', 'favorites', 'err_subcategory_category_mismatch', 'err_subsubcategory_category_mismatch'],
            ],
            'admin_wiki' => [
                'admin_wiki',
                ['add_category', 'add_subcategory', 'add_subsubcategory', 'subcategory_saved', 'subsubcategory_saved', 'err_category_has_subcategories', 'err_subcategory_has_subsubcategories', 'err_subcategory_has_documents', 'err_subsubcategory_has_documents', 'proposal_type_subsubcategory'],
            ],
            'articles' => [
                'articles',
                ['all_categories', 'subcategory_field', 'subsubcategory_field', 'no_subcategory', 'no_subsubcategory', 'favorites', 'favorite_label', 'err_subcategory_category_mismatch', 'err_subsubcategory_category_mismatch'],
            ],
            'admin_articles' => [
                'admin_articles',
                ['subcategory_field', 'subsubcategory_field', 'no_subcategory', 'no_subsubcategory', 'add_category', 'add_subcategory', 'add_subsubcategory', 'err_category_has_subcategories', 'err_subcategory_has_subsubcategories', 'err_subcategory_has_documents', 'err_subsubcategory_has_documents', 'proposal_type_subsubcategory'],
            ],
        ];
    }

    /**
     * @return array<string, array{0:string,1:list<string>}>
     */
    public static function adminFallbackTranslationDomains(): array
    {
        return [
            'admin_albums' => [
                'admin_albums',
                ['category_field', 'subcategory_field', 'no_subcategory', 'add_category', 'add_subcategory', 'err_category_has_documents', 'err_category_has_subcategories', 'err_subcategory_has_documents', 'wizard_title', 'wizard_step_upload', 'wizard_finalize', 'batch_max_size'],
            ],
            'admin_articles' => [
                'admin_articles',
                ['subcategory_field', 'subsubcategory_field', 'no_subcategory', 'no_subsubcategory', 'add_category', 'add_subcategory', 'add_subsubcategory', 'ok_subcategory_updated', 'ok_subcategory_deleted', 'ok_subsubcategory_updated', 'ok_subsubcategory_deleted', 'err_category_has_subcategories', 'err_subcategory_has_subsubcategories', 'err_subcategory_has_documents', 'err_subsubcategory_has_documents', 'proposal_type_subsubcategory'],
            ],
            'admin_wiki' => [
                'admin_wiki',
                ['new_page', 'empty', 'status_pending', 'status_published', 'status_rejected', 'add_category', 'add_subcategory', 'add_subsubcategory', 'category_saved', 'subcategory_saved', 'subsubcategory_saved', 'err_category_has_documents', 'err_category_has_subcategories', 'err_subcategory_has_subsubcategories', 'err_subcategory_has_documents', 'err_subsubcategory_has_documents'],
            ],
        ];
    }
}
