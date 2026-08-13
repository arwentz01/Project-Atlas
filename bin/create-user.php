<?php

declare(strict_types=1);

if(PHP_SAPI!=='cli'){fwrite(STDERR,"CLI only.\n");exit(1);}require_once dirname(__DIR__).'/bootstrap.php';
$email=$argv[1]??'';$password=$argv[2]??'';$name=$argv[3]??'Atlas Administrator';
if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<8){fwrite(STDERR,"Usage: php bin/create-user.php email@example.com '8+ character password' 'Display Name'\n");exit(1);}try{$db=Database::connect();$s=$db->prepare("INSERT INTO users (email,password_hash,display_name,role,active) VALUES (:email,:hash,:name,'administrator',1) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),display_name=VALUES(display_name),role='administrator',active=1");$s->execute(['email'=>mb_strtolower($email),'hash'=>password_hash($password,PASSWORD_DEFAULT),'name'=>$name]);fwrite(STDOUT,"Atlas administrator ready: {$email}\n");}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
