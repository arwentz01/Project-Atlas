<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Sources;

interface SourceWorkspaceRepository
{
    public function createDocument(?string $organizationId, int $userId, array $input): string;
    /** @return array<string,mixed>|null */
    public function findDocument(string $id, ?string $organizationId): ?array;
    public function updateDocumentStatus(string $id, ?string $organizationId, string $status, string $notes = ''): bool;
    public function saveDocumentPage(string $documentId, int $pageNumber, int $userId, array $input): string;
    /** @return list<array<string,mixed>> */
    public function documentPages(string $documentId, int $limit = 25): array;
    public function createSection(string $documentId, array $input): string;
    public function createCandidate(string $sectionId, ?string $organizationId, array $input): string;
    /** @return array<string,mixed>|null */
    public function findCandidate(string $candidateId, ?string $organizationId): ?array;
    public function reviewCandidate(string $id, string $status, int $userId): bool;
    public function createRequirement(?string $organizationId, int $userId, array $input): string;
    public function reviewRequirement(string $id, ?string $organizationId, string $status, int $userId): bool;
    public function markRequirementsForSourceReview(string $oldDocumentId, string $newDocumentId, ?string $organizationId, int $userId, string $reason): int;
    public function clearRequirementSourceReview(string $requirementId, ?string $organizationId, int $userId, string $note): bool;
    public function createRequirementChangeProposal(string $requirementId, ?string $organizationId, int $userId, array $input): string;
    /** @return array<string,mixed>|null */
    public function findRequirementChangeProposal(string $proposalId, ?string $organizationId): ?array;
    public function applyRequirementChangeProposal(string $proposalId, string $requirementId, ?string $organizationId, int $userId, array $changes): bool;
    public function rejectRequirementChangeProposal(string $proposalId, string $requirementId, ?string $organizationId, int $userId, string $note): bool;
    /** @return list<array<string,mixed>> */
    public function requirementChangeProposals(string $requirementId, ?string $organizationId, int $limit = 25): array;
    /** @return list<array<string,mixed>> */
    public function changeProposalQueue(?string $organizationId, int $limit = 50, array $criteria = []): array;
    public function appendRequirementRevision(string $requirementId, ?string $organizationId, int $userId, string $revisionType, array $snapshot): string;
    /** @return list<array<string,mixed>> */
    public function requirementRevisions(string $requirementId, ?string $organizationId, int $limit = 25): array;
    public function createInsuranceProfile(?string $organizationId, int $userId, array $input): string;
    /** @return list<array<string,mixed>> */
    public function insuranceProfiles(?string $organizationId, int $limit = 25, array $criteria = []): array;
    public function createDmeCategory(array $input): string;
    /** @return list<array<string,mixed>> */
    public function dmeCategories(int $limit = 100, array $criteria = []): array;
    public function findRequirement(string $id, ?string $organizationId): ?array;
    /** @return array<string,mixed>|null */
    public function findRequirementSource(string $candidateId, ?string $organizationId): ?array;
    /** @return list<array<string,mixed>> */
    public function requirementsForSourceDocument(string $documentId, ?string $organizationId, int $limit = 50): array;
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
