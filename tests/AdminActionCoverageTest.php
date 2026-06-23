<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminActionCoverageTest extends TestCase
{
    public function testAdminNonProposalPostActionsKeepControllerCoverage(): void
    {
        foreach ($this->nonProposalActionContracts() as $label => $contract) {
            $source = $this->source((string) $contract['page']);
            foreach ((array) $contract['controller_snippets'] as $snippet) {
                self::assertStringContainsString(
                    (string) $snippet,
                    $source,
                    sprintf('%s must keep controller coverage for %s.', $label, $snippet)
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
     * @return array<string, array{page: string, controller_snippets: list<string>, selenium: string, selenium_snippets: list<string>}>
     */
    private function nonProposalActionContracts(): array
    {
        return [
            'admin articles bulk and scheduled actions' => [
                'page' => 'pages/admin_articles.php',
                'controller_snippets' => ["'bulk_update_articles'", "'save_category'", "'retry_scheduled_article'", "'retry_scheduled_bulk'"],
                'selenium' => 'tests/selenium/admin-maintenance-coverage.test.js',
                'selenium_snippets' => ['bulk_update_articles', 'retry_scheduled_article', 'retry_scheduled_bulk'],
            ],
            'admin article editor actions' => [
                'page' => 'pages/admin_articles.php',
                'controller_snippets' => ["'save_article'", "'preview_article'", "'delete_article'", "'restore_revision'"],
                'selenium' => 'tests/selenium/admin-articles-wiki-workflow.test.js',
                'selenium_snippets' => ['save_article', 'preview_article', 'restore_revision', 'delete_article'],
            ],
            'admin news moderation actions' => [
                'page' => 'pages/admin_news.php',
                'controller_snippets' => ["'moderate_post'", "'assign_section_manager'"],
                'selenium' => 'tests/selenium/admin-maintenance-coverage.test.js',
                'selenium_snippets' => ['moderate_post', 'assign_section_manager'],
            ],
            'admin library bulk actions' => [
                'page' => 'pages/admin_library.php',
                'controller_snippets' => ["'bulk_delete_documents'", "'merge_tags'"],
                'selenium' => 'tests/selenium/admin-maintenance-coverage.test.js',
                'selenium_snippets' => ['bulk_delete_documents', 'merge_tags'],
            ],
            'admin classifieds bulk actions' => [
                'page' => 'pages/admin_classifieds.php',
                'controller_snippets' => ["'bulk_update'", "'delete'"],
                'selenium' => 'tests/selenium/admin-maintenance-coverage.test.js',
                'selenium_snippets' => ['bulk_update', 'admin_classifieds'],
            ],
            'admin advertising actions' => [
                'page' => 'pages/admin_ads.php',
                'controller_snippets' => ["'add_placement'", "'moderate_ad'"],
                'selenium' => 'tests/selenium/member-ads-workflow.test.js',
                'selenium_snippets' => ['add_placement', 'moderate_ad'],
            ],
            'admin privacy status actions' => [
                'page' => 'pages/admin_privacy.php',
                'controller_snippets' => ['privacy_update_request_status('],
                'selenium' => 'tests/selenium/member-privacy-notifications.test.js',
                'selenium_snippets' => ['admin_privacy', 'select[name="status"]', "'resolved'"],
            ],
            'admin translation review actions' => [
                'page' => 'pages/admin_translation_reviews.php',
                'controller_snippets' => ["'review_news_translation'", "'review_article_translation'"],
                'selenium' => 'tests/selenium/admin-editorial-translation-workflow.test.js',
                'selenium_snippets' => ['review_news_translation', 'review_article_translation'],
            ],
            'admin newsletter lifecycle actions' => [
                'page' => 'pages/admin_newsletters.php',
                'controller_snippets' => ["'set_status'", "'delete_subscriber'", "'send_campaign'"],
                'selenium' => 'tests/selenium/admin-newsletters-workflow.test.js',
                'selenium_snippets' => ['set_status', 'delete_subscriber', 'send_campaign'],
            ],
            'admin permission assignment actions' => [
                'page' => 'pages/admin_permissions.php',
                'controller_snippets' => ["'assign_role'", "'remove_role'"],
                'selenium' => 'tests/selenium/admin-configuration-workflows.test.js',
                'selenium_snippets' => ['assign_role', 'remove_role'],
            ],
            'admin member account actions' => [
                'page' => 'pages/admin_members.php',
                'controller_snippets' => ["'create_member'", "'update_member'", 'member_cleanup_registration_auth_orphan(', 'member_delete_unlinked_auth_user('],
                'selenium' => 'tests/selenium/admin-module-contract.test.js',
                'selenium_snippets' => ["admin_members: ['update_member', 'create_member']"],
            ],
            'admin module visibility actions' => [
                'page' => 'pages/admin_modules.php',
                'controller_snippets' => ['seed_modules();', 'UPDATE modules SET is_enabled = ?, visibility = ?', '$allowedVisibility'],
                'selenium' => 'tests/selenium/admin-configuration-workflows.test.js',
                'selenium_snippets' => ['admin_modules', 'visibility_', "moduleState('press'"],
            ],
            'admin event save actions' => [
                'page' => 'pages/admin_events.php',
                'controller_snippets' => ['admin_event_unique_slug(', 'INSERT INTO events', 'UPDATE events SET'],
                'selenium' => 'tests/selenium/admin-content-workflows.test.js',
                'selenium_snippets' => ['admin_events', 'event_view', 'events_feed', "format: 'ics'"],
            ],
            'admin wiki taxonomy and status actions' => [
                'page' => 'pages/admin_wiki.php',
                'controller_snippets' => ["'update_page_status'", "'delete_category'", "'delete_subcategory'", 'wiki_revisions'],
                'selenium' => 'tests/selenium/admin-articles-wiki-workflow.test.js',
                'selenium_snippets' => ['admin_wiki', 'pageStatusForm', 'delete_subcategory', 'delete_category'],
            ],
            'admin album maintenance and photo actions' => [
                'page' => 'pages/admin_albums.php',
                'controller_snippets' => ["'rebuild_thumbnails'", "'update_photo'", "'delete_photo'", "'reorder_photo'", "'finalize_album_creation'"],
                'selenium' => 'tests/selenium/admin-maintenance-coverage.test.js',
                'selenium_snippets' => ['rebuild_thumbnails', 'update_photo', 'delete_photo', 'reorder_photo'],
            ],
            'admin auction lot actions' => [
                'page' => 'pages/admin_auctions.php',
                'controller_snippets' => ['auction_unique_slug(', 'INSERT INTO auction_lots', 'UPDATE auction_lots SET'],
                'selenium' => 'tests/selenium/admin-auctions-workflow.test.js',
                'selenium_snippets' => ['admin_auctions', 'auction_view', 'auction_bid'],
            ],
            'admin editorial content actions' => [
                'page' => 'pages/admin_editorial.php',
                'controller_snippets' => ['save_editorial_content(', "'committee.title'", "'press.contact'"],
                'selenium' => 'tests/selenium/admin-editorial-translation-workflow.test.js',
                'selenium_snippets' => ['admin_editorial', 'content[committee_title][fr]', 'editorial_contents'],
            ],
            'admin press publication actions' => [
                'page' => 'pages/admin_press.php',
                'controller_snippets' => ["'contact'", "'release'"],
                'selenium' => 'tests/selenium/admin-configuration-workflows.test.js',
                'selenium_snippets' => ['admin_press', 'contact', 'release'],
            ],
            'admin live feed update actions' => [
                'page' => 'pages/admin_live_feeds.php',
                'controller_snippets' => ['validate_remote_feed_url(', 'UPDATE live_feeds SET'],
                'selenium' => 'tests/selenium/admin-configuration-workflows.test.js',
                'selenium_snippets' => ['admin_live_feeds', 'feeds[', 'notes'],
            ],
            'admin dinner reservation actions' => [
                'page' => 'pages/admin_dinner_reservations.php',
                'controller_snippets' => ['dinner_reservations', 'dinner_reservation_lines', 'INSERT INTO dinner_reservations'],
                'selenium' => 'tests/selenium/admin-configuration-workflows.test.js',
                'selenium_snippets' => ['admin_dinner_reservations', 'reserved_by', 'quantity-input'],
            ],
            'admin committee membership actions' => [
                'page' => 'pages/admin_committee.php',
                'controller_snippets' => ['committee_move', 'committee_role', 'committee_bio'],
                'selenium' => 'tests/selenium/admin-committee-workflow.test.js',
                'selenium_snippets' => ['admin_committee', 'committee_role', 'committee_bio'],
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
