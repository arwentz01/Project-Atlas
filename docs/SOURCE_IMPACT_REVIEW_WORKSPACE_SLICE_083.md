# Source Impact Review Workspace Slice 0.83

Atlas 0.83 adds a dedicated source-impact review queue.

## What changed

- `SourceWorkspaceService::sourceImpactReviewQueue()` returns internal queue rows.
- Dashboard data includes `source_impact_queue`.
- `GET /wp-json/atlas/v1/source-impact-reviews` exposes the queue through REST.
- The Sources admin shows requirements currently marked `needs_source_review`.

## Guardrails

- Queue rows remain internal-only and not patient-packet-safe.
- Requirements are not rewritten automatically.

