#!/usr/bin/env php
<?php

if ($argc < 3) {
    fwrite(STDERR, "Usage: {$argv[0]} <before.json> <after.json>\n");
    exit(1);
}

$before = json_decode(file_get_contents($argv[1]), true);
$after = json_decode(file_get_contents($argv[2]), true);

if (! $before || ! $after) {
    fwrite(STDERR, "Failed to parse one or both files\n");
    exit(1);
}

$metrics = [
    'score' => ['categories', 'performance', 'score'],
    'FCP' => ['audits', 'first-contentful-paint', 'numericValue'],
    'LCP' => ['audits', 'largest-contentful-paint', 'numericValue'],
    'TBT' => ['audits', 'total-blocking-time', 'numericValue'],
    'CLS' => ['audits', 'cumulative-layout-shift', 'numericValue'],
    'TTI' => ['audits', 'interactive', 'numericValue'],
    'Speed Index' => ['audits', 'speed-index', 'numericValue'],
];

printf("%-15s %15s %15s %15s\n", 'Metric', 'Before', 'After', 'Delta');
printf("%s\n", str_repeat('-', 65));

foreach ($metrics as $label => $path) {
    $a = $before;
    $b = $after;
    foreach ($path as $key) {
        $a = $a[$key] ?? null;
        $b = $b[$key] ?? null;
    }

    if ($a === null || $b === null) {
        continue;
    }

    $delta = $b - $a;
    $arrow = $label === 'score' ? ($delta >= 0 ? '↑' : '↓') : ($delta <= 0 ? '↑' : '↓');

    printf("%-15s %15s %15s %14s%s\n",
        $label,
        number_format((float) $a, $label === 'score' ? 2 : 0),
        number_format((float) $b, $label === 'score' ? 2 : 0),
        number_format((float) $delta, $label === 'score' ? 2 : 0),
        $arrow
    );
}
