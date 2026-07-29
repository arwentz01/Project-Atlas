<?php
declare(strict_types=1);

namespace Atlas\Platform;

use Atlas\Platform\Core\Capabilities\CapabilityRegistry;
use Atlas\Platform\Core\Audit\AuditRecorder;
use Atlas\Platform\Core\Audit\WordPressAuditRecorder;
use Atlas\Platform\Core\Container\Container;
use Atlas\Platform\Core\Diagnostics\DiagnosticsModule;
use Atlas\Platform\Core\Health\HealthModule;
use Atlas\Platform\Core\Logging\Logger;
use Atlas\Platform\Core\Logging\WordPressLogger;
use Atlas\Platform\Core\Migrations\MigrationDiscovery;
use Atlas\Platform\Core\Migrations\MigrationLock;
use Atlas\Platform\Core\Migrations\MigrationRepository;
use Atlas\Platform\Core\Migrations\MigrationRunner;
use Atlas\Platform\Core\Migrations\MigrationStore;
use Atlas\Platform\Core\Migrations\Lock;
use Atlas\Platform\Core\Modules\ModuleRegistry;
use Atlas\Platform\Organizations\OrganizationsModule;
use Atlas\Platform\Organizations\Repositories\MembershipRepository;
use Atlas\Platform\Organizations\Repositories\OrganizationRepository;
use Atlas\Platform\Organizations\Repositories\WordPressMembershipRepository;
use Atlas\Platform\Organizations\Repositories\WordPressOrganizationRepository;
use Atlas\Platform\Organizations\Services\CurrentOrganizationResolver;
use Atlas\Platform\Organizations\Services\DefaultCurrentOrganizationResolver;
use Atlas\Platform\Organizations\Onboarding\OrganizationOnboardingRepository;
use Atlas\Platform\Organizations\Onboarding\WordPressOrganizationOnboardingRepository;
use Atlas\Platform\Preview\InMemoryPreviewResourceRepository;
use Atlas\Platform\Preview\PreviewModule;
use Atlas\Platform\Preview\PreviewResourceRepository;
use Atlas\Platform\Resources\Repositories\ResourceRepository;
use Atlas\Platform\Resources\Repositories\WordPressResourceRepository;
use Atlas\Platform\Resources\ResourcesModule;
use Atlas\Platform\Resources\Authoring\ResourceDraftRepository;
use Atlas\Platform\Resources\Authoring\WordPressResourceDraftRepository;
use Atlas\Platform\Resources\Search\ResourceSearchRepository;
use Atlas\Platform\Resources\Search\WordPressResourceSearchRepository;
use Atlas\Platform\Resources\Editorial\EditorialRepository;
use Atlas\Platform\Resources\Editorial\EditorialTransitionPolicy;
use Atlas\Platform\Resources\Editorial\WordPressEditorialRepository;
use Atlas\Platform\PatientResources\PatientResourcesModule;
use Atlas\Platform\PatientResources\Repositories\BrandingRepository;
use Atlas\Platform\PatientResources\Repositories\PatientResourceAccessRepository;
use Atlas\Platform\PatientResources\Repositories\VariantRepository;
use Atlas\Platform\PatientResources\Repositories\WordPressBrandingRepository;
use Atlas\Platform\PatientResources\Repositories\WordPressPatientResourceAccessRepository;
use Atlas\Platform\PatientResources\Repositories\WordPressVariantRepository;
use Atlas\Platform\Workflows\Repositories\WorkflowRepository;
use Atlas\Platform\Workflows\Repositories\WordPressWorkflowRepository;
use Atlas\Platform\Workflows\WorkflowsModule;
use Atlas\Platform\Workflows\Authoring\WorkflowDraftRepository;
use Atlas\Platform\Workflows\Authoring\WordPressWorkflowDraftRepository;

final class Plugin
{
    private static ?self $instance = null;
    private Container $container;
    private bool $booted = false;

