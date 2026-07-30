<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$plugin = $root . '/plugin/atlas-platform';
$manifestPath = $plugin . '/release-manifest.json';
$failures = [];

$assert = static function (bool $condition, string $message) use (&$failures): void {
    echo ($condition ? 'PASS: ' : 'FAIL: ') . $message . PHP_EOL;
    if (! $condition) {
        $failures[] = $message;
    }
};

$manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
$assert(($manifest['version'] ?? '') === '0.57.0', 'release manifest matches plugin version 0.57.0');
$required = is_array($manifest['required'] ?? null) ? $manifest['required'] : [];
$assert(count($required) === count(array_unique($required)), 'release manifest has no duplicate paths');
foreach ($required as $relative) {
    $assert(is_string($relative) && is_file($plugin . '/' . $relative), 'release contains ' . (string) $relative);
}

$migrationFiles = glob($plugin . '/migrations/*.php') ?: [];
$migrationNames = array_map('basename', $migrationFiles);
sort($migrationNames, SORT_STRING);
$manifestMigrations = array_values(array_map(
    static fn(string $path): string => basename($path),
    array_filter($required, static fn(mixed $path): bool => is_string($path) && str_starts_with($path, 'migrations/'))
));
sort($manifestMigrations, SORT_STRING);
$assert($migrationNames === $manifestMigrations, 'release manifest contains every deployed migration and no phantom migration');

$main = (string) file_get_contents($plugin . '/atlas-platform.php');
preg_match('/Version:\s*([0-9.]+)/', $main, $headerVersion);
preg_match("/ATLAS_PLATFORM_VERSION',\s*'([0-9.]+)'/", $main, $constantVersion);
$assert(($headerVersion[1] ?? '') === ($manifest['version'] ?? ''), 'plugin header version matches release manifest');
$assert(($constantVersion[1] ?? '') === ($manifest['version'] ?? ''), 'runtime version constant matches release manifest');

if ($failures !== []) {
    fwrite(STDERR, count($failures) . " release gate assertion(s) failed.\n");
    exit(1);
}

echo "All release gate checks passed.\n";
