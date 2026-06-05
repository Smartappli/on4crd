<?php
declare(strict_types=1);

if (!function_exists('pagination_state')) {
/**
 * Normalize common pagination values for list pages.
 *
 * @return array{page:int, per_page:int, total_pages:int, offset:int}
 */
function pagination_state(int $totalItems, int $requestedPage, int $perPage): array
{
    $perPage = max(1, $perPage);
    $totalPages = max(1, (int) ceil(max(0, $totalItems) / $perPage));
    $page = min(max(1, $requestedPage), $totalPages);

    return [
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'offset' => ($page - 1) * $perPage,
    ];
}
}

if (!function_exists('env')) {
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null) {
        return $default;
    }
    return $value;
}
}

if (!function_exists('storage_path')) {
function storage_path(string $path = ''): string
{
    $base = dirname(__DIR__) . '/storage';
    if ($path === '') {
        return $base;
    }
    return $base . '/' . ltrim($path, '/');
}
}

if (!function_exists('site_contact_email')) {
function site_contact_email(): string
{
    $email = trim((string) config('privacy.controller_email', 'crdurnal@gmail.com'));
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : 'crdurnal@gmail.com';
}
}

if (!function_exists('llphant_embedding_generator')) {
function llphant_embedding_generator(): ?object
{
    return null;
}
}

if (!function_exists('llphant_embedding_vector')) {
/** @return list<float> */
function llphant_embedding_vector(string $text): array
{
    return [];
}
}

if (!function_exists('redirect_url')) {
function redirect_url(string $url): void
{
    header('Location: ' . $url, true, 302);
    exit;
}
}

if (!function_exists('redirect')) {
function redirect(string $route): void
{
    redirect_url(route_url($route));
}
}

if (!function_exists('set_flash')) {
function set_flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}
}

if (!function_exists('consume_flashes')) {
function consume_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    if (!is_array($flashes)) {
        $flashes = [];
    }
    unset($_SESSION['_flash']);

    return array_values(array_filter($flashes, static fn ($item): bool => is_array($item)));
}
}