    private function __construct()
    {
        global $wpdb;
        $this->container = new Container();
        $this->container->instance(Container::class, $this->container);
        $this->container->singleton(Logger::class, WordPressLogger::class);
        $this->container->singleton(CapabilityRegistry::class);
        $this->container->singleton(PreviewResourceRepository::class, InMemoryPreviewResourceRepository::class);
        $this->container->instance(OrganizationRepository::class, new WordPressOrganizationRepository($wpdb));
        $this->container->instance(MembershipRepository::class, new WordPressMembershipRepository($wpdb));
        $this->container->instance(AuditRecorder::class, new WordPressAuditRecorder($wpdb));
        $this->container->instance(OrganizationOnboardingRepository::class, new WordPressOrganizationOnboardingRepository($wpdb,$this->container->get(AuditRecorder::class)));
        $this->container->singleton(CurrentOrganizationResolver::class, DefaultCurrentOrganizationResolver::class);
        $this->container->instance(MigrationDiscovery::class, new MigrationDiscovery(ATLAS_PLATFORM_DIR . 'migrations', ATLAS_PLATFORM_DIR));
        $repository = new MigrationRepository($wpdb);
        $this->container->instance(MigrationRepository::class, $repository);
        $this->container->instance(MigrationStore::class, $repository);
        $lock = new MigrationLock();
        $this->container->instance(MigrationLock::class, $lock);
        $this->container->instance(Lock::class, $lock);
        $this->container->instance(ResourceRepository::class, new WordPressResourceRepository($wpdb, $this->container->get(Logger::class)));
        $this->container->instance(ResourceDraftRepository::class, new WordPressResourceDraftRepository($wpdb,$this->container->get(AuditRecorder::class)));
        $this->container->instance(ResourceSearchRepository::class, new WordPressResourceSearchRepository($wpdb));
        $this->container->instance(EditorialRepository::class,new WordPressEditorialRepository($wpdb,$this->container->get(EditorialTransitionPolicy::class)));
        $this->container->instance(BrandingRepository::class,new WordPressBrandingRepository($wpdb));
        $this->container->instance(VariantRepository::class,new WordPressVariantRepository($wpdb));
        $this->container->instance(PatientResourceAccessRepository::class,new WordPressPatientResourceAccessRepository($wpdb));
        $this->container->instance(WorkflowRepository::class,new WordPressWorkflowRepository($wpdb));
        $this->container->instance(WorkflowDraftRepository::class,new WordPressWorkflowDraftRepository($wpdb,$this->container->get(AuditRecorder::class)));
        $this->container->singleton(MigrationRunner::class, fn(Container $container): MigrationRunner => new MigrationRunner($container->get(MigrationDiscovery::class), $container->get(MigrationStore::class), $container->get(Lock::class), $container->get(Logger::class), $wpdb));
        $registry = new ModuleRegistry($this->container, $this->container->get(Logger::class));
        $this->container->instance(ModuleRegistry::class, $registry);
    }
    public static function instance(): self { return self::$instance ??= new self(); }
    public function container(): Container { return $this->container; }
    public function boot(): void
    {
        if ($this->booted) { return; }
        $registry = $this->container->get(ModuleRegistry::class);
        $registry->add($this->container->get(HealthModule::class));
        $registry->add($this->container->get(DiagnosticsModule::class));
        $registry->add($this->container->get(OrganizationsModule::class));
        $registry->add($this->container->get(ResourcesModule::class));
        $registry->add($this->container->get(PatientResourcesModule::class));
        $registry->add($this->container->get(WorkflowsModule::class));
        $registry->add($this->container->get(PreviewModule::class));
        $registry->bootAll();
        if (get_option('atlas_platform_capability_version', '') !== ATLAS_PLATFORM_VERSION) {
            $this->container->get(CapabilityRegistry::class)->synchronize();
        }
        $this->booted = true;
        do_action('atlas_platform_booted', $this);
    }
    public function activate(): void
    {
        $this->container->get(CapabilityRegistry::class)->synchronize();
        $this->container->get(MigrationRunner::class)->runPending();
        update_option('atlas_platform_version', ATLAS_PLATFORM_VERSION, false);
        if (get_option('atlas_platform_installed_at', '') === '') { add_option('atlas_platform_installed_at', gmdate('c'), '', false); }
        $this->container->get(Logger::class)->log('info', 'plugin.activated', 'Atlas Platform activated.', ['version' => ATLAS_PLATFORM_VERSION]);
    }
}
