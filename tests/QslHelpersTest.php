<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QslHelpersTest extends TestCase
{
    public function testQslNormalizationHelpers(): void
    {
        self::assertSame('ON4CRD/P', qsl_normalize_callsign(' on4crd / p '));
        self::assertSame('20260412', qsl_normalize_date('2026-04-12'));
        self::assertSame('0915', qsl_normalize_time('9:15'));
        self::assertSame('59 73 TNX', qsl_normalize_comment(" 59   73   TNX "));
    }

    public function testParseAdifReadsBasicRecord(): void
    {
        $adif = <<<ADIF
<CALL:6>ON4ABC<QSO_DATE:8>20260412<TIME_ON:4>0915<BAND:3>20M<MODE:3>SSB<RST_SENT:2>59<RST_RCVD:2>57<EOR>
ADIF;

        $rows = parse_adif($adif);

        self::assertCount(1, $rows);
        self::assertSame('ON4ABC', $rows[0]['call']);
        self::assertSame('20260412', $rows[0]['qso_date']);
        self::assertSame('0915', $rows[0]['time_on']);
        self::assertSame('20M', $rows[0]['band']);
        self::assertSame('SSB', $rows[0]['mode']);
    }

    public function testGenerateQslSvgBuildsSafeSvg(): void
    {
        $payload = [
            'title' => 'Contact 20m',
            'own_call' => 'ON4CRD',
            'own_name' => 'ON4CRD Club',
            'own_qth' => 'Charleroi',
            'qso_call' => 'F4XYZ',
            'qso_date' => '20260412',
            'time_on' => '0915',
            'band' => '20M',
            'mode' => 'SSB',
            'rst_sent' => '59',
            'rst_recv' => '57',
            'comment' => 'TNX QSO 73',
            'template_name' => 'classic'
        ];

        $svg = generate_qsl_svg($payload);

        self::assertStringContainsString('<svg', $svg);
        self::assertStringContainsString('ON4CRD', $svg);
        self::assertStringContainsString('F4XYZ', $svg);
        self::assertStringContainsString('20M', $svg);
        self::assertStringNotContainsString('<script', $svg);
    }

    public function testBuildQslSvgPayloadBuildsNormalizedPayloadFromUserAndData(): void
    {
        $payload = build_qsl_svg_payload(
            [
                'callsign' => 'ON4CRD',
                'full_name' => 'Radio Club',
                'qth' => 'Charleroi',
            ],
            [
                'qso_call' => ' f4xyz/p ',
                'qso_date' => '2026-04-25',
                'time_on' => '9:5',
                'band' => '20m',
                'mode' => 'ssb',
                'rst_sent' => '59',
                'rst_recv' => '57',
            ],
            '  TNX   73  '
        );

        self::assertSame('ON4CRD', $payload['own_call']);
        self::assertSame('F4XYZ/P', $payload['qso_call']);
        self::assertSame('20260425', $payload['qso_date']);
        self::assertSame('0905', $payload['time_on']);
        self::assertSame('20M', $payload['band']);
        self::assertSame('SSB', $payload['mode']);
        self::assertSame('TNX 73', $payload['comment']);
    }

    public function testQslCardTitleAndDisplayFormattingHelpers(): void
    {
        $payload = [
            'qso_call' => 'F4XYZ',
            'qso_date' => '20260425',
            'band' => '20m',
            'mode' => 'ssb',
        ];

        self::assertSame('QSL • F4XYZ • 25/04/2026 • 20M • SSB', qsl_card_title($payload));
        self::assertSame('25/04/2026', qsl_format_display_date('2026-04-25'));
        self::assertSame('09:05', qsl_format_display_time('9:5'));
    }

    public function testSanitizeSvgDocumentFallsBackForDangerousSvg(): void
    {
        $svg = sanitize_svg_document('<svg><script>alert(1)</script></svg>');

        self::assertStringContainsString('QSL sécurisée indisponible', $svg);
        self::assertStringNotContainsString('<script', $svg);
    }
}
