<?php
declare(strict_types=1);

define('ATLAS_PLATFORM_DIR', dirname(__DIR__) . '/plugins/atlas-platform/');
define('ATLAS_PLATFORM_VERSION', 'test');
require ATLAS_PLATFORM_DIR . 'src/Autoloader.php';
Atlas\Platform\Autoloader::register();

$GLOBALS['atlas_test_options'] = [];
function add_option(string $name, mixed $value, string $deprecated = '', bool $autoload = true): bool { if (array_key_exists($name, $GLOBALS['atlas_test_options'])) { return false; } $GLOBALS['atlas_test_options'][$name] = $value; return true; }
function get_option(string $name, mixed $default = false): mixed { return $GLOBALS['atlas_test_options'][$name] ?? $default; }
function delete_option(string $name): bool { if (! array_key_exists($name, $GLOBALS['atlas_test_options'])) { return false; } unset($GLOBALS['atlas_test_options'][$name]); return true; }

use Atlas\Platform\Core\Container\Container;
use Atlas\Platform\Core\Container\ContainerException;
use Atlas\Platform\Core\Logging\Logger;
use Atlas\Platform\Core\Migrations\MigrationDiscovery;
use Atlas\Platform\Core\Migrations\MigrationLock;
use Atlas\Platform\Core\Modules\Module;
use Atlas\Platform\Core\Modules\ModuleRegistry;

final class MemoryLogger implements Logger { public array $entries = []; public function log(string $level, string $event, string $message, array $context = [], string $module = 'core', ?\Throwable $exception = null): void { $this->entries[] = $event; } public function recentErrors(int $limit = 20): array { return []; } }
final class Dependency {}
final class Consumer { public function __construct(public Dependency $dependency) {} }
final class CircularA { public function __construct(public CircularB $dependency) {} }
final class CircularB { public function __construct(public CircularA $dependency) {} }
final class TestModule implements Module { public function __construct(private string $name, private array $requires, private array &$order) {} public function slug(): string { return $this->name; } public function version(): string { return '1'; } public function dependencies(): array { return $this->requires; } public function register(Container $container): void {} public function boot(): void { $this->order[] = $this->name; } public function health(): array { return ['status' => 'ok']; } }
final class FailingModule implements Module { public function slug(): string { return 'failing'; } public function version(): string { return '1'; } public function dependencies(): array { return []; } public function register(Container $container): void { throw new RuntimeException('registration failure'); } public function boot(): void {} public function health(): array { return ['status' => 'failed']; } }

function expect(bool $condition, string $message): void { if (! $condition) { throw new RuntimeException($message); } echo "PASS: {$message}\n"; }
function throws(callable $callback, string $contains): bool { try { $callback(); } catch (\Throwable $exception) { return str_contains($exception->getMessage(), $contains); } return false; }

$container = new Container();
expect($container->get(Consumer::class)->dependency instanceof Dependency, 'container automatically resolves constructor dependencies');
$registered = new Dependency(); $container->instance('registered.instance', $registered);
expect($container->has('registered.instance') && $container->get('registered.instance') === $registered, 'container has detects registered instances');
$container->singleton(Dependency::class);
expect($container->has(Dependency::class), 'container has detects service bindings');
expect($container->get(Dependency::class) === $container->get(Dependency::class), 'singleton binding returns the same instance');
expect(throws(fn() => $container->get(CircularA::class), 'Circular dependency'), 'container reports circular dependencies');

$logger = new MemoryLogger(); $order = []; $registry = new ModuleRegistry($container, $logger);
$registry->add(new TestModule('dependent', ['base'], $order)); $registry->add(new TestModule('base', [], $order)); $registry->bootAll();
expect($order === ['base', 'dependent'], 'module dependencies boot in order');
expect(throws(fn() => $registry->add(new TestModule('base', [], $order)), 'Duplicate'), 'duplicate module slugs fail clearly');
$failedRegistry = new ModuleRegistry($container, $logger);
expect(throws(fn() => $failedRegistry->add(new FailingModule()), 'failed to register') && $failedRegistry->statuses()['failing'] === 'failed', 'module registration failures are recorded and surfaced');

$root = sys_get_temp_dir() . '/atlas-migration-test-' . bin2hex(random_bytes(4)); mkdir($root . '/migrations', 0777, true);
$template = static fn(string $id): string => '<?php return new class implements \\Atlas\\Platform\\Core\\Migrations\\Migration { public function id(): string { return "' . $id . '"; } public function description(): string { return "test"; } public function up(\\Atlas\\Platform\\Core\\Migrations\\MigrationContext $context): void {} };';
file_put_contents($root . '/migrations/0076_test.php', $template('0076')); file_put_contents($root . '/migrations/0077_after_limit.php', $template('0077'));
$inventory = (new MigrationDiscovery($root . '/migrations', $root))->discover();
expect(isset($inventory->migrations['0077']), 'migration discovery finds identifiers after 076');
file_put_contents($root . '/migrations/bad.php', '<?php return null;');
$inventory = (new MigrationDiscovery($root . '/migrations', $root))->discover();
expect(count($inventory->malformed) === 1 && $inventory->malformed[0]['path'] === 'migrations/bad.php', 'malformed migrations are reported with relative paths');
foreach (glob($root . '/migrations/*') ?: [] as $file) { unlink($file); } rmdir($root . '/migrations'); rmdir($root);

$lock = new MigrationLock(30); $owner = $lock->acquire();
expect(is_string($owner) && $lock->acquire() === null, 'migration lock prevents concurrent acquisition');
expect(! $lock->release('another-owner') && $lock->release($owner), 'migration lock can only be released by its owner');
$GLOBALS['atlas_test_options']['atlas_platform_migration_lock'] = ['owner' => 'stale', 'created' => time() - 60, 'expires' => time() - 1];
expect($lock->status()['state'] === 'stale' && $lock->clearStale(), 'stale migration locks are detectable and recoverable');

echo "All focused tests passed.\n";
