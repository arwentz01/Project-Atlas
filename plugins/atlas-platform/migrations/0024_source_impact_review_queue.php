<?php
declare(strict_types=1);
use Atlas\Platform\Core\Migrations\Migration;
use Atlas\Platform\Core\Migrations\MigrationContext;
use Atlas\Platform\Core\Migrations\SchemaInspector;
return new class implements Migration{
public function id():string{return'0024';}
public function description():string{return'Add payer requirement source impact review queue fields.';}
public function up(MigrationContext$c):void{$db=$c->database;$s=new SchemaInspector($db);$req=$db->prefix.'atlas_payer_requirements';$s->assertTable($req);
if(!$this->columnExists($db,$req,'source_review_status')){$s->execute("ALTER TABLE `{$req}` ADD COLUMN source_review_status varchar(40) NOT NULL DEFAULT 'current' AFTER review_status",'Add requirement source review status');}
if(!$this->columnExists($db,$req,'source_review_document_id')){$s->execute("ALTER TABLE `{$req}` ADD COLUMN source_review_document_id char(36) NULL AFTER source_review_status",'Add requirement source review document');}
if(!$this->columnExists($db,$req,'source_review_reason')){$s->execute("ALTER TABLE `{$req}` ADD COLUMN source_review_reason text NULL AFTER source_review_document_id",'Add requirement source review reason');}
if(!$this->columnExists($db,$req,'source_reviewed_at')){$s->execute("ALTER TABLE `{$req}` ADD COLUMN source_reviewed_at datetime NULL AFTER source_review_reason",'Add requirement source reviewed timestamp');}
$s->assertColumn($req,'source_review_status','varchar(40)',false,'current');$s->assertColumn($req,'source_review_document_id','char(36)',true,null);$s->assertColumn($req,'source_review_reason','text',true,null);$s->assertColumn($req,'source_reviewed_at','datetime',true,null);
if(!$s->indexMatches($req,'atlas_requirements_source_review',['source_review_status','source_review_document_id','updated_at'],false)){$s->execute("ALTER TABLE `{$req}` ADD INDEX atlas_requirements_source_review (source_review_status, source_review_document_id, updated_at)",'Add requirement source review index');}
$s->assertIndex($req,'atlas_requirements_source_review',['source_review_status','source_review_document_id','updated_at'],false);
}
private function columnExists(object$db,string$table,string$column):bool{return is_array($db->get_row($db->prepare("SHOW COLUMNS FROM `{$table}` WHERE Field = %s",$column),ARRAY_A));}
};
