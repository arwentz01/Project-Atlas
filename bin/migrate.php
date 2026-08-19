#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
if ($schema === false) {
    fwrite(STDERR, "Could not read database/schema.sql\n");
    exit(1);
}

try {
    $db=Database::connection();
    $statements=preg_split('/;\s*(?:\r?\n|$)/',trim($schema))?:[];
    $applied=0;
    foreach($statements as $index=>$statement){
        $statement=trim($statement);
        if($statement==='')continue;
        try{$db->exec($statement);$applied++;}
        catch(Throwable $exception){
            $preview=preg_replace('/\s+/',' ',substr($statement,0,180));
            throw new RuntimeException('Statement '.($index+1).' failed near: '.$preview.'. '.$exception->getMessage(),0,$exception);
        }
    }
    $missing=SystemStatus::missingTables($db);
    if($missing)throw new RuntimeException('Migration completed its SQL statements but these tables are still missing: '.implode(', ',$missing));
    fwrite(STDOUT, "Atlas database schema is ready ({$applied} statements checked).\n");
} catch (Throwable $exception) {
    fwrite(STDERR, "Migration failed: {$exception->getMessage()}\n");
    exit(1);
}
