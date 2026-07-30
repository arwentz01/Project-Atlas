<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources;

use Atlas\Platform\Core\Container\Container;
use Atlas\Platform\Core\Modules\Module;
use Atlas\Platform\Resources\Rest\ResourceController;
use Atlas\Platform\Resources\Presentation\ResourceAdminPage;
use Atlas\Platform\Resources\Rest\ResourceDraftController;
use Atlas\Platform\Resources\Presentation\ResourceLibraryAdminPage;
use Atlas\Platform\Resources\Admin\ResourceAuthoringAdminPage;
use Atlas\Platform\Resources\Editorial\Admin\EditorialQueueAdminPage;
use Atlas\Platform\Resources\Personal\PersonalWorkspaceAdminPage;
use Atlas\Platform\Resources\Packets\PacketBuilderAdminPage;
use Atlas\Platform\Resources\Sources\SourceWorkspaceAdminPage;
use Atlas\Platform\Resources\Rest\SourceWorkspaceController;
use Atlas\Platform\Resources\Rest\PacketController;

final class ResourcesModule implements Module
{
    public function __construct(private ResourceController $controller,private ResourceAdminPage $page,private ResourceDraftController $drafts,private ResourceLibraryAdminPage $library,private ResourceAuthoringAdminPage $authoring,private EditorialQueueAdminPage $review,private PersonalWorkspaceAdminPage $personal,private PacketBuilderAdminPage $packets,private SourceWorkspaceAdminPage $sources,private SourceWorkspaceController $sourceController,private PacketController $packetController) {}
    public function slug(): string { return 'resources'; }
    public function version(): string { return ATLAS_PLATFORM_VERSION; }
    public function dependencies(): array { return ['organizations']; }
    public function register(Container $container): void
    {
        add_action('admin_menu',[$this->personal,'register']);
        add_action('admin_post_atlas_save_resource',[$this->personal,'saveResource']);
        add_action('admin_post_atlas_remove_saved_resource',[$this->personal,'removeResource']);
        add_action('admin_post_atlas_save_search',[$this->personal,'saveSearch']);
        add_action('admin_post_atlas_create_packet',[$this->packets,'create']);
        add_action('admin_post_atlas_add_packet_item',[$this->packets,'addItem']);
        add_action('admin_post_atlas_remove_packet_item',[$this->packets,'removeItem']);
        add_action('admin_post_atlas_update_packet_status',[$this->packets,'status']);
        add_action('admin_post_atlas_create_source_document',[$this->sources,'createDocument']);
        add_action('admin_post_atlas_update_source_status',[$this->sources,'updateDocumentStatus']);
        add_action('admin_post_atlas_create_source_section',[$this->sources,'createSection']);
        add_action('admin_post_atlas_create_extraction_candidate',[$this->sources,'createCandidate']);
        add_action('admin_post_atlas_review_extraction_candidate',[$this->sources,'reviewCandidate']);
        add_action('admin_post_atlas_create_payer_requirement',[$this->sources,'createRequirement']);
        add_action('admin_post_atlas_review_payer_requirement',[$this->sources,'reviewRequirement']);
    }
    public function boot(): void { add_action('rest_api_init', [$this, 'registerRoutes']); add_action('admin_menu',[$this->page,'register']); add_action('admin_menu',[$this->library,'register']); add_action('admin_menu',[$this->authoring,'register']); add_action('admin_menu',[$this->review,'register']); add_action('admin_menu',[$this->packets,'register']); add_action('admin_menu',[$this->sources,'register']); add_action('admin_enqueue_scripts',[$this->page,'enqueue']); add_action('admin_enqueue_scripts',[$this->library,'enqueue']); add_action('admin_enqueue_scripts',[$this->authoring,'enqueue']); add_action('admin_enqueue_scripts',[$this->review,'enqueue']); add_action('admin_enqueue_scripts',[$this->packets,'enqueue']); add_action('admin_enqueue_scripts',[$this->sources,'enqueue']); add_action('admin_post_atlas_create_resource',[$this->authoring,'create']); add_action('admin_post_atlas_editorial_transition',[$this->review,'transition']); add_action('admin_post_atlas_create_revision',[$this->review,'revise']); add_action('admin_post_atlas_archive_resource',[$this->review,'archive']); add_action('admin_post_atlas_assign_reviewer',[$this->review,'assign']); add_action('admin_post_atlas_add_review_note',[$this->review,'addNote']); add_filter('atlas_admin_navigation',[$this,'navigation'],10,2); }
    public function navigation(array $items,string $currentSlug):array{if(current_user_can('atlas_access')){$items[]=['slug'=>'atlas-resources','label'=>__('Resources','atlas-platform'),'icon'=>'dashicons-book-alt','url'=>admin_url('admin.php?page=atlas-resources'),'capability'=>'atlas_access','current'=>$currentSlug==='atlas-resources'];}if(current_user_can('atlas_create_resources')){$items[]=['slug'=>'atlas-resource-create','label'=>__('Create Resource','atlas-platform'),'icon'=>'dashicons-edit','url'=>admin_url('admin.php?page=atlas-resource-create'),'capability'=>'atlas_create_resources','current'=>$currentSlug==='atlas-resource-create'];}if(current_user_can('atlas_create_packets')){$items[]=['slug'=>'atlas-packets','label'=>__('Packets','atlas-platform'),'icon'=>'dashicons-media-document','url'=>admin_url('admin.php?page=atlas-packets'),'capability'=>'atlas_create_packets','current'=>$currentSlug==='atlas-packets'];}if(current_user_can('atlas_upload_sources')){$items[]=['slug'=>'atlas-sources','label'=>__('Sources','atlas-platform'),'icon'=>'dashicons-media-text','url'=>admin_url('admin.php?page=atlas-sources'),'capability'=>'atlas_upload_sources','current'=>$currentSlug==='atlas-sources'];}if(current_user_can('atlas_review_resources')||current_user_can('atlas_publish_resources')){$items[]=['slug'=>'atlas-resource-review','label'=>__('Review Queue','atlas-platform'),'icon'=>'dashicons-yes-alt','url'=>admin_url('admin.php?page=atlas-resource-review'),'capability'=>'atlas_review_resources','current'=>$currentSlug==='atlas-resource-review'];}return$items;}
    public function registerRoutes(): void
    {
        register_rest_route('atlas/v1', '/resources/(?P<id>[a-fA-F0-9-]{36})', ['methods' => 'GET', 'callback' => [$this->controller, 'show'], 'permission_callback' => [$this->controller, 'permission']]);
        register_rest_route('atlas/v1', '/resources', ['methods' => 'GET', 'callback' => [$this->controller, 'index'], 'permission_callback' => [$this->controller, 'permission']]);
        register_rest_route('atlas/v1', '/resources/drafts', ['methods' => 'POST', 'callback' => [$this->drafts, 'create'], 'permission_callback' => [$this->drafts, 'permission']]);
        register_rest_route('atlas/v1','/resource-versions/(?P<id>[a-fA-F0-9-]{36})/transitions',['methods'=>'POST','callback'=>[$this->controller,'transition'],'permission_callback'=>[$this->controller,'transitionPermission']]);
        register_rest_route('atlas/v1','/sources/dashboard',['methods'=>'GET','callback'=>[$this->sourceController,'dashboard'],'permission_callback'=>[$this->sourceController,'readPermission']]);
        register_rest_route('atlas/v1','/sources/documents',['methods'=>'POST','callback'=>[$this->sourceController,'createDocument'],'permission_callback'=>[$this->sourceController,'sourceWritePermission']]);
        register_rest_route('atlas/v1','/payer-requirements',['methods'=>'GET','callback'=>[$this->sourceController,'requirements'],'permission_callback'=>[$this->sourceController,'readPermission']]);
        register_rest_route('atlas/v1','/payer-requirements',['methods'=>'POST','callback'=>[$this->sourceController,'createRequirement'],'permission_callback'=>[$this->sourceController,'reviewPermission']]);
        register_rest_route('atlas/v1','/payer-requirements/(?P<id>[a-fA-F0-9-]{36})/review',['methods'=>'POST','callback'=>[$this->sourceController,'reviewRequirement'],'permission_callback'=>[$this->sourceController,'reviewPermission']]);
        register_rest_route('atlas/v1','/packets',['methods'=>'POST','callback'=>[$this->packetController,'create'],'permission_callback'=>[$this->packetController,'createPermission']]);
        register_rest_route('atlas/v1','/packets/(?P<id>[a-fA-F0-9-]{36})',['methods'=>'GET','callback'=>[$this->packetController,'show'],'permission_callback'=>[$this->packetController,'permission']]);
    }
    public function health(): array { return ['status' => 'ok', 'mode' => 'read_only']; }
}
