<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Domain;

final class ResourceVersion
{
    /** @param array<string, mixed> $body */
    public function __construct(public readonly string $id, public readonly string $resourceId, public readonly int $version, public readonly string $title, public readonly string $summary, public readonly array $body, public readonly string $reviewStatus, public readonly ?string $effectiveDate, public readonly ?string $reviewDueDate, public readonly string $changeSummary, public readonly int $authorUserId, public readonly string $createdAt) {}
}
