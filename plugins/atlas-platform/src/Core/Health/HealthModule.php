<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Health;

use Atlas\Platform\Core\Container\Container;
use Atlas\Platform\Core\Logging\Logger;
use Atlas\Platform\Core\Modules\Module;
use WP_REST_Request;
use WP_REST_Response;

final class HealthModule implements Module
{
    public function __construct(private HealthService $service, private Logger $logger) {}
    public function slug(): string { return 'health'; }
    public function version(): string { return ATLAS_PLATFORM_VERSION; }
    public function dependencies(): array { return []; }
    public function register(Container $container): void {}
    public function boot(): void { add_action('rest_api_init', [$this, 'registerRoutes']); }
    public function health(): array { return $this->service->summary(); }
    public function registerRoutes(): void
    {
        register_rest_route('atlas/v1', '/health', ['methods' => 'GET', 'callback' => [$this, 'respond'], 'permission_callback' => '__return_true']);
    }
    public function respond(WP_REST_Request $request): WP_REST_Response
    {
        $summary = $this->service->summary();
        if ($summary['status'] !== 'ok') { $this->logger->log('warning', 'health.degraded', 'Public health check is degraded.', ['pending_migrations' => $summary['pending_migration_count']], 'health'); }
        return new WP_REST_Response($summary, $summary['status'] === 'ok' ? 200 : 503);
    }
}
