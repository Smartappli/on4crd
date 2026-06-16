<?php
declare(strict_types=1);

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed');
}

if (!ensure_member_library_table()) {
    http_response_code(404);
    exit('Document not found');
}

$documentId = (int) ($_GET['id'] ?? 0);
if ($documentId <= 0) {
    http_response_code(404);
    exit('Document not found');
}

$stmt = db()->prepare('SELECT title, file_path FROM member_library_documents WHERE id = ? LIMIT 1');
$stmt->execute([$documentId]);
$document = $stmt->fetch() ?: null;
if (!is_array($document)) {
    http_response_code(404);
    exit('Document not found');
}

$safePath = safe_storage_public_path_or_null((string) ($document['file_path'] ?? ''), ['storage/uploads/library/']);
$extension = strtolower(pathinfo((string) $safePath, PATHINFO_EXTENSION));
$allowedExtensions = ['pdf', 'docx', 'txt', 'md', 'html', 'htm'];
$isDownload = (string) ($_GET['download'] ?? '') === '1';
if ($safePath === null || !in_array($extension, $allowedExtensions, true) || (!$isDownload && $extension !== 'pdf')) {
    http_response_code(404);
    exit('Document not found');
}

$absolutePath = dirname(__DIR__) . '/' . $safePath;
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
    $filename = 'document.pdf';
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

$length = $end - $start + 1;
$contentTypes = [
    'pdf' => 'application/pdf',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt' => 'text/plain; charset=utf-8',
    'md' => 'text/plain; charset=utf-8',
    'html' => 'application/octet-stream',
    'htm' => 'application/octet-stream',
];
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
