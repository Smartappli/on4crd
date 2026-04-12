<?php
declare(strict_types=1);

/**
 * Stubs for global helper functions that may be provided at runtime.
 * Used by static analysis to resolve symbols referenced by page controllers.
 */

if (!function_exists('db')) { function db(): mixed { return null; } }
if (!function_exists('current_user')) { function current_user(): ?array { return null; } }
if (!function_exists('require_login')) { function require_login(): array { return []; } }
if (!function_exists('require_permission')) { function require_permission(string $permission): void {} }
if (!function_exists('has_permission')) { function has_permission(string $permission): bool { return false; } }
if (!function_exists('set_flash')) { function set_flash(string $type, string $message): void {} }
if (!function_exists('redirect')) { function redirect(string $route): void {} }
if (!function_exists('redirect_url')) { function redirect_url(string $url): void {} }
if (!function_exists('table_exists')) { function table_exists(string $table): bool { return false; } }
if (!function_exists('render_layout')) { function render_layout(string $content, string $title = ''): string { return $content; } }
if (!function_exists('base_url')) { function base_url(string $path = ''): string { return $path; } }
if (!function_exists('route_url')) { function route_url(string $route, array $query = []): string { return $route; } }
if (!function_exists('module_enabled')) { function module_enabled(string $module): bool { return false; } }
if (!function_exists('set_page_meta')) { function set_page_meta(string $title = '', string $description = ''): void {} }

if (!function_exists('localized_article_row')) { function localized_article_row(array $row): array { return $row; } }
if (!function_exists('answer_question_from_knowledge')) { function answer_question_from_knowledge(string $question): string { return ''; } }
if (!function_exists('committee_members')) { function committee_members(): array { return []; } }
if (!function_exists('editorial_text')) { function editorial_text(string $key, string $default = ''): string { return $default; } }
if (!function_exists('asset_url')) { function asset_url(string $path): string { return $path; } }
if (!function_exists('placeholder_avatar')) { function placeholder_avatar(string $name = ''): string { return ''; } }
if (!function_exists('widget_catalog')) { function widget_catalog(): array { return []; } }
if (!function_exists('render_widget')) { function render_widget(string $slug, array $options = []): string { return ''; } }
if (!function_exists('render_robots_txt')) { function render_robots_txt(): string { return ''; } }
if (!function_exists('render_sitemap_xml')) { function render_sitemap_xml(): string { return ''; } }

if (!function_exists('format_price_eur')) { function format_price_eur(int $amountCents): string { return ''; } }
if (!function_exists('format_integer_or_unlimited')) { function format_integer_or_unlimited(?int $value): string { return ''; } }
if (!function_exists('parse_price_to_cents')) { function parse_price_to_cents(string $price): int { return 0; } }

if (!function_exists('auction_status_label')) { function auction_status_label(string $status): string { return $status; } }
if (!function_exists('ad_status_label')) { function ad_status_label(string $status): string { return $status; } }
if (!function_exists('ad_format_label')) { function ad_format_label(string $format): string { return $format; } }
if (!function_exists('ad_runtime_status')) { function ad_runtime_status(array $ad): string { return (string) ($ad['status'] ?? ''); } }
if (!function_exists('ad_format_catalog')) { function ad_format_catalog(): array { return []; } }
if (!function_exists('available_ad_placements')) { function available_ad_placements(): array { return []; } }
if (!function_exists('ad_placements_for_member')) { function ad_placements_for_member(int $memberId): array { return []; } }
if (!function_exists('member_ads')) { function member_ads(int $memberId): array { return []; } }
if (!function_exists('ad_fetch_by_id')) { function ad_fetch_by_id(int $adId): ?array { return null; } }
if (!function_exists('ad_daily_stats')) { function ad_daily_stats(int $adId, int $days = 30): array { return []; } }

if (!function_exists('shop_public_products')) { function shop_public_products(?string $category = null): array { return []; } }
if (!function_exists('shop_categories')) { function shop_categories(): array { return []; } }
if (!function_exists('shop_cart_state')) { function shop_cart_state(): array { return ['items' => [], 'total_cents' => 0]; } }
if (!function_exists('shop_cart_add')) { function shop_cart_add(int $productId, int $quantity = 1): void {} }
if (!function_exists('shop_cart_update')) { function shop_cart_update(int $productId, int $quantity): void {} }
if (!function_exists('shop_cart_remove')) { function shop_cart_remove(int $productId): void {} }
if (!function_exists('shop_cart_clear')) { function shop_cart_clear(): void {} }
if (!function_exists('shop_order_status_label')) { function shop_order_status_label(string $status): string { return $status; } }
if (!function_exists('shop_product_by_slug')) { function shop_product_by_slug(string $slug): ?array { return null; } }
if (!function_exists('shop_status_label')) { function shop_status_label(string $status): string { return $status; } }

if (!function_exists('import_adif_records')) { function import_adif_records(int $memberId, array $records): int { return 0; } }
if (!function_exists('create_qsl_cards_from_qsos')) { function create_qsl_cards_from_qsos(int $memberId, array $qsoIds): int { return 0; } }
if (!function_exists('build_qsl_svg_payload')) { function build_qsl_svg_payload(array $user, array $data, string $comment = ''): array { return []; } }
if (!function_exists('qsl_card_title')) { function qsl_card_title(array $payload): string { return ''; } }
if (!function_exists('qsl_format_display_date')) { function qsl_format_display_date(string $value): string { return $value; } }
if (!function_exists('qsl_format_display_time')) { function qsl_format_display_time(string $value): string { return $value; } }
