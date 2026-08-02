<?php
declare(strict_types=1);
use Atlas\Platform\Core\Migrations\Migration;
use Atlas\Platform\Core\Migrations\MigrationContext;
use Atlas\Platform\Core\Migrations\SchemaInspector;
return new class implements Migration{
public function id():string{return'0022';}
public function description():string{return'Create page-aware source document extraction storage.';}
public function up(MigrationContext$c):void{$db=$c->database;$s=new SchemaInspector($db);$ch=$db->get_charset_collate();$docs=$db->prefix.'atlas_source_documents';$pages=$db->prefix.'atlas_source_document_pages';$s->assertTable($docs);
if(!$s->tableExists($pages)){$s->execute("CREATE TABLE `{$pages}` (id char(36) NOT NULL, source_document_id char(36) NOT NULL, page_number int unsigned NOT NULL, extraction_method varchar(40) NOT NULL DEFAULT 'manual', text_content longtext NOT NULL, text_checksum char(64) NOT NULL, extracted_at datetime NOT NULL, created_by bigint(20) unsigned NOT NULL, PRIMARY KEY (id), UNIQUE KEY atlas_source_page_unique (source_document_id,page_number), KEY atlas_source_page_lookup (source_document_id,extracted_at)) ENGINE=InnoDB {$ch}",'Create source document pages');}
$s->assertTable($pages);$s->assertColumn($pages,'id','char(36)',false,null);$s->assertColumn($pages,'source_document_id','char(36)',false,null);$s->assertColumn($pages,'page_number','int unsigned',false,null);$s->assertColumn($pages,'extraction_method','varchar(40)',false,'manual');$s->assertColumn($pages,'text_content','longtext',false,null);$s->assertColumn($pages,'text_checksum','char(64)',false,null);$s->assertColumn($pages,'extracted_at','datetime',false,null);$s->assertColumn($pages,'created_by','bigint unsigned',false,null);$s->assertIndex($pages,'atlas_source_page_unique',['source_document_id','page_number'],true);$s->assertIndex($pages,'atlas_source_page_lookup',['source_document_id','extracted_at'],false);
}
};
