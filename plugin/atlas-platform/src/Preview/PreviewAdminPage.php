<?php
declare(strict_types=1);

namespace Atlas\Platform\Preview;

use Atlas\Platform\Organizations\Services\CurrentOrganizationResolver;

final class PreviewAdminPage
{
    private string $hookSuffix = '';

    public function __construct(private PreviewService $service, private CurrentOrganizationResolver $organizations) {}

    public function register(): void
    {
        $this->hookSuffix = (string) add_menu_page(
            __('Atlas', 'atlas-platform'),
            __('Atlas', 'atlas-platform'),
            'atlas_access',
            'atlas',
            [$this, 'render'],
            'dashicons-location-alt',
            3
        );
    }

    public function enqueueAssets(string $hookSuffix): void
    {
        if ($hookSuffix !== $this->hookSuffix) {
            return;
        }

        wp_enqueue_style(
            'atlas-preview',
            ATLAS_PLATFORM_URL . 'assets/css/atlas-preview.css',
            [],
            ATLAS_PLATFORM_VERSION
        );
    }

    public function render(): void
    {
        if (! current_user_can('atlas_access')) {
            wp_die(esc_html__('You are not allowed to access Atlas.', 'atlas-platform'), '', ['response' => 403]);
        }

        $query = isset($_GET['atlas_search'])
            ? sanitize_text_field(wp_unslash((string) $_GET['atlas_search']))
            : '';
        $view = $this->service->home($query);
        $user = wp_get_current_user();
        $view['user_name'] = $user->display_name !== '' ? $user->display_name : __('Atlas user', 'atlas-platform');
        $organization = $this->organizations->resolveForUser((int) $user->ID);
        $view['organization_name'] = $organization?->name ?? __('No organization selected', 'atlas-platform');

        require ATLAS_PLATFORM_DIR . 'templates/preview/home.php';
    }
}
