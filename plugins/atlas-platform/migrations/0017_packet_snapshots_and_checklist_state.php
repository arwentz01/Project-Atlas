<?php
declare(strict_types=1);
use Atlas\Platform\Core\Migrations\Migration;
use Atlas\Platform\Core\Migrations\MigrationContext;
use Atlas\Platform\Core\Migrations\SchemaInspector;
return new class implements Migration{
public function id():string{return'0017';}
public function description():string{return'Create patient packet print snapshots and documentation checklist state.';}
public function up(MigrationContext$c):void{$db=$c->database;$s=new SchemaInspector($db);$ch=$db->get_charset_collate();$snap=$db->prefix.'atlas_packet_snapshots';$state=$db->prefix.'atlas_requirement_checklist_state';
if(!$s->tableExists($snap)){$s->execute("CREATE TABLE `{$snap}` (id char(36) NOT NULL, packet_id char(36) NOT NULL, organization_id char(36) NULL, actor_user_id bigint(20) unsigned NOT NULL, packet_status varchar(20) NOT NULL, snapshot_json longtext NOT NULL, created_at datetime NOT NULL, PRIMARY KEY(id), INDEX atlas_packet_snapshot_packet(packet_id,created_at)) ENGINE=InnoDB {$ch}",'Create packet snapshots');}
$s->assertTable($snap);foreach([['id','char(36)',false],['packet_id','char(36)',false],['organization_id','char(36)',true],['actor_user_id','bigint unsigned',false],['packet_status','varchar(20)',false],['snapshot_json','longtext',false],['created_at','datetime',false]]as[$a,$b,$n]){$s->assertColumn($snap,$a,$b,$n);}
if(!$s->tableExists($state)){$s->execute("CREATE TABLE `{$state}` (id char(36) NOT NULL, requirement_id char(36) NOT NULL, organization_id char(36) NULL, checklist_hash char(64) NOT NULL, label text NOT NULL, status varchar(30) NOT NULL DEFAULT 'needed', notes text NULL, updated_by bigint(20) unsigned NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY(id), UNIQUE INDEX atlas_checklist_requirement_hash(requirement_id,checklist_hash), INDEX atlas_checklist_org_status(organization_id,status,updated_at)) ENGINE=InnoDB {$ch}",'Create checklist state');}
$s->assertTable($state);foreach([['id','char(36)',false,null],['requirement_id','char(36)',false,null],['organization_id','char(36)',true,null],['checklist_hash','char(64)',false,null],['label','text',false,null],['status','varchar(30)',false,'needed'],['notes','text',true,null],['updated_by','bigint unsigned',false,null],['updated_at','datetime',false,null]]as[$a,$b,$n,$d]){$s->assertColumn($state,$a,$b,$n,$d);}
}
};
