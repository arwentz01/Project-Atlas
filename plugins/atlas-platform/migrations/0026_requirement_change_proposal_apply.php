<?php
declare(strict_types=1);

use Atlas\Platform\Core\Migrations\Migration;
use Atlas\Platform\Core\Migrations\MigrationContext;
use Atlas\Platform\Core\Migrations\SchemaInspector;

return new class implements Migration {
    public function id(): string { return '0026'; }
    public function description(): string { return 'Add applied metadata to requirement change proposals.'; }
    public function up(MigrationContext $c): void
    {
        $db = $c->database;
        $s = new SchemaInspector($db);
        $table = $db->prefix . 'atlas_requirement_change_proposals';
        $s->assertTable($table);
        if (! $this->columnExists($db, $table, 'applied_by')) {
            $s->execute("ALTER TABLE `{$table}` ADD COLUMN applied_by bigint unsigned NULL AFTER created_by", 'Add proposal applied by user');
        }
        if (! $this->columnExists($db, $table, 'applied_at')) {
            $s->execute("ALTER TABLE `{$table}` ADD COLUMN applied_at datetime NULL AFTER applied_by", 'Add proposal applied timestamp');
        }
        $s->assertColumn($table, 'applied_by', 'bigint unsigned', true, null);
        $s->assertColumn($table, 'applied_at', 'datetime', true, null);
    }
    private function columnExists(object $db, string $table, string $column): bool
    {
        return is_array($db->get_row($db->prepare("SHOW COLUMNS FROM `{$table}` WHERE Field = %s", $column), ARRAY_A));
    }
};
