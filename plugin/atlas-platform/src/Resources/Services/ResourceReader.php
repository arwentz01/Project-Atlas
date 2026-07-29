<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Services;

use Atlas\Platform\Resources\Domain\PublishedResource;
use Atlas\Platform\Resources\Repositories\ResourceRepository;

final class ResourceReader
{
    public function __construct(private ResourceRepository $resources) {}
    public function findPublished(string $resourceId, ?string $organizationId): ?PublishedResource { return $this->resources->findPublishedForContext($resourceId, $organizationId); }
}
