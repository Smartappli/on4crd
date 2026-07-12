<?php
declare(strict_types=1);

/**
 * Immutable data prepared once for a DOCX rendering pass.
 *
 * The low-level XML converters remain procedural so they can also be reused by
 * the library/document-preview modules. This object keeps orchestration state
 * out of the public facade and prevents the main document, headers and notes
 * from rebuilding the same package metadata independently.
 */
final class ArticleDocxImportContext
{
    /**
     * @param array<string,string> $relationships
     * @param array<string,string> $imageDataUris
     * @param array<string,string> $numberingFormats
     * @param array<string,array<string,string>> $noteBodies
     * @param array<string,string> $altChunkHtmlByRelationshipId
     */
    public function __construct(
        public readonly DOMXPath $xpath,
        public readonly string $relationshipsXml,
        public readonly array $relationships,
        public readonly array $imageDataUris,
        public readonly array $numberingFormats,
        public readonly array $noteBodies,
        public readonly array $altChunkHtmlByRelationshipId,
    ) {
    }
}

/**
 * DOCX package reader and HTML rendering coordinator for article imports.
 */
final class ArticleDocxImporter
{
    public function isValidDocument(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }

        $contentTypesXml = article_docx_part_contents($path, '[Content_Types].xml');
        $documentXml = article_docx_part_contents($path, 'word/document.xml');

        return $this->hasWordMainDocumentContentType($contentTypesXml)
            && $this->hasWordDocumentBody($documentXml);
    }

    public function extractHtml(string $path): string
    {
        $xml = article_docx_part_contents($path, 'word/document.xml');
        if ($xml === '') {
            return '';
        }

        $context = $this->createContext($path, $xml);
        if ($context === null) {
            return article_import_text_to_html(strip_tags($xml));
        }

        return $this->renderDocument($path, $context);
    }

    private function createContext(string $path, string $xml): ?ArticleDocxImportContext
    {
        $dom = $this->loadXmlDocument($xml);
        if ($dom === null) {
            return null;
        }

        $xpath = new DOMXPath($dom);
        $relationshipsXml = article_docx_part_contents($path, 'word/_rels/document.xml.rels');
        $relationships = article_docx_relationship_targets($relationshipsXml);
        $numberingFormats = article_docx_numbering_formats(article_docx_part_contents($path, 'word/numbering.xml'));

        return new ArticleDocxImportContext(
            $xpath,
            $relationshipsXml,
            $relationships,
            article_docx_image_data_uris($path, $relationshipsXml, 'word', article_docx_image_dimensions_by_relationship_id($xpath)),
            $numberingFormats,
            [
                'footnote' => article_docx_note_bodies($path, 'footnote', $numberingFormats),
                'endnote' => article_docx_note_bodies($path, 'endnote', $numberingFormats),
            ],
            article_docx_alt_chunk_html_by_relationship_id($path, $relationshipsXml),
        );
    }

    private function renderDocument(string $path, ArticleDocxImportContext $context): string
    {
        $referencedNotes = [];
        $headerFooterHtml = article_docx_header_footer_html(
            $path,
            $context->relationshipsXml,
            $context->numberingFormats,
            $context->noteBodies,
            $referencedNotes,
        );
        $bodyNodes = $context->xpath->query('/*[local-name()="document"]/*[local-name()="body"]/*');
        if (!$bodyNodes instanceof DOMNodeList) {
            return '';
        }

        $html = $headerFooterHtml['headers'];
        $openListTag = null;
        foreach ($bodyNodes as $block) {
            if (!$block instanceof DOMElement) {
                continue;
            }

            article_docx_append_block_html(
                $block,
                $context->xpath,
                $context->relationships,
                $context->imageDataUris,
                $context->numberingFormats,
                $html,
                $openListTag,
                $context->noteBodies,
                $referencedNotes,
                $context->altChunkHtmlByRelationshipId,
            );
        }

        if ($openListTag !== null) {
            $html[] = '</' . $openListTag . '>';
        }
        foreach ($headerFooterHtml['footers'] as $footerHtml) {
            $html[] = '<hr>' . $footerHtml;
        }

        $notesHtml = article_docx_referenced_notes_html($context->noteBodies, $referencedNotes);
        if ($notesHtml !== '') {
            $html[] = $notesHtml;
        }

        return sanitize_rich_html(implode("\n", $html));
    }

    private function hasWordMainDocumentContentType(string $xml): bool
    {
        $dom = $this->loadXmlDocument($xml);
        if ($dom === null) {
            return false;
        }

        $xpath = new DOMXPath($dom);
        $overrides = $xpath->query('/*[local-name()="Types"]/*[local-name()="Override"]');
        if (!$overrides instanceof DOMNodeList) {
            return false;
        }

        foreach ($overrides as $override) {
            if (!$override instanceof DOMElement) {
                continue;
            }

            if ($override->getAttribute('PartName') === '/word/document.xml'
                && $override->getAttribute('ContentType') === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml') {
                return true;
            }
        }

        return false;
    }

    private function hasWordDocumentBody(string $xml): bool
    {
        $dom = $this->loadXmlDocument($xml);
        if ($dom === null || $dom->documentElement?->localName !== 'document') {
            return false;
        }

        $bodyNodes = (new DOMXPath($dom))->query('/*[local-name()="document"]/*[local-name()="body"]');

        return $bodyNodes instanceof DOMNodeList && $bodyNodes->length > 0;
    }

    private function loadXmlDocument(string $xml): ?DOMDocument
    {
        if ($xml === '') {
            return null;
        }

        $dom = new DOMDocument();
        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        return $loaded ? $dom : null;
    }
}
