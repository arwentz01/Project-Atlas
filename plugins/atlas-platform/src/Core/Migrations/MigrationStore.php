<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Migrations;

interface MigrationStore
{
    public function ensureLedger(): void;
    public function tableExists(): bool;
    /** @return list<string> */
    public function completedIds(): array;
    public function record(Migration $migration, int $elapsedMs): void;
}
