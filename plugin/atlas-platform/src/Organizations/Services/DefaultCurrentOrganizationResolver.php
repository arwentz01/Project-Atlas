<?php
declare(strict_types=1);

namespace Atlas\Platform\Organizations\Services;

use Atlas\Platform\Organizations\Domain\Organization;
use Atlas\Platform\Organizations\Repositories\MembershipRepository;
use Atlas\Platform\Organizations\Repositories\OrganizationRepository;

final class DefaultCurrentOrganizationResolver implements CurrentOrganizationResolver
{
    public function __construct(private MembershipRepository $memberships, private OrganizationRepository $organizations) {}
    public function resolveForUser(int $userId): ?Organization
    {
        if ($userId <= 0) { return null; }
        $ids = $this->memberships->findActiveOrganizationIdsForUser($userId);
        if (count($ids) !== 1) { return null; }
        return $this->organizations->findActiveById($ids[0]);
    }
}
