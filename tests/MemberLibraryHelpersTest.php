<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MemberLibraryHelpersTest extends TestCase
{
    private function requireSeleniumDatabase(): void
    {
        if (!extension_loaded('pdo_mysql')) {
            self::markTestSkipped('pdo_mysql is required for member library database integration coverage.');
        }

        $configPath = str_replace('\\', '/', (string) getenv('ON4CRD_CONFIG_FILE'));
        if ($configPath === '' || !str_ends_with($configPath, '/storage/auth/selenium-config.php')) {
            self::markTestSkipped('Member library database integration coverage requires storage/auth/selenium-config.php.');
        }

        try {
            db()->query('SELECT 1');
        } catch (Throwable $throwable) {
            self::markTestSkipped('Selenium database is unavailable: ' . $throwable->getMessage());
        }
    }

    /**
     * @return array{public:string,absolute:string}
     */
    private function writePrivateLibraryFixture(string $name, string $contents): array
    {
        $directory = dirname(__DIR__) . '/storage/private/library/phpunit';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            self::fail('Unable to create member library fixture directory.');
        }

        $publicPath = 'storage/private/library/phpunit/' . $name;
        $absolutePath = dirname(__DIR__) . '/' . $publicPath;
        file_put_contents($absolutePath, $contents);

        return ['public' => $publicPath, 'absolute' => $absolutePath];
    }

    public function testVisibleTaxonomyKeepsOnlyNonEmptyCategoriesAndSubcategories(): void
    {
        $categories = [
            ['category' => 'general', 'label' => 'General', 'total' => 0],
            ['category' => 'formation', 'label' => 'Formation', 'total' => 3],
            ['category' => 'technique', 'label' => 'Technique', 'total' => 1],
        ];
        $subcategories = [
            'formation' => [
                ['code' => 'licence', 'label' => 'Licence', 'total' => 2],
                ['code' => 'empty', 'label' => 'Empty', 'total' => 0],
            ],
            'technique' => [
                ['code' => 'antennes', 'label' => 'Antennes', 'total' => 0],
            ],
        ];

        self::assertSame(
            [
                ['category' => 'formation', 'label' => 'Formation', 'total' => 3],
                ['category' => 'technique', 'label' => 'Technique', 'total' => 1],
            ],
            member_library_visible_categories($categories)
        );
        self::assertSame(
            [
                'formation' => [
                    ['code' => 'licence', 'label' => 'Licence', 'total' => 2],
                ],
            ],
            member_library_visible_subcategories_by_category($subcategories)
        );
    }

    public function testSubcategoryReferencesNormalizeAndRoundTrip(): void
    {
        self::assertSame('formation:hf-antennas', member_library_subcategory_ref('Formation', 'HF Antennas'));
        self::assertSame(
            ['category' => 'formation', 'subcategory' => 'hf-antennas'],
            member_library_subcategory_ref_parts(' Formation : HF Antennas ')
        );
        self::assertSame(
            ['category' => '', 'subcategory' => 'hf-antennas'],
            member_library_subcategory_ref_parts('HF Antennas')
        );
    }

    public function testProposalSummaryExtractsTopicSubtopicTagsAndDescription(): void
    {
        $summary = "Category: technique\nSubcategory: HF Antennas\nTags: technique,unknown,antenne\nDescription: First line\nSecond line";

        self::assertSame('technique', member_library_proposal_category_from_summary($summary));
        self::assertSame('hf-antennas', member_library_proposal_subcategory_from_summary($summary));
        self::assertSame('technique,antenne', member_library_proposal_tags_from_summary($summary));
        self::assertSame("First line\nSecond line", member_library_proposal_description_from_summary($summary));
    }

    public function testDocumentProposalActionsAndIdsAreParsedStrictly(): void
    {
        self::assertSame('update_document', member_library_document_proposal_action("Action: update_document\nDocument ID: 42"));
        self::assertSame('delete_document', member_library_document_proposal_action("Action: delete_document\nDocument ID: 42"));
        self::assertSame('', member_library_document_proposal_action('Action: publish_document'));
        self::assertSame(42, member_library_document_proposal_document_id("Action: update_document\nDocument ID: 42"));
        self::assertSame(0, member_library_document_proposal_document_id("Action: update_document\nDocument ID: abc"));
    }

    public function testCleanTagsKeepsControlledVocabularyDeduplicatedAndLimited(): void
    {
        self::assertSame('formation,securite,technique', member_library_clean_tags(' formation,unknown,securite,formation,technique '));
    }

    public function testDocumentUploadConfigurationAcceptsCommonDocumentMimeVariants(): void
    {
        $extensions = member_library_document_upload_extensions();
        $mimes = member_library_document_upload_mimes();

        self::assertContains('doc', $extensions);
        self::assertContains('application/msword', $mimes['doc']);
        self::assertContains('application/cdfv2', $mimes['doc']);
        self::assertContains('application/vnd.pdf', $mimes['pdf']);
        self::assertContains('text/pdf', $mimes['pdf']);
        self::assertContains('application/x-zip-compressed', $mimes['docx']);
        self::assertContains('text/x-markdown', $mimes['md']);
    }

    public function testFavoritesLabelUsesPluralFallbacks(): void
    {
        self::assertSame('Mes favoris', member_library_favorites_label(['favorites' => 'Mes favoris'], 'fr'));
        self::assertSame('Favoris', member_library_favorites_label(['favorite' => 'Favori'], 'fr'));
        self::assertSame('Favorites', member_library_favorites_label(['favorite' => 'Favorite'], 'en'));
        self::assertSame('Favorito', member_library_favorites_label(['favorite' => 'Favorito'], 'es'));
    }

    public function testExtractTextNormalizesPlainHtmlAndMissingFiles(): void
    {
        $plain = tempnam(sys_get_temp_dir(), 'library-text-');
        $html = tempnam(sys_get_temp_dir(), 'library-html-');
        self::assertIsString($plain);
        self::assertIsString($html);

        try {
            file_put_contents($plain, "Line one\n\nLine\t two");
            file_put_contents($html, "<h1>Title</h1> <p>Body\ntext</p>");

            self::assertSame('Line one Line two', member_library_extract_text($plain, 'txt'));
            self::assertSame('Title Body text', member_library_extract_text($html, 'html'));
            self::assertSame('', member_library_extract_text($plain . '-missing', 'txt'));
        } finally {
            @unlink($plain);
            @unlink($html);
        }
    }

    public function testProposalSourcePathAcceptsLibraryReferencesAndRejectsUnsafePaths(): void
    {
        self::assertSame(
            'storage/private/library/example.doc',
            member_library_proposal_source_path('storage/private/library/example.doc')
        );
        self::assertSame(
            'storage/uploads/library/legacy.pdf',
            member_library_proposal_source_path('https://example.test/index.php?download=storage/uploads/library/legacy.pdf')
        );
        self::assertSame(
            'storage/private/library/encoded.doc',
            member_library_proposal_source_path(rawurlencode('storage/private/library/encoded.doc'))
        );

        self::assertSame('', member_library_proposal_source_path('storage/private/../library/evil.pdf'));
        self::assertSame('', member_library_proposal_source_path('storage/uploads/not-library/evil.pdf'));
        self::assertSame('', member_library_proposal_source_path('https://example.test/storage/private/member_modules/not-library.pdf'));
    }

    public function testDefaultTaxonomyAndIngestionTemplatesUseControlledVocabulary(): void
    {
        $categoryCodes = array_column(member_library_default_categories(), 'code');
        $controlledTags = library_controlled_vocabulary_list();

        foreach (['general', 'formation', 'technique', 'club'] as $expectedCategory) {
            self::assertContains($expectedCategory, $categoryCodes);
        }

        foreach (member_library_default_subcategories() as $subcategory) {
            self::assertContains((string) $subcategory['category_code'], $categoryCodes);
            self::assertNotSame('', member_library_subcategory_slug((string) $subcategory['code']));
        }

        foreach (library_ingestion_templates_map() as $template) {
            self::assertContains((string) $template['category'], $categoryCodes);
            foreach ($template['tags'] as $tag) {
                self::assertContains($tag, $controlledTags);
            }
        }

        self::assertSame(['formation', 'Technique'], library_filter_controlled_tags([' formation ', 'unknown', 'Technique']));
    }

    public function testAcceptedProposalAppliesFullDocumentLifecycleAgainstDatabase(): void
    {
        $this->requireSeleniumDatabase();

        ensure_member_library_table();
        member_library_ensure_categories_table();
        member_library_ensure_subcategories_table();

        $token = 'phpunit-library-' . bin2hex(random_bytes(4));
        $initial = $this->writePrivateLibraryFixture($token . '.txt', "Initial document\nBody");
        $replacement = $this->writePrivateLibraryFixture($token . '-replacement.html', '<h1>Replacement</h1> <p>Body</p>');
        $categoryTitle = 'PHPUnit Category ' . $token;
        $messages = [
            'propose_document_category' => 'Categorie',
            'propose_document_subcategory' => 'Sous-thematique',
            'tags' => 'Mots cles',
            'propose_document_description' => 'Description',
        ];

        try {
            db()->prepare('DELETE FROM member_library_documents WHERE file_path IN (?, ?)')
                ->execute([$initial['public'], $replacement['public']]);
            db()->prepare('DELETE FROM member_library_categories WHERE code = ?')
                ->execute([member_library_category_slug($categoryTitle)]);

            self::assertNull(member_library_apply_accepted_proposal([
                'proposal_type' => 'category',
                'title' => $categoryTitle,
            ], $messages));

            $categoryStmt = db()->prepare('SELECT label FROM member_library_categories WHERE code = ? LIMIT 1');
            $categoryStmt->execute([member_library_category_slug($categoryTitle)]);
            self::assertSame($categoryTitle, (string) $categoryStmt->fetchColumn());

            $documentId = member_library_apply_accepted_proposal([
                'proposal_type' => 'content',
                'member_id' => 1,
                'title' => 'Initial ' . $token,
                'summary' => "Categorie: Formation\nSous-thematique: HF Antennas\nMots cles: formation,unknown,technique\nDescription: First line\nSecond line",
                'source_ref' => $initial['public'],
            ], $messages);
            self::assertIsInt($documentId);
            self::assertGreaterThan(0, $documentId);

            $createdStmt = db()->prepare('SELECT category, subcategory, tags, title, description, file_path, extracted_text FROM member_library_documents WHERE id = ? LIMIT 1');
            $createdStmt->execute([$documentId]);
            $created = $createdStmt->fetch();
            self::assertIsArray($created);
            self::assertSame('formation', $created['category']);
            self::assertSame('hf-antennas', $created['subcategory']);
            self::assertSame('formation,technique', $created['tags']);
            self::assertSame('Initial ' . $token, $created['title']);
            self::assertSame("First line\nSecond line", $created['description']);
            self::assertSame($initial['public'], $created['file_path']);
            self::assertSame('Initial document Body', $created['extracted_text']);

            self::assertSame($documentId, member_library_apply_accepted_proposal([
                'proposal_type' => 'content',
                'member_id' => 1,
                'title' => 'Duplicate ' . $token,
                'summary' => "Categorie: Technique\nMots cles: antenne",
                'source_ref' => $initial['public'],
            ], $messages));

            member_library_apply_accepted_proposal([
                'proposal_type' => 'content',
                'title' => 'Updated ' . $token,
                'summary' => "Action: update_document\nDocument ID: {$documentId}\nCategorie: Technique\nSous-thematique: Mesures\nMots cles: antenne,securite\nDescription: Updated description",
                'source_ref' => $replacement['public'],
            ], $messages);

            $updatedStmt = db()->prepare('SELECT category, subcategory, tags, title, description, file_path, extracted_text FROM member_library_documents WHERE id = ? LIMIT 1');
            $updatedStmt->execute([$documentId]);
            $updated = $updatedStmt->fetch();
            self::assertIsArray($updated);
            self::assertSame('technique', $updated['category']);
            self::assertSame('mesures', $updated['subcategory']);
            self::assertSame('antenne,securite', $updated['tags']);
            self::assertSame('Updated ' . $token, $updated['title']);
            self::assertSame('Updated description', $updated['description']);
            self::assertSame($replacement['public'], $updated['file_path']);
            self::assertSame('Replacement Body', $updated['extracted_text']);
            self::assertFileDoesNotExist($initial['absolute']);

            self::assertSame($documentId, member_library_apply_accepted_proposal([
                'proposal_type' => 'content',
                'summary' => "Action: delete_document\nDocument ID: {$documentId}",
            ], $messages));

            $deletedStmt = db()->prepare('SELECT COUNT(*) FROM member_library_documents WHERE id = ?');
            $deletedStmt->execute([$documentId]);
            self::assertSame(0, (int) $deletedStmt->fetchColumn());
            self::assertFileDoesNotExist($replacement['absolute']);
        } finally {
            db()->prepare('DELETE FROM member_library_documents WHERE file_path IN (?, ?)')
                ->execute([$initial['public'], $replacement['public']]);
            db()->prepare('DELETE FROM member_library_categories WHERE code = ?')
                ->execute([member_library_category_slug($categoryTitle)]);
            @unlink($initial['absolute']);
            @unlink($replacement['absolute']);
        }
    }
}
