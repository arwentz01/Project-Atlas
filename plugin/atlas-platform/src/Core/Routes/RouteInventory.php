<?php
declare(strict_types=1);

namespace Atlas\Platform\Core\Routes;

final class RouteInventory
{
    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return [
            ['name'=>'workflow_draft_create','method'=>'POST','path'=>'/atlas/v1/workflows/drafts','module'=>'workflows','capability'=>'atlas_manage_workflows','registered'=>true,'implemented'=>true,'navigation'=>false,'mutates'=>true],
            ['name'=>'resource_draft_create','method'=>'POST','path'=>'/atlas/v1/resources/drafts','module'=>'resources','capability'=>'atlas_create_resources','registered'=>true,'implemented'=>true,'navigation'=>false,'mutates'=>true],
            ['name'=>'organization_create','method'=>'POST','path'=>'/atlas/v1/organizations','module'=>'organizations','capability'=>'atlas_manage_organizations','registered'=>true,'implemented'=>true,'navigation'=>false,'mutates'=>true],
            ['name' => 'atlas_home', 'method' => 'GET', 'path' => 'admin.php?page=atlas', 'module' => 'preview', 'capability' => 'atlas_access', 'registered' => true, 'implemented' => true, 'navigation' => true, 'mutates' => false],
            ['name' => 'health', 'method' => 'GET', 'path' => '/wp-json/atlas/v1/health', 'module' => 'health', 'capability' => 'public', 'registered' => true, 'implemented' => true, 'navigation' => false, 'mutates' => false],
            ['name' => 'current_organization', 'method' => 'GET', 'path' => '/wp-json/atlas/v1/organizations/current', 'module' => 'organizations', 'capability' => 'atlas_access', 'registered' => true, 'implemented' => true, 'navigation' => false, 'mutates' => false],
            ['name' => 'resource_detail', 'method' => 'GET', 'path' => '/wp-json/atlas/v1/resources/{id}', 'module' => 'resources', 'capability' => 'atlas_access', 'registered' => true, 'implemented' => true, 'navigation' => false, 'mutates' => false],
            ['name' => 'resource_search', 'method' => 'GET', 'path' => '/wp-json/atlas/v1/resources', 'module' => 'resources', 'capability' => 'atlas_access', 'registered' => true, 'implemented' => true, 'navigation' => false, 'mutates' => false],
            ['name' => 'resource_view', 'method' => 'GET', 'path' => 'admin.php?page=atlas-resource&id={id}', 'module' => 'resources', 'capability' => 'atlas_access', 'registered' => true, 'implemented' => true, 'navigation' => false, 'mutates' => false],
            ['name'=>'resource_transition','method'=>'POST','path'=>'/wp-json/atlas/v1/resource-versions/{id}/transitions','module'=>'resources','capability'=>'atlas_review_resources or atlas_publish_resources','registered'=>true,'implemented'=>true,'navigation'=>false,'mutates'=>true],
            ['name'=>'patient_resource_variant','method'=>'POST','path'=>'/wp-json/atlas/v1/patient-resources/{id}/variants','module'=>'patient_resources','capability'=>'atlas_manage_branding','registered'=>true,'implemented'=>true,'navigation'=>false,'mutates'=>true],
            ['name'=>'workflow_detail','method'=>'GET','path'=>'/wp-json/atlas/v1/workflows/{id}','module'=>'workflows','capability'=>'atlas_access','registered'=>true,'implemented'=>true,'navigation'=>false,'mutates'=>false],
            ['name'=>'release_readiness','method'=>'GET','path'=>'/wp-json/atlas/v1/diagnostics/readiness','module'=>'diagnostics','capability'=>'atlas_view_diagnostics','registered'=>true,'implemented'=>true,'navigation'=>false,'mutates'=>false],
            ['name' => 'diagnostics', 'method' => 'GET', 'path' => 'tools.php?page=atlas-diagnostics', 'module' => 'diagnostics', 'capability' => 'atlas_view_diagnostics', 'registered' => true, 'implemented' => true, 'navigation' => true, 'mutates' => false],
            ['name' => 'run_migrations', 'method' => 'POST', 'path' => 'admin-post.php?action=atlas_run_migrations', 'module' => 'diagnostics', 'capability' => 'atlas_run_migrations', 'registered' => true, 'implemented' => true, 'navigation' => false, 'mutates' => true],
            ['name' => 'clear_stale_lock', 'method' => 'POST', 'path' => 'admin-post.php?action=atlas_clear_stale_migration_lock', 'module' => 'diagnostics', 'capability' => 'atlas_run_migrations', 'registered' => true, 'implemented' => true, 'navigation' => false, 'mutates' => true],
        ];
    }
}
