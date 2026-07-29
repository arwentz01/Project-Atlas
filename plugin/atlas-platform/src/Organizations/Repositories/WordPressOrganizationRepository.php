<?php
declare(strict_types=1);

namespace Atlas\Platform\Organizations\Repositories;

use Atlas\Platform\Organizations\Domain\Organization;

final class WordPressOrganizationRepository implements OrganizationRepository
{
    private string $table;
    public function __construct(private object $database) { $this->table = $database->prefix . 'atlas_organizations'; }
    public function findActiveById(string $organizationId): ?Organization
    {
        $row = $this->database->get_row($this->database->prepare("SELECT id, name, slug, status, created_at, updated_at FROM `{$this->table}` WHERE id = %s AND status = %s LIMIT 1", $organizationId, 'active'), ARRAY_A);
        if (! is_array($row)) { return null; }
        return new Organization((string) $row['id'], (string) $row['name'], (string) $row['slug'], (string) $row['status'], (string) $row['created_at'], (string) $row['updated_at']);
    }
    public function findActiveByIds(array $organizationIds): array
    {
        $organizationIds = array_values(array_unique(array_filter($organizationIds, 'is_string')));
        if ($organizationIds === []) { return []; }
        $placeholders = implode(', ', array_fill(0, count($organizationIds), '%s'));
        $arguments = array_merge($organizationIds, ['active']);
        $rows = $this->database->get_results($this->database->prepare("SELECT id, name, slug, status, created_at, updated_at FROM `{$this->table}` WHERE id IN ({$placeholders}) AND status = %s ORDER BY name ASC, id ASC", ...$arguments), ARRAY_A);
        if (! is_array($rows)) { return []; }
        return array_map(static fn(array $row): Organization => new Organization((string) $row['id'], (string) $row['name'], (string) $row['slug'], (string) $row['status'], (string) $row['created_at'], (string) $row['updated_at']), $rows);
    }
}
