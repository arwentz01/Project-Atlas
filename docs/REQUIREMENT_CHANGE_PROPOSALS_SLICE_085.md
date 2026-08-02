# Requirement change proposals slice 0.85

Atlas 0.85 adds draft payer requirement change proposals for source-impact review work.

When a superseding source flags a published requirement for review, staff can now create a draft proposed update that records:

- the impacted payer requirement;
- the updated source document under review;
- the human proposal reason;
- proposed field-level requirement changes.

The proposal is intentionally non-mutating. Published requirement text and review status remain unchanged until a later approval/apply workflow is added.

Verification:

- source workspace service coverage proves proposal drafting, detail workspace exposure, and non-mutating behavior;
- REST inventory includes `GET` and `POST /atlas/v1/payer-requirements/{id}/change-proposals`;
- the release manifest includes migration `0025_requirement_change_proposals.php`.
