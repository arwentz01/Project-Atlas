# Prior Authorization Prep Workspace slice 0.70.0

This larger release band turns prior authorization workups into a visible staff workspace.

## Added

- Service-level `priorAuthorizationPrepWorkspace()` summary.
- Sources admin panel for **Prior authorization prep workspace**.
- Workspace cards for:
  - required workups
  - ready for submission prep
  - blocked workups
  - task preview count
- Staff task preview derived from prior authorization workups.
- Blocked workup list with missing gaps and evidence links.
- Filter-preserving export link for required prior authorization summaries.

## Boundary

This is still internal, non-PHI preparation support. It does not create patient records, does not submit to payers, and is not patient packet content.
