<?php

declare(strict_types=1);

namespace Atlas\Support;

final class Fixtures
{
    public static function enabled(): bool { return (bool) apply_filters('atlas_enable_demo_data', true); }
    private static function data(array $data): array { return self::enabled() ? $data : []; }

    public static function dashboard(): array
    {
        return self::data([
            'welcome' => 'Good evening', 'organization' => 'Atlas Demo Health',
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
        ]);
    }

    public static function resources(): array
    {
        return self::data([
            ['slug'=>'home-oxygen-qualification','title'=>'Home Oxygen Qualification & Documentation','summary'=>'A practical guide to qualifying testing, documentation, and discharge coordination for home oxygen.','category'=>'DME','audience'=>'Nursing + Care Management','reviewed'=>'Jul 18, 2026','trust'=>'Reviewed','source'=>'CMS Medicare Learning Network'],
            ['slug'=>'snf-placement-checklist','title'=>'Skilled Nursing Facility Placement Checklist','summary'=>'What to gather, confirm, and communicate before sending a referral for post-acute skilled nursing placement.','category'=>'Post-acute','audience'=>'Care Management','reviewed'=>'Jul 11, 2026','trust'=>'Reviewed','source'=>'Atlas Demo Health'],
            ['slug'=>'home-health-eligibility','title'=>'Home Health Eligibility Guide','summary'=>'Fast reference for homebound status, skilled need, ordering requirements, and common referral pitfalls.','category'=>'Home Health','audience'=>'Clinical Teams','reviewed'=>'Jun 29, 2026','trust'=>'Reviewed','source'=>'CMS'],
            ['slug'=>'transportation-options','title'=>'Non-Emergency Transportation Options','summary'=>'Decision support for choosing transportation based on mobility, payer, medical need, and destination.','category'=>'Transportation','audience'=>'Care Coordination','reviewed'=>'Jun 14, 2026','trust'=>'Local review','source'=>'Atlas Demo Health'],
            ['slug'=>'dme-documentation','title'=>'Durable Medical Equipment Documentation Basics','summary'=>'Core documentation elements that commonly determine whether DME orders move forward or bounce back.','category'=>'DME','audience'=>'Prescribers + Nursing','reviewed'=>'May 31, 2026','trust'=>'Reviewed','source'=>'CMS'],
        ]);
    }

    public static function resource(string $slug): ?array
    {
        foreach (self::resources() as $resource) if ($resource['slug'] === $slug) return $resource + [
            'key_points'=>['Confirm the qualifying clinical criteria before the patient leaves the care setting.','Match the order and supporting documentation to the payer requirement.','Document the result that establishes medical necessity and make the source easy to locate.'],
            'workflow'=>['Verify qualifying test or assessment','Confirm order language and prescriber documentation','Identify in-network supplier','Send complete referral packet','Confirm acceptance and delivery plan'],
            'citation'=>'Centers for Medicare & Medicaid Services. Medicare coverage guidance and supplier documentation requirements.',
            'related'=>['Arrange Home Oxygen','Medicare DME: Oxygen','Home Oxygen: What to Expect'],
        ];
        return null;
    }
}
