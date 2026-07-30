<?php
declare(strict_types=1);
use Atlas\Platform\Core\Migrations\Migration;use Atlas\Platform\Core\Migrations\MigrationContext;use Atlas\Platform\Core\Migrations\SchemaInspector;
return new class implements Migration{
public function id():string{return'0013';}public function description():string{return'Create saved resources, searches, and recent activity.';}
public function up(MigrationContext$c):void{$db=$c->database;$s=new SchemaInspector($db);$charset=$db->get_charset_collate();
$saved=$db->prefix.'atlas_saved_resources';$searches=$db->prefix.'atlas_saved_searches';$recent=$db->prefix.'atlas_recent_resources';
if(!$s->tableExists($saved)){$s->execute("CREATE TABLE `{$saved}` (user_id bigint(20) unsigned NOT NULL,resource_id char(36) NOT NULL,created_at datetime NOT NULL,PRIMARY KEY(user_id,resource_id)) ENGINE=InnoDB {$charset}",'Create saved resources');}
if(!$s->tableExists($searches)){$s->execute("CREATE TABLE `{$searches}` (id char(36) NOT NULL,user_id bigint(20) unsigned NOT NULL,name varchar(120) NOT NULL,criteria_json longtext NOT NULL,created_at datetime NOT NULL,PRIMARY KEY(id),INDEX atlas_saved_search_user(user_id,created_at)) ENGINE=InnoDB {$charset}",'Create saved searches');}
if(!$s->tableExists($recent)){$s->execute("CREATE TABLE `{$recent}` (user_id bigint(20) unsigned NOT NULL,resource_id char(36) NOT NULL,viewed_at datetime NOT NULL,PRIMARY KEY(user_id,resource_id),INDEX atlas_recent_user_time(user_id,viewed_at)) ENGINE=InnoDB {$charset}",'Create recent resources');}
foreach([$saved,$searches,$recent]as$table){$s->assertTable($table);}
}};
