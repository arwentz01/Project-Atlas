<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Capabilities;

use Atlas\Platform\Core\Logging\Logger;

final class CapabilityRegistry
{
    /** @var array<string, string> */
    private const DEFINITIONS = [
        'atlas_access' => 'Access Atlas',
        'atlas_manage_atlas' => 'Manage the Atlas platform',
        'atlas_view_diagnostics' => 'View Atlas diagnostics',
        'atlas_manage_organizations' => 'Manage organizations',
        'atlas_manage_branding' => 'Manage organization branding',
        'atlas_manage_members' => 'Manage organization members',
        'atlas_create_resources' => 'Create resource drafts',
        'atlas_edit_resources' => 'Edit resource drafts',
        'atlas_manage_workflows' => 'Manage operational workflows',
        'atlas_publish_resources' => 'Publish resources',
        'atlas_review_resources' => 'Review resources',
        'atlas_create_packets' => 'Create Atlas packets',
        'atlas_upload_sources' => 'Upload and register source documents',
        'atlas_review_extractions' => 'Review extracted candidates and payer requirements',
        'atlas_run_migrations' => 'Run Atlas migrations',
    ];

    public function __construct(private Logger $logger) {}

    /** @return array<string, string> */
    public function definitions(): array { return self::DEFINITIONS; }

    public function synchronize(): void
    {
        $role = get_role('administrator');
        if ($role !== null) {
            foreach (array_keys(self::DEFINITIONS) as $capability) {
                $role->add_cap($capability);
            }
        }
        update_option('atlas_platform_capability_version', ATLAS_PLATFORM_VERSION, false);
        $this->logger->log('info', 'capabilities.synchronized', 'Atlas capabilities synchronized.', ['count' => count(self::DEFINITIONS)]);
    }

    /** @return array<string, bool> */
    public function administratorAssignments(): array
    {
        $role = get_role('administrator');
        $result = [];
        foreach (self::DEFINITIONS as $capability => $_label) {
            $result[$capability] = $role !== null && $role->has_cap($capability);
        }
        return $result;
    }
}
