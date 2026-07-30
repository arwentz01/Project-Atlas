<?php

declare(strict_types=1);

namespace Atlas\Platform\Organizations\Admin;

use Atlas\Platform\Core\Logging\Logger;
use Atlas\Platform\Organizations\Onboarding\OrganizationOnboardingService;
use Atlas\Platform\Organizations\Services\CurrentOrganizationResolver;
use Atlas\Platform\Organizations\Services\OrganizationContextService;
use Atlas\Platform\Organizations\Services\OrganizationAdministrationService;
use Atlas\Platform\PatientResources\Domain\BrandingProfile;
use Atlas\Platform\PatientResources\Repositories\BrandingRepository;
use Atlas\Platform\PatientResources\Services\BrandingService;
use Throwable;

final class OrganizationsAdminPage
{
    private string $hook = '';

    public function __construct(private OrganizationContextService $contexts, private CurrentOrganizationResolver $current, private OrganizationOnboardingService $onboarding, private OrganizationAdministrationService $administration, private BrandingRepository $branding, private BrandingService $brandingService, private Logger $logger) {}

    public function register(): void
    {
        $this->hook = (string) add_submenu_page('atlas', __('Organizations', 'atlas-platform'), __('Organizations', 'atlas-platform'), 'atlas_access', 'atlas-organizations', [$this, 'render']);
    }

    public function enqueue(string $hook): void
    {
        if ($hook === $this->hook) { wp_enqueue_style('atlas-preview', ATLAS_PLATFORM_URL . 'assets/css/atlas-preview.css', [], ATLAS_PLATFORM_VERSION); }
    }

    public function select(): void
    {
        $this->authorize('atlas_access', 'atlas_select_organization');
        $id = strtolower(sanitize_text_field(wp_unslash((string) ($_POST['organization_id'] ?? ''))));
        try { $this->contexts->select(get_current_user_id(), $id); $notice = 'selected'; }
        catch (Throwable $failure) { $this->logger->log('error', 'organizations.context_selection_failed', 'Organization context selection failed.', ['module' => 'organizations'], 'organizations', $failure); $notice = 'selection-failed'; }
        $this->redirect($notice);
    }

    public function create(): void
    {
        $this->authorize('atlas_manage_organizations', 'atlas_create_organization');
        try {
            $result = $this->onboarding->create(
                sanitize_text_field(wp_unslash((string) ($_POST['idempotency_key'] ?? ''))),
                sanitize_text_field(wp_unslash((string) ($_POST['name'] ?? ''))),
                sanitize_title(wp_unslash((string) ($_POST['slug'] ?? ''))),
                get_current_user_id()
            );
            $this->contexts->select(get_current_user_id(), (string) $result['organization_id']);
            $notice = $result['replayed'] ? 'existing' : 'created';
        } catch (Throwable $failure) { $this->logger->log('error', 'organizations.admin_create_failed', 'Organization creation from administration failed.', ['module' => 'organizations'], 'organizations', $failure); $notice = 'create-failed'; }
        $this->redirect($notice);
    }

    public function invite(): void
    {
        $this->authorize('atlas_manage_members','atlas_invite_member');$organization=$this->requiredOrganization();
        try{$email=sanitize_email(wp_unslash((string)($_POST['email']??'')));$token=$this->administration->invite($organization->id,$email,(array)($_POST['roles']??[]),get_current_user_id());$url=add_query_arg(['action'=>'atlas_accept_invitation','token'=>$token],admin_url('admin-post.php'));if(!wp_mail($email,__('You are invited to Atlas','atlas-platform'),sprintf(__("Sign in, then accept your invitation:\n%s",'atlas-platform'),$url))){throw new \RuntimeException('WordPress could not send the invitation email.');}$notice='invited';}catch(Throwable $failure){$this->logger->log('error','organizations.invite_failed','Organization invitation failed.',[],'organizations',$failure);$notice='invite-failed';}$this->redirect($notice);
    }
    public function acceptInvitation():void
    {
        if(!is_user_logged_in()){auth_redirect();}$user=wp_get_current_user();$ok=$this->administration->accept(sanitize_text_field(wp_unslash((string)($_GET['token']??''))),(int)$user->ID,(string)$user->user_email);$this->redirect($ok?'invitation-accepted':'invitation-invalid');
    }
    public function revokeInvitation():void
    {
        $this->authorize('atlas_manage_members','atlas_revoke_invitation');$organization=$this->requiredOrganization();$ok=$this->administration->revoke($organization->id,sanitize_text_field(wp_unslash((string)($_POST['invitation_id']??''))),get_current_user_id());$this->redirect($ok?'invitation-revoked':'member-action-failed');
    }
    public function updateMemberRoles():void
    {
        $this->authorize('atlas_manage_members','atlas_update_member_roles');$organization=$this->requiredOrganization();try{$ok=$this->administration->updateRoles($organization->id,sanitize_text_field(wp_unslash((string)($_POST['membership_id']??''))),(array)($_POST['roles']??[]),get_current_user_id());}catch(Throwable){$ok=false;}$this->redirect($ok?'roles-updated':'member-action-failed');
    }
    public function removeMember():void
    {
        $this->authorize('atlas_manage_members','atlas_remove_member');$organization=$this->requiredOrganization();$ok=$this->administration->remove($organization->id,sanitize_text_field(wp_unslash((string)($_POST['membership_id']??''))),get_current_user_id());$this->redirect($ok?'member-removed':'member-action-failed');
    }
    public function saveBranding():void
    {
        $this->authorize('atlas_manage_branding','atlas_save_branding');$organization=$this->requiredOrganization();try{$this->brandingService->save($organization->id,wp_unslash($_POST),get_current_user_id());$notice='branding-saved';}catch(Throwable $failure){$this->logger->log('error','organizations.branding_failed','Organization branding update failed.',[],'organizations',$failure);$notice='branding-failed';}$this->redirect($notice);
    }

    public function render(): void
    {
        if (! current_user_can('atlas_access')) { wp_die(esc_html__('You are not allowed to access Atlas organizations.', 'atlas-platform'), '', ['response' => 403]); }
        $userId = get_current_user_id();
        $organizations = $this->contexts->availableForUser($userId);
        $current = $this->current->resolveForUser($userId);
        $members=$current===null?[]:$this->administration->members($current->id);
        $invitations=$current===null?[]:$this->administration->invitations($current->id);
        $brand=$current===null?null:($this->branding->findForOrganization($current->id)??BrandingProfile::neutral($current->id,$current->name));
        $roles=OrganizationAdministrationService::ROLES;
        $notice = sanitize_key(wp_unslash((string) ($_GET['atlas_notice'] ?? '')));
        require ATLAS_PLATFORM_DIR . 'templates/organizations/index.php';
    }

    private function authorize(string $capability, string $nonce): void
    {
        if (! current_user_can($capability)) { wp_die(esc_html__('You are not allowed to perform this action.', 'atlas-platform'), '', ['response' => 403]); }
        check_admin_referer($nonce);
    }

    private function requiredOrganization(): \Atlas\Platform\Organizations\Domain\Organization
    {
        $organization=$this->current->resolveForUser(get_current_user_id());if($organization===null){$this->redirect('context-required');}return $organization;
    }

    private function redirect(string $notice): never
    {
        wp_safe_redirect(add_query_arg('atlas_notice', $notice, admin_url('admin.php?page=atlas-organizations')));
        exit;
    }
}
