<?php
declare(strict_types=1);

if (!function_exists('article_import_text_to_html')) {
function article_import_text_to_html(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", trim($text));
    $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
    if ($text === '') {
        return '';
    }

    $lines = explode("\n", $text);
    $html = [];
    $paragraph = [];
    $listType = null;

    $flushParagraph = static function () use (&$html, &$paragraph): void {
        $content = trim(implode(' ', $paragraph));
        $paragraph = [];
        if ($content !== '') {
            $html[] = '<p>' . e($content) . '</p>';
        }
    };
    $closeList = static function () use (&$html, &$listType): void {
        if ($listType !== null) {
            $html[] = '</' . $listType . '>';
            $listType = null;
        }
    };

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            $flushParagraph();
            $closeList();
            continue;
        }

        if (preg_match('/^#{1,6}\s+(.+)$/u', $line, $matches)) {
            $flushParagraph();
            $closeList();
            $level = min(4, max(2, strspn($line, '#') + 1));
            $html[] = '<h' . $level . '>' . e(trim($matches[1])) . '</h' . $level . '>';
            continue;
        }

        if (preg_match('/^(?:[-*•])\s+(.+)$/u', $line, $matches)) {
            $flushParagraph();
            if ($listType !== 'ul') {
                $closeList();
                $listType = 'ul';
                $html[] = '<ul>';
            }
            $html[] = '<li>' . e(trim($matches[1])) . '</li>';
            continue;
        }

        if (preg_match('/^\d+[\.)]\s+(.+)$/u', $line, $matches)) {
            $flushParagraph();
            if ($listType !== 'ol') {
                $closeList();
                $listType = 'ol';
                $html[] = '<ol>';
            }
            $html[] = '<li>' . e(trim($matches[1])) . '</li>';
            continue;
        }

        $closeList();
        $paragraph[] = $line;
    }

    $flushParagraph();
    $closeList();

    return sanitize_rich_html(implode("\n", $html));
}
}

if (!function_exists('article_extract_docx_html')) {
function article_extract_docx_html(string $path): string
{
    if (!is_file($path)) {
        return '';
    }

    $xml = article_docx_part_contents($path, 'word/document.xml');
    if ($xml === '') {
        return '';
    }

    $dom = new DOMDocument();
    $previousUseInternalErrors = libxml_use_internal_errors(true);
    $loaded = $dom->loadXML($xml);
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);
    if (!$loaded) {
        return article_import_text_to_html(strip_tags($xml));
    }

    $xpath = new DOMXPath($dom);
    $relationships = article_docx_relationship_targets(article_docx_part_contents($path, 'word/_rels/document.xml.rels'));
    $numberingFormats = article_docx_numbering_formats(article_docx_part_contents($path, 'word/numbering.xml'));
    $bodyNodes = $xpath->query('/*[local-name()="document"]/*[local-name()="body"]/*');
    if (!$bodyNodes instanceof DOMNodeList) {
        return '';
    }

    $html = [];
    $openListTag = null;
    foreach ($bodyNodes as $block) {
        if (!$block instanceof DOMElement) {
            continue;
        }

        $localName = $block->localName;
        if ($localName === 'tbl') {
            if ($openListTag !== null) {
                $html[] = '</' . $openListTag . '>';
                $openListTag = null;
            }
            $table = article_docx_table_html($block, $xpath, $relationships);
            if ($table !== '') {
                $html[] = $table;
            }
            continue;
        }

        if ($localName !== 'p') {
            continue;
        }

        $inlineHtml = article_docx_inline_html($block, $xpath, $relationships);
        if (article_docx_html_is_empty($inlineHtml)) {
            if ($openListTag !== null) {
                $html[] = '</' . $openListTag . '>';
                $openListTag = null;
            }
            continue;
        }

        $style = article_docx_paragraph_style($block, $xpath);
        $listTag = article_docx_paragraph_list_tag($block, $xpath, $numberingFormats);

        if ($listTag !== '') {
            if ($openListTag !== $listTag) {
                if ($openListTag !== null) {
                    $html[] = '</' . $openListTag . '>';
                }
                $html[] = '<' . $listTag . '>';
                $openListTag = $listTag;
            }
            $html[] = '<li>' . $inlineHtml . '</li>';
            continue;
        }

        if ($openListTag !== null) {
            $html[] = '</' . $openListTag . '>';
            $openListTag = null;
        }

        if (str_contains($style, 'heading') || str_contains($style, 'titre')) {
            $level = preg_match('/([1-6])/', $style, $matches) ? (int) $matches[1] + 1 : 2;
            $level = min(4, max(2, $level));
            $html[] = '<h' . $level . '>' . $inlineHtml . '</h' . $level . '>';
        } else {
            $html[] = '<p>' . $inlineHtml . '</p>';
        }
    }

    if ($openListTag !== null) {
        $html[] = '</' . $openListTag . '>';
    }

    return sanitize_rich_html(implode("\n", $html));
}
}

