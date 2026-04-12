<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QslHelpersExtendedTest extends TestCase
{
    public function testQslNormalizeCallsignRemovesInvalidCharsAndSlashes(): void
    {
        self::assertSame('ON4CRD', qsl_normalize_callsign('/ on4@crd //'));
        self::assertSame('F4XYZ/P', qsl_normalize_callsign('f4xyz / p'));
    }

    public function testQslNormalizeDateAndTimeHandleShortAndInvalidInput(): void
    {
        self::assertSame('', qsl_normalize_date('2026-04'));
        self::assertSame('0900', qsl_normalize_time('9'));
        self::assertSame('', qsl_normalize_time(''));
    }

    public function testQslNormalizeCommentCompactsWhitespaceAndLimitsLength(): void
    {
        $comment = qsl_normalize_comment("  59   TNX   QSO   73  ");
        self::assertSame('59 TNX QSO 73', $comment);

        $long = str_repeat('A', 220);
        self::assertSame(180, strlen(qsl_normalize_comment($long)));
    }

    public function testParseAdifSupportsMultipleRecordsAndOptionalFields(): void
    {
        $adif = <<<ADIF
<CALL:6>ON4ABC<QSO_DATE:8>20260412<TIME_ON:4>0915<BAND:3>20M<MODE:3>SSB<EOR>
<CALL:6>F4XYZ/QSO<QSO_DATE:8>20260413<TIME_ON:4>2030<BAND:2>2M<MODE:2>FM<COMMENT:11>  TNX   73  <EOR>
ADIF;

        $rows = parse_adif($adif);
        self::assertCount(2, $rows);
        self::assertSame('ON4ABC', $rows[0]['call']);
        self::assertSame('F4XYZ/QSO', $rows[1]['call']);
        self::assertSame('TNX 73', $rows[1]['comment']);
    }

    public function testSanitizeSvgDocumentReturnsSafeFallbackForDangerousMarkup(): void
    {
        $dangerous = '<svg><image onload="evil()"></image></svg>';
        $sanitized = sanitize_svg_document($dangerous);

        self::assertStringContainsString('QSL sécurisée indisponible', $sanitized);
        self::assertStringNotContainsString('onload=', strtolower($sanitized));
    }

    public function testSanitizeSvgDocumentLeavesSafeSvgUntouched(): void
    {
        $safe = '<svg xmlns="http://www.w3.org/2000/svg"><text>ON4CRD</text></svg>';
        self::assertSame($safe, sanitize_svg_document($safe));
    }

    public function testGenerateQslSvgEscapesDynamicPayloadValues(): void
    {
        $svg = generate_qsl_svg([
            'title' => 'Test <b>QSL</b>',
            'own_call' => 'ON4CRD',
            'own_name' => 'Club <script>alert(1)</script>',
            'own_qth' => 'Charleroi',
            'qso_call' => 'F4XYZ',
            'qso_date' => '20260412',
            'time_on' => '0915',
            'band' => '20M',
            'mode' => 'SSB',
            'rst_sent' => '59',
            'rst_recv' => '57',
            'comment' => 'TNX <img src=x onerror=1> 73',
        ]);

        self::assertStringContainsString('&lt;b&gt;QSL&lt;/b&gt;', $svg);
        self::assertStringNotContainsString('<script', strtolower($svg));
        self::assertStringContainsString('&lt;img src=x onerror=1&gt;', $svg);
    }
}

