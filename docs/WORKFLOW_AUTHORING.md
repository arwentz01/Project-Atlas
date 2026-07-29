# Workflow draft authoring

Atlas 0.14.0 adds capability-protected `POST /wp-json/atlas/v1/workflows/drafts`. A draft contains a bounded ordered list of 1–50 steps, with required titles, instructions, and explicit requirements; warnings and related Resource identifiers are optional. The workflow may retain the Resource Version used as its reviewed source.

Organization scope is resolved from the authenticated user's membership and cannot be supplied by the client. Platform scope additionally requires `atlas_manage_atlas`. A required idempotency key makes retries return the original draft identifiers. Workflow identity, immutable version, ordered steps, operation result, and audit event are persisted atomically.
