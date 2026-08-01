<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Packets;

use Atlas\Platform\Resources\Presentation\StructuredContentRenderer;
use Atlas\Platform\Resources\Services\ResourceReader;
use Atlas\Platform\Resources\Sources\SourceWorkspaceRepository;

final class DefaultPacketItemResolver implements PacketItemResolver
{
    public function __construct(private ResourceReader $resources, private SourceWorkspaceRepository $sources, private StructuredContentRenderer $renderer) {}

    public function resolve(array $item, ?string $organizationId): array
    {
        $type = (string) ($item['type'] ?? $item['item_type'] ?? 'resource');
        $id = (string) ($item['item_id'] ?? $item['id'] ?? '');
        $fallbackTitle = (string) (($item['title'] ?? '') !== '' ? $item['title'] : $id);
        if ($type === 'resource') {
            $resource = $this->resources->findPublished($id, $organizationId);
            if ($resource === null) { return $this->unresolved($item, $fallbackTitle, 'Resource unavailable'); }
            return [
                'id' => (string) ($item['id'] ?? ''),
                'type' => 'resource',
                'item_id' => $id,
                'title' => $resource->version->title,
                'summary' => $resource->version->summary,
                'html' => $this->renderer->render($resource->version->body),
                'notes' => (string) ($item['notes'] ?? ''),
                'status' => 'resolved',
                'provenance' => array_map(static fn($citation): array => $citation->toArray(), $resource->citations),
            ];
        }
        if ($type === 'requirement') {
            return $this->unresolved($item, $fallbackTitle, 'Payer requirements are internal documentation guidance and are not rendered in patient packets.');
        }
        return [
            'id' => (string) ($item['id'] ?? ''),
            'type' => 'instruction',
            'item_id' => $id,
            'title' => $fallbackTitle,
            'summary' => '',
            'html' => '<p>' . esc_html((string) ($item['notes'] ?? '')) . '</p>',
            'notes' => '',
            'status' => 'authored',
            'provenance' => [],
        ];
    }

    private function unresolved(array $item, string $title, string $message): array
    {
        return [
            'id' => (string) ($item['id'] ?? ''),
            'type' => (string) ($item['type'] ?? $item['item_type'] ?? 'resource'),
            'item_id' => (string) ($item['item_id'] ?? $item['id'] ?? ''),
            'title' => $title,
            'summary' => $message,
            'html' => '',
            'notes' => (string) ($item['notes'] ?? ''),
            'status' => 'unresolved',
            'provenance' => [],
        ];
    }

    /** @return list<array{label:string,value:string}> */
    private function requirementProvenance(array $requirement, ?string $organizationId): array
    {
        $candidateId = (string) ($requirement['source_candidate_id'] ?? '');
        if ($candidateId === '') { return []; }
        $source = $this->sources->findRequirementSource($candidateId, $organizationId);
        if ($source === null) {
            return [['label' => __('Source candidate', 'atlas-platform'), 'value' => $candidateId]];
        }
        $out = [];
        $document = trim((string) ($source['publisher'] ?? '') . ' - ' . (string) ($source['document_title'] ?? ''));
        if ($document !== '-') { $out[] = ['label' => __('Source document', 'atlas-platform'), 'value' => $document]; }
        $location = $this->sourceLocation($source);
        if ($location !== '') { $out[] = ['label' => __('Source location', 'atlas-platform'), 'value' => $location]; }
        $dates = $this->sourceDates($source);
        if ($dates !== '') { $out[] = ['label' => __('Source dates', 'atlas-platform'), 'value' => $dates]; }
        $excerpt = trim((string) ($source['text_excerpt'] ?? ''));
        if ($excerpt !== '') { $out[] = ['label' => __('Source excerpt', 'atlas-platform'), 'value' => substr($excerpt, 0, 280)]; }
        $statement = trim((string) ($source['statement'] ?? ''));
        if ($statement !== '') { $out[] = ['label' => __('Reviewed extraction', 'atlas-platform'), 'value' => substr($statement, 0, 220)]; }
        return $out;
    }

    private function sourceLocation(array $source): string
    {
        $parts = [];
        $page = (int) ($source['page_number'] ?? 0);
        if ($page > 0) { $parts[] = sprintf(__('page %d', 'atlas-platform'), $page); }
        $section = trim((string) ($source['section_label'] ?? ''));
        if ($section !== '') { $parts[] = $section; }
        $anchor = trim((string) ($source['anchor'] ?? ''));
        if ($anchor !== '') { $parts[] = '#' . $anchor; }
        return implode(' · ', $parts);
    }

    private function sourceDates(array $source): string
    {
        $parts = [];
        $effective = trim((string) ($source['effective_date'] ?? ''));
        if ($effective !== '') { $parts[] = sprintf(__('effective %s', 'atlas-platform'), $effective); }
        $retrieved = trim((string) ($source['retrieved_at'] ?? ''));
        if ($retrieved !== '') { $parts[] = sprintf(__('retrieved %s', 'atlas-platform'), substr($retrieved, 0, 10)); }
        return implode(' · ', $parts);
    }
}
