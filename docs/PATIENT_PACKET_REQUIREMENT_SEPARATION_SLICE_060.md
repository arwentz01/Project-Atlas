# Patient Packet / Requirement Separation Slice 0.60

This slice separates patient-facing packet materials from internal payer requirement work.

## Patient packet guardrails

- Patient packets accept patient-facing Resources and patient-facing authored instructions.
- New packet items with type `requirement` are rejected by `PacketService`.
- Existing historical requirement references in packets are not rendered as patient-facing content.
- Packet builder language now describes packets as patient-facing education/instruction collections.

## Internal documentation lane

- Payer requirements remain in the Source Workspace as internal documentation guidance.
- `SourceWorkspaceService::documentationChecklist()` converts published requirements into staff-facing checklist items.
- `SourceWorkspaceService::requirementEvidence()` returns requirement text, checklist items, and source evidence.
- REST routes:
  - `GET /wp-json/atlas/v1/documentation-checklists`
  - `GET /wp-json/atlas/v1/payer-requirements/{id}/evidence`
- The Sources admin page now shows an internal documentation checklist and requirement evidence detail.

## Migration impact

No migration is required. The slice reuses source document, source section, extraction candidate, and payer requirement tables.
