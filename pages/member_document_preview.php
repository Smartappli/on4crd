<?php
declare(strict_types=1);

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed');
}

if (!ensure_member_module_documents_table()) {
    http_response_code(404);
    exit('Document not found');
}

$moduleCode = member_document_module_normalize((string) ($_GET['module'] ?? ''));
$documentId = (int) ($_GET['id'] ?? 0);
$definition = $moduleCode !== '' ? member_document_module_definition($moduleCode) : null;
if ($moduleCode === '' || $definition === null || $documentId <= 0) {
    http_response_code(404);
    exit('Document not found');
}
require_module_enabled($moduleCode, (string) ($definition['route'] ?? $moduleCode));

$stmt = db()->prepare('SELECT title, file_path FROM member_module_documents WHERE id = ? AND module_code = ? LIMIT 1');
$stmt->execute([$documentId, $moduleCode]);
$document = $stmt->fetch() ?: null;
if (!is_array($document)) {
    http_response_code(404);
    exit('Document not found');
}

$safePath = member_document_safe_path((string) ($document['file_path'] ?? ''));
$extension = strtolower(pathinfo((string) $safePath, PATHINFO_EXTENSION));
$allowedExtensions = ['pdf', 'docx', 'txt', 'md', 'html', 'htm', 'ppt', 'pptx', 'xls', 'xlsx', 'csv', 'zip', 'jpg', 'jpeg', 'png', 'webp', 'mp4', 'webm', 'mov', 'm4v'];
$isDownload = (string) ($_GET['download'] ?? '') === '1';
$canRenderInline = $extension === 'pdf' || ($moduleCode === 'videos' && member_document_is_video_extension($extension));
if ($safePath === null || !in_array($extension, $allowedExtensions, true) || (!$isDownload && !$canRenderInline)) {
    http_response_code(404);
    exit('Document not found');
}

$absolutePath = storage_document_absolute_path($safePath);
if (!is_file($absolutePath) || !is_readable($absolutePath)) {
    http_response_code(404);
    exit('Document not found');
}

$size = filesize($absolutePath);
if ($size === false || $size < 1) {
    http_response_code(404);
    exit('Document not found');
}

$filename = str_replace(['"', "\r", "\n"], '', basename($safePath));
if ($filename === '') {
    $filename = 'document.' . ($extension !== '' ? $extension : 'bin');
}

session_write_close();
while (ob_get_level() > 0) {
    ob_end_clean();
}

$start = 0;
$end = $size - 1;
$statusCode = 200;
$rangeHeader = trim((string) ($_SERVER['HTTP_RANGE'] ?? ''));
if (preg_match('/^bytes=(\d*)-(\d*)$/', $rangeHeader, $matches) === 1) {
    $rangeStart = (string) $matches[1];
    $rangeEnd = (string) $matches[2];
    if ($rangeStart !== '' || $rangeEnd !== '') {
        if ($rangeStart === '') {
            $suffixLength = max(0, (int) $rangeEnd);
            $start = max(0, $size - $suffixLength);
        } else {
            $start = (int) $rangeStart;
        }
        if ($rangeEnd !== '' && $rangeStart !== '') {
            $end = min((int) $rangeEnd, $size - 1);
        }
        if ($start > $end || $start >= $size) {
            http_response_code(416);
            header('Content-Range: bytes */' . $size);
            exit;
        }
        $statusCode = 206;
    }
}

$contentTypes = [
    'pdf' => 'application/pdf',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt' => 'text/plain; charset=utf-8',
    'md' => 'text/plain; charset=utf-8',
    'html' => 'application/octet-stream',
    'htm' => 'application/octet-stream',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'csv' => 'text/csv; charset=utf-8',
    'zip' => 'application/zip',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'mov' => 'video/quicktime',
    'm4v' => 'video/x-m4v',
];
$length = $end - $start + 1;
$disposition = $isDownload ? 'attachment' : 'inline';
http_response_code($statusCode);
header('Content-Type: ' . ($contentTypes[$extension] ?? 'application/octet-stream'));
header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
header('Content-Length: ' . $length);
header('Accept-Ranges: bytes');
header('Cache-Control: private, max-age=600');
header('X-Content-Type-Options: nosniff');
if ($statusCode === 206) {
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
}

$handle = fopen($absolutePath, 'rb');
if ($handle === false) {
    http_response_code(404);
    exit;
}

fseek($handle, $start);
$remaining = $length;
while ($remaining > 0 && !feof($handle)) {
    $chunk = fread($handle, min(8192, $remaining));
    if ($chunk === false || $chunk === '') {
        break;
    }
    echo $chunk;
    $remaining -= strlen($chunk);
}
fclose($handle);
exit;
