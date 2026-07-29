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
}
