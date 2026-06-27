<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ComicsIntegrationTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function loadLocaleFile(string $path): array
    {
        self::assertFileExists($path);

        $messages = require $path; // NOSONAR - locale files return message arrays.
        self::assertIsArray($messages);

        return $messages;
    }

    public function testComicsLocaleFilesKeepKeyParityAndNonEmptyValues(): void
    {
        $reference = $this->loadLocaleFile(__DIR__ . '/../app/i18n/comics/fr.php');
        $referenceKeys = array_keys($reference);
        sort($referenceKeys);

        foreach (supported_locales() as $locale) {
            $messages = $this->loadLocaleFile(__DIR__ . '/../app/i18n/comics/' . $locale . '.php');
            $keys = array_keys($messages);
            sort($keys);

            self::assertSame($referenceKeys, $keys, sprintf('Comics locale key mismatch for %s.', $locale));
            foreach ($messages as $key => $value) {
                self::assertNotSame('', trim((string) $value), sprintf('Empty comics translation %s for %s.', (string) $key, $locale));
            }
        }
    }

    public function testComicsSeoKeysMatchComicsDomainTranslations(): void
    {
        foreach (supported_locales() as $locale) {
            $comics = $this->loadLocaleFile(__DIR__ . '/../app/i18n/comics/' . $locale . '.php');
            $seo = $this->loadLocaleFile(__DIR__ . '/../app/i18n/seo/' . $locale . '.php');

            self::assertSame((string) $comics['meta_title'], (string) $seo['comics_title'], sprintf('SEO title mismatch for comics locale %s.', $locale));
            self::assertSame((string) $comics['meta_desc'], (string) $seo['comics_description'], sprintf('SEO description mismatch for comics locale %s.', $locale));
        }
    }

    public function testComicsPublicCollectionUsesNativeTranslationsAndExistingAssets(): void
    {
        $collection = comics_public_collection('fr');

        self::assertSame('fr', $collection['locale']);
        self::assertSame('BD radioamateur ON4CRD', $collection['title']);
        self::assertCount(3, $collection['boards']);
        self::assertContains('fr', $collection['available_languages']);
        self::assertContains('ja', $collection['available_languages']);
        self::assertCount(count(supported_locales()), $collection['alternate_urls']);
        self::assertStringContainsString('route=comics', $collection['alternate_urls']['fr']);
        self::assertStringContainsString('lang=fr', $collection['alternate_urls']['fr']);
        self::assertStringContainsString('lang=ja', $collection['alternate_urls']['ja']);

        $expectedTitles = [
            'Les 10 commandements du radioamateur',
            'Mon premier QSO',
            'La découverte de la loi d\'Ohm',
        ];
        self::assertSame($expectedTitles, array_column($collection['boards'], 'title'));

        foreach ($collection['boards'] as $board) {
            $path = __DIR__ . '/../' . (string) $board['image'];
            self::assertFileExists($path, sprintf('Missing comics asset %s.', (string) $board['image']));
            self::assertSame('image/png', $board['type']);
            self::assertStringContainsString('/assets/comics/', (string) $board['url']);
            self::assertSame(1055, $board['width']);
            self::assertSame(1491, $board['height']);
            self::assertGreaterThan(0, $board['content_size']);

            $thumbnailPath = __DIR__ . '/../' . (string) $board['thumbnail'];
            self::assertFileExists($thumbnailPath, sprintf('Missing comics thumbnail %s.', (string) $board['thumbnail']));
            self::assertSame('image/jpeg', $board['thumbnail_type']);
            self::assertStringContainsString('-thumb.jpg', (string) $board['thumbnail_url']);
            self::assertSame(420, $board['thumbnail_width']);
            self::assertSame(594, $board['thumbnail_height']);
            self::assertGreaterThan(0, $board['thumbnail_content_size']);
            self::assertLessThan($board['content_size'], $board['thumbnail_content_size']);
        }
    }

    public function testComicsHelperIsLoadedForPageAndDiscoveryRoutes(): void
    {
        $helperMap = app_route_helper_map();

        self::assertArrayHasKey('comics_helpers.php', $helperMap);
        foreach (['comics', 'sitemap.xml', 'llms.txt', 'ai-index.json', 'knowledge-graph.jsonld'] as $route) {
            self::assertContains($route, $helperMap['comics_helpers.php']);
        }
    }

    public function testComicsGeoPagesUseSharedCollectionAndNoLegacyHardcodedLabels(): void
    {
        $paths = [
            __DIR__ . '/../pages/comics.php',
            __DIR__ . '/../pages/ai_index.php',
            __DIR__ . '/../pages/llms.php',
            __DIR__ . '/../pages/knowledge_graph.php',
            __DIR__ . '/../pages/sitemap.php',
        ];

        foreach ($paths as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);
            self::assertStringContainsString('comics_public_collection', $source, basename($path) . ' must use the shared comics collection helper.');
            foreach (['Ma premiere fois', 'La decouverte de la loi d Ohm', 'ON4CRD Comics', 'Ohm s law'] as $legacyLabel) {
                self::assertStringNotContainsString($legacyLabel, $source, basename($path) . ' still contains legacy comics label ' . $legacyLabel);
            }
        }
    }
}
