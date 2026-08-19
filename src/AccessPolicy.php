<?php
declare(strict_types=1);

final class AccessPolicy
{
    public function __construct(private PDO $db) {}

    public function allows(array $organization, string $capability, ?int $locationId=null, ?int $departmentId=null): bool
    {
        if(in_array($organization['role'],['owner','admin'],true)) return true;
        $membershipId=(int)$organization['membership_id'];
        $column=['schedule'=>'can_schedule','approve'=>'can_approve','payroll'=>'can_manage_payroll','credentials'=>'can_manage_credentials'][$capability]??null;
        if(!$column) return false;
        $q=$this->db->prepare("SELECT {$column},read_only FROM membership_permissions WHERE membership_id=? AND organization_id=?");
        $q->execute([$membershipId,(int)$organization['id']]);$permission=$q->fetch();
        $allowed=$permission&&!$permission['read_only']&&(bool)$permission[$column];
        if(!$allowed){$q=$this->db->prepare('SELECT COUNT(*) FROM access_delegations WHERE membership_id=? AND organization_id=? AND capability=? AND revoked_at IS NULL AND NOW() BETWEEN starts_at AND expires_at');$q->execute([$membershipId,(int)$organization['id'],$capability]);$allowed=(bool)$q->fetchColumn();}
        if(!$allowed)return false;
        foreach([['location',$locationId],['department',$departmentId]] as [$type,$resource]){if(!$resource)continue;$q=$this->db->prepare('SELECT COUNT(*) FROM membership_scopes WHERE membership_id=? AND scope_type=?');$q->execute([$membershipId,$type]);$scoped=(int)$q->fetchColumn();if($scoped){$q=$this->db->prepare('SELECT COUNT(*) FROM membership_scopes WHERE membership_id=? AND scope_type=? AND resource_id=?');$q->execute([$membershipId,$type,$resource]);if(!$q->fetchColumn())return false;}}
        return true;
    }

    public function require(array $organization,string $capability,?int $locationId=null,?int $departmentId=null): void
    {
        if(!$this->allows($organization,$capability,$locationId,$departmentId))throw new InvalidArgumentException('Your role, delegated access, or resource scope does not allow this action.');
    }
}
