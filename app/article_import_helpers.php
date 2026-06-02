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

    $xml = '';
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($path) === true) {
            $documentXml = $zip->getFromName('word/document.xml');
            $zip->close();
            if (is_string($documentXml)) {
                $xml = $documentXml;
            }
        }
    } else {
        $unzip = article_find_binary('unzip');
        if ($unzip !== '') {
            $output = @shell_exec(escapeshellarg($unzip) . ' -p ' . escapeshellarg($path) . ' word/document.xml');
            if (is_string($output)) {
                $xml = $output;
            }
        }
    }

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
    $paragraphs = $xpath->query('//*[local-name()="p"]');
    if (!$paragraphs instanceof DOMNodeList) {
        return '';
    }

    $html = [];
    $listOpen = false;
    foreach ($paragraphs as $paragraph) {
        if (!$paragraph instanceof DOMElement) {
            continue;
        }

        $text = '';
        $runs = $xpath->query('.//*[local-name()="t" or local-name()="tab" or local-name()="br"]', $paragraph);
        if ($runs instanceof DOMNodeList) {
            foreach ($runs as $run) {
                if (!$run instanceof DOMNode) {
                    continue;
                }
                $localName = $run->localName;
                if ($localName === 'tab') {
                    $text .= ' ';
                } elseif ($localName === 'br') {
                    $text .= "\n";
                } else {
                    $text .= $run->textContent;
                }
            }
        }

        $text = trim((string) preg_replace('/[ \t]+/u', ' ', $text));
        if ($text === '') {
            if ($listOpen) {
                $html[] = '</ul>';
                $listOpen = false;
            }
            continue;
        }

        $style = '';
        $styleNodes = $xpath->query('.//*[local-name()="pStyle"]', $paragraph);
        $styleNode = $styleNodes instanceof DOMNodeList ? $styleNodes->item(0) : null;
        if ($styleNode instanceof DOMElement) {
            $style = strtolower($styleNode->getAttribute('w:val') ?: $styleNode->getAttribute('val'));
        }
        $numNodes = $xpath->query('.//*[local-name()="numPr"]', $paragraph);
        $isList = $numNodes instanceof DOMNodeList && $numNodes->length > 0;

        if ($isList) {
            if (!$listOpen) {
                $html[] = '<ul>';
                $listOpen = true;
            }
            $html[] = '<li>' . e($text) . '</li>';
            continue;
        }

        if ($listOpen) {
            $html[] = '</ul>';
            $listOpen = false;
        }

        if (str_contains($style, 'heading') || str_contains($style, 'titre')) {
            $level = preg_match('/([1-6])/', $style, $matches) ? (int) $matches[1] + 1 : 2;
            $level = min(4, max(2, $level));
            $html[] = '<h' . $level . '>' . e($text) . '</h' . $level . '>';
        } else {
            $html[] = '<p>' . nl2br(e($text)) . '</p>';
        }
    }

    if ($listOpen) {
        $html[] = '</ul>';
    }

    return sanitize_rich_html(implode("\n", $html));
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
