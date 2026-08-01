<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Domain;

final class ResourcePolicy
{
    public const GLOBAL_SCOPES = ['platform', 'public'];
    private const SCOPES = ['platform', 'organization', 'personal', 'regional', 'public'];
    private const TYPES = ['patient_education', 'clinical_skill', 'lab_reference', 'payer_summary', 'community_resource', 'form', 'quick_reference'];
    private const REVIEW_STATUSES = ['draft', 'in_review', 'approved', 'published', 'review_due', 'superseded', 'archived'];
    public function validScope(string $scope): bool { return in_array($scope, self::SCOPES, true); }
    public function validType(string $type): bool { return in_array($type, self::TYPES, true); }
    public function validReviewStatus(string $status): bool { return in_array($status, self::REVIEW_STATUSES, true); }
    public function validIdentifier(string $id): bool { return preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/', strtolower($id)) === 1; }
    public function scopeKey(string $scope, ?string $organizationId): ?string
    {
        if (in_array($scope, self::GLOBAL_SCOPES, true)) { return $scope; }
        if ($scope === 'organization' && is_string($organizationId) && $organizationId !== '') { return 'organization:' . strtolower($organizationId); }
        return null;
    }
}
