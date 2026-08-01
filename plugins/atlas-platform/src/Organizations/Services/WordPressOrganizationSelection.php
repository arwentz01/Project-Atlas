<?php

declare(strict_types=1);

namespace Atlas\Platform\Organizations\Services;

final class WordPressOrganizationSelection implements OrganizationSelection
{
    private const META_KEY = 'atlas_current_organization_id';

    public function selectedForUser(int $userId): ?string
    {
        $selected = get_user_meta($userId, self::META_KEY, true);
        return is_string($selected) && $selected !== '' ? $selected : null;
    }

    public function selectForUser(int $userId, string $organizationId): void
    {
        update_user_meta($userId, self::META_KEY, $organizationId);
    }
}