if (!function_exists('article_docx_numbering_formats')) {
/**
 * @return array<string,array<string,string>>
 */
function article_docx_numbering_formats(string $xml): array
{
    if ($xml === '') {
        return [];
    }

    $dom = new DOMDocument();
    $previousUseInternalErrors = libxml_use_internal_errors(true);
    $loaded = $dom->loadXML($xml);
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);
    if (!$loaded) {
        return [];
    }

    $xpath = new DOMXPath($dom);
    $abstractFormats = [];
    $abstractNodes = $xpath->query('//*[local-name()="abstractNum"]');
    if ($abstractNodes instanceof DOMNodeList) {
        foreach ($abstractNodes as $abstractNode) {
            if (!$abstractNode instanceof DOMElement) {
                continue;
            }

            $abstractId = article_docx_attribute($abstractNode, 'abstractNumId');
            if ($abstractId === '') {
                continue;
            }

            $lvlNodes = $xpath->query('./*[local-name()="lvl"]', $abstractNode);
            if (!$lvlNodes instanceof DOMNodeList) {
                continue;
            }

            foreach ($lvlNodes as $lvlNode) {
                if (!$lvlNode instanceof DOMElement) {
                    continue;
                }

                $level = article_docx_attribute($lvlNode, 'ilvl');
                $formatNodes = $xpath->query('./*[local-name()="numFmt"]', $lvlNode);
                $formatNode = $formatNodes instanceof DOMNodeList ? $formatNodes->item(0) : null;
                if ($level !== '' && $formatNode instanceof DOMElement) {
                    $abstractFormats[$abstractId][$level] = strtolower(article_docx_attribute($formatNode, 'val'));
                }
            }
        }
    }

    $formats = [];
    $numNodes = $xpath->query('//*[local-name()="num"]');
    if ($numNodes instanceof DOMNodeList) {
        foreach ($numNodes as $numNode) {
            if (!$numNode instanceof DOMElement) {
                continue;
            }

            $numId = article_docx_attribute($numNode, 'numId');
            $abstractIdNodes = $xpath->query('./*[local-name()="abstractNumId"]', $numNode);
            $abstractIdNode = $abstractIdNodes instanceof DOMNodeList ? $abstractIdNodes->item(0) : null;
            $abstractId = $abstractIdNode instanceof DOMElement ? article_docx_attribute($abstractIdNode, 'val') : '';
            if ($numId !== '' && $abstractId !== '' && isset($abstractFormats[$abstractId])) {
                $formats[$numId] = $abstractFormats[$abstractId];
            }
        }
    }

    return $formats;
}
}

if (!function_exists('article_docx_paragraph_list_tag')) {
/**
 * @param array<string,array<string,string>> $numberingFormats
 */
function article_docx_paragraph_list_tag(DOMElement $paragraph, DOMXPath $xpath, array $numberingFormats): string
{
    $numPrNodes = $xpath->query('./*[local-name()="pPr"]/*[local-name()="numPr"]', $paragraph);
    $numPr = $numPrNodes instanceof DOMNodeList ? $numPrNodes->item(0) : null;
    if (!$numPr instanceof DOMElement) {
        return '';
    }

    $numIdNodes = $xpath->query('./*[local-name()="numId"]', $numPr);
    $numIdNode = $numIdNodes instanceof DOMNodeList ? $numIdNodes->item(0) : null;
    $numId = $numIdNode instanceof DOMElement ? article_docx_attribute($numIdNode, 'val') : '';
    if ($numId === '') {
        return 'ul';
    }

    $levelNodes = $xpath->query('./*[local-name()="ilvl"]', $numPr);
    $levelNode = $levelNodes instanceof DOMNodeList ? $levelNodes->item(0) : null;
    $level = $levelNode instanceof DOMElement ? article_docx_attribute($levelNode, 'val') : '0';
    $format = $numberingFormats[$numId][$level] ?? $numberingFormats[$numId]['0'] ?? '';

    return article_docx_numbering_format_tag($format);
}
}

if (!function_exists('article_docx_numbering_format_tag')) {
function article_docx_numbering_format_tag(string $format): string
{
    $format = strtolower(trim($format));
    if ($format === '' || in_array($format, ['bullet', 'none'], true)) {
        return 'ul';
    }

    return 'ol';
}
}

