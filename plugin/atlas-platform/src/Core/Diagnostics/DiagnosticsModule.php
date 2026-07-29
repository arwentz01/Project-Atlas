<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Diagnostics;

use Atlas\Platform\Core\Capabilities\CapabilityRegistry;
use Atlas\Platform\Core\Container\Container;
use Atlas\Platform\Core\Health\HealthService;
use Atlas\Platform\Core\Logging\Logger;
use Atlas\Platform\Core\Migrations\MigrationLock;
use Atlas\Platform\Core\Migrations\MigrationRunner;
use Atlas\Platform\Core\Modules\Module;
use Atlas\Platform\Core\Modules\ModuleRegistry;
use Throwable;

final class DiagnosticsModule implements Module
{
    public function __construct(private ModuleRegistry $modules, private CapabilityRegistry $capabilities, private MigrationRunner $migrations, private MigrationLock $lock, private HealthService $health, private Logger $logger, private ReleaseManifest $release) {}
    public function slug(): string { return 'diagnostics'; }
    public function version(): string { return ATLAS_PLATFORM_VERSION; }
    public function dependencies(): array { return ['health']; }
    public function register(Container $container): void {}
    public function boot(): void
    {
        add_action('admin_menu', [$this, 'registerPage']);
        add_action('admin_post_atlas_run_migrations', [$this, 'runMigrations']);
        add_action('admin_post_atlas_clear_stale_migration_lock', [$this, 'clearStaleLock']);
    }
    public function health(): array { return ['status' => 'ok']; }
    public function registerPage(): void
    {
        add_management_page(__('Atlas Diagnostics', 'atlas-platform'), __('Atlas Diagnostics', 'atlas-platform'), 'atlas_view_diagnostics', 'atlas-diagnostics', [$this, 'render']);
    }
    public function runMigrations(): void
    {
        $this->authorizeMutation('atlas_run_migrations');
        try { $this->migrations->runPending(); $notice = 'migrations-complete'; }
        catch (Throwable $exception) { $this->logger->log('error', 'diagnostics.migration_action_failed', 'Diagnostics migration action failed.', [], 'diagnostics', $exception); $notice = 'migration-error'; }
        wp_safe_redirect(add_query_arg('atlas_notice', $notice, admin_url('tools.php?page=atlas-diagnostics'))); exit;
    }
    public function clearStaleLock(): void
    {
        $this->authorizeMutation('atlas_run_migrations');
        $notice = $this->lock->clearStale() ? 'lock-cleared' : 'lock-not-stale';
        wp_safe_redirect(add_query_arg('atlas_notice', $notice, admin_url('tools.php?page=atlas-diagnostics'))); exit;
    }
    public function render(): void
    {
        if (! current_user_can('atlas_view_diagnostics')) { wp_die(esc_html__('You are not allowed to view Atlas diagnostics.', 'atlas-platform')); }
        $status = $this->migrations->status(); $health = $this->health->summary(); $theme = wp_get_theme(); $release = $this->release->verify();
        echo '<div class="wrap"><h1>' . esc_html__('Atlas Diagnostics', 'atlas-platform') . '</h1>';
        if (isset($_GET['atlas_notice'])) { echo '<div class="notice notice-info"><p>' . esc_html($this->notice(sanitize_key(wp_unslash((string) $_GET['atlas_notice'])))) . '</p></div>'; }
        echo '<h2>' . esc_html__('Overall health', 'atlas-platform') . '</h2><p><strong>' . esc_html((string) $health['status']) . '</strong></p>';
        $this->table(__('Environment', 'atlas-platform'), ['Atlas version' => ATLAS_PLATFORM_VERSION, 'Schema version' => (string) get_option('atlas_platform_db_version', '0'), 'WordPress' => (string) get_bloginfo('version'), 'PHP' => PHP_VERSION, 'Active theme' => $theme->get('Name') . ' ' . $theme->get('Version'), 'Multisite' => is_multisite() ? 'Yes' : 'No', 'REST API' => 'Registered', 'Plugin directory readable' => is_readable(ATLAS_PLATFORM_DIR) ? 'Yes' : 'No', 'Release manifest' => $release['valid'] ? 'Complete' : 'Incomplete: ' . ($release['error'] ?: implode(', ', $release['missing']))]);
        echo '<h2>' . esc_html__('Modules', 'atlas-platform') . '</h2><table class="widefat striped"><thead><tr><th>Module</th><th>Version</th><th>Dependencies</th><th>Status</th></tr></thead><tbody>';
        foreach ($this->modules->modules() as $slug => $module) { echo '<tr><td>' . esc_html($slug) . '</td><td>' . esc_html($module->version()) . '</td><td>' . esc_html(implode(', ', $module->dependencies()) ?: 'None') . '</td><td>' . esc_html($this->modules->statuses()[$slug] ?? 'unknown') . '</td></tr>'; }
        echo '</tbody></table>';
        echo '<h2>' . esc_html__('Capabilities', 'atlas-platform') . '</h2><table class="widefat striped"><thead><tr><th>Capability</th><th>Description</th><th>Administrator</th></tr></thead><tbody>';
        $assigned = $this->capabilities->administratorAssignments(); foreach ($this->capabilities->definitions() as $capability => $label) { echo '<tr><td><code>' . esc_html($capability) . '</code></td><td>' . esc_html($label) . '</td><td>' . esc_html(($assigned[$capability] ?? false) ? 'Assigned' : 'Missing') . '</td></tr>'; } echo '</tbody></table>';
        $inventory = $status['inventory'];
        $this->table(__('Migrations', 'atlas-platform'), ['Discovered' => implode(', ', array_keys($inventory->migrations)) ?: 'None', 'Completed' => implode(', ', $status['completed']) ?: 'None', 'Pending' => implode(', ', $status['pending']) ?: 'None', 'Latest' => $status['latest'] ?? 'None', 'Malformed' => $this->malformed($inventory->malformed), 'Duplicates' => implode(', ', $inventory->duplicates) ?: 'None', 'Sequence gaps' => implode(', ', $inventory->gaps) ?: 'None', 'First unable to run' => $inventory->malformed[0]['path'] ?? $inventory->duplicates[0] ?? $inventory->gaps[0] ?? 'None', 'Lock' => (string) ($this->lock->status()['state'] ?? 'unknown')]);
        if (current_user_can('atlas_run_migrations')) { $this->action('atlas_run_migrations', __('Run pending migrations', 'atlas-platform')); if (($this->lock->status()['state'] ?? '') === 'stale') { $this->action('atlas_clear_stale_migration_lock', __('Clear stale migration lock', 'atlas-platform')); } }
        echo '<h2>' . esc_html__('Recent operational errors', 'atlas-platform') . '</h2><pre style="white-space:pre-wrap">' . esc_html(wp_json_encode($this->logger->recentErrors(), JSON_PRETTY_PRINT)) . '</pre></div>';
    }
    private function authorizeMutation(string $capability): void { if (! current_user_can($capability)) { wp_die(esc_html__('You are not allowed to perform this action.', 'atlas-platform'), '', ['response' => 403]); } check_admin_referer('atlas_diagnostics_action'); }
    /** @param array<string, string> $rows */ private function table(string $title, array $rows): void { echo '<h2>' . esc_html($title) . '</h2><table class="widefat striped"><tbody>'; foreach ($rows as $label => $value) { echo '<tr><th scope="row">' . esc_html($label) . '</th><td>' . esc_html($value) . '</td></tr>'; } echo '</tbody></table>'; }
    private function action(string $action, string $label): void { echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin-top:1em"><input type="hidden" name="action" value="' . esc_attr($action) . '">'; wp_nonce_field('atlas_diagnostics_action'); submit_button($label, 'secondary', 'submit', false); echo '</form>'; }
    /** @param list<array<string, string>> $items */ private function malformed(array $items): string { return $items === [] ? 'None' : implode('; ', array_map(static fn(array $item): string => $item['path'] . ': ' . $item['error'], $items)); }
    private function notice(string $code): string { return ['migrations-complete' => 'Pending migrations completed.', 'migration-error' => 'A migration failed. Review recent operational errors.', 'lock-cleared' => 'The stale migration lock was cleared.', 'lock-not-stale' => 'No stale migration lock was found.'][$code] ?? 'Atlas Diagnostics updated.'; }
}
