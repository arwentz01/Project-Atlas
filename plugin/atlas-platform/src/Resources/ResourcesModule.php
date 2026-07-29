<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources;

use Atlas\Platform\Core\Container\Container;
use Atlas\Platform\Core\Modules\Module;
use Atlas\Platform\Resources\Rest\ResourceController;

final class ResourcesModule implements Module
{
    public function __construct(private ResourceController $controller) {}
    public function slug(): string { return 'resources'; }
    public function version(): string { return ATLAS_PLATFORM_VERSION; }
    public function dependencies(): array { return ['organizations']; }
    public function register(Container $container): void {}
    public function boot(): void { add_action('rest_api_init', [$this, 'registerRoutes']); }
    public function registerRoutes(): void
    {
        register_rest_route('atlas/v1', '/resources/(?P<id>[a-fA-F0-9-]{36})', ['methods' => 'GET', 'callback' => [$this->controller, 'show'], 'permission_callback' => [$this->controller, 'permission']]);
        register_rest_route('atlas/v1', '/resources', ['methods' => 'GET', 'callback' => [$this->controller, 'index'], 'permission_callback' => [$this->controller, 'permission']]);
    }
    public function health(): array { return ['status' => 'ok', 'mode' => 'read_only']; }
}
