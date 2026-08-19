#!/usr/bin/env php
<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/bootstrap.php';
$db=Database::connection();$failures=[];$passes=0;
function expect(string $label,bool $condition):void{global $failures,$passes;if($condition){$passes++;fwrite(STDOUT,"[PASS] {$label}\n");}else{$failures[]=$label;fwrite(STDERR,"[FAIL] {$label}\n");}}
expect('PHP supported',version_compare(PHP_VERSION,'8.0.0','>='));expect('Schema complete',SystemStatus::missingTables($db)===[]);expect('Passwords use modern hashing',password_verify('atlas-test',password_hash('atlas-test',PASSWORD_DEFAULT)));expect('CSRF token length',strlen(Csrf::token())>=32);
$organizations=$db->query('SELECT id FROM organizations ORDER BY id LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);if($organizations){$oid=(int)$organizations[0];$member=(int)$db->query('SELECT id FROM memberships WHERE organization_id='.$oid.' AND status="active" LIMIT 1')->fetchColumn();if($member){$scheduling=new SchedulingRepository($db);$production=new ProductionReadinessRepository($db);expect('Member schedule query',is_array($scheduling->memberSchedule($oid,$member)));expect('Tenant search query',is_array($production->search($oid,'test')));expect('Saved view query',is_array($production->savedViews($oid,$member)));$q=$db->prepare('SELECT COUNT(*) FROM memberships WHERE id=? AND organization_id<>?');$q->execute([$member,$oid]);expect('Membership belongs to one tenant',(int)$q->fetchColumn()===0);}}
expect('PWA manifest exists',is_file(dirname(__DIR__).'/manifest.json'));expect('Service worker exists',is_file(dirname(__DIR__).'/sw.js'));expect('Private storage access rule',is_file(dirname(__DIR__).'/storage/private/.htaccess'));
fwrite(STDOUT,"\n{$passes} automated checks passed.\n");if($failures){fwrite(STDERR,count($failures)." checks failed.\n");exit(1);}
