<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Modules;

use Atlas\Platform\Core\Container\Container;

interface Module
{
    public function slug(): string;
    public function version(): string;
    /** @return list<string> */
    public function dependencies(): array;
    public function register(Container $container): void;
    public function boot(): void;
    /** @return array<string, mixed> */
    public function health(): array;
}
