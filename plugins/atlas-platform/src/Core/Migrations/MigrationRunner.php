<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Migrations;

use Atlas\Platform\Core\Logging\Logger;
use RuntimeException;
use Throwable;

final class MigrationRunner
{
    public function __construct(private MigrationDiscovery $discovery, private MigrationStore $repository, private Lock $lock, private Logger $logger, private object $database) {}

    /** @return array{completed: list<string>, pending: list<string>, latest: string|null, inventory: MigrationInventory} */
    public function status(): array
    {
        $inventory = $this->discovery->discover();
        $completed = $this->repository->completedIds();
        $pending = array_values(array_diff(array_keys($inventory->migrations), $completed));
        $ids = array_keys($inventory->migrations);
        return ['completed' => $completed, 'pending' => $pending, 'latest' => $ids === [] ? null : $ids[count($ids) - 1], 'inventory' => $inventory];
    }

    /** @return list<string> identifiers completed by this invocation */
    public function runPending(int $limit = 10): array
    {
        $inventory = $this->discovery->discover();
        if ($inventory->malformed !== [] || $inventory->duplicates !== [] || $inventory->gaps !== []) {
            throw new RuntimeException('Migration inventory is invalid; review Atlas Diagnostics.');
        }
        $token = $this->lock->acquire();
        if ($token === null) { throw new RuntimeException('Another Atlas migration runner holds the lock.'); }
        $ran = [];
        try {
            $this->repository->ensureLedger();
            $completed = $this->repository->completedIds();
            foreach ($inventory->migrations as $migration) {
                if (in_array($migration->id(), $completed, true) || count($ran) >= max(1, $limit)) { continue; }
                $started = microtime(true);
                $this->logger->log('info', 'migration.started', 'Migration started.', ['migration_id' => $migration->id(), 'class' => get_class($migration), 'description' => $migration->description(), 'step' => 1, 'total_steps' => 1], 'migrations');
                try {
                    $migration->up(new MigrationContext($this->database, $this->repository));
                    $elapsed = (int) round((microtime(true) - $started) * 1000);
                    $this->repository->record($migration, $elapsed);
                    update_option('atlas_platform_db_version', $migration->id(), false);
                    $ran[] = $migration->id();
                    $this->logger->log('info', 'migration.completed', 'Migration completed.', ['migration_id' => $migration->id(), 'elapsed_ms' => $elapsed], 'migrations');
                } catch (Throwable $exception) {
                    $this->logger->log('critical', 'migration.failed', 'Migration failed.', ['migration_id' => $migration->id(), 'class' => get_class($migration), 'description' => $migration->description(), 'step' => 1, 'total_steps' => 1, 'statement' => 0, 'sql_preview' => '[not available]', 'database_error' => substr((string) ($this->database->last_error ?? ''), 0, 500), 'elapsed_ms' => (int) round((microtime(true) - $started) * 1000)], 'migrations', $exception);
                    throw new RuntimeException(sprintf('Migration %s failed: %s', $migration->id(), $exception->getMessage()), 0, $exception);
                }
            }
            return $ran;
        } finally {
            $this->lock->release($token);
        }
    }
}
