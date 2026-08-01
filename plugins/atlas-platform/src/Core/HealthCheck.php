<?php

declare(strict_types=1);

namespace Atlas\Platform\Core;

use WP_REST_Request;
use WP_REST_Response;

final class HealthCheck implements Module
{
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route('atlas/v1', '/health', [
            'methods' => 'GET',
            'callback' => [$this, 'respond'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function respond(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'name' => 'Atlas Platform',
            'status' => 'ok',
            'version' => ATLAS_PLATFORM_VERSION,
            'wordpress' => get_bloginfo('version'),
            'php' => PHP_VERSION,
            'timestamp' => gmdate('c'),
        ]);
    }
}
