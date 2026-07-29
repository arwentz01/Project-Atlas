<?php

declare(strict_types=1);

define('ATLAS_PLATFORM_DIR', dirname(__DIR__) . '/plugin/atlas-platform/');
require ATLAS_PLATFORM_DIR . 'src/Autoloader.php';
Atlas\Platform\Autoloader::register();

$GLOBALS['atlas_test_capabilities'] = [];
function __(string $text): string { return $text; }
function admin_url(string $path): string { return 'https://example.test/wp-admin/' . $path; }
function current_user_can(string $capability): bool { return in_array($capability, $GLOBALS['atlas_test_capabilities'], true); }
function apply_filters(string $hook, mixed $value): mixed { return $value; }

use Atlas\Platform\Core\Admin\AdminNavigation;

$assert = static function (bool $condition, string $message): void {
    if (! $condition) { throw new RuntimeException($message); }
    echo "PASS: {$message}\n";
};

$navigation = new AdminNavigation();
$assert($navigation->visible('atlas') === [], 'users without Atlas capabilities see no Atlas destinations');
$GLOBALS['atlas_test_capabilities'] = ['atlas_access'];
$items = $navigation->visible('atlas');
$assert(count($items) === 1 && $items[0]['slug'] === 'atlas' && $items[0]['current'], 'Atlas users see only implemented authorized destinations');
$GLOBALS['atlas_test_capabilities'][] = 'atlas_view_diagnostics';
$items = $navigation->visible('atlas-diagnostics');
$assert(count($items) === 2 && $items[1]['current'], 'diagnostic navigation visibility and current state share capability policy');
echo "All admin navigation tests passed.\n";