if (!function_exists('article_docx_part_contents')) {
function article_docx_part_contents(string $path, string $partName): string
{
    $partName = ltrim(str_replace('\\', '/', $partName), '/');
    if ($partName === '' || !is_file($path)) {
        return '';
    }

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($path) === true) {
            $contents = $zip->getFromName($partName);
            $zip->close();
            if (is_string($contents)) {
                return $contents;
            }
        }
    }

    $contents = article_zip_entry_contents($path, $partName);
    if ($contents !== '') {
        return $contents;
    }

    $unzip = article_find_binary('unzip');
    if ($unzip === '') {
        return '';
    }

    $output = @shell_exec(escapeshellarg($unzip) . ' -p ' . escapeshellarg($path) . ' ' . escapeshellarg($partName));
    return is_string($output) ? $output : '';
}
}

if (!function_exists('article_zip_entry_contents')) {
function article_zip_entry_contents(string $path, string $entryName): string
{
    $entryName = ltrim(str_replace('\\', '/', $entryName), '/');
    $zip = is_file($path) ? file_get_contents($path) : false;
    if (!is_string($zip) || $zip === '' || $entryName === '') {
        return '';
    }

    $length = strlen($zip);
    $tailLength = min($length, 22 + 0xffff);
    $tail = substr($zip, $length - $tailLength);
    $eocdTailOffset = strrpos($tail, "PK\x05\x06");
    if ($eocdTailOffset === false) {
        return '';
    }

    $eocdOffset = $length - $tailLength + $eocdTailOffset;
    if ($eocdOffset + 22 > $length) {
        return '';
    }

    $centralDirectorySize = article_unpack_uint32(substr($zip, $eocdOffset + 12, 4));
    $centralDirectoryOffset = article_unpack_uint32(substr($zip, $eocdOffset + 16, 4));
    if ($centralDirectoryOffset < 0 || $centralDirectorySize < 0 || $centralDirectoryOffset + $centralDirectorySize > $length) {
        return '';
    }

    $position = $centralDirectoryOffset;
    $end = $centralDirectoryOffset + $centralDirectorySize;
    while ($position + 46 <= $end && substr($zip, $position, 4) === "PK\x01\x02") {
        $method = article_unpack_uint16(substr($zip, $position + 10, 2));
        $compressedSize = article_unpack_uint32(substr($zip, $position + 20, 4));
        $fileNameLength = article_unpack_uint16(substr($zip, $position + 28, 2));
        $extraLength = article_unpack_uint16(substr($zip, $position + 30, 2));
        $commentLength = article_unpack_uint16(substr($zip, $position + 32, 2));
        $localHeaderOffset = article_unpack_uint32(substr($zip, $position + 42, 4));
        $fileName = substr($zip, $position + 46, $fileNameLength);

        if ($fileName === $entryName) {
            if ($localHeaderOffset + 30 > $length || substr($zip, $localHeaderOffset, 4) !== "PK\x03\x04") {
                return '';
            }

            $localNameLength = article_unpack_uint16(substr($zip, $localHeaderOffset + 26, 2));
            $localExtraLength = article_unpack_uint16(substr($zip, $localHeaderOffset + 28, 2));
            $dataOffset = $localHeaderOffset + 30 + $localNameLength + $localExtraLength;
            if ($dataOffset < 0 || $dataOffset + $compressedSize > $length) {
                return '';
            }

            $compressed = substr($zip, $dataOffset, $compressedSize);
            if ($method === 0) {
                return $compressed;
            }
            if ($method === 8) {
                $inflated = @gzinflate($compressed);
                return is_string($inflated) ? $inflated : '';
            }

            return '';
        }

        $position += 46 + $fileNameLength + $extraLength + $commentLength;
    }

    return '';
}
}

if (!function_exists('article_unpack_uint16')) {
function article_unpack_uint16(string $bytes): int
{
    $value = unpack('v', $bytes);
    return is_array($value) ? (int) $value[1] : 0;
}
}

if (!function_exists('article_unpack_uint32')) {
function article_unpack_uint32(string $bytes): int
{
    $value = unpack('V', $bytes);
    return is_array($value) ? (int) $value[1] : 0;
}
}

