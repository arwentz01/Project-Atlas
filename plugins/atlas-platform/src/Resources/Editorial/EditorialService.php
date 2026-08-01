<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Editorial;

use InvalidArgumentException;

final class EditorialService
{
    public function __construct(private EditorialRepository $repository){}
    public function transition(string $versionId,string $to,int $userId,?string $organizationId,bool $allowPlatform,string $key):?EditorialResult
    {
        if(preg_match('/^[a-f0-9-]{36}$/',strtolower($versionId))!==1){throw new InvalidArgumentException('Invalid resource version identifier.');}
        if(!in_array($to,['draft','in_review','approved','published','review_due','superseded','archived'],true)){throw new InvalidArgumentException('Invalid editorial target status.');}
        if(preg_match('/^[A-Za-z0-9._:-]{8,128}$/',$key)!==1){throw new InvalidArgumentException('A valid idempotency key is required.');}
        return $this->repository->transition(strtolower($versionId),$to,$userId,$organizationId,$allowPlatform,$key);
    }
}
