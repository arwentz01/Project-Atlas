<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$php = PHP_BINARY;
$tests = [
    'run.php',
    'release-gate.php',
    'bootstrap-smoke.php',
    'wordpress-api-compatibility.php',
    'admin-navigation.php',
    'editorial.php',
    'editorial-queue.php',
    'migration-runner.php',
    'organization-context.php',
    'organization-onboarding.php',
    'organizations.php',
    'patient-resource-catalog.php',
    'patient-resources.php',
    'preview.php',
    'readiness.php',
    'resource-authoring.php',
    'resource-library.php',
    'resource-presentation.php',
    'resources.php',
    'schema-inspector.php',
    'source-workspace.php',
    'workflow-authoring.php',
    'workflow-catalog.php',
    'workflows.php',
];

$failures = [];

foreach ($tests as $test) {
    $path = __DIR__ . '/' . $test;
    if (! is_file($path)) {
        $failures[] = $test . ' is missing';
        continue;
    }

    echo PHP_EOL . '==> ' . $test . PHP_EOL;
    $command = escapeshellarg($php) . ' ' . escapeshellarg($path);
    passthru($command, $status);

    if ($status !== 0) {
        $failures[] = $test . ' exited with status ' . (string) $status;
    }
}

if ($failures !== []) {
    fwrite(STDERR, PHP_EOL . count($failures) . " standalone test failure(s):" . PHP_EOL);
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . PHP_EOL);
    }
    exit(1);
}

echo PHP_EOL . "All standalone Atlas tests passed." . PHP_EOL;
