<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Sources;

use Atlas\Platform\Organizations\Services\CurrentOrganizationResolver;
use InvalidArgumentException;

final class SourceWorkspaceAdminPage
{
    private string $hook = '';

    public function __construct(private SourceWorkspaceService $service, private SourceWorkspaceRepository $repo, private CurrentOrganizationResolver $orgs) {}

    public function register(): void
    {
        $this->hook = (string) add_submenu_page('atlas', __('Sources', 'atlas-platform'), __('Sources', 'atlas-platform'), 'atlas_upload_sources', 'atlas-sources', [$this, 'render']);
    }

    public function enqueue(string $hook): void
    {
        if ($hook === $this->hook) { wp_enqueue_style('atlas-preview', ATLAS_PLATFORM_URL . 'assets/css/atlas-preview.css', [], ATLAS_PLATFORM_VERSION); }
    }

    public function createDocument(): void
    {
        $this->guard('atlas_upload_sources', 'atlas_create_source_document');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $this->attempt(fn() => $this->service->createDocument($org?->id, get_current_user_id(), wp_unslash($_POST)));
    }

    public function updateDocumentStatus(): void
    {
        $this->guard('atlas_upload_sources', 'atlas_update_source_status');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $this->attempt(fn() => $this->service->updateDocumentStatus(sanitize_text_field(wp_unslash((string) ($_POST['source_document_id'] ?? ''))), $org?->id, sanitize_key(wp_unslash((string) ($_POST['status'] ?? ''))), sanitize_textarea_field(wp_unslash((string) ($_POST['notes'] ?? ''))), get_current_user_id()));
    }

    public function createSection(): void
    {
        $this->guard('atlas_upload_sources', 'atlas_create_source_section');
        $this->attempt(fn() => $this->service->createSection(sanitize_text_field(wp_unslash((string) ($_POST['source_document_id'] ?? ''))), wp_unslash($_POST), get_current_user_id()));
    }

    public function createCandidate(): void
    {
        $this->guard('atlas_review_extractions', 'atlas_create_extraction_candidate');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $this->attempt(fn() => $this->service->createCandidate(sanitize_text_field(wp_unslash((string) ($_POST['source_section_id'] ?? ''))), $org?->id, wp_unslash($_POST), get_current_user_id()));
    }

    public function reviewCandidate(): void
    {
        $this->guard('atlas_review_extractions', 'atlas_review_extraction_candidate');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $this->attempt(fn() => $this->service->reviewCandidate(sanitize_text_field(wp_unslash((string) ($_POST['candidate_id'] ?? ''))), sanitize_key(wp_unslash((string) ($_POST['status'] ?? ''))), get_current_user_id(), $org?->id));
    }

    public function createRequirement(): void
    {
        $this->guard('atlas_review_extractions', 'atlas_create_payer_requirement');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $this->attempt(fn() => $this->service->createRequirement($org?->id, get_current_user_id(), wp_unslash($_POST)));
    }

    public function reviewRequirement(): void
    {
        $this->guard('atlas_review_extractions', 'atlas_review_payer_requirement');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $this->attempt(fn() => $this->service->reviewRequirement(sanitize_text_field(wp_unslash((string) ($_POST['requirement_id'] ?? ''))), $org?->id, sanitize_key(wp_unslash((string) ($_POST['status'] ?? ''))), get_current_user_id()));
    }

    public function createInsuranceProfile(): void
    {
        $this->guard('atlas_review_extractions', 'atlas_create_insurance_profile');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $this->attempt(fn() => $this->service->createInsuranceProfile($org?->id, get_current_user_id(), wp_unslash($_POST)));
    }

    public function createDmeCategory(): void
    {
        $this->guard('atlas_review_extractions', 'atlas_create_dme_category');
        $this->attempt(fn() => $this->service->createDmeCategory(get_current_user_id(), wp_unslash($_POST)));
    }

    public function updateChecklist(): void
    {
        $this->guard('atlas_review_extractions', 'atlas_update_checklist_state');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $this->attempt(fn() => $this->service->updateChecklistState(sanitize_text_field(wp_unslash((string) ($_POST['requirement_id'] ?? ''))), $org?->id, sanitize_text_field(wp_unslash((string) ($_POST['hash'] ?? ''))), sanitize_text_field(wp_unslash((string) ($_POST['label'] ?? ''))), sanitize_key(wp_unslash((string) ($_POST['status'] ?? ''))), sanitize_textarea_field(wp_unslash((string) ($_POST['notes'] ?? ''))), get_current_user_id()));
    }

    public function render(): void
    {
        $this->guard('atlas_upload_sources', '');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $criteria = [
            'payer' => sanitize_text_field(wp_unslash((string) ($_GET['payer'] ?? ''))),
            'topic' => sanitize_text_field(wp_unslash((string) ($_GET['topic'] ?? ''))),
            'status' => sanitize_key(wp_unslash((string) ($_GET['status'] ?? ''))),
        ];
        $dashboard = $this->service->dashboard($org?->id);
        $summary = $dashboard['summary'];
        $reviewLanes = $dashboard['review_lanes'] ?? [];
        $freshnessAlerts = $dashboard['freshness_alerts'] ?? [];
        $documents = $dashboard['documents'];
        $candidates = $dashboard['candidates'];
        $requirements = $this->service->requirements($org?->id, $criteria, 50);
        $insuranceProfiles = $dashboard['insurance_profiles'] ?? [];
        $dmeCategories = $dashboard['dme_categories'] ?? [];
        $dmeMatches = $this->service->dmeRequirementMatches($org?->id, ['payer'=>$criteria['payer'], 'dme_category'=>$criteria['topic'], 'jurisdiction'=>''], 25);
        $checklist = $this->service->documentationChecklist($org?->id, $criteria + ['status' => $criteria['status'] ?: 'published'], 50);
        $evidenceId = sanitize_text_field(wp_unslash((string) ($_GET['requirement_id'] ?? '')));
        $evidence = $evidenceId === '' ? null : $this->service->requirementEvidence($evidenceId, $org?->id);
        $error = sanitize_text_field(wp_unslash((string) ($_GET['atlas_error'] ?? '')));
        require ATLAS_PLATFORM_DIR . 'templates/resources/sources.php';
    }

    private function guard(string $cap, string $nonce): void
    {
        if (! current_user_can($cap)) { wp_die(esc_html__('Not allowed.', 'atlas-platform'), '', ['response' => 403]); }
        if ($nonce !== '') { check_admin_referer($nonce); }
    }

    private function attempt(callable $action): never
    {
        try { $action(); $this->redirect(); } catch (InvalidArgumentException $e) { $this->redirect($e->getMessage()); }
    }

    private function redirect(string $error = ''): never
    {
        $args = ['page' => 'atlas-sources'];
        if ($error !== '') { $args['atlas_error'] = $error; }
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }
}
