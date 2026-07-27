# ADR-003: Use HTML as the Canonical Printable-Resource Format

**Status:** Accepted

## Context

Atlas must produce patient handouts, logs, packets, and organization-branded materials that can be updated, translated, previewed, printed, and exported.

## Decision

Atlas will store structured content and render accessible HTML/CSS as the canonical presentation. PDF files are generated outputs, not the editable source of truth.

## Consequences

- Content can be updated without recreating static files.
- Organization branding can be applied at render time.
- Browser printing can support the initial shared-hosting deployment.
- A future server-side PDF renderer can be added behind a rendering interface.
- Print styles and accessibility require deliberate testing.

## Alternatives Considered

- Authoring static PDFs: rejected because updates, localization, and branding become cumbersome.
- Office-document templates: rejected as the primary format because rendering consistency and automation are weaker.
