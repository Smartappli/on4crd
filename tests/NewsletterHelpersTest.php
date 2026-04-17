<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class NewsletterHelpersTest extends TestCase
{
    public function testNewsletterNormalizeEmailReturnsLowercaseValidEmail(): void
    {
        self::assertSame('user@example.org', newsletter_normalize_email(' User@Example.org '));
        self::assertSame('', newsletter_normalize_email('not-an-email'));
    }

    public function testNewsletterParseCsvEmailsSupportsCommaAndSemicolonAndDeduplicates(): void
    {
        $csv = "email\nfoo@example.org\nbar@example.org;baz@example.org\nfoo@example.org";
        $emails = newsletter_parse_csv_emails($csv);

        self::assertContains('foo@example.org', $emails);
        self::assertContains('bar@example.org', $emails);
        self::assertContains('baz@example.org', $emails);
        self::assertCount(3, $emails);
    }

    public function testNewsletterGenerateTokenHasExpectedLength(): void
    {
        $token = newsletter_generate_token();

        self::assertSame(48, strlen($token));
        self::assertMatchesRegularExpression('/^[a-f0-9]{48}$/', $token);
    }
}
