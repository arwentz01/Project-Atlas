<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Authoring;
use InvalidArgumentException;
final class ResourceDraftService
{
public function __construct(private ResourceDraftRepository$repository,private ResourceDraftValidator$validator,private ResourceMetadataRepository$metadata){}
public function create(string$key,array$input,string$organizationId,int$userId,bool$mayCreatePlatform):array
{
$key=trim($key);if($key===''||strlen($key)>191){throw new InvalidArgumentException('A valid idempotency key is required.');}$draft=$this->validator->normalize($input);
if($draft['scope']==='platform'){if(!$mayCreatePlatform){throw new InvalidArgumentException('Platform resource creation is not permitted.');}$draft['organization_id']=null;$draft['scope_key']='platform';}else{if($organizationId===''){throw new InvalidArgumentException('An active organization context is required.');}$draft['organization_id']=$organizationId;$draft['scope_key']='organization:'.$organizationId;}
$fingerprint=hash('sha256',wp_json_encode([$draft,$userId]));$result=$this->repository->create($key,$fingerprint,$draft,$userId);$this->metadata->save((string)$result['resource_id'],$draft['metadata']);return$result;
}}
