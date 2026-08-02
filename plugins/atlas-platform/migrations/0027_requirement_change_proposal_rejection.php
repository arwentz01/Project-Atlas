<?php
declare(strict_types=1);

use Atlas\Platform\Core\Migrations\Migration;
use Atlas\Platform\Core\Migrations\MigrationContext;
use Atlas\Platform\Core\Migrations\SchemaInspector;

return new class implements Migration {
    public function id(): string { return '0027'; }
    public function description(): string { return 'Add rejection metadata to requirement change proposals.'; }
    public function up(MigrationContext $c): void
    {
        $db = $c->database;
        $s = new SchemaInspector($db);
        $table = $db->prefix . 'atlas_requirement_change_proposals';
        $s->assertTable($table);
        if (! $this->columnExists($db, $table, 'rejection_note')) {
            $s->execute("ALTER TABLE `{$table}` ADD COLUMN rejection_note text NULL AFTER proposal_reason", 'Add proposal rejection note');
        }
        if (! $this->columnExists($db, $table, 'rejected_by')) {
            $s->execute("ALTER TABLE `{$table}` ADD COLUMN rejected_by bigint unsigned NULL AFTER applied_at", 'Add proposal rejected by user');
        }
        if (! $this->columnExists($db, $table, 'rejected_at')) {
            $s->execute("ALTER TABLE `{$table}` ADD COLUMN rejected_at datetime NULL AFTER rejected_by", 'Add proposal rejected timestamp');
        }
        $s->assertColumn($table, 'rejection_note', 'text', true, null);
        $s->assertColumn($table, 'rejected_by', 'bigint unsigned', true, null);
        $s->assertColumn($table, 'rejected_at', 'datetime', true, null);
    }
    private function columnExists(object $db, string $table, string $column): bool
    {
        return is_array($db->get_row($db->prepare("SHOW COLUMNS FROM `{$table}` WHERE Field = %s", $column), ARRAY_A));
    }
};
