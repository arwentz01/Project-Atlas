<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Domain;

final class PublishedResource
{
    /** @param list<Citation> $citations */
    public function __construct(public readonly Resource $resource, public readonly ResourceVersion $version, public readonly array $citations) {}
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['id' => $this->resource->id, 'scope' => $this->resource->scope, 'organization_id' => $this->resource->organizationId, 'type' => $this->resource->type, 'slug' => $this->resource->slug, 'version' => $this->version->version, 'title' => $this->version->title, 'summary' => $this->version->summary, 'body' => $this->version->body, 'review_status' => $this->version->reviewStatus, 'effective_date' => $this->version->effectiveDate, 'review_due_date' => $this->version->reviewDueDate, 'change_summary' => $this->version->changeSummary, 'citations' => array_map(static fn(Citation $citation): array => $citation->toArray(), $this->citations)];
    }
}
