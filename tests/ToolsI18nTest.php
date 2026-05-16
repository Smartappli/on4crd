<?php

declare(strict_types=1);

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

    /**
     * @dataProvider localeKeyParityProvider
     */
    public function testToolsLocaleHasSameKeysAsFrench(string $locale): void
    {
        $fr = $this->loadLocale('fr');
        $candidate = $this->loadLocale($locale);

        self::assertSame(array_keys($fr), array_keys($candidate));
    }

    /**
     * @return array<string, array{0:string}>
     */
    public static function localeKeyParityProvider(): array
    {
        return [
            'it' => ['it'],
            'pt' => ['pt'],
            'id' => ['id'],
            'ar' => ['ar'],
            'bn' => ['bn'],
            'ja' => ['ja'],
            'zh' => ['zh'],
            'ru' => ['ru'],
            'hi' => ['hi'],
        ];
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
