<?php
declare(strict_types=1);

namespace Atlas\Platform\Organizations\Services;

use Atlas\Platform\Organizations\Repositories\MembershipRepository;

final class OrganizationAuthorization
{
    public function __construct(private MembershipRepository $memberships) {}
    public function userCanAccess(int $userId, string $organizationId, bool $isPlatformAdministrator = false): bool
    {
        if ($userId <= 0 || $organizationId === '') { return false; }
        return $isPlatformAdministrator || $this->memberships->userHasActiveMembership($userId, $organizationId);
    }
}
