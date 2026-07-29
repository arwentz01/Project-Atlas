# Operational Workflows

Atlas 0.10.0 adds immutable, versioned, tenant-aware operational workflow reads. A Workflow has a stable identity and current published version. Ordered steps carry an instruction, explicit requirement, optional warning, and optional related Resource. A workflow version may retain the Resource Version from which its requirements were reviewed.

`GET /wp-json/atlas/v1/workflows/{uuid}` requires authentication and `atlas_access`. The repository applies publication and organization scope in SQL; missing, draft, and cross-organization workflows share one not-found response. Steps are ordered by database-constrained position and stable identifier.

Migration `0006` creates workflows, immutable versions, and steps with unique scope/slug, workflow/version, and version/position constraints. Secondary indexes are separately applied and verified for partial-DDL recovery. Foreign keys remain deferred until workflow deletion, supersession, and cross-module Resource retention semantics are approved.

## Deferred builder work

- Workflow authoring and publication actions.
- Conditional branches.
- Form attachments.
- Assignments and completion tracking.
- Source-change notifications.
- A production payer workflow; no clinical or coverage claim is seeded by this migration.

## Manual verification

1. Insert published global, Organization A, Organization B, and draft workflow fixtures.
2. Confirm global access, exact tenant isolation, and draft exclusion through direct REST requests.
3. Confirm ordered steps, warnings, requirements, source-version identity, and related-resource identity.
4. Confirm invalid IDs, missing resources, unauthorized users, and invalid methods return intentional errors.
5. Run migration `0006` twice and from partial tables/indexes on MySQL 5.7 and 8.0.
