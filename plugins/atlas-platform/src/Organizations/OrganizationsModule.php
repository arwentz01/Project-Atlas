<?php

declare(strict_types=1);

namespace Atlas\Platform\Organizations;

use Atlas\Platform\Core\Container\Container;
use Atlas\Platform\Core\Modules\Module;
use Atlas\Platform\Organizations\Rest\CurrentOrganizationController;
use Atlas\Platform\Organizations\Rest\OrganizationOnboardingController;
use Atlas\Platform\Organizations\Admin\OrganizationsAdminPage;

final class OrganizationsModule implements Module
{
    public function __construct(private CurrentOrganizationController $controller, private OrganizationOnboardingController $onboarding, private OrganizationsAdminPage $page) {}
    public function slug(): string { return 'organizations'; }
    public function version(): string { return ATLAS_PLATFORM_VERSION; }
    public function dependencies(): array { return []; }
    public function register(Container $container): void
    {
        /**
         * The Organizations vertical slice will register its repositories,
         * services, capabilities, migrations, REST routes, and admin screens here.
         */
    }

    public function boot(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
        add_action('admin_menu', [$this->page, 'register']);
        add_action('admin_enqueue_scripts', [$this->page, 'enqueue']);
        add_action('admin_post_atlas_select_organization', [$this->page, 'select']);
        add_action('admin_post_atlas_create_organization', [$this->page, 'create']);
        add_action('admin_post_atlas_invite_member', [$this->page, 'invite']);
        add_action('admin_post_atlas_accept_invitation', [$this->page, 'acceptInvitation']);
        add_action('admin_post_nopriv_atlas_accept_invitation', [$this->page, 'acceptInvitation']);
        add_action('admin_post_atlas_revoke_invitation', [$this->page, 'revokeInvitation']);
        add_action('admin_post_atlas_update_member_roles', [$this->page, 'updateMemberRoles']);
        add_action('admin_post_atlas_remove_member', [$this->page, 'removeMember']);
        add_action('admin_post_atlas_save_branding', [$this->page, 'saveBranding']);
        add_filter('atlas_admin_navigation', [$this, 'navigation'], 10, 2);
        do_action('atlas_organizations_register', $this);
    }
    public function navigation(array $items, string $currentSlug): array
    {
        if (current_user_can('atlas_access')) { $items[] = ['slug'=>'atlas-organizations','label'=>__('Organizations','atlas-platform'),'icon'=>'dashicons-groups','url'=>admin_url('admin.php?page=atlas-organizations'),'capability'=>'atlas_access','current'=>$currentSlug==='atlas-organizations']; }
        return $items;
    }
    public function registerRoutes(): void
    {
        register_rest_route('atlas/v1', '/organizations/current', ['methods' => 'GET', 'callback' => [$this->controller, 'show'], 'permission_callback' => [$this->controller, 'permission']]);
        register_rest_route('atlas/v1', '/organizations', ['methods' => 'POST', 'callback' => [$this->onboarding, 'create'], 'permission_callback' => [$this->onboarding, 'permission']]);
    }
    public function health(): array { return ['status' => 'ok', 'feature_status' => 'foundation']; }
}
