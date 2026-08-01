<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Search;

use Atlas\Platform\Resources\Domain\ResourcePolicy;
use InvalidArgumentException;

final class ResourceSearchService
{
    public function __construct(private ResourceSearchRepository $repository, private ResourcePolicy $policy) {}
    public function search(SearchCriteria $criteria, ?string $organizationId): SearchPage
    {
        if ($criteria->type !== null && ! $this->policy->validType($criteria->type)) { throw new InvalidArgumentException('Unsupported resource type filter.'); }
        return $this->repository->searchPublished($criteria, $organizationId);
    }
}
