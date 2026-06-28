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

    public function testComicsHelperFunctionsNormalizeRelatedResources(): void
    {
        self::assertSame('fr', comics_public_locale('fr'));
        self::assertSame((string) config('app.default_locale', 'fr'), comics_public_locale('invalid-locale'));
        self::assertSame('text/markdown', comics_public_document_type('assets/comics/example.md?v=1'));
        self::assertSame('application/pdf', comics_public_document_type('https://example.test/file.pdf'));
        self::assertSame('application/octet-stream', comics_public_document_type('file.unknown'));

        $documents = comics_public_related_documents([
            ['path' => 'assets/comics/loi-ohm-fiche-memo.md', 'title' => 'Memo', 'text' => 'Local memo'],
            ['url' => 'https://example.test/radio.pdf', 'title' => 'Remote PDF'],
            ['path' => '../private.txt', 'title' => 'Unsafe path'],
            ['url' => 'javascript:alert(1)', 'title' => 'Unsafe URL'],
            ['path' => 'assets/comics/loi-ohm-fiche-memo.md', 'title' => ''],
        ]);
        self::assertCount(2, $documents);
        self::assertFalse($documents[0]['external']);
        self::assertSame('loi-ohm-fiche-memo.md', $documents[0]['download_name']);
        self::assertSame('text/markdown', $documents[0]['type']);
        self::assertGreaterThan(0, $documents[0]['content_size']);
        self::assertTrue($documents[1]['external']);
        self::assertSame('application/pdf', $documents[1]['type']);
        self::assertSame(0, $documents[1]['content_size']);

        $links = comics_public_related_links([
            ['route' => 'tools', 'fragment' => 'tool-ohm-law', 'title' => 'Tool', 'text' => 'Internal tool'],
            ['url' => 'https://example.test/ohm', 'title' => 'External reference'],
            ['url' => 'javascript:alert(1)', 'title' => 'Unsafe link'],
            ['route' => '', 'title' => 'Missing target'],
        ]);
        self::assertCount(2, $links);
        self::assertFalse($links[0]['external']);
        self::assertStringContainsString('route=tools', $links[0]['url']);
        self::assertStringEndsWith('#tool-ohm-law', $links[0]['url']);
        self::assertTrue($links[1]['external']);
        self::assertSame('https://example.test/ohm', $links[1]['url']);

        $documentObject = comics_public_document_object($documents[0]);
        self::assertSame('DigitalDocument', $documentObject['@type']);
        self::assertSame('Memo', $documentObject['name']);

        $linkObject = comics_public_link_object($links[0]);
        self::assertSame('WebPage', $linkObject['@type']);
        self::assertSame('Tool', $linkObject['name']);
        self::assertStringContainsString('#tool-ohm-law-webpage', $linkObject['@id']);
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
            self::assertArrayHasKey('documents', $board);
            self::assertArrayHasKey('links', $board);
        }

        self::assertSame([], $collection['boards'][0]['documents']);
        self::assertSame([], $collection['boards'][1]['documents']);
        self::assertSame([], $collection['boards'][0]['links']);
        self::assertSame([], $collection['boards'][1]['links']);

        $relatedDocuments = $collection['boards'][2]['documents'];
        self::assertCount(1, $relatedDocuments);
        $relatedDocument = $relatedDocuments[0];
        self::assertSame('Fiche mémo', $relatedDocument['title']);
        self::assertSame('text/markdown', $relatedDocument['type']);
        self::assertFalse($relatedDocument['external']);
        self::assertSame('loi-ohm-fiche-memo.md', $relatedDocument['download_name']);
        self::assertStringContainsString('/assets/comics/loi-ohm-fiche-memo.md', (string) $relatedDocument['url']);
        self::assertFileExists(__DIR__ . '/../' . (string) $relatedDocument['path']);
        self::assertGreaterThan(0, $relatedDocument['content_size']);

        $relatedLinks = $collection['boards'][2]['links'];
        self::assertCount(1, $relatedLinks);
        self::assertSame('Calculateur loi d\'Ohm', $relatedLinks[0]['title']);
        self::assertFalse($relatedLinks[0]['external']);
        self::assertStringContainsString('route=tools', $relatedLinks[0]['url']);
        self::assertStringEndsWith('#tool-ohm-law', $relatedLinks[0]['url']);
    }

    public function testComicsStructuredDataHelpersExposeFullImageAndThumbnailObjects(): void
    {
        $boards = comics_public_collection('fr')['boards'];
        $board = $boards[0];
        $image = comics_public_image_object($board);
        $thumbnail = comics_public_image_object($board, true);
        $work = comics_public_creative_work($board, 'fr', 'https://example.test/comics#collection', 'https://example.test/#organization');

        self::assertSame('ImageObject', $image['@type']);
        self::assertStringContainsString('.png', (string) $image['url']);
        self::assertSame('image/png', $image['encodingFormat']);
        self::assertSame(1055, $image['width']);
        self::assertSame(1491, $image['height']);

        self::assertSame('ImageObject', $thumbnail['@type']);
        self::assertStringContainsString('-thumb.jpg', (string) $thumbnail['url']);
        self::assertSame('image/jpeg', $thumbnail['encodingFormat']);
        self::assertSame(420, $thumbnail['width']);
        self::assertSame(594, $thumbnail['height']);

        self::assertSame('CreativeWork', $work['@type']);
        self::assertSame($image, $work['image']);
        self::assertSame($thumbnail, $work['thumbnail']);
        self::assertSame((string) $thumbnail['url'], $work['thumbnailUrl']);
        self::assertSame(['@id' => 'https://example.test/comics#collection'], $work['isPartOf']);
        self::assertSame(['@id' => 'https://example.test/#organization'], $work['publisher']);
        self::assertSame(['@id' => 'https://example.test/#organization'], $work['creator']);
        self::assertArrayNotHasKey('hasPart', $work);

        $ohmWork = comics_public_creative_work($boards[2], 'fr', 'https://example.test/comics#collection', 'https://example.test/#organization');
        self::assertArrayHasKey('hasPart', $ohmWork);
        self::assertIsArray($ohmWork['hasPart']);
        self::assertSame('DigitalDocument', $ohmWork['hasPart'][0]['@type']);
        self::assertSame('text/markdown', $ohmWork['hasPart'][0]['encodingFormat']);
        self::assertStringContainsString('loi-ohm-fiche-memo.md', (string) $ohmWork['hasPart'][0]['@id']);
        self::assertStringEndsWith('#document', (string) $ohmWork['hasPart'][0]['@id']);
        self::assertArrayHasKey('subjectOf', $ohmWork);
        self::assertSame('WebPage', $ohmWork['subjectOf'][0]['@type']);
        self::assertStringContainsString('route=tools', (string) $ohmWork['subjectOf'][0]['url']);
        self::assertStringContainsString('#tool-ohm-law', (string) $ohmWork['subjectOf'][0]['url']);
    }

    public function testComicsHelperIsLoadedForPageAndDiscoveryRoutes(): void
    {
        $helperMap = app_route_helper_map();

        self::assertArrayHasKey('comics_helpers.php', $helperMap);
        foreach (['comics', 'sitemap.xml', 'llms.txt', 'ai-index.json', 'knowledge-graph.jsonld'] as $route) {
            self::assertContains($route, $helperMap['comics_helpers.php']);
        }
    }

    public function testComicsPageIncludesProgressiveViewerAndDownloadControls(): void
    {
        $pageSource = file_get_contents(__DIR__ . '/../pages/comics.php');
        self::assertIsString($pageSource);
        foreach (['data-comics-viewer-open', 'data-comics-viewer-download', 'download_board_prefix', 'download_board_label', 'viewer_close', 'related_documents_title', 'related_links_title', 'comics-related-documents', 'comics-related-links'] as $snippet) {
            self::assertStringContainsString($snippet, $pageSource);
        }

        $jsPath = __DIR__ . '/../assets/js/modules/comics.js';
        self::assertFileExists($jsPath);
        $jsSource = file_get_contents($jsPath);
        self::assertIsString($jsSource);
        self::assertStringContainsString('showModal', $jsSource);
        self::assertStringContainsString('event.preventDefault()', $jsSource);

        $cssPath = __DIR__ . '/../assets/css/modules/comics.css';
        self::assertFileExists($cssPath);
        $cssSource = file_get_contents($cssPath);
        self::assertIsString($cssSource);
        self::assertStringContainsString('.comics-viewer', $cssSource);
        self::assertStringContainsString('.comics-card-action', $cssSource);
        self::assertStringContainsString('.comics-related-document', $cssSource);
        self::assertStringContainsString('.comics-related-link', $cssSource);
    }

    public function testComicsRoutePreloadsGeneratedThumbnailAsset(): void
    {
        $hrefs = [];
        foreach (module_preload_assets_for_route('comics') as $asset) {
            if (is_array($asset)) {
                $hrefs[] = (string) ($asset['href'] ?? '');
                continue;
            }

            $hrefs[] = (string) $asset;
        }

        $preloadHtml = implode("\n", $hrefs);
        self::assertStringContainsString('les-10-commandements-radio-amateur-thumb.jpg', $preloadHtml);
        self::assertStringNotContainsString('les-10-commandements-radio-amateur.png', $preloadHtml);
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
