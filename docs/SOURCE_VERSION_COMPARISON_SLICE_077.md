# Source Version Comparison Slice 0.77

Atlas 0.77 adds an internal, review-only comparison path for source documents.

## What changed

- Source documents can now carry a `source_family_key`, `source_version_label`, and `supersedes_document_id`.
- Newer source documents can be compared against the source they supersede.
- Stored page text is compared by page number and checksum.
- Requirements sourced from the superseded document are returned as impacted review targets.
- The Sources admin shows the first available comparison summary with changed pages and requirement review links.

## Guardrails

- Comparisons are marked `internal_only` and `patient_packet_safe=false`.
- Atlas does not automatically rewrite payer requirements from a changed source.
- The result is a staff review queue input: changed pages plus requirements that should be reviewed before updated guidance is published.

## Deferred

- Automatic source monitoring/crawling.
- OCR/PDF extraction automation.
- Requirement diff authoring or automatic revision proposals.
- Bulk comparison selection UI across all source families.
