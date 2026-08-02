# Source Impact Review Queue Slice 0.78

Atlas 0.78 turns source version comparison results into durable staff review work.

## What changed

- Payer requirements now have source-review metadata:
  - `source_review_status`
  - `source_review_document_id`
  - `source_review_reason`
  - `source_reviewed_at`
- A newer source comparison can open an impact review queue for requirements linked to the superseded source.
- Requirements can be filtered by `needs_source_review`.
- Review lanes now include a `needs_source_review` count.
- Staff can clear the source-review flag after completing human review.

## Guardrails

- This does not rewrite payer requirement text.
- This does not republish requirements automatically.
- The queue is internal-only and remains separate from patient packets.

## Deferred

- Rich side-by-side requirement redlines.
- Batch reassignment/ownership.
- Automatic watcher/crawler ingestion.
