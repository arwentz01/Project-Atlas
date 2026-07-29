<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Domain;

final class Resource
{
    public function __construct(public readonly string $id, public readonly string $scope, public readonly ?string $organizationId, public readonly string $type, public readonly string $slug, public readonly string $createdAt, public readonly string $updatedAt) {}
}
