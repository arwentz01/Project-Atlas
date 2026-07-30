<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Repositories;

use Atlas\Platform\Core\Logging\Logger;
use Atlas\Platform\Resources\Domain\Citation;
use Atlas\Platform\Resources\Domain\PublishedResource;
use Atlas\Platform\Resources\Domain\Resource;
use Atlas\Platform\Resources\Domain\ResourceVersion;
use JsonException;

final class WordPressResourceRepository implements ResourceRepository
{
    private string $resources;
    private string $versions;
    private string $sources;
    private string $citations;
    public function __construct(private object $database, private Logger $logger)
    {
        $this->resources = $database->prefix . 'atlas_resources';
        $this->versions = $database->prefix . 'atlas_resource_versions';
        $this->sources = $database->prefix . 'atlas_sources';
        $this->citations = $database->prefix . 'atlas_citations';
    }
    public function findPublishedForContext(string $resourceId, ?string $organizationId): ?PublishedResource
    {
        $sql = "SELECT r.id, r.scope, r.organization_id, r.resource_type, r.slug, r.created_at AS resource_created_at, r.updated_at, v.id AS version_id, v.version_number, v.title, v.summary, v.body_json, v.review_status, v.effective_date, v.review_due_date, v.change_summary, v.author_user_id, v.created_at AS version_created_at FROM `{$this->resources}` r INNER JOIN `{$this->versions}` v ON v.id = r.current_version_id WHERE r.id = %s AND r.archived_at IS NULL AND v.review_status = %s AND (r.scope IN ('platform', 'public') OR (r.scope = 'organization' AND r.organization_id = %s)) LIMIT 1";
        $row = $this->database->get_row($this->database->prepare($sql, $resourceId, 'published', $organizationId ?? ''), ARRAY_A);
        if (! is_array($row)) { return null; }
        try { $body = json_decode((string) $row['body_json'], true, 32, JSON_THROW_ON_ERROR); }
        catch (JsonException $exception) { $this->logger->log('error', 'resource.invalid_body', 'A published resource has invalid structured content.', ['resource_id' => $resourceId], 'resources', $exception); return null; }
        if (! is_array($body)) { return null; }
        $resource = new Resource((string) $row['id'], (string) $row['scope'], $row['organization_id'] === null ? null : (string) $row['organization_id'], (string) $row['resource_type'], (string) $row['slug'], (string) $row['resource_created_at'], (string) $row['updated_at']);
        $version = new ResourceVersion((string) $row['version_id'], (string) $row['id'], (int) $row['version_number'], (string) $row['title'], (string) $row['summary'], $body, (string) $row['review_status'], $row['effective_date'] === null ? null : (string) $row['effective_date'], $row['review_due_date'] === null ? null : (string) $row['review_due_date'], (string) $row['change_summary'], (int) $row['author_user_id'], (string) $row['version_created_at']);
        return new PublishedResource($resource, $version, $this->findCitations($version->id));
    }
    /** @return list<Citation> */
    private function findCitations(string $versionId): array
    {
        $sql = "SELECT c.id, s.publisher, s.title AS source_title, s.source_url, s.document_identifier, s.effective_date, c.page_reference, c.section_reference FROM `{$this->citations}` c INNER JOIN `{$this->sources}` s ON s.id = c.source_id WHERE c.resource_version_id = %s ORDER BY c.display_order ASC, c.id ASC";
        $rows = $this->database->get_results($this->database->prepare($sql, $versionId), ARRAY_A);
        if (! is_array($rows)) { return []; }
        return array_map(static fn(array $row): Citation => new Citation((string) $row['id'], (string) $row['publisher'], (string) $row['source_title'], $row['source_url'] === null ? null : (string) $row['source_url'], $row['document_identifier'] === null ? null : (string) $row['document_identifier'], $row['effective_date'] === null ? null : (string) $row['effective_date'], $row['page_reference'] === null ? null : (string) $row['page_reference'], $row['section_reference'] === null ? null : (string) $row['section_reference']), $rows);
    }
}
