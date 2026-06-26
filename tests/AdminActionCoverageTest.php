<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminActionCoverageTest extends TestCase
{
    public function testAdminNonProposalPostActionsKeepControllerCoverage(): void
    {
        foreach ($this->nonProposalActionContracts() as $label => $contract) {
            $source = $this->contractControllerSource($contract);
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

    public function testAdminProposalPostActionsKeepControllerCoverage(): void
    {
        foreach ($this->proposalActionContracts() as $label => $contract) {
            $source = $this->contractControllerSource($contract);
            foreach ((array) $contract['controller_snippets'] as $snippet) {
                self::assertStringContainsString(
                    (string) $snippet,
                    $source,
                    sprintf('%s must keep controller coverage for %s.', $label, $snippet)
                );
            }
        }
    }

    public function testAdminProposalPostActionsKeepSeleniumCoverage(): void
    {
        foreach ($this->proposalActionContracts() as $label => $contract) {
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
     * @return array<string, array{page: string, controller_snippets: list<string>, selenium: string, selenium_snippets: list<string>, source_paths?: list<string>}>
     */
    private function nonProposalActionContracts(): array
    {
        return [
            'admin articles bulk and scheduled actions' => [
                'page' => 'pages/admin_articles.php',
                'controller_snippets' => ["'bulk_update_articles'", "'save_category'", "'retry_scheduled_article'", "'retry_scheduled_bulk'"],
                'selenium' => 'tests/selenium/admin-maintenance-coverage.test.js',
                'selenium_snippets' => ['save_category', 'bulk_update_articles', 'retry_scheduled_article', 'retry_scheduled_bulk'],
            ],
            'admin article editor actions' => [
                'page' => 'pages/admin_articles.php',
                'controller_snippets' => ["'save_article'", "'preview_article'", "'delete_article'", "'restore_revision'"],
                'selenium' => 'tests/selenium/admin-articles-wiki-workflow.test.js',
                'selenium_snippets' => ['save_article', 'preview_article', 'restore_revision', 'delete_article'],
            ],
            'admin article taxonomy actions' => [
                'page' => 'pages/admin_articles.php',
                'controller_snippets' => ["'add_category'", "'update_category'", "'delete_category'", "'add_subcategory'", "'update_subcategory'", "'delete_subcategory'"],
                'selenium' => 'tests/selenium/admin-articles-wiki-workflow.test.js',
                'selenium_snippets' => ['add_category', 'add_subcategory', 'delete_subcategory', 'delete_category'],
            ],
            'admin news save actions' => [
                'page' => 'pages/admin_news.php',
                'controller_snippets' => ["'save_post'", 'INSERT INTO news_posts', 'UPDATE news_posts SET'],
                'selenium' => 'tests/selenium/admin-content-workflows.test.js',
                'selenium_snippets' => ['admin_news', 'save_post', 'news_view'],
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
            'admin library upload taxonomy and delete actions' => [
                'page' => 'pages/admin_library.php',
                'controller_snippets' => ["'upload'", "'delete_document'", "'add_category'", "'update_category'", "'delete_category'", "'add_subcategory'", "'update_subcategory'", "'delete_subcategory'", "'add_subsubcategory'", "'update_subsubcategory'", "'delete_subsubcategory'", 'library_store_upload('],
                'selenium' => 'tests/selenium/admin-module-crud-workflows.test.js',
                'selenium_snippets' => ['admin_library', 'admin-library-upload-form', 'delete_document', 'createLibraryDocumentFromAdminRoute', 'createUpdateDeleteAdminTaxonomy', 'add_subsubcategory', 'update_subsubcategory', 'delete_subsubcategory'],
            ],
            'admin classifieds bulk actions' => [
                'page' => 'pages/admin_classifieds.php',
                'controller_snippets' => ["'bulk_update'", "'delete'"],
                'selenium' => 'tests/selenium/admin-maintenance-coverage.test.js',
                'selenium_snippets' => ['bulk_update', 'admin_classifieds', 'classifiedEditForm', 'singleDeleteForm'],
            ],
            'admin advertising actions' => [
                'page' => 'pages/admin_ads.php',
                'controller_snippets' => ["'add_placement'", "'moderate_ad'"],
                'selenium' => 'tests/selenium/member-ads-workflow.test.js',
                'selenium_snippets' => ['add_placement', 'moderate_ad'],
            ],
            'admin privacy status actions' => [
                'page' => 'pages/admin_privacy.php',
                'controller_snippets' => ['privacy_update_request_status(', 'apply_erasure'],
                'selenium' => 'tests/selenium/member-privacy-notifications.test.js',
                'selenium_snippets' => ['admin_privacy', 'select[name="status"]', "'resolved'", 'apply_erasure', 'createErasureRequestFixture'],
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
            'admin newsletter import and campaign actions' => [
                'page' => 'pages/admin_newsletters.php',
                'controller_snippets' => ["'add_subscriber'", "'import_csv'", "'create_campaign'"],
                'selenium' => 'tests/selenium/admin-newsletters-workflow.test.js',
                'selenium_snippets' => ['add_subscriber', 'import_csv', 'create_campaign'],
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
                'selenium' => 'tests/selenium/admin-configuration-workflows.test.js',
                'selenium_snippets' => ['admin_members', 'create_member', 'memberForm', 'createdMemberForm'],
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
            'admin dashboard widget actions' => [
                'page' => 'pages/admin_dashboard.php',
                'controller_snippets' => ['dashboard_widget_settings', 'REPLACE INTO dashboard_widget_settings', "'widget_'"],
                'selenium' => 'tests/selenium/member-account-dashboard-workflow.test.js',
                'selenium_snippets' => ['admin_dashboard', 'widget_radio_clocks', 'dashboard_widget_settings'],
            ],
            'admin wiki taxonomy and status actions' => [
                'page' => 'pages/admin_wiki.php',
                'controller_snippets' => ["'update_page_status'", "'add_category'", "'update_category'", "'delete_category'", "'add_subcategory'", "'update_subcategory'", "'delete_subcategory'", 'wiki_revisions'],
                'selenium' => 'tests/selenium/admin-articles-wiki-workflow.test.js',
                'selenium_snippets' => ['admin_wiki', 'add_category', 'add_subcategory', 'pageStatusForm', 'delete_subcategory', 'delete_category'],
            ],
            'admin taxonomy label update actions' => [
                'page' => 'pages/admin_articles.php',
                'source_paths' => ['pages/admin_wiki.php', 'pages/admin_library.php', 'app/member_webotheque.php', 'app/member_module_documents.php'],
                'controller_snippets' => ["'update_category'", "'update_subcategory'"],
                'selenium' => 'tests/selenium/admin-maintenance-coverage.test.js',
                'selenium_snippets' => ['update_category', 'update_subcategory', 'taxonomyEditState'],
            ],
            'admin album lifecycle and taxonomy actions' => [
                'page' => 'pages/admin_albums.php',
                'controller_snippets' => ["'add_category'", "'update_category'", "'delete_category'", "'add_subcategory'", "'update_subcategory'", "'delete_subcategory'", "'create_album'", "'update_album'", "'delete_album'"],
                'selenium' => 'tests/selenium/admin-module-crud-workflows.test.js',
                'selenium_snippets' => ['admin_albums', 'createAlbumFromAdminRoute', 'update_album', 'delete_album', 'createUpdateDeleteAdminTaxonomy'],
            ],
            'admin album wizard upload actions' => [
                'page' => 'pages/admin_albums.php',
                'controller_snippets' => ["'create_album'", "'upload_photo'", "'finalize_album_creation'"],
                'selenium' => 'tests/selenium/admin-albums.test.js',
                'selenium_snippets' => ['create_album', 'upload_photo', 'album-wizard'],
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
            'admin webotheque link and taxonomy actions' => [
                'page' => 'pages/admin_webotheque.php',
                'source_paths' => ['app/member_webotheque.php'],
                'controller_snippets' => ['render_admin_webotheque_page();', "'add_link'", "'update_link'", "'delete_link'", "'update_category'", "'delete_subcategory'"],
                'selenium' => 'tests/selenium/admin-module-crud-workflows.test.js',
                'selenium_snippets' => ['admin_webotheque', 'createWebothequeFromAdminRoute', 'update_link', 'delete_link', 'createUpdateDeleteAdminTaxonomy'],
            ],
            'admin presentation and video document actions' => [
                'page' => 'pages/admin_presentations.php',
                'source_paths' => ['pages/admin_videos.php', 'app/member_module_documents.php'],
                'controller_snippets' => ["render_admin_member_document_module_page('presentations')", "render_admin_member_document_module_page('videos')", "'upload'", "'delete_document'", "'update_category'", "'delete_subcategory'"],
                'selenium' => 'tests/selenium/admin-module-crud-workflows.test.js',
                'selenium_snippets' => ['admin_presentations', 'admin_videos', 'createModuleDocumentFromAdminRoute', 'deleteMemberModuleDocumentFromAdminRoute', 'createUpdateDeleteAdminTaxonomy'],
            ],
            'admin pv fichiers and telechargements document actions' => [
                'page' => 'pages/admin_pv.php',
                'source_paths' => ['pages/admin_fichiers.php', 'pages/admin_telechargements.php', 'app/member_module_documents.php'],
                'controller_snippets' => ["render_admin_member_document_module_page('pv')", "render_admin_member_document_module_page('fichiers')", "redirect('admin_fichiers')", "'upload'", "'delete_document'", 'member_document_store_upload('],
                'selenium' => 'tests/selenium/member-document-modules.test.js',
                'selenium_snippets' => ['admin_pv', 'admin_fichiers', 'telechargements', 'delete_document', '#admin-member-document-upload'],
            ],
            'admin pv and fichiers taxonomy actions' => [
                'page' => 'pages/admin_pv.php',
                'source_paths' => ['pages/admin_fichiers.php', 'app/member_module_documents.php'],
                'controller_snippets' => ["'update_category'", "'delete_category'", "'update_subcategory'", "'delete_subcategory'"],
                'selenium' => 'tests/selenium/admin-module-crud-workflows.test.js',
                'selenium_snippets' => ['admin_pv', 'admin_fichiers', 'createUpdateDeleteAdminTaxonomy'],
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
                'selenium_snippets' => ['admin_committee', 'committee_role', 'committee_bio', 'committee_move', 'moveDownButton'],
            ],
        ];
    }

    /**
     * @return array<string, array{page: string, controller_snippets: list<string>, selenium: string, selenium_snippets: list<string>, source_paths?: list<string>}>
     */
    private function proposalActionContracts(): array
    {
        return [
            'admin dashboard proposal moderation' => [
                'page' => 'pages/admin.php',
                'source_paths' => ['app/admin_helpers.php'],
                'controller_snippets' => ['update_content_proposal_status', 'admin_update_content_proposal_status(', 'admin_apply_accepted_content_proposal('],
                'selenium' => 'tests/selenium/admin-proposals-workflow.test.js',
                'selenium_snippets' => ['proposalDashboardForm', 'updateDashboardProposal', "'reviewed'", "'rejected'", "'accepted'"],
            ],
            'admin article proposal moderation' => [
                'page' => 'pages/admin_articles.php',
                'controller_snippets' => ["'update_proposal_status'", 'area = "articles"'],
                'selenium' => 'tests/selenium/admin-articles-wiki-workflow.test.js',
                'selenium_snippets' => ['submitProposalStatus', 'admin_articles', "'rejected'"],
            ],
            'admin wiki proposal moderation' => [
                'page' => 'pages/admin_wiki.php',
                'controller_snippets' => ["'update_proposal_status'", 'area = "wiki"'],
                'selenium' => 'tests/selenium/admin-articles-wiki-workflow.test.js',
                'selenium_snippets' => ['submitProposalStatus', 'admin_wiki', "'reviewed'"],
            ],
            'admin library proposal moderation' => [
                'page' => 'pages/admin_library.php',
                'controller_snippets' => ["'update_proposal_status'", 'area = "members_library"', 'member_library_apply_accepted_proposal('],
                'selenium' => 'tests/selenium/admin-proposals-workflow.test.js',
                'selenium_snippets' => ['admin_library', 'updateModuleProposal', 'libraryCategoryByLabel'],
            ],
            'admin webotheque proposal moderation' => [
                'page' => 'pages/admin_webotheque.php',
                'source_paths' => ['app/member_webotheque.php'],
                'controller_snippets' => ["'update_proposal_status'", 'area = "webotheque"', 'webotheque_apply_accepted_proposal('],
                'selenium' => 'tests/selenium/admin-proposals-workflow.test.js',
                'selenium_snippets' => ['admin_webotheque', 'updateModuleProposal', 'webothequeLinkByUrl'],
            ],
            'admin events proposal moderation' => [
                'page' => 'app/admin_helpers.php',
                'controller_snippets' => ['admin_apply_accepted_event_proposal(', 'if ($area === \'events\')'],
                'selenium' => 'tests/selenium/admin-proposals-workflow.test.js',
                'selenium_snippets' => ['updateDashboardProposal', 'eventByTitle', "'events'"],
            ],
            'admin auctions proposal moderation' => [
                'page' => 'app/admin_helpers.php',
                'controller_snippets' => ['admin_apply_accepted_auction_proposal(', 'if ($area === \'auctions\')'],
                'selenium' => 'tests/selenium/admin-proposals-workflow.test.js',
                'selenium_snippets' => ['updateDashboardProposal', 'auctionLotByTitle', "'auctions'"],
            ],
            'admin news proposal moderation' => [
                'page' => 'app/admin_helpers.php',
                'controller_snippets' => ['admin_apply_accepted_news_proposal(', 'if ($area === \'news\')'],
                'selenium' => 'tests/selenium/admin-proposals-workflow.test.js',
                'selenium_snippets' => ['updateDashboardProposal', 'newsPostByTitle', 'newsSectionByName', "'news'"],
            ],
        ];
    }

    /**
     * @param array{page: string, source_paths?: list<string>} $contract
     */
    private function contractControllerSource(array $contract): string
    {
        $paths = array_merge([(string) $contract['page']], (array) ($contract['source_paths'] ?? []));

        return implode("\n", array_map(fn(string $path): string => $this->source($path), $paths));
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(dirname(__DIR__) . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        self::assertIsString($source, sprintf('Could not read %s.', $relativePath));

        return $source;
    }
}
