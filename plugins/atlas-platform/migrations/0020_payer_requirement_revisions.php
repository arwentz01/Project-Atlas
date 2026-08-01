<?php
declare(strict_types=1);
use Atlas\Platform\Core\Migrations\Migration;
use Atlas\Platform\Core\Migrations\MigrationContext;
use Atlas\Platform\Core\Migrations\SchemaInspector;
return new class implements Migration{
public function id():string{return'0020';}
public function description():string{return'Create immutable payer requirement revision snapshots.';}
public function up(MigrationContext$c):void{$db=$c->database;$s=new SchemaInspector($db);$ch=$db->get_charset_collate();$req=$db->prefix.'atlas_payer_requirements';$rev=$db->prefix.'atlas_payer_requirement_revisions';$s->assertTable($req);
if(!$s->tableExists($rev)){$s->execute("CREATE TABLE `{$rev}` (id char(36) NOT NULL, requirement_id char(36) NOT NULL, organization_id char(36) NULL, revision_number int unsigned NOT NULL, revision_type varchar(40) NOT NULL, snapshot_json longtext NOT NULL, created_by bigint(20) unsigned NOT NULL, created_at datetime NOT NULL, PRIMARY KEY (id), UNIQUE KEY atlas_requirement_revision_number (requirement_id,revision_number), KEY atlas_requirement_revision_lookup (organization_id,requirement_id,created_at)) ENGINE=InnoDB {$ch}",'Create payer requirement revisions');}
$s->assertTable($rev);$s->assertColumn($rev,'id','char(36)',false,null);$s->assertColumn($rev,'requirement_id','char(36)',false,null);$s->assertColumn($rev,'organization_id','char(36)',true,null);$s->assertColumn($rev,'revision_number','int unsigned',false,null);$s->assertColumn($rev,'revision_type','varchar(40)',false,null);$s->assertColumn($rev,'snapshot_json','longtext',false,null);$s->assertColumn($rev,'created_by','bigint unsigned',false,null);$s->assertColumn($rev,'created_at','datetime',false,null);$s->assertIndex($rev,'atlas_requirement_revision_number',['requirement_id','revision_number'],true);$s->assertIndex($rev,'atlas_requirement_revision_lookup',['organization_id','requirement_id','created_at'],false);
}
};
