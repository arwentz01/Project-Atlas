<?php
declare(strict_types=1);
use Atlas\Platform\Core\Migrations\Migration;use Atlas\Platform\Core\Migrations\MigrationContext;use Atlas\Platform\Core\Migrations\SchemaInspector;
return new class implements Migration{
public function id():string{return'0012';}public function description():string{return'Create editorial assignments and append-only review notes.';}
public function up(MigrationContext$c):void{$db=$c->database;$s=new SchemaInspector($db);$charset=$db->get_charset_collate();$assign=$db->prefix.'atlas_editorial_assignments';$notes=$db->prefix.'atlas_editorial_notes';
if(!$s->tableExists($assign)){$s->execute("CREATE TABLE `{$assign}` (resource_version_id char(36) NOT NULL, reviewer_user_id bigint(20) unsigned NOT NULL, due_at datetime NULL, assigned_by bigint(20) unsigned NOT NULL, assigned_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY(resource_version_id)) ENGINE=InnoDB {$charset}",'Create editorial assignments');}
if(!$s->tableExists($notes)){$s->execute("CREATE TABLE `{$notes}` (id char(36) NOT NULL, resource_version_id char(36) NOT NULL, author_user_id bigint(20) unsigned NOT NULL, note_type varchar(30) NOT NULL, note_text text NOT NULL, created_at datetime NOT NULL, PRIMARY KEY(id), INDEX atlas_editorial_note_version(resource_version_id,created_at)) ENGINE=InnoDB {$charset}",'Create editorial notes');}
$s->assertTable($assign);$s->assertTable($notes);$s->assertIndex($assign,'PRIMARY',['resource_version_id'],true);$s->assertIndex($notes,'PRIMARY',['id'],true);
}};
