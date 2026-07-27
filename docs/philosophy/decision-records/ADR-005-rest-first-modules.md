# ADR-005: Expose Module Capabilities Through REST APIs

**Status:** Accepted

## Context

Atlas will begin inside WordPress but may later support a custom frontend, mobile client, external workers, or integrations. Coupling all behavior to WordPress admin forms would make those paths expensive.

## Decision

Significant module capabilities will be exposed through versioned Atlas REST endpoints. Interfaces may call those endpoints rather than duplicating business rules in presentation code.

## Consequences

- The application UI, future clients, and integrations can use the same behavior.
- Authorization must be enforced server-side for every route.
- Endpoints require stable contracts, validation, error formats, and versioning discipline.
- Not every internal method needs an endpoint; APIs represent meaningful product actions.

## Alternatives Considered

- Traditional WordPress forms only: rejected because behavior would be trapped in one interface.
- GraphQL initially: deferred because REST is simpler and well-supported within WordPress for the first release.
