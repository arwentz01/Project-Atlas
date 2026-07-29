<?php
declare(strict_types=1);

define('ATLAS_PLATFORM_DIR', dirname(__DIR__) . '/plugin/atlas-platform/');
require ATLAS_PLATFORM_DIR . 'src/Autoloader.php';
Atlas\Platform\Autoloader::register();

use Atlas\Platform\Organizations\Domain\Organization;
use Atlas\Platform\Organizations\Domain\OrganizationPolicy;
use Atlas\Platform\Organizations\Repositories\MembershipRepository;
use Atlas\Platform\Organizations\Repositories\OrganizationRepository;
use Atlas\Platform\Organizations\Services\DefaultCurrentOrganizationResolver;
use Atlas\Platform\Organizations\Services\OrganizationAuthorization;
use Atlas\Platform\Organizations\Services\OrganizationSelection;

final class MemoryOrganizations implements OrganizationRepository
{
    /** @param array<string, Organization> $organizations */ public function __construct(private array $organizations) {}
    public function findActiveById(string $organizationId): ?Organization { $organization = $this->organizations[$organizationId] ?? null; return $organization?->status === 'active' ? $organization : null; }
    public function findActiveByIds(array $organizationIds): array { return array_values(array_filter(array_map(fn(string $id): ?Organization => $this->findActiveById($id), $organizationIds))); }
}
final class MemorySelection implements OrganizationSelection { public array $selected = []; public function selectedForUser(int $userId): ?string { return $this->selected[$userId] ?? null; } public function selectForUser(int $userId, string $organizationId): void { $this->selected[$userId] = $organizationId; } }
final class MemoryMemberships implements MembershipRepository
{
    /** @param array<int, list<string>> $memberships */ public function __construct(private array $memberships) {}
    public function findActiveOrganizationIdsForUser(int $userId): array { return $this->memberships[$userId] ?? []; }
    public function userHasActiveMembership(int $userId, string $organizationId): bool { return in_array($organizationId, $this->memberships[$userId] ?? [], true); }
}
function org_expect(bool $condition, string $message): void { if (! $condition) { throw new RuntimeException($message); } echo "PASS: {$message}\n"; }

$a = new Organization('550e8400-e29b-41d4-a716-446655440000', 'Clinic A', 'clinic-a', 'active', '2026-01-01 00:00:00', '2026-01-01 00:00:00');
$b = new Organization('6ba7b810-9dad-41d1-80b4-00c04fd430c8', 'Clinic B', 'clinic-b', 'active', '2026-01-01 00:00:00', '2026-01-01 00:00:00');
$suspended = new Organization('6ba7b811-9dad-41d1-80b4-00c04fd430c8', 'Suspended', 'suspended', 'suspended', '2026-01-01 00:00:00', '2026-01-01 00:00:00');
$organizations = new MemoryOrganizations([$a->id => $a, $b->id => $b, $suspended->id => $suspended]);
$memberships = new MemoryMemberships([10 => [$a->id], 20 => [$b->id], 30 => [$a->id, $b->id], 40 => [$suspended->id]]);
$selection = new MemorySelection();
$resolver = new DefaultCurrentOrganizationResolver($memberships, $organizations, $selection);

org_expect($resolver->resolveForUser(10)?->id === $a->id, 'a user with one active membership resolves that organization');
org_expect($resolver->resolveForUser(30) === null, 'a user with multiple memberships requires explicit context selection');
$selection->selectForUser(30, $b->id);
org_expect($resolver->resolveForUser(30)?->id === $b->id, 'an explicitly selected active membership becomes current context');
$selection->selectForUser(30, $suspended->id);
org_expect($resolver->resolveForUser(30) === null, 'a selection outside active memberships is ignored');
org_expect($resolver->resolveForUser(40) === null, 'a suspended organization cannot become current context');
org_expect($resolver->resolveForUser(0) === null, 'an unauthenticated user has no organization context');

$authorization = new OrganizationAuthorization($memberships);
org_expect($authorization->userCanAccess(10, $a->id), 'membership authorizes access to the owned organization');
org_expect(! $authorization->userCanAccess(10, $b->id), 'membership in organization A does not authorize organization B');
org_expect($authorization->userCanAccess(10, $b->id, true), 'an explicit platform-administrator decision can authorize cross-organization access');
org_expect(! $authorization->userCanAccess(0, $a->id, true), 'platform authority never authenticates an absent user');

$policy = new OrganizationPolicy();
org_expect($policy->isValidIdentifier($a->id) && ! $policy->isValidIdentifier('42'), 'organization identifiers require UUID version 4 syntax');
org_expect($policy->isValidSlug('clinic-a') && ! $policy->isValidSlug('../clinic'), 'organization slugs use a bounded canonical format');
org_expect($policy->isValidStatus('active') && ! $policy->isValidStatus('deleted'), 'organization statuses use the centralized policy');

echo "All organization foundation tests passed.\n";
