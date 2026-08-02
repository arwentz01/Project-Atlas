<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Sources;

final class WordPressSourceWorkspaceRepository implements SourceWorkspaceRepository
{
    public function __construct(private object $db) {}

    public function createDocument(?string $org, int $user, array $in): string
    {
        $id = wp_generate_uuid4();
        $this->db->insert($this->db->prefix . 'atlas_source_documents', [
            'id' => $id,
            'organization_id' => $org,
            'title' => $this->txt($in, 'title', 255),
            'publisher' => $this->txt($in, 'publisher', 255),
            'source_family_key' => $this->txt($in, 'source_family_key', 191),
            'source_version_label' => $this->txt($in, 'source_version_label', 120),
            'supersedes_document_id' => $this->txt($in, 'supersedes_document_id', 36) ?: null,
            'source_url' => $this->txt($in, 'source_url', 500),
            'preserved_file_path' => $this->txt($in, 'preserved_file_path', 500) ?: null,
            'original_filename' => $this->txt($in, 'original_filename', 255) ?: null,
            'mime_type' => $this->txt($in, 'mime_type', 100) ?: null,
            'file_size_bytes' => isset($in['file_size_bytes']) ? max(0, (int) $in['file_size_bytes']) : null,
            'preserved_at' => $this->txt($in, 'preserved_at', 30) ?: null,
            'document_type' => sanitize_key((string) ($in['document_type'] ?? 'policy')),
            'effective_date' => $this->date($in['effective_date'] ?? null),
            'retrieved_at' => gmdate('Y-m-d H:i:s'),
            'last_checked_at' => null,
            'checksum' => $this->txt($in, 'checksum', 128),
            'extraction_status' => 'queued',
            'notes' => $this->txt($in, 'notes', 2000),
            'created_by' => $user,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ], ['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s']);
        return $id;
    }

    public function findDocument(string $id, ?string $org): ?array
    {
        $t = $this->db->prefix . 'atlas_source_documents';
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM `{$t}` WHERE id=%s AND (organization_id IS NULL OR organization_id=%s) LIMIT 1", $id, (string) $org), ARRAY_A);
        return is_array($row) ? $row : null;
    }

    public function updateDocumentStatus(string $id, ?string $org, string $status, string $notes = ''): bool
    {
        if (! in_array($status, ['queued','extracting','ready','stale','failed'], true)) { return false; }
        $where = ['id' => $id];
        $formats = ['%s'];
        if ($org !== null) { $where['organization_id'] = $org; $formats[] = '%s'; }
        return $this->db->update($this->db->prefix . 'atlas_source_documents', ['extraction_status' => $status, 'last_checked_at' => gmdate('Y-m-d H:i:s'), 'notes' => substr($notes, 0, 2000)], $where, ['%s','%s','%s'], $formats) !== false;
    }

    public function saveDocumentPage(string $documentId, int $pageNumber, int $user, array $in): string
    {
        $table = $this->db->prefix . 'atlas_source_document_pages';
        $existing = $this->db->get_var($this->db->prepare("SELECT id FROM `{$table}` WHERE source_document_id=%s AND page_number=%d LIMIT 1", $documentId, $pageNumber));
        $text = $this->txt($in, 'text_content', 20000);
        $data = [
            'source_document_id' => $documentId,
            'page_number' => max(1, $pageNumber),
            'extraction_method' => sanitize_key((string) ($in['extraction_method'] ?? 'manual')),
            'text_content' => $text,
            'text_checksum' => hash('sha256', $text),
            'extracted_at' => gmdate('Y-m-d H:i:s'),
            'created_by' => $user,
        ];
        if (is_string($existing) && $existing !== '') {
            $this->db->update($table, $data, ['id' => $existing], ['%s','%d','%s','%s','%s','%s','%d'], ['%s']);
            return $existing;
        }
        $id = wp_generate_uuid4();
        $data['id'] = $id;
        $this->db->insert($table, $data, ['%s','%d','%s','%s','%s','%s','%d','%s']);
        return $id;
    }

