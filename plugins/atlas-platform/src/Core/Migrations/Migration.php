<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Migrations;

interface Migration
{
    public function id(): string;
    public function description(): string;
    public function up(MigrationContext $context): void;
}
