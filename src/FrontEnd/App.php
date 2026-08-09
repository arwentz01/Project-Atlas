<?php

declare(strict_types=1);

namespace Atlas\FrontEnd;

use Atlas\Support\Fixtures;

final class App
{
    public static function routes(): array
    {
        return [
            ['name' => 'home', 'pattern' => 'atlas', 'label' => 'Home', 'nav' => true],
        ];
    }

    public function render(string $route): void
    {
        $routes = array_column(self::routes(), null, 'name');
        if (! isset($routes[$route])) {
            status_header(404);
            nocache_headers();
            $this->shell('Not found', '<div class="atlas-empty"><h1>Page not found</h1><p>This Atlas destination is not available.</p></div>', $route);
            return;
        }

        wp_enqueue_style('atlas-app', ATLAS_URL . 'assets/app.css', [], ATLAS_VERSION);
        wp_enqueue_script('atlas-app', ATLAS_URL . 'assets/app.js', [], ATLAS_VERSION, true);

        $method = 'page_' . str_replace('-', '_', $route);
        $content = method_exists($this, $method) ? $this->{$method}() : '';
        $this->shell($routes[$route]['label'], $content, $route);
    }

    private function page_home(): string
    {
        $data = Fixtures::dashboard();
        $name = wp_get_current_user()->display_name ?: wp_get_current_user()->user_login;
        ob_start();
        ?>
        <section class="atlas-hero">
            <div>
                <p class="atlas-eyebrow"><?php echo esc_html($data['welcome'] ?? 'Welcome'); ?></p>
                <h1><?php echo esc_html($name); ?></h1>
                <p>Everything you need to move care forward, without hunting through five systems to find it.</p>
            </div>
            <div class="atlas-org-card" aria-label="Current organization">
                <span>Working in</span>
                <strong><?php echo esc_html($data['organization'] ?? 'Personal workspace'); ?></strong>
            </div>
        </section>

        <section aria-labelledby="quick-actions-title">
            <div class="atlas-section-heading"><div><p class="atlas-eyebrow">Start here</p><h2 id="quick-actions-title">What do you need to do?</h2></div></div>
            <div class="atlas-action-grid">
                <?php foreach (($data['quick_actions'] ?? []) as $item) : ?>
                    <a class="atlas-action-card" href="<?php echo esc_url(home_url($item['href'])); ?>">
                        <span class="atlas-action-arrow" aria-hidden="true">↗</span>
                        <strong><?php echo esc_html($item['label']); ?></strong>
                        <span><?php echo esc_html($item['hint']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section aria-labelledby="recent-title">
            <div class="atlas-section-heading"><div><p class="atlas-eyebrow">Pick up where you left off</p><h2 id="recent-title">Recent work</h2></div></div>
            <div class="atlas-list-card">
                <?php foreach (($data['recent'] ?? []) as $item) : ?>
                    <div class="atlas-list-row">
                        <div><span class="atlas-badge"><?php echo esc_html($item['type']); ?></span><strong><?php echo esc_html($item['title']); ?></strong></div>
                        <span class="atlas-muted"><?php echo esc_html($item['meta']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    private function shell(string $title, string $content, string $current): void
    {
        $user = wp_get_current_user();
        ?><!doctype html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="robots" content="noindex,nofollow">
            <title><?php echo esc_html($title . ' | Atlas'); ?></title>
            <?php wp_head(); ?>
        </head>
        <body class="atlas-body">
            <a class="atlas-skip-link" href="#atlas-main">Skip to main content</a>
            <div class="atlas-app">
                <aside class="atlas-sidebar" id="atlas-sidebar" aria-label="Atlas navigation">
                    <a class="atlas-brand" href="<?php echo esc_url(home_url('/atlas')); ?>" aria-label="Atlas home"><span class="atlas-brand-mark">A</span><span>Atlas</span></a>
                    <nav>
                        <?php foreach (self::routes() as $route) : if (empty($route['nav'])) continue; ?>
                            <a href="<?php echo esc_url(home_url('/' . trim($route['pattern'], '/'))); ?>" <?php echo $current === $route['name'] ? 'aria-current="page"' : ''; ?>>
                                <span class="atlas-nav-dot" aria-hidden="true"></span><?php echo esc_html($route['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                    <div class="atlas-sidebar-footer">
                        <span class="atlas-avatar"><?php echo esc_html(strtoupper(substr($user->display_name ?: $user->user_login, 0, 1))); ?></span>
                        <div><strong><?php echo esc_html($user->display_name ?: $user->user_login); ?></strong><span>Atlas Demo Health</span></div>
                    </div>
                </aside>
                <div class="atlas-workspace">
                    <header class="atlas-topbar">
                        <button class="atlas-menu-button" type="button" data-atlas-menu aria-controls="atlas-sidebar" aria-expanded="false">Menu</button>
                        <span class="atlas-context">Atlas Demo Health</span>
                        <div class="atlas-top-actions"><button type="button" class="atlas-icon-button" aria-label="Search Atlas">⌕</button><a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Sign out</a></div>
                    </header>
                    <main class="atlas-main" id="atlas-main"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></main>
                </div>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html><?php
    }
}
