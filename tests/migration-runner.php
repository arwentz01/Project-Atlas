<?php
declare(strict_types=1);

define('ATLAS_PLATFORM_DIR', dirname(__DIR__) . '/plugins/atlas-platform/');
require ATLAS_PLATFORM_DIR . 'src/Autoloader.php';
Atlas\Platform\Autoloader::register();

$GLOBALS['atlas_test_options'] = [];
$GLOBALS['atlas_migration_calls'] = [];
function update_option(string $name, mixed $value, bool $autoload = true): bool { $GLOBALS['atlas_test_options'][$name] = $value; return true; }

use Atlas\Platform\Core\Logging\Logger;
use Atlas\Platform\Core\Migrations\Lock;
use Atlas\Platform\Core\Migrations\Migration;
use Atlas\Platform\Core\Migrations\MigrationDiscovery;
use Atlas\Platform\Core\Migrations\MigrationRunner;
use Atlas\Platform\Core\Migrations\MigrationStore;

final class RunnerLogger implements Logger
{
    /** @var list<array{event: string, context: array<string, mixed>}> */
    public array $entries = [];
    public function log(string $level, string $event, string $message, array $context = [], string $module = 'core', ?Throwable $exception = null): void { $this->entries[] = ['event' => $event, 'context' => $context]; }
    public function recentErrors(int $limit = 20): array { return []; }
}
final class MemoryMigrationStore implements MigrationStore
{
    /** @var list<string> */ public array $completed = [];
    public int $ensures = 0;
    public function ensureLedger(): void { $this->ensures++; }
    public function tableExists(): bool { return true; }
    public function completedIds(): array { return $this->completed; }
    public function record(Migration $migration, int $elapsedMs): void { $this->completed[] = $migration->id(); }
}
final class MemoryLock implements Lock
{
    public bool $available = true;
    public bool $held = false;
    public int $releases = 0;
    public function acquire(): ?string { if (! $this->available || $this->held) { return null; } $this->held = true; return 'owner'; }
    public function release(string $token): bool { if (! $this->held || $token !== 'owner') { return false; } $this->held = false; $this->releases++; return true; }
    public function status(): array { return ['state' => $this->held ? 'active' : 'unlocked']; }
    public function clearStale(): bool { return false; }
}
final class FakeDatabase { public string $last_error = ''; }

function assert_runner(bool $condition, string $message): void { if (! $condition) { throw new RuntimeException($message); } echo "PASS: {$message}\n"; }
function migration_directory(array $ids): string
{
    $root = sys_get_temp_dir() . '/atlas-runner-' . bin2hex(random_bytes(4)); mkdir($root . '/migrations', 0777, true);
    foreach ($ids as $id) {
        $source = '<?php return new class implements \\Atlas\\Platform\\Core\\Migrations\\Migration { public function id(): string { return "' . $id . '"; } public function description(): string { return "migration ' . $id . '"; } public function up(\\Atlas\\Platform\\Core\\Migrations\\MigrationContext $context): void { $GLOBALS["atlas_migration_calls"][] = "' . $id . '"; if (($GLOBALS["atlas_fail_migration"] ?? "") === "' . $id . '") { throw new \\RuntimeException("unknown database failure"); } } };';
        file_put_contents($root . '/migrations/' . $id . '_test.php', $source);
    }
    return $root;
}
function remove_migration_directory(string $root): void { foreach (glob($root . '/migrations/*') ?: [] as $file) { unlink($file); } rmdir($root . '/migrations'); rmdir($root); }

$root = migration_directory(['0001', '0002']); $store = new MemoryMigrationStore(); $lock = new MemoryLock(); $logger = new RunnerLogger();
$runner = new MigrationRunner(new MigrationDiscovery($root . '/migrations', $root), $store, $lock, $logger, new FakeDatabase());
assert_runner($runner->runPending() === ['0001', '0002'], 'pending migrations complete in deterministic order');
assert_runner($store->completed === ['0001', '0002'], 'successful migrations are recorded after execution');
assert_runner($runner->runPending() === [] && $GLOBALS['atlas_migration_calls'] === ['0001', '0002'], 'a second run does not execute completed migrations');
assert_runner($lock->releases === 2 && ! $lock->held, 'the migration lock is released after successful runs');
remove_migration_directory($root);

$GLOBALS['atlas_migration_calls'] = []; $GLOBALS['atlas_fail_migration'] = '0001';
$root = migration_directory(['0001']); $store = new MemoryMigrationStore(); $lock = new MemoryLock(); $logger = new RunnerLogger();
$runner = new MigrationRunner(new MigrationDiscovery($root . '/migrations', $root), $store, $lock, $logger, new FakeDatabase());
try { $runner->runPending(); throw new RuntimeException('Expected the failing migration to throw.'); } catch (RuntimeException $exception) { assert_runner(str_contains($exception->getMessage(), 'unknown database failure'), 'unknown migration errors are surfaced'); }
assert_runner($store->completed === [], 'failed migrations are never marked complete');
assert_runner($lock->releases === 1 && ! $lock->held, 'the migration lock is released after failure');
assert_runner(in_array('migration.failed', array_column($logger->entries, 'event'), true), 'migration failures are logged');
unset($GLOBALS['atlas_fail_migration']);
assert_runner($runner->runPending() === ['0001'] && $store->completed === ['0001'], 'a failed migration resumes safely on retry');
remove_migration_directory($root);

$GLOBALS['atlas_migration_calls'] = [];
$root = migration_directory(['0001', '0002']); $store = new MemoryMigrationStore(); $store->completed = ['0001'];
$runner = new MigrationRunner(new MigrationDiscovery($root . '/migrations', $root), $store, new MemoryLock(), new RunnerLogger(), new FakeDatabase());
assert_runner($runner->runPending() === ['0002'] && $GLOBALS['atlas_migration_calls'] === ['0002'], 'a partially completed inventory resumes at the first pending migration');
remove_migration_directory($root);

$root = migration_directory(['0001']); $lock = new MemoryLock(); $lock->available = false;
$runner = new MigrationRunner(new MigrationDiscovery($root . '/migrations', $root), new MemoryMigrationStore(), $lock, new RunnerLogger(), new FakeDatabase());
try { $runner->runPending(); throw new RuntimeException('Expected lock contention to throw.'); } catch (RuntimeException $exception) { assert_runner(str_contains($exception->getMessage(), 'holds the lock'), 'concurrent migration execution is rejected'); }
remove_migration_directory($root);

echo "All migration runner tests passed.\n";
