<?php

declare(strict_types=1);

namespace Atlas\Support;

final class Fixtures
{
    public static function enabled(): bool
    {
        return (bool) apply_filters('atlas_enable_demo_data', true);
    }

    public static function dashboard(): array
    {
        if (! self::enabled()) {
            return [];
        }

        return [
            'welcome' => 'Good evening',
            'organization' => 'Atlas Demo Health',
            'quick_actions' => [
                ['label' => 'Find a resource', 'href' => '/resources', 'hint' => 'Clinical and operational references'],
                ['label' => 'Check payer requirements', 'href' => '/insurance', 'hint' => 'Coverage, documentation, and forms'],
                ['label' => 'Open a playbook', 'href' => '/playbooks', 'hint' => 'Step-by-step operational guidance'],
            ],
            'recent' => [
                ['title' => 'Home Oxygen Qualification & Documentation', 'type' => 'Resource', 'meta' => 'Reviewed Jul 2026'],
                ['title' => 'Medicare DME: Oxygen', 'type' => 'Payer requirement', 'meta' => 'Effective Jan 2026'],
                ['title' => 'Arrange Home Oxygen', 'type' => 'Playbook', 'meta' => '7 steps'],
            ],
        ];
    }
}
