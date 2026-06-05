<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ToolsI18nTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private function loadLocale(string $locale): array
    {
        $path = __DIR__ . '/../app/i18n/tools/' . $locale . '.php';
        self::assertFileExists($path);

        $data = require $path;
        self::assertIsArray($data);

        /** @var array<string, string> $data */
        return $data;
    }

    #[DataProvider('localeKeyParityProvider')]
    public function testToolsLocaleHasSameKeysAsFrench(string $locale): void
    {
        $fr = $this->loadLocale('fr');
        $candidate = $this->loadLocale($locale);

        $frKeys = array_keys($fr);
        $candidateKeys = array_keys($candidate);
        sort($frKeys);
        sort($candidateKeys);
        self::assertSame($frKeys, $candidateKeys);
    }

    /**
     * @return array<string, array{0:string}>
     */
    public static function localeKeyParityProvider(): array
    {
        $messages = require __DIR__ . '/../app/i18n/tools.php';
        unset($messages['fr']);

        $cases = [];
        foreach (array_keys($messages) as $locale) {
            $cases[(string) $locale] = [(string) $locale];
        }

        return $cases;
    }

    public function testToolsLocalesDoNotContainCommonTechnicalMojibake(): void
    {
        $messages = require __DIR__ . '/../app/i18n/tools.php';
        self::assertIsArray($messages);

        $badFragments = ['Âµ', 'Â°', 'Î©', 'â†’', 'â†”', 'â€™', 'â€œ', 'â€“', 'â€”'];
        foreach ($messages as $locale => $translations) {
            self::assertIsArray($translations);
            $joined = implode("\n", array_map('strval', $translations));
            foreach ($badFragments as $fragment) {
                self::assertStringNotContainsString($fragment, $joined, sprintf('Locale %s contains mojibake fragment %s.', (string) $locale, $fragment));
            }
        }
    }

    public function testItalianToolsLocaleDoesNotContainKnownSpanishFragments(): void
    {
        $it = $this->loadLocale('it');
        $joined = implode("\n", $it);

        $unexpectedFragments = [
            'Frecuencia',
            'Calculadora',
            'Impedancia',
            'Longitud',
            'Pérdida',
            'Tensión de',
            'Coeficiente de reflexión',
            'Ángulo de incidencia',
            'Altura virtual',
            'Conversión ERP',
            'Nivel A',
            'Nivel B',
            'No se puede cargar',
            'Ej.:',
        ];

        foreach ($unexpectedFragments as $fragment) {
            self::assertStringNotContainsString($fragment, $joined);
        }
    }

    public function testPortugueseToolsLocaleDoesNotContainKnownSpanishFragments(): void
    {
        $pt = $this->loadLocale('pt');
        $joined = implode("\n", $pt);

        $unexpectedFragments = [
            'Frecuencia',
            'Impedancia',
            'Inductancia',
            'Capacitancia',
            'Longitud',
            'Pérdida',
            'Capacidad de batería',
            'Tensión de',
            'Conversión ERP',
            'Nivel A',
            'Nivel B',
            'Ej.:',
        ];

        foreach ($unexpectedFragments as $fragment) {
            self::assertStringNotContainsString($fragment, $joined);
        }
    }
}
