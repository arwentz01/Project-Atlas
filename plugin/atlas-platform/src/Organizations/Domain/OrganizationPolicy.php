<?php
declare(strict_types=1);

namespace Atlas\Platform\Organizations\Domain;

final class OrganizationPolicy
{
    public const ACTIVE = 'active';
    public const SUSPENDED = 'suspended';
    public const ARCHIVED = 'archived';
    private const STATUSES = [self::ACTIVE, self::SUSPENDED, self::ARCHIVED];

    public function isValidStatus(string $status): bool { return in_array($status, self::STATUSES, true); }
    public function isValidIdentifier(string $identifier): bool { return preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/', strtolower($identifier)) === 1; }
    public function isValidSlug(string $slug): bool { return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1 && strlen($slug) <= 191; }
}
