<?php
declare(strict_types=1);
use Atlas\Platform\Core\Migrations\Migration;
use Atlas\Platform\Core\Migrations\MigrationContext;
use Atlas\Platform\Core\Migrations\SchemaInspector;
return new class implements Migration{
public function id():string{return'0018';}
public function description():string{return'Create non-PHI insurance profiles and DME category catalog.';}
public function up(MigrationContext$c):void{$db=$c->database;$s=new SchemaInspector($db);$ch=$db->get_charset_collate();$profiles=$db->prefix.'atlas_insurance_profiles';$dme=$db->prefix.'atlas_dme_categories';
if(!$s->tableExists($profiles)){$s->execute("CREATE TABLE `{$profiles}` (id char(36) NOT NULL, organization_id char(36) NULL, payer varchar(191) NOT NULL, plan_name varchar(191) NOT NULL DEFAULT '', line_of_business varchar(80) NOT NULL DEFAULT '', jurisdiction varchar(120) NOT NULL DEFAULT '', portal_url varchar(500) NULL, phone varchar(80) NOT NULL DEFAULT '', effective_date date NULL, status varchar(30) NOT NULL DEFAULT 'active', created_by bigint(20) unsigned NOT NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY(id), INDEX atlas_insurance_profile_lookup(organization_id,payer,plan_name,jurisdiction,status)) ENGINE=InnoDB {$ch}",'Create insurance profiles');}
$s->assertTable($profiles);foreach([['id','char(36)',false,null],['organization_id','char(36)',true,null],['payer','varchar(191)',false,null],['plan_name','varchar(191)',false,''],['line_of_business','varchar(80)',false,''],['jurisdiction','varchar(120)',false,''],['portal_url','varchar(500)',true,null],['phone','varchar(80)',false,''],['effective_date','date',true,null],['status','varchar(30)',false,'active'],['created_by','bigint unsigned',false,null],['created_at','datetime',false,null],['updated_at','datetime',false,null]]as[$a,$b,$n,$d]){$s->assertColumn($profiles,$a,$b,$n,$d);}
if(!$s->tableExists($dme)){$s->execute("CREATE TABLE `{$dme}` (id char(36) NOT NULL, slug varchar(120) NOT NULL, label varchar(191) NOT NULL, description text NULL, status varchar(30) NOT NULL DEFAULT 'active', created_at datetime NOT NULL, PRIMARY KEY(id), UNIQUE INDEX atlas_dme_category_slug(slug), INDEX atlas_dme_category_status(status,label)) ENGINE=InnoDB {$ch}",'Create DME categories');}
$s->assertTable($dme);foreach([['id','char(36)',false,null],['slug','varchar(120)',false,null],['label','varchar(191)',false,null],['description','text',true,null],['status','varchar(30)',false,'active'],['created_at','datetime',false,null]]as[$a,$b,$n,$d]){$s->assertColumn($dme,$a,$b,$n,$d);}
}
};
