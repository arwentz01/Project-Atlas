<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Health;

use Atlas\Platform\Core\Migrations\MigrationRunner;
use Atlas\Platform\Core\Modules\ModuleRegistry;

final class HealthService
{
    public function __construct(private ModuleRegistry $modules, private MigrationRunner $migrations) {}
    /** @return array<string, mixed> */
    public function summary(): array
    {
        $migration = $this->migrations->status();
        $moduleStatus = $this->modules->statuses();
        $healthy = ! in_array('failed', $moduleStatus, true) && $migration['inventory']->malformed === [] && $migration['inventory']->duplicates === [] && $migration['inventory']->gaps === [];
        return ['status' => $healthy ? 'ok' : 'degraded', 'plugin_version' => ATLAS_PLATFORM_VERSION, 'schema_version' => (string) get_option('atlas_platform_db_version', '0'), 'modules' => $moduleStatus, 'pending_migration_count' => count($migration['pending']), 'environment' => ['php_supported' => version_compare(PHP_VERSION, '8.1', '>='), 'wordpress_supported' => version_compare((string) get_bloginfo('version'), '6.5', '>=')], 'timestamp' => gmdate('c')];
    }
}
