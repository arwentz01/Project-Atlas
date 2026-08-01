<?php
declare(strict_types=1);

use Atlas\Platform\Core\Migrations\Migration;
use Atlas\Platform\Core\Migrations\MigrationContext;
use Atlas\Platform\Core\Migrations\SchemaInspector;

return new class implements Migration {
    public function id(): string { return '0010'; }
    public function description(): string { return 'Create organization membership invitations.'; }
    public function up(MigrationContext $context): void
    {
        $db = $context->database;
        $schema = new SchemaInspector($db);
        $table = $db->prefix . 'atlas_organization_invitations';
        if (! $schema->tableExists($table)) {
            $schema->execute("CREATE TABLE `{$table}` (id char(36) NOT NULL, organization_id char(36) NOT NULL, email varchar(320) NOT NULL, roles_json longtext NOT NULL, token_hash char(64) NOT NULL, status varchar(20) NOT NULL DEFAULT 'pending', invited_by bigint(20) unsigned NOT NULL, accepted_by bigint(20) unsigned NULL, expires_at datetime NOT NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY (id), UNIQUE INDEX atlas_invitation_token (token_hash), INDEX atlas_invitation_org_status (organization_id,status), INDEX atlas_invitation_email_status (email,status)) ENGINE=InnoDB {$db->get_charset_collate()}", 'Create organization invitations');
        }
        $schema->assertTable($table);
        foreach ([['id','char(36)',false,null],['organization_id','char(36)',false,null],['email','varchar(320)',false,null],['roles_json','longtext',false,null],['token_hash','char(64)',false,null],['status','varchar(20)',false,'pending'],['invited_by','bigint unsigned',false,null],['accepted_by','bigint unsigned',true,null],['expires_at','datetime',false,null],['created_at','datetime',false,null],['updated_at','datetime',false,null]] as [$column,$type,$nullable,$default]) {
            $schema->assertColumn($table, $column, $type, $nullable, $default);
        }
        $schema->assertIndex($table, 'PRIMARY', ['id'], true);
        $schema->assertIndex($table, 'atlas_invitation_token', ['token_hash'], true);
    }
};
