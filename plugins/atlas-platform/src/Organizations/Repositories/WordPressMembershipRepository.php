<?php
declare(strict_types=1);

namespace Atlas\Platform\Organizations\Repositories;

final class WordPressMembershipRepository implements MembershipRepository
{
    private string $table;
    public function __construct(private object $database) { $this->table = $database->prefix . 'atlas_organization_memberships'; }
    public function findActiveOrganizationIdsForUser(int $userId): array
    {
        $ids = $this->database->get_col($this->database->prepare("SELECT organization_id FROM `{$this->table}` WHERE user_id = %d AND status = %s ORDER BY created_at ASC, organization_id ASC", $userId, 'active'));
        return is_array($ids) ? array_values(array_map('strval', $ids)) : [];
    }
    public function userHasActiveMembership(int $userId, string $organizationId): bool
    {
        $found = $this->database->get_var($this->database->prepare("SELECT id FROM `{$this->table}` WHERE user_id = %d AND organization_id = %s AND status = %s LIMIT 1", $userId, $organizationId, 'active'));
        return is_string($found) && $found !== '';
    }
}
