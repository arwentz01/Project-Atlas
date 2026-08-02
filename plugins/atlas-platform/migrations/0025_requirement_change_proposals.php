<?php
declare(strict_types=1);

use Atlas\Platform\Core\Migrations\Migration;
use Atlas\Platform\Core\Migrations\MigrationContext;
use Atlas\Platform\Core\Migrations\SchemaInspector;

return new class implements Migration {
    public function id(): string { return '0025'; }
    public function description(): string { return 'Add draft payer requirement change proposals.'; }
    public function up(MigrationContext $c): void
    {
        $db = $c->database;
        $s = new SchemaInspector($db);
        $table = $db->prefix . 'atlas_requirement_change_proposals';
        if (! $s->tableExists($table)) {
            $charset = $db->get_charset_collate();
            $s->execute("CREATE TABLE `{$table}` (
                id char(36) NOT NULL,
                requirement_id char(36) NOT NULL,
                organization_id char(36) NULL,
                source_document_id char(36) NULL,
                proposal_status varchar(40) NOT NULL DEFAULT 'draft',
                proposal_reason text NOT NULL,
                proposed_changes_json longtext NOT NULL,
                created_by bigint unsigned NOT NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY atlas_req_change_prop_lookup (organization_id, requirement_id, proposal_status, updated_at),
                KEY atlas_req_change_prop_source (source_document_id, proposal_status)
            ) {$charset}", 'Create requirement change proposal table');
        }
        $s->assertTable($table);
        $s->assertColumn($table, 'id', 'char(36)', false, null);
        $s->assertColumn($table, 'requirement_id', 'char(36)', false, null);
        $s->assertColumn($table, 'organization_id', 'char(36)', true, null);
        $s->assertColumn($table, 'source_document_id', 'char(36)', true, null);
        $s->assertColumn($table, 'proposal_status', 'varchar(40)', false, 'draft');
        $s->assertColumn($table, 'proposal_reason', 'text', false, null);
        $s->assertColumn($table, 'proposed_changes_json', 'longtext', false, null);
        $s->assertIndex($table, 'atlas_req_change_prop_lookup', ['organization_id', 'requirement_id', 'proposal_status', 'updated_at'], false);
        $s->assertIndex($table, 'atlas_req_change_prop_source', ['source_document_id', 'proposal_status'], false);
    }
};
