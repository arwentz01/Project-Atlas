<?php
declare(strict_types=1);

namespace Atlas\Platform\Organizations\Repositories;

interface MembershipRepository
{
    /** @return list<string> */
    public function findActiveOrganizationIdsForUser(int $userId): array;
    public function userHasActiveMembership(int $userId, string $organizationId): bool;
}
