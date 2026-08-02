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
        $this->attempt(function () use ($org): void {
            $input = wp_unslash($_POST);
            $file = is_array($_FILES['source_file'] ?? null) ? $this->preserveUploadedFile($_FILES['source_file']) : [];
            $this->service->createDocument($org?->id, get_current_user_id(), $input + $file);
        });
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

    public function saveDocumentPage(): void
    {
        $this->guard('atlas_upload_sources', 'atlas_save_source_page');
        $this->attempt(fn() => $this->service->saveDocumentPageText(sanitize_text_field(wp_unslash((string) ($_POST['source_document_id'] ?? ''))), wp_unslash($_POST), get_current_user_id()));
    }

    public function createCandidate(): void
    {
        $this->guard('atlas_review_extractions', 'atlas_create_extraction_candidate');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $this->attempt(fn() => $this->service->createCandidate(sanitize_text_field(wp_unslash((string) ($_POST['source_section_id'] ?? ''))), $org?->id, wp_unslash($_POST), get_current_user_id()));
    }

    public function createCandidateFromPage(): void
    {
        $this->guard('atlas_review_extractions', 'atlas_create_candidate_from_page');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $this->attempt(fn() => $this->service->createCandidateFromPage(sanitize_text_field(wp_unslash((string) ($_POST['source_document_id'] ?? ''))), $org?->id, wp_unslash($_POST), get_current_user_id()));
    }

    public function reviewCandidate(): void
    {
        $this->guard('atlas_review_extractions', 'atlas_review_extraction_candidate');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $this->attempt(fn() => $this->service->reviewCandidate(sanitize_text_field(wp_unslash((string) ($_POST['candidate_id'] ?? ''))), sanitize_key(wp_unslash((string) ($_POST['status'] ?? ''))), get_current_user_id(), $org?->id));
    }

    public function createRequirementFromCandidate(): void
    {
        $this->guard('atlas_review_extractions', 'atlas_create_requirement_from_candidate');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $this->attempt(fn() => $this->service->createRequirementDraftFromCandidate(sanitize_text_field(wp_unslash((string) ($_POST['candidate_id'] ?? ''))), $org?->id, get_current_user_id(), wp_unslash($_POST)));
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

    public function createRequirementChangeProposal(): void
    {
        $this->guard('atlas_review_extractions', 'atlas_create_requirement_change_proposal');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $this->attempt(fn() => $this->service->createRequirementChangeProposal(sanitize_text_field(wp_unslash((string) ($_POST['requirement_id'] ?? ''))), $org?->id, get_current_user_id(), wp_unslash($_POST)));
    }

    public function applyRequirementChangeProposal(): void
    {
        $this->guard('atlas_review_extractions', 'atlas_apply_requirement_change_proposal');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $this->attempt(fn() => $this->service->applyRequirementChangeProposal(sanitize_text_field(wp_unslash((string) ($_POST['proposal_id'] ?? ''))), sanitize_text_field(wp_unslash((string) ($_POST['requirement_id'] ?? ''))), $org?->id, get_current_user_id()));
    }

    public function rejectRequirementChangeProposal(): void
    {
        $this->guard('atlas_review_extractions', 'atlas_reject_requirement_change_proposal');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $this->attempt(fn() => $this->service->rejectRequirementChangeProposal(sanitize_text_field(wp_unslash((string) ($_POST['proposal_id'] ?? ''))), sanitize_text_field(wp_unslash((string) ($_POST['requirement_id'] ?? ''))), $org?->id, get_current_user_id(), sanitize_textarea_field(wp_unslash((string) ($_POST['rejection_note'] ?? '')))));
    }

    public function openSourceImpactReview(): void
    {
        $this->guard('atlas_review_extractions', 'atlas_open_source_impact_review');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $this->attempt(fn() => $this->service->openSourceImpactReview(sanitize_text_field(wp_unslash((string) ($_POST['source_document_id'] ?? ''))), $org?->id, get_current_user_id()));
    }

    public function clearSourceImpactReview(): void
    {
        $this->guard('atlas_review_extractions', 'atlas_clear_source_impact_review');
        $org = $this->orgs->resolveForUser(get_current_user_id());
        $this->attempt(fn() => $this->service->clearSourceImpactReview(sanitize_text_field(wp_unslash((string) ($_POST['requirement_id'] ?? ''))), $org?->id, get_current_user_id(), sanitize_textarea_field(wp_unslash((string) ($_POST['review_note'] ?? '')))));
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
        $proposalCriteria = [
            'payer' => $criteria['payer'],
            'topic' => $criteria['topic'],
            'proposal_status' => sanitize_key(wp_unslash((string) ($_GET['proposal_status'] ?? ''))),
            'source_review_status' => sanitize_key(wp_unslash((string) ($_GET['source_review_status'] ?? ''))),
        ];
        $comparisonSourceId = sanitize_text_field(wp_unslash((string) ($_GET['comparison_source_id'] ?? '')));
        if ($comparisonSourceId !== '' && preg_match('/^[a-f0-9-]{36}$/i', $comparisonSourceId) !== 1) { $comparisonSourceId = ''; }
        $dashboard = $this->service->dashboard($org?->id, $comparisonSourceId === '' ? null : $comparisonSourceId);
        $summary = $dashboard['summary'];
        $reviewLanes = $dashboard['review_lanes'] ?? [];
        $freshnessAlerts = $dashboard['freshness_alerts'] ?? [];
        $documents = $dashboard['documents'];
        $documentPages = $dashboard['document_pages'] ?? [];
        $selectedDocumentId = sanitize_text_field(wp_unslash((string) ($_GET['source_document_id'] ?? ($documents[0]['id'] ?? ''))));
        $sourceIntake = $selectedDocumentId === '' ? null : $this->service->sourceIntakeWorkspace($selectedDocumentId, $org?->id);
        $sourceComparison = $dashboard['source_comparison'] ?? null;
        $sourceImpactQueue = $dashboard['source_impact_queue'] ?? ['items'=>[],'summary'=>[]];
        $changeProposalQueues = [
            'draft' => $this->service->changeProposalQueue($org?->id, $proposalCriteria + ['proposal_status'=>'draft'], 50),
            'applied' => $this->service->changeProposalQueue($org?->id, $proposalCriteria + ['proposal_status'=>'applied'], 25),
            'rejected' => $this->service->changeProposalQueue($org?->id, $proposalCriteria + ['proposal_status'=>'rejected'], 25),
        ];
        $candidates = $dashboard['candidates'];
        $requirements = $this->service->requirements($org?->id, $criteria, 50);
        $insuranceProfiles = $dashboard['insurance_profiles'] ?? [];
        $dmeCategories = $dashboard['dme_categories'] ?? [];
        $dmeMatches = $this->service->dmeRequirementMatches($org?->id, ['payer'=>$criteria['payer'], 'dme_category'=>$criteria['topic'], 'jurisdiction'=>''], 25);
        $dmeCoverageOps = $this->service->dmeCoverageOperationsDashboard($org?->id, ['payer'=>$criteria['payer'], 'topic'=>$criteria['topic']]);
        $priorAuthPrep = $this->service->priorAuthorizationPrepWorkspace($org?->id, ['payer'=>$criteria['payer'], 'topic'=>$criteria['topic']]);
        $checklist = $this->service->documentationChecklist($org?->id, $criteria + ['status' => $criteria['status'] ?: 'published'], 50);
        $detailId = sanitize_text_field(wp_unslash((string) ($_GET['requirement_id'] ?? '')));
        $requirementDetail = $detailId === '' ? null : $this->service->requirementDetailWorkspace($detailId, $org?->id);
        $evidence = $requirementDetail === null ? null : ['requirement' => $requirementDetail['requirement'], 'source' => $requirementDetail['source'], 'checklist_items' => $requirementDetail['checklist_items']];
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
        try { $action(); $this->redirect(); } catch (InvalidArgumentException $e) { $this->redirect($this->friendlyError($e->getMessage())); }
    }

    private function friendlyError(string $message): string
    {
        $message = trim($message);
        if (str_contains($message, 'Resolve draft requirement change proposals')) { return 'Resolve draft requirement change proposals before clearing source review.'; }
        if (str_contains($message, 'stale because the requirement already reflects')) { return 'This proposal is stale because the requirement already matches the proposed values.'; }
        if (str_contains($message, 'Do not enter patient-identifying')) { return 'Remove patient-identifying information before saving this internal source workspace item.'; }
        return $message;
    }

    /** @param array<string,mixed> $file @return array<string,mixed> */
    private function preserveUploadedFile(array $file): array
    {
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { return []; }
        if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) { throw new InvalidArgumentException('Source file upload failed.'); }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || ! is_uploaded_file($tmp)) { throw new InvalidArgumentException('Source file upload is invalid.'); }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 25 * 1024 * 1024) { throw new InvalidArgumentException('Source file must be a PDF under 25 MB.'); }
        $name = sanitize_file_name((string) ($file['name'] ?? 'source.pdf'));
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $mime = function_exists('mime_content_type') ? (string) mime_content_type($tmp) : '';
        if ($extension !== 'pdf' || ($mime !== '' && ! in_array($mime, ['application/pdf', 'application/octet-stream'], true))) { throw new InvalidArgumentException('Only PDF source files can be preserved.'); }
        $uploads = wp_upload_dir(null, false);
        $base = rtrim((string) ($uploads['basedir'] ?? ''), '/\\') . '/atlas-source-documents';
        if ($base === '/atlas-source-documents' || ! wp_mkdir_p($base)) { throw new InvalidArgumentException('Atlas could not prepare source file storage.'); }
        $checksum = hash_file('sha256', $tmp);
        if (! is_string($checksum) || $checksum === '') { throw new InvalidArgumentException('Atlas could not checksum the source file.'); }
        $target = $base . '/' . gmdate('YmdHis') . '-' . $checksum . '.pdf';
        if (! @move_uploaded_file($tmp, $target)) { throw new InvalidArgumentException('Atlas could not preserve the source file.'); }
        if (! is_file($base . '/index.html')) { @file_put_contents($base . '/index.html', ''); }
        return ['preserved_file_path'=>$target,'original_filename'=>$name,'mime_type'=>'application/pdf','file_size_bytes'=>$size,'preserved_at'=>gmdate('Y-m-d H:i:s'),'checksum'=>$checksum];
    }

    private function redirect(string $error = ''): never
    {
        $args = ['page' => 'atlas-sources'];
        if ($error !== '') { $args['atlas_error'] = $error; }
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }
}
