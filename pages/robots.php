<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$base = base_url();
$disallowRules = [
    '/index.php?route=admin',
    '/index.php?route=dashboard',
    '/index.php?route=profile',
    '/index.php?route=settings',
    '/index.php?route=qsl',
    '/storage/cache/',
    '/storage/uploads/',
];

$lines = ['User-agent: *', 'Allow: /'];
foreach ($disallowRules as $rule) {
    $lines[] = 'Disallow: ' . $rule;
}

$answerEngineAgents = ['GPTBot', 'ChatGPT-User', 'OAI-SearchBot', 'PerplexityBot', 'ClaudeBot', 'Claude-SearchBot', 'CCBot'];
foreach ($answerEngineAgents as $agent) {
    $lines[] = '';
    $lines[] = 'User-agent: ' . $agent;
    $lines[] = 'Allow: /';
    foreach ($disallowRules as $rule) {
        $lines[] = 'Disallow: ' . $rule;
    }
}

$lines = array_merge($lines, [
    '',
    'Sitemap: ' . route_url('sitemap.xml'),
    'LLMS: ' . route_url('llms.txt'),
    'AI-Index: ' . route_url('ai-index.json'),
    'Knowledge-Graph: ' . route_url('knowledge-graph.jsonld'),
    'Host: ' . (string) parse_url($base, PHP_URL_HOST),
]);

echo implode("\n", $lines) . "\n";
