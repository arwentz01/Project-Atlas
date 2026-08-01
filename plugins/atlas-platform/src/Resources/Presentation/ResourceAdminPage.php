<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Presentation;

use Atlas\Platform\Organizations\Services\CurrentOrganizationResolver;
use Atlas\Platform\Resources\Domain\ResourcePolicy;
use Atlas\Platform\Resources\Services\ResourceReader;
use Atlas\Platform\PatientResources\Repositories\BrandingRepository;

final class ResourceAdminPage
{
    private string $hook='';
    public function __construct(private ResourceReader $reader,private CurrentOrganizationResolver $organizations,private ResourcePolicy $policy,private StructuredContentRenderer $renderer,private BrandingRepository $branding){}
    public function register():void{$this->hook=(string)add_submenu_page(null,__('Atlas Resource','atlas-platform'),__('Atlas Resource','atlas-platform'),'atlas_access','atlas-resource',[$this,'render']);}
    public function enqueue(string $hook):void{if($hook===$this->hook){wp_enqueue_style('atlas-preview',ATLAS_PLATFORM_URL.'assets/css/atlas-preview.css',[],ATLAS_PLATFORM_VERSION);}}
    public function render():void
    {
        if(!current_user_can('atlas_access')){wp_die(esc_html__('You are not allowed to access this resource.','atlas-platform'),'', ['response'=>403]);}
        $id=isset($_GET['id'])?strtolower(sanitize_text_field(wp_unslash((string)$_GET['id']))):'';
        if(!$this->policy->validIdentifier($id)){wp_die(esc_html__('The resource identifier is invalid.','atlas-platform'),'', ['response'=>400]);}
        $organization=$this->organizations->resolveForUser(get_current_user_id());$resource=$this->reader->findPublished($id,$organization?->id);
        if($resource===null){wp_die(esc_html__('The resource was not found or is not accessible.','atlas-platform'),'', ['response'=>404]);}
        $brand=$organization===null?null:$this->branding->findForOrganization($organization->id);$view=['resource'=>$resource,'body_html'=>$this->renderer->render($resource->version->body),'branding'=>$brand,'logo_url'=>$brand?->logoAttachmentId===null?null:wp_get_attachment_url($brand->logoAttachmentId)];
        require ATLAS_PLATFORM_DIR.'templates/resources/detail.php';
    }
}
