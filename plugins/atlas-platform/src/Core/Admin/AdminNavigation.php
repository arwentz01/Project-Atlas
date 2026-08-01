<?php

declare(strict_types=1);

namespace Atlas\Platform\Core\Admin;

final class AdminNavigation
{
    /** @return list<array{slug:string,label:string,icon:string,url:string,capability:string,current:bool}> */
    public function visible(string $currentSlug): array
    {
        $items = [
            ['slug' => 'atlas', 'label' => __('Home', 'atlas-platform'), 'icon' => 'dashicons-admin-home', 'url' => admin_url('admin.php?page=atlas'), 'capability' => 'atlas_access'],
            ['slug' => 'atlas-diagnostics', 'label' => __('Diagnostics', 'atlas-platform'), 'icon' => 'dashicons-admin-tools', 'url' => admin_url('tools.php?page=atlas-diagnostics'), 'capability' => 'atlas_view_diagnostics'],
        ];

        $visible = [];
        foreach ($items as $item) {
            if (! current_user_can($item['capability'])) {
                continue;
            }
            $item['current'] = $item['slug'] === $currentSlug;
            $visible[] = $item;
        }

        /** @var list<array{slug:string,label:string,icon:string,url:string,capability:string,current:bool}> $visible */
        return apply_filters('atlas_admin_navigation', $visible, $currentSlug);
    }
}
