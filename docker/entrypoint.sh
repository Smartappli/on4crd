#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/html"
CONFIG_FILE="$APP_DIR/config/config.php"

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-on4crd}"
DB_USER="${DB_USER:-on4crd}"
DB_PASS="${DB_PASS:-on4crd}"
APP_URL="${APP_URL:-http://localhost:8080}"
ADMIN_CALLSIGN="${ADMIN_CALLSIGN:-ON4CRD}"
ADMIN_NAME="${ADMIN_NAME:-Administrateur}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.test}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-ChangeMeNow!123}"

mkdir -p "$APP_DIR/config" "$APP_DIR/storage/cache" "$APP_DIR/storage/uploads"

cat > "$CONFIG_FILE" <<PHP
<?php
declare(strict_types=1);

return [
    'db' => [
        'dsn' => 'mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_NAME};charset=utf8mb4',
        'user' => '${DB_USER}',
        'pass' => '${DB_PASS}',
    ],
    'app' => [
        'site_name' => 'ON4CRD v3.6.1',
        'base_url' => '${APP_URL}',
        'default_locale' => 'fr',
        'supported_locales' => ['fr', 'en', 'de', 'nl'],
        'session_name' => 'on4crd_session',
        'allow_install' => false,
    ],
    'security' => [
        'csrf_key' => 'docker-local-key',
    ],
    'tracking' => [
        'matomo_url' => '',
        'matomo_site_id' => '',
    ],
    'social' => [
        'album_webhooks' => [],
    ],
    'translation' => [
        'provider' => 'none',
        'deepl_api_key' => '',
        'cache_ttl' => 604800,
    ],
    'radio_data' => [
        'cache_ttl' => 900,
        'noaa_scales_url' => 'https://services.swpc.noaa.gov/products/noaa-scales.json',
        'noaa_kp_url' => 'https://services.swpc.noaa.gov/products/noaa-planetary-k-index.json',
        'noaa_flux_url' => 'https://services.swpc.noaa.gov/json/solar-radio-flux.json',
        'noaa_alerts_url' => 'https://services.swpc.noaa.gov/products/alerts.json',
        'hamqth_dx_url' => 'https://www.hamqth.com/dxc_csv.php?limit=12',
        'satnogs_tle_url' => 'https://db.satnogs.org/api/tle/',
        'contest_rss_url' => 'https://www.contestcalendar.com/weeklycont.php/calendar.rss',
    ],
    'chatbot' => [
        'provider' => 'local',
        'external_api_url' => '',
        'external_api_key' => '',
    ],
];
PHP

until mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" --silent; do
  echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
  sleep 2
done

php "$APP_DIR/docker/auto_install.php" \
    --db-host="$DB_HOST" \
    --db-port="$DB_PORT" \
    --db-name="$DB_NAME" \
    --db-user="$DB_USER" \
    --db-pass="$DB_PASS" \
    --admin-callsign="$ADMIN_CALLSIGN" \
    --admin-name="$ADMIN_NAME" \
    --admin-email="$ADMIN_EMAIL" \
    --admin-password="$ADMIN_PASSWORD"

exec "$@"
