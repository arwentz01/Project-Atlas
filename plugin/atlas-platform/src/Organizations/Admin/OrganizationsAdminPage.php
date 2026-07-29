<?php

declare(strict_types=1);

namespace Atlas\Platform\Organizations\Admin;

use Atlas\Platform\Core\Logging\Logger;
use Atlas\Platform\Organizations\Onboarding\OrganizationOnboardingService;
use Atlas\Platform\Organizations\Services\CurrentOrganizationResolver;
use Atlas\Platform\Organizations\Services\OrganizationContextService;
use Throwable;

final class OrganizationsAdminPage
{
    private string $hook = '';

    public function __construct(private OrganizationContextService $contexts, private CurrentOrganizationResolver $current, private OrganizationOnboardingService $onboarding, private Logger $logger) {}

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

    public function render(): void
    {
        if (! current_user_can('atlas_access')) { wp_die(esc_html__('You are not allowed to access Atlas organizations.', 'atlas-platform'), '', ['response' => 403]); }
        $userId = get_current_user_id();
        $organizations = $this->contexts->availableForUser($userId);
        $current = $this->current->resolveForUser($userId);
        $notice = sanitize_key(wp_unslash((string) ($_GET['atlas_notice'] ?? '')));
        require ATLAS_PLATFORM_DIR . 'templates/organizations/index.php';
    }

    private function authorize(string $capability, string $nonce): void
    {
        if (! current_user_can($capability)) { wp_die(esc_html__('You are not allowed to perform this action.', 'atlas-platform'), '', ['response' => 403]); }
        check_admin_referer($nonce);
    }

    private function redirect(string $notice): never
    {
        wp_safe_redirect(add_query_arg('atlas_notice', $notice, admin_url('admin.php?page=atlas-organizations')));
        exit;
    }
}
