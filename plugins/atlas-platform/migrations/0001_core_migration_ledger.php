<?php
declare(strict_types=1);

use Atlas\Platform\Core\Migrations\Migration;
use Atlas\Platform\Core\Migrations\MigrationContext;
return new class implements Migration {
    public function id(): string { return '0001'; }
    public function description(): string { return 'Verify the Atlas forward-only migration ledger.'; }
    public function up(MigrationContext $context): void
    {
        $context->repository->ensureLedger();
        if (! $context->repository->tableExists()) { throw new \RuntimeException('The migration ledger is not available after creation.'); }
    }
};
