<?php

declare(strict_types=1);

namespace Atlas\Platform\Resources\Presentation;

use Atlas\Platform\Organizations\Services\CurrentOrganizationResolver;
use Atlas\Platform\Resources\Search\ResourceSearchService;
use Atlas\Platform\Resources\Search\SearchCriteria;
use InvalidArgumentException;

final class ResourceLibraryAdminPage
{
    private string $hook = '';
    private const TYPES = ['patient_education', 'clinical_skill', 'lab_reference', 'payer_summary', 'community_resource', 'form', 'quick_reference'];

    public function __construct(private ResourceSearchService $search, private CurrentOrganizationResolver $organizations) {}

    public function register(): void
    {
        $this->hook = (string) add_submenu_page('atlas', __('Resources', 'atlas-platform'), __('Resources', 'atlas-platform'), 'atlas_access', 'atlas-resources', [$this, 'render']);
    }

    public function enqueue(string $hook): void
    {
        if ($hook === $this->hook) { wp_enqueue_style('atlas-preview', ATLAS_PLATFORM_URL . 'assets/css/atlas-preview.css', [], ATLAS_PLATFORM_VERSION); }
    }

    public function render(): void
    {
        if (! current_user_can('atlas_access')) { wp_die(esc_html__('You are not allowed to access Atlas resources.', 'atlas-platform'), '', ['response' => 403]); }
        $query = sanitize_text_field(wp_unslash((string) ($_GET['atlas_search'] ?? '')));
        $type = sanitize_key(wp_unslash((string) ($_GET['atlas_type'] ?? '')));
        $pageNumber = max(1, absint($_GET['atlas_page'] ?? 1));
        $error = '';
        try {
            $criteria = SearchCriteria::normalize($query, $type === '' ? null : $type, $pageNumber, 20);
            $organization = $this->organizations->resolveForUser(get_current_user_id());
            $results = $this->search->search($criteria, $organization?->id);
        } catch (InvalidArgumentException $exception) {
            $criteria = SearchCriteria::normalize('', null, 1, 20);
            $results = $this->search->search($criteria, null);
            $error = $exception->getMessage();
        }
        $types = self::TYPES;
        require ATLAS_PLATFORM_DIR . 'templates/resources/library.php';
    }
}
