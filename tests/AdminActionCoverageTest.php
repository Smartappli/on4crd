<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminActionCoverageTest extends TestCase
{
    public function testAdminNonProposalPostActionsKeepControllerCoverage(): void
    {
        foreach ($this->nonProposalActionContracts() as $label => $contract) {
            $source = $this->source((string) $contract['page']);
            foreach ((array) $contract['actions'] as $action) {
                self::assertStringContainsString(
                    "'" . $action . "'",
                    $source,
                    sprintf('%s must keep POST action %s.', $label, $action)
                );
            }
        }
    }

    public function testAdminNonProposalPostActionsKeepSeleniumCoverage(): void
    {
        foreach ($this->nonProposalActionContracts() as $label => $contract) {
            $source = $this->source((string) $contract['selenium']);
            foreach ((array) $contract['selenium_snippets'] as $snippet) {
                self::assertStringContainsString(
                    (string) $snippet,
                    $source,
                    sprintf('%s must keep Selenium coverage for %s.', $label, $snippet)
                );
            }
        }
    }

    /**
     * @return array<string, array{page: string, actions: list<string>, selenium: string, selenium_snippets: list<string>}>
     */
    private function nonProposalActionContracts(): array
    {
        return [
            'admin articles bulk and scheduled actions' => [
                'page' => 'pages/admin_articles.php',
                'actions' => ['bulk_update_articles', 'save_category', 'retry_scheduled_article', 'retry_scheduled_bulk'],
                'selenium' => 'tests/selenium/admin-maintenance-coverage.test.js',
                'selenium_snippets' => ['bulk_update_articles', 'retry_scheduled_article', 'retry_scheduled_bulk'],
            ],
            'admin news moderation actions' => [
                'page' => 'pages/admin_news.php',
                'actions' => ['moderate_post', 'assign_section_manager'],
                'selenium' => 'tests/selenium/admin-maintenance-coverage.test.js',
                'selenium_snippets' => ['moderate_post', 'assign_section_manager'],
            ],
            'admin library bulk actions' => [
                'page' => 'pages/admin_library.php',
                'actions' => ['bulk_delete_documents', 'merge_tags'],
                'selenium' => 'tests/selenium/admin-maintenance-coverage.test.js',
                'selenium_snippets' => ['bulk_delete_documents', 'merge_tags'],
            ],
            'admin classifieds bulk actions' => [
                'page' => 'pages/admin_classifieds.php',
                'actions' => ['bulk_update', 'delete'],
                'selenium' => 'tests/selenium/admin-maintenance-coverage.test.js',
                'selenium_snippets' => ['bulk_update', 'admin_classifieds'],
            ],
            'admin advertising actions' => [
                'page' => 'pages/admin_ads.php',
                'actions' => ['add_placement', 'moderate_ad'],
                'selenium' => 'tests/selenium/member-ads-workflow.test.js',
                'selenium_snippets' => ['add_placement', 'moderate_ad'],
            ],
            'admin privacy status actions' => [
                'page' => 'pages/admin_privacy.php',
                'actions' => ['privacy_update_request_status'],
                'selenium' => 'tests/selenium/member-privacy-notifications.test.js',
                'selenium_snippets' => ['admin_privacy', 'status"])), \'resolved\''],
            ],
            'admin translation review actions' => [
                'page' => 'pages/admin_translation_reviews.php',
                'actions' => ['review_news_translation', 'review_article_translation'],
                'selenium' => 'tests/selenium/admin-editorial-translation-workflow.test.js',
                'selenium_snippets' => ['review_news_translation', 'review_article_translation'],
            ],
            'admin newsletter lifecycle actions' => [
                'page' => 'pages/admin_newsletters.php',
                'actions' => ['set_status', 'delete_subscriber', 'send_campaign'],
                'selenium' => 'tests/selenium/admin-newsletters-workflow.test.js',
                'selenium_snippets' => ['set_status', 'delete_subscriber', 'send_campaign'],
            ],
            'admin permission assignment actions' => [
                'page' => 'pages/admin_permissions.php',
                'actions' => ['assign_role', 'remove_role'],
                'selenium' => 'tests/selenium/admin-configuration-workflows.test.js',
                'selenium_snippets' => ['assign_role', 'remove_role'],
            ],
        ];
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(dirname(__DIR__) . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        self::assertIsString($source, sprintf('Could not read %s.', $relativePath));

        return $source;
    }
}
