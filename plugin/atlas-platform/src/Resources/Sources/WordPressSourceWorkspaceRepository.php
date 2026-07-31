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
            'source_url' => $this->txt($in, 'source_url', 500),
            'document_type' => sanitize_key((string) ($in['document_type'] ?? 'policy')),
            'effective_date' => $this->date($in['effective_date'] ?? null),
            'retrieved_at' => gmdate('Y-m-d H:i:s'),
            'last_checked_at' => null,
            'checksum' => $this->txt($in, 'checksum', 128),
            'extraction_status' => 'queued',
            'notes' => $this->txt($in, 'notes', 2000),
            'created_by' => $user,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ], ['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s']);
        return $id;
    }

    public function updateDocumentStatus(string $id, ?string $org, string $status, string $notes = ''): bool
    {
        if (! in_array($status, ['queued','extracting','ready','stale','failed'], true)) { return false; }
        $where = ['id' => $id];
        $formats = ['%s'];
        if ($org !== null) { $where['organization_id'] = $org; $formats[] = '%s'; }
        return $this->db->update($this->db->prefix . 'atlas_source_documents', ['extraction_status' => $status, 'last_checked_at' => gmdate('Y-m-d H:i:s'), 'notes' => substr($notes, 0, 2000)], $where, ['%s','%s','%s'], $formats) !== false;
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

    public function reviewCandidate(string $id, string $status, int $user): bool
    {
        if (! in_array($status, ['approved','rejected','needs_changes'], true)) { return false; }
        return $this->db->update($this->db->prefix . 'atlas_extraction_candidates', ['status' => $status, 'reviewer_user_id' => $user, 'reviewed_at' => gmdate('Y-m-d H:i:s')], ['id' => $id], ['%s','%d','%s'], ['%s']) !== false;
    }

    public function createRequirement(?string $org, int $user, array $in): string
    {
        $id = wp_generate_uuid4();
        $now = gmdate('Y-m-d H:i:s');
        $this->db->insert($this->db->prefix . 'atlas_payer_requirements', [
            'id' => $id,
            'organization_id' => $org,
            'payer' => $this->txt($in, 'payer', 191),
            'plan_name' => $this->txt($in, 'plan_name', 191),
            'topic' => $this->txt($in, 'topic', 191),
            'jurisdiction' => $this->txt($in, 'jurisdiction', 120),
            'requirement_type' => sanitize_key((string) ($in['requirement_type'] ?? 'documentation')),
            'requirement_text' => $this->txt($in, 'requirement_text', 5000),
            'source_candidate_id' => $this->txt($in, 'source_candidate_id', 36),
            'review_status' => 'draft',
            'effective_date' => $this->date($in['effective_date'] ?? null),
            'expires_at' => $this->date($in['expires_at'] ?? null),
            'created_by' => $user,
            'created_at' => $now,
            'updated_at' => $now,
        ], ['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s']);
        return $id;
    }

    public function reviewRequirement(string $id, ?string $org, string $status, int $user): bool
    {
        if (! in_array($status, ['draft','in_review','published','retired'], true)) { return false; }
        $where = ['id' => $id];
        $formats = ['%s'];
        if ($org !== null) { $where['organization_id'] = $org; $formats[] = '%s'; }
        return $this->db->update($this->db->prefix . 'atlas_payer_requirements', ['review_status' => $status, 'reviewed_by' => $user, 'reviewed_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')], $where, ['%s','%d','%s','%s'], $formats) !== false;
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
        $sql = "SELECT c.id candidate_id,c.statement,c.status candidate_status,s.id section_id,s.page_number,s.section_label,s.text_excerpt,s.anchor,d.id document_id,d.title document_title,d.publisher,d.source_url,d.effective_date,d.retrieved_at FROM `{$c}` c INNER JOIN `{$s}` s ON s.id=c.source_section_id INNER JOIN `{$d}` d ON d.id=s.source_document_id WHERE c.id=%s AND (c.organization_id IS NULL OR c.organization_id=%s) AND (d.organization_id IS NULL OR d.organization_id=%s) LIMIT 1";
        $row = $this->db->get_row($this->db->prepare($sql, $candidateId, (string) $org, (string) $org), ARRAY_A);
        return is_array($row) ? $row : null;
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
        $status = sanitize_key((string) ($criteria['status'] ?? ''));
        $sql = "SELECT * FROM `{$t}` WHERE (organization_id IS NULL OR organization_id=%s)";
        $args = [(string) $org];
        if ($payer !== '') { $sql .= ' AND payer LIKE %s'; $args[] = '%' . $this->db->esc_like($payer) . '%'; }
        if ($topic !== '') { $sql .= ' AND topic LIKE %s'; $args[] = '%' . $this->db->esc_like($topic) . '%'; }
        if ($status !== '') { $sql .= ' AND review_status=%s'; $args[] = $status; }
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
