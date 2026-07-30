<?php

declare(strict_types=1);

namespace Atlas\Platform\Resources\Admin;

use Atlas\Platform\Core\Logging\Logger;
use Atlas\Platform\Organizations\Services\CurrentOrganizationResolver;
use Atlas\Platform\Resources\Authoring\ResourceDraftService;
use Throwable;

final class ResourceAuthoringAdminPage
{
    private string $hook = '';
    public function __construct(private ResourceDraftService $drafts, private CurrentOrganizationResolver $organizations, private Logger $logger) {}
    public function register(): void { $this->hook = (string) add_submenu_page('atlas', __('Create Resource', 'atlas-platform'), __('Create Resource', 'atlas-platform'), 'atlas_create_resources', 'atlas-resource-create', [$this, 'render']); }
    public function enqueue(string $hook): void { if ($hook === $this->hook) { wp_enqueue_style('atlas-preview', ATLAS_PLATFORM_URL . 'assets/css/atlas-preview.css', [], ATLAS_PLATFORM_VERSION); } }
    public function create(): void
    {
        if (! current_user_can('atlas_create_resources')) { wp_die(esc_html__('You are not allowed to create Atlas resources.', 'atlas-platform'), '', ['response' => 403]); }
        check_admin_referer('atlas_create_resource');
        $userId = get_current_user_id();
        $organization = $this->organizations->resolveForUser($userId);
        $body = sanitize_textarea_field(wp_unslash((string) ($_POST['body'] ?? '')));
        $paragraphs = array_values(array_filter(array_map('trim', preg_split('/\R{2,}/', $body) ?: []), static fn(string $value): bool => $value !== ''));
        $input = [
            'scope' => sanitize_key(wp_unslash((string) ($_POST['scope'] ?? 'organization'))),
            'slug' => sanitize_title(wp_unslash((string) ($_POST['slug'] ?? ''))),
            'resource_type' => sanitize_key(wp_unslash((string) ($_POST['resource_type'] ?? ''))),
            'title' => sanitize_text_field(wp_unslash((string) ($_POST['title'] ?? ''))),
            'summary' => sanitize_textarea_field(wp_unslash((string) ($_POST['summary'] ?? ''))),
            'audiences' => sanitize_textarea_field(wp_unslash((string) ($_POST['audiences'] ?? ''))),
            'specialties' => sanitize_textarea_field(wp_unslash((string) ($_POST['specialties'] ?? ''))),
            'jurisdictions' => sanitize_textarea_field(wp_unslash((string) ($_POST['jurisdictions'] ?? ''))),
            'payers' => sanitize_textarea_field(wp_unslash((string) ($_POST['payers'] ?? ''))),
            'tags' => sanitize_textarea_field(wp_unslash((string) ($_POST['tags'] ?? ''))),
            'body' => array_map(static fn(string $text): array => ['type' => 'paragraph', 'text' => $text], $paragraphs),
            'change_summary' => sanitize_textarea_field(wp_unslash((string) ($_POST['change_summary'] ?? 'Initial draft'))),
            'source' => ['publisher' => sanitize_text_field(wp_unslash((string) ($_POST['source_publisher'] ?? ''))), 'title' => sanitize_text_field(wp_unslash((string) ($_POST['source_title'] ?? ''))), 'url' => esc_url_raw(wp_unslash((string) ($_POST['source_url'] ?? ''))), 'document_identifier' => sanitize_text_field(wp_unslash((string) ($_POST['document_identifier'] ?? ''))), 'effective_date' => sanitize_text_field(wp_unslash((string) ($_POST['effective_date'] ?? '')))],
            'citation' => ['page' => sanitize_text_field(wp_unslash((string) ($_POST['citation_page'] ?? ''))), 'section' => sanitize_text_field(wp_unslash((string) ($_POST['citation_section'] ?? '')))],
        ];
        try {
            $result = $this->drafts->create(sanitize_text_field(wp_unslash((string) ($_POST['idempotency_key'] ?? ''))), $input, $organization?->id ?? '', $userId, current_user_can('atlas_manage_atlas'));
            $url = add_query_arg(['page' => 'atlas-resource-create', 'atlas_notice' => $result['replayed'] ? 'restored' : 'created', 'version_id' => $result['resource_version_id']], admin_url('admin.php'));
        } catch (Throwable $failure) {
            $this->logger->log('error', 'resources.admin_draft_failed', 'Resource draft creation from administration failed.', [], 'resources', $failure);
            $url = add_query_arg(['page' => 'atlas-resource-create', 'atlas_notice' => 'failed'], admin_url('admin.php'));
        }
        wp_safe_redirect($url); exit;
    }
    public function render(): void
    {
        if (! current_user_can('atlas_create_resources')) { wp_die(esc_html__('You are not allowed to create Atlas resources.', 'atlas-platform'), '', ['response' => 403]); }
        $organization = $this->organizations->resolveForUser(get_current_user_id());
        $notice = sanitize_key(wp_unslash((string) ($_GET['atlas_notice'] ?? '')));
        $versionId = sanitize_text_field(wp_unslash((string) ($_GET['version_id'] ?? '')));
        require ATLAS_PLATFORM_DIR . 'templates/resources/create.php';
    }
}
