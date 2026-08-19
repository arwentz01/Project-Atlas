#!/usr/bin/env php
<?php
declare(strict_types=1);
$file=$argv[1]??'';if(!$file||!is_file($file)){fwrite(STDERR,"Usage: php bin/restore-check.php /path/to/atlas-backup.sql\n");exit(1);}$contents=file_get_contents($file);$required=['CREATE TABLE `users`','CREATE TABLE `organizations`','CREATE TABLE `memberships`','CREATE TABLE `shifts`'];foreach($required as $needle)if(!str_contains((string)$contents,$needle)){fwrite(STDERR,"Backup validation failed: {$needle} not found.\n");exit(1);}fwrite(STDOUT,"Backup contains required Atlas tables. Perform a staging restore before production use.\n");
