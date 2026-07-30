<?php
declare(strict_types=1);
namespace Atlas\Platform\PatientResources\Repositories;
use Atlas\Platform\PatientResources\Domain\BrandingProfile;
use RuntimeException;
final class WordPressBrandingRepository implements BrandingRepository
{
    private string $table;
    public function __construct(private object $db){$this->table=$db->prefix.'atlas_branding_profiles';}
    public function findForOrganization(string $id):?BrandingProfile{$r=$this->db->get_row($this->db->prepare("SELECT organization_id,display_name,logo_attachment_id,contact_block,footer,primary_color FROM `{$this->table}` WHERE organization_id=%s LIMIT 1",$id),ARRAY_A);return is_array($r)?new BrandingProfile((string)$r['organization_id'],(string)$r['display_name'],$r['logo_attachment_id']===null?null:(int)$r['logo_attachment_id'],(string)$r['contact_block'],(string)$r['footer'],(string)$r['primary_color']):null;}
    public function save(BrandingProfile $profile):void
    {
        $result=$this->db->replace($this->table,['organization_id'=>$profile->organizationId,'display_name'=>$profile->displayName,'logo_attachment_id'=>$profile->logoAttachmentId,'contact_block'=>$profile->contactBlock,'footer'=>$profile->footer,'primary_color'=>$profile->primaryColor,'updated_at'=>current_time('mysql',true)]);
        if($result===false){throw new RuntimeException('The branding profile could not be saved.');}
    }
}
