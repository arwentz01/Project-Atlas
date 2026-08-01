<?php
declare(strict_types=1);

namespace Atlas\Platform\Preview;

use Atlas\Platform\Core\Admin\AdminNavigation;
use Atlas\Platform\Organizations\Services\CurrentOrganizationResolver;

final class PreviewAdminPage
{
    private string $hookSuffix = '';

    public function __construct(private PreviewService $service, private CurrentOrganizationResolver $organizations, private AdminNavigation $navigation) {}

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
        $page = sanitize_key(wp_unslash((string) ($_GET['page'] ?? '')));
        if ($hookSuffix !== $this->hookSuffix && ! str_starts_with($page, 'atlas')) {
            return;
        }

        wp_enqueue_style(
            'atlas-preview',
            ATLAS_PLATFORM_URL . 'assets/css/atlas-preview.css',
            [],
            ATLAS_PLATFORM_VERSION
        );
    }

    public function renderApplicationNavigation(): void
    {
        $current = sanitize_key(wp_unslash((string) ($_GET['page'] ?? '')));
        if (! str_starts_with($current, 'atlas') || ! current_user_can('atlas_access')) {
            return;
        }

        $user = wp_get_current_user();
        $organization = $this->organizations->resolveForUser((int) $user->ID);
        echo '<nav class="atlas-global-nav" aria-label="' . esc_attr__('Atlas application navigation', 'atlas-platform') . '">';
        echo '<a class="atlas-global-brand" href="' . esc_url(admin_url('admin.php?page=atlas')) . '"><span aria-hidden="true">A</span><strong>Atlas</strong></a>';
        echo '<div class="atlas-global-links">';
        foreach ($this->navigation->visible($current) as $item) {
            echo '<a class="' . esc_attr($item['current'] ? 'is-active' : '') . '" href="' . esc_url($item['url']) . '"' . ($item['current'] ? ' aria-current="page"' : '') . '>';
            echo '<span class="dashicons ' . esc_attr($item['icon']) . '" aria-hidden="true"></span>' . esc_html($item['label']) . '</a>';
        }
        echo '</div><span class="atlas-global-context">' . esc_html($organization?->name ?? __('No organization selected', 'atlas-platform')) . '</span></nav>';
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
        $view['navigation'] = $this->navigation->visible('atlas');
        $view['has_organization'] = $organization !== null;

        require ATLAS_PLATFORM_DIR . 'templates/preview/home.php';
    }
}
