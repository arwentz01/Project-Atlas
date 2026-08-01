<?php
declare(strict_types=1);

define('ATLAS_PLATFORM_DIR', dirname(__DIR__) . '/plugins/atlas-platform/');
define('ABSPATH', __DIR__ . '/');
require ATLAS_PLATFORM_DIR . 'src/Autoloader.php';
Atlas\Platform\Autoloader::register();

use Atlas\Platform\Preview\InMemoryPreviewResourceRepository;
use Atlas\Platform\Preview\PreviewService;
use Atlas\Platform\Core\Routes\RouteInventory;

function esc_html(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function esc_attr(string $value): string { return esc_html($value); }
function esc_url(string $value): string { return esc_html($value); }
function esc_html__(string $value, string $domain = ''): string { return esc_html($value); }
function esc_attr__(string $value, string $domain = ''): string { return esc_attr($value); }
function __(string $value, string $domain = ''): string { return $value; }
function _n(string $single, string $plural, int $number, string $domain = ''): string { return $number === 1 ? $single : $plural; }
function admin_url(string $path = ''): string { return 'https://example.test/wp-admin/' . ltrim($path, '/'); }

function preview_expect(bool $condition, string $message): void
{
    if (! $condition) { throw new RuntimeException($message); }
    echo "PASS: {$message}\n";
}

$service = new PreviewService(new InMemoryPreviewResourceRepository());
$home = $service->home('');
preview_expect($home['total'] === 4, 'preview home returns the bounded demonstration collection');
$coverage = $service->home('coverage');
preview_expect($coverage['total'] === 1 && $coverage['resources'][0]['title'] === 'Hospital bed coverage workflow', 'preview search filters resources by meaningful text');
$none = $service->home('not-present');
preview_expect($none['total'] === 0 && $none['resources'] === [], 'preview search returns an intentional empty state');
$routes = array_column((new RouteInventory())->all(), null, 'name');
preview_expect(isset($routes['atlas_home']) && $routes['atlas_home']['capability'] === 'atlas_access' && $routes['atlas_home']['mutates'] === false, 'preview page is recorded as a read-only capability-protected route');

$view = [
    'query' => '<script>alert("query")</script>',
    'resources' => [[
        'title' => '<script>alert("title")</script>',
        'type' => 'Type <unsafe>',
        'authority' => 'Authority <unsafe>',
        'status' => 'Status <unsafe>',
        'summary' => 'Summary <unsafe>',
        'updated' => 'Updated <unsafe>',
        'tone' => 'blue" onclick="alert(1)',
    ]],
    'total' => 1,
    'user_name' => '<img src=x onerror=alert(1)>',
    'organization_name' => '<script>organization</script>',
    'has_organization' => false,
    'navigation' => [[
        'slug' => 'atlas',
        'label' => '<script>Home</script>',
        'icon' => 'dashicons-home" onclick="alert(1)',
        'url' => 'https://example.test/wp-admin/admin.php?page=atlas',
        'capability' => 'atlas_access',
        'current' => true,
    ]],
];
ob_start();
require ATLAS_PLATFORM_DIR . 'templates/preview/home.php';
$html = (string) ob_get_clean();
preview_expect(! str_contains($html, '<script>') && ! str_contains($html, 'onclick="alert(1)'), 'preview template escapes untrusted content and attributes');
preview_expect(str_contains($html, '&lt;script&gt;alert(&quot;title&quot;)&lt;/script&gt;'), 'preview template preserves escaped content for display');

echo "All preview tests passed.\n";
