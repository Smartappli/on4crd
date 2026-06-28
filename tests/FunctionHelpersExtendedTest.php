<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FunctionHelpersExtendedTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER = [];
        $_POST = [];
        $_GET = [];
    }

    public function testSafeStoragePublicPathOrNullReturnsNullForUnauthorizedPath(): void
    {
        self::assertNull(safe_storage_public_path_or_null('storage/uploads/file.png'));
    }

    public function testMbSafeStrimwidthTrimsWithMarker(): void
    {
        self::assertSame('ABCD…', mb_safe_strimwidth('ABCDEFGHIJ', 0, 5, '…'));
    }

    public function testSanitizeHrefAttributeRejectsDataAndVbscriptSchemes(): void
    {
        self::assertNull(sanitize_href_attribute('data:text/html;base64,PHNjcmlwdD4='));
        self::assertNull(sanitize_href_attribute('vbscript:msgbox(1)'));
    }

    public function testSanitizeHrefAttributeRejectsProtocolRelativeUrls(): void
    {
        self::assertNull(sanitize_href_attribute('//evil.example/path'));
    }

    public function testSanitizeHrefAttributeRejectsUrlsWithLineBreaks(): void
    {
        self::assertNull(sanitize_href_attribute("/ok\r\njavascript:alert(1)"));
    }

    public function testArticleSanitizeContentStripsUnsupportedTagsAndAttributes(): void
    {
        $html = '<p id="intro" style="position:fixed">Intro</p><form><input name="token" value="secret"><button>Send</button></form><a href="javascript:alert(1)" onclick="evil()">Bad</a><a href="/articles" target="_blank" class="external">OK</a><p><s class="bad">Barre</s><sup data-x="1">2</sup><sub>i</sub></p><table><tr><th scope="bad">Bad</th><th scope="COL">Head</th></tr></table>';

        $clean = article_sanitize_content($html);

        self::assertStringContainsString('<p>Intro</p>', $clean);
        self::assertStringContainsString('Send', $clean);
        self::assertStringContainsString('rel="noopener noreferrer"', $clean);
        self::assertStringContainsString('<s>Barre</s>', $clean);
        self::assertStringContainsString('<sup>2</sup>', $clean);
        self::assertStringContainsString('<sub>i</sub>', $clean);
        self::assertStringContainsString('<th>Bad</th>', $clean);
        self::assertStringContainsString('<th scope="col">Head</th>', $clean);
        self::assertStringNotContainsString('style=', $clean);
        self::assertStringNotContainsString('id=', $clean);
        self::assertStringNotContainsString('class=', $clean);
        self::assertStringNotContainsString('data-x=', $clean);
        self::assertStringNotContainsString('scope="bad"', $clean);
        self::assertStringNotContainsString('<form', $clean);
        self::assertStringNotContainsString('<input', $clean);
        self::assertStringNotContainsString('<button', $clean);
        self::assertStringNotContainsString('javascript:', $clean);
        self::assertStringNotContainsString('onclick', $clean);
    }

    public function testExtractLatestKpMeasurementReturnsNullWhenPayloadHasOnlyHeader(): void
    {
        $payload = [
            ['time_tag', 'kp_index'],
        ];

        self::assertNull(extract_latest_kp_measurement($payload));
    }

    public function testExtractLatestKpMeasurementReturnsNullWhenNoRowContainsNumericKp(): void
    {
        $payload = [
            ['time_tag', 'kp_index'],
            ['2026-04-26 00:00:00.000', ''],
            ['2026-04-26 03:00:00.000', 'n/a'],
        ];

        self::assertNull(extract_latest_kp_measurement($payload));
    }

    public function testEnvReturnsDefaultWhenMissingAndServerValueWhenPresent(): void
    {
        self::assertSame('fallback', env('ON4CRD_TEST_ENV', 'fallback'));
        $_SERVER['ON4CRD_TEST_ENV'] = 'server-value';
        self::assertSame('server-value', env('ON4CRD_TEST_ENV', 'fallback'));
    }

    public function testStoragePathBuildsAbsolutePath(): void
    {
        $base = storage_path();
        self::assertStringEndsWith('/storage', $base);
        self::assertSame($base . '/uploads/library', storage_path('uploads/library'));
    }

    public function testAssetUrlAddsVersionParameterForExistingFile(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['HTTPS'] = 'on';

        $url = asset_url('assets/css/app.css');
        self::assertStringContainsString('/assets/css/app.css', $url);
        self::assertMatchesRegularExpression('/[?&]v=\\d+$/', $url);
    }

    public function testLocaleFallbackChainHandlesRegionalTags(): void
    {
        self::assertSame(['pt', 'en', 'fr'], locale_fallback_chain('pt-BR'));
        self::assertSame(['en', 'fr'], locale_fallback_chain('en-US'));
    }

    public function testI18nLocalizedValueUsesFallbackChain(): void
    {
        $localized = [
            'fr' => 'Bonjour',
            'en' => 'Hello',
        ];

        self::assertSame('Hello', i18n_localized_value($localized, 'pt-BR'));
        self::assertSame('Bonjour', i18n_localized_value(['fr' => 'Bonjour'], 'ja-JP'));
    }

    public function testCurrentLocaleUsesAcceptLanguageWhenSessionEmpty(): void
    {
        unset($_SESSION['locale']);
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,de;q=0.9,en;q=0.8';

        self::assertSame('de', current_locale());
    }

    public function testCleanQueryParamsDropsOnlyEmptyValues(): void
    {
        self::assertSame(
            ['zero' => 0, 'string_zero' => '0', 'valid' => 'abc'],
            clean_query_params(['empty' => '', 'null' => null, 'false' => false, 'zero' => 0, 'string_zero' => '0', 'valid' => 'abc'])
        );
    }

    public function testPaginationStateClampsPageAndComputesOffset(): void
    {
        self::assertSame(
            ['page' => 3, 'per_page' => 10, 'total_pages' => 3, 'offset' => 20],
            pagination_state(25, 9, 10)
        );
        self::assertSame(
            ['page' => 1, 'per_page' => 1, 'total_pages' => 1, 'offset' => 0],
            pagination_state(0, -2, 0)
        );
    }

    public function testI18nDomainTranslatorReturnsKeyWhenMissing(): void
    {
        $translator = i18n_domain_translator('missing_test_domain', 'fr');

        self::assertSame('unknown_key', $translator('unknown_key'));
    }

    public function testArticleImportTextToHtmlBuildsStructuredSafeHtml(): void
    {
        $html = article_import_text_to_html("# Title\n\nFirst line\ncontinues here\n\n- one\n- two\n\n<script>alert(1)</script>");

        self::assertStringContainsString('<h2>Title</h2>', $html);
        self::assertStringContainsString('<p>First line continues here</p>', $html);
        self::assertStringContainsString('<ul>', $html);
        self::assertStringContainsString('<li>one</li>', $html);
        self::assertStringNotContainsString('<script', $html);
    }

    public function testArticleExtractDocxHtmlPreservesRichDocumentStructure(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'docx-rich-');
        self::assertIsString($tmp);

        $documentXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">
  <w:body>
    <w:p>
      <w:pPr><w:pStyle w:val="Heading1"/></w:pPr>
      <w:r><w:t>Titre importe</w:t></w:r>
    </w:p>
    <w:p>
      <w:r><w:rPr><w:b/><w:i/></w:rPr><w:t>Texte fort</w:t></w:r>
      <w:r><w:t> et </w:t></w:r>
      <w:hyperlink r:id="rId1"><w:r><w:t>lien fiable</w:t></w:r></w:hyperlink>
      <w:r><w:br/><w:t>Suite</w:t></w:r>
    </w:p>
    <w:p>
      <w:r><w:rPr><w:strike/></w:rPr><w:t>Texte barre</w:t></w:r>
      <w:r><w:t> </w:t></w:r>
      <w:r><w:rPr><w:vertAlign w:val="superscript"/></w:rPr><w:t>exposant</w:t></w:r>
      <w:r><w:t> </w:t></w:r>
      <w:r><w:rPr><w:vertAlign w:val="subscript"/></w:rPr><w:t>indice</w:t></w:r>
    </w:p>
    <w:p>
      <w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr></w:pPr>
      <w:r><w:t>Element de liste</w:t></w:r>
    </w:p>
    <w:p>
      <w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="2"/></w:numPr></w:pPr>
      <w:r><w:t>Element numerote</w:t></w:r>
    </w:p>
    <w:p>
      <w:r>
        <w:drawing>
          <wp:inline>
            <wp:extent cx="95250" cy="190500"/>
            <wp:docPr id="1" name="Image Word" descr="Image importee"/>
            <a:graphic><a:graphicData><a:blip r:embed="rId3"/></a:graphicData></a:graphic>
          </wp:inline>
        </w:drawing>
      </w:r>
    </w:p>
    <w:p>
      <w:hyperlink r:id="rId2"><w:r><w:t>Lien bloque</w:t></w:r></w:hyperlink>
    </w:p>
    <w:tbl>
      <w:tr>
        <w:trPr><w:tblHeader/></w:trPr>
        <w:tc><w:p><w:r><w:t>Colonne A</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:r><w:t>Colonne B</w:t></w:r></w:p></w:tc>
      </w:tr>
      <w:tr>
        <w:tc>
          <w:tcPr><w:vMerge w:val="restart"/></w:tcPr>
          <w:p><w:r><w:t>Cellule A</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr><w:gridSpan w:val="2"/></w:tcPr>
          <w:p><w:r><w:t>Cellule B</w:t></w:r></w:p>
        </w:tc>
      </w:tr>
      <w:tr>
        <w:tc><w:tcPr><w:vMerge/></w:tcPr><w:p/></w:tc>
        <w:tc><w:p><w:r><w:t>Cellule C</w:t></w:r></w:p></w:tc>
      </w:tr>
    </w:tbl>
  </w:body>
