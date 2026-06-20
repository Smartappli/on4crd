<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class GitHubWorkflowContractTest extends TestCase
{
    private function source(string $path): string
    {
        $source = file_get_contents(dirname(__DIR__) . '/' . $path);
        self::assertIsString($source);

        return $source;
    }

    public function testComposerExposesPhpStanAsProjectScript(): void
    {
        $composer = json_decode($this->source('composer.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($composer);

        self::assertArrayHasKey('phpstan/phpstan', $composer['require-dev'] ?? []);
        self::assertSame('@php vendor/bin/phpstan analyse --no-progress --memory-limit=1G', $composer['scripts']['analyse'] ?? null);
    }

    public function testCiWorkflowRunsPhpStanThroughComposer(): void
    {
        $workflow = $this->source('.github/workflows/ci.yml');

        self::assertStringContainsString('SAST (PHPStan)', $workflow);
        self::assertStringContainsString('run: composer analyse', $workflow);
    }

    public function testBrowserWorkflowRunsSeleniumWithDatabaseAndArtifacts(): void
    {
        $workflow = $this->source('.github/workflows/tests.yml');

        self::assertStringContainsString('services:', $workflow);
        self::assertStringContainsString('mysql:', $workflow);
        self::assertMatchesRegularExpression('/^\s*image:\s+mysql:(?!latest\b)[0-9][0-9A-Za-z._-]*\s*$/m', $workflow);
        self::assertStringContainsString('ON4CRD_CONFIG_FILE:', $workflow);
        self::assertStringContainsString('SELENIUM_BASE_URL:', $workflow);
        self::assertStringContainsString('SELENIUM_CHROME_BINARY', $workflow);
        self::assertStringContainsString('scripts/prepare_selenium_ci.php', $workflow);
        self::assertStringContainsString('scripts/create_selenium_admin.php', $workflow);
        self::assertStringContainsString('tests/selenium/seed_fixtures.php', $workflow);
        self::assertStringContainsString('ON4CRD_ALLOW_SELENIUM_FIXTURES', $workflow);
        self::assertStringContainsString('npm run test:selenium', $workflow);
        self::assertStringContainsString('selenium-artifacts', $workflow);
    }

    public function testImageWorkflowCoversRepositoryImagesBeyondAssets(): void
    {
        $workflow = $this->source('.github/workflows/images.yml');

        foreach (['jpg', 'jpeg', 'png', 'webp', 'avif'] as $extension) {
            self::assertStringContainsString("- '**/*." . $extension . "'", $workflow);
            self::assertStringNotContainsString("- 'assets/**/*." . $extension . "'", $workflow);
        }

        self::assertStringContainsString('calibreapp/image-actions', $workflow);
        self::assertStringContainsString('Runtime uploads under storage/uploads are ignored by git', $workflow);
        self::assertStringContainsString('app/upload_helpers.php', $workflow);
    }

    public function testSeleniumCiPreparationScriptBuildsConfigAndSchema(): void
    {
        $script = $this->source('scripts/prepare_selenium_ci.php');

        self::assertStringContainsString('SELENIUM_CONFIG_PATH', $script);
        self::assertStringContainsString('SELENIUM_DB_DSN', $script);
        self::assertStringContainsString('/schema/schema.sql', $script);
        self::assertStringContainsString('/app/bootstrap.php', $script);
        self::assertStringContainsString('CREATE DATABASE IF NOT EXISTS', $script);
    }
}
