# Payer Requirement Revisions slice 0.74.0

Atlas 0.74.0 adds immutable revision snapshots for internal payer/DME requirements.

Each requirement creation and review-state change appends a row to `atlas_payer_requirement_revisions` with:

- requirement and organization scope;
- monotonic revision number per requirement;
- revision type such as `created` or `status_published`;
- JSON snapshot of the visible requirement fields and readiness state;
- actor and timestamp.

The current `atlas_payer_requirements` row remains the fast current-state record. The revision table is an append-only audit/history ledger for review, comparison, and future supersede workflows.
