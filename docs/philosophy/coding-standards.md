# Atlas Coding Standards

## General

- Target supported WordPress and PHP versions documented in the plugin README.
- Use strict namespacing under `Atlas\Platform`.
- Follow WordPress security practices while keeping domain logic framework-independent.
- Prefer small classes with one clear responsibility.
- New behavior requires tests at the narrowest useful level.

## Dependencies

- Controllers depend on services.
- Services depend on interfaces.
- Repositories implement persistence interfaces.
- Templates receive prepared view data and do not query storage.
- Modules do not access another module's tables directly.

## WordPress Boundaries

- Hooks are registered in module bootstrappers, not scattered through domain classes.
- WordPress globals and functions should be isolated in adapters where practical.
- All custom capabilities use the `atlas_` prefix.
- All REST routes use the `atlas/v1` namespace until an intentional version change.
- All custom tables use the active WordPress table prefix plus `atlas_`.

## Security

- Every write action requires nonce or REST authentication checks as appropriate.
- Every protected action requires capability and object-scope authorization.
- Validate and normalize inputs before business logic.
- Escape output at render time for the exact context.
- Never log PHI, credentials, authentication tokens, or uploaded document contents unnecessarily.

## Data

- Schema changes use ordered, repeatable migrations.
- Repositories are the only classes permitted to issue direct persistence queries.
- Operational records should use immutable IDs and timestamps.
- Destructive actions default to soft deletion where auditability matters.

## Quality

- Use descriptive names over abbreviations.
- Public interfaces and non-obvious decisions require documentation.
- Avoid speculative abstractions unless a known replacement boundary exists.
- Do not suppress errors without recording or handling them.
- A feature is not complete without authorization, validation, failure states, and audit behavior where applicable.
