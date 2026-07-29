<?php
declare(strict_types=1);

namespace Atlas\Platform\Organizations\Domain;

final class Organization
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    /** @return array<string, string> */
    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'slug' => $this->slug, 'status' => $this->status, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt];
    }
}
