<?php

declare(strict_types=1);

namespace Atlas\Platform\Organizations\Services;

use Atlas\Platform\Core\Audit\AuditRecorder;
use Atlas\Platform\Organizations\Domain\Organization;
use Atlas\Platform\Organizations\Domain\OrganizationPolicy;
use Atlas\Platform\Organizations\Repositories\MembershipRepository;
use Atlas\Platform\Organizations\Repositories\OrganizationRepository;
use InvalidArgumentException;

final class OrganizationContextService
{
    public function __construct(private MembershipRepository $memberships, private OrganizationRepository $organizations, private OrganizationSelection $selection, private OrganizationPolicy $policy, private AuditRecorder $audit) {}

    /** @return list<Organization> */
    public function availableForUser(int $userId): array
    {
        return $this->organizations->findActiveByIds($this->memberships->findActiveOrganizationIdsForUser($userId));
    }

    public function select(int $userId, string $organizationId): Organization
    {
        if ($userId < 1 || ! $this->policy->isValidIdentifier($organizationId) || ! $this->memberships->userHasActiveMembership($userId, $organizationId)) {
            throw new InvalidArgumentException('The selected organization is not available to this user.');
        }
        $organization = $this->organizations->findActiveById($organizationId);
        if ($organization === null) {
            throw new InvalidArgumentException('The selected organization is not active.');
        }
        $this->selection->selectForUser($userId, $organizationId);
        $this->audit->record('organization.context_selected', 'organizations', $userId, $organizationId, 'organization', $organizationId);
        return $organization;
    }
}
