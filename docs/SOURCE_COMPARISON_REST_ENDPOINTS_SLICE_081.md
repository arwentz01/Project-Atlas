# Source Comparison REST Endpoints Slice 0.81

Atlas 0.81 adds explicit REST endpoints for source comparison and source-impact review actions.

## Endpoints

- `GET /wp-json/atlas/v1/sources/documents/{id}/comparison`
  - Returns the internal source version comparison for a source document.
  - Requires `atlas_access`.

- `POST /wp-json/atlas/v1/sources/documents/{id}/impact-review`
  - Opens source-impact review for requirements linked to the superseded source.
  - Requires `atlas_review_extractions`.

- `DELETE /wp-json/atlas/v1/payer-requirements/{id}/source-review`
  - Clears a requirement's source-review flag after human review.
  - Requires `atlas_review_extractions`.

## Guardrails

- The comparison response remains internal-only and not patient-packet-safe.
- Mutating endpoints require review capability.
- Requirements are flagged or cleared; Atlas does not rewrite requirement text automatically.
