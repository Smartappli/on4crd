<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminUsabilityContractTest extends TestCase
{
    public function testMemberAdministrationKeepsScalablePaginationAndFormRecovery(): void
    {
        $source = $this->source('pages/admin_members.php');

        self::assertStringContainsString('SELECT COUNT(*) FROM members', $source);
        self::assertStringContainsString("' LIMIT ' . \$memberPerPage . ' OFFSET ' . \$memberOffset", $source);
        self::assertStringContainsString('class="admin-pagination"', $source);
        self::assertStringContainsString("'page' => \$postReturnPage", $source);
        self::assertStringContainsString("\$_SESSION['_admin_member_create_old']", $source);
        self::assertStringContainsString("\$_SESSION['_admin_member_update_old']", $source);
        self::assertStringContainsString('class="admin-member-editor"', $source);
        self::assertStringContainsString('<details', $source);
    }

    public function testAdminNavigationIsGroupedByTaskDomain(): void
    {
        $catalog = $this->source('app/module_catalog.php');
        $renderer = $this->source('app/layout_renderer.php');

        foreach (['content', 'media', 'communication', 'members', 'settings'] as $group) {
            self::assertStringContainsString("'group' => '" . $group . "'", $catalog);
            self::assertStringContainsString("'" . $group . "'", $renderer);
        }
        self::assertStringContainsString('class="admin-shell-group"', $renderer);
    }

    public function testAdminCommonUxProvidesDirtyFormsConfirmationsAndResponsiveTables(): void
    {
        $renderer = $this->source('app/layout_renderer.php');
        $script = $this->source('assets/js/modules/admin_common.js');
        $styles = $this->source('assets/css/modules/admin.css');

        self::assertStringContainsString('assets/js/modules/admin_common.js', $renderer);
        self::assertStringContainsString('data-admin-unsaved-label', $renderer);
        self::assertStringContainsString("form[data-admin-dirty-track]", $script);
        self::assertStringContainsString("beforeunload", $script);
        self::assertStringContainsString('unsavedChangesLabel', $script);
        self::assertStringContainsString("data-confirm-message", $script);
        self::assertStringContainsString("admin-responsive-table", $script);
        self::assertStringContainsString("cell.scope = 'col'", $script);
        self::assertStringContainsString("table.admin-responsive-table", $styles);
        self::assertStringContainsString("form[data-admin-wizard]", $script);
        self::assertStringContainsString("data-admin-wizard-step", $script);
        self::assertStringContainsString("aria-current', 'step'", $script);
        self::assertStringContainsString('firstInvalid.reportValidity()', $script);
        self::assertStringContainsString("admin-wizard-progress", $styles);
        self::assertStringContainsString("admin-wizard-controls", $styles);
        self::assertStringContainsString('bindProposalMenuWizards', $this->source('assets/js/modules/module_dialogs.js'));
        self::assertStringContainsString('proposal-wizard-dialog', $this->source('assets/css/modules/shared.css'));
    }

    public function testArticleEditorUsesTheProgressiveWizardContract(): void
    {
        $source = $this->source('pages/admin_articles.php');

        self::assertStringContainsString('data-admin-wizard', $source);
        self::assertStringContainsString('data-admin-wizard-step', $source);
        self::assertStringContainsString("data-admin-wizard-title=\"<?= e(\$t('content_section')) ?>\"", $source);
        self::assertStringContainsString("data-admin-wizard-title=\"<?= e(\$t('taxonomy_section')) ?>\"", $source);
        self::assertStringContainsString("data-admin-wizard-title=\"<?= e(\$t('publication_section')) ?>\"", $source);
    }

    public function testContentAndAgendaFormsUseTheProgressiveWizardContract(): void
    {
        foreach ([
            'pages/admin_library.php',
            'pages/admin_news.php',
            'pages/admin_events.php',
            'pages/admin_members.php',
            'pages/admin_dinner_reservations.php',
            'pages/admin_newsletters.php',
            'pages/admin_auctions.php',
            'pages/admin_classifieds.php',
            'pages/admin_committee.php',
            'pages/admin_ads.php',
            'pages/admin_press.php',
            'pages/admin_editorial.php',
            'pages/admin_live_feeds.php',
        ] as $path) {
            $source = $this->source($path);

            self::assertStringContainsString('data-admin-wizard', $source, $path);
            self::assertStringContainsString('data-admin-wizard-step', $source, $path);
            self::assertStringContainsString('data-admin-wizard-previous-label', $source, $path);
            self::assertStringContainsString('data-admin-wizard-next-label', $source, $path);
        }
    }

    public function testProposalMenusOpenAFirstStepWizardInsteadOfAVisibleDropdown(): void
    {
        $dialogModule = $this->source('assets/js/modules/module_dialogs.js');
        $renderer = $this->source('app/layout_renderer.php');

        self::assertStringContainsString('details[class*="propose-menu"]', $dialogModule);
        self::assertStringContainsString('proposal-wizard-choices', $dialogModule);
        self::assertStringContainsString("menu.hidden = true", $dialogModule);
        self::assertStringContainsString("'classifieds'", $renderer);
    }

    public function testTranslationReviewKeepsFiltersAndSourceComparison(): void
    {
        $source = $this->source('pages/admin_translation_reviews.php');

        self::assertStringContainsString("\$_GET['review_locale']", $source);
        self::assertStringContainsString("\$_GET['review_type']", $source);
        self::assertStringContainsString('source_excerpt', $source);
        self::assertStringContainsString('source_content', $source);
        self::assertStringContainsString('class="admin-translation-compare"', $source);
        self::assertStringContainsString('data-admin-dirty-track', $source);
        self::assertStringContainsString('SELECT COUNT(*) FROM news_translations', $source);
        self::assertStringContainsString('SELECT COUNT(*) FROM article_translations', $source);
        self::assertStringContainsString('news_page', $source);
        self::assertStringContainsString('article_page', $source);
        self::assertStringContainsString("LIMIT ' . \$translationPerPage", $source);
    }

    public function testAdminConfirmationsKeepLocalizedLabelsAndNativeFallback(): void
    {
        $renderer = $this->source('app/layout_renderer.php');
        $script = $this->source('assets/js/modules/admin_common.js');

        self::assertStringContainsString('data-admin-confirm-title', $renderer);
        self::assertStringContainsString('data-admin-confirm-cancel', $renderer);
        self::assertStringContainsString("typeof confirmDialog.showModal !== 'function'", $script);
        self::assertStringContainsString('window.confirm(message)', $script);
        self::assertStringContainsString('admin-confirm-dialog-title', $script);
        self::assertStringContainsString('pending.focusTarget?.focus()', $script);
    }

    public function testFlashFeedbackUsesLiveRegionsForAdministrativeResults(): void
    {
        $renderer = $this->source('app/layout_renderer.php');

        self::assertStringContainsString("role=\"' . (\$isUrgent ? 'alert' : 'status')", $renderer);
        self::assertStringContainsString("aria-live=\"' . (\$isUrgent ? 'assertive' : 'polite')", $renderer);
    }

    public function testHighImpactAdministrationActionsHaveContextualSafeguards(): void
    {
        $permissions = $this->source('pages/admin_permissions.php');
        $news = $this->source('pages/admin_news.php');
        $modules = $this->source('pages/admin_modules.php');
        $script = $this->source('assets/js/modules/admin_common.js');

        self::assertStringContainsString('confirm_remove_role', $permissions);
        self::assertStringContainsString('data-admin-dirty-track', $permissions);
        self::assertStringContainsString('data-confirm-when-select="status:published|rejected"', $news);
        self::assertStringContainsString('confirm_moderation_decision', $news);
        self::assertStringContainsString('data-admin-dirty-track', $modules);
        self::assertStringContainsString("expectedValue.split('|')", $script);
    }

    public function testTaxonomyDeletesConfirmOnlyTheDestructiveSubmitter(): void
    {
        $library = $this->source('pages/admin_library.php');
        $script = $this->source('assets/js/modules/admin_common.js');

        self::assertStringContainsString('data-confirm-when-submit-action="delete_category"', $library);
        self::assertStringContainsString('data-confirm-when-submit-action="delete_subcategory"', $library);
        self::assertStringContainsString('data-confirm-when-submit-action="delete_subsubcategory"', $library);
        self::assertStringContainsString('submitActionCondition', $script);
        self::assertStringContainsString("submitter.name === 'action'", $script);
    }

    public function testErasureRequestsRequireAnExplicitConfirmationOnlyWhenSelected(): void
    {
        $privacy = $this->source('pages/admin_privacy.php');
        $script = $this->source('assets/js/modules/admin_common.js');

        self::assertStringContainsString('confirm_apply_erasure', $privacy);
        self::assertStringContainsString('data-confirm-when-checked="apply_erasure"', $privacy);
        self::assertStringContainsString('checkedCondition', $script);
        self::assertStringContainsString("field.type !== 'checkbox'", $script);
    }

    public function testNewsletterSendingRequiresAnExplicitConfirmation(): void
    {
        $newsletters = $this->source('pages/admin_newsletters.php');

        self::assertStringContainsString('confirm_send_campaign', $newsletters);
        self::assertStringContainsString('name="action" value="send_campaign"', $newsletters);
    }

    public function testAdvertisementRejectionRequiresAnExplicitConfirmation(): void
    {
        $ads = $this->source('pages/admin_ads.php');

        self::assertStringContainsString('confirm_reject_ad', $ads);
        self::assertStringContainsString('data-confirm-when-select="status:rejected"', $ads);
    }

    public function testArticleRejectionRequiresAnExplicitConfirmation(): void
    {
        $articles = $this->source('pages/admin_articles.php');

        self::assertStringContainsString('confirm_reject_article', $articles);
        self::assertStringContainsString('admin-article-reject-form" data-confirm-message', $articles);
        self::assertStringContainsString('admin-article-row-reject-form" data-confirm-message', $articles);
    }

    public function testWikiRejectionRequiresAnExplicitConfirmation(): void
    {
        $wiki = $this->source('pages/admin_wiki.php');

        self::assertStringContainsString('confirm_reject_wiki', $wiki);
        self::assertStringContainsString('data-confirm-when-select="status:rejected"', $wiki);
        self::assertStringContainsString('data-confirm-when-select="proposal_status:rejected"', $wiki);
    }

    public function testLibraryProposalRejectionRequiresAnExplicitConfirmation(): void
    {
        $library = $this->source('pages/admin_library.php');

        self::assertStringContainsString('confirm_reject_proposal', $library);
        self::assertStringContainsString('data-confirm-when-select="proposal_status:rejected"', $library);
    }

    public function testLongRunningAdministrationFormsTrackUnsavedWork(): void
    {
        foreach ([
            'pages/admin_live_feeds.php',
            'pages/admin_editorial.php',
            'pages/admin_auctions.php',
            'pages/admin_press.php',
            'pages/admin_ads.php',
            'pages/admin_dinner_reservations.php',
            'pages/admin_albums.php',
            'pages/admin_library.php',
            'pages/admin_dashboard.php',
            'pages/admin_committee.php',
            'pages/admin_newsletters.php',
            'pages/admin_wiki.php',
            'pages/admin_privacy.php',
        ] as $path) {
            self::assertStringContainsString('data-admin-dirty-track', $this->source($path));
        }
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(__DIR__ . '/../' . $relativePath);
        self::assertIsString($source);

        return $source;
    }
}
