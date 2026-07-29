<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Migrations;

interface Lock
{
    public function acquire(): ?string;
    public function release(string $token): bool;
    /** @return array<string, mixed> */
    public function status(): array;
    public function clearStale(): bool;
}
