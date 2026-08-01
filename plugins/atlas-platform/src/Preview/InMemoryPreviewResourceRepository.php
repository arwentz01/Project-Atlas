<?php
declare(strict_types=1);

namespace Atlas\Platform\Preview;

final class InMemoryPreviewResourceRepository implements PreviewResourceRepository
{
    /** @var list<array<string, string>> */
    private const RESOURCES = [
        [
            'title' => 'Hospital bed coverage workflow',
            'type' => 'Coverage workflow',
            'authority' => 'Official source example',
            'status' => 'Preview only',
            'summary' => 'An illustrative path from a payer policy to documentation steps, required forms, and follow-up.',
            'updated' => 'Demonstration content',
            'tone' => 'blue',
        ],
        [
            'title' => 'Blood pressure tracking log',
            'type' => 'Patient education',
            'authority' => 'Reviewed resource example',
            'status' => 'Preview only',
            'summary' => 'A print-ready example showing how a clinic could provide a clear, organization-branded tracking resource.',
            'updated' => 'Demonstration content',
            'tone' => 'green',
        ],
        [
            'title' => 'Subcutaneous injection refresher',
            'type' => 'Clinical reference',
            'authority' => 'Educational example',
            'status' => 'Preview only',
            'summary' => 'A concise reference layout for supplies, preparation, key steps, safety notes, and escalation guidance.',
            'updated' => 'Demonstration content',
            'tone' => 'violet',
        ],
        [
            'title' => 'Local referral verification checklist',
            'type' => 'Organization workflow',
            'authority' => 'Organization policy example',
            'status' => 'Preview only',
            'summary' => 'An example of local operational knowledge kept separate from official policy and community reports.',
            'updated' => 'Demonstration content',
            'tone' => 'amber',
        ],
    ];

    public function search(string $query = ''): array
    {
        $query = trim($query);
        if ($query === '') {
            return self::RESOURCES;
        }

        return array_values(array_filter(
            self::RESOURCES,
            static function (array $resource) use ($query): bool {
                $haystack = implode(' ', [$resource['title'], $resource['type'], $resource['authority'], $resource['summary']]);
                return stripos($haystack, $query) !== false;
            }
        ));
    }
}
