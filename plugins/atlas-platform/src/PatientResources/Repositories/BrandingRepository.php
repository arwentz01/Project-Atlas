<?php
declare(strict_types=1);namespace Atlas\Platform\PatientResources\Repositories;use Atlas\Platform\PatientResources\Domain\BrandingProfile;interface BrandingRepository{public function findForOrganization(string $organizationId):?BrandingProfile;}
