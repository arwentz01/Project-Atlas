<?php
declare(strict_types=1);

namespace Atlas\Platform\Organizations\Services;

use Atlas\Platform\Organizations\Domain\Organization;

interface CurrentOrganizationResolver { public function resolveForUser(int $userId): ?Organization; }
