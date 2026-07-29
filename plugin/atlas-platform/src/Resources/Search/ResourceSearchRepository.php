<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Search;

interface ResourceSearchRepository { public function searchPublished(SearchCriteria $criteria, ?string $organizationId): SearchPage; }
