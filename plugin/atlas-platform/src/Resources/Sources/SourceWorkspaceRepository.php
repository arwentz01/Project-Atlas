<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Sources;

interface SourceWorkspaceRepository
{
    public function createDocument(?string $organizationId, int $userId, array $input): string;
    public function updateDocumentStatus(string $id, ?string $organizationId, string $status, string $notes = ''): bool;
    public function createSection(string $documentId, array $input): string;
    public function createCandidate(string $sectionId, ?string $organizationId, array $input): string;
    public function reviewCandidate(string $id, string $status, int $userId): bool;
    public function createRequirement(?string $organizationId, int $userId, array $input): string;
    public function reviewRequirement(string $id, ?string $organizationId, string $status, int $userId): bool;
    public function createInsuranceProfile(?string $organizationId, int $userId, array $input): string;
    /** @return list<array<string,mixed>> */
    public function insuranceProfiles(?string $organizationId, int $limit = 25, array $criteria = []): array;
    public function createDmeCategory(array $input): string;
    /** @return list<array<string,mixed>> */
    public function dmeCategories(int $limit = 100, array $criteria = []): array;
    public function findRequirement(string $id, ?string $organizationId): ?array;
    /** @return array<string,mixed>|null */
    public function findRequirementSource(string $candidateId, ?string $organizationId): ?array;
    public function saveChecklistState(string $requirementId, ?string $organizationId, string $hash, string $label, string $status, string $notes, int $userId): bool;
    /** @param list<string> $requirementIds @return array<string,array<string,mixed>> */
    public function checklistState(array $requirementIds, ?string $organizationId): array;
    /** @return list<array<string,mixed>> */
    public function documents(?string $organizationId, int $limit = 25): array;
    /** @return list<array<string,mixed>> */
    public function candidates(?string $organizationId, int $limit = 25): array;
    /** @return list<array<string,mixed>> */
    public function requirements(?string $organizationId, int $limit = 25, array $criteria = []): array;
    /** @return array<string,int> */
    public function summary(?string $organizationId): array;
}
