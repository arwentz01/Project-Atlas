<?php

declare(strict_types=1);

namespace Atlas;

use Atlas\FrontEnd\App;

final class Plugin
{
    public static function boot(): void
    {
        add_action('init', [self::class, 'registerRoutes']);
        add_filter('query_vars', [self::class, 'queryVars']);

        // Run before WordPress canonical/404 handling. Atlas first honors the
        // rewrite query var, then falls back to matching the request path
        // directly against the central route registry.
        add_action('template_redirect', [self::class, 'dispatch'], 1);

        add_action('admin_menu', [self::class, 'registerAdminPage']);
    }

    public static function registerRoutes(): void
    {
        foreach (App::routes() as $route) {
            add_rewrite_rule(
                '^' . trim((string) $route['pattern'], '/') . '/?$',
                'index.php?atlas_route=' . rawurlencode((string) $route['name']),
                'top'
            );
        }
    }

    public static function queryVars(array $vars): array
    {
        $vars[] = 'atlas_route';
        return $vars;
    }

    public static function dispatch(): void
    {
        $route = (string) get_query_var('atlas_route');

        if ($route === '') {
            $route = self::routeFromRequestPath();
        }

        if ($route === '') {
            return;
        }

        if (! is_user_logged_in()) {
            auth_redirect();
        }

        // WordPress may already consider a direct Atlas path a 404. Atlas owns
        // registered application paths, so clear that state before rendering.
        global $wp_query;
        if ($wp_query instanceof \WP_Query) {
            $wp_query->is_404 = false;
        }
        status_header(200);
        nocache_headers();

        (new App())->render($route);
        exit;
    }

    private static function routeFromRequestPath(): string
    {
        $requestUri = isset($_SERVER['REQUEST_URI'])
            ? (string) wp_unslash($_SERVER['REQUEST_URI'])
            : '';

        if ($requestUri === '') {
            return '';
        }

        $requestPath = (string) wp_parse_url($requestUri, PHP_URL_PATH);
        $homePath = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);

        $requestPath = '/' . ltrim($requestPath, '/');
        $homePath = '/' . trim($homePath, '/');

        // Support WordPress installed in a subdirectory as well as web root.
        if ($homePath !== '/' && str_starts_with($requestPath, $homePath . '/')) {
            $requestPath = substr($requestPath, strlen($homePath));
        } elseif ($homePath !== '/' && $requestPath === $homePath) {
            $requestPath = '/';
        }

        $requestPath = trim($requestPath, '/');

        foreach (App::routes() as $route) {
            if ($requestPath === trim((string) $route['pattern'], '/')) {
                return (string) $route['name'];
            }
        }

        return '';
    }

    public static function registerAdminPage(): void
    {
        add_menu_page(
            'Project Atlas',
            'Atlas',
            'read',
            'project-atlas',
            [self::class, 'renderAdminPage'],
            'dashicons-location-alt',
            3
        );
    }

    public static function renderAdminPage(): void
    {
        if (! current_user_can('read')) {
            wp_die(esc_html__('You do not have permission to view Atlas diagnostics.', 'project-atlas'));
        }

        $routes = App::routes();
        $permalinkStructure = (string) get_option('permalink_structure');
        ?>
        <div class="wrap">
            <h1>Project Atlas</h1>
            <p><strong>Visual foundation <?php echo esc_html(ATLAS_VERSION); ?></strong></p>
            <p>Atlas is active. Routine product screens live on the front end; this admin page is intentionally limited to setup and diagnostics.</p>

            <?php if ($permalinkStructure === '') : ?>
                <div class="notice notice-warning inline">
                    <p><strong>Pretty permalinks are disabled.</strong> Root-level Atlas URLs such as <code>/atlas</code> depend on WordPress receiving clean application paths. Go to <a href="<?php echo esc_url(admin_url('options-permalink.php')); ?>">Settings → Permalinks</a>, choose a non-Plain structure, and save once.</p>
                </div>
            <?php else : ?>
                <div class="notice notice-success inline"><p>WordPress permalink routing is enabled.</p></div>
            <?php endif; ?>

            <h2>Registered front-end routes</h2>
            <table class="widefat striped" style="max-width:1000px">
                <thead><tr><th>Route</th><th>Name</th><th>Open</th></tr></thead>
                <tbody>
                <?php foreach ($routes as $route) : ?>
                    <tr>
                        <td><code>/<?php echo esc_html(trim((string) $route['pattern'], '/')); ?></code></td>
                        <td><?php echo esc_html((string) $route['label']); ?></td>
                        <td><a href="<?php echo esc_url(home_url('/' . trim((string) $route['pattern'], '/'))); ?>">Open in Atlas</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <p style="margin-top:18px">If routes were added while the plugin was already active, saving <a href="<?php echo esc_url(admin_url('options-permalink.php')); ?>">Settings → Permalinks</a> once refreshes WordPress rewrite rules. Atlas also includes a direct-path fallback beginning in version 1.0.1.</p>
        </div>
        <?php
    }

    public static function activate(): void
    {
        self::registerRoutes();
        flush_rewrite_rules(false);
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules(false);
    }
}
