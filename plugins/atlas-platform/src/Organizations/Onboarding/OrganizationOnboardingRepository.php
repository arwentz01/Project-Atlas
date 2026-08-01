<?php
declare(strict_types=1);namespace Atlas\Platform\Organizations\Onboarding;interface OrganizationOnboardingRepository{/** @return array{organization_id:string,membership_id:string,replayed:bool} */public function create(string$idempotencyKey,string$fingerprint,string$name,string$slug,int$userId):array;}
