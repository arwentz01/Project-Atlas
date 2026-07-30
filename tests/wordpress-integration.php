<?php
declare(strict_types=1);

use Atlas\Platform\Core\Capabilities\CapabilityRegistry;
use Atlas\Platform\Core\Migrations\MigrationRunner;
use Atlas\Platform\Core\Modules\ModuleRegistry;
use Atlas\Platform\Core\Routes\RouteInventory;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "WordPress must be loaded before this integration suite.\n");
    exit(1);
}

/** @var list<string> $failures */
$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) {
        echo "PASS: {$message}\n";
        return;
    }

    $failures[] = $message;
    echo "FAIL: {$message}\n";
};

$pluginFile = WP_PLUGIN_DIR . '/atlas-platform/atlas-platform.php';
$assert(is_file($pluginFile), 'the deployable Atlas plugin is installed in wp-content/plugins');
if (! is_file($pluginFile)) {
    exit(1);
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
if (! is_plugin_active('atlas-platform/atlas-platform.php')) {
    $activation = activate_plugin('atlas-platform/atlas-platform.php');
    $assert(! is_wp_error($activation), 'Atlas activates through the WordPress plugin API');
    if (is_wp_error($activation)) {
        fwrite(STDERR, esc_html($activation->get_error_message()) . "\n");
        exit(1);
    }
} else {
    $assert(true, 'Atlas is active in WordPress');
}

if (! function_exists('atlas')) {
    require_once $pluginFile;
}

atlas()->boot();
$container = atlas()->container();
$assert($container->has(ModuleRegistry::class), 'the Atlas application exposes its module registry');

$modules = $container->get(ModuleRegistry::class);
$statuses = $modules->statuses();
foreach (['health', 'diagnostics', 'organizations', 'resources', 'patient_resources', 'workflows', 'preview'] as $module) {
    $assert(($statuses[$module] ?? '') === 'booted', sprintf('the %s module is booted', $module));
}

$capabilities = $container->get(CapabilityRegistry::class);
$administrator = get_role('administrator');
$assert($administrator !== null, 'WordPress has an administrator role');
if ($administrator !== null) {
    foreach (array_keys($capabilities->definitions()) as $capability) {
        $assert($administrator->has_cap($capability), sprintf('administrators receive %s', $capability));
    }
}

$runner = $container->get(MigrationRunner::class);
$initial = $runner->status();
$applied = $runner->runPending();
$before = $runner->status();
$firstRepeat = $runner->runPending();
$secondRepeat = $runner->runPending();
$after = $runner->status();
$assert(count($applied) === count($initial['pending']), 'the integration gate applies every initially pending migration');
$assert($firstRepeat === [], 'the first repeated migration run makes no changes');
$assert($secondRepeat === [], 'a second complete migration run makes no changes');
$assert($before['completed'] === $after['completed'], 'completed migration records remain stable across repeated runs');
$assert($after['pending'] === [], 'the deployed migration inventory has no pending migrations');
$assert($after['inventory']->malformed === [], 'the deployed migration inventory has no malformed files');
$assert($after['inventory']->duplicates === [], 'the deployed migration inventory has no duplicate identifiers');
$assert($after['inventory']->gaps === [], 'the deployed migration inventory has no sequence gaps');

do_action('rest_api_init');
$server = rest_get_server();
$routes = $server->get_routes();
$assert(isset($routes['/atlas/v1/health']), 'the public health route is registered');
$assert(isset($routes['/atlas/v1/organizations/current']), 'the current organization route is registered');
$assert(isset($routes['/atlas/v1/organizations']), 'the organization onboarding route is registered');
$assert(isset($routes['/atlas/v1/resources/(?P<id>[a-fA-F0-9-]{36})']), 'the resource detail route is registered');
$assert(isset($routes['/atlas/v1/resources']), 'the resource search route is registered');
$assert(isset($routes['/atlas/v1/resources/drafts']), 'the resource draft creation route is registered');
$assert(isset($routes['/atlas/v1/resource-versions/(?P<id>[a-fA-F0-9-]{36})/transitions']), 'the editorial transition route is registered');
$assert(isset($routes['/atlas/v1/sources/dashboard']), 'the source workspace dashboard route is registered');
$assert(isset($routes['/atlas/v1/sources/documents']), 'the source document creation route is registered');
$assert(isset($routes['/atlas/v1/payer-requirements']), 'the payer requirement collection route is registered');
$assert(isset($routes['/atlas/v1/payer-requirements/(?P<id>[a-fA-F0-9-]{36})/review']), 'the payer requirement review route is registered');
$assert(isset($routes['/atlas/v1/packets']), 'the packet creation route is registered');
$assert(isset($routes['/atlas/v1/packets/(?P<id>[a-fA-F0-9-]{36})']), 'the packet preview route is registered');
$assert(isset($routes['/atlas/v1/patient-resources/(?P<id>[a-fA-F0-9-]{36})/variants']), 'the patient resource variant route is registered');
$assert(isset($routes['/atlas/v1/workflows/(?P<id>[a-fA-F0-9-]{36})']), 'the workflow detail route is registered');
$assert(isset($routes['/atlas/v1/workflows/drafts']), 'the workflow draft creation route is registered');
$assert(isset($routes['/atlas/v1/diagnostics/readiness']), 'the protected release readiness route is registered');
$healthRequest = new WP_REST_Request('GET', '/atlas/v1/health');
$healthResponse = rest_do_request($healthRequest);
$healthData = $healthResponse->get_data();
$assert($healthResponse->get_status() === 200, 'the public health route returns HTTP 200');
$assert(is_array($healthData) && ($healthData['status'] ?? '') === 'ok', 'the public health route reports an ok status');
foreach (['plugin_version', 'schema_version', 'modules', 'pending_migration_count', 'environment', 'timestamp'] as $field) {
    $assert(is_array($healthData) && array_key_exists($field, $healthData), sprintf('the health response contains %s', $field));
}
$encodedHealth = wp_json_encode($healthData);
$assert(is_string($encodedHealth) && ! str_contains($encodedHealth, ABSPATH), 'the public health response does not expose the WordPress absolute path');
$assert(is_string($encodedHealth) && ! str_contains(strtolower($encodedHealth), 'sql'), 'the public health response does not expose SQL details');
$organizationResponse = rest_do_request(new WP_REST_Request('GET', '/atlas/v1/organizations/current'));
$assert(in_array($organizationResponse->get_status(), [401, 403], true), 'an unauthenticated request cannot read organization context');
$protectedRequests = [
    new WP_REST_Request('POST', '/atlas/v1/organizations'),
    new WP_REST_Request('POST', '/atlas/v1/resources/drafts'),
    new WP_REST_Request('POST', '/atlas/v1/sources/documents'),
    new WP_REST_Request('POST', '/atlas/v1/payer-requirements'),
    new WP_REST_Request('POST', '/atlas/v1/packets'),
    new WP_REST_Request('POST', '/atlas/v1/workflows/drafts'),
    new WP_REST_Request('GET', '/atlas/v1/diagnostics/readiness'),
];
foreach ($protectedRequests as $protectedRequest) {
    $protectedResponse = rest_do_request($protectedRequest);
    $assert(in_array($protectedResponse->get_status(), [401, 403], true), sprintf('an unauthenticated %s request to %s is denied', $protectedRequest->get_method(), $protectedRequest->get_route()));
}
$invalidMethod = rest_do_request(new WP_REST_Request('DELETE', '/atlas/v1/health'));
$assert($invalidMethod->get_status() === 404, 'the health route rejects an invalid method');

global $menu;
$administrators = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
if ($administrators !== []) {
    wp_set_current_user((int) $administrators[0]);
}
do_action('admin_menu');
$atlasMenu = array_values(array_filter(
    is_array($menu) ? $menu : [],
    static fn(array $item): bool => ($item[2] ?? '') === 'atlas'
));
$assert(count($atlasMenu) === 1, 'WordPress registers one top-level Atlas navigation item');
$assert(($atlasMenu[0][1] ?? '') === 'atlas_access', 'Atlas navigation uses the atlas_access destination capability');
$assert(($atlasMenu[0][2] ?? '') === 'atlas', 'Atlas navigation keeps the application shell as its top-level destination');

global $submenu;
$atlasSubmenu = is_array($submenu['atlas'] ?? null) ? $submenu['atlas'] : [];
$atlasSubmenuSlugs = array_column($atlasSubmenu, 2);
$assert(($atlasSubmenuSlugs[0] ?? '') === 'atlas', 'Atlas home is registered before feature submenus');
foreach (['atlas-organizations', 'atlas-resources', 'atlas-packets', 'atlas-sources', 'atlas-workflows'] as $submenuSlug) {
    $assert(in_array($submenuSlug, $atlasSubmenuSlugs, true), sprintf('Atlas registers the %s submenu beneath the application shell', $submenuSlug));
}
foreach (['atlas_invite_member','atlas_accept_invitation','atlas_revoke_invitation','atlas_update_member_roles','atlas_remove_member','atlas_save_branding'] as $action) {
    $assert(has_action('admin_post_' . $action) !== false, sprintf('the %s administration action is registered', $action));
}
foreach (['atlas_create_revision','atlas_archive_resource','atlas_assign_reviewer','atlas_add_review_note'] as $action) {
    $assert(has_action('admin_post_' . $action) !== false, sprintf('the %s resource governance action is registered', $action));
}
foreach (['atlas_create_packet','atlas_add_packet_item','atlas_remove_packet_item','atlas_update_packet_status','atlas_create_source_document','atlas_update_source_status','atlas_create_source_section','atlas_create_extraction_candidate','atlas_review_extraction_candidate','atlas_create_payer_requirement','atlas_review_payer_requirement'] as $action) {
    $assert(has_action('admin_post_' . $action) !== false, sprintf('the %s source-to-requirement action is registered', $action));
}
$assert(has_action('admin_post_nopriv_atlas_accept_invitation') !== false, 'logged-out invitation acceptance redirects through the invitation handler');

$inventory = array_column((new RouteInventory())->all(), null, 'name');
$assert(($inventory['atlas_home']['capability'] ?? '') === 'atlas_access', 'route inventory and Atlas navigation use the same capability');
$assert(($inventory['diagnostics']['capability'] ?? '') === 'atlas_view_diagnostics', 'diagnostics inventory uses the diagnostics capability');
$assert(($inventory['organizations_admin']['capability'] ?? '') === 'atlas_access', 'organization administration navigation and destination share atlas_access');
$assert(($inventory['resources_admin']['capability'] ?? '') === 'atlas_access', 'resource library navigation and destination share atlas_access');
$assert(($inventory['resource_create_admin']['capability'] ?? '') === 'atlas_create_resources', 'resource authoring navigation and destination share the authoring capability');
$assert(($inventory['packet_builder']['capability'] ?? '') === 'atlas_create_packets', 'packet navigation and destination share packet capability');
$assert(($inventory['source_workspace']['capability'] ?? '') === 'atlas_upload_sources', 'source navigation and destination share source capability');
$assert(function_exists('wp_generate_uuid4'), 'the WordPress UUID API required by mutations is available');

$testUserId = wp_insert_user([
    'user_login' => 'atlas_integration_' . wp_generate_password(10, false, false),
    'user_pass' => wp_generate_password(32, true, true),
    'user_email' => 'atlas-integration-' . wp_generate_password(8, false, false) . '@example.test',
    'role' => 'subscriber',
]);
$assert(! is_wp_error($testUserId), 'the suite creates a disposable unauthorized user');
if (! is_wp_error($testUserId)) {
    wp_set_current_user((int) $testUserId);
    $assert(! current_user_can('atlas_access'), 'an ordinary subscriber cannot access Atlas');
    $assert(! current_user_can('atlas_view_diagnostics'), 'an ordinary subscriber cannot view Atlas diagnostics');
    $assert(! current_user_can('atlas_run_migrations'), 'an ordinary subscriber cannot run Atlas migrations');
    foreach ($protectedRequests as $protectedRequest) {
        $subscriberResponse = rest_do_request($protectedRequest);
        $assert(in_array($subscriberResponse->get_status(), [401, 403], true), sprintf('a subscriber cannot call %s %s directly', $protectedRequest->get_method(), $protectedRequest->get_route()));
    }
    wp_delete_user((int) $testUserId);
}

if ($failures !== []) {
    fwrite(STDERR, sprintf("\n%d WordPress integration assertion(s) failed.\n", count($failures)));
    exit(1);
}

echo "All WordPress integration checks passed.\n";
