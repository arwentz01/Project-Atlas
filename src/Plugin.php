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
        add_action('template_redirect', [self::class, 'dispatch']);
    }

    public static function registerRoutes(): void
    {
        foreach (App::routes() as $route) {
            add_rewrite_rule(
                '^' . trim($route['pattern'], '/') . '/?$',
                'index.php?atlas_route=' . rawurlencode($route['name']),
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
            return;
        }

        if (! is_user_logged_in()) {
            auth_redirect();
        }

        (new App())->render($route);
        exit;
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
