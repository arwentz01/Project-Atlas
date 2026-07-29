# Source-Aware Resources Foundation

Atlas 0.5.0 introduces the read-only persistence and API boundary for source-aware resources. It does not yet implement search, authoring, publishing actions, or clinical content.

## Model

- `Resource` is the stable identity, ownership scope, organization boundary, type, and slug.
- `ResourceVersion` is an immutable numbered snapshot with structured JSON content, author, review status, effective date, review-due date, and change summary.
- `Source` stores publisher, document identity, source URL, effective/retrieved dates, and checksum.
- `Citation` connects one resource version to one precise source and optional page or section reference.

Published content is read through `PublishedResource`; draft and other nonpublished states are not returned by the public reader.

## Scope and tenant isolation

The repository accepts a server-resolved organization context. Platform and explicitly public resources can be read globally. Organization resources require an exact matching organization identifier in the SQL query. A missing context or a different organization returns the same not-found response, avoiding resource-existence disclosure.

`scope_key` provides concurrency-safe slug uniqueness for platform, public, and organization ownership. Organization scope keys contain the validated organization UUID. Personal and regional scopes are modeled but are not yet accepted by a creation service.

## Storage and migration

Migration `0003` creates resources, immutable versions, sources, and citations using WordPress charset/collation and MySQL 5.7-compatible statements. Secondary indexes are added and verified individually so interrupted DDL can resume. Existing tables, columns, engines, collations, defaults, and index definitions are verified before being accepted.

Foreign keys remain deferred until deletion, supersession, source ownership, and retention semantics are approved. Repository scope is the primary tenant boundary regardless of future constraints.

## REST API

`GET /wp-json/atlas/v1/resources/{uuid}` requires an authenticated user with `atlas_access`. It returns only the current published version accessible to the user's server-resolved organization. Invalid identifiers use `atlas_resource_invalid_id`; missing, unpublished, and cross-organization resources use the indistinguishable `atlas_resource_not_found` response.

## Deferred

- Search and filters.
- Resource creation and editing.
- Review and publication mutations.
- Version promotion and supersession.
- Source ingestion and file storage.
- Citation excerpts.
- Specialized subtype fields.
- Production clinical content.

## Manual verification

1. Run migration `0003` twice on clean MySQL 5.7 and MySQL 8.0.
2. Resume from each partially created table and missing secondary index.
3. Confirm incompatible existing columns and indexes fail without ledger completion.
4. Insert one platform resource, Organization A resource, Organization B resource, draft version, and published version.
5. Confirm A cannot retrieve B, B cannot retrieve A, and neither can retrieve drafts.
6. Confirm global published resources remain available to authenticated Atlas users without organization context.
7. Confirm malformed JSON is logged without exposing the body and returns not found.
8. Confirm unauthenticated and capability-denied direct requests fail.
