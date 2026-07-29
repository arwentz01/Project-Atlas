<?php
declare(strict_types=1);

namespace Atlas\Platform\Organizations\Services;

use Atlas\Platform\Organizations\Domain\Organization;
use Atlas\Platform\Organizations\Repositories\MembershipRepository;
use Atlas\Platform\Organizations\Repositories\OrganizationRepository;

final class DefaultCurrentOrganizationResolver implements CurrentOrganizationResolver
{
    public function __construct(private MembershipRepository $memberships, private OrganizationRepository $organizations, private OrganizationSelection $selection) {}
    public function resolveForUser(int $userId): ?Organization
    {
        if ($userId <= 0) { return null; }
        $ids = $this->memberships->findActiveOrganizationIdsForUser($userId);
        if ($ids === []) { return null; }
        $selected = $this->selection->selectedForUser($userId);
        if ($selected !== null && in_array($selected, $ids, true)) { return $this->organizations->findActiveById($selected); }
        if (count($ids) === 1) { return $this->organizations->findActiveById($ids[0]); }
        return null;
    }
}
