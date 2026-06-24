<?php
declare(strict_types=1);

function ensure_storage_htaccess(string $directory, string $rules): void
{
    $file = rtrim($directory, '/') . '/.htaccess';
    if (!is_file($file)) {
        file_put_contents($file, $rules);
    }
}

if (!function_exists('safe_storage_public_path')) {
function safe_storage_public_path(string $path, array $allowedPrefixes = ['storage/press/']): string
{
    $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');
    if ($normalized === '' || str_contains($normalized, "\0") || str_contains($normalized, '..')) {
        throw new RuntimeException(i18n_error_text('storage_path_invalid', 'Invalid storage path.'));
    }

    foreach ($allowedPrefixes as $prefix) {
        $prefix = ltrim(str_replace('\\', '/', trim($prefix)), '/');
        if ($prefix !== '' && str_starts_with($normalized, $prefix)) {
            return $normalized;
        }
    }

    throw new RuntimeException(i18n_error_text('storage_path_forbidden', 'Storage path is not allowed.'));
}
}

if (!function_exists('safe_storage_public_path_or_null')) {
function safe_storage_public_path_or_null(string $path, array $allowedPrefixes = ['storage/press/']): ?string
{
    try {
        return safe_storage_public_path($path, $allowedPrefixes);
    } catch (Throwable) {
        return null;
    }
}
}

if (!function_exists('storage_document_allowed_prefixes')) {
/**
 * @return list<string>
 */
function storage_document_allowed_prefixes(): array
{
    return [
        'storage/private/library/',
        'storage/private/member_modules/',
        // Legacy paths remain readable only through authorized controllers.
        'storage/uploads/library/',
        'storage/uploads/member_modules/',
    ];
}
}

if (!function_exists('safe_storage_document_path')) {
function safe_storage_document_path(string $path, array $allowedPrefixes = []): string
{
    return safe_storage_public_path($path, $allowedPrefixes !== [] ? $allowedPrefixes : storage_document_allowed_prefixes());
}
}

if (!function_exists('safe_storage_document_path_or_null')) {
function safe_storage_document_path_or_null(string $path, array $allowedPrefixes = []): ?string
{
    try {
        return safe_storage_document_path($path, $allowedPrefixes);
    } catch (Throwable) {
        return null;
    }
}
}

if (!function_exists('storage_document_absolute_path')) {
function storage_document_absolute_path(string $safePath): string
{
    return dirname(__DIR__) . '/' . safe_storage_document_path($safePath);
}
}

function detect_uploaded_mime_type(string $tmpPath): string
{
    if (!is_file($tmpPath)) {
        return '';
    }
    $mime = '';

    if (function_exists('finfo_open') && function_exists('finfo_file')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mime = strtolower(trim((string) finfo_file($finfo, $tmpPath)));
            if ($mime !== '' && $mime !== 'application/octet-stream') {
                return $mime;
            }
        }
    }

    if (function_exists('mime_content_type')) {
        $detected = strtolower(trim((string) @mime_content_type($tmpPath)));
        if ($detected !== '' && $detected !== 'application/octet-stream') {
            return $detected;
        }
        if ($mime === '') {
            $mime = $detected;
        }
    }

    $fallback = detect_uploaded_mime_type_from_content($tmpPath);
    if ($fallback !== '') {
        return $fallback;
    }

    return $mime;
}

