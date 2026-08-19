#!/usr/bin/env php
<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/bootstrap.php';
$root=dirname(__DIR__);$fail=[];$checks=[];
$checks['Production debug disabled']=Config::get('APP_ENV')!=='production'||Config::get('APP_DEBUG','false')!=='true';
$checks['HTTPS application URL']=Config::get('APP_ENV')!=='production'||str_starts_with((string)Config::get('APP_URL',''),'https://');
$checks['Secure production sessions']=Config::get('APP_ENV')!=='production'||Config::get('SESSION_SECURE')==='true';
$checks['Environment ignored']=str_contains((string)file_get_contents($root.'/.gitignore'),'.env');
$checks['Private uploads protected']=is_file($root.'/storage/private/.htaccess');
$checks['Directory listing disabled']=str_contains((string)file_get_contents($root.'/.htaccess'),'Options -Indexes');
$checks['Security headers configured']=str_contains((string)file_get_contents($root.'/index.php'),'Content-Security-Policy');
$checks['No committed local environment']=!is_file($root.'/.env')||Config::get('APP_ENV')!=='production';
foreach($checks as $label=>$pass){fwrite($pass?STDOUT:STDERR,sprintf("[%s] %s\n",$pass?'PASS':'FAIL',$label));if(!$pass)$fail[]=$label;}if($fail)exit(1);
