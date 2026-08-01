<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Editorial;

use JsonException;
use RuntimeException;

final class WordPressEditorialRepository implements EditorialRepository
{
    private string $resources;private string $versions;private string $operations;private string $audit;
    public function __construct(private object $db,private EditorialTransitionPolicy $policy){$this->resources=$db->prefix.'atlas_resources';$this->versions=$db->prefix.'atlas_resource_versions';$this->operations=$db->prefix.'atlas_operations';$this->audit=$db->prefix.'atlas_audit_events';}
    public function transition(string $versionId,string $to,int $actorUserId,?string $organizationId,bool $allowPlatform,string $idempotencyKey):?EditorialResult
    {
        $existing=$this->existing($idempotencyKey);if($existing!==null){return $this->matchingReplay($existing,$versionId,$to);}
        if($this->db->query('START TRANSACTION')===false){throw new RuntimeException('Unable to start the editorial transaction.');}
        try{
            $scope=$allowPlatform?"(r.scope IN ('platform','public') OR (r.scope='organization' AND r.organization_id=%s))":"(r.scope='organization' AND r.organization_id=%s)";
            $row=$this->db->get_row($this->db->prepare("SELECT v.id,v.resource_id,v.review_status FROM `{$this->versions}` v INNER JOIN `{$this->resources}` r ON r.id=v.resource_id WHERE v.id=%s AND {$scope} FOR UPDATE",$versionId,$organizationId??''),ARRAY_A);
            if(!is_array($row)){throw new RuntimeException('Resource version was not found or is not accessible.');}
            $from=(string)$row['review_status'];if(!$this->policy->allows($from,$to)){throw new RuntimeException("Transition from {$from} to {$to} is not allowed.");}
            if($this->db->update($this->versions,['review_status'=>$to],['id'=>$versionId],['%s'],['%s'])===false){throw new RuntimeException('Unable to update editorial status.');}
            if($to==='published'&&$this->db->update($this->resources,['current_version_id'=>$versionId,'updated_at'=>gmdate('Y-m-d H:i:s')],['id'=>(string)$row['resource_id']],['%s','%s'],['%s'])===false){throw new RuntimeException('Unable to publish the resource version.');}
            $result=new EditorialResult((string)$row['resource_id'],$versionId,$from,$to,false);$json=wp_json_encode($result->toArray());if(!is_string($json)){throw new RuntimeException('Unable to encode operation result.');}
            $now=gmdate('Y-m-d H:i:s');$auditId=wp_generate_uuid4();$operationId=wp_generate_uuid4();
            if($this->db->insert($this->audit,['id'=>$auditId,'event_name'=>'resource.transitioned','module'=>'resources','actor_user_id'=>$actorUserId,'organization_id'=>$organizationId,'object_type'=>'resource_version','object_id'=>$versionId,'context_json'=>wp_json_encode(['from'=>$from,'to'=>$to]),'occurred_at'=>$now],['%s','%s','%s','%d','%s','%s','%s','%s','%s'])===false){throw new RuntimeException('Unable to append the audit event.');}
            if($this->db->insert($this->operations,['id'=>$operationId,'operation_key'=>$idempotencyKey,'resource_version_id'=>$versionId,'operation_name'=>'transition:'.$to,'result_json'=>$json,'completed_at'=>$now],['%s','%s','%s','%s','%s','%s'])===false){throw new RuntimeException('Unable to record the idempotent operation.');}
            if($this->db->query('COMMIT')===false){throw new RuntimeException('Unable to commit the editorial transaction.');}return $result;
        }catch(\Throwable $e){$this->db->query('ROLLBACK');$existing=$this->existing($idempotencyKey);if($existing!==null){return $this->matchingReplay($existing,$versionId,$to);}throw $e;}
    }
    private function existing(string $key):?EditorialResult
    {
        $json=$this->db->get_var($this->db->prepare("SELECT result_json FROM `{$this->operations}` WHERE operation_key=%s LIMIT 1",$key));if(!is_string($json)){return null;}
        try{$data=json_decode($json,true,16,JSON_THROW_ON_ERROR);}catch(JsonException){return null;}if(!is_array($data)){return null;}
        return new EditorialResult((string)$data['resource_id'],(string)$data['version_id'],(string)$data['from'],(string)$data['to'],true);
    }
    private function matchingReplay(EditorialResult $result,string $versionId,string $to):EditorialResult
    {
        if($result->versionId!==$versionId||$result->to!==$to){throw new RuntimeException('The idempotency key belongs to a different operation.');}return $result;
    }
}
