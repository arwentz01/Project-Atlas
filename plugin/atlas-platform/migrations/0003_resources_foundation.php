<?php
declare(strict_types=1);

use Atlas\Platform\Core\Migrations\Migration;
use Atlas\Platform\Core\Migrations\MigrationContext;
use Atlas\Platform\Core\Migrations\SchemaInspector;

return new class implements Migration {
    public function id(): string { return '0003'; }
    public function description(): string { return 'Create and verify source-aware resource foundations.'; }
    public function up(MigrationContext $context): void
    {
        $db = $context->database; $schema = new SchemaInspector($db); $charset = $db->get_charset_collate();
        $resources = $db->prefix . 'atlas_resources'; $versions = $db->prefix . 'atlas_resource_versions'; $sources = $db->prefix . 'atlas_sources'; $citations = $db->prefix . 'atlas_citations';
        if (! $schema->tableExists($resources)) { $schema->execute("CREATE TABLE `{$resources}` (id char(36) NOT NULL, scope varchar(20) NOT NULL, scope_key varchar(64) NOT NULL, organization_id char(36) NULL, resource_type varchar(40) NOT NULL, slug varchar(191) NOT NULL, current_version_id char(36) NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY (id)) ENGINE=InnoDB {$charset}", 'Create resources table'); }
        $schema->assertTable($resources);
        foreach ([['id','char(36)',false,null],['scope','varchar(20)',false,null],['scope_key','varchar(64)',false,null],['organization_id','char(36)',true,null],['resource_type','varchar(40)',false,null],['slug','varchar(191)',false,null],['current_version_id','char(36)',true,null],['created_at','datetime',false,null],['updated_at','datetime',false,null]] as [$column,$type,$nullable,$default]) { $schema->assertColumn($resources, $column, $type, $nullable, $default); }
        $schema->assertIndex($resources, 'PRIMARY', ['id'], true);
        $this->ensureIndex($schema, $resources, 'atlas_resource_scope_slug_unique', ['scope_key','slug'], true, "ALTER TABLE `{$resources}` ADD UNIQUE INDEX atlas_resource_scope_slug_unique (scope_key, slug)");
        $this->ensureIndex($schema, $resources, 'atlas_resource_context', ['scope','organization_id','resource_type'], false, "ALTER TABLE `{$resources}` ADD INDEX atlas_resource_context (scope, organization_id, resource_type)");

        if (! $schema->tableExists($versions)) { $schema->execute("CREATE TABLE `{$versions}` (id char(36) NOT NULL, resource_id char(36) NOT NULL, version_number int unsigned NOT NULL, title varchar(255) NOT NULL, summary text NOT NULL, body_json longtext NOT NULL, review_status varchar(20) NOT NULL DEFAULT 'draft', effective_date date NULL, review_due_date date NULL, change_summary text NOT NULL, author_user_id bigint(20) unsigned NOT NULL, created_at datetime NOT NULL, PRIMARY KEY (id)) ENGINE=InnoDB {$charset}", 'Create resource versions table'); }
        $schema->assertTable($versions);
        foreach ([['id','char(36)',false,null],['resource_id','char(36)',false,null],['version_number','int unsigned',false,null],['title','varchar(255)',false,null],['summary','text',false,null],['body_json','longtext',false,null],['review_status','varchar(20)',false,'draft'],['effective_date','date',true,null],['review_due_date','date',true,null],['change_summary','text',false,null],['author_user_id','bigint unsigned',false,null],['created_at','datetime',false,null]] as [$column,$type,$nullable,$default]) { $schema->assertColumn($versions, $column, $type, $nullable, $default); }
        $schema->assertIndex($versions, 'PRIMARY', ['id'], true);
        $this->ensureIndex($schema, $versions, 'atlas_resource_version_unique', ['resource_id','version_number'], true, "ALTER TABLE `{$versions}` ADD UNIQUE INDEX atlas_resource_version_unique (resource_id, version_number)");
        $this->ensureIndex($schema, $versions, 'atlas_resource_review', ['review_status','review_due_date'], false, "ALTER TABLE `{$versions}` ADD INDEX atlas_resource_review (review_status, review_due_date)");

        if (! $schema->tableExists($sources)) { $schema->execute("CREATE TABLE `{$sources}` (id char(36) NOT NULL, publisher varchar(255) NOT NULL, title varchar(255) NOT NULL, source_url text NULL, document_identifier varchar(191) NULL, effective_date date NULL, retrieved_at datetime NOT NULL, checksum char(64) NOT NULL, created_at datetime NOT NULL, PRIMARY KEY (id)) ENGINE=InnoDB {$charset}", 'Create sources table'); }
        $schema->assertTable($sources);
        foreach ([['id','char(36)',false,null],['publisher','varchar(255)',false,null],['title','varchar(255)',false,null],['source_url','text',true,null],['document_identifier','varchar(191)',true,null],['effective_date','date',true,null],['retrieved_at','datetime',false,null],['checksum','char(64)',false,null],['created_at','datetime',false,null]] as [$column,$type,$nullable,$default]) { $schema->assertColumn($sources, $column, $type, $nullable, $default); }
        $schema->assertIndex($sources, 'PRIMARY', ['id'], true);
        $this->ensureIndex($schema, $sources, 'atlas_source_checksum_unique', ['checksum'], true, "ALTER TABLE `{$sources}` ADD UNIQUE INDEX atlas_source_checksum_unique (checksum)");

        if (! $schema->tableExists($citations)) { $schema->execute("CREATE TABLE `{$citations}` (id char(36) NOT NULL, citation_key char(64) NOT NULL, resource_version_id char(36) NOT NULL, source_id char(36) NOT NULL, page_reference varchar(40) NULL, section_reference varchar(255) NULL, display_order int unsigned NOT NULL DEFAULT 0, created_at datetime NOT NULL, PRIMARY KEY (id)) ENGINE=InnoDB {$charset}", 'Create citations table'); }
        $schema->assertTable($citations);
        foreach ([['id','char(36)',false,null],['citation_key','char(64)',false,null],['resource_version_id','char(36)',false,null],['source_id','char(36)',false,null],['page_reference','varchar(40)',true,null],['section_reference','varchar(255)',true,null],['display_order','int unsigned',false,'0'],['created_at','datetime',false,null]] as [$column,$type,$nullable,$default]) { $schema->assertColumn($citations, $column, $type, $nullable, $default); }
        $schema->assertIndex($citations, 'PRIMARY', ['id'], true);
        $this->ensureIndex($schema, $citations, 'atlas_citation_key_unique', ['citation_key'], true, "ALTER TABLE `{$citations}` ADD UNIQUE INDEX atlas_citation_key_unique (citation_key)");
        $this->ensureIndex($schema, $citations, 'atlas_citation_version_order', ['resource_version_id','display_order'], false, "ALTER TABLE `{$citations}` ADD INDEX atlas_citation_version_order (resource_version_id, display_order)");
    }
    /** @param list<string> $columns */
    private function ensureIndex(SchemaInspector $schema, string $table, string $name, array $columns, bool $unique, string $sql): void
    {
        if (! $schema->indexMatches($table, $name, $columns, $unique)) { $schema->execute($sql, "Add {$name}"); $schema->assertIndex($table, $name, $columns, $unique); }
    }
};
