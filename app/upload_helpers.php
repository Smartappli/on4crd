<?php
declare(strict_types=1);

function ensure_storage_htaccess(string $directory, string $rules): void
{
    $file = rtrim($directory, '/') . '/.htaccess';
    if (!is_file($file)) {
        file_put_contents($file, $rules);
    }
}

function detect_uploaded_mime_type(string $tmpPath): string
{
    if (!is_file($tmpPath)) {
        return '';
    }
    if (!function_exists('finfo_open') || !function_exists('finfo_file') || !function_exists('finfo_close')) {
        return '';
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        return '';
    }
    $mime = (string) finfo_file($finfo, $tmpPath);
    finfo_close($finfo);

    return strtolower(trim($mime));
}

function upload_i18n_message(string $key): string
{
    $locale = current_locale();
    $messages = i18n_domain_messages('upload_messages');

    return (string) ($messages[$locale][$key] ?? $messages['en'][$key] ?? $messages['fr'][$key] ?? '');
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
    int $maxBytes
): string {
    $errorCode = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_i18n_message('upload_failed'));
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

    $filename = $prefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
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
