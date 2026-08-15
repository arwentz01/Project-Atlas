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
    Database::connection()->exec($schema);
    fwrite(STDOUT, "Atlas database schema is ready.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, "Migration failed: {$exception->getMessage()}\n");
    exit(1);
}
