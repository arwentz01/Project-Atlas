<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Repositories;

use Atlas\Platform\Resources\Domain\PublishedResource;

interface ResourceRepository { public function findPublishedForContext(string $resourceId, ?string $organizationId): ?PublishedResource; }
