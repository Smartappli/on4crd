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
        self::assertStringContainsString("form[data-admin-dirty-track]", $script);
        self::assertStringContainsString("beforeunload", $script);
        self::assertStringContainsString("data-confirm-message", $script);
        self::assertStringContainsString("admin-responsive-table", $script);
        self::assertStringContainsString("table.admin-responsive-table", $styles);
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
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(__DIR__ . '/../' . $relativePath);
        self::assertIsString($source);

        return $source;
    }
}
