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
        add_action('parse_request', [self::class, 'recognizeRequest'], 1);
        add_action('template_redirect', [self::class, 'dispatch'], 1);
        add_action('admin_menu', [self::class, 'registerAdminPage']);
    }

    public static function registerRoutes(): void
    {
        foreach (App::routes() as $route) {
            $pattern = trim((string) $route['pattern'], '/');
            if ($pattern === '') {
                continue;
            }
            add_rewrite_rule(
                '^' . $pattern . '/?$',
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

    public static function recognizeRequest(\WP $wp): void
    {
        // Honor an explicit route first. This gives diagnostics and hosting
        // fallbacks a route path that does not depend on pretty permalinks.
        $explicit = isset($wp->query_vars['atlas_route'])
            ? sanitize_key((string) $wp->query_vars['atlas_route'])
            : '';

        if ($explicit !== '' && self::routeExists($explicit)) {
            return;
        }

        $requestPath = trim((string) $wp->request, '/');

        foreach (App::routes() as $route) {
            if ($requestPath === trim((string) $route['pattern'], '/')) {
                $wp->query_vars['atlas_route'] = (string) $route['name'];
                return;
            }
        }
    }

    public static function dispatch(): void
    {
        $route = sanitize_key((string) get_query_var('atlas_route'));

        // Some server stacks leave a public query var out of the main query on
        // the front page. Keep an explicit, validated GET fallback for the
        // diagnostics route test.
        if ($route === '' && isset($_GET['atlas_route'])) {
            $candidate = sanitize_key((string) wp_unslash($_GET['atlas_route']));
            if (self::routeExists($candidate)) {
                $route = $candidate;
            }
        }

        if ($route === '') {
            $route = self::routeFromRequestPath();
        }

        if ($route === '' || ! self::routeExists($route)) {
            return;
        }

        if (! is_user_logged_in()) {
            auth_redirect();
        }

        global $wp_query;
        if ($wp_query instanceof \WP_Query) {
            $wp_query->is_404 = false;
        }

        status_header(200);
        nocache_headers();
        (new App())->render($route);
        exit;
    }

    private static function routeExists(string $name): bool
    {
        foreach (App::routes() as $route) {
            if ((string) $route['name'] === $name) {
                return true;
            }
        }
        return false;
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
        $sitePath = (string) wp_parse_url(site_url('/'), PHP_URL_PATH);
        $requestPath = '/' . ltrim($requestPath, '/');

        foreach (array_unique([$homePath, $sitePath]) as $basePath) {
            $basePath = '/' . trim((string) $basePath, '/');
            if ($basePath === '/') {
                continue;
            }
            if (str_starts_with($requestPath, $basePath . '/')) {
                $requestPath = substr($requestPath, strlen($basePath));
                break;
            }
            if ($requestPath === $basePath || $requestPath === $basePath . '/') {
                $requestPath = '/';
                break;
            }
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
        add_menu_page('Project Atlas','Atlas','read','project-atlas',[self::class,'renderAdminPage'],'dashicons-location-alt',3);
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
            <p>Atlas is active. This WordPress installation is treated as the Atlas application root.</p>
            <?php if ($permalinkStructure === '') : ?><div class="notice notice-warning inline"><p><strong>Pretty permalinks are disabled.</strong> Go to <a href="<?php echo esc_url(admin_url('options-permalink.php')); ?>">Settings → Permalinks</a>, choose a non-Plain structure, and save once.</p></div><?php else : ?><div class="notice notice-success inline"><p>WordPress permalink routing is enabled.</p></div><?php endif; ?>
            <h2>Environment</h2>
            <table class="widefat striped" style="max-width:1000px"><tbody>
                <tr><th style="width:220px">Home URL</th><td><code><?php echo esc_html(home_url('/')); ?></code></td></tr>
                <tr><th>WordPress URL</th><td><code><?php echo esc_html(site_url('/')); ?></code></td></tr>
                <tr><th>Permalink structure</th><td><code><?php echo esc_html($permalinkStructure ?: '(Plain)'); ?></code></td></tr>
                <tr><th>Application root</th><td><strong><?php echo esc_html(home_url('/')); ?></strong></td></tr>
                <tr><th>Route recognition</th><td><strong>explicit route + site-relative parse_request + rewrite + direct-path fallback</strong></td></tr>
            </tbody></table>
            <h2>Registered front-end routes</h2>
            <p><strong>Pretty URL</strong> tests normal WordPress routing. <strong>Direct test</strong> enters through the WordPress front controller and does not depend on the pretty-path rewrite matching the route.</p>
            <table class="widefat striped" style="max-width:1100px"><thead><tr><th>Route</th><th>Name</th><th>Pretty URL</th><th>Direct test</th></tr></thead><tbody>
            <?php foreach ($routes as $route) :
                $pattern=trim((string)$route['pattern'],'/');
                $pretty=$pattern===''?home_url('/'):home_url('/'.$pattern.'/');
                $direct=add_query_arg('atlas_route',(string)$route['name'],home_url('/'));
            ?>
                <tr>
                    <td><code><?php echo esc_html($pattern===''?'/':'/'.$pattern); ?></code></td>
                    <td><?php echo esc_html((string)$route['label']); ?></td>
                    <td><a href="<?php echo esc_url($pretty); ?>">Open pretty URL</a></td>
                    <td><a href="<?php echo esc_url($direct); ?>">Run direct test</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
            <p style="margin-top:18px">Atlas 1.0.4 adds rewrite-independent route diagnostics. If a direct test works while its pretty URL returns 404, Atlas rendering is healthy and the remaining issue is the local web-server/WordPress pretty-URL handoff.</p>
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
