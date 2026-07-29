<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Search;

final class SearchPage
{
    /** @param list<SearchResult> $results */
    public function __construct(public readonly array $results, public readonly int $page, public readonly int $perPage, public readonly bool $hasMore) {}
    /** @return array<string, mixed> */
    public function toArray(): array { return ['items'=>array_map(static fn(SearchResult $result): array=>$result->toArray(),$this->results),'page'=>$this->page,'per_page'=>$this->perPage,'has_more'=>$this->hasMore]; }
}
