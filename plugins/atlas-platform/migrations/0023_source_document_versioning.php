<?php
declare(strict_types=1);
use Atlas\Platform\Core\Migrations\Migration;
use Atlas\Platform\Core\Migrations\MigrationContext;
use Atlas\Platform\Core\Migrations\SchemaInspector;
return new class implements Migration{
public function id():string{return'0023';}
public function description():string{return'Add source document family and supersession fields for version comparison.';}
public function up(MigrationContext$c):void{$db=$c->database;$s=new SchemaInspector($db);$docs=$db->prefix.'atlas_source_documents';$s->assertTable($docs);
if(!$s->columnExists($docs,'source_family_key')){$s->execute("ALTER TABLE `{$docs}` ADD COLUMN source_family_key varchar(191) NOT NULL DEFAULT '' AFTER preserved_at",'Add source document family key');}
if(!$s->columnExists($docs,'source_version_label')){$s->execute("ALTER TABLE `{$docs}` ADD COLUMN source_version_label varchar(120) NOT NULL DEFAULT '' AFTER source_family_key",'Add source document version label');}
if(!$s->columnExists($docs,'supersedes_document_id')){$s->execute("ALTER TABLE `{$docs}` ADD COLUMN supersedes_document_id char(36) NULL AFTER source_version_label",'Add superseded source document reference');}
$s->assertColumn($docs,'source_family_key','varchar(191)',false,'');
$s->assertColumn($docs,'source_version_label','varchar(120)',false,'');
$s->assertColumn($docs,'supersedes_document_id','char(36)',true,null);
if(!$s->indexMatches($docs,'atlas_source_docs_family',['organization_id','source_family_key','effective_date','created_at'],false)){$s->execute("ALTER TABLE `{$docs}` ADD INDEX atlas_source_docs_family (organization_id,source_family_key,effective_date,created_at)",'Add source document family index');}
if(!$s->indexMatches($docs,'atlas_source_docs_supersedes',['supersedes_document_id'],false)){$s->execute("ALTER TABLE `{$docs}` ADD INDEX atlas_source_docs_supersedes (supersedes_document_id)",'Add source document supersession index');}
$s->assertIndex($docs,'atlas_source_docs_family',['organization_id','source_family_key','effective_date','created_at'],false);
$s->assertIndex($docs,'atlas_source_docs_supersedes',['supersedes_document_id'],false);
}
};
