<?php

declare(strict_types=1);

define('ATLAS_PLATFORM_DIR', dirname(__DIR__) . '/plugin/atlas-platform/');
require ATLAS_PLATFORM_DIR . 'src/Autoloader.php';
Atlas\Platform\Autoloader::register();

use Atlas\Platform\Core\Audit\AuditRecorder;
use Atlas\Platform\Organizations\Domain\Organization;
use Atlas\Platform\Organizations\Domain\OrganizationPolicy;
use Atlas\Platform\Organizations\Repositories\MembershipRepository;
use Atlas\Platform\Organizations\Repositories\OrganizationRepository;
use Atlas\Platform\Organizations\Services\OrganizationContextService;
use Atlas\Platform\Organizations\Services\OrganizationSelection;

$a = new Organization('550e8400-e29b-41d4-a716-446655440000', 'Clinic A', 'clinic-a', 'active', '', '');
$b = new Organization('6ba7b810-9dad-41d1-80b4-00c04fd430c8', 'Clinic B', 'clinic-b', 'active', '', '');
$organizations = new class([$a->id => $a, $b->id => $b]) implements OrganizationRepository {
    public function __construct(private array $items) {}
    public function findActiveById(string $id): ?Organization { return $this->items[$id] ?? null; }
    public function findActiveByIds(array $ids): array { return array_values(array_intersect_key($this->items, array_flip($ids))); }
};
$memberships = new class($a->id) implements MembershipRepository {
    public function __construct(private string $id) {}
    public function findActiveOrganizationIdsForUser(int $userId): array { return $userId === 7 ? [$this->id] : []; }
    public function userHasActiveMembership(int $userId, string $organizationId): bool { return $userId === 7 && $organizationId === $this->id; }
};
$selection = new class implements OrganizationSelection { public array $values=[]; public function selectedForUser(int $userId): ?string{return $this->values[$userId]??null;} public function selectForUser(int $userId,string $id):void{$this->values[$userId]=$id;} };
$audit = new class implements AuditRecorder { public array $events=[]; public function record(string $event,string $module,int $actorId,?string $organizationId,string $objectType,string $objectId,array $context=[]):void{$this->events[]=compact('event','module','actorId','organizationId','objectType','objectId','context');} };
$service = new OrganizationContextService($memberships, $organizations, $selection, new OrganizationPolicy(), $audit);
$expect = static function(bool $condition,string $message):void{if(!$condition){throw new RuntimeException($message);}echo "PASS: {$message}\n";};
$expect(count($service->availableForUser(7))===1,'available contexts are restricted to active memberships');
$expect($service->select(7,$a->id)->id===$a->id && count($audit->events)===1,'an authorized context selection is persisted and audited');
try{$service->select(7,$b->id);throw new RuntimeException('Expected isolation failure.');}catch(InvalidArgumentException){$expect(true,'a user in organization A cannot select organization B');}
echo "All organization context tests passed.\n";
