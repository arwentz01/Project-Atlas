<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources;

use Atlas\Platform\Core\Container\Container;
use Atlas\Platform\Core\Modules\Module;
use Atlas\Platform\Resources\Rest\ResourceController;
use Atlas\Platform\Resources\Presentation\ResourceAdminPage;
use Atlas\Platform\Resources\Rest\ResourceDraftController;
use Atlas\Platform\Resources\Presentation\ResourceLibraryAdminPage;

final class ResourcesModule implements Module
{
    public function __construct(private ResourceController $controller,private ResourceAdminPage $page,private ResourceDraftController $drafts,private ResourceLibraryAdminPage $library) {}
    public function slug(): string { return 'resources'; }
    public function version(): string { return ATLAS_PLATFORM_VERSION; }
    public function dependencies(): array { return ['organizations']; }
    public function register(Container $container): void {}
    public function boot(): void { add_action('rest_api_init', [$this, 'registerRoutes']); add_action('admin_menu',[$this->page,'register']); add_action('admin_menu',[$this->library,'register']); add_action('admin_enqueue_scripts',[$this->page,'enqueue']); add_action('admin_enqueue_scripts',[$this->library,'enqueue']); add_filter('atlas_admin_navigation',[$this,'navigation'],10,2); }
    public function navigation(array $items,string $currentSlug):array{if(current_user_can('atlas_access')){$items[]=['slug'=>'atlas-resources','label'=>__('Resources','atlas-platform'),'icon'=>'dashicons-book-alt','url'=>admin_url('admin.php?page=atlas-resources'),'capability'=>'atlas_access','current'=>$currentSlug==='atlas-resources'];}return$items;}
    public function registerRoutes(): void
    {
        register_rest_route('atlas/v1', '/resources/(?P<id>[a-fA-F0-9-]{36})', ['methods' => 'GET', 'callback' => [$this->controller, 'show'], 'permission_callback' => [$this->controller, 'permission']]);
        register_rest_route('atlas/v1', '/resources', ['methods' => 'GET', 'callback' => [$this->controller, 'index'], 'permission_callback' => [$this->controller, 'permission']]);
        register_rest_route('atlas/v1', '/resources/drafts', ['methods' => 'POST', 'callback' => [$this->drafts, 'create'], 'permission_callback' => [$this->drafts, 'permission']]);
        register_rest_route('atlas/v1','/resource-versions/(?P<id>[a-fA-F0-9-]{36})/transitions',['methods'=>'POST','callback'=>[$this->controller,'transition'],'permission_callback'=>[$this->controller,'transitionPermission']]);
    }
    public function health(): array { return ['status' => 'ok', 'mode' => 'read_only']; }
}
