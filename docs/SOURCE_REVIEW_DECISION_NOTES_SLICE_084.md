# Source Review Decision Notes Slice 0.84

Atlas 0.84 requires a human review note when clearing source-impact review.

## What changed

- Clearing a source-review flag requires a non-empty, non-PHI note.
- The note is retained in `source_review_reason` after the flag is cleared.
- The clear action still appends an immutable `source_review_cleared` requirement revision.
- Admin and REST clear paths both accept the review note.

## Guardrails

- Notes pass through the existing PHI guard.
- Empty notes are rejected.
