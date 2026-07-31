<?php
declare(strict_types=1);
use Atlas\Platform\Core\Migrations\Migration;
use Atlas\Platform\Core\Migrations\MigrationContext;
use Atlas\Platform\Core\Migrations\SchemaInspector;
return new class implements Migration{
public function id():string{return'0019';}
public function description():string{return'Add DME-specific requirement detail fields.';}
public function up(MigrationContext$c):void{$db=$c->database;$s=new SchemaInspector($db);$req=$db->prefix.'atlas_payer_requirements';$s->assertTable($req);
if(!$s->columnExists($req,'dme_category_slug')){$s->execute("ALTER TABLE `{$req}` ADD COLUMN dme_category_slug varchar(120) NOT NULL DEFAULT '' AFTER topic",'Add DME category slug');}
$s->assertColumn($req,'dme_category_slug','varchar(120)',false,'');
if(!$s->columnExists($req,'prior_authorization_status')){$s->execute("ALTER TABLE `{$req}` ADD COLUMN prior_authorization_status varchar(30) NOT NULL DEFAULT 'unknown' AFTER requirement_type",'Add prior authorization status');}
$s->assertColumn($req,'prior_authorization_status','varchar(30)',false,'unknown');
if(!$s->columnExists($req,'frequency_limit')){$s->execute("ALTER TABLE `{$req}` ADD COLUMN frequency_limit varchar(191) NOT NULL DEFAULT '' AFTER prior_authorization_status",'Add frequency limit');}
$s->assertColumn($req,'frequency_limit','varchar(191)',false,'');
if(!$s->columnExists($req,'replacement_interval')){$s->execute("ALTER TABLE `{$req}` ADD COLUMN replacement_interval varchar(191) NOT NULL DEFAULT '' AFTER frequency_limit",'Add replacement interval');}
$s->assertColumn($req,'replacement_interval','varchar(191)',false,'');
if(!$s->columnExists($req,'required_forms_json')){$s->execute("ALTER TABLE `{$req}` ADD COLUMN required_forms_json longtext NOT NULL AFTER replacement_interval",'Add required forms JSON');}
$s->assertColumn($req,'required_forms_json','longtext',false,null);
if(!$s->columnExists($req,'coverage_criteria_text')){$s->execute("ALTER TABLE `{$req}` ADD COLUMN coverage_criteria_text text NOT NULL AFTER required_forms_json",'Add coverage criteria text');}
$s->assertColumn($req,'coverage_criteria_text','text',false,null);
if(!$s->indexMatches($req,'atlas_requirements_dme_match',['organization_id','payer','dme_category_slug','jurisdiction','review_status'],false)){$s->execute("ALTER TABLE `{$req}` ADD INDEX atlas_requirements_dme_match (organization_id,payer,dme_category_slug,jurisdiction,review_status)",'Add DME requirement match index');}
$s->assertIndex($req,'atlas_requirements_dme_match',['organization_id','payer','dme_category_slug','jurisdiction','review_status'],false);
}
};
