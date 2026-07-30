<?php
declare(strict_types=1);

namespace Atlas\Platform\Organizations\Repositories;

interface OrganizationAdministrationRepository
{
    /** @return list<array<string,mixed>> */
    public function members(string $organizationId): array;
    /** @return list<array<string,mixed>> */
    public function invitations(string $organizationId): array;
    public function invite(string $organizationId, string $email, array $roles, int $actorId): string;
    public function accept(string $token, int $userId, string $email): bool;
    public function revoke(string $organizationId, string $invitationId): bool;
    public function updateRoles(string $organizationId, string $membershipId, array $roles): bool;
    public function remove(string $organizationId, string $membershipId, int $actorId): bool;
}