    public function documentPages(string $documentId, int $limit = 25): array
    {
        $table = $this->db->prefix . 'atlas_source_document_pages';
        $limit = max(1, min(100, $limit));
        $rows = $this->db->get_results($this->db->prepare("SELECT * FROM `{$table}` WHERE source_document_id=%s ORDER BY page_number ASC LIMIT %d", $documentId, $limit), ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    public function createSection(string $doc, array $in): string
    {
        $id = wp_generate_uuid4();
        $this->db->insert($this->db->prefix . 'atlas_source_sections', [
            'id' => $id,
            'source_document_id' => $doc,
            'page_number' => max(0, (int) ($in['page_number'] ?? 0)) ?: null,
            'section_label' => $this->txt($in, 'section_label', 191),
            'text_excerpt' => $this->txt($in, 'text_excerpt', 5000),
            'anchor' => $this->txt($in, 'anchor', 191),
            'created_at' => gmdate('Y-m-d H:i:s'),
        ], ['%s','%s','%d','%s','%s','%s','%s']);
        return $id;
    }

    public function createCandidate(string $section, ?string $org, array $in): string
    {
        $id = wp_generate_uuid4();
        $this->db->insert($this->db->prefix . 'atlas_extraction_candidates', [
            'id' => $id,
            'source_section_id' => $section,
            'organization_id' => $org,
            'candidate_type' => sanitize_key((string) ($in['candidate_type'] ?? 'requirement')),
            'statement' => $this->txt($in, 'statement', 5000),
            'status' => 'pending',
            'created_at' => gmdate('Y-m-d H:i:s'),
        ], ['%s','%s','%s','%s','%s','%s','%s']);
        return $id;
    }

    public function findCandidate(string $candidateId, ?string $org): ?array
    {
        $c = $this->db->prefix . 'atlas_extraction_candidates';
        $s = $this->db->prefix . 'atlas_source_sections';
        $d = $this->db->prefix . 'atlas_source_documents';
        $sql = "SELECT c.*,s.source_document_id,s.page_number,s.section_label,s.text_excerpt,s.anchor,d.title document_title,d.publisher,d.effective_date document_effective_date FROM `{$c}` c INNER JOIN `{$s}` s ON s.id=c.source_section_id INNER JOIN `{$d}` d ON d.id=s.source_document_id WHERE c.id=%s AND (c.organization_id IS NULL OR c.organization_id=%s) AND (d.organization_id IS NULL OR d.organization_id=%s) LIMIT 1";
        $row = $this->db->get_row($this->db->prepare($sql, $candidateId, (string) $org, (string) $org), ARRAY_A);
        return is_array($row) ? $row : null;
    }

    public function reviewCandidate(string $id, string $status, int $user): bool
    {
        if (! in_array($status, ['approved','rejected','needs_changes'], true)) { return false; }
        return $this->db->update($this->db->prefix . 'atlas_extraction_candidates', ['status' => $status, 'reviewer_user_id' => $user, 'reviewed_at' => gmdate('Y-m-d H:i:s')], ['id' => $id], ['%s','%d','%s'], ['%s']) !== false;
    }

    public function createRequirement(?string $org, int $user, array $in): string
    {
        $id = wp_generate_uuid4();
        $now = gmdate('Y-m-d H:i:s');
        $requiredForms = wp_json_encode(is_array($in['required_forms'] ?? null) ? $in['required_forms'] : []);
        if (! is_string($requiredForms)) { $requiredForms = '[]'; }
        $this->db->insert($this->db->prefix . 'atlas_payer_requirements', [
            'id' => $id,
            'organization_id' => $org,
            'payer' => $this->txt($in, 'payer', 191),
            'plan_name' => $this->txt($in, 'plan_name', 191),
            'topic' => $this->txt($in, 'topic', 191),
            'dme_category_slug' => sanitize_title((string) ($in['dme_category_slug'] ?? '')),
            'jurisdiction' => $this->txt($in, 'jurisdiction', 120),
            'requirement_type' => sanitize_key((string) ($in['requirement_type'] ?? 'documentation')),
            'prior_authorization_status' => sanitize_key((string) ($in['prior_authorization_status'] ?? 'unknown')),
            'frequency_limit' => $this->txt($in, 'frequency_limit', 191),
            'replacement_interval' => $this->txt($in, 'replacement_interval', 191),
            'required_forms_json' => $requiredForms,
            'coverage_criteria_text' => $this->txt($in, 'coverage_criteria_text', 5000),
            'requirement_text' => $this->txt($in, 'requirement_text', 5000),
            'source_candidate_id' => $this->txt($in, 'source_candidate_id', 36),
            'review_status' => 'draft',
            'effective_date' => $this->date($in['effective_date'] ?? null),
            'expires_at' => $this->date($in['expires_at'] ?? null),
            'created_by' => $user,
            'created_at' => $now,
            'updated_at' => $now,
        ], ['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s']);
        return $id;
    }

    public function createInsuranceProfile(?string $org, int $user, array $in): string
    {
        $id = wp_generate_uuid4();
        $now = gmdate('Y-m-d H:i:s');
        $this->db->insert($this->db->prefix . 'atlas_insurance_profiles', [
            'id' => $id,
            'organization_id' => $org,
            'payer' => $this->txt($in, 'payer', 191),
            'plan_name' => $this->txt($in, 'plan_name', 191),
            'line_of_business' => sanitize_key((string) ($in['line_of_business'] ?? '')),
            'jurisdiction' => $this->txt($in, 'jurisdiction', 120),
            'portal_url' => $this->txt($in, 'portal_url', 500) ?: null,
            'phone' => $this->txt($in, 'phone', 80),
            'effective_date' => $this->date($in['effective_date'] ?? null),
            'status' => sanitize_key((string) ($in['status'] ?? 'active')),
            'created_by' => $user,
            'created_at' => $now,
            'updated_at' => $now,
        ], ['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s']);
        return $id;
    }

    public function insuranceProfiles(?string $org, int $limit = 25, array $criteria = []): array
    {
        $t = $this->db->prefix . 'atlas_insurance_profiles';
        $limit = max(1, min(100, $limit));
        $payer = $this->txt($criteria, 'payer', 191);
        $jurisdiction = $this->txt($criteria, 'jurisdiction', 120);
        $status = sanitize_key((string) ($criteria['status'] ?? ''));
        $sql = "SELECT * FROM `{$t}` WHERE (organization_id IS NULL OR organization_id=%s)";
        $args = [(string) $org];
        if ($payer !== '') { $sql .= ' AND payer LIKE %s'; $args[] = '%' . $this->db->esc_like($payer) . '%'; }
        if ($jurisdiction !== '') { $sql .= ' AND jurisdiction=%s'; $args[] = $jurisdiction; }
        if ($status !== '') { $sql .= ' AND status=%s'; $args[] = $status; }
        $sql .= ' ORDER BY payer,plan_name LIMIT %d';
        $args[] = $limit;
        $rows = $this->db->get_results($this->db->prepare($sql, ...$args), ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    public function createDmeCategory(array $in): string
    {
        $id = wp_generate_uuid4();
        $this->db->insert($this->db->prefix . 'atlas_dme_categories', [
            'id' => $id,
            'slug' => sanitize_title((string) ($in['slug'] ?? '')),
            'label' => $this->txt($in, 'label', 191),
            'description' => $this->txt($in, 'description', 2000),
            'status' => sanitize_key((string) ($in['status'] ?? 'active')),
            'created_at' => gmdate('Y-m-d H:i:s'),
        ], ['%s','%s','%s','%s','%s','%s']);
        return $id;
    }

    public function dmeCategories(int $limit = 100, array $criteria = []): array
    {
        $t = $this->db->prefix . 'atlas_dme_categories';
        $limit = max(1, min(200, $limit));
        $status = sanitize_key((string) ($criteria['status'] ?? 'active'));
        $rows = $this->db->get_results($this->db->prepare("SELECT * FROM `{$t}` WHERE status=%s ORDER BY label LIMIT %d", $status, $limit), ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    public function reviewRequirement(string $id, ?string $org, string $status, int $user): bool
    {
        if (! in_array($status, ['draft','in_review','published','retired'], true)) { return false; }
        $where = ['id' => $id];
        $formats = ['%s'];
        if ($org !== null) { $where['organization_id'] = $org; $formats[] = '%s'; }
        return $this->db->update($this->db->prefix . 'atlas_payer_requirements', ['review_status' => $status, 'reviewed_by' => $user, 'reviewed_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')], $where, ['%s','%d','%s','%s'], $formats) !== false;
    }

    public function markRequirementsForSourceReview(string $oldDocumentId, string $newDocumentId, ?string $org, int $user, string $reason): int
    {
        $r = $this->db->prefix . 'atlas_payer_requirements';
        $c = $this->db->prefix . 'atlas_extraction_candidates';
        $s = $this->db->prefix . 'atlas_source_sections';
        $now = gmdate('Y-m-d H:i:s');
        $sql = "UPDATE `{$r}` r INNER JOIN `{$c}` c ON c.id=r.source_candidate_id INNER JOIN `{$s}` s ON s.id=c.source_section_id SET r.source_review_status='needs_source_review',r.source_review_document_id=%s,r.source_review_reason=%s,r.source_reviewed_at=NULL,r.reviewed_by=%d,r.updated_at=%s WHERE s.source_document_id=%s AND (r.organization_id IS NULL OR r.organization_id=%s)";
        $this->db->query($this->db->prepare($sql, $newDocumentId, substr($reason, 0, 2000), $user, $now, $oldDocumentId, (string) $org));
        return max(0, (int) $this->db->rows_affected);
    }

    public function clearRequirementSourceReview(string $requirementId, ?string $org, int $user, string $note): bool
    {
        $where = ['id' => $requirementId];
        $formats = ['%s'];
        if ($org !== null) { $where['organization_id'] = $org; $formats[] = '%s'; }
        return $this->db->update($this->db->prefix . 'atlas_payer_requirements', ['source_review_status' => 'current', 'source_review_reason' => substr($note, 0, 1000), 'source_reviewed_at' => gmdate('Y-m-d H:i:s'), 'reviewed_by' => $user, 'updated_at' => gmdate('Y-m-d H:i:s')], $where, ['%s','%s','%s','%d','%s'], $formats) !== false;
    }

    public function createRequirementChangeProposal(string $requirementId, ?string $org, int $user, array $in): string
    {
        $table = $this->db->prefix . 'atlas_requirement_change_proposals';
        $id = wp_generate_uuid4();
        $now = gmdate('Y-m-d H:i:s');
        $json = wp_json_encode(is_array($in['proposed_changes'] ?? null) ? $in['proposed_changes'] : []);
        if (! is_string($json)) { $json = '{}'; }
        $this->db->insert($table, [
            'id' => $id,
            'requirement_id' => $requirementId,
            'organization_id' => $org,
            'source_document_id' => $this->txt($in, 'source_document_id', 36) ?: null,
            'proposal_status' => sanitize_key((string) ($in['proposal_status'] ?? 'draft')),
            'proposal_reason' => $this->txt($in, 'proposal_reason', 2000),
            'proposed_changes_json' => $json,
            'created_by' => $user,
            'created_at' => $now,
            'updated_at' => $now,
        ], ['%s','%s','%s','%s','%s','%s','%s','%d','%s','%s']);
        return $id;
    }

    public function requirementChangeProposals(string $requirementId, ?string $org, int $limit = 25): array
    {
        $table = $this->db->prefix . 'atlas_requirement_change_proposals';
        $limit = max(1, min(100, $limit));
        $rows = $this->db->get_results($this->db->prepare("SELECT * FROM `{$table}` WHERE requirement_id=%s AND (organization_id IS NULL OR organization_id=%s) ORDER BY updated_at DESC LIMIT %d", $requirementId, (string) $org, $limit), ARRAY_A);
        $out = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $changes = json_decode((string) ($row['proposed_changes_json'] ?? '{}'), true);
            $row['proposed_changes'] = is_array($changes) ? $changes : [];
            $out[] = $row;
        }
        return $out;
    }

    public function findRequirementChangeProposal(string $proposalId, ?string $org): ?array
    {
        $table = $this->db->prefix . 'atlas_requirement_change_proposals';
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM `{$table}` WHERE id=%s AND (organization_id IS NULL OR organization_id=%s) LIMIT 1", $proposalId, (string) $org), ARRAY_A);
        if (! is_array($row)) { return null; }
        $changes = json_decode((string) ($row['proposed_changes_json'] ?? '{}'), true);
        $row['proposed_changes'] = is_array($changes) ? $changes : [];
        return $row;
    }

    public function applyRequirementChangeProposal(string $proposalId, string $requirementId, ?string $org, int $user, array $changes): bool
    {
        $requirementData = [];
        $formats = [];
        foreach (['payer','plan_name','topic','dme_category_slug','jurisdiction','requirement_type','prior_authorization_status','frequency_limit','replacement_interval','coverage_criteria_text','requirement_text','effective_date','expires_at'] as $field) {
            if (array_key_exists($field, $changes)) { $requirementData[$field] = $changes[$field]; $formats[] = '%s'; }
        }
        if (array_key_exists('required_forms', $changes)) {
            $json = wp_json_encode(is_array($changes['required_forms']) ? $changes['required_forms'] : []);
            $requirementData['required_forms_json'] = is_string($json) ? $json : '[]';
            $formats[] = '%s';
        }
        if ($requirementData === []) { return false; }
        $requirementData['reviewed_by'] = $user;
        $requirementData['reviewed_at'] = gmdate('Y-m-d H:i:s');
        $requirementData['updated_at'] = gmdate('Y-m-d H:i:s');
        $formats[] = '%d';
        $formats[] = '%s';
        $formats[] = '%s';
        $where = ['id' => $requirementId];
        $whereFormats = ['%s'];
        if ($org !== null) { $where['organization_id'] = $org; $whereFormats[] = '%s'; }
        $updated = $this->db->update($this->db->prefix . 'atlas_payer_requirements', $requirementData, $where, $formats, $whereFormats);
        if ($updated === false) { return false; }
        $proposalWhere = ['id' => $proposalId, 'requirement_id' => $requirementId];
        $proposalFormats = ['%s', '%s'];
        if ($org !== null) { $proposalWhere['organization_id'] = $org; $proposalFormats[] = '%s'; }
        return $this->db->update($this->db->prefix . 'atlas_requirement_change_proposals', ['proposal_status' => 'applied', 'applied_by' => $user, 'applied_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')], $proposalWhere, ['%s','%d','%s','%s'], $proposalFormats) !== false;
    }

    public function rejectRequirementChangeProposal(string $proposalId, string $requirementId, ?string $org, int $user, string $note): bool
    {
        $where = ['id' => $proposalId, 'requirement_id' => $requirementId];
        $formats = ['%s', '%s'];
        if ($org !== null) { $where['organization_id'] = $org; $formats[] = '%s'; }
        return $this->db->update($this->db->prefix . 'atlas_requirement_change_proposals', ['proposal_status' => 'rejected', 'rejection_note' => substr($note, 0, 2000), 'rejected_by' => $user, 'rejected_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')], $where, ['%s','%s','%d','%s','%s'], $formats) !== false;
    }

    public function changeProposalQueue(?string $org, int $limit = 50, array $criteria = []): array
    {
        $p = $this->db->prefix . 'atlas_requirement_change_proposals';
        $r = $this->db->prefix . 'atlas_payer_requirements';
        $limit = max(1, min(100, $limit));
        $status = sanitize_key((string) ($criteria['proposal_status'] ?? 'draft'));
        $sql = "SELECT p.*,r.payer,r.plan_name,r.topic,r.review_status,r.source_review_status FROM `{$p}` p INNER JOIN `{$r}` r ON r.id=p.requirement_id WHERE (p.organization_id IS NULL OR p.organization_id=%s)";
        $args = [(string) $org];
        if ($status !== '') { $sql .= ' AND p.proposal_status=%s'; $args[] = $status; }
        $sql .= ' ORDER BY p.updated_at DESC LIMIT %d';
        $args[] = $limit;
        $rows = $this->db->get_results($this->db->prepare($sql, ...$args), ARRAY_A);
        $out = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $changes = json_decode((string) ($row['proposed_changes_json'] ?? '{}'), true);
            $row['proposed_changes'] = is_array($changes) ? $changes : [];
            $out[] = $row;
        }
        return $out;
    }

    public function appendRequirementRevision(string $requirementId, ?string $org, int $user, string $revisionType, array $snapshot): string
    {
        $table = $this->db->prefix . 'atlas_payer_requirement_revisions';
        $next = (int) $this->db->get_var($this->db->prepare("SELECT COALESCE(MAX(revision_number),0)+1 FROM `{$table}` WHERE requirement_id=%s", $requirementId));
        $json = wp_json_encode($snapshot);
        if (! is_string($json)) { $json = '{}'; }
        $id = wp_generate_uuid4();
        $this->db->insert($table, [
            'id' => $id,
            'requirement_id' => $requirementId,
            'organization_id' => $org,
            'revision_number' => $next,
            'revision_type' => sanitize_key($revisionType),
            'snapshot_json' => $json,
            'created_by' => $user,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ], ['%s','%s','%s','%d','%s','%s','%d','%s']);
        return $id;
    }

    public function requirementRevisions(string $requirementId, ?string $org, int $limit = 25): array
    {
        $table = $this->db->prefix . 'atlas_payer_requirement_revisions';
        $limit = max(1, min(100, $limit));
        $rows = $this->db->get_results($this->db->prepare("SELECT * FROM `{$table}` WHERE requirement_id=%s AND (organization_id IS NULL OR organization_id=%s) ORDER BY revision_number DESC LIMIT %d", $requirementId, (string) $org, $limit), ARRAY_A);
        $out = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $snapshot = json_decode((string) ($row['snapshot_json'] ?? '{}'), true);
            $row['snapshot'] = is_array($snapshot) ? $snapshot : [];
            $out[] = $row;
        }
        return $out;
    }