</w:document>
XML;
        $relationshipsXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" Target="https://example.test/docx" TargetMode="External"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" Target="javascript:alert(1)" TargetMode="External"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/image1.png"/>
</Relationships>
XML;
        $numberingXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:abstractNum w:abstractNumId="0">
    <w:lvl w:ilvl="0"><w:numFmt w:val="bullet"/></w:lvl>
  </w:abstractNum>
  <w:abstractNum w:abstractNumId="1">
    <w:lvl w:ilvl="0"><w:numFmt w:val="decimal"/></w:lvl>
  </w:abstractNum>
  <w:num w:numId="1"><w:abstractNumId w:val="0"/></w:num>
  <w:num w:numId="2"><w:abstractNumId w:val="1"/></w:num>
</w:numbering>
XML;
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=', true);
        self::assertIsString($pngBytes);

        file_put_contents($tmp, self::zipFixture([
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId0" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
            'word/document.xml' => $documentXml,
            'word/_rels/document.xml.rels' => $relationshipsXml,
            'word/numbering.xml' => $numberingXml,
            'word/media/image1.png' => $pngBytes,
        ]));

        try {
            $html = article_extract_docx_html($tmp);

            self::assertStringContainsString('<h2>Titre importe</h2>', $html);
            self::assertStringContainsString('<strong><em>Texte fort</em></strong>', $html);
            self::assertStringContainsString('<a href="https://example.test/docx">lien fiable</a>', $html);
            self::assertStringContainsString('<br>', $html);
            self::assertStringContainsString('<s>Texte barre</s>', $html);
            self::assertStringContainsString('<sup>exposant</sup>', $html);
            self::assertStringContainsString('<sub>indice</sub>', $html);
            self::assertStringContainsString('<ul>', $html);
            self::assertStringContainsString('<li>Element de liste</li>', $html);
            self::assertStringContainsString('<ol>', $html);
            self::assertStringContainsString('<li>Element numerote</li>', $html);
            self::assertStringContainsString('<img src="data:image/png;base64,', $html);
            self::assertStringContainsString('alt="Image importee"', $html);
            self::assertStringContainsString('width="10"', $html);
            self::assertStringContainsString('height="20"', $html);
            self::assertStringContainsString('<table>', $html);
            self::assertStringContainsString('<thead>', $html);
            self::assertStringContainsString('<th scope="col">Colonne A</th>', $html);
            self::assertStringContainsString('<th scope="col">Colonne B</th>', $html);
            self::assertStringContainsString('<tbody>', $html);
            self::assertStringContainsString('<td rowspan="2">Cellule A</td>', $html);
            self::assertStringContainsString('<td colspan="2">Cellule B</td>', $html);
            self::assertStringContainsString('<td>Cellule C</td>', $html);
            self::assertStringContainsString('Lien bloque', $html);
            self::assertStringNotContainsString('javascript:', $html);
        } finally {
            @unlink($tmp);
        }
    }

    public function testArticleExtractDocxHtmlSkipsOversizedInlineImages(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'docx-large-image-');
        self::assertIsString($tmp);

        $documentXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">
  <w:body>
    <w:p><w:r><w:t>Texte conserve avant image lourde</w:t></w:r></w:p>
    <w:p>
      <w:r>
        <w:drawing>
          <wp:inline>
            <wp:extent cx="95250" cy="190500"/>
            <wp:docPr id="1" name="Image Word" descr="Image lourde"/>
            <a:graphic><a:graphicData><a:blip r:embed="rId1"/></a:graphicData></a:graphic>
          </wp:inline>
        </w:drawing>
      </w:r>
    </w:p>
    <w:p><w:r><w:t>Texte conserve apres image lourde</w:t></w:r></w:p>
  </w:body>
