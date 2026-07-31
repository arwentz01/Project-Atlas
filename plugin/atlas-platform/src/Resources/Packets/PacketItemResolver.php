<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Packets;

interface PacketItemResolver
{
    /** @return array<string,mixed> */
    public function resolve(array $item, ?string $organizationId): array;
}
