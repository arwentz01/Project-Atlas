<?php
declare(strict_types=1);
use Atlas\Platform\Core\Migrations\Migration;
use Atlas\Platform\Core\Migrations\MigrationContext;
use Atlas\Platform\Core\Migrations\SchemaInspector;
return new class implements Migration{
public function id():string{return'0021';}
public function description():string{return'Add preserved source document file metadata.';}
public function up(MigrationContext$c):void{$db=$c->database;$s=new SchemaInspector($db);$docs=$db->prefix.'atlas_source_documents';$s->assertTable($docs);
foreach([
['preserved_file_path','varchar(500)',true,null,"ALTER TABLE `{$docs}` ADD COLUMN preserved_file_path varchar(500) NULL AFTER source_url",'Add preserved source file path'],
['original_filename','varchar(255)',true,null,"ALTER TABLE `{$docs}` ADD COLUMN original_filename varchar(255) NULL AFTER preserved_file_path",'Add original source filename'],
['mime_type','varchar(100)',true,null,"ALTER TABLE `{$docs}` ADD COLUMN mime_type varchar(100) NULL AFTER original_filename",'Add source MIME type'],
['file_size_bytes','bigint unsigned',true,null,"ALTER TABLE `{$docs}` ADD COLUMN file_size_bytes bigint(20) unsigned NULL AFTER mime_type",'Add source file size'],
['preserved_at','datetime',true,null,"ALTER TABLE `{$docs}` ADD COLUMN preserved_at datetime NULL AFTER file_size_bytes",'Add source preservation timestamp'],
]as[$column,$type,$nullable,$default,$sql,$operation]){if(!$s->columnExists($docs,$column)){$s->execute($sql,$operation);}$s->assertColumn($docs,$column,$type,$nullable,$default);}
if(!$s->indexMatches($docs,'atlas_source_documents_checksum',['checksum'],false)){$s->execute("ALTER TABLE `{$docs}` ADD INDEX atlas_source_documents_checksum (checksum)",'Add source document checksum index');}$s->assertIndex($docs,'atlas_source_documents_checksum',['checksum'],false);
}
};
