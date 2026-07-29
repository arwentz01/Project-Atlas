# Organization onboarding

Atlas 0.12.0 adds capability-protected `POST /wp-json/atlas/v1/organizations`. The request accepts `name` and `slug` plus a required `Idempotency-Key` header. A successful first request atomically creates the organization, assigns the initiating user as an active organization administrator, and records the operation. An identical retry returns the original identifiers; reuse of a key for different input returns a conflict.

The endpoint requires `atlas_manage_organizations`. Database uniqueness protects organization slugs, memberships, and operation keys under concurrent requests. Atlas does not infer organization access from a submitted organization identifier.
