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
                <tr><th>Route recognition</th><td><strong>site-relative parse_request + rewrite + direct-path fallback</strong></td></tr>
            </tbody></table>
            <h2>Registered front-end routes</h2>
            <table class="widefat striped" style="max-width:1000px"><thead><tr><th>Route</th><th>Name</th><th>Open</th></tr></thead><tbody>
            <?php foreach ($routes as $route) : $pattern=trim((string)$route['pattern'],'/'); $url=$pattern===''?home_url('/'):home_url('/'.$pattern); ?>
                <tr><td><code><?php echo esc_html($pattern===''?'/':'/'.$pattern); ?></code></td><td><?php echo esc_html((string)$route['label']); ?></td><td><a href="<?php echo esc_url($url); ?>">Open in Atlas</a></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <p style="margin-top:18px">Atlas 1.0.3 treats the WordPress Home URL as the Atlas application root. On this local install, <code>http://localhost/atlas/</code> is Atlas Home and <code>http://localhost/atlas/resources/</code> is Resources.</p>
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
