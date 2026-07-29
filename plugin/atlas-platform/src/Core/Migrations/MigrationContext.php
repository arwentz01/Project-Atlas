<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Migrations;

final class MigrationContext
{
    public function __construct(public readonly object $database, public readonly MigrationStore $repository) {}
}
