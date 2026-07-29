<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
function plugin_dir_path(string $file): string { return dirname($file) . '/'; }
function plugin_dir_url(string $file): string { return 'https://example.test/plugins/atlas-platform/'; }
function register_activation_hook(string $file, callable $callback): void {}
function register_deactivation_hook(string $file, callable $callback): void {}
function add_action(string $hook, callable $callback): void {}
require dirname(__DIR__) . '/plugin/atlas-platform/atlas-platform.php';
if (! function_exists('atlas')) { throw new RuntimeException('atlas() helper was not loaded.'); }
echo "Plugin bootstrap loaded without a fatal error.\n";
