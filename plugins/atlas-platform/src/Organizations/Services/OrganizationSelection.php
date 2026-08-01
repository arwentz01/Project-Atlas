<?php

declare(strict_types=1);

namespace Atlas\Platform\Organizations\Services;

interface OrganizationSelection
{
    public function selectedForUser(int $userId): ?string;
    public function selectForUser(int $userId, string $organizationId): void;
}
