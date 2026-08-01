<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Authoring;
interface ResourceMetadataRepository{public function save(string $resourceId,array $metadata):void;}
