<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ToolsPanelsContractTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private static function panelMap(): array
    {
        $map = require __DIR__ . '/../app/config/tools_panels.php';
        self::assertIsArray($map);

        /** @var array<string, string> $map */
        return $map;
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function panelProvider(): array
    {
        $cases = [];
        foreach (self::panelMap() as $toolId => $panelFile) {
            $cases[$toolId] = [$toolId, $panelFile];
        }

        return $cases;
    }

    public function testEveryCatalogToolHasAMappedPanel(): void
    {
        $catalog = require __DIR__ . '/../app/config/tools_catalog.php';
        self::assertIsArray($catalog);

        $mappedToolIds = array_keys(self::panelMap());
        foreach ($catalog as $group => $entries) {
            self::assertIsArray($entries, sprintf('Catalog group %s must be an array.', (string) $group));
            foreach ($entries as $entry) {
                self::assertIsArray($entry);
                $toolId = (string) ($entry['id'] ?? '');
                self::assertNotSame('', $toolId, sprintf('Catalog group %s contains an entry without id.', (string) $group));
                self::assertContains($toolId, $mappedToolIds, sprintf('Catalog tool %s has no mapped panel.', $toolId));
            }
        }
    }

    public function testEveryPanelPartialIsRegistered(): void
    {
        $registeredFiles = array_values(self::panelMap());
        $panelFiles = glob(__DIR__ . '/../pages/tools_panels/*.php');
        self::assertIsArray($panelFiles);

        foreach ($panelFiles as $path) {
            $file = basename((string) $path);
            self::assertContains($file, $registeredFiles, sprintf('Panel partial %s is not registered in tools_panels.php.', $file));
        }
    }

    #[DataProvider('panelProvider')]
    public function testEveryMappedPanelFileExists(string $toolId, string $panelFile): void
    {
        $path = __DIR__ . '/../pages/tools_panels/' . $panelFile;
        self::assertFileExists($path, sprintf('Tool %s points to missing panel %s.', $toolId, $panelFile));
    }

    #[DataProvider('panelProvider')]
    public function testEveryMappedPanelRendersItsToolArticle(string $toolId, string $panelFile): void
    {
        $html = $this->renderPanel($panelFile);

        self::assertStringContainsString('data-tool-panel', $html, sprintf('Panel %s must expose data-tool-panel.', $toolId));
        self::assertMatchesRegularExpression(
            '/<article\b(?=[^>]*\bid="' . preg_quote($toolId, '/') . '")(?=[^>]*\bdata-tool-panel\b)/',
            $html,
            sprintf('Panel %s must render an article with the mapped id.', $toolId)
        );
    }

    #[DataProvider('panelProvider')]
    public function testEveryMappedPanelIsClientInitializable(string $toolId): void
    {
        $initializers = $this->extractJsObjectKeys('toolInitializers');

        self::assertContains($toolId, $initializers, sprintf('Tool %s has no client initializer in tools.js.', $toolId));
    }

    #[DataProvider('panelProvider')]
    public function testSimpleConverterPanelsHaveAConverterImplementation(string $toolId, string $panelFile): void
    {
        $html = $this->renderPanel($panelFile);
        if (!str_contains($html, 'id="' . $toolId . '-in"')) {
            self::assertTrue(true);
            return;
        }

        $converters = $this->extractJsObjectKeys('simpleToolConverters');
        self::assertContains($toolId, $converters, sprintf('Simple converter %s is rendered but has no JS converter.', $toolId));
    }

    public function testTextInputsKeepDecimalModeWithoutNativeNumberConstraints(): void
    {
        $panelFiles = glob(__DIR__ . '/../pages/tools_panels/*.php');
        self::assertIsArray($panelFiles);

        foreach ($panelFiles as $path) {
            $content = file_get_contents((string) $path);
            self::assertIsString($content);
            self::assertDoesNotMatchRegularExpression(
                '/<input\b(?=[^>]*\btype="text")[^>]*\s(?:min|max|step)=/i',
                $content,
                sprintf('Text inputs in %s must use data-min/data-max/data-step, not native min/max/step.', basename((string) $path))
            );
        }
    }

    public function testEveryMappedPanelHasAnE2eScenario(): void
    {
        $spec = file_get_contents(__DIR__ . '/e2e/tools-all-calculators.spec.ts');
        self::assertIsString($spec);

        foreach (array_keys(self::panelMap()) as $toolId) {
            self::assertMatchesRegularExpression(
                '/(?:id:\s*|unitConversionSteps\(|simpleConverterScenario\()\'' . preg_quote((string) $toolId, '/') . '\'/',
                $spec,
                sprintf('Tool %s has no scenario in tools-all-calculators.spec.ts.', (string) $toolId)
            );
        }
    }

    public function testUnitConversionQuickLinksNeverExposeRawToolIdsAsLabels(): void
    {
        $quickLinkIds = [
            'tool-power',
            'tool-freq-wave',
            'tool-dbuv',
            'tool-gain-conv',
            'tool-kw-w',
            'tool-hz-khz',
            'tool-in-mm',
            'tool-c-f',
            'tool-vpp-vrms',
            'tool-sunit-dbuv',
        ];

        foreach (['tool_unit_converter.php', 'tool_unit_conversions.php'] as $panelFile) {
            $html = $this->renderPanel($panelFile);
            foreach ($quickLinkIds as $rawLabel) {
                self::assertStringNotContainsString(
                    '>' . $rawLabel . '<',
                    $html,
                    sprintf('Unit conversion panel %s exposes raw label %s.', $panelFile, $rawLabel)
                );
            }
        }
    }

    private function renderPanel(string $panelFile): string
    {
        $path = __DIR__ . '/../pages/tools_panels/' . $panelFile;
        self::assertFileExists($path);

        /** @var array<string, string> $t */
        $t = require __DIR__ . '/../app/i18n/tools/fr.php';
        $conversionTools = [];
        $radioMathTools = [];

        set_error_handler(
            static function (int $severity, string $message, string $file, int $line): never {
                throw new ErrorException($message, 0, $severity, $file, $line);
            }
        );

        $bufferLevel = ob_get_level();
        try {
            ob_start();
            require $path;
            $html = (string) ob_get_clean();
        } finally {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
            restore_error_handler();
        }

        self::assertNotSame('', trim($html), sprintf('Panel %s rendered empty HTML.', $panelFile));

        return $html;
    }

    /**
     * @return list<string>
     */
    private function extractJsObjectKeys(string $objectName): array
    {
        $js = file(__DIR__ . '/../assets/js/modules/tools.js', FILE_IGNORE_NEW_LINES);
        self::assertIsArray($js);

        $keys = [];
        $inside = false;
        foreach ($js as $line) {
            if (!$inside && str_contains((string) $line, 'const ' . $objectName . ' = {')) {
                $inside = true;
                continue;
            }

            if (!$inside) {
                continue;
            }

            if (trim((string) $line) === '};') {
                break;
            }

            if (preg_match('/^\s*[\'"]([^\'"]+)[\'"]\s*:/', (string) $line, $matches) === 1) {
                $keys[] = (string) $matches[1];
            }
        }

        self::assertNotSame([], $keys, sprintf('Could not extract keys from JS object %s.', $objectName));

        return array_values(array_unique($keys));
    }
}