    public function findRequirement(string $id, ?string $org): ?array
    {
        $t = $this->db->prefix . 'atlas_payer_requirements';
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM `{$t}` WHERE id=%s AND (organization_id IS NULL OR organization_id=%s) LIMIT 1", $id, (string) $org), ARRAY_A);
        return is_array($row) ? $row : null;
    }

    public function findRequirementSource(string $candidateId, ?string $org): ?array
    {
        $c = $this->db->prefix . 'atlas_extraction_candidates';
        $s = $this->db->prefix . 'atlas_source_sections';
        $d = $this->db->prefix . 'atlas_source_documents';
        $sql = "SELECT c.id candidate_id,c.statement,c.status candidate_status,s.id section_id,s.page_number,s.section_label,s.text_excerpt,s.anchor,d.id document_id,d.title document_title,d.publisher,d.source_family_key,d.source_version_label,d.supersedes_document_id,d.source_url,d.original_filename,d.mime_type,d.file_size_bytes,d.checksum,d.preserved_at,d.effective_date,d.retrieved_at FROM `{$c}` c INNER JOIN `{$s}` s ON s.id=c.source_section_id INNER JOIN `{$d}` d ON d.id=s.source_document_id WHERE c.id=%s AND (c.organization_id IS NULL OR c.organization_id=%s) AND (d.organization_id IS NULL OR d.organization_id=%s) LIMIT 1";
        $row = $this->db->get_row($this->db->prepare($sql, $candidateId, (string) $org, (string) $org), ARRAY_A);
        return is_array($row) ? $row : null;
    }

