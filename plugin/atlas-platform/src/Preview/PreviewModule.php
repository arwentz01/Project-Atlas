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
        add_action('admin_menu', [$this->page, 'register']);
        add_action('admin_enqueue_scripts', [$this->page, 'enqueueAssets']);
    }
    public function health(): array { return ['status' => 'ok', 'mode' => 'preview']; }
}