function detect_uploaded_mime_type_from_content(string $tmpPath): string
{
    $imageInfo = @getimagesize($tmpPath);
    if (is_array($imageInfo)) {
        $imageMime = strtolower(trim((string) ($imageInfo['mime'] ?? '')));
        if (in_array($imageMime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return $imageMime;
        }
    }

    $signature = @file_get_contents($tmpPath, false, null, 0, 64);
    if ($signature === false || $signature === '') {
        return '';
    }

    if (str_starts_with($signature, '%PDF-')) {
        return 'application/pdf';
    }
    if (str_starts_with($signature, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")) {
        return 'application/msword';
    }
    if (str_starts_with($signature, "\xFF\xD8\xFF")) {
        return 'image/jpeg';
    }
    if (str_starts_with($signature, "\x89PNG\r\n\x1A\n")) {
        return 'image/png';
    }
    if (str_starts_with($signature, 'RIFF') && str_contains(substr($signature, 8, 16), 'WEBP')) {
        return 'image/webp';
    }
    if (str_starts_with($signature, "PK\x03\x04")) {
        return 'application/zip';
    }
    if (!str_contains($signature, "\0") && preg_match('//u', $signature) === 1) {
        return 'text/plain';
    }

    return '';
}

function upload_i18n_message(string $key): string
{
    $locale = current_locale();
    $messages = i18n_domain_messages('upload_messages');

    return (string) ($messages[$locale][$key] ?? $messages['en'][$key] ?? $messages['fr'][$key] ?? '');
}

function upload_error_message(int $errorCode): string
{
    return match ($errorCode) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => upload_i18n_message('file_too_large_or_empty'),
        default => upload_i18n_message('upload_failed'),
    };
}

function assert_upload_file_is_valid_signature(string $tmpPath, array $allowedExtensions): void
{
    $signature = @file_get_contents($tmpPath, false, null, 0, 16);
    if ($signature === false) {
        throw new RuntimeException(upload_i18n_message('uploaded_unreadable'));
    }

    $known = [
        'pdf' => '%PDF-',
        'jpg' => "\xFF\xD8\xFF",
        'jpeg' => "\xFF\xD8\xFF",
        'png' => "\x89PNG\r\n\x1A\n",
        'webp' => 'RIFF',
        'docx' => "PK\x03\x04",
        'doc' => "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1",
    ];

    $hasKnownSignature = false;
    foreach ($allowedExtensions as $extension) {
        $extension = strtolower((string) $extension);
        if (!isset($known[$extension])) {
            continue;
        }
        $hasKnownSignature = true;
        if (str_starts_with($signature, $known[$extension])) {
            if ($extension !== 'webp' || str_contains(substr($signature, 8), 'WEBP')) {
                return;
            }
        }
    }

    if (!$hasKnownSignature) {
        return;
    }

    throw new RuntimeException(upload_i18n_message('invalid_signature'));
}

function secure_move_uploaded_file(
    array $upload,
    string $destinationDirectory,
    string $prefix,
    array $allowedExtensions,
    array $allowedMimes,
    int $maxBytes,
    bool $anonymousFilename = false
): string {
    $errorCode = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($errorCode));
    }

    $tmpPath = (string) ($upload['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException(upload_i18n_message('upload_invalid'));
    }

    $size = (int) ($upload['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        throw new RuntimeException(upload_i18n_message('file_too_large_or_empty'));
    }

    $originalName = (string) ($upload['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException(upload_i18n_message('extension_not_allowed'));
    }

    $allowedMimesForExtension = $allowedMimes;
    if (isset($allowedMimes[$extension]) && is_array($allowedMimes[$extension])) {
        $allowedMimesForExtension = $allowedMimes[$extension];
    }

    $mime = detect_uploaded_mime_type($tmpPath);
    if (!in_array($mime, $allowedMimesForExtension, true)) {
        throw new RuntimeException(upload_i18n_message('mime_not_allowed'));
    }
    assert_upload_file_is_valid_signature($tmpPath, [$extension]);

    $sanitizedTmpPath = $tmpPath;
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $sanitizedTmpPath = sanitize_uploaded_image_file($tmpPath, $extension);
    }

    if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0755, true) && !is_dir($destinationDirectory)) {
        throw new RuntimeException(upload_i18n_message('cannot_create_destination_dir'));
    }

    if ($anonymousFilename) {
        $hashSource = random_bytes(32) . '|' . hash_file('sha256', $sanitizedTmpPath);
        $filename = hash('sha256', $hashSource) . '.' . $extension;
    } else {
        $filename = $prefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    }
    $destinationPath = rtrim($destinationDirectory, '/') . '/' . $filename;
    $moved = $sanitizedTmpPath === $tmpPath
        ? move_uploaded_file($tmpPath, $destinationPath)
        : rename($sanitizedTmpPath, $destinationPath);
    if (!$moved) {
        throw new RuntimeException(upload_i18n_message('cannot_move_uploaded_file'));
    }

    @chmod($destinationPath, 0640);
    return $filename;
}

function sanitize_uploaded_image_file(string $tmpPath, string $extension): string
{
    $raw = @file_get_contents($tmpPath);
    if ($raw === false) {
        throw new RuntimeException(upload_i18n_message('uploaded_image_unreadable'));
    }

    if (!function_exists('imagecreatefromstring')) {
        return $tmpPath;
    }

    $image = @imagecreatefromstring($raw);
    if ($image === false) {
        throw new RuntimeException(upload_i18n_message('uploaded_image_invalid'));
    }

    $outputPath = tempnam(sys_get_temp_dir(), 'on4crd-img-');
    if ($outputPath === false) {
        imagedestroy($image);
        throw new RuntimeException(upload_i18n_message('cannot_create_temp_file'));
    }

    $writeOk = match ($extension) {
        'jpg', 'jpeg' => imagejpeg($image, $outputPath, 90),
        'png' => imagepng($image, $outputPath, 6),
        'webp' => function_exists('imagewebp') ? imagewebp($image, $outputPath, 85) : false,
        default => false,
    };
    imagedestroy($image);

    if (!$writeOk) {
        @unlink($outputPath);
        throw new RuntimeException(upload_i18n_message('image_metadata_cleanup_failed'));
    }

    return $outputPath;
}
