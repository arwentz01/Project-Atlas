#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$failures = [];
$checks = [
    'PHP 8.0+' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'PDO MySQL extension' => extension_loaded('pdo_mysql'),
    '.env file' => is_file(dirname(__DIR__) . '/.env'),
    'Application stylesheet' => is_file(dirname(__DIR__) . '/assets/app.css'),
    'Application JavaScript' => is_file(dirname(__DIR__) . '/assets/app.js'),
];

foreach ($checks as $label => $passed) {
    fwrite(STDOUT, sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $label));
    if (!$passed) $failures[] = $label;
}

try {
    $db = Database::connection();
    fwrite(STDOUT, "[PASS] Database connection\n");
    $missing = SystemStatus::missingTables($db);
    if ($missing) {
        fwrite(STDOUT, '[FAIL] Missing tables: ' . implode(', ', $missing) . "\n");
        $failures[] = 'Database migration';
    } else {
        fwrite(STDOUT, "[PASS] Database migration\n");
    }
} catch (Throwable $exception) {
    fwrite(STDOUT, '[FAIL] Database connection: ' . $exception->getMessage() . "\n");
    $failures[] = 'Database connection';
}

if ($failures) {
    fwrite(STDERR, "\nAtlas is not ready: " . implode(', ', $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "\nAtlas is ready for browser testing.\n");
