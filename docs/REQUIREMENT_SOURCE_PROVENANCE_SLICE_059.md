# Requirement Source Provenance Slice 0.59

This slice enriches packet previews with source-anchored payer requirement provenance.

## Vertical behavior

- Packet previews still resolve packet items through `PacketService`.
- Requirement items now ask `PacketItemResolver` for display-ready content and provenance.
- `DefaultPacketItemResolver` asks `SourceWorkspaceRepository` for a requirement and its reviewed source trail.
- `WordPressSourceWorkspaceRepository` resolves the existing chain:
  `atlas_payer_requirements.source_candidate_id` -> `atlas_extraction_candidates` -> `atlas_source_sections` -> `atlas_source_documents`.
- Packet print views can now show source document, page/section/anchor, source dates, excerpt, and reviewed extraction statement.

## Architectural boundary

Packets do not query source tables directly. The source workspace repository owns the storage shape, while packet rendering consumes an interface-level provenance record.

## Migration impact

No database migration is required. The slice uses fields introduced in migrations `0015` and `0016`.

## Remaining limitation

The provenance chain is source-candidate anchored. A later slice can add immutable quote-level anchors or multi-source evidence when a requirement is synthesized from more than one source section.
