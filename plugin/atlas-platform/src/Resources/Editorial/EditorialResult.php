<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Editorial;

final class EditorialResult
{
    public function __construct(public readonly string $resourceId,public readonly string $versionId,public readonly string $from,public readonly string $to,public readonly bool $replayed){}
    /** @return array<string,mixed> */ public function toArray():array{return ['resource_id'=>$this->resourceId,'version_id'=>$this->versionId,'from'=>$this->from,'to'=>$this->to,'replayed'=>$this->replayed];}
}
