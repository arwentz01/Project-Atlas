<?php
declare(strict_types=1);namespace Atlas\Platform\PatientResources\Repositories;interface PatientResourceAccessRepository{public function isPublishedPatientEducationVersion(string $versionId,string $organizationId):bool;}