    public function requirementsForSourceDocument(string $documentId, ?string $org, int $limit = 50): array
    {
        $r = $this->db->prefix . 'atlas_payer_requirements';
        $c = $this->db->prefix . 'atlas_extraction_candidates';
        $s = $this->db->prefix . 'atlas_source_sections';
        $limit = max(1, min(100, $limit));
        $sql = "SELECT r.*,c.id candidate_id,c.statement candidate_statement,s.page_number,s.section_label FROM `{$r}` r INNER JOIN `{$c}` c ON c.id=r.source_candidate_id INNER JOIN `{$s}` s ON s.id=c.source_section_id WHERE s.source_document_id=%s AND (r.organization_id IS NULL OR r.organization_id=%s) ORDER BY r.updated_at DESC LIMIT %d";
        $rows = $this->db->get_results($this->db->prepare($sql, $documentId, (string) $org, $limit), ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    public function saveChecklistState(string $requirementId, ?string $org, string $hash, string $label, string $status, string $notes, int $userId): bool
    {
        $table = $this->db->prefix . 'atlas_requirement_checklist_state';
        $existing = $this->db->get_var($this->db->prepare("SELECT id FROM `{$table}` WHERE requirement_id=%s AND checklist_hash=%s LIMIT 1", $requirementId, $hash));
        $data = ['requirement_id'=>$requirementId,'organization_id'=>$org,'checklist_hash'=>$hash,'label'=>substr($label,0,1000),'status'=>$status,'notes'=>substr($notes,0,1000),'updated_by'=>$userId,'updated_at'=>gmdate('Y-m-d H:i:s')];
        if (is_string($existing) && $existing !== '') { return $this->db->update($table, $data, ['id'=>$existing], ['%s','%s','%s','%s','%s','%s','%d','%s'], ['%s']) !== false; }
        $data['id'] = wp_generate_uuid4();
        return $this->db->insert($table, $data, ['%s','%s','%s','%s','%s','%s','%d','%s','%s']) !== false;
    }

    public function checklistState(array $requirementIds, ?string $org): array
    {
        $ids = array_values(array_filter(array_map('strval', $requirementIds)));
        if ($ids === []) { return []; }
        $table = $this->db->prefix . 'atlas_requirement_checklist_state';
        $placeholders = implode(',', array_fill(0, count($ids), '%s'));
        $rows = $this->db->get_results($this->db->prepare("SELECT * FROM `{$table}` WHERE requirement_id IN ({$placeholders}) AND (organization_id IS NULL OR organization_id=%s)", ...array_merge($ids, [(string) $org])), ARRAY_A);
        $out = [];
        foreach (is_array($rows) ? $rows : [] as $row) { $out[(string)$row['requirement_id'] . ':' . (string)$row['checklist_hash']] = $row; }
        return $out;
    }

    public function documents(?string $org, int $limit = 25): array { return $this->rows('atlas_source_documents', $org, $limit, 'created_at'); }
    public function candidates(?string $org, int $limit = 25): array { return $this->rows('atlas_extraction_candidates', $org, $limit, 'created_at'); }

    public function requirements(?string $org, int $limit = 25, array $criteria = []): array
    {
        $t = $this->db->prefix . 'atlas_payer_requirements';
        $limit = max(1, min(100, $limit));
        $payer = $this->txt($criteria, 'payer', 191);
        $topic = $this->txt($criteria, 'topic', 191);
        $dmeSlug = sanitize_title((string) ($criteria['dme_category_slug'] ?? ''));
        $status = sanitize_key((string) ($criteria['status'] ?? ''));
        $sourceReview = sanitize_key((string) ($criteria['source_review_status'] ?? ''));
        $sql = "SELECT * FROM `{$t}` WHERE (organization_id IS NULL OR organization_id=%s)";
        $args = [(string) $org];
        if ($payer !== '') { $sql .= ' AND payer LIKE %s'; $args[] = '%' . $this->db->esc_like($payer) . '%'; }
        if ($topic !== '') { $sql .= ' AND topic LIKE %s'; $args[] = '%' . $this->db->esc_like($topic) . '%'; }
        if ($dmeSlug !== '') { $sql .= ' AND dme_category_slug=%s'; $args[] = $dmeSlug; }
        $plan = $this->txt($criteria, 'plan_name', 191);
        $jurisdiction = $this->txt($criteria, 'jurisdiction', 120);
        $type = sanitize_key((string) ($criteria['requirement_type'] ?? ''));
        $priorAuth = sanitize_key((string) ($criteria['prior_authorization_status'] ?? ''));
        if ($plan !== '') { $sql .= ' AND (plan_name=%s OR plan_name=' . "''" . ')'; $args[] = $plan; }
        if ($jurisdiction !== '') { $sql .= ' AND (jurisdiction=%s OR jurisdiction=' . "''" . ')'; $args[] = $jurisdiction; }
        if ($type !== '') { $sql .= ' AND requirement_type=%s'; $args[] = $type; }
        if ($priorAuth !== '') { $sql .= ' AND prior_authorization_status=%s'; $args[] = $priorAuth; }
        if ($status !== '') { $sql .= ' AND review_status=%s'; $args[] = $status; }
        if ($sourceReview !== '') { $sql .= ' AND source_review_status=%s'; $args[] = $sourceReview; }
        $sql .= ' ORDER BY updated_at DESC LIMIT %d';
        $args[] = $limit;
        $rows = $this->db->get_results($this->db->prepare($sql, ...$args), ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    public function summary(?string $org): array
    {
        $out = [];
        foreach (['atlas_source_documents' => 'extraction_status', 'atlas_extraction_candidates' => 'status', 'atlas_payer_requirements' => 'review_status'] as $table => $field) {
            $t = $this->db->prefix . $table;
            $rows = $this->db->get_results($this->db->prepare("SELECT {$field} label,COUNT(*) total FROM `{$t}` WHERE organization_id IS NULL OR organization_id=%s GROUP BY {$field}", (string) $org), ARRAY_A);
            foreach (is_array($rows) ? $rows : [] as $row) { $out[$table . '.' . (string) $row['label']] = (int) $row['total']; }
        }
        return $out;
    }

    private function rows(string $table, ?string $org, int $limit, string $order): array
    {
        $t = $this->db->prefix . $table;
        $limit = max(1, min(100, $limit));
        $rows = $this->db->get_results($this->db->prepare("SELECT * FROM `{$t}` WHERE organization_id IS NULL OR organization_id=%s ORDER BY {$order} DESC LIMIT %d", (string) $org, $limit), ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    private function txt(array $in, string $key, int $len): string { return substr(trim((string) ($in[$key] ?? '')), 0, $len); }
    private function date(mixed $v): ?string { $s = trim((string) $v); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) === 1 ? $s : null; }
}
