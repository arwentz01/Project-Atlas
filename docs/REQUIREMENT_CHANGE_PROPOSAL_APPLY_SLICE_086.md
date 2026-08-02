# Requirement change proposal apply slice 0.86

Atlas 0.86 applies draft requirement change proposals through a controlled service path.

The slice adds:

- applied proposal metadata (`applied_by`, `applied_at`);
- a REST apply endpoint at `POST /atlas/v1/payer-requirements/{id}/change-proposals/{proposal_id}/apply`;
- requirement mutation limited to proposed fields;
- immutable `change_proposal_applied` revision snapshots;
- audit event `payer_requirement.change_proposal_applied`.

Drafting remains separate from applying, so staff can review proposed changes before updating internal payer requirement guidance.
