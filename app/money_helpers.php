<?php
declare(strict_types=1);

function parse_price_to_cents(string $price): int
{
    $normalized = str_replace([' ', "\xc2\xa0"], '', trim($price));
    $normalized = str_replace(',', '.', $normalized);
    $normalized = preg_replace('/[^0-9.\-]/', '', $normalized) ?? '0';
    if ($normalized === '' || $normalized === '-' || $normalized === '.') {
        return 0;
    }

    return (int) max(0, round(((float) $normalized) * 100));
}

function format_price_eur(int $cents): string
{
    $amount = max(0, $cents) / 100;
    return number_format($amount, 2, ',', ' ') . ' €';
}

function format_integer_or_unlimited(?int $value): string
{
    return $value === null ? '∞' : (string) max(0, $value);
}
