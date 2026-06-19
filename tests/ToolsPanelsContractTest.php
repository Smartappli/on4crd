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
        $map = require __DIR__ . '/../app/config/tools_panels.php'; // NOSONAR - test data file returns an array.
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
        $catalog = require __DIR__ . '/../app/config/tools_catalog.php'; // NOSONAR - test data file returns an array.
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
    public function testEveryMappedPanelIsClientInitializable(string $toolId, string $panelFile): void
    {
        self::assertNotSame('', $panelFile);

        $initializers = $this->extractJsObjectKeys('toolInitializers');

        self::assertContains($toolId, $initializers, sprintf('Tool %s has no client initializer in tools.js.', $toolId));
    }

    #[DataProvider('panelProvider')]
    public function testSimpleConverterPanelsHaveAConverterImplementation(string $toolId, string $panelFile): void
    {
        $html = $this->renderPanel($panelFile);

        $hasSimpleInput = str_contains($html, 'id="' . $toolId . '-in"');
        $hasSimpleOutput = str_contains($html, 'id="' . $toolId . '-out"');
        self::assertSame(
            $hasSimpleInput,
            $hasSimpleOutput,
            sprintf('Simple converter %s must expose matching -in and -out elements.', $toolId)
        );

        if (!$hasSimpleInput) {
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

    public function testToolNavigationPreservesBrowserHistory(): void
    {
        $js = file_get_contents(__DIR__ . '/../assets/js/modules/tools.js');
        self::assertIsString($js);

        self::assertStringContainsString('window.history.pushState', $js, 'Tool navigation clicks must create browser history entries.');
        self::assertStringContainsString("setActiveTool(targetId, { pushHistory: true })", $js, 'Tool navigation must push history only through the resolved activation path.');
        self::assertStringContainsString("window.addEventListener('popstate'", $js, 'Tool navigation must react to browser back/forward.');
        self::assertStringNotContainsString('window.history.replaceState(null, \'\', `#${targetId}`)', $js, 'Tool navigation must not replace the previous tool history entry.');
    }

    private function renderPanel(string $panelFile): string
    {
        $path = __DIR__ . '/../pages/tools_panels/' . $panelFile;
        self::assertFileExists($path);

        /** @var array<string, string> $t */
        $t = require __DIR__ . '/../app/i18n/tools/fr.php'; // NOSONAR - locale file returns an array.
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
            require $path; // NOSONAR - panel partials must be rendered repeatedly in contract tests.
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
        $depth = 0;
        foreach ($js as $line) {
            if (!$inside && str_contains((string) $line, 'const ' . $objectName . ' = {')) {
                $inside = true;
                $depth = 1;
                continue;
            }

            if (!$inside) {
                continue;
            }

            if (preg_match('/^\s*[\'"]([^\'"]+)[\'"]\s*:/', (string) $line, $matches) === 1) {
                $keys[] = (string) $matches[1];
            }

            $depth += substr_count((string) $line, '{') - substr_count((string) $line, '}');
            if ($depth <= 0) {
                break;
            }
        }

        self::assertNotSame([], $keys, sprintf('Could not extract keys from JS object %s.', $objectName));

        return array_values(array_unique($keys));
    }
}
