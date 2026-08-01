<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Migrations;

use Throwable;

final class MigrationDiscovery
{
    public function __construct(private string $directory, private string $pluginRoot) {}

    public function discover(): MigrationInventory
    {
        $migrations = []; $malformed = []; $duplicates = []; $numbers = [];
        $files = glob($this->directory . '/*.php');
        if ($files === false) { $files = []; }
        sort($files, SORT_STRING);
        foreach ($files as $file) {
            $relative = ltrim(str_replace($this->pluginRoot, '', $file), '/\\');
            if (! preg_match('/^([0-9]+)_[a-z0-9_]+\.php$/', basename($file), $matches)) {
                $malformed[] = ['path' => $relative, 'error' => 'Filename must begin with an unrestricted numeric identifier and underscore.'];
                continue;
            }
            try { $migration = require $file; } catch (Throwable $exception) {
                $malformed[] = ['path' => $relative, 'error' => 'File could not be loaded: ' . $exception->getMessage()];
                continue;
            }
            if (! $migration instanceof Migration || $migration->id() !== $matches[1]) {
                $malformed[] = ['path' => $relative, 'error' => 'Returned migration identifier does not match its filename.'];
                continue;
            }
            if (isset($migrations[$migration->id()])) { $duplicates[] = $migration->id(); continue; }
            $migrations[$migration->id()] = $migration;
            $numbers[] = (int) $migration->id();
        }
        uksort($migrations, static fn(string $a, string $b): int => strnatcmp($a, $b));
        $gaps = [];
        if ($numbers !== []) {
            sort($numbers, SORT_NUMERIC);
            for ($number = $numbers[0]; $number <= $numbers[count($numbers) - 1]; $number++) {
                if (! in_array($number, $numbers, true)) { $gaps[] = (string) $number; }
            }
        }
        return new MigrationInventory($migrations, array_values($malformed), array_values(array_unique($duplicates)), $gaps);
    }
}
