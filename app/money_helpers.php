<?php
declare(strict_types=1);

function parse_price_to_cents(string $price): int
{
    $normalized = str_replace([' ', "\t", "\r", "\n", "\xc2\xa0", "'"], '', trim($price));
    $normalized = preg_replace('/[^0-9,.\-]/', '', $normalized) ?? '';
    if ($normalized === '' || str_contains($normalized, '-')) {
        return 0;
    }

    $lastComma = strrpos($normalized, ',');
    $lastDot = strrpos($normalized, '.');
    $decimalSeparator = null;
    if ($lastComma !== false || $lastDot !== false) {
        $decimalSeparator = (int) $lastComma > (int) $lastDot ? ',' : '.';
    }

    $integerPart = $normalized;
    $decimalPart = '';
    if ($decimalSeparator !== null) {
        $lastSeparator = strrpos($normalized, $decimalSeparator);
        if ($lastSeparator !== false) {
            $tail = substr($normalized, $lastSeparator + 1);
            $otherSeparator = $decimalSeparator === ',' ? '.' : ',';
            $hasOtherSeparator = str_contains($normalized, $otherSeparator);
            $separatorCount = substr_count($normalized, $decimalSeparator);
            $tailIsDecimal = $tail !== '' && strlen($tail) <= 2;
            if (!$hasOtherSeparator && $separatorCount === 1 && strlen($tail) === 3) {
                $tailIsDecimal = false;
            }

            if ($tailIsDecimal) {
                $integerPart = substr($normalized, 0, $lastSeparator);
                $decimalPart = $tail;
            }
        }
    }

    $integerDigits = preg_replace('/\D/', '', $integerPart) ?? '';
    $decimalDigits = preg_replace('/\D/', '', $decimalPart) ?? '';
    if ($integerDigits === '' && $decimalDigits === '') {
        return 0;
    }

    $cents = ((int) ($integerDigits !== '' ? $integerDigits : '0')) * 100;
    if ($decimalDigits !== '') {
        $cents += (int) str_pad(substr($decimalDigits, 0, 2), 2, '0');
    }

    return max(0, $cents);
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
