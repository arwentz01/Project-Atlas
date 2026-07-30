<?php
declare(strict_types=1);
namespace Atlas\Platform\PatientResources\Services;
use Atlas\Platform\Core\Audit\AuditRecorder;
use Atlas\Platform\PatientResources\Domain\BrandingProfile;
use Atlas\Platform\PatientResources\Domain\PatientResourcePolicy;
use Atlas\Platform\PatientResources\Repositories\BrandingRepository;
use InvalidArgumentException;
final class BrandingService
{
    public function __construct(private BrandingRepository $branding,private PatientResourcePolicy $policy,private AuditRecorder $audit){}
    public function save(string $organizationId,array $input,int $actorId):BrandingProfile
    {
        $name=trim(sanitize_text_field((string)($input['display_name']??'')));$contact=trim(sanitize_textarea_field((string)($input['contact_block']??'')));$footer=trim(sanitize_textarea_field((string)($input['footer']??'')));$color=strtolower(trim((string)($input['primary_color']??'')));$logo=absint($input['logo_attachment_id']??0);
        if($name===''||strlen($name)>255||strlen($contact)>1000||strlen($footer)>1000||!$this->policy->validColor($color)){throw new InvalidArgumentException('The branding profile contains invalid values.');}
        if($logo>0&&!wp_attachment_is_image($logo)){throw new InvalidArgumentException('The selected logo must be an image attachment.');}
        $profile=new BrandingProfile($organizationId,$name,$logo>0?$logo:null,$contact,$footer,$color);$this->branding->save($profile);$this->audit->record('organization.branding_updated','patient_resources',$actorId,$organizationId,'branding_profile',$organizationId);return $profile;
    }
}
