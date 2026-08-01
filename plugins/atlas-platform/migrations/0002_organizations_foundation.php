<?php
declare(strict_types=1);

use Atlas\Platform\Core\Migrations\Migration;
use Atlas\Platform\Core\Migrations\MigrationContext;
use Atlas\Platform\Core\Migrations\SchemaInspector;

return new class implements Migration {
    public function id(): string { return '0002'; }
    public function description(): string { return 'Create and verify organization and membership foundations.'; }
    public function up(MigrationContext $context): void
    {
        $db = $context->database;
        $schema = new SchemaInspector($db);
        $organizations = $db->prefix . 'atlas_organizations';
        $memberships = $db->prefix . 'atlas_organization_memberships';
        $charset = $db->get_charset_collate();

        if (! $schema->tableExists($organizations)) {
            $schema->execute("CREATE TABLE `{$organizations}` (id char(36) NOT NULL, name varchar(255) NOT NULL, slug varchar(191) NOT NULL, status varchar(20) NOT NULL DEFAULT 'active', created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY (id)) ENGINE=InnoDB {$charset}", 'Create organizations table');
        }
        $schema->assertTable($organizations);
        $schema->assertColumn($organizations, 'id', 'char(36)', false);
        $schema->assertColumn($organizations, 'name', 'varchar(255)', false);
        $schema->assertColumn($organizations, 'slug', 'varchar(191)', false);
        $schema->assertColumn($organizations, 'status', 'varchar(20)', false, 'active');
        $schema->assertColumn($organizations, 'created_at', 'datetime', false);
        $schema->assertColumn($organizations, 'updated_at', 'datetime', false);
        $schema->assertIndex($organizations, 'PRIMARY', ['id'], true);
        if (! $schema->indexMatches($organizations, 'atlas_org_slug_unique', ['slug'], true)) {
            $schema->execute("ALTER TABLE `{$organizations}` ADD UNIQUE INDEX atlas_org_slug_unique (slug)", 'Add organization slug uniqueness');
            $schema->assertIndex($organizations, 'atlas_org_slug_unique', ['slug'], true);
        }

        if (! $schema->tableExists($memberships)) {
            $schema->execute("CREATE TABLE `{$memberships}` (id char(36) NOT NULL, organization_id char(36) NOT NULL, user_id bigint(20) unsigned NOT NULL, status varchar(20) NOT NULL DEFAULT 'active', roles_json longtext NOT NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY (id)) ENGINE=InnoDB {$charset}", 'Create organization memberships table');
        }
        $schema->assertTable($memberships);
        $schema->assertColumn($memberships, 'id', 'char(36)', false);
        $schema->assertColumn($memberships, 'organization_id', 'char(36)', false);
        $schema->assertColumn($memberships, 'user_id', 'bigint unsigned', false);
        $schema->assertColumn($memberships, 'status', 'varchar(20)', false, 'active');
        $schema->assertColumn($memberships, 'roles_json', 'longtext', false);
        $schema->assertColumn($memberships, 'created_at', 'datetime', false);
        $schema->assertColumn($memberships, 'updated_at', 'datetime', false);
        $schema->assertIndex($memberships, 'PRIMARY', ['id'], true);
        if (! $schema->indexMatches($memberships, 'atlas_membership_unique', ['organization_id', 'user_id'], true)) {
            $schema->execute("ALTER TABLE `{$memberships}` ADD UNIQUE INDEX atlas_membership_unique (organization_id, user_id)", 'Add membership uniqueness');
            $schema->assertIndex($memberships, 'atlas_membership_unique', ['organization_id', 'user_id'], true);
        }
        if (! $schema->indexMatches($memberships, 'atlas_membership_user_status', ['user_id', 'status'], false)) {
            $schema->execute("ALTER TABLE `{$memberships}` ADD INDEX atlas_membership_user_status (user_id, status)", 'Add membership user lookup');
            $schema->assertIndex($memberships, 'atlas_membership_user_status', ['user_id', 'status'], false);
        }
        if (! $schema->indexMatches($memberships, 'atlas_membership_org_status', ['organization_id', 'status'], false)) {
            $schema->execute("ALTER TABLE `{$memberships}` ADD INDEX atlas_membership_org_status (organization_id, status)", 'Add membership organization lookup');
            $schema->assertIndex($memberships, 'atlas_membership_org_status', ['organization_id', 'status'], false);
        }
    }
};
