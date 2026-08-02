# Requirement proposal decision slices 0.99-1.03

Atlas 0.99 through 1.03 tightens the staff decision workflow for payer requirement change proposals:

- 0.99 adds a dedicated change proposal workspace payload with the current requirement, source comparison, diff, and decision guidance.
- 1.00 exposes the proposal workspace through REST at `/wp-json/atlas/v1/payer-requirements/{id}/change-proposals/{proposal_id}/workspace`.
- 1.01 flags stale no-op proposals when the current requirement already matches the proposed values.
- 1.02 blocks stale no-op proposal application so staff do not create misleading revision history.
- 1.03 adds queue intelligence: changed-field counts, per-item warning payloads, and needs-attention counts.

All payloads remain internal-only and explicitly unsafe for patient packet output.
