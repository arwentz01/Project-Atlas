<?php
declare(strict_types=1);

namespace Atlas\Platform\Organizations\Repositories;

use RuntimeException;

final class WordPressOrganizationAdministrationRepository implements OrganizationAdministrationRepository
{
    private string $memberships;
    private string $invitations;
    public function __construct(private object $db)
    {
        $this->memberships = $db->prefix . 'atlas_organization_memberships';
        $this->invitations = $db->prefix . 'atlas_organization_invitations';
    }
    public function members(string $organizationId): array
    {
        $rows = $this->db->get_results($this->db->prepare("SELECT m.id,m.user_id,m.status,m.roles_json,m.created_at,u.user_email,u.display_name FROM `{$this->memberships}` m INNER JOIN `{$this->db->users}` u ON u.ID=m.user_id WHERE m.organization_id=%s AND m.status='active' ORDER BY u.display_name,u.user_email", $organizationId), ARRAY_A);
        if (! is_array($rows)) { return []; }
        return array_map(static function (array $row): array {
            $roles = json_decode((string) $row['roles_json'], true);
            $row['roles'] = is_array($roles) ? array_values(array_map('strval', $roles)) : [];
            unset($row['roles_json']);
            return $row;
        }, $rows);
    }
    public function invitations(string $organizationId): array
    {
        $rows = $this->db->get_results($this->db->prepare("SELECT id,email,roles_json,status,expires_at,created_at FROM `{$this->invitations}` WHERE organization_id=%s AND status='pending' ORDER BY created_at DESC", $organizationId), ARRAY_A);
        return is_array($rows) ? $rows : [];
    }
    public function invite(string $organizationId, string $email, array $roles, int $actorId): string
    {
        $token = bin2hex(random_bytes(32));
        $now = current_time('mysql', true);
        $ok = $this->db->insert($this->invitations, ['id'=>wp_generate_uuid4(),'organization_id'=>$organizationId,'email'=>$email,'roles_json'=>wp_json_encode($roles),'token_hash'=>hash('sha256',$token),'status'=>'pending','invited_by'=>$actorId,'accepted_by'=>null,'expires_at'=>gmdate('Y-m-d H:i:s',time()+7*DAY_IN_SECONDS),'created_at'=>$now,'updated_at'=>$now]);
        if ($ok === false) { throw new RuntimeException('The organization invitation could not be saved.'); }
        return $token;
    }
    public function accept(string $token, int $userId, string $email): bool
    {
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM `{$this->invitations}` WHERE token_hash=%s AND status='pending' AND expires_at>=%s LIMIT 1", hash('sha256',$token), current_time('mysql',true)), ARRAY_A);
        if (! is_array($row) || ! hash_equals(strtolower((string)$row['email']), strtolower($email))) { return false; }
        $existing = $this->db->get_var($this->db->prepare("SELECT id FROM `{$this->memberships}` WHERE organization_id=%s AND user_id=%d LIMIT 1", $row['organization_id'], $userId));
        $now = current_time('mysql', true);
        if (is_string($existing) && $existing !== '') {
            $saved = $this->db->update($this->memberships, ['status'=>'active','roles_json'=>$row['roles_json'],'updated_at'=>$now], ['id'=>$existing]);
        } else {
            $saved = $this->db->insert($this->memberships, ['id'=>wp_generate_uuid4(),'organization_id'=>$row['organization_id'],'user_id'=>$userId,'status'=>'active','roles_json'=>$row['roles_json'],'created_at'=>$now,'updated_at'=>$now]);
        }
        if ($saved === false) { return false; }
        return $this->db->update($this->invitations, ['status'=>'accepted','accepted_by'=>$userId,'updated_at'=>$now], ['id'=>$row['id'],'status'=>'pending']) !== false;
    }
    public function revoke(string $organizationId, string $invitationId): bool
    {
        return $this->db->update($this->invitations, ['status'=>'revoked','updated_at'=>current_time('mysql',true)], ['id'=>$invitationId,'organization_id'=>$organizationId,'status'=>'pending']) === 1;
    }
    public function updateRoles(string $organizationId, string $membershipId, array $roles): bool
    {
        return $this->db->update($this->memberships, ['roles_json'=>wp_json_encode($roles),'updated_at'=>current_time('mysql',true)], ['id'=>$membershipId,'organization_id'=>$organizationId,'status'=>'active']) === 1;
    }
    public function remove(string $organizationId, string $membershipId, int $actorId): bool
    {
        $userId = (int) $this->db->get_var($this->db->prepare("SELECT user_id FROM `{$this->memberships}` WHERE id=%s AND organization_id=%s AND status='active'", $membershipId, $organizationId));
        if ($userId < 1 || $userId === $actorId) { return false; }
        $count = (int) $this->db->get_var($this->db->prepare("SELECT COUNT(*) FROM `{$this->memberships}` WHERE organization_id=%s AND status='active'", $organizationId));
        if ($count <= 1) { return false; }
        return $this->db->update($this->memberships, ['status'=>'removed','updated_at'=>current_time('mysql',true)], ['id'=>$membershipId,'organization_id'=>$organizationId]) === 1;
    }
}
