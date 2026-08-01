<?php
declare(strict_types=1);

define('ATLAS_PLATFORM_DIR', dirname(__DIR__) . '/plugins/atlas-platform/');
define('ARRAY_A', 'ARRAY_A');
require ATLAS_PLATFORM_DIR . 'src/Autoloader.php';
Atlas\Platform\Autoloader::register();
function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }

use Atlas\Platform\Core\Migrations\SchemaInspector;

final class InspectorDatabase
{
    public string $collate = 'utf8mb4_unicode_ci';
    public string $last_error = '';
    /** @var array<string, mixed> */ public array $column = ['Type' => 'bigint(20) unsigned', 'Null' => 'NO', 'Default' => null];
    /** @var list<array<string, mixed>> */ public array $indexes = [['Seq_in_index' => 2, 'Column_name' => 'user_id', 'Non_unique' => 0], ['Seq_in_index' => 1, 'Column_name' => 'organization_id', 'Non_unique' => 0]];
    public function prepare(string $query, mixed ...$arguments): string { return $query; }
    public function esc_like(string $value): string { return $value; }
    public function get_var(string $query): string { return 'wp_atlas_test'; }
    public function get_row(string $query, string $format): array { return str_starts_with($query, 'SHOW TABLE STATUS') ? ['Engine' => 'InnoDB', 'Collation' => $this->collate] : $this->column; }
    public function get_results(string $query, string $format): array { return $this->indexes; }
    public function query(string $sql): int|false { return $this->last_error === '' ? 1 : false; }
}
function schema_expect(bool $condition, string $message): void { if (! $condition) { throw new RuntimeException($message); } echo "PASS: {$message}\n"; }
function schema_throws(callable $callback, string $message): void { try { $callback(); } catch (RuntimeException) { echo "PASS: {$message}\n"; return; } throw new RuntimeException($message); }

$database = new InspectorDatabase(); $schema = new SchemaInspector($database);
schema_expect($schema->tableExists('wp_atlas_test'), 'schema inspection detects an exact table name');
$schema->assertTable('wp_atlas_test'); schema_expect(true, 'schema inspection accepts expected engine and collation');
$schema->assertColumn('wp_atlas_test', 'user_id', 'bigint unsigned', false); schema_expect(true, 'schema inspection treats MySQL 5.7 integer display width as storage-equivalent');
$schema->assertIndex('wp_atlas_test', 'membership_unique', ['organization_id', 'user_id'], true); schema_expect(true, 'schema inspection verifies ordered unique index columns');
$database->column['Type'] = 'int(11) unsigned'; schema_throws(fn() => $schema->assertColumn('wp_atlas_test', 'user_id', 'bigint unsigned', false), 'schema inspection rejects an incompatible integer type');
$database->indexes = []; schema_throws(fn() => $schema->assertIndex('wp_atlas_test', 'membership_unique', ['organization_id', 'user_id'], true), 'schema inspection rejects a missing required index');
$database->last_error = 'unknown database error'; schema_throws(fn() => $schema->execute('ALTER TABLE test', 'Test mutation'), 'schema mutations surface unknown database errors');

echo "All schema inspector tests passed.\n";
