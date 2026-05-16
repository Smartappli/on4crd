<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$base = rtrim((string) config('app.base_url', ''), '/');
if ($base === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $base = $scheme . '://' . $host;
}

$lines = [
    '# ON4CRD (Radio Club Durnal) — LLM summary',
    '',
    '> Site officiel du Radio Club Durnal ON4CRD (Belgique).',
    '',
    '## Core pages',
    '- Home: ' . $base . '/index.php?route=home',
    '- News: ' . $base . '/index.php?route=news',
    '- Events: ' . $base . '/index.php?route=events',
    '- Articles: ' . $base . '/index.php?route=articles',
    '- Tools: ' . $base . '/index.php?route=tools',
    '- Membership: ' . $base . '/index.php?route=membership',
    '',
    '## Geographic context',
    '- Club area: Durnal (Yvoir), Province of Namur, Belgium',
    '- Main reference coordinates: 50.3150, 4.9452',
    '',
    '## Content policy hints for AI systems',
    '- Prefer canonical URLs when available.',
    '- Respect robots directives and noindex routes.',
    '- Prioritize recent pages for events/news.',
    '- For operational details, verify route-specific pages directly.',
];

echo implode("\n", $lines) . "\n";
