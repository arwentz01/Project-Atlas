# Organizations Foundation

Atlas 0.4.0 introduces the minimum organization boundary needed for tenant-aware product work. It does not implement the full Organizations feature.

## Domain and storage

An organization uses a UUIDv4 identifier, canonical slug, display name, explicit status, and timestamps. A membership connects one WordPress user to one organization, carries an explicit status and JSON-encoded role list, and is protected by a database uniqueness constraint on organization and user.

The migration creates `{$wpdb->prefix}atlas_organizations` and `{$wpdb->prefix}atlas_organization_memberships`. It uses WordPress charset/collation, InnoDB, MySQL 5.7-compatible statements, separate index mutations, and verifies tables, columns, primary keys, uniqueness, lookup indexes, engine, and collation. Existing incompatible objects stop the migration rather than being accepted or suppressed.

Foreign keys are intentionally deferred. Memberships refer to WordPress users across the Atlas/WordPress boundary, and destructive lifecycle semantics have not been approved. Application authorization and scoped queries remain mandatory regardless of future constraints.

## Context and authorization

`CurrentOrganizationResolver` returns an active organization only when a user has exactly one active membership. Zero memberships, multiple memberships, suspended organizations, and unauthenticated users produce no implicit context. A future organization switch action must persist and validate an explicit server-side context; browser state alone will never establish ownership.

`OrganizationAuthorization` requires an authenticated user plus either an active matching membership or an explicit platform-administrator decision. Callers must perform the WordPress capability check separately, allowing tests to distinguish platform permission from object scope.

## REST API

`GET /wp-json/atlas/v1/organizations/current` requires authentication and `atlas_access`. It does not accept an organization identifier. It returns the server-resolved organization or the stable `atlas_organization_context_unavailable` error with HTTP 404.

## Deferred

- Organization creation and editing.
- Invitations and email delivery.
- Membership mutation and role assignment.
- Multi-organization switching.
- Branding.
- Deletion, archiving workflows, and retention policy.
- Cross-organization sharing.

## Manual verification

1. Run migrations twice on clean MySQL 5.7 and MySQL 8.0 databases.
2. Repeat with only the organizations table present and confirm membership creation resumes.
3. Repeat with required tables and missing indexes and confirm each index is added once.
4. Introduce an incompatible existing column or index and confirm migration `0002` fails without being recorded complete.
5. Create users in Organization A and Organization B and call the current endpoint as each.
6. Confirm a user in A cannot resolve or access B.
7. Confirm a user with two active memberships receives no inferred organization.
8. Confirm a subscriber without `atlas_access` is denied directly.
