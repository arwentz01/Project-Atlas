<?php
declare(strict_types=1);
use Atlas\Platform\Core\Migrations\Migration;use Atlas\Platform\Core\Migrations\MigrationContext;use Atlas\Platform\Core\Migrations\SchemaInspector;
return new class implements Migration{
public function id():string{return'0011';}public function description():string{return'Add structured resource metadata and archival state.';}
public function up(MigrationContext$c):void{$db=$c->database;$s=new SchemaInspector($db);$table=$db->prefix.'atlas_resources';$metadata=$db->prefix.'atlas_resource_metadata';
if(!$s->tableExists($metadata)){$s->execute("CREATE TABLE `{$metadata}` (resource_id char(36) NOT NULL, metadata_json longtext NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY(resource_id)) ENGINE=InnoDB {$db->get_charset_collate()}",'Create resource metadata');}
if(!$s->columnExists($table,'archived_at')){$s->execute("ALTER TABLE `{$table}` ADD COLUMN archived_at datetime NULL AFTER current_version_id",'Add resource archival state');}
$s->assertTable($metadata);$s->assertColumn($metadata,'resource_id','char(36)',false);$s->assertColumn($metadata,'metadata_json','longtext',false);$s->assertIndex($metadata,'PRIMARY',['resource_id'],true);$s->assertColumn($table,'archived_at','datetime',true);
}};
