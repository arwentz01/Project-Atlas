<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Search;

final class SearchResult
{
    public function __construct(public readonly string $id, public readonly string $title, public readonly string $summary, public readonly string $type, public readonly string $scope, public readonly string $reviewStatus, public readonly ?string $effectiveDate, public readonly ?string $reviewDueDate, public readonly ?string $sourcePublisher, public readonly ?string $sourceTitle) {}
    /** @return array<string, string|null> */
    public function toArray(): array { return ['id'=>$this->id,'title'=>$this->title,'summary'=>$this->summary,'type'=>$this->type,'scope'=>$this->scope,'review_status'=>$this->reviewStatus,'effective_date'=>$this->effectiveDate,'review_due_date'=>$this->reviewDueDate,'source_publisher'=>$this->sourcePublisher,'source_title'=>$this->sourceTitle]; }
}
