<?php
declare(strict_types=1);

namespace Atlas\Platform\Organizations\Repositories;

use Atlas\Platform\Organizations\Domain\Organization;

interface OrganizationRepository
{
    public function findActiveById(string $organizationId): ?Organization;
}