if (!function_exists('article_docx_relationship_targets')) {
/**
 * @return array<string,string>
 */
function article_docx_relationship_targets(string $xml): array
{
    if ($xml === '') {
        return [];
    }

    $dom = new DOMDocument();
    $previousUseInternalErrors = libxml_use_internal_errors(true);
    $loaded = $dom->loadXML($xml);
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);
    if (!$loaded) {
        return [];
    }

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//*[local-name()="Relationship"]');
    if (!$nodes instanceof DOMNodeList) {
        return [];
    }

    $targets = [];
    foreach ($nodes as $node) {
        if (!$node instanceof DOMElement) {
            continue;
        }
        $id = trim($node->getAttribute('Id'));
        $target = trim($node->getAttribute('Target'));
        if ($id !== '' && $target !== '') {
            $targets[$id] = $target;
        }
    }

    return $targets;
}
}

if (!function_exists('article_docx_attribute')) {
function article_docx_attribute(DOMElement $element, string $localName): string
{
    foreach ($element->attributes as $attribute) {
        if ($attribute->localName === $localName || $attribute->name === $localName) {
            return (string) $attribute->value;
        }
    }

    return '';
}
}

if (!function_exists('article_docx_paragraph_style')) {
function article_docx_paragraph_style(DOMElement $paragraph, DOMXPath $xpath): string
{
    $styleNodes = $xpath->query('./*[local-name()="pPr"]/*[local-name()="pStyle"]', $paragraph);
    $styleNode = $styleNodes instanceof DOMNodeList ? $styleNodes->item(0) : null;
    if (!$styleNode instanceof DOMElement) {
        return '';
    }

    return strtolower(article_docx_attribute($styleNode, 'val'));
}
}

if (!function_exists('article_docx_inline_html')) {
/**
 * @param array<string,string> $relationships
 */
function article_docx_inline_html(DOMNode $container, DOMXPath $xpath, array $relationships): string
{
    $html = '';
    foreach ($container->childNodes as $child) {
        if ($child instanceof DOMText) {
            if (trim($child->textContent) === '') {
                continue;
            }
            $html .= e($child->textContent);
            continue;
        }
        if (!$child instanceof DOMElement) {
            continue;
        }

        $localName = $child->localName;
        if ($localName === 'pPr' || $localName === 'rPr') {
            continue;
        }
        if ($localName === 'r') {
            $html .= article_docx_run_html($child, $xpath);
            continue;
        }
        if ($localName === 'hyperlink') {
            $inner = article_docx_inline_html($child, $xpath, $relationships);
            if (article_docx_html_is_empty($inner)) {
                continue;
            }

            $href = '';
            $relationshipId = article_docx_attribute($child, 'id');
            if ($relationshipId !== '' && isset($relationships[$relationshipId])) {
                $href = article_docx_safe_href($relationships[$relationshipId]);
            } else {
                $anchor = preg_replace('/[^a-z0-9_.:-]+/i', '-', article_docx_attribute($child, 'anchor')) ?? '';
                $href = $anchor !== '' ? article_docx_safe_href('#' . trim($anchor, '-')) : '';
            }

            $html .= $href !== '' ? '<a href="' . e($href) . '">' . $inner . '</a>' : $inner;
            continue;
        }
        if ($localName === 'tab') {
            $html .= ' ';
            continue;
        }
        if ($localName === 'br') {
            $html .= '<br>';
            continue;
        }

        $html .= article_docx_inline_html($child, $xpath, $relationships);
    }

    return trim((string) preg_replace('/[ \t\r\n]+/u', ' ', $html));
}
}

if (!function_exists('article_docx_run_html')) {
function article_docx_run_html(DOMElement $run, DOMXPath $xpath): string
{
    $html = '';
    foreach ($run->childNodes as $child) {
        if (!$child instanceof DOMElement) {
            continue;
        }

        $localName = $child->localName;
        if ($localName === 'rPr') {
            continue;
        }
        if ($localName === 't' || $localName === 'delText') {
            $html .= e($child->textContent);
            continue;
        }
        if ($localName === 'tab') {
            $html .= ' ';
            continue;
        }
        if ($localName === 'br') {
            $html .= '<br>';
        }
    }

    if (article_docx_html_is_empty($html)) {
        return '';
    }

    if (article_docx_run_property_enabled($run, $xpath, 'u')) {
        $html = '<u>' . $html . '</u>';
    }
    if (article_docx_run_property_enabled($run, $xpath, 'i')) {
        $html = '<em>' . $html . '</em>';
    }
    if (article_docx_run_property_enabled($run, $xpath, 'b')) {
        $html = '<strong>' . $html . '</strong>';
    }

    return $html;
}
}

