<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Domain;

final class Citation
{
    public function __construct(public readonly string $id, public readonly string $publisher, public readonly string $sourceTitle, public readonly ?string $sourceUrl, public readonly ?string $documentIdentifier, public readonly ?string $effectiveDate, public readonly ?string $page, public readonly ?string $section) {}
    /** @return array<string, string|null> */
    public function toArray(): array { return ['id' => $this->id, 'publisher' => $this->publisher, 'source_title' => $this->sourceTitle, 'source_url' => $this->sourceUrl, 'document_identifier' => $this->documentIdentifier, 'effective_date' => $this->effectiveDate, 'page' => $this->page, 'section' => $this->section]; }
}
