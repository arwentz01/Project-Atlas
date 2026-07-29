<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Search;

use InvalidArgumentException;

final class SearchCriteria
{
    public function __construct(public readonly string $query, public readonly ?string $type, public readonly int $page, public readonly int $perPage)
    {
        if (strlen($query) > 100) { throw new InvalidArgumentException('Search query exceeds 100 characters.'); }
        if ($page < 1 || $page > 100) { throw new InvalidArgumentException('Search page must be between 1 and 100.'); }
        if ($perPage < 1 || $perPage > 50) { throw new InvalidArgumentException('Search page size must be between 1 and 50.'); }
    }
    public static function normalize(string $query, ?string $type, int $page = 1, int $perPage = 20): self
    {
        $query = trim(preg_replace('/\s+/', ' ', $query) ?? $query);
        $type = $type === null || trim($type) === '' ? null : strtolower(trim($type));
        return new self($query, $type, $page, $perPage);
    }
}
