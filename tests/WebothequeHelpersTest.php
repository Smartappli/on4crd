<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class WebothequeHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER = [];
        $_POST = [];
        $_GET = [];
    }

    public function testUrlNormalizationKeepsOnlyHttpUrls(): void
    {
        self::assertSame('https://example.org/path?q=1', webotheque_normalize_url(' example.org/path?q=1 '));
        self::assertSame('http://example.org', webotheque_normalize_url('http://example.org'));
        self::assertSame('', webotheque_normalize_url('ftp://example.org/file.zip'));
        self::assertSame('', webotheque_normalize_url('javascript:alert(1)'));
    }

    public function testDomainExtractionNormalizesWwwPrefix(): void
    {
        self::assertSame('example.org', webotheque_domain_from_url('https://www.Example.org/path'));
        self::assertSame('sub.example.org', webotheque_domain_from_url('https://sub.example.org/path'));
        self::assertSame('', webotheque_domain_from_url('not-a-url'));
    }

    public function testTagsAreSplitCleanedAndDeduplicated(): void
    {
        self::assertSame(
            [
                'ft8' => 'FT8',
                'antennas' => 'antennas',
                'propagation' => 'Propagation',
            ],
            webotheque_tags_from_text(' FT8, antennas; #FT8 ; Propagation ')
        );
    }

    public function testCategoryInputAcceptsKnownCodesOnly(): void
    {
        $categories = [
            'general' => 'General',
            'modes-numeriques' => 'Modes numeriques',
        ];

        self::assertSame('general', webotheque_category_from_input('', $categories));
        self::assertSame('modes-numeriques', webotheque_category_from_input('Modes numeriques', $categories));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('err_category');
        webotheque_category_from_input('Unknown topic', $categories);
    }

    public function testCardsEscapeContentAndExposeSafeExternalLinks(): void
    {
        $html = render_webotheque_cards([
            [
                'title' => '<b>Useful link</b>',
                'url' => 'https://www.example.org/path',
                'description' => '<script>alert(1)</script>Readable',
                'tags' => 'FT8',
                'category' => 'general',
            ],
        ], [
            'link' => 'Link',
            'open' => 'Open',
            'tags' => 'Tags',
            'domain_field' => 'Topic',
        ], [
            'general' => 'General',
        ]);

        self::assertStringContainsString('&lt;b&gt;Useful link&lt;/b&gt;', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;Readable', $html);
        self::assertStringContainsString('href="https://www.example.org/path"', $html);
        self::assertStringContainsString('target="_blank" rel="noopener noreferrer"', $html);
        self::assertStringContainsString('example.org', $html);
        self::assertStringNotContainsString('<script>', $html);
    }
}
