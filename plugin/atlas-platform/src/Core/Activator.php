<?php

declare(strict_types=1);

namespace Atlas\Platform\Core;

use Atlas\Platform\Plugin;
use RuntimeException;

final class Activator
{
    public static function activate(): void
    {
        if (version_compare(PHP_VERSION, '8.1', '<')) {
            deactivate_plugins(plugin_basename(ATLAS_PLATFORM_FILE));
            throw new RuntimeException('Atlas Platform requires PHP 8.1 or newer.');
        }

        if (version_compare((string) get_bloginfo('version'), '6.5', '<')) {
            deactivate_plugins(plugin_basename(ATLAS_PLATFORM_FILE));
            throw new RuntimeException('Atlas Platform requires WordPress 6.5 or newer.');
        }

        Plugin::instance()->activate();
        flush_rewrite_rules(false);
    }
}
