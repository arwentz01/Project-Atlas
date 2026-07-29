<?php
declare(strict_types=1);

namespace Atlas\Platform\Preview;

final class PreviewService
{
    public function __construct(private PreviewResourceRepository $resources) {}

    /** @return array{query: string, resources: list<array<string, string>>, total: int} */
    public function home(string $query): array
    {
        $normalized = trim($query);
        $resources = $this->resources->search($normalized);

        return [
            'query' => $normalized,
            'resources' => $resources,
            'total' => count($resources),
        ];
    }
}
