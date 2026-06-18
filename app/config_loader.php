<?php
declare(strict_types=1);

function app_config_file_path(): string
{
    $override = trim((string) getenv('ON4CRD_CONFIG_FILE'));
    if ($override !== '') {
        return $override;
    }

    return __DIR__ . '/../config/config.php';
}

