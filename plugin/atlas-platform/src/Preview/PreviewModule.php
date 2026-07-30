<?php
declare(strict_types=1);

namespace Atlas\Platform\Preview;

use Atlas\Platform\Core\Container\Container;
use Atlas\Platform\Core\Modules\Module;

final class PreviewModule implements Module
{
    public function __construct(private PreviewAdminPage $page) {}
    public function slug(): string { return 'preview'; }
    public function version(): string { return ATLAS_PLATFORM_VERSION; }
    public function dependencies(): array { return ['organizations']; }
    public function register(Container $container): void {}
    public function boot(): void
    {
        // Register the application shell before feature modules add their
        // submenus. WordPress otherwise promotes the first feature submenu to
        // the parent destination and can generate invalid /wp-admin/{slug}
        // links for the Atlas navigation.
        add_action('admin_menu', [$this->page, 'register'], 5);
        add_action('admin_enqueue_scripts', [$this->page, 'enqueueAssets']);
        add_action('in_admin_header', [$this->page, 'renderApplicationNavigation']);
    }
    public function health(): array { return ['status' => 'ok', 'mode' => 'preview']; }
}
