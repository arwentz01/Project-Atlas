<?php
declare(strict_types=1);namespace Atlas\Platform\PatientResources\Catalog;final class PatientEducationItem{public function __construct(public readonly string$resourceId,public readonly string$versionId,public readonly string$title,public readonly string$summary,public readonly string$scope,public readonly ?string$sourcePublisher){}}