</w:document>
XML;
        $relationshipsXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/image-large.png"/>
</Relationships>
XML;

        file_put_contents($tmp, self::zipFixture([
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId0" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
            'word/document.xml' => $documentXml,
            'word/_rels/document.xml.rels' => $relationshipsXml,
            'word/media/image-large.png' => str_repeat('x', article_docx_max_inline_image_bytes() + 1),
        ]));

        try {
            $html = article_extract_docx_html($tmp);

            self::assertStringContainsString('Texte conserve avant image lourde', $html);
            self::assertStringContainsString('Texte conserve apres image lourde', $html);
            self::assertStringNotContainsString('<img ', $html);
            self::assertLessThan(50000, strlen($html));
        } finally {
            @unlink($tmp);
        }
    }

    public function testUploadSignatureValidatorAcceptsDocxZipHeader(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'docx-sig-');
        self::assertIsString($tmp);
        file_put_contents($tmp, "PK\x03\x04" . str_repeat("\0", 16));

        try {
            assert_upload_file_is_valid_signature($tmp, ['docx']);
            $this->addToAssertionCount(1);
        } finally {
            @unlink($tmp);
        }
    }

    public function testUploadSignatureValidatorAcceptsLegacyDocOleHeader(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'doc-sig-');
        self::assertIsString($tmp);
        file_put_contents($tmp, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" . str_repeat("\0", 16));

        try {
            assert_upload_file_is_valid_signature($tmp, ['doc']);
            self::assertSame('application/msword', detect_uploaded_mime_type_from_content($tmp));
        } finally {
            @unlink($tmp);
        }
    }

    public function testDetectUploadedMimeTypeUsesImageContentFallback(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'png-mime-');
        self::assertIsString($tmp);
        file_put_contents(
            $tmp,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAAGklEQVR4nGP8z8Dwn4GBgYGJgYGB4T8ABQsCBAJH7m4AAAAASUVORK5CYII=', true)
        );

        try {
            self::assertSame('image/png', detect_uploaded_mime_type($tmp));
        } finally {
            @unlink($tmp);
        }
    }

    public function testDetectUploadedMimeTypeUsesPdfSignatureFallback(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pdf-mime-');
        self::assertIsString($tmp);
        file_put_contents($tmp, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n");

        try {
            self::assertSame('application/pdf', detect_uploaded_mime_type($tmp));
        } finally {
            @unlink($tmp);
        }
    }

    public function testUploadSignatureValidatorAllowsTextExtensionsWithoutBinarySignature(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'txt-sig-');
        self::assertIsString($tmp);
        file_put_contents($tmp, 'plain text document');

        try {
            assert_upload_file_is_valid_signature($tmp, ['txt']);
            $this->addToAssertionCount(1);
        } finally {
            @unlink($tmp);
        }
    }

    public function testUploadErrorMessageReportsPhpSizeLimitsAsTooLarge(): void
    {
        self::assertSame(upload_i18n_message('file_too_large_or_empty'), upload_error_message(UPLOAD_ERR_INI_SIZE));
        self::assertSame(upload_i18n_message('file_too_large_or_empty'), upload_error_message(UPLOAD_ERR_FORM_SIZE));
        self::assertSame(upload_i18n_message('upload_failed'), upload_error_message(UPLOAD_ERR_PARTIAL));
    }

    public function testClassifiedsPayloadValidationRejectsOversizedFields(): void
    {
        $categories = ['gear' => 'Gear'];
        classifieds_validate_payload('gear', 'Title', 'Description', 'Namur', 'contact@example.test', $categories, 'Invalid');
        $this->expectException(RuntimeException::class);
        classifieds_validate_payload('gear', str_repeat('x', 191), 'Description', 'Namur', 'contact@example.test', $categories, 'Invalid');
    }

    public function testClassifiedsActiveWhereSqlSupportsOptionalAlias(): void
    {
        self::assertSame(
            'status = "active" AND (expires_at IS NULL OR expires_at >= NOW())',
            classifieds_active_where_sql()
        );
        self::assertSame(
            'ca.status = "active" AND (ca.expires_at IS NULL OR ca.expires_at >= NOW())',
            classifieds_active_where_sql('ca')
        );
    }

    public function testClassifiedsExpirationAppliesOnlyToActiveStatus(): void
    {
        self::assertSame('2026-06-07 12:00:00', classifieds_expires_at_for_status('active', strtotime('2026-05-08 12:00:00')));
        self::assertNull(classifieds_expires_at_for_status('pending', strtotime('2026-05-08 12:00:00')));
    }

    public function testParsePriceToCentsHandlesEuropeanAndUsSeparators(): void
    {
        self::assertSame(1250, parse_price_to_cents('12,50'));
        self::assertSame(1250, parse_price_to_cents('12.50'));
        self::assertSame(123456, parse_price_to_cents('1 234,56'));
        self::assertSame(123456, parse_price_to_cents('1.234,56'));
        self::assertSame(123456, parse_price_to_cents('1,234.56'));
        self::assertSame(123400, parse_price_to_cents('1.234'));
        self::assertSame(0, parse_price_to_cents('-12,50'));
    }

    public function testClassifiedsModerationUsesDedicatedPermission(): void
    {
        self::assertArrayHasKey('classifieds.moderate', core_permission_catalog());

        $classifiedsCards = array_values(array_filter(
            admin_module_cards_catalog(),
            static fn(array $card): bool => (string) $card['route'] === 'admin_classifieds'
        ));

        self::assertCount(1, $classifiedsCards);
        self::assertSame('classifieds.moderate', (string) ($classifiedsCards[0]['permission'] ?? ''));
    }

    public function testContentProposalPayloadSanitizesAndValidatesInput(): void
    {
        $payload = content_proposal_payload(
            12,
            'articles',
            'category',
            "  <b>Antennes</b>\n",
            content_proposal_details_text(['Reason' => '<script>bad()</script>Useful category']),
            "member@example.test\r\n",
            'source'
        );

        self::assertSame(12, $payload['member_id']);
        self::assertSame('articles', $payload['area']);
        self::assertSame('category', $payload['proposal_type']);
        self::assertSame('Antennes', $payload['title']);
        self::assertSame('Reason: bad()Useful category', $payload['summary']);
        self::assertSame('member@example.test', $payload['contact']);

        $webothequePayload = content_proposal_payload(12, 'webotheque', 'content', 'Useful link');
        self::assertSame('webotheque', $webothequePayload['area']);

        $webothequeDomainPayload = content_proposal_payload(12, 'webotheque', 'domain', 'Radio resources');
        self::assertSame('domain', $webothequeDomainPayload['proposal_type']);

        $webothequeTagPayload = content_proposal_payload(12, 'webotheque', 'tag', 'Antenna');
        self::assertSame('tag', $webothequeTagPayload['proposal_type']);
    }

    public function testContentProposalPayloadRejectsUnknownArea(): void
    {
        $this->expectException(RuntimeException::class);

        content_proposal_payload(12, 'unknown', 'category', 'Title');
    }

    public function testContentProposalSummaryDetailsParseContinuationLinesAndLabels(): void
    {
        $summary = "Description: First line\nSecond line\nMots clés: FT8, Antenne";

        self::assertSame("First line\nSecond line", content_proposal_detail_from_summary($summary, ['description']));
        self::assertSame('FT8, Antenne', content_proposal_detail_from_summary($summary, ['Mots cles']));
    }

    public function testPasswordChangeRequiredRequiresAdminForcedMarker(): void
    {
        self::assertFalse(member_password_change_required([
            'id' => 1,
            'password_change_required' => 1,
            'password_reset_forced_at' => null,
        ]));
        self::assertFalse(member_password_change_required([
            'id' => 1,
            'password_change_required' => 0,
            'password_reset_forced_at' => '2026-06-06 12:00:00',
        ]));
        self::assertTrue(member_password_change_required([
            'id' => 1,
            'password_change_required' => 1,
            'password_reset_forced_at' => '2026-06-06 12:00:00',
        ]));
    }

    public function testMemberCountryHelpersFormatKnownCountryWithFlag(): void
    {
        self::assertSame('BE', member_country_code_for('Belgique'));
        self::assertNotSame('', member_country_flag('Belgique'));

        $html = member_country_html('Belgique');
        self::assertStringContainsString('country-with-flag', $html);
        self::assertStringContainsString('Belgique', $html);
    }

    public function testCoordinatesToMaidenheadBuildsSixCharacterGrid(): void
    {
        self::assertSame('JO20LH', coordinates_to_maidenhead(50.3150, 4.9452));
        self::assertSame('JO20', coordinates_to_maidenhead(50.3150, 4.9452, 4));
        self::assertNull(coordinates_to_maidenhead(91.0, 4.9452));
    }

    public function testMemberProfilePostalAddressRequiresAllParts(): void
    {
        self::assertTrue(member_profile_postal_address_is_complete('Belgique', 'Rue des Ecoles', '5530', 'Purnode'));
        self::assertFalse(member_profile_postal_address_is_complete('Belgique', '', '5530', 'Purnode'));
    }

    public function testMemberProfileRadioZonesResolveKnownCountries(): void
    {
        self::assertSame(
            ['cq_zone' => '14', 'itu_zone' => '27'],
            member_profile_radio_zones_for_coordinates(50.85, 4.35, 'BE')
        );
        self::assertSame(
            ['cq_zone' => '15', 'itu_zone' => '28'],
            member_profile_radio_zones_for_coordinates(48.2, 16.37, 'Autriche')
        );
        self::assertSame(
            ['cq_zone' => '3', 'itu_zone' => '6'],
            member_profile_radio_zones_for_coordinates(37.77, -122.42, 'US')
        );
    }

    public function testMemberNameHelpersSplitAndComposeFullName(): void
    {
        self::assertSame('Jean Dupont', member_full_name_from_parts(' Jean ', ' Dupont '));
        self::assertSame(
            ['first_name' => 'Jean', 'last_name' => 'Dupont'],
            member_name_parts_from_full_name(' Jean   Dupont ')
        );
        self::assertSame(
            ['first_name' => 'Jean', 'last_name' => ''],
            member_name_parts_from_full_name('Jean')
        );
    }

    public function testMemberWithNamePartsBackfillsFromFullName(): void
    {
        $member = member_with_name_parts(['full_name' => 'Jean Dupont']);

        self::assertSame('Jean', $member['first_name']);
        self::assertSame('Dupont', $member['last_name']);
    }

    public function testMemberContactEmailDefaultsToClubAddressWhenMissing(): void
    {
        self::assertSame('crdurnal@gmail.com', member_contact_email_from_input(''));
        self::assertSame('crdurnal@gmail.com', member_contact_email_from_input('   '));
        self::assertSame('crdurnal@gmail.com', member_contact_email_from_input(' crddurnal@gmail.com '));
        self::assertSame('member@example.test', member_contact_email_from_input(' member@example.test '));
    }

    public function testMemberSharedContactEmailUsesUniqueAuthEmail(): void
    {
        $authEmail = member_auth_email_for_contact_email('crdurnal@gmail.com', 'ON4CRD');
        $otherAuthEmail = member_auth_email_for_contact_email('crdurnal@gmail.com', 'ON8CJ');
        $on4benAuthEmail = member_auth_email_for_contact_email('crdurnal@gmail.com', 'ON4BEN');
        $on4benTypoAuthEmail = member_auth_email_for_contact_email('crddurnal@gmail.com', 'ON4BEN');

        self::assertNotSame('crdurnal@gmail.com', $authEmail);
        self::assertNotSame('crdurnal@gmail.com', $otherAuthEmail);
        self::assertNotSame('crdurnal@gmail.com', $on4benAuthEmail);
        self::assertNotSame('crddurnal@gmail.com', $on4benTypoAuthEmail);
        self::assertNotSame($authEmail, $otherAuthEmail);
        self::assertNotSame($authEmail, $on4benAuthEmail);
        self::assertNotSame($otherAuthEmail, $on4benAuthEmail);
        self::assertSame($on4benAuthEmail, $on4benTypoAuthEmail);
        self::assertStringStartsWith('on4crd-', $authEmail);
        self::assertStringStartsWith('on8cj-', $otherAuthEmail);
        self::assertStringStartsWith('on4ben-', $on4benAuthEmail);
        self::assertStringEndsWith('@local.invalid', $authEmail);
        self::assertStringEndsWith('@local.invalid', $otherAuthEmail);
        self::assertStringEndsWith('@local.invalid', $on4benAuthEmail);
        self::assertSame('member@example.test', member_auth_email_for_contact_email('member@example.test', 'ON4CRD'));
    }

    public function testMemberProfileVisibilityDefaultsProtectSensitiveIdentityFields(): void
    {
        $t = static fn(string $key): string => $key;
        $fields = member_profile_visibility_fields($t);

        self::assertSame('members', $fields['visibility_first_name']['default']);
        self::assertSame('private', $fields['visibility_last_name']['default']);
        self::assertSame('private', $fields['visibility_address']['default']);
        self::assertSame('private', $fields['visibility_postal_code']['default']);
        self::assertSame('private', $fields['visibility_operator_since']['default']);
    }

    public function testMemberProfileAllowedVisibilityLevelsMatchViewerRole(): void
    {
        self::assertSame(['public'], member_profile_allowed_visibility_levels(null));
        self::assertSame(['public', 'members'], member_profile_allowed_visibility_levels(['is_committee' => 0]));
        self::assertSame(['public', 'members', 'private'], member_profile_allowed_visibility_levels(['is_committee' => 1]));
    }

    public function testDirectoryCardKeepsCallsignOnlyMembersVisible(): void
    {
        $visibleFields = ['first_name', 'country', 'is_uba_member'];

        self::assertTrue(member_directory_card_has_visible_content(['callsign' => 'ON4BEN'], $visibleFields));
        self::assertTrue(member_directory_card_has_visible_content(['callsign' => 'ON7ZB'], $visibleFields));
        self::assertTrue(member_directory_card_has_visible_content(['callsign' => '', 'country' => 'Belgique'], $visibleFields));
        self::assertFalse(member_directory_card_has_visible_content(['callsign' => '', 'country' => '', 'is_uba_member' => 0], $visibleFields));
    }

    public function testMemberProfileOperatorSinceOptionsIncludesCurrentSelection(): void
    {
        $html = member_profile_operator_since_options_html('2020');

        self::assertStringContainsString('<option value="2020" selected>2020</option>', $html);
        self::assertStringContainsString('<option value=""></option>', $html);
    }

    public function testMemberProfileLicenceClassOptionsUseTranslatedLabelsAndKeepCodes(): void
    {
        $t = static fn(string $key): string => [
            'licence_none' => 'Aucune',
            'licence_onl' => 'Écouteur (ONL)',
            'licence_base' => 'Licence de base (ON3)',
            'licence_intermediate' => 'Licence intermédiaire (ON2)',
            'licence_on1' => 'Ancienne licence (ON1)',
            'licence_harec' => 'HAREC',
            'licence_other' => 'Autre',
        ][$key] ?? $key;

        $html = member_profile_licence_class_options_html($t, 'ON2');

        self::assertStringContainsString('value="ON2" selected', $html);
        self::assertStringContainsString('Licence intermédiaire (ON2)', $html);
        self::assertStringContainsString('value="ON3"', $html);
        self::assertStringContainsString('Licence de base (ON3)', $html);
        self::assertStringContainsString('value="ON1"', $html);
        self::assertStringContainsString('>Ancienne licence (ON1)</option>', $html);
        self::assertStringContainsString('>Écouteur (ONL)</option>', $html);
        self::assertStringNotContainsString('>ON2</option>', $html);
        self::assertStringNotContainsString('>ON3</option>', $html);
        self::assertSame('Licence intermédiaire (ON2)', member_profile_licence_class_display_text($t, 'ON2'));
        self::assertSame('Licence de base (ON3)', member_profile_licence_class_display_text($t, 'ON3'));
        self::assertSame('Licence radioamateur', member_profile_licence_class_display_text($t, 'Licence radioamateur'));
    }

    public function testMemberProfileQslViaOptionsUseTranslatedLabelsAndKeepLegacyValues(): void
    {
        $t = static fn(string $key): string => [
            'qsl_choice_bureau' => 'Bureau',
            'qsl_choice_direct' => 'Direct',
            'qsl_choice_bureau_direct' => 'Bureau ou direct',
            'qsl_choice_lotw' => 'LoTW',
            'qsl_choice_eqsl' => 'eQSL',
            'qsl_choice_lotw_eqsl' => 'LoTW + eQSL',
            'qsl_choice_qrz' => 'QRZ.com',
            'qsl_choice_manager' => 'manager QSL',
            'qsl_choice_no_qsl' => 'Pas de QSL',
            'qsl_choice_other' => 'Autre',
        ][$key] ?? $key;

        $html = member_profile_qsl_via_options_html($t, 'bureau_direct');
        $legacyHtml = member_profile_qsl_via_options_html($t, 'Via ON4CRD');

        self::assertStringContainsString('value="bureau_direct" selected', $html);
        self::assertStringContainsString('Bureau ou direct', $html);
        self::assertStringContainsString('value="no_qsl"', $html);
        self::assertStringContainsString('Pas de QSL', $html);
        self::assertStringContainsString('value="Via ON4CRD" selected', $legacyHtml);
        self::assertSame('Pas de QSL', member_profile_qsl_via_display_text($t, 'no_qsl'));
        self::assertSame('Via ON4CRD', member_profile_qsl_via_display_text($t, 'Via ON4CRD'));
    }

    public function testMemberProfileChoicePostKeepsAllowedValuesOnly(): void
    {
        self::assertSame(
            '2m, 70cm',
            member_profile_normalize_choice_post(['2m', 'bad', '70cm', '2m'], member_profile_favourite_band_choices())
        );
        self::assertSame(
            'FM, FT8',
            member_profile_normalize_choice_post('FM, bad, FT8', member_profile_favourite_mode_choices())
        );
    }

    public function testMemberProfileCheckboxGroupMarksCurrentValues(): void
    {
        $html = member_profile_checkbox_group_html('favourite_modes', ['FM', 'SSB'], 'SSB');

        self::assertStringContainsString('name="favourite_modes[]"', $html);
        self::assertStringContainsString('value="SSB" checked', $html);
        self::assertStringNotContainsString('value="FM" checked', $html);
    }

    public function testMemberProfilePreviewRowsHideSensitiveFieldsByDefault(): void
    {
        $t = static fn(string $key): string => $key;
        $member = [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'address' => 'Rue de la Radio 1',
            'postal_code' => '5530',
        ];

        $memberRows = member_profile_preview_rows($member, 'members', $t);
        $memberLabels = array_column($memberRows, 'label');
        self::assertContains('first_name', $memberLabels);
        self::assertNotContains('last_name', $memberLabels);
        self::assertNotContains('address', $memberLabels);
        self::assertNotContains('postal_code', $memberLabels);

        $privateRows = member_profile_preview_rows($member, 'private', $t);
        $privateLabels = array_column($privateRows, 'label');
        self::assertContains('last_name', $privateLabels);
        self::assertContains('address', $privateLabels);
        self::assertContains('postal_code', $privateLabels);
    }

    public function testMemberProfilePreviewRowsIncludeNonEmptyExtendedFields(): void
    {
        $t = static fn(string $key): string => $key;
        $member = [
            'country' => 'Belgique',
            'operator_since' => '2020',
            'cq_zone' => '14',
            'qrz_url' => 'https://www.qrz.com/db/ON4DG',
            'visibility_country' => 'public',
            'visibility_operator_since' => 'public',
            'visibility_qrz' => 'public',
        ];

        $rows = member_profile_preview_rows($member, 'public', $t);
        $labels = array_column($rows, 'label');

        self::assertContains('country', $labels);
        self::assertContains('operator_since', $labels);
        self::assertContains('cq_zone', $labels);
        self::assertContains('qrz_url', $labels);
        self::assertStringContainsString('country-with-flag', (string) $rows[0]['html']);
    }

    public function testMemberProfilePreviewRowsTranslateQslChoiceCodes(): void
    {
        $t = static fn(string $key): string => [
            'qsl_via' => 'QSL via',
            'qsl_choice_bureau' => 'Bureau',
            'qsl_choice_direct' => 'Direct',
            'qsl_choice_bureau_direct' => 'Bureau ou direct',
            'qsl_choice_lotw' => 'LoTW',
            'qsl_choice_eqsl' => 'eQSL',
            'qsl_choice_lotw_eqsl' => 'LoTW + eQSL',
            'qsl_choice_qrz' => 'QRZ.com',
            'qsl_choice_manager' => 'manager QSL',
            'qsl_choice_no_qsl' => 'Pas de QSL',
            'qsl_choice_other' => 'Autre',
        ][$key] ?? $key;
        $member = [
            'qsl_via' => 'no_qsl',
            'visibility_qsl' => 'public',
        ];

        $rows = member_profile_preview_rows($member, 'public', $t);

        self::assertSame('QSL via', $rows[0]['label']);
        self::assertSame('Pas de QSL', $rows[0]['text']);
        self::assertSame('Pas de QSL', $rows[0]['html']);
    }

    public function testMemberProfilePreviewRowsTranslateLicenceClassCodes(): void
    {
        $t = static fn(string $key): string => [
            'licence' => 'Licence',
            'licence_none' => 'Aucune',
            'licence_onl' => 'Écouteur (ONL)',
            'licence_base' => 'Licence de base (ON3)',
            'licence_intermediate' => 'Licence intermédiaire (ON2)',
            'licence_on1' => 'Ancienne licence (ON1)',
            'licence_harec' => 'HAREC',
            'licence_other' => 'Autre',
        ][$key] ?? $key;
        $member = [
            'licence_class' => 'ON3',
            'visibility_licence_class' => 'public',
        ];

        $rows = member_profile_preview_rows($member, 'public', $t);

        self::assertSame('Licence', $rows[0]['label']);
        self::assertSame('Licence de base (ON3)', $rows[0]['text']);
        self::assertSame('Licence de base (ON3)', $rows[0]['html']);
    }

    public function testMemberProfileSelectColumnsCoverProfileAndGdprFields(): void
    {
        $selectSql = member_profile_select_columns_sql();
        foreach ([
            'callsign',
            'first_name',
            'last_name',
            'full_name',
            'email',
            'phone',
            'country',
            'address',
            'postal_code',
            'qth',
            'locator',
            'licence_class',
            'operator_since',
            'cq_zone',
            'itu_zone',
            'qsl_via',
            'lotw_username',
            'eqsl_username',
            'qrz_url',
            'website',
            'is_uba_member',
            'uba_member_number',
            'station_equipment',
            'antennas',
            'favourite_bands',
            'favourite_modes',
            'interests',
            'photo_path',
            'avatar_path',
            'visibility_photo',
            'visibility_licence_class',
            'visibility_qsl',
            'visibility_qrz',
            'visibility_favourite_bands',
            'visibility_favourite_modes',
            'visibility_station',
            'visibility_antennas',
            'visibility_interests',
        ] as $column) {
            self::assertStringContainsString($column, $selectSql, sprintf('%s must be selected for profile/GDPR previews.', $column));
        }
    }

    public function testMemberQrzSaveKeepsExistingUrlForSameCallsign(): void
    {
        self::assertSame(
            'https://www.qrz.com/db/ON4DG',
            member_qrz_url_for_profile_save('ON4DG', 'ON4DG', 'https://www.qrz.com/db/ON4DG')
        );
    }

    public function testMemberQrzSaveUsesSubmittedUrlWhenProvided(): void
    {
        self::assertSame(
            'https://www.qrz.com/db/ON7ZB',
            member_qrz_url_for_profile_save('ON4DG', 'ON4DG', 'https://www.qrz.com/db/ON4DG', ' https://www.qrz.com/db/ON7ZB ')
        );
    }

    public function testMemberLotwUsernameDefaultsToCallsignWhenEmpty(): void
    {
        self::assertSame('ON4DG', member_lotw_username_for_profile_save(' on4dg ', ''));
        self::assertSame('custom-lotw', member_lotw_username_for_profile_save('ON4DG', ' custom-lotw '));
        self::assertNull(member_lotw_username_for_profile_save('', ''));
    }

    public function testArticleTranslationTargetLocalesCoverEveryNonFrenchLocale(): void
    {
        $targets = article_translation_target_locales();

        self::assertNotContains('fr', $targets);
        self::assertSame(count(supported_locales()) - 1, count($targets));
        self::assertContains('en', $targets);
        self::assertContains('id', $targets);
    }

    public function testArticleTranslationPublicStatusesExcludePendingReview(): void
    {
        $statuses = article_translation_public_statuses();

        self::assertSame(['reviewed', 'auto'], $statuses);
        self::assertNotContains('needs_review', $statuses);
        self::assertNotContains('missing', $statuses);
    }

    public function testArticleTranslationDeeplTargetsCoverEveryArticleLocale(): void
    {
        foreach (article_translation_target_locales() as $locale) {
            self::assertNotNull(article_translation_deepl_target($locale), 'Missing DeepL target for ' . $locale);
        }

        self::assertSame('HR', article_translation_deepl_target('hr'));
        self::assertSame('GA', article_translation_deepl_target('ga'));
        self::assertSame('MT', article_translation_deepl_target('mt'));
        self::assertSame('HI', article_translation_deepl_target('hi'));
        self::assertSame('BN', article_translation_deepl_target('bn'));
    }

    public function testArticleTranslationSourceHashTracksSourceChanges(): void
    {
        $baseHash = article_translation_source_hash('Titre', 'Resume', '<p>Contenu</p>');

        self::assertSame($baseHash, article_translation_source_hash(' Titre ', ' Resume ', ' <p>Contenu</p> '));
        self::assertNotSame($baseHash, article_translation_source_hash('Titre', 'Resume', '<p>Contenu modifie</p>'));
    }

    public function testArticleExcerptFromInputTreatsEmptyEditorMarkupAsMissing(): void
    {
        self::assertSame('', article_excerpt_from_input(''));
        self::assertSame('', article_excerpt_from_input('<p><br></p>'));
        self::assertSame('', article_excerpt_from_input("<div>\n&nbsp;\n</div>"));
        self::assertSame("Resume\nSuite", article_excerpt_from_input('<p>Resume</p><p><strong>Suite</strong></p>'));
    }

    public function testNewsTranslationTargetLocalesCoverEveryNonFrenchLocale(): void
    {
        $targets = news_translation_target_locales();

        self::assertNotContains('fr', $targets);
        self::assertSame(count(supported_locales()) - 1, count($targets));
        self::assertContains('en', $targets);
        self::assertContains('id', $targets);
    }

    public function testNewsTranslationPublicStatusesExcludePendingReview(): void
    {
        $statuses = news_translation_public_statuses();

        self::assertSame(['reviewed', 'auto'], $statuses);
        self::assertNotContains('needs_review', $statuses);
        self::assertNotContains('missing', $statuses);
    }

    public function testNewsTranslationDeeplTargetsCoverEveryNewsLocale(): void
    {
        foreach (news_translation_target_locales() as $locale) {
            self::assertNotNull(news_translation_deepl_target($locale), 'Missing DeepL target for ' . $locale);
        }

        self::assertSame('HR', news_translation_deepl_target('hr'));
        self::assertSame('GA', news_translation_deepl_target('ga'));
        self::assertSame('MT', news_translation_deepl_target('mt'));
        self::assertSame('HI', news_translation_deepl_target('hi'));
        self::assertSame('BN', news_translation_deepl_target('bn'));
    }

    public function testNewsTranslationSourceHashTracksSourceChanges(): void
    {
        $baseHash = news_translation_source_hash('Titre', 'Resume', '<p>Contenu</p>');

        self::assertSame($baseHash, news_translation_source_hash(' Titre ', ' Resume ', ' <p>Contenu</p> '));
        self::assertNotSame($baseHash, news_translation_source_hash('Titre', 'Resume', '<p>Contenu modifie</p>'));
    }

    public function testArticleTranslationPendingFallbackDetectsSourceCopiesOnly(): void
    {
        $source = [
            'title' => 'Titre',
            'excerpt' => 'Resume',
            'content' => '<p>Contenu</p>',
        ];

        self::assertTrue(article_translation_pending_row_is_source_fallback([
            'status' => 'needs_review',
            'title' => ' Titre ',
            'excerpt' => ' Resume ',
            'content' => ' <p>Contenu</p> ',
        ], $source));
        self::assertFalse(article_translation_pending_row_is_source_fallback([
            'status' => 'needs_review',
            'title' => 'Translated title',
            'excerpt' => 'Resume',
            'content' => '<p>Contenu</p>',
        ], $source));
        self::assertFalse(article_translation_pending_row_is_source_fallback([
            'status' => 'auto',
            'title' => 'Titre',
            'excerpt' => 'Resume',
            'content' => '<p>Contenu</p>',
        ], $source));
    }

    public function testArticlePublicationDatetimePrefersPublishedAt(): void
    {
        self::assertSame('2026-06-03 09:30:00', article_publication_datetime([
            'published_at' => '2026-06-03 09:30:00',
            'created_at' => '2026-06-01 08:00:00',
            'updated_at' => '2026-06-04 10:00:00',
        ]));
        self::assertSame('2026-06-01 08:00:00', article_publication_datetime([
            'published_at' => null,
            'created_at' => '2026-06-01 08:00:00',
            'updated_at' => '2026-06-04 10:00:00',
        ]));
        self::assertNull(article_publication_datetime([
            'published_at' => '',
            'created_at' => 'not-a-date',
            'updated_at' => '',
        ]));
        self::assertSame('COALESCE(published_at, created_at, updated_at)', article_publication_sort_expression());
        self::assertSame('COALESCE(a.published_at, a.created_at, a.updated_at)', article_publication_sort_expression_for_alias('a'));
        self::assertSame('COALESCE(published_at, created_at, updated_at)', article_publication_sort_expression_for_alias('bad.alias'));
    }

    public function testArticleDuplicateSlugErrorDetectsUniqueViolation(): void
    {
        $exception = new PDOException('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry "article" for key "articles.slug"');
        $exception->errorInfo = ['23000', 1062, 'Duplicate entry "article" for key "articles.slug"'];

        self::assertTrue(article_is_duplicate_slug_error($exception));
        self::assertFalse(article_is_duplicate_slug_error(new RuntimeException('Duplicate entry')));
    }

    /**
     * @param array<string,string> $entries
     */
    private static function zipFixture(array $entries): string
    {
        $localParts = [];
        $centralParts = [];
        $offset = 0;

        foreach ($entries as $name => $content) {
            $nameBytes = $name;
            $compressed = gzdeflate($content);
            self::assertIsString($compressed);
            $crc = crc32($content);
            $method = 8;

            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0,
                $method,
                0,
                0,
                $crc,
                strlen($compressed),
                strlen($content),
                strlen($nameBytes),
                0
            );
            $localParts[] = $localHeader . $nameBytes . $compressed;

            $centralParts[] = pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                $method,
                0,
                0,
                $crc,
                strlen($compressed),
                strlen($content),
                strlen($nameBytes),
                0,
                0,
                0,
                0,
                0,
                $offset
            ) . $nameBytes;

            $offset += strlen($localHeader) + strlen($nameBytes) + strlen($compressed);
        }

        $centralDirectory = implode('', $centralParts);
        $localData = implode('', $localParts);
        $entryCount = count($entries);
        $endRecord = pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            $entryCount,
            $entryCount,
            strlen($centralDirectory),
            strlen($localData),
            0
        );

        return $localData . $centralDirectory . $endRecord;
    }

}
