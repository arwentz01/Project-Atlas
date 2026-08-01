<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Migrations;

final class MigrationInventory
{
    /** @param array<string, Migration> $migrations @param list<array<string, string>> $malformed @param list<string> $duplicates @param list<string> $gaps */
    public function __construct(public array $migrations, public array $malformed, public array $duplicates, public array $gaps) {}
}
