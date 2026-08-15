<?php

declare(strict_types=1);

final class AtlasRepository
{
    public function __construct(private PDO $db) {}

    public function organizationsForUser(int $userId): array
    {
        $sql = 'SELECT o.*, m.role, m.id AS membership_id FROM organizations o JOIN memberships m ON m.organization_id = o.id WHERE m.user_id = ? AND m.status = "active" ORDER BY o.name';
        $statement = $this->db->prepare($sql);
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }

    public function organizationForUser(int $organizationId, int $userId): ?array
    {
        $statement = $this->db->prepare('SELECT o.*, m.role, m.id AS membership_id FROM organizations o JOIN memberships m ON m.organization_id = o.id WHERE o.id = ? AND m.user_id = ? AND m.status = "active" LIMIT 1');
        $statement->execute([$organizationId, $userId]);
        return $statement->fetch() ?: null;
    }

    public function createOrganization(int $userId, string $name, string $type, string $locationName, string $timezone): int
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Organization name is required.');
        }
        $this->db->beginTransaction();
        try {
            $slug = $this->uniqueSlug($name);
            $statement = $this->db->prepare('INSERT INTO organizations (name, slug, organization_type, timezone, created_by) VALUES (?, ?, ?, ?, ?)');
            $statement->execute([$name, $slug, $type, $timezone, $userId]);
            $organizationId = (int)$this->db->lastInsertId();
            $this->db->prepare('INSERT INTO memberships (organization_id, user_id, role, status) VALUES (?, ?, "owner", "active")')->execute([$organizationId, $userId]);
            if (trim($locationName) !== '') {
                $this->db->prepare('INSERT INTO locations (organization_id, name, timezone, is_primary) VALUES (?, ?, ?, 1)')->execute([$organizationId, trim($locationName), $timezone]);
            }
            $this->audit($organizationId, $userId, 'organization.created', 'organization', $organizationId, ['name' => $name]);
            $this->db->commit();
            return $organizationId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function overview(int $organizationId): array
    {
        $result = [];
        foreach (['locations', 'departments', 'positions', 'supervisor_groups'] as $table) {
            $statement = $this->db->prepare("SELECT * FROM {$table} WHERE organization_id = ? AND active = 1 ORDER BY name");
            $statement->execute([$organizationId]);
            $result[$table] = $statement->fetchAll();
        }
        $statement = $this->db->prepare('SELECT m.id AS membership_id, m.role, u.id, u.name, u.email, d.name AS department_name, p.name AS position_name, sg.name AS supervisor_group_name FROM memberships m JOIN users u ON u.id = m.user_id LEFT JOIN staff_assignments sa ON sa.membership_id = m.id AND sa.is_primary = 1 LEFT JOIN departments d ON d.id = sa.department_id LEFT JOIN positions p ON p.id = sa.position_id LEFT JOIN supervisor_groups sg ON sg.id = sa.supervisor_group_id WHERE m.organization_id = ? AND m.status = "active" ORDER BY u.name');
        $statement->execute([$organizationId]);
        $result['people'] = $statement->fetchAll();
        $statement = $this->db->prepare('SELECT i.*, d.name AS department_name, p.name AS position_name FROM invitations i LEFT JOIN departments d ON d.id = i.department_id LEFT JOIN positions p ON p.id = i.position_id WHERE i.organization_id = ? AND i.accepted_at IS NULL AND i.expires_at > NOW() ORDER BY i.created_at DESC');
        $statement->execute([$organizationId]);
        $result['invitations'] = $statement->fetchAll();
        return $result;
    }

    public function addLocation(int $organizationId, int $userId, string $name): void
    {
        $this->insertNamed('locations', $organizationId, $name, []);
        $this->audit($organizationId, $userId, 'location.created', 'location', (int)$this->db->lastInsertId(), ['name' => trim($name)]);
    }

    public function addDepartment(int $organizationId, int $userId, string $name, ?int $locationId, string $color): void
    {
        $locationId = $this->ownedId('locations', $organizationId, $locationId);
        $this->insertNamed('departments', $organizationId, $name, ['location_id' => $locationId, 'color' => $color]);
        $this->audit($organizationId, $userId, 'department.created', 'department', (int)$this->db->lastInsertId(), ['name' => trim($name)]);
    }

    public function addPosition(int $organizationId, int $userId, string $name, ?int $departmentId, string $category, string $color): void
    {
        $departmentId = $this->ownedId('departments', $organizationId, $departmentId);
        $this->insertNamed('positions', $organizationId, $name, ['department_id' => $departmentId, 'category' => $category, 'color' => $color]);
        $this->audit($organizationId, $userId, 'position.created', 'position', (int)$this->db->lastInsertId(), ['name' => trim($name)]);
    }

    public function addSupervisorGroup(int $organizationId, int $userId, string $name, ?int $departmentId, array $supervisorMembershipIds): void
    {
        $departmentId = $this->ownedId('departments', $organizationId, $departmentId);
        $this->db->beginTransaction();
        try {
            $this->insertNamed('supervisor_groups', $organizationId, $name, ['department_id' => $departmentId ?: null]);
            $groupId = (int)$this->db->lastInsertId();
            $membershipCheck = $this->db->prepare('SELECT id FROM memberships WHERE id = ? AND organization_id = ? AND status = "active"');
            $insert = $this->db->prepare('INSERT IGNORE INTO supervisor_group_members (supervisor_group_id, membership_id) VALUES (?, ?)');
            foreach ($supervisorMembershipIds as $membershipId) {
                $membershipCheck->execute([(int)$membershipId, $organizationId]);
                if ($membershipCheck->fetchColumn()) {
                    $insert->execute([$groupId, (int)$membershipId]);
                }
            }
            if ($departmentId) {
                $this->db->prepare('UPDATE departments SET default_supervisor_group_id = ? WHERE id = ? AND organization_id = ?')->execute([$groupId, $departmentId, $organizationId]);
                $this->db->prepare('UPDATE staff_assignments SET supervisor_group_id = ? WHERE department_id = ? AND organization_id = ?')->execute([$groupId, $departmentId, $organizationId]);
            }
            $this->audit($organizationId, $userId, 'supervisor_group.created', 'supervisor_group', $groupId, ['name' => trim($name)]);
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function createInvitation(int $organizationId, int $userId, array $input, string $baseUrl): string
    {
        $email = strtolower(trim((string)($input['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Enter a valid email address for the invitation.');
        }
        $token = bin2hex(random_bytes(24));
        $departmentId = $this->ownedId('departments', $organizationId, !empty($input['department_id']) ? (int)$input['department_id'] : null);
        $positionId = $this->ownedId('positions', $organizationId, !empty($input['position_id']) ? (int)$input['position_id'] : null);
        $locationId = $this->ownedId('locations', $organizationId, !empty($input['location_id']) ? (int)$input['location_id'] : null);
        $statement = $this->db->prepare('INSERT INTO invitations (organization_id, email, role, department_id, position_id, location_id, token_hash, invited_by, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 14 DAY))');
        $statement->execute([
            $organizationId,
            $email,
            $input['role'] ?? 'member',
            $departmentId,
            $positionId,
            $locationId,
            hash('sha256', $token),
            $userId,
        ]);
        $invitationId = (int)$this->db->lastInsertId();
        $this->audit($organizationId, $userId, 'invitation.created', 'invitation', $invitationId, ['email' => $email]);
        return rtrim($baseUrl, '/') . '/invite?token=' . $token;
    }

    public function invitation(string $token): ?array
    {
        $statement = $this->db->prepare('SELECT i.*, o.name AS organization_name FROM invitations i JOIN organizations o ON o.id = i.organization_id WHERE i.token_hash = ? AND i.accepted_at IS NULL AND i.expires_at > NOW() LIMIT 1');
        $statement->execute([hash('sha256', $token)]);
        return $statement->fetch() ?: null;
    }

    public function acceptInvitation(string $token, int $userId): int
    {
        $invitation = $this->invitation($token);
        if (!$invitation) {
            throw new InvalidArgumentException('This invitation is invalid or has expired.');
        }
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare('INSERT INTO memberships (organization_id, user_id, role, status) VALUES (?, ?, ?, "active") ON DUPLICATE KEY UPDATE role = VALUES(role), status = "active"');
            $statement->execute([$invitation['organization_id'], $userId, $invitation['role']]);
            $membership = $this->db->prepare('SELECT id FROM memberships WHERE organization_id = ? AND user_id = ?');
            $membership->execute([$invitation['organization_id'], $userId]);
            $membershipId = (int)$membership->fetchColumn();
            $groupStatement = $this->db->prepare('SELECT default_supervisor_group_id FROM departments WHERE id = ? AND organization_id = ?');
            $groupStatement->execute([$invitation['department_id'], $invitation['organization_id']]);
            $groupId = $groupStatement->fetchColumn() ?: null;
            $assignment = $this->db->prepare('INSERT INTO staff_assignments (organization_id, membership_id, location_id, department_id, position_id, supervisor_group_id, is_primary) VALUES (?, ?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE location_id = VALUES(location_id), department_id = VALUES(department_id), position_id = VALUES(position_id), supervisor_group_id = VALUES(supervisor_group_id)');
            $assignment->execute([$invitation['organization_id'], $membershipId, $invitation['location_id'], $invitation['department_id'], $invitation['position_id'], $groupId]);
            $this->db->prepare('UPDATE invitations SET accepted_at = NOW(), accepted_by = ? WHERE id = ?')->execute([$userId, $invitation['id']]);
            $this->audit((int)$invitation['organization_id'], $userId, 'invitation.accepted', 'membership', $membershipId, []);
            $this->db->commit();
            return (int)$invitation['organization_id'];
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    private function insertNamed(string $table, int $organizationId, string $name, array $extra): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('A name is required.');
        }
        $columns = array_merge(['organization_id', 'name'], array_keys($extra));
        $values = array_merge([$organizationId, $name], array_values($extra));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->db->prepare('INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')')->execute($values);
    }

    private function uniqueSlug(string $name): string
    {
        $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)) ?? '', '-') ?: 'organization';
        $slug = $base;
        $counter = 2;
        $statement = $this->db->prepare('SELECT COUNT(*) FROM organizations WHERE slug = ?');
        while (true) {
            $statement->execute([$slug]);
            if ((int)$statement->fetchColumn() === 0) return $slug;
            $slug = $base . '-' . $counter++;
        }
    }

    private function ownedId(string $table, int $organizationId, ?int $id): ?int
    {
        if (!$id) return null;
        if (!in_array($table, ['locations', 'departments', 'positions'], true)) {
            throw new LogicException('Invalid organization resource type.');
        }
        $statement = $this->db->prepare("SELECT id FROM {$table} WHERE id = ? AND organization_id = ? AND active = 1");
        $statement->execute([$id, $organizationId]);
        if (!$statement->fetchColumn()) {
            throw new InvalidArgumentException('One of the selected organization resources is unavailable.');
        }
        return $id;
    }

    private function audit(int $organizationId, int $userId, string $action, string $entityType, int $entityId, array $metadata): void
    {
        $statement = $this->db->prepare('INSERT INTO audit_logs (organization_id, user_id, action, entity_type, entity_id, metadata_json, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([$organizationId, $userId, $action, $entityType, $entityId, json_encode($metadata), $_SERVER['REMOTE_ADDR'] ?? null]);
    }
}
