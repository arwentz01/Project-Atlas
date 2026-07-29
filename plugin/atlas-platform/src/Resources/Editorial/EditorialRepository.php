<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Editorial;

interface EditorialRepository { public function transition(string $versionId,string $to,int $actorUserId,?string $organizationId,bool $allowPlatform,string $idempotencyKey):?EditorialResult; }
