<?php
/**
 * Plugin Name: Atlas Platform
 * Plugin URI: https://github.com/arwentz01/Project-Atlas
 * Description: Clinical operations platform foundation for Project Atlas.
 * Version: 0.22.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Project Atlas
 * License: GPL-2.0-or-later
 * Text Domain: atlas-platform
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('ATLAS_PLATFORM_VERSION', '0.22.0');
define('ATLAS_PLATFORM_FILE', __FILE__);
define('ATLAS_PLATFORM_DIR', plugin_dir_path(__FILE__));
define('ATLAS_PLATFORM_URL', plugin_dir_url(__FILE__));

require_once ATLAS_PLATFORM_DIR . 'src/Autoloader.php';

Atlas\Platform\Autoloader::register();

if (! function_exists('atlas')) {
    /** Return the single Atlas application instance. */
    function atlas(): Atlas\Platform\Plugin
    {
        return Atlas\Platform\Plugin::instance();
    }
}

register_activation_hook(__FILE__, [Atlas\Platform\Core\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [Atlas\Platform\Core\Deactivator::class, 'deactivate']);

add_action('plugins_loaded', static function (): void {
    Atlas\Platform\Plugin::instance()->boot();
});
