<?php
declare(strict_types=1);

namespace Atlas\Platform\Organizations\Services;

use Atlas\Platform\Core\Audit\AuditRecorder;
use Atlas\Platform\Organizations\Repositories\OrganizationAdministrationRepository;
use InvalidArgumentException;

final class OrganizationAdministrationService
{
    public const ROLES = ['organization_admin','editor','reviewer','publisher','member'];
    public function __construct(private OrganizationAdministrationRepository $repository, private AuditRecorder $audit) {}
    public function members(string $organizationId): array { return $this->repository->members($organizationId); }
    public function invitations(string $organizationId): array { return $this->repository->invitations($organizationId); }
    public function invite(string $organizationId, string $email, array $roles, int $actorId): string
    {
        $email = strtolower(trim($email)); $roles = $this->roles($roles);
        if (! is_email($email)) { throw new InvalidArgumentException('Enter a valid email address.'); }
        $token = $this->repository->invite($organizationId, $email, $roles, $actorId);
        $this->audit->record('organization.member_invited','organizations',$actorId,$organizationId,'organization',$organizationId,['email_hash'=>hash('sha256',$email),'roles'=>$roles]);
        return $token;
    }
    public function accept(string $token, int $userId, string $email): bool
    {
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1 || $userId < 1) { return false; }
        $accepted = $this->repository->accept($token, $userId, $email);
        if ($accepted) { $this->audit->record('organization.invitation_accepted','organizations',$userId,null,'user',(string)$userId); }
        return $accepted;
    }
    public function revoke(string $organizationId, string $invitationId, int $actorId): bool
    {
        $changed=$this->repository->revoke($organizationId,$invitationId);
        if($changed){$this->audit->record('organization.invitation_revoked','organizations',$actorId,$organizationId,'invitation',$invitationId);} return $changed;
    }
    public function updateRoles(string $organizationId,string $membershipId,array $roles,int $actorId):bool
    {
        $roles=$this->roles($roles);$changed=$this->repository->updateRoles($organizationId,$membershipId,$roles);
        if($changed){$this->audit->record('organization.member_roles_updated','organizations',$actorId,$organizationId,'membership',$membershipId,['roles'=>$roles]);}return $changed;
    }
    public function remove(string $organizationId,string $membershipId,int $actorId):bool
    {
        $changed=$this->repository->remove($organizationId,$membershipId,$actorId);
        if($changed){$this->audit->record('organization.member_removed','organizations',$actorId,$organizationId,'membership',$membershipId);}return $changed;
    }
    private function roles(array $roles):array
    {
        $roles=array_values(array_unique(array_intersect(self::ROLES,array_map('sanitize_key',$roles))));
        if($roles===[]){throw new InvalidArgumentException('Select at least one organization role.');}return $roles;
    }
}
