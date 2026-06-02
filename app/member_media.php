<?php
declare(strict_types=1);

function generate_member_avatar_from_photo(string $photoPublicPath, int $memberId): ?string
{
    $sourcePath = dirname(__DIR__) . '/' . ltrim($photoPublicPath, '/');
    if (!is_file($sourcePath) || !extension_loaded('gd')) {
        return null;
    }

    $info = @getimagesize($sourcePath);
    $mime = (string) ($info['mime'] ?? '');
    $sourceImage = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($sourcePath),
        'image/png' => @imagecreatefrompng($sourcePath),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
        default => false,
    };
    if (!$sourceImage) {
        return null;
    }

    $sourceWidth = imagesx($sourceImage);
    $sourceHeight = imagesy($sourceImage);
    $side = min($sourceWidth, $sourceHeight);
    $srcX = (int) floor(($sourceWidth - $side) / 2);
    $srcY = (int) floor(($sourceHeight - $side) / 2);

    $avatar = imagecreatetruecolor(256, 256);
    imagealphablending($avatar, false);
    imagesavealpha($avatar, true);
    $transparent = imagecolorallocatealpha($avatar, 0, 0, 0, 127);
    imagefill($avatar, 0, 0, $transparent);
    imagecopyresampled($avatar, $sourceImage, 0, 0, $srcX, $srcY, 256, 256, $side, $side);
    imagedestroy($sourceImage);

    $targetDir = dirname(__DIR__) . '/storage/uploads/members/avatars';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        imagedestroy($avatar);
        return null;
    }
    $fileName = 'avatar_' . $memberId . '_' . date('YmdHis') . '.png';
    $targetPath = $targetDir . '/' . $fileName;
    $saved = imagepng($avatar, $targetPath, 8);
    imagedestroy($avatar);
    if (!$saved) {
        return null;
    }

    return 'storage/uploads/members/avatars/' . $fileName;
}


function member_default_avatar_data_uri(string $label = ''): string
{
    $trimmed = trim($label);
    $initial = strtoupper(function_exists('mb_substr') ? (string) mb_substr($trimmed, 0, 1, 'UTF-8') : substr($trimmed, 0, 1));
    if ($initial === '' || !preg_match('/[A-Z0-9]/', $initial)) {
        $initial = 'R';
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256" viewBox="0 0 256 256" role="img" aria-label="Avatar">'
        . '<defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#1d4ed8"/><stop offset="100%" stop-color="#0f172a"/></linearGradient></defs>'
        . '<rect width="256" height="256" rx="128" fill="url(#bg)"/>'
        . '<text x="50%" y="56%" text-anchor="middle" dominant-baseline="middle" font-family="Arial, Helvetica, sans-serif" font-size="112" font-weight="700" fill="#f8fafc">'
        . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8')
        . '</text></svg>';

    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

function member_avatar_src(array $member): string
{
    $avatarPath = trim((string) ($member['avatar_path'] ?? ''));
    if ($avatarPath !== '') {
        return asset_url($avatarPath);
    }

    $photoPath = trim((string) ($member['photo_path'] ?? ''));
    if ($photoPath !== '') {
        return asset_url($photoPath);
    }

    $label = (string) ($member['callsign'] ?? ($member['full_name'] ?? ''));

    return member_default_avatar_data_uri($label);
}
