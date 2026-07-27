# Atlas Architecture Principles

## Core Request Flow

All application actions should follow this path:

`UI -> REST controller -> application service -> repository -> storage`

Templates and controllers must not contain business rules. Business services must not issue ad hoc SQL.

## Module Boundaries

- Modules communicate through explicit interfaces and application services.
- One module must not query another module's tables directly.
- Cross-module events should be explicit and auditable.
- WordPress APIs are adapters around Atlas behavior, not the location of Atlas behavior.

## Storage Rules

- WordPress posts and taxonomies are appropriate for editorial, revision-controlled content.
- Custom tables are appropriate for organizations, memberships, workflows, provenance, verifications, and audit events.
- Only repositories access persistence directly.
- Schema changes are versioned migrations committed to Git.
- Records with operational significance use stable identifiers and soft deletion where appropriate.

## Security Rules

- Authorization requires both a capability check and an object-scope check.
- Organization membership must be verified for every tenant-owned action.
- Tenant identifiers supplied by clients are never trusted without server-side validation.
- Inputs are validated and sanitized; outputs are escaped for their rendering context.
- Sensitive actions create audit records.

## Extensibility Rules

The following capabilities must be represented by interfaces even when Atlas has only one implementation:

- Search
- Branding
- Document import
- Packet rendering
- Job queue
- Notifications
- Audit logging

## Hosting Constraint

The initial deployment may run on shared hosting. Heavy document extraction, OCR, embeddings, crawling, or long-running AI processing must remain separable from the WordPress request lifecycle.