if (!function_exists('article_docx_run_property_enabled')) {
function article_docx_run_property_enabled(DOMElement $run, DOMXPath $xpath, string $property): bool
{
    $nodes = $xpath->query('./*[local-name()="rPr"]/*[local-name()="' . $property . '"]', $run);
    $node = $nodes instanceof DOMNodeList ? $nodes->item(0) : null;
    if (!$node instanceof DOMElement) {
        return false;
    }

    $value = strtolower(article_docx_attribute($node, 'val'));
    return !in_array($value, ['0', 'false', 'none', 'off'], true);
}
}

if (!function_exists('article_docx_table_html')) {
/**
 * @param array<string,string> $relationships
 */
function article_docx_table_html(DOMElement $table, DOMXPath $xpath, array $relationships): string
{
    $rows = [];
    $rowNodes = $xpath->query('./*[local-name()="tr"]', $table);
    if (!$rowNodes instanceof DOMNodeList) {
        return '';
    }

    foreach ($rowNodes as $rowNode) {
        if (!$rowNode instanceof DOMElement) {
            continue;
        }

        $cells = [];
        $cellNodes = $xpath->query('./*[local-name()="tc"]', $rowNode);
        if (!$cellNodes instanceof DOMNodeList) {
            continue;
        }

        foreach ($cellNodes as $cellNode) {
            if (!$cellNode instanceof DOMElement) {
                continue;
            }

            $paragraphs = [];
            $paragraphNodes = $xpath->query('./*[local-name()="p"]', $cellNode);
            if ($paragraphNodes instanceof DOMNodeList) {
                foreach ($paragraphNodes as $paragraphNode) {
                    if ($paragraphNode instanceof DOMElement) {
                        $inline = article_docx_inline_html($paragraphNode, $xpath, $relationships);
                        if (!article_docx_html_is_empty($inline)) {
                            $paragraphs[] = $inline;
                        }
                    }
                }
            }

            $attributes = '';
            $gridSpanNodes = $xpath->query('./*[local-name()="tcPr"]/*[local-name()="gridSpan"]', $cellNode);
            $gridSpanNode = $gridSpanNodes instanceof DOMNodeList ? $gridSpanNodes->item(0) : null;
            if ($gridSpanNode instanceof DOMElement) {
                $gridSpan = (int) article_docx_attribute($gridSpanNode, 'val');
                if ($gridSpan > 1 && $gridSpan <= 20) {
                    $attributes = ' colspan="' . $gridSpan . '"';
                }
            }

            $cells[] = '<td' . $attributes . '>' . implode('<br>', $paragraphs) . '</td>';
        }

        if ($cells !== []) {
            $rows[] = '<tr>' . implode('', $cells) . '</tr>';
        }
    }

    return $rows === [] ? '' : '<table><tbody>' . implode('', $rows) . '</tbody></table>';
}
}

if (!function_exists('article_docx_safe_href')) {
function article_docx_safe_href(string $href): string
{
    try {
        $safe = sanitize_href_attribute($href);
    } catch (Throwable) {
        return '';
    }

    return $safe ?? '';
}
}

if (!function_exists('article_docx_html_is_empty')) {
function article_docx_html_is_empty(string $html): bool
{
    return trim(html_entity_decode(strip_tags(str_replace('<br>', '', $html)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) === '';
}
}

if (!function_exists('article_find_binary')) {
function article_find_binary(string $binary): string
{
    $binary = trim($binary);
    if ($binary === '' || preg_match('/[^a-z0-9_.-]/i', $binary)) {
        return '';
    }

    $command = PHP_OS_FAMILY === 'Windows'
        ? 'where ' . escapeshellarg($binary) . ' 2>NUL'
        : 'command -v ' . escapeshellarg($binary) . ' 2>/dev/null';
    $output = @shell_exec($command);
    if (!is_string($output) || trim($output) === '') {
        return '';
    }

    $lines = preg_split('/\R/u', trim($output)) ?: [];
    foreach ($lines as $line) {
        $candidate = trim($line);
        if ($candidate !== '' && (is_file($candidate) || PHP_OS_FAMILY !== 'Windows')) {
            return $candidate;
        }
    }

    return '';
}
}

if (!function_exists('article_extract_pdf_text')) {
function article_extract_pdf_text(string $path): string
{
    if (!is_file($path)) {
        return '';
    }

    $binary = article_find_binary('pdftotext');
    if ($binary === '') {
        return '';
    }

    $command = escapeshellarg($binary) . ' -layout -enc UTF-8 ' . escapeshellarg($path) . ' -';
    $output = @shell_exec($command);
    if (!is_string($output) || trim($output) === '') {
        return '';
    }

    return trim((string) preg_replace('/[ \t]+/u', ' ', $output));
}
}
