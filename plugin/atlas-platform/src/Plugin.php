<?php

declare(strict_types=1);

namespace Atlas\Platform;

use Atlas\Platform\Core\HealthCheck;
use Atlas\Platform\Core\Module;
use Atlas\Platform\Organizations\OrganizationsModule;

final class Plugin
{
    private static ?self $instance = null;

    /** @var list<Module> */
    private array $modules = [];

    private bool $booted = false;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->modules = [
            new HealthCheck(),
            new OrganizationsModule(),
        ];

        foreach ($this->modules as $module) {
            $module->register();
        }

        $this->booted = true;

        do_action('atlas_platform_booted', $this);
    }
}
