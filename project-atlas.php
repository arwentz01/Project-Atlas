<?php
/**
 * Plugin Name: Project Atlas
 * Description: Front-end healthcare operations workspace for clinicians and care teams.
 * Version: 0.3.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Project Atlas
 * Text Domain: project-atlas
 */

declare(strict_types=1);
if(!defined('ABSPATH'))exit;
define('ATLAS_VERSION','0.3.0');define('ATLAS_FILE',__FILE__);define('ATLAS_DIR',plugin_dir_path(__FILE__));define('ATLAS_URL',plugin_dir_url(__FILE__));
require_once ATLAS_DIR.'src/Support/Fixtures.php';
require_once ATLAS_DIR.'src/FrontEnd/Views/Insurance.php';
require_once ATLAS_DIR.'src/FrontEnd/App.php';
require_once ATLAS_DIR.'src/Plugin.php';
Atlas\Plugin::boot();
register_activation_hook(__FILE__,[Atlas\Plugin::class,'activate']);register_deactivation_hook(__FILE__,[Atlas\Plugin::class,'deactivate']);
