<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Logging;

use Throwable;

interface Logger
{
    /** @param array<string, mixed> $context */
    public function log(string $level, string $event, string $message, array $context = [], string $module = 'core', ?Throwable $exception = null): void;
    /** @return list<array<string, mixed>> */
    public function recentErrors(int $limit = 20): array;
}
