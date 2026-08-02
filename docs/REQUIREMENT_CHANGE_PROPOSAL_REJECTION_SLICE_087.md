# Requirement change proposal rejection slice 0.87

Atlas 0.87 completes the first proposal lifecycle loop with explicit rejection.

Staff can reject a draft payer requirement change proposal with a required non-PHI decision note. Rejected proposals are retained for audit context and cannot later be applied.

The slice adds:

- rejection metadata (`rejection_note`, `rejected_by`, `rejected_at`);
- `POST /atlas/v1/payer-requirements/{id}/change-proposals/{proposal_id}/reject`;
- service validation that only draft proposals can be rejected;
- service validation that rejected proposals cannot be applied later;
- audit event `payer_requirement.change_proposal_rejected`.
