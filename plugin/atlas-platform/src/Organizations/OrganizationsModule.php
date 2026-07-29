<?php

declare(strict_types=1);

namespace Atlas\Platform\Organizations;

use Atlas\Platform\Core\Container\Container;
use Atlas\Platform\Core\Modules\Module;
use Atlas\Platform\Organizations\Rest\CurrentOrganizationController;

final class OrganizationsModule implements Module
{
    public function __construct(private CurrentOrganizationController $controller) {}
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
        do_action('atlas_organizations_register', $this);
    }
    public function registerRoutes(): void
    {
        register_rest_route('atlas/v1', '/organizations/current', ['methods' => 'GET', 'callback' => [$this->controller, 'show'], 'permission_callback' => [$this->controller, 'permission']]);
    }
    public function health(): array { return ['status' => 'ok', 'feature_status' => 'foundation']; }
}
