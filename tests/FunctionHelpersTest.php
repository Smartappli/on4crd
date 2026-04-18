<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FunctionHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER = [];
        $_POST = [];
        $_GET = [];
    }

    public function testEscapeHelperEscapesHtml(): void
    {
        self::assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', e('<script>alert(1)</script>'));
    }

    public function testSlugifyTransliteratesAndNormalizes(): void
    {
        self::assertSame('ecole-radio-club-2026', slugify('École Radio Club 2026'));
    }

    public function testNormalizeHttpUrlRejectsJavascriptScheme(): void
    {
        $this->expectException(RuntimeException::class);
        normalize_http_url('javascript:alert(1)');
    }

    public function testSanitizeHrefAttributeAllowsRelativeAndBlocksJavascript(): void
    {
        self::assertSame('/agenda', sanitize_href_attribute('/agenda'));
        self::assertNull(sanitize_href_attribute('javascript:alert(1)'));
    }

    public function testSanitizeRichHtmlRemovesDangerousMarkup(): void
    {
        $html = '<p><a href="javascript:alert(1)" onclick="alert(1)">Lien</a>'
            . '<img src="https://example.org/test.jpg" onerror="alert(1)"></p><script>alert(1)</script>';

        $sanitized = sanitize_rich_html($html);

        self::assertStringNotContainsString('<script', $sanitized);
        self::assertStringNotContainsString('onclick=', $sanitized);
        self::assertStringNotContainsString('javascript:', $sanitized);
        self::assertStringContainsString('<img', $sanitized);
        self::assertStringContainsString('loading="lazy"', $sanitized);
    }

    public function testSafeStoragePublicPathAcceptsOnlyWhitelistedPrefixes(): void
    {
        self::assertSame('storage/press/doc.pdf', safe_storage_public_path('storage/press/doc.pdf'));

        $this->expectException(RuntimeException::class);
        safe_storage_public_path('storage/uploads/doc.pdf');
    }

    public function testCsrfTokenAndVerificationWork(): void
    {
        $token = csrf_token();
        $_POST['_csrf'] = $token;

        verify_csrf();

        self::assertSame(64, strlen($token));
    }

    public function testValidateRemoteFeedUrlRejectsLocalNetworks(): void
    {
        $this->expectException(RuntimeException::class);
        validate_remote_feed_url('http://127.0.0.1/feed.xml');
    }

    public function testIsHttpsRequestReturnsTrueWhenServerPortIsInteger443(): void
    {
        $_SERVER['SERVER_PORT'] = 443;

        self::assertTrue(is_https_request());
    }

    public function testIsHttpsRequestUsesFirstForwardedProtoValue(): void
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https, http';

        self::assertTrue(is_https_request());
    }
}
